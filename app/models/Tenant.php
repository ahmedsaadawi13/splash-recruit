<?php
// FILE: /app/models/Tenant.php

/**
 * Tenant Model
 *
 * Manages tenant (company) data and operations.
 */
class Tenant extends Model
{
    protected $table = 'tenants';
    protected $usesTenant = false; // Tenants table is not tenant-scoped

    /**
     * Find tenant by code
     *
     * @param string $code
     * @return array|null
     */
    public function findByCode($code)
    {
        $sql = "SELECT * FROM {$this->table} WHERE code = :code LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':code', $code);
        return $this->db->fetch();
    }

    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return array|null
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':slug', $slug);
        return $this->db->fetch();
    }

    /**
     * Find tenant by subdomain
     *
     * @param string $subdomain
     * @return array|null
     */
    public function findBySubdomain($subdomain)
    {
        $sql = "SELECT * FROM {$this->table} WHERE career_site_subdomain = :subdomain LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':subdomain', $subdomain);
        return $this->db->fetch();
    }

    /**
     * Get all active tenants
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getActiveTenants($limit = null, $offset = 0)
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name ASC";

        if ($limit !== null) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * Get tenant with subscription
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getWithSubscription($tenantId)
    {
        $sql = "SELECT t.*, ts.status as subscription_status, ts.plan_id, p.name as plan_name
                FROM {$this->table} t
                LEFT JOIN tenant_subscriptions ts ON t.id = ts.tenant_id AND ts.status IN ('active', 'trialing')
                LEFT JOIN plans p ON ts.plan_id = p.id
                WHERE t.id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Count total tenants
     *
     * @param array $filters
     * @return int
     */
    public function countAll($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE 1=1";

        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
        }

        $this->db->query($sql);

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        $result = $this->db->fetch();
        return (int)$result['count'];
    }
}
