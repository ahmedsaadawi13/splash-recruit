<?php
// FILE: /app/controllers/ApiController.php

/**
 * ApiController
 *
 * Handles public API endpoints for external integrations.
 */
class ApiController extends Controller
{
    private $tenant;

    public function __construct()
    {
        parent::__construct();
        $this->authenticateApiKey();
    }

    private function authenticateApiKey()
    {
        $apiKey = $this->request->get('api_key') ?? $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$apiKey) {
            $this->json(['success' => false, 'message' => 'API key required.'], 401);
        }

        $db = Database::getInstance();
        $db->query("SELECT t.* FROM tenants t
                    INNER JOIN tenant_api_keys tak ON t.id = tak.tenant_id
                    WHERE tak.api_key = :api_key AND tak.is_active = 1 AND t.status = 'active'
                    LIMIT 1");
        $db->bind(':api_key', $apiKey);
        $this->tenant = $db->fetch();

        if (!$this->tenant) {
            $this->json(['success' => false, 'message' => 'Invalid API key.'], 401);
        }

        // Update last used
        $db->query("UPDATE tenant_api_keys SET last_used_at = NOW() WHERE api_key = :api_key");
        $db->bind(':api_key', $apiKey);
        $db->execute();
    }

    public function getJobs()
    {
        $jobModel = $this->model('Job');

        $filters = array_filter([
            'department' => $this->request->get('department'),
            'location_city' => $this->request->get('location'),
            'employment_type' => $this->request->get('type')
        ]);

        $jobs = $jobModel->getPublishedJobs($this->tenant['id'], $filters);

        $response = [
            'success' => true,
            'data' => array_map(function($job) {
                return [
                    'id' => $job['id'],
                    'title' => $job['title'],
                    'slug' => $job['slug'],
                    'reference_code' => $job['reference_code'],
                    'department' => $job['department'],
                    'location_city' => $job['location_city'],
                    'location_country' => $job['location_country'],
                    'location_type' => $job['location_type'],
                    'employment_type' => $job['employment_type'],
                    'description' => strip_tags($job['description']),
                    'published_at' => $job['published_at'],
                    'apply_url' => getenv('APP_URL') . '/careers/' . $this->tenant['slug'] . '/jobs/' . $job['slug']
                ];
            }, $jobs)
        ];

        $this->json($response);
    }

    public function createApplication()
    {
        if (!$this->request->isPost()) {
            $this->json(['success' => false, 'message' => 'Method not allowed.'], 405);
        }

        $jobId = $this->request->post('job_id');
        $jobReference = $this->request->post('job_reference');

        // Find job
        $jobModel = $this->model('Job');
        $job = null;

        if ($jobId) {
            $job = $jobModel->find($jobId, $this->tenant['id']);
        } elseif ($jobReference) {
            $db = Database::getInstance();
            $db->query("SELECT * FROM jobs WHERE reference_code = :ref AND tenant_id = :tenant_id LIMIT 1");
            $db->bind(':ref', $jobReference);
            $db->bind(':tenant_id', $this->tenant['id']);
            $job = $db->fetch();
        }

        if (!$job || $job['status'] !== 'published') {
            $this->json(['success' => false, 'message' => 'Job not found or not available.'], 404);
        }

        // Validate candidate data
        $validator = Validation::make($this->request->post(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            $this->json(['success' => false, 'message' => $validator->firstError()], 400);
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
                'source' => $this->request->post('source', 'api')
            ]);
        } else {
            $candidateId = $candidate['id'];
        }

        // Check if already applied
        if ($applicationModel->hasApplied($candidateId, $job['id'])) {
            $this->json(['success' => false, 'message' => 'Candidate has already applied to this job.'], 400);
        }

        // Get first stage of pipeline
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
            'source' => $this->request->post('source', 'api')
        ]);

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

        $this->json([
            'success' => true,
            'message' => 'Application submitted successfully.',
            'data' => [
                'application_id' => $applicationId,
                'candidate_id' => $candidateId
            ]
        ], 201);
    }

    public function getApplicationStatus($applicationId)
    {
        $applicationModel = $this->model('Application');
        $application = $applicationModel->find($applicationId, $this->tenant['id']);

        if (!$application) {
            $this->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        $this->json([
            'success' => true,
            'data' => [
                'application_id' => $application['id'],
                'status' => $application['status'],
                'applied_at' => $application['applied_at'],
                'last_updated' => $application['last_status_change_at']
            ]
        ]);
    }
}
