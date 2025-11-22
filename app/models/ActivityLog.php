<?php
// FILE: /app/models/ActivityLog.php

/**
 * ActivityLog Model
 *
 * Manages activity logging and audit trail.
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';
    protected $usesTenant = false;

    /**
     * Log activity
     *
     * @param array $data
     * @return int
     */
    public function log($data)
    {
        $request = new Request();

        $logData = array_merge([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ], $data);

        return $this->insert($logData);
    }

    /**
     * Get logs by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $filters = [], $limit = 50, $offset = 0)
    {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.tenant_id = :tenant_id";

        if (isset($filters['entity_type'])) {
            $sql .= " AND al.entity_type = :entity_type";
        }

        if (isset($filters['action'])) {
            $sql .= " AND al.action = :action";
        }

        if (isset($filters['user_id'])) {
            $sql .= " AND al.user_id = :user_id";
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['entity_type'])) {
            $this->db->bind(':entity_type', $filters['entity_type']);
        }

        if (isset($filters['action'])) {
            $this->db->bind(':action', $filters['action']);
        }

        if (isset($filters['user_id'])) {
            $this->db->bind(':user_id', $filters['user_id']);
        }

        return $this->db->fetchAll();
    }

    /**
     * Get logs for entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return array
     */
    public function getByEntity($entityType, $entityId)
    {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.entity_type = :entity_type AND al.entity_id = :entity_id
                ORDER BY al.created_at DESC";

        $this->db->query($sql);
        $this->db->bind(':entity_type', $entityType);
        $this->db->bind(':entity_id', $entityId);
        return $this->db->fetchAll();
    }
}
