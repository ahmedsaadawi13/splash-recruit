<?php
// FILE: /app/models/TenantSubscription.php

/**
 * TenantSubscription Model
 *
 * Manages tenant subscriptions and quota enforcement.
 */
class TenantSubscription extends Model
{
    protected $table = 'tenant_subscriptions';
    protected $usesTenant = false;

    /**
     * Get active subscription for tenant
     *
     * @param int $tenantId
     * @return array|null
     */
    public function getActive($tenantId)
    {
        $sql = "SELECT ts.*, p.*,
                ts.id as subscription_id, ts.status as subscription_status
                FROM {$this->table} ts
                INNER JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = :tenant_id
                AND ts.status IN ('active', 'trialing')
                ORDER BY ts.created_at DESC
                LIMIT 1";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->fetch();
    }

    /**
     * Check if tenant can create job
     *
     * @param int $tenantId
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function canCreateJob($tenantId)
    {
        $subscription = $this->getActive($tenantId);

        if (!$subscription) {
            return ['allowed' => false, 'message' => 'No active subscription found.'];
        }

        // Check if subscription is active
        if ($subscription['subscription_status'] !== 'active' && $subscription['subscription_status'] !== 'trialing') {
            return ['allowed' => false, 'message' => 'Subscription is not active.'];
        }

        // Check max jobs limit
        if ($subscription['max_jobs'] !== null) {
            $jobModel = new Job();
            $currentJobs = $jobModel->countByTenant($tenantId, []);

            if ($currentJobs >= $subscription['max_jobs']) {
                return ['allowed' => false, 'message' => "You have reached the maximum number of jobs ({$subscription['max_jobs']}) for your plan."];
            }
        }

        // Check active jobs limit
        if ($subscription['max_active_jobs'] !== null) {
            $jobModel = new Job();
            $activeJobs = $jobModel->countByTenant($tenantId, ['status' => 'published']);

            if ($activeJobs >= $subscription['max_active_jobs']) {
                return ['allowed' => false, 'message' => "You have reached the maximum number of active jobs ({$subscription['max_active_jobs']}) for your plan."];
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Check if tenant can add user
     *
     * @param int $tenantId
     * @return array ['allowed' => bool, 'message' => string]
     */
    public function canAddUser($tenantId)
    {
        $subscription = $this->getActive($tenantId);

        if (!$subscription || !in_array($subscription['subscription_status'], ['active', 'trialing'])) {
            return ['allowed' => false, 'message' => 'No active subscription found.'];
        }

        if ($subscription['max_users'] !== null) {
            $userModel = new User();
            $currentUsers = $userModel->count($tenantId, ['status' => 'active']);

            if ($currentUsers >= $subscription['max_users']) {
                return ['allowed' => false, 'message' => "You have reached the maximum number of users ({$subscription['max_users']}) for your plan."];
            }
        }

        return ['allowed' => true, 'message' => ''];
    }

    /**
     * Get usage statistics for tenant
     *
     * @param int $tenantId
     * @return array
     */
    public function getUsageStats($tenantId)
    {
        $subscription = $this->getActive($tenantId);

        $jobModel = new Job();
        $userModel = new User();
        $candidateModel = new Candidate();
        $fileModel = new CandidateFile();

        return [
            'jobs_count' => $jobModel->countByTenant($tenantId, []),
            'active_jobs_count' => $jobModel->countByTenant($tenantId, ['status' => 'published']),
            'users_count' => $userModel->count($tenantId, ['status' => 'active']),
            'candidates_count' => $candidateModel->countByTenant($tenantId, []),
            'storage_used_bytes' => $fileModel->getTotalStorageUsed($tenantId),
            'max_jobs' => $subscription['max_jobs'] ?? 'unlimited',
            'max_active_jobs' => $subscription['max_active_jobs'] ?? 'unlimited',
            'max_users' => $subscription['max_users'] ?? 'unlimited',
            'max_storage_size' => $subscription['max_storage_size'] ?? 'unlimited'
        ];
    }
}
