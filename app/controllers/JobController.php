<?php
// FILE: /app/controllers/JobController.php

/**
 * JobController
 *
 * Handles job management (CRUD operations).
 */
class JobController extends Controller
{
    /**
     * List jobs
     */
    public function index()
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter', 'hiring_manager']);

        $tenantId = $this->getTenantId();
        $jobModel = $this->model('Job');

        // Get filters
        $filters = [
            'status' => $this->request->get('status'),
            'department' => $this->request->get('department'),
            'search' => $this->request->get('search')
        ];

        $filters = array_filter($filters);

        // Pagination
        $page = max(1, (int)$this->request->get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Get jobs
        $jobs = $jobModel->getByTenant($tenantId, $filters, $perPage, $offset);
        $totalJobs = $jobModel->countByTenant($tenantId, $filters);

        $paginator = new Paginator($totalJobs, $perPage, $page);

        $this->view('jobs/index', [
            'title' => 'Jobs - SplashRecruit',
            'jobs' => $jobs,
            'paginator' => $paginator,
            'filters' => $filters
        ]);
    }

    /**
     * Show job details
     */
    public function show($id)
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();

        $jobModel = $this->model('Job');
        $job = $jobModel->getWithDetails($id, $tenantId);

        if (!$job) {
            $this->session->setFlash('error', 'Job not found.');
            $this->redirect('/jobs');
        }

        // Get applications for this job
        $applicationModel = $this->model('Application');
        $applications = $applicationModel->getByJobGroupedByStage($id, $tenantId);

