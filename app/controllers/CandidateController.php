<?php
// FILE: /app/controllers/CandidateController.php

/**
 * CandidateController
 *
 * Handles candidate management.
 */
class CandidateController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();

        $candidateModel = $this->model('Candidate');

        $filters = array_filter([
            'search' => $this->request->get('search'),
            'source' => $this->request->get('source')
        ]);

        $page = max(1, (int)$this->request->get('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $candidates = $candidateModel->getByTenant($tenantId, $filters, $perPage, $offset);
        $totalCandidates = $candidateModel->countByTenant($tenantId, $filters);

        $paginator = new Paginator($totalCandidates, $perPage, $page);

        $this->view('candidates/index', [
            'title' => 'Candidates - SplashRecruit',
            'candidates' => $candidates,
            'paginator' => $paginator,
            'filters' => $filters
        ]);
    }

    public function show($id)
    {
        $this->requireAuth();
        $tenantId = $this->getTenantId();

        $candidateModel = $this->model('Candidate');
        $candidate = $candidateModel->getWithApplications($id, $tenantId);

        if (!$candidate) {
            $this->session->setFlash('error', 'Candidate not found.');
            $this->redirect('/candidates');
        }

        // Get applications
        $applicationModel = $this->model('Application');
        $applications = $applicationModel->getByTenant($tenantId, ['candidate_id' => $id], 50, 0);

        // Get files
        $fileModel = $this->model('CandidateFile');
        $files = $fileModel->getByCandidate($id, $tenantId);

        // Get tags
        $tags = $candidateModel->getTags($id);

        $this->view('candidates/show', [
            'title' => $candidate['full_name'] . ' - SplashRecruit',
            'candidate' => $candidate,
            'applications' => $applications,
            'files' => $files,
            'tags' => $tags
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter', 'hr_assistant']);

        $this->view('candidates/create', [
            'title' => 'Add Candidate - SplashRecruit'
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->requireRole(['tenant_admin', 'recruiter', 'hr_assistant']);
        $this->requireCsrf();

        $tenantId = $this->getTenantId();

        $validator = Validation::make($this->request->post(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            $this->session->setFlash('error', $validator->firstError());
            $this->redirect('/candidates/create');
        }

        $candidateModel = $this->model('Candidate');

        $data = [
            'tenant_id' => $tenantId,
            'first_name' => $this->request->post('first_name'),
            'last_name' => $this->request->post('last_name'),
            'email' => $this->request->post('email'),
            'phone' => $this->request->post('phone'),
            'country' => $this->request->post('country'),
            'city' => $this->request->post('city'),
            'current_company' => $this->request->post('current_company'),
            'current_title' => $this->request->post('current_title'),
            'years_of_experience' => $this->request->post('years_of_experience'),
            'linkedin_url' => $this->request->post('linkedin_url'),
            'source' => 'manual'
        ];

        $candidateId = $candidateModel->insert($data);

        // Handle file upload
        if ($this->request->file('cv') && $this->request->file('cv')['error'] === UPLOAD_ERR_OK) {
            $this->uploadCandidateCV($candidateId, $tenantId);
        }

        $this->session->setFlash('success', 'Candidate added successfully.');
        $this->redirect('/candidates/' . $candidateId);
    }

    private function uploadCandidateCV($candidateId, $tenantId)
    {
        $fileUpload = new FileUpload($this->request->file('cv'));
        $fileUpload->setAllowedTypes(['pdf', 'doc', 'docx', 'application/pdf', 'application/msword']);
        $fileUpload->setMaxSize(5242880); // 5MB

        $result = $fileUpload->upload();

        if ($result) {
            $fileModel = $this->model('CandidateFile');
            $fileModel->insert([
                'tenant_id' => $tenantId,
                'candidate_id' => $candidateId,
                'file_path' => $result['file_path'],
                'file_name' => $result['stored_name'],
                'original_name' => $result['original_name'],
                'mime_type' => $result['mime_type'],
                'size_bytes' => $result['size'],
                'is_primary' => 1,
                'uploaded_by' => $this->auth->user('id')
            ]);
        }
    }
}
