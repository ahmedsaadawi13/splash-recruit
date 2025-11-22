<?php
// FILE: /app/models/PipelineStage.php

/**
 * PipelineStage Model
 *
 * Manages pipeline stages.
 */
class PipelineStage extends Model
{
    protected $table = 'pipeline_stages';
    protected $usesTenant = false; // Stages are linked through pipeline

    /**
     * Get stages by pipeline
     *
     * @param int $pipelineId
     * @return array
     */
    public function getByPipeline($pipelineId)
    {
        $sql = "SELECT ps.*,
                (SELECT COUNT(*) FROM applications WHERE current_stage_id = ps.id) as applications_count
                FROM {$this->table} ps
                WHERE ps.pipeline_id = :pipeline_id
                ORDER BY ps.position ASC";

        $this->db->query($sql);
        $this->db->bind(':pipeline_id', $pipelineId);
        return $this->db->fetchAll();
    }

    /**
     * Reorder stages
     *
     * @param array $stageOrders Array of ['id' => position]
     * @return bool
     */
    public function reorder($stageOrders)
    {
        $this->beginTransaction();

        try {
            foreach ($stageOrders as $stageId => $position) {
                $sql = "UPDATE {$this->table} SET position = :position WHERE id = :stage_id";
                $this->db->query($sql);
                $this->db->bind(':position', $position);
                $this->db->bind(':stage_id', $stageId);
                $this->db->execute();
            }

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }
}
