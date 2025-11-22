<?php
// FILE: /app/models/CandidateFile.php

/**
 * CandidateFile Model
 *
 * Manages candidate files (CVs, resumes, documents).
 */
class CandidateFile extends Model
{
    protected $table = 'candidate_files';

    /**
     * Get files for candidate
     *
     * @param int $candidateId
     * @param int $tenantId
     * @return array
     */
    public function getByCandidate($candidateId, $tenantId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE candidate_id = :candidate_id AND tenant_id = :tenant_id
                ORDER BY is_primary DESC, uploaded_at DESC";

        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }

    /**
     * Get files for application
     *
     * @param int $applicationId
     * @param int $tenantId
     * @return array
     */
    public function getByApplication($applicationId, $tenantId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE application_id = :application_id AND tenant_id = :tenant_id
                ORDER BY is_primary DESC, uploaded_at DESC";

        $this->db->query($sql);
        $this->db->bind(':application_id', $applicationId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetchAll();
    }

    /**
     * Get primary CV for candidate
     *
     * @param int $candidateId
     * @param int $tenantId
     * @return array|null
     */
    public function getPrimaryCV($candidateId, $tenantId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE candidate_id = :candidate_id AND tenant_id = :tenant_id AND is_primary = 1
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':candidate_id', $candidateId);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Set as primary CV
     *
     * @param int $fileId
     * @param int $candidateId
     * @param int $tenantId
     * @return bool
     */
    public function setAsPrimary($fileId, $candidateId, $tenantId)
    {
        $this->beginTransaction();

        try {
            // Unset all primary flags for candidate
            $sql = "UPDATE {$this->table} SET is_primary = 0
                    WHERE candidate_id = :candidate_id AND tenant_id = :tenant_id";
            $this->db->query($sql);
            $this->db->bind(':candidate_id', $candidateId);
            $this->db->bind(':tenant_id', $tenantId);
            $this->db->execute();

            // Set new primary
            $sql = "UPDATE {$this->table} SET is_primary = 1
                    WHERE id = :file_id AND candidate_id = :candidate_id AND tenant_id = :tenant_id";
            $this->db->query($sql);
            $this->db->bind(':file_id', $fileId);
            $this->db->bind(':candidate_id', $candidateId);
            $this->db->bind(':tenant_id', $tenantId);
            $this->db->execute();

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * Calculate total storage used by tenant
     *
     * @param int $tenantId
     * @return int
     */
    public function getTotalStorageUsed($tenantId)
    {
        $sql = "SELECT SUM(size_bytes) as total FROM {$this->table}
                WHERE tenant_id = :tenant_id";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        $result = $this->db->fetch();

        return (int)($result['total'] ?? 0);
    }
}
