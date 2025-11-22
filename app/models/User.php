<?php
// FILE: /app/models/User.php

/**
 * User Model
 *
 * Manages user data and operations.
 */
class User extends Model
{
    protected $table = 'users';
    protected $usesTenant = false; // Users can be platform-wide or tenant-specific

    /**
     * Find user by email
     *
     * @param string $email
     * @return array|null
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':email', $email);
        return $this->db->fetch();
    }

    /**
     * Get users by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @return array
     */
    public function getByTenant($tenantId, $filters = [])
    {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";

        if (isset($filters['role'])) {
            $sql .= " AND role = :role";
        }

        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
        }

        $sql .= " ORDER BY name ASC";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['role'])) {
            $this->db->bind(':role', $filters['role']);
        }

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        return $this->db->fetchAll();
    }

    /**
     * Update last login
     *
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE {$this->table} SET last_login_at = NOW() WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    /**
     * Check if email is unique
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function isEmailUnique($email, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
        }

        $this->db->query($sql);
        $this->db->bind(':email', $email);

        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }

        $result = $this->db->fetch();
        return $result['count'] == 0;
    }

    /**
     * Get platform admins
     *
     * @return array
     */
    public function getPlatformAdmins()
    {
        $sql = "SELECT * FROM {$this->table} WHERE role = 'platform_admin' AND status = 'active' ORDER BY name ASC";
        $this->db->query($sql);
        return $this->db->fetchAll();
    }
}
