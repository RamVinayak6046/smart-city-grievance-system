<?php
/**
 * AssignmentService — core automatic complaint routing & assignment algorithm.
 *
 * This is the SINGLE authoritative implementation of:
 *   1. Category → Department routing
 *   2. Ward eligibility check
 *   3. Availability / workload filtering
 *   4. Lowest-workload official selection
 *   5. Transactional complaint + assignment + history creation
 */

require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/AuditService.php';

class AssignmentService
{
    private PDO                 $pdo;
    private NotificationService $notif;
    private AuditService        $audit;

    public function __construct(PDO $pdo)
    {
        $this->pdo   = $pdo;
        $this->notif = new NotificationService($pdo);
        $this->audit = new AuditService($pdo);
    }

    // ── Public API ────────────────────────────────────────────────

    /**
     * Create a new complaint and attempt automatic assignment.
     * Wrapped in a database transaction.
     *
     * @param array $data  Validated complaint data
     * @param int   $citizenId
     * @return array  ['complaint_id', 'complaint_code', 'status', 'official_id', 'official_name']
     */
    public function createAndAssign(array $data, int $citizenId): array
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Resolve department from category
            $category   = $this->getCategory($data['category_id']);
            $deptId     = $category['department_id'] ?? null;

            // 2. Determine priority (category default unless overridden by keyword rules)
            $priority = $data['priority'] ?? $category['default_priority'] ?? 'medium';

            // 3. Generate unique complaint code (within transaction for safety)
            $code = $this->generateCode();

            // 4. Insert complaint record
            $stmt = $this->pdo->prepare(
                "INSERT INTO complaints
                    (complaint_code, citizen_id, category_id, department_id, ward_id,
                     title, description, address, latitude, longitude, priority, status)
                 VALUES
                    (:code, :cid, :catid, :deptid, :wardid,
                     :title, :desc, :addr, :lat, :lng, :priority, 'pending')"
            );
            $stmt->execute([
                ':code'    => $code,
                ':cid'     => $citizenId,
                ':catid'   => $data['category_id'],
                ':deptid'  => $deptId,
                ':wardid'  => $data['ward_id'],
                ':title'   => $data['title'],
                ':desc'    => $data['description'],
                ':addr'    => $data['address'] ?? null,
                ':lat'     => $data['latitude'] ?? null,
                ':lng'     => $data['longitude'] ?? null,
                ':priority'=> $priority,
            ]);
            $complaintId = (int) $this->pdo->lastInsertId();

            // 5. Attempt auto-assignment
            $official = null;
            if ($deptId) {
                $official = $this->selectOfficial($deptId, (int)$data['ward_id']);
            }

            if ($official) {
                // 6a. Assign: update complaint status + create assignment + update official
                $this->pdo->prepare(
                    "UPDATE complaints SET status = 'assigned' WHERE id = :id"
                )->execute([':id' => $complaintId]);

                $this->pdo->prepare(
                    "INSERT INTO complaint_assignments
                        (complaint_id, official_id, assigned_by, assignment_type, assignment_status)
                     VALUES (:cid, :oid, NULL, 'automatic', 'active')"
                )->execute([':cid' => $complaintId, ':oid' => $official['id']]);

                $this->pdo->prepare(
                    "UPDATE staff SET last_assigned_at = CURRENT_TIMESTAMP WHERE id = :id"
                )->execute([':id' => $official['id']]);

                $finalStatus = 'assigned';
            } else {
                $finalStatus = 'pending';
            }

            // 7. Record initial history
            $this->addHistory($complaintId, $citizenId, 'citizen', null, $finalStatus, 'Complaint submitted.');

            $this->pdo->commit();

            // 8. Notifications (outside transaction — non-fatal if they fail)
            try {
                $this->notif->complaintSubmitted($citizenId, $code, $complaintId, $official !== null);

                if ($official) {
                    $this->notif->complaintAssigned($official['id'], $code, $complaintId);
                } elseif ($deptId) {
                    $this->notif->unassignedComplaint($deptId, $code, $complaintId);
                    $this->notif->notifySuperAdmins(
                        'Unassigned Complaint: ' . $code,
                        "No eligible official found for complaint $code in department ID $deptId.",
                        $complaintId
                    );
                }
            } catch (Throwable) {}

            // 9. Audit
            $this->audit->log(
                $citizenId, 'citizen', 'complaint_submitted',
                'complaint', $complaintId,
                ['code' => $code, 'status' => $finalStatus, 'official_id' => $official['id'] ?? null]
            );

