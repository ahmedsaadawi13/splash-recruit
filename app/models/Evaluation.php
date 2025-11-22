<?php
// FILE: /app/models/Evaluation.php

/**
 * Evaluation Model
 *
 * Manages candidate evaluations and feedback.
 */
class Evaluation extends Model
{
    protected $table = 'evaluations';

    /**
     * Get evaluations for application
     *
     * @param int $applicationId
     * @return array
     */
    public function getByApplication($applicationId)
    {
        $sql = "SELECT e.*, u.name as evaluator_name, i.scheduled_start as interview_date
                FROM {$this->table} e
                INNER JOIN users u ON e.evaluator_user_id = u.id
                LEFT JOIN interviews i ON e.interview_id = i.id
                WHERE e.application_id = :application_id
                ORDER BY e.created_at DESC";

        $this->db->query($sql);
        $this->db->bind(':application_id', $applicationId);
        return $this->db->fetchAll();
    }

    /**
     * Get evaluation by interview and evaluator
     *
     * @param int $interviewId
     * @param int $evaluatorId
     * @return array|null
     */
    public function getByInterviewAndEvaluator($interviewId, $evaluatorId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE interview_id = :interview_id AND evaluator_user_id = :evaluator_id
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':interview_id', $interviewId);
        $this->db->bind(':evaluator_id', $evaluatorId);
        return $this->db->fetch();
    }

    /**
     * Get average rating for application
     *
     * @param int $applicationId
     * @return float
     */
    public function getAverageRating($applicationId)
    {
        $sql = "SELECT AVG(overall_rating) as avg_rating FROM {$this->table}
                WHERE application_id = :application_id AND overall_rating IS NOT NULL";

        $this->db->query($sql);
        $this->db->bind(':application_id', $applicationId);
        $result = $this->db->fetch();

        return $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
    }
}
