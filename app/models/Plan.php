<?php
// FILE: /app/models/Plan.php

/**
 * Plan Model
 *
 * Manages subscription plans.
 */
class Plan extends Model
{
    protected $table = 'plans';
    protected $usesTenant = false;

    /**
     * Get all active plans
     *
     * @return array
     */
    public function getActivePlans()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE is_active = 1
                ORDER BY price ASC";

        $this->db->query($sql);
        return $this->db->fetchAll();
    }

    /**
     * Find plan by code
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
     * Get plan features
     *
     * @param int $planId
     * @return array
     */
    public function getFeatures($planId)
    {
        $plan = $this->find($planId);

        if (!$plan || !$plan['features']) {
            return [];
        }

        return json_decode($plan['features'], true) ?? [];
    }
}
