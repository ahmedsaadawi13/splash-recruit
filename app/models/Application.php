<?php
// FILE: /app/models/Application.php

/**
 * Application Model
 *
 * Manages job applications and pipeline operations.
 */
class Application extends Model
{
    protected $table = 'applications';

    /**
     * Get applications by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT a.*, c.full_name as candidate_name, c.email as candidate_email,
                c.phone as candidate_phone, j.title as job_title, j.reference_code as job_reference,
                ps.name as current_stage_name
                FROM {$this->table} a
                INNER JOIN candidates c ON a.candidate_id = c.id
                INNER JOIN jobs j ON a.job_id = j.id
                LEFT JOIN pipeline_stages ps ON a.current_stage_id = ps.id
                WHERE a.tenant_id = :tenant_id";

        if (isset($filters['job_id'])) {
            $sql .= " AND a.job_id = :job_id";
        }

        if (isset($filters['status'])) {
            $sql .= " AND a.status = :status";
        }

        if (isset($filters['stage_id'])) {
            $sql .= " AND a.current_stage_id = :stage_id";
        }

        if (isset($filters['search'])) {
            $sql .= " AND (c.full_name LIKE :search OR c.email LIKE :search OR j.title LIKE :search)";
        }

        $sql .= " ORDER BY a.applied_at DESC LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['job_id'])) {
            $this->db->bind(':job_id', $filters['job_id']);
        }

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        if (isset($filters['stage_id'])) {
            $this->db->bind(':stage_id', $filters['stage_id']);
        }

        if (isset($filters['search'])) {
            $this->db->bind(':search', '%' . $filters['search'] . '%');
        }

        return $this->db->fetchAll();
    }

    /**
     * Get application with full details
     *
     * @param int $applicationId
     * @param int $tenantId
     * @return array|null
     */
    public function getWithDetails($applicationId, $tenantId)
    {
        $sql = "SELECT a.*, c.*, j.title as job_title, j.reference_code as job_reference,
                ps.name as current_stage_name,
                a.id as application_id, a.status as application_status, a.created_at as application_created_at
                FROM {$this->table} a
                INNER JOIN candidates c ON a.candidate_id = c.id
                INNER JOIN jobs j ON a.job_id = j.id
                LEFT JOIN pipeline_stages ps ON a.current_stage_id = ps.id
                WHERE a.id = :application_id AND a.tenant_id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':application_id', $applicationId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Get applications for a job grouped by stage
     *
     * @param int $jobId
     * @param int $tenantId
     * @return array
     */
    public function getByJobGroupedByStage($jobId, $tenantId)
    {
        $sql = "SELECT a.*, c.full_name as candidate_name, c.email as candidate_email,
                ps.id as stage_id, ps.name as stage_name, ps.position as stage_position
                FROM {$this->table} a
                INNER JOIN candidates c ON a.candidate_id = c.id
                LEFT JOIN pipeline_stages ps ON a.current_stage_id = ps.id
                WHERE a.job_id = :job_id AND a.tenant_id = :tenant_id
                ORDER BY ps.position ASC, a.applied_at DESC";

        $this->db->query($sql);
        $this->db->bind(':job_id', $jobId);
        $this->db->bind(':tenant_id', $tenantId);

        $applications = $this->db->fetchAll();

        // Group by stage
        $grouped = [];
        foreach ($applications as $app) {
            $stageId = $app['stage_id'] ?? 'no_stage';
            if (!isset($grouped[$stageId])) {
                $grouped[$stageId] = [
                    'stage_id' => $app['stage_id'],
                    'stage_name' => $app['stage_name'] ?? 'No Stage',
                    'applications' => []
                ];
            }
            $grouped[$stageId]['applications'][] = $app;
        }

        return array_values($grouped);
    }

    /**
     * Move application to new stage
     *
     * @param int $applicationId
     * @param int $newStageId
     * @param int $userId
     * @param string|null $note
     * @return bool
     */
    public function moveToStage($applicationId, $newStageId, $userId, $note = null)
    {
        // Get current stage
        $app = $this->find($applicationId);
        if (!$app) {
            return false;
        }

        $this->beginTransaction();

        try {
            // Update application
            $sql = "UPDATE {$this->table}
                    SET current_stage_id = :new_stage_id, last_status_change_at = NOW()
                    WHERE id = :application_id";

            $this->db->query($sql);
            $this->db->bind(':new_stage_id', $newStageId);
            $this->db->bind(':application_id', $applicationId);
            $this->db->execute();

            // Log stage change
            $sql = "INSERT INTO application_stage_history
                    (application_id, from_stage_id, to_stage_id, changed_by_user_id, note)
                    VALUES (:application_id, :from_stage_id, :to_stage_id, :user_id, :note)";

            $this->db->query($sql);
            $this->db->bind(':application_id', $applicationId);
            $this->db->bind(':from_stage_id', $app['current_stage_id']);
            $this->db->bind(':to_stage_id', $newStageId);
            $this->db->bind(':user_id', $userId);
            $this->db->bind(':note', $note);
            $this->db->execute();

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            error_log('Failed to move application to stage: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get stage history for application
     *
     * @param int $applicationId
     * @return array
     */
    public function getStageHistory($applicationId)
    {
        $sql = "SELECT ash.*, u.name as changed_by_name,
                ps1.name as from_stage_name, ps2.name as to_stage_name
                FROM application_stage_history ash
                LEFT JOIN users u ON ash.changed_by_user_id = u.id
                LEFT JOIN pipeline_stages ps1 ON ash.from_stage_id = ps1.id
                LEFT JOIN pipeline_stages ps2 ON ash.to_stage_id = ps2.id
                WHERE ash.application_id = :application_id
                ORDER BY ash.changed_at DESC";

        $this->db->query($sql);
        $this->db->bind(':application_id', $applicationId);
        return $this->db->fetchAll();
    }

    /**
     * Count applications by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @return int
     */
    public function countByTenant($tenantId, $filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} a WHERE a.tenant_id = :tenant_id";

        if (isset($filters['job_id'])) {
            $sql .= " AND a.job_id = :job_id";
        }

        if (isset($filters['status'])) {
            $sql .= " AND a.status = :status";
        }

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['job_id'])) {
            $this->db->bind(':job_id', $filters['job_id']);
        }

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        $result = $this->db->fetch();
        return (int)$result['count'];
    }

    /**
     * Check if candidate has already applied to job
     *
     * @param int $candidateId
     * @param int $jobId
     * @return bool
     */
    public function hasApplied($candidateId, $jobId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE candidate_id = :candidate_id AND job_id = :job_id";

        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':job_id', $jobId);

        $result = $this->db->fetch();
        return $result['count'] > 0;
    }
}
