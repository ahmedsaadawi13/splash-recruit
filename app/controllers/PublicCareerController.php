<?php
// FILE: /app/controllers/PublicCareerController.php

/**
 * PublicCareerController
 *
 * Handles public career pages and job applications (no authentication required).
 */
class PublicCareerController extends Controller
{
    private $tenant;

    public function careers($tenantSlug)
    {
        $this->loadTenant($tenantSlug);

        $jobModel = $this->model('Job');
        $jobs = $jobModel->getPublishedJobs($this->tenant['id'], []);

        $this->view('public_careers/index', [
            'title' => 'Careers at ' . $this->tenant['name'],
            'tenant' => $this->tenant,
            'jobs' => $jobs
        ]);
    }

    public function jobDetail($tenantSlug, $jobSlug)
    {
        $this->loadTenant($tenantSlug);

        $jobModel = $this->model('Job');
        $job = $jobModel->findBySlug($jobSlug, $this->tenant['id']);

        if (!$job || $job['status'] !== 'published') {
            http_response_code(404);
            echo '<h1>Job Not Found</h1>';
            exit;
        }

        $this->view('public_careers/job', [
            'title' => $job['title'] . ' - ' . $this->tenant['name'],
            'tenant' => $this->tenant,
            'job' => $job
        ]);
    }

    public function apply($tenantSlug, $jobSlug)
    {
        $this->loadTenant($tenantSlug);

        $jobModel = $this->model('Job');
        $job = $jobModel->findBySlug($jobSlug, $this->tenant['id']);

        if (!$job || $job['status'] !== 'published') {
            http_response_code(404);
            echo '<h1>Job Not Found</h1>';
            exit;
        }

        if (!$this->request->isPost()) {
            $this->redirect('/careers/' . $tenantSlug . '/jobs/' . $jobSlug);
        }

        // Validate
        $validator = Validation::make($this->request->post(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('error', $validator->firstError());
            $this->redirect('/careers/' . $tenantSlug . '/jobs/' . $jobSlug);
        }

        $candidateModel = $this->model('Candidate');
        $applicationModel = $this->model('Application');

        // Find or create candidate
        $candidate = $candidateModel->findByEmail($this->request->post('email'), $this->tenant['id']);

        if (!$candidate) {
            $candidateId = $candidateModel->insert([
                'tenant_id' => $this->tenant['id'],
                'first_name' => $this->request->post('first_name'),
                'last_name' => $this->request->post('last_name'),
                'email' => $this->request->post('email'),
                'phone' => $this->request->post('phone'),
                'city' => $this->request->post('city'),
                'country' => $this->request->post('country'),
                'current_company' => $this->request->post('current_company'),
                'current_title' => $this->request->post('current_title'),
                'linkedin_url' => $this->request->post('linkedin_url'),
                'source' => 'career_page'
            ]);
        } else {
            $candidateId = $candidate['id'];
        }

        // Check if already applied
        if ($applicationModel->hasApplied($candidateId, $job['id'])) {
            $this->session->setFlash('error', 'You have already applied to this job.');
            $this->redirect('/careers/' . $tenantSlug . '/jobs/' . $jobSlug);
        }

        // Get first stage
        $pipelineStageModel = $this->model('PipelineStage');
        $stages = $pipelineStageModel->getByPipeline($job['pipeline_id']);
        $firstStage = $stages[0] ?? null;

        // Create application
        $applicationId = $applicationModel->insert([
            'tenant_id' => $this->tenant['id'],
            'candidate_id' => $candidateId,
            'job_id' => $job['id'],
            'current_stage_id' => $firstStage ? $firstStage['id'] : null,
            'status' => 'applied',
            'source' => 'career_page'
        ]);

        // Handle CV upload
        if ($this->request->file('cv') && $this->request->file('cv')['error'] === UPLOAD_ERR_OK) {
            $fileUpload = new FileUpload($this->request->file('cv'));
            $fileUpload->setAllowedTypes(['pdf', 'doc', 'docx', 'application/pdf', 'application/msword']);
            $fileUpload->setMaxSize(5242880);

            $result = $fileUpload->upload();

            if ($result) {
                $fileModel = $this->model('CandidateFile');
                $fileModel->insert([
                    'tenant_id' => $this->tenant['id'],
                    'candidate_id' => $candidateId,
                    'application_id' => $applicationId,
                    'file_path' => $result['file_path'],
                    'file_name' => $result['stored_name'],
                    'original_name' => $result['original_name'],
                    'mime_type' => $result['mime_type'],
                    'size_bytes' => $result['size'],
                    'is_primary' => 1
                ]);
            }
        }

        // Send acknowledgement email
        $templateModel = $this->model('EmailTemplate');
        $template = $templateModel->getByCode('application_received', $this->tenant['id']);

        if ($template) {
            $mailer = new Mailer();
            $mailer->to($this->request->post('email'))
                   ->setTenantId($this->tenant['id'])
                   ->sendTemplate($template['id'], [
                       'candidate_name' => $this->request->post('first_name') . ' ' . $this->request->post('last_name'),
                       'job_title' => $job['title'],
                       'company_name' => $this->tenant['name'],
                       'tenant_id' => $this->tenant['id']
                   ]);
        }

        $this->session->setFlash('success', 'Your application has been submitted successfully!');
        $this->redirect('/careers/' . $tenantSlug . '/jobs/' . $jobSlug . '/thank-you');
    }

    public function thankYou($tenantSlug, $jobSlug)
    {
        $this->loadTenant($tenantSlug);

        $this->view('public_careers/thank_you', [
            'title' => 'Thank You - ' . $this->tenant['name'],
            'tenant' => $this->tenant
        ]);
    }

    private function loadTenant($slug)
    {
        $tenantModel = $this->model('Tenant');
        $this->tenant = $tenantModel->findBySlug($slug);

        if (!$this->tenant || $this->tenant['status'] !== 'active') {
            http_response_code(404);
            echo '<h1>Company Not Found</h1>';
            exit;
        }
    }
}
