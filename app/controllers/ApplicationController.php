<?php
// FILE: /app/controllers/ApplicationController.php

/**
 * ApplicationController
 *
 * Handles job applications and pipeline movement.
 */
class ApplicationController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();

        $applicationModel = $this->model('Application');

        $filters = array_filter([
            'job_id' => $this->request->get('job_id'),
            'status' => $this->request->get('status'),
            'search' => $this->request->get('search')
        ]);

        $page = max(1, (int)$this->request->get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $applications = $applicationModel->getByTenant($tenantId, $filters, $perPage, $offset);
        $totalApplications = $applicationModel->countByTenant($tenantId, $filters);

        $paginator = new Paginator($totalApplications, $perPage, $page);

        $this->view('applications/index', [
            'title' => 'Applications - SplashRecruit',
            'applications' => $applications,
            'paginator' => $paginator,
            'filters' => $filters
        ]);
    }

    public function show($id)
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();

        $applicationModel = $this->model('Application');
        $application = $applicationModel->getWithDetails($id, $tenantId);

        if (!$application) {
            $this->session->setFlash('error', 'Application not found.');
            $this->redirect('/applications');
        }

        // Get stage history
        $stageHistory = $applicationModel->getStageHistory($id);

        // Get evaluations
        $evaluationModel = $this->model('Evaluation');
        $evaluations = $evaluationModel->getByApplication($id);

        // Get interviews
        $db = Database::getInstance();
        $db->query("SELECT * FROM interviews WHERE application_id = :app_id ORDER BY scheduled_start DESC");
        $db->bind(':app_id', $id);
        $interviews = $db->fetchAll();

        // Get available stages for this job's pipeline
        $pipelineStageModel = $this->model('PipelineStage');
        $stages = $pipelineStageModel->getByPipeline($application['pipeline_id'] ?? 0);

        $this->view('applications/show', [
            'title' => 'Application - ' . $application['candidate_name'],
            'application' => $application,
            'stageHistory' => $stageHistory,
            'evaluations' => $evaluations,
            'interviews' => $interviews,
            'stages' => $stages
        ]);
    }

    public function moveToStage($id)
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter', 'hiring_manager']);
        $this->requireCsrf();

        $tenantId = $this->getTenantId();
        $applicationModel = $this->model('Application');

        $application = $applicationModel->find($id, $tenantId);

        if (!$application) {
            $this->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        $newStageId = $this->request->post('stage_id');
        $note = $this->request->post('note');

        $success = $applicationModel->moveToStage($id, $newStageId, $this->auth->user('id'), $note);

        if ($success) {
            // Log activity
            $activityLog = $this->model('ActivityLog');
            $activityLog->log([
                'tenant_id' => $tenantId,
                'user_id' => $this->auth->user('id'),
                'action' => 'stage_changed',
                'description' => 'Moved application to new stage',
                'entity_type' => 'application',
                'entity_id' => $id
            ]);

            $this->json(['success' => true, 'message' => 'Application moved successfully.']);
        } else {
            $this->json(['success' => false, 'message' => 'Failed to move application.'], 500);
        }
    }
}