        $this->view('jobs/show', [
            'title' => $job['title'] . ' - SplashRecruit',
            'job' => $job,
            'applications' => $applications
        ]);
    }

    /**
     * Show create job form
     */
    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter']);

        $tenantId = $this->getTenantId();

        // Check quota
        $subscriptionModel = $this->model('TenantSubscription');
        $canCreate = $subscriptionModel->canCreateJob($tenantId);

        if (!$canCreate['allowed']) {
            $this->session->setFlash('error', $canCreate['message']);
            $this->redirect('/jobs');
        }

        // Get hiring managers and pipelines
        $userModel = $this->model('User');
        $pipelineModel = $this->model('Pipeline');

        $hiringManagers = $userModel->getByTenant($tenantId, ['role' => 'hiring_manager']);
        $pipelines = $pipelineModel->findAll($tenantId);

        $this->view('jobs/create', [
            'title' => 'Create Job - SplashRecruit',
            'hiringManagers' => $hiringManagers,
            'pipelines' => $pipelines
        ]);
    }

    /**
     * Store new job
     */
    public function store()
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter']);
        $this->requireCsrf();

        $tenantId = $this->getTenantId();

        // Validate
        $validator = Validation::make($this->request->post(), [
            'title' => 'required|min:3|max:255',
            'department' => 'required',
            'employment_type' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('error', $validator->firstError());
            $this->redirect('/jobs/create');
        }

        // Check quota
        $subscriptionModel = $this->model('TenantSubscription');
        $canCreate = $subscriptionModel->canCreateJob($tenantId);

        if (!$canCreate['allowed']) {
            $this->session->setFlash('error', $canCreate['message']);
            $this->redirect('/jobs');
        }

        $jobModel = $this->model('Job');

        // Generate slug
        $slug = Slug::generateUnique($this->request->post('title'), 'jobs', 'slug', null, $tenantId);

        // Get default pipeline
        $pipelineModel = $this->model('Pipeline');
        $defaultPipeline = $pipelineModel->getDefault($tenantId);

        $data = [
            'tenant_id' => $tenantId,
            'title' => $this->request->post('title'),
            'slug' => $slug,
            'reference_code' => $this->request->post('reference_code'),
            'department' => $this->request->post('department'),
            'location_type' => $this->request->post('location_type', 'on_site'),
            'location_city' => $this->request->post('location_city'),
            'location_country' => $this->request->post('location_country'),
            'employment_type' => $this->request->post('employment_type'),
            'salary_range_min' => $this->request->post('salary_range_min'),
            'salary_range_max' => $this->request->post('salary_range_max'),
            'salary_currency' => $this->request->post('salary_currency', 'USD'),
            'salary_visible' => $this->request->post('salary_visible', 0),
            'description' => $this->request->post('description'),
            'requirements' => $this->request->post('requirements'),
            'benefits' => $this->request->post('benefits'),
            'status' => $this->request->post('status', 'draft'),
            'hiring_manager_user_id' => $this->request->post('hiring_manager_user_id'),
            'pipeline_id' => $defaultPipeline ? $defaultPipeline['id'] : null,
            'created_by' => $this->auth->user('id')
        ];

        if ($data['status'] === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $jobId = $jobModel->insert($data);

        // Log activity
        $activityLog = $this->model('ActivityLog');
        $activityLog->log([
            'tenant_id' => $tenantId,
            'user_id' => $this->auth->user('id'),
            'action' => 'created',
            'description' => 'Created job: ' . $data['title'],
            'entity_type' => 'job',
            'entity_id' => $jobId
        ]);

        $this->session->setFlash('success', 'Job created successfully.');
        $this->redirect('/jobs/' . $jobId);
    }

    /**
     * Show edit job form
     */
    public function edit($id)
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter']);

        $tenantId = $this->getTenantId();
        $jobModel = $this->model('Job');

        $job = $jobModel->find($id, $tenantId);

        if (!$job) {
            $this->session->setFlash('error', 'Job not found.');
            $this->redirect('/jobs');
        }

        // Get hiring managers and pipelines
        $userModel = $this->model('User');
        $pipelineModel = $this->model('Pipeline');

        $hiringManagers = $userModel->getByTenant($tenantId, ['role' => 'hiring_manager']);
        $pipelines = $pipelineModel->findAll($tenantId);

        $this->view('jobs/edit', [
            'title' => 'Edit Job - SplashRecruit',
            'job' => $job,
            'hiringManagers' => $hiringManagers,
            'pipelines' => $pipelines
        ]);
    }

    /**
     * Update job
     */
    public function update($id)
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter']);
        $this->requireCsrf();

        $tenantId = $this->getTenantId();
        $jobModel = $this->model('Job');

        $job = $jobModel->find($id, $tenantId);

        if (!$job) {
            $this->session->setFlash('error', 'Job not found.');
            $this->redirect('/jobs');
        }

        // Validate
        $validator = Validation::make($this->request->post(), [
            'title' => 'required|min:3|max:255',
            'department' => 'required',
            'employment_type' => 'required',
            'description' => 'required'
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('error', $validator->firstError());
            $this->redirect('/jobs/' . $id . '/edit');
        }

        $data = [
            'title' => $this->request->post('title'),
            'department' => $this->request->post('department'),
            'location_type' => $this->request->post('location_type'),
            'location_city' => $this->request->post('location_city'),
            'location_country' => $this->request->post('location_country'),
            'employment_type' => $this->request->post('employment_type'),
            'salary_range_min' => $this->request->post('salary_range_min'),
            'salary_range_max' => $this->request->post('salary_range_max'),
            'salary_visible' => $this->request->post('salary_visible', 0),
            'description' => $this->request->post('description'),
            'requirements' => $this->request->post('requirements'),
            'benefits' => $this->request->post('benefits'),
            'status' => $this->request->post('status'),
            'hiring_manager_user_id' => $this->request->post('hiring_manager_user_id'),
            'updated_by' => $this->auth->user('id')
        ];

        // Update slug if title changed
        if ($data['title'] !== $job['title']) {
            $data['slug'] = Slug::generateUnique($data['title'], 'jobs', 'slug', $id, $tenantId);
        }

        // Update published_at if status changed to published
        if ($data['status'] === 'published' && $job['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $jobModel->update($id, $data, $tenantId);

        // Log activity
        $activityLog = $this->model('ActivityLog');
        $activityLog->log([
            'tenant_id' => $tenantId,
            'user_id' => $this->auth->user('id'),
            'action' => 'updated',
            'description' => 'Updated job: ' . $data['title'],
            'entity_type' => 'job',
            'entity_id' => $id
        ]);

        $this->session->setFlash('success', 'Job updated successfully.');
        $this->redirect('/jobs/' . $id);
    }

    /**
     * Delete job
     */
    public function delete($id)
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin']);
        $this->requireCsrf();

        $tenantId = $this->getTenantId();
        $jobModel = $this->model('Job');

        $job = $jobModel->find($id, $tenantId);

        if (!$job) {
            $this->session->setFlash('error', 'Job not found.');
            $this->redirect('/jobs');
        }

        $jobModel->delete($id, $tenantId);

        // Log activity
        $activityLog = $this->model('ActivityLog');
        $activityLog->log([
            'tenant_id' => $tenantId,
            'user_id' => $this->auth->user('id'),
            'action' => 'deleted',
            'description' => 'Deleted job: ' . $job['title'],
            'entity_type' => 'job',
            'entity_id' => $id
        ]);

        $this->session->setFlash('success', 'Job deleted successfully.');
        $this->redirect('/jobs');
    }
}
