<?php
// FILE: /app/controllers/DashboardController.php

/**
 * DashboardController
 *
 * Handles dashboard display and KPIs.
 */
class DashboardController extends Controller
{
    /**
     * Show dashboard
     */
    public function index()
    {
        $this->requireAuth();

        $userRole = $this->auth->user('role');

        // Route to appropriate dashboard based on role
        if ($userRole === 'platform_admin') {
            $this->platformAdminDashboard();
        } else {
            $this->tenantDashboard();
        }
    }

    /**
     * Platform admin dashboard
     */
    private function platformAdminDashboard()
    {
        $tenantModel = $this->model('Tenant');
        $userModel = $this->model('User');
        $jobModel = $this->model('Job');
        $candidateModel = $this->model('Candidate');

        // Get KPIs
        $stats = [
            'total_tenants' => $tenantModel->countAll(['status' => 'active']),
            'total_users' => $this->getTotalUsers(),
            'total_jobs' => $this->getTotalJobs(),
            'total_candidates' => $this->getTotalCandidates()
        ];

        // Get recent tenants
        $recentTenants = $tenantModel->getActiveTenants(5, 0);

        $this->view('dashboard/platform_admin', [
            'title' => 'Platform Dashboard - SplashRecruit',
            'stats' => $stats,
            'recentTenants' => $recentTenants
        ]);
    }

    /**
     * Tenant dashboard
     */
    private function tenantDashboard()
    {
        $tenantId = $this->getTenantId();

        $jobModel = $this->model('Job');
        $candidateModel = $this->model('Candidate');
        $applicationModel = $this->model('Application');

        // Get KPIs
        $stats = [
            'total_jobs' => $jobModel->countByTenant($tenantId, []),
            'active_jobs' => $jobModel->countByTenant($tenantId, ['status' => 'published']),
            'total_candidates' => $candidateModel->countByTenant($tenantId, []),
            'total_applications' => $applicationModel->countByTenant($tenantId, []),
            'new_applications_this_week' => $this->getNewApplicationsThisWeek($tenantId)
        ];

        // Get recent jobs
        $recentJobs = $jobModel->getByTenant($tenantId, [], 5, 0);

        // Get recent applications
        $recentApplications = $applicationModel->getByTenant($tenantId, [], 10, 0);

        $this->view('dashboard/tenant', [
            'title' => 'Dashboard - SplashRecruit',
            'stats' => $stats,
            'recentJobs' => $recentJobs,
            'recentApplications' => $recentApplications
        ]);
    }

    /**
     * Get total users across all tenants
     */
    private function getTotalUsers()
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $result = $db->fetch();
        return $result['count'];
    }

    /**
     * Get total jobs across all tenants
     */
    private function getTotalJobs()
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) as count FROM jobs");
        $result = $db->fetch();
        return $result['count'];
    }

    /**
     * Get total candidates across all tenants
     */
    private function getTotalCandidates()
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) as count FROM candidates");
        $result = $db->fetch();
        return $result['count'];
    }

    /**
     * Get new applications this week
     */
    private function getNewApplicationsThisWeek($tenantId)
    {
        $db = Database::getInstance();
        $db->query("SELECT COUNT(*) as count FROM applications
                    WHERE tenant_id = :tenant_id
                    AND applied_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $db->bind(':tenant_id', $tenantId);
        $result = $db->fetch();
        return $result['count'];
    }
}
