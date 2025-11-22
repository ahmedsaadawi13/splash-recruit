<?php
// FILE: /app/models/Pipeline.php

/**
 * Pipeline Model
 *
 * Manages recruitment pipelines.
 */
class Pipeline extends Model
{
    protected $table = 'pipelines';

    /**
     * Get default pipeline for tenant
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getDefault($tenantId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE tenant_id = :tenant_id AND is_default = 1
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Get pipeline with stages
     *
     * @param int $pipelineId
     * @param int $tenantId
     * @return array|null
     */
    public function getWithStages($pipelineId, $tenantId)
    {
        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM pipeline_stages WHERE pipeline_id = p.id) as stages_count
                FROM {$this->table} p
                WHERE p.id = :pipeline_id AND p.tenant_id = :tenant_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':pipeline_id', $pipelineId);
        $this->db->bind(':tenant_id', $tenantId);

        $pipeline = $this->db->fetch();

        if ($pipeline) {
            // Get stages
            $stagesModel = new PipelineStage();
            $pipeline['stages'] = $stagesModel->getByPipeline($pipelineId);
        }

        return $pipeline;
    }

    /**
     * Set as default pipeline
     *
     * @param int $pipelineId
     * @param int $tenantId
     * @return bool
     */
    public function setAsDefault($pipelineId, $tenantId)
    {
        $this->beginTransaction();

        try {
            // Unset all defaults for tenant
            $sql = "UPDATE {$this->table} SET is_default = 0 WHERE tenant_id = :tenant_id";
            $this->db->query($sql);
            $this->db->bind(':tenant_id', $tenantId);
            $this->db->execute();

            // Set new default
            $sql = "UPDATE {$this->table} SET is_default = 1
                    WHERE id = :pipeline_id AND tenant_id = :tenant_id";
            $this->db->query($sql);
            $this->db->bind(':pipeline_id', $pipelineId);
            $this->db->bind(':tenant_id', $tenantId);
            $this->db->execute();

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }
}
