<?php
// FILE: /app/models/Interview.php

/**
 * Interview Model
 *
 * Manages interview scheduling and details.
 */
class Interview extends Model
{
    protected $table = 'interviews';

    /**
     * Get interviews by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT i.*, a.id as application_id, c.full_name as candidate_name,
                j.title as job_title, j.reference_code as job_reference
                FROM {$this->table} i
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN candidates c ON a.candidate_id = c.id
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE i.tenant_id = :tenant_id";

        if (isset($filters['status'])) {
            $sql .= " AND i.status = :status";
        }

        if (isset($filters['date_from'])) {
            $sql .= " AND i.scheduled_start >= :date_from";
        }

        if (isset($filters['date_to'])) {
            $sql .= " AND i.scheduled_start <= :date_to";
        }

        $sql .= " ORDER BY i.scheduled_start DESC LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['status'])) {
            $this->db->bind(':status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $this->db->bind(':date_from', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $this->db->bind(':date_to', $filters['date_to']);
        }

        return $this->db->fetchAll();
    }

    /**
     * Get interviews for user (as interviewer)
     *
     * @param int $userId
     * @param int $tenantId
     * @return array
     */
    public function getForInterviewer($userId, $tenantId)
    {
        $sql = "SELECT i.*, a.id as application_id, c.full_name as candidate_name,
                j.title as job_title
                FROM {$this->table} i
                INNER JOIN interview_interviewers ii ON i.id = ii.interview_id
                INNER JOIN applications a ON i.application_id = a.id
                INNER JOIN candidates c ON a.candidate_id = c.id
                INNER JOIN jobs j ON a.job_id = j.id
                WHERE ii.user_id = :user_id AND i.tenant_id = :tenant_id
                ORDER BY i.scheduled_start ASC";

        $this->db->query($sql);
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }

    /**
     * Get interviewers for interview
     *
     * @param int $interviewId
     * @return array
     */
    public function getInterviewers($interviewId)
    {
        $sql = "SELECT u.* FROM users u
                INNER JOIN interview_interviewers ii ON u.id = ii.user_id
                WHERE ii.interview_id = :interview_id";

        $this->db->query($sql);
        $this->db->bind(':interview_id', $interviewId);
        return $this->db->fetchAll();
    }

    /**
     * Add interviewer
     *
     * @param int $interviewId
     * @param int $userId
     * @return int
     */
    public function addInterviewer($interviewId, $userId)
    {
        $sql = "INSERT INTO interview_interviewers (interview_id, user_id) VALUES (:interview_id, :user_id)";
        $this->db->query($sql);
        $this->db->bind(':interview_id', $interviewId);
        $this->db->bind(':user_id', $userId);
        $this->db->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Remove interviewer
     *
     * @param int $interviewId
     * @param int $userId
     * @return bool
     */
    public function removeInterviewer($interviewId, $userId)
    {
        $sql = "DELETE FROM interview_interviewers WHERE interview_id = :interview_id AND user_id = :user_id";
        $this->db->query($sql);
        $this->db->bind(':interview_id', $interviewId);
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }
}