            return [
                'complaint_id'   => $complaintId,
                'complaint_code' => $code,
                'status'         => $finalStatus,
                'official_id'    => $official['id']    ?? null,
                'official_name'  => $official['name']  ?? null,
                'dept_id'        => $deptId,
            ];
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[AssignmentService] Transaction failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Manually reassign a complaint (by dept admin or super admin).
     */
    public function reassign(
        int    $complaintId,
        int    $newOfficialId,
        int    $assignedById,
        string $assignerRole,
        string $reason
    ): bool {
        $this->pdo->beginTransaction();
        try {
            // End existing active assignment
            $oldStmt = $this->pdo->prepare(
                "SELECT id, official_id FROM complaint_assignments
                 WHERE complaint_id = :cid AND assignment_status = 'active' LIMIT 1"
            );
            $oldStmt->execute([':cid' => $complaintId]);
            $oldAssignment = $oldStmt->fetch();

            if ($oldAssignment) {
                $this->pdo->prepare(
                    "UPDATE complaint_assignments
                     SET assignment_status = 'superseded', ended_at = CURRENT_TIMESTAMP
                     WHERE id = :id"
                )->execute([':id' => $oldAssignment['id']]);
            }

            // Get current status to build new status
            $curStatus = $this->pdo->prepare("SELECT status FROM complaints WHERE id = :id");
            $curStatus->execute([':id' => $complaintId]);
            $currentStatus = $curStatus->fetchColumn();

            $newStatus = in_array($currentStatus, ['pending', 'assigned']) ? 'assigned' : $currentStatus;

            // Create new assignment
            $this->pdo->prepare(
                "INSERT INTO complaint_assignments
                    (complaint_id, official_id, assigned_by, assignment_type, assignment_status, assignment_reason)
                 VALUES (:cid, :oid, :by, 'manual', 'active', :reason)"
            )->execute([
                ':cid'    => $complaintId,
                ':oid'    => $newOfficialId,
                ':by'     => $assignedById,
                ':reason' => $reason,
            ]);

            $this->pdo->prepare(
                "UPDATE complaints SET status = 'assigned', department_id = (
                    SELECT department_id FROM staff WHERE id = :oid
                 ) WHERE id = :cid"
            )->execute([':oid' => $newOfficialId, ':cid' => $complaintId]);

            $this->pdo->prepare(
                "UPDATE staff SET last_assigned_at = CURRENT_TIMESTAMP WHERE id = :id"
            )->execute([':id' => $newOfficialId]);

            $this->addHistory(
                $complaintId, $assignedById, $assignerRole,
                $currentStatus, 'assigned',
                'Manually reassigned. Reason: ' . $reason
            );

            $this->pdo->commit();

            // Notifications
            $code = $this->getComplaintCode($complaintId);
            if ($oldAssignment) {
                $this->notif->complaintReassigned(
                    (int) $oldAssignment['official_id'], $newOfficialId,
                    $code, $complaintId, $reason
                );
            } else {
                $this->notif->complaintAssigned($newOfficialId, $code, $complaintId);
            }

            return true;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            error_log('[AssignmentService::reassign] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Transition complaint status with validation.
     * Returns ['ok' => bool, 'error' => string]
     */
    public function transition(
        int    $complaintId,
        string $newStatus,
        int    $actorId,
        string $actorRole,
        string $remarks = ''
    ): array {
        $stmt = $this->pdo->prepare("SELECT status, department_id FROM complaints WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $complaintId]);
        $complaint = $stmt->fetch();

        if (!$complaint) return ['ok' => false, 'error' => 'Complaint not found.'];

        $currentStatus = $complaint['status'];

        if (!$this->isPermittedTransition($currentStatus, $newStatus, $actorRole)) {
            return ['ok' => false, 'error' => "Transition from '$currentStatus' to '$newStatus' is not permitted."];
        }

        $updates = [':status' => $newStatus, ':id' => $complaintId];
        $sql     = "UPDATE complaints SET status = :status";

        if ($newStatus === 'resolved') {
            $sql       .= ', resolved_at = CURRENT_TIMESTAMP';
        }
        $sql .= ' WHERE id = :id';

        $this->pdo->prepare($sql)->execute($updates);
        $this->addHistory($complaintId, $actorId, $actorRole, $currentStatus, $newStatus, $remarks);

        // Update assignment timestamps
        if ($newStatus === 'in_progress') {
            $this->pdo->prepare(
                "UPDATE complaint_assignments SET accepted_at = COALESCE(accepted_at, CURRENT_TIMESTAMP)
                 WHERE complaint_id = :cid AND assignment_status = 'active'"
            )->execute([':cid' => $complaintId]);
        }
        if ($newStatus === 'awaiting_verification') {
            $this->pdo->prepare(
                "UPDATE complaint_assignments SET completed_at = CURRENT_TIMESTAMP
                 WHERE complaint_id = :cid AND assignment_status = 'active'"
            )->execute([':cid' => $complaintId]);
        }
        if ($newStatus === 'resolved') {
            $this->pdo->prepare(
                "UPDATE complaint_assignments SET assignment_status = 'completed', ended_at = CURRENT_TIMESTAMP
                 WHERE complaint_id = :cid AND assignment_status = 'active'"
            )->execute([':cid' => $complaintId]);

            // Notify citizen
            $code      = $this->getComplaintCode($complaintId);
            $citizenId = (int) $this->pdo->query("SELECT citizen_id FROM complaints WHERE id=$complaintId")->fetchColumn();
            $this->notif->complaintResolved($citizenId, $code, $complaintId);
        }

        return ['ok' => true, 'error' => ''];
    }

    // ── Private helpers ───────────────────────────────────────────

    private function isPermittedTransition(string $from, string $to, string $role): bool
    {
        $map = [
            'pending'               => ['assigned'],
            'assigned'              => ['in_progress', 'pending', 'rejected', 'cancelled'],
            'in_progress'           => ['awaiting_verification', 'rejected', 'cancelled'],
            'awaiting_verification' => ['resolved', 'rework_required'],
            'rework_required'       => ['in_progress', 'awaiting_verification'],
            'resolved'              => [],
            'rejected'              => [],
            'cancelled'             => [],
        ];

        if (!in_array($to, $map[$from] ?? [], true)) {
            return false;
        }

        // Role-based restrictions:
        // Only admins can approve as resolved or require rework
        if (in_array($to, ['resolved', 'rework_required'], true) && !in_array($role, ['department_admin', 'super_admin'], true)) {
            return false;
        }

        // Officials cannot reject or cancel complaints directly
        if (in_array($to, ['rejected', 'cancelled'], true) && $role === 'work_official') {
            return false;
        }

        return true;
    }

    /**
     * Select the eligible official with the lowest active workload.
     * Tie-breaks by oldest last_assigned_at.
     */
    private function selectOfficial(int $deptId, int $wardId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.id, s.name, s.last_assigned_at,
                    COUNT(ca.id) AS active_workload
             FROM staff s
             INNER JOIN staff_wards sw ON sw.staff_id = s.id AND sw.ward_id = :ward_id
             LEFT JOIN complaint_assignments ca
                 ON ca.official_id = s.id
                 AND ca.assignment_status = 'active'
                 AND EXISTS (
                     SELECT 1 FROM complaints c
                     WHERE c.id = ca.complaint_id
                       AND c.status IN ('assigned', 'in_progress', 'awaiting_verification')
                 )
             WHERE s.department_id   = :dept_id
               AND s.account_status  = 'active'
               AND s.availability_status = 'available'
             GROUP BY s.id, s.name, s.max_active_workload, s.last_assigned_at
             HAVING active_workload < s.max_active_workload
             ORDER BY active_workload ASC, CASE WHEN s.last_assigned_at IS NULL THEN 0 ELSE 1 END, s.last_assigned_at ASC
             LIMIT 1"
        );
        $stmt->execute([':dept_id' => $deptId, ':ward_id' => $wardId]);
        return $stmt->fetch() ?: null;
    }

    private function getCategory(int $categoryId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, department_id, default_priority FROM categories WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $categoryId]);
        return $stmt->fetch() ?: [];
    }

