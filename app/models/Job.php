<?php
// FILE: /app/models/Job.php

/**
 * Job Model
 *
 * Manages job postings and operations.
 */
class Job extends Model
{
    protected $table = 'jobs';

    /**
     * Get jobs by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT j.*, u.name as hiring_manager_name,
                (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as applications_count
                FROM {$this->table} j
                LEFT JOIN users u ON j.hiring_manager_user_id = u.id
                WHERE j.tenant_id = :tenant_id";

        if (isset($filters['status'])) {
            $sql .= " AND j.status = :status";
        }

        if (isset($filters['department'])) {
            $sql .= " AND j.department = :department";
        }

        if (isset($filters['search'])) {
            $sql .= " AND (j.title LIKE :search OR j.reference_code LIKE :search)";
        }

        $sql .= " ORDER BY j.created_at DESC LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        if (isset($filters['department'])) {
            $this->db->bind(':department', $filters['department']);
        }

        if (isset($filters['search'])) {
            $this->db->bind(':search', '%' . $filters['search'] . '%');
        }

        return $this->db->fetchAll();
    }

    /**
     * Get published jobs for career page
     *
     * @param int $tenantId
     * @param array $filters
     * @return array
     */
    public function getPublishedJobs($tenantId, $filters = [])
    {
        $sql = "SELECT id, title, slug, reference_code, department, location_type, location_city,
                location_country, employment_type, salary_range_min, salary_range_max, salary_currency,
                salary_visible, description, published_at
                FROM {$this->table}
                WHERE tenant_id = :tenant_id AND status = 'published'";

        if (isset($filters['department'])) {
            $sql .= " AND department = :department";
        }

        if (isset($filters['location_city'])) {
            $sql .= " AND location_city = :location_city";
        }

        if (isset($filters['employment_type'])) {
            $sql .= " AND employment_type = :employment_type";
        }

        $sql .= " ORDER BY published_at DESC";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['department'])) {
            $this->db->bind(':department', $filters['department']);
        }

        if (isset($filters['location_city'])) {
            $this->db->bind(':location_city', $filters['location_city']);
        }

        if (isset($filters['employment_type'])) {
            $this->db->bind(':employment_type', $filters['employment_type']);
        }

        return $this->db->fetchAll();
    }

    /**
     * Find job by slug
     *
     * @param string $slug
     * @param int $tenantId
     * @return array|null
     */
    public function findBySlug($slug, $tenantId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug AND tenant_id = :tenant_id LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':slug', $slug);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Count jobs by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @return int
     */
    public function countByTenant($tenantId, $filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE tenant_id = :tenant_id";

        if (isset($filters['status'])) {
            $sql .= " AND status = :status";
        }

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        $result = $this->db->fetch();
        return (int)$result['count'];
    }

    /**
     * Get job with details
     *
     * @param int $jobId
     * @param int $tenantId
     * @return array|null
     */
    public function getWithDetails($jobId, $tenantId)
    {
        $sql = "SELECT j.*, u.name as hiring_manager_name, u.email as hiring_manager_email,
                p.name as pipeline_name,
                (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_applications
                FROM {$this->table} j
                LEFT JOIN users u ON j.hiring_manager_user_id = u.id
                LEFT JOIN pipelines p ON j.pipeline_id = p.id
                WHERE j.id = :job_id AND j.tenant_id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':job_id', $jobId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Get departments for tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getDepartments($tenantId)
    {
        $sql = "SELECT DISTINCT department FROM {$this->table}
                WHERE tenant_id = :tenant_id AND department IS NOT NULL
                ORDER BY department ASC";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }
}
