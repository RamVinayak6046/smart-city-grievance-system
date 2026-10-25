<?php
/**
 * AuditService — writes to the audit_logs table.
 */
class AuditService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(
        ?int   $actorId,
        string $actorRole,
        string $action,
        string $entityType  = '',
        ?int   $entityId    = null,
        array  $details     = [],
        ?string $ip         = null
    ): void {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO audit_logs
                    (actor_id, actor_role, action, entity_type, entity_id, details, ip_address)
                 VALUES
                    (:aid, :role, :action, :etype, :eid, :details, :ip)"
            );
            $stmt->execute([
                ':aid'     => $actorId,
                ':role'    => $actorRole,
                ':action'  => $action,
                ':etype'   => $entityType ?: null,
                ':eid'     => $entityId,
                ':details' => empty($details) ? null : json_encode($details, JSON_UNESCAPED_UNICODE),
                ':ip'      => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            ]);
        } catch (PDOException $e) {
            error_log('[AuditService] ' . $e->getMessage());
        }
    }

    /**
     * Convenience wrapper using current session user.
     */
    public function logFromSession(
        string  $action,
        string  $entityType = '',
        ?int    $entityId   = null,
        array   $details    = []
    ): void {
        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role']    ?? 'system',
            $action,
            $entityType,
            $entityId,
            $details
        );
    }
}