    private function generateCode(): string
    {
        $year   = date('Y');
        $prefix = COMPLAINT_ID_PREFIX . '-' . $year . '-';

        $stmt = $this->pdo->prepare(
            "SELECT complaint_code FROM complaints
             WHERE complaint_code LIKE :like
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([':like' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        $seq = $last ? ((int) substr($last, strrpos($last, '-') + 1)) + 1 : 1;
        return $prefix . str_pad($seq, 6, '0', STR_PAD_LEFT);
    }

    private function addHistory(
        int     $complaintId,
        int     $actorId,
        string  $actorRole,
        ?string $oldStatus,
        string  $newStatus,
        string  $remarks = ''
    ): void {
        $this->pdo->prepare(
            "INSERT INTO complaint_history
                (complaint_id, actor_id, actor_role, old_status, new_status, remarks)
             VALUES (:cid, :aid, :role, :old, :new, :remarks)"
        )->execute([
            ':cid'     => $complaintId,
            ':aid'     => $actorId,
            ':role'    => $actorRole,
            ':old'     => $oldStatus,
            ':new'     => $newStatus,
            ':remarks' => $remarks,
        ]);
    }

    private function getComplaintCode(int $complaintId): string
    {
        $stmt = $this->pdo->prepare("SELECT complaint_code FROM complaints WHERE id = :id");
        $stmt->execute([':id' => $complaintId]);
        return (string) $stmt->fetchColumn();
    }
}
