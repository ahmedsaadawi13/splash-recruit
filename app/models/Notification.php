<?php
// FILE: /app/models/Notification.php

/**
 * Notification Model
 *
 * Manages email notifications and their status.
 */
class Notification extends Model
{
    protected $table = 'notifications';
    protected $usesTenant = false; // Notifications can be platform-wide

    /**
     * Get pending notifications
     *
     * @param int $limit
     * @return array
     */
    public function getPending($limit = 100)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'pending'
                ORDER BY created_at ASC
                LIMIT {$limit}";

        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * Mark as sent
     *
     * @param int $notificationId
     * @return bool
     */
    public function markAsSent($notificationId)
    {
        $sql = "UPDATE {$this->table}
                SET status = 'sent', sent_at = NOW()
                WHERE id = :notification_id";

        $this->db->query($sql);
        $this->db->bind(':notification_id', $notificationId);
        return $this->db->execute();
    }

    /**
     * Mark as failed
     *
     * @param int $notificationId
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed($notificationId, $errorMessage)
    {
        $sql = "UPDATE {$this->table}
                SET status = 'failed', error_message = :error_message
                WHERE id = :notification_id";

        $this->db->query($sql);
        $this->db->bind(':notification_id', $notificationId);
        $this->db->bind(':error_message', $errorMessage);
        return $this->db->execute();
    }

    /**
     * Get notifications by tenant
     *
     * @param int $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE tenant_id = :tenant_id
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }
}
