<?php
// FILE: /app/models/Candidate.php

/**
 * Candidate Model
 *
 * Manages candidate data and operations.
 */
class Candidate extends Model
{
    protected $table = 'candidates';

    /**
     * Find candidate by email
     *
     * @param string $email
     * @param int $tenantId
     * @return array|null
     */
    public function findByEmail($email, $tenantId)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email AND tenant_id = :tenant_id LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':email', $email);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Get candidates by tenant with filters
     *
     * @param int $tenantId
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getByTenant($tenantId, $filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM applications WHERE candidate_id = c.id) as applications_count
                FROM {$this->table} c
                WHERE c.tenant_id = :tenant_id";

        if (isset($filters['search'])) {
            $sql .= " AND (c.full_name LIKE :search OR c.email LIKE :search OR c.current_company LIKE :search)";
        }

        if (isset($filters['source'])) {
            $sql .= " AND c.source = :source";
        }

        if (isset($filters['city'])) {
            $sql .= " AND c.city = :city";
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT {$limit} OFFSET {$offset}";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['search'])) {
            $this->db->bind(':search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['source'])) {
            $this->db->bind(':source', $filters['source']);
        }

        if (isset($filters['city'])) {
            $this->db->bind(':city', $filters['city']);
        }

        return $this->db->fetchAll();
    }

    /**
     * Get candidate with applications
     *
     * @param int $candidateId
     * @param int $tenantId
     * @return array|null
     */
    public function getWithApplications($candidateId, $tenantId)
    {
        $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM applications WHERE candidate_id = c.id) as total_applications
                FROM {$this->table} c
                WHERE c.id = :candidate_id AND c.tenant_id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Get candidate tags
     *
     * @param int $candidateId
     * @return array
     */
    public function getTags($candidateId)
    {
        $sql = "SELECT t.* FROM tags t
                INNER JOIN candidate_tags ct ON t.id = ct.tag_id
                WHERE ct.candidate_id = :candidate_id
                ORDER BY t.name ASC";

        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        return $this->db->fetchAll();
    }

    /**
     * Add tag to candidate
     *
     * @param int $candidateId
     * @param int $tagId
     * @return int
     */
    public function addTag($candidateId, $tagId)
    {
        $sql = "INSERT INTO candidate_tags (candidate_id, tag_id) VALUES (:candidate_id, :tag_id)";
        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':tag_id', $tagId);
        $this->db->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Remove tag from candidate
     *
     * @param int $candidateId
     * @param int $tagId
     * @return bool
     */
    public function removeTag($candidateId, $tagId)
    {
        $sql = "DELETE FROM candidate_tags WHERE candidate_id = :candidate_id AND tag_id = :tag_id";
        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':tag_id', $tagId);
        return $this->db->execute();
    }

    /**
     * Count candidates by tenant
     *
     * @param int $tenantId
     * @param array $filters
     * @return int
     */
    public function countByTenant($tenantId, $filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE tenant_id = :tenant_id";

        if (isset($filters['search'])) {
            $sql .= " AND (full_name LIKE :search OR email LIKE :search OR current_company LIKE :search)";
        }

        if (isset($filters['source'])) {
            $sql .= " AND source = :source";
        }

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);

        if (isset($filters['search'])) {
            $this->db->bind(':search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['source'])) {
            $this->db->bind(':source', $filters['source']);
        }

        $result = $this->db->fetch();
        return (int)$result['count'];
    }
}
