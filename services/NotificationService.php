<?php
/**
 * NotificationService — creates notification records in MySQL.
 */
class NotificationService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Create a single notification record.
     */
    public function create(
        string $recipientType,
        int    $recipientId,
        string $title,
        string $message,
        ?int   $complaintId = null
    ): bool {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications
                (recipient_type, recipient_id, complaint_id, title, message)
             VALUES
                (:rtype, :rid, :cid, :title, :message)"
        );
        return $stmt->execute([
            ':rtype'   => $recipientType,
            ':rid'     => $recipientId,
            ':cid'     => $complaintId,
            ':title'   => $title,
            ':message' => $message,
        ]);
    }

    /**
     * Notify the official that a new complaint has been assigned to them.
     */
    public function complaintAssigned(int $officialId, string $complaintCode, int $complaintId): void
    {
        $this->create(
            'work_official', $officialId,
            'New Assignment: ' . $complaintCode,
            'A new complaint (' . $complaintCode . ') has been assigned to you. Please review and accept.',
            $complaintId
        );
    }

    /**
     * Notify the citizen that their complaint has been submitted and (possibly) assigned.
     */
    public function complaintSubmitted(int $citizenId, string $complaintCode, int $complaintId, bool $assigned): void
    {
        $msg = $assigned
            ? "Your complaint ($complaintCode) has been submitted and assigned to an official."
            : "Your complaint ($complaintCode) has been submitted. Assignment is pending.";

        $this->create('citizen', $citizenId, 'Complaint Submitted: ' . $complaintCode, $msg, $complaintId);
    }

    /**
     * Notify department admin of a new unassigned complaint.
     */
    public function unassignedComplaint(int $deptId, string $complaintCode, int $complaintId): void
    {
        // Find all active department admins for this department
        $stmt = $this->pdo->prepare(
            "SELECT id FROM staff
             WHERE department_id = :did AND role_id = 3 AND account_status = 'active'"
        );
        $stmt->execute([':did' => $deptId]);
        foreach ($stmt->fetchAll() as $admin) {
            $this->create(
                'department_admin', (int) $admin['id'],
                'Unassigned Complaint: ' . $complaintCode,
                "Complaint $complaintCode could not be auto-assigned. Please assign it manually.",
                $complaintId
            );
        }
    }

    /**
     * Notify department admin that an official submitted work for verification.
     */
    public function awaitingVerification(int $deptId, string $complaintCode, int $complaintId): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT id FROM staff WHERE department_id = :did AND role_id = 3 AND account_status = 'active'"
        );
        $stmt->execute([':did' => $deptId]);
        foreach ($stmt->fetchAll() as $admin) {
            $this->create(
                'department_admin', (int) $admin['id'],
                'Work Submitted for Verification: ' . $complaintCode,
                "Complaint $complaintCode has been submitted for verification by the assigned official.",
                $complaintId
            );
        }
    }

    /**
     * Notify official that their work was accepted (resolved).
     */
    public function workApproved(int $officialId, string $complaintCode, int $complaintId): void
    {
        $this->create(
            'work_official', $officialId,
            'Work Approved: ' . $complaintCode,
            "Your completed work on complaint $complaintCode has been approved and marked Resolved.",
            $complaintId
        );
    }

    /**
     * Notify official that their work was rejected.
     */
    public function workRejected(int $officialId, string $complaintCode, int $complaintId, string $reason): void
    {
        $this->create(
            'work_official', $officialId,
            'Work Rejected: ' . $complaintCode,
            "Your completion submission for $complaintCode was rejected. Reason: $reason",
            $complaintId
        );
    }

    /**
     * Notify citizen that their complaint was resolved.
     */
    public function complaintResolved(int $citizenId, string $complaintCode, int $complaintId): void
    {
        $this->create(
            'citizen', $citizenId,
            'Complaint Resolved: ' . $complaintCode,
            "Great news! Your complaint ($complaintCode) has been resolved and verified.",
            $complaintId
        );
    }

    /**
     * Notify the new and old official about reassignment.
     */
    public function complaintReassigned(
        int    $oldOfficialId,
        int    $newOfficialId,
        string $complaintCode,
        int    $complaintId,
        string $reason
    ): void {
        $this->create(
            'work_official', $oldOfficialId,
            'Assignment Removed: ' . $complaintCode,
            "Complaint $complaintCode has been reassigned. Reason: $reason",
            $complaintId
        );
        $this->create(
            'work_official', $newOfficialId,
            'New Assignment: ' . $complaintCode,
            "Complaint $complaintCode has been reassigned to you. Reason: $reason",
            $complaintId
        );
    }

    /**
     * Notify super admin of a super-admin-level event.
     */
    public function notifySuperAdmins(string $title, string $message, ?int $complaintId = null): void
    {
        $stmt = $this->pdo->query(
            "SELECT id FROM staff WHERE role_id = 4 AND account_status = 'active'"
        );
        foreach ($stmt->fetchAll() as $sa) {
            $this->create('super_admin', (int)$sa['id'], $title, $message, $complaintId);
        }
    }
}
