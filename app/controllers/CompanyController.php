<?php
if (!defined('PREVENT_DIRECT_SCRIPT_ACCESS')) {
    define('PREVENT_DIRECT_SCRIPT_ACCESS', true);
}

defined('PREVENT_DIRECT_SCRIPT_ACCESS') OR exit('No direct script access allowed');

class CompanyController extends Controller
{
    protected $companyModel;
    protected $jobModel; // ADD THIS

    public function __construct()
    {
        parent::__construct();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize models
        $this->initializeModels();
    }

    /**
     * Initialize required models
     */
    public function initializeModels()
    {
        try {
            $this->companyModel = new Company_model();
            error_log("Company_model loaded successfully");
        } catch (Exception $e) {
            error_log("Failed to load Company_model: " . $e->getMessage());
        }

        // ADD JOB MODEL INITIALIZATION
        try {
            $this->jobModel = new Job_model();
            error_log("Job_model loaded successfully");
        } catch (Exception $e) {
            error_log("Failed to load Job_model: " . $e->getMessage());
        }
    }

    /**
     * Main companies listing page
     */
    public function index()
    {
        if (!$this->isLoggedIn()) {
            redirect('/login');
            return;
        }

        $user = $this->getCurrentUser();
        
        // Get filter parameters
        $filters = [
            'search' => $_GET['search'] ?? '',
            'industry' => $_GET['industry'] ?? '',
            'company_size' => $_GET['company_size'] ?? '',
            'location' => $_GET['location'] ?? '',
            'page' => $_GET['page'] ?? 1,
            'per_page' => 12
        ];

        // Get companies data
        $companiesData = $this->companyModel->getAllCompanies($filters);
        
        // Build data array
        $data = [
            'user' => $user,
            'current_page' => 'companies',
            'title' => 'Browse Companies - HireTech',
            'companies' => $companiesData['companies'],
            'total_companies' => $companiesData['total_companies'],
            'total_pages' => $companiesData['total_pages'],
            'current_page_num' => $companiesData['current_page'],
            'filters' => $filters,
            'industries' => $this->companyModel->getIndustries(),
            'company_sizes' => $this->companyModel->getCompanySizes()
        ];

        // Load sidebar data
        $sidebarData = $this->loadSidebarData($user);
        $data = array_merge($data, $sidebarData);

        $this->call->view('companies', $data);
    }
/**
 * Company detail page - FIXED VERSION
 */
public function view($id)
{
    if (!$this->isLoggedIn()) {
        redirect('/login');
        return;
    }

    $user = $this->getCurrentUser();
    
    // Get company details
    $company = $this->companyModel->getCompanyById($id);
    
    if (!$company) {
        $_SESSION['error'] = 'Company not found.';
        redirect('/companies');
        return;
    }

    // GET JOBS FOR THIS COMPANY - FIXED SECTION
    $companyName = $company['company_name'];
    error_log("Looking for jobs for company: " . $companyName);
    
    // Method 1: Try to get jobs using the company name
    $companyJobs = $this->getCompanyJobs($companyName);
    
    // Method 2: If no jobs found, try using user_id (fallback for old data)
    if (empty($companyJobs) && isset($company['user_id'])) {
        error_log("No jobs found by company name, trying user_id: " . $company['user_id']);
        $companyJobs = $this->companyModel->getCompanyRecentJobs($companyName, 10); // Increased limit
    }
    
    $company['recent_jobs'] = $companyJobs;
    $company['total_jobs'] = count($companyJobs);
    $company['active_jobs'] = count($companyJobs);
    
    error_log("Final job count for company: " . count($companyJobs));

    // Build data array
    $data = [
        'user' => $user,
        'current_page' => 'companies',
        'title' => $company['company_name'] . ' - HireTech',
        'company' => $company,
        'is_employer' => ($user['role'] === 'employer')
    ];

    // Load sidebar data
    $sidebarData = $this->loadSidebarData($user);
    $data = array_merge($data, $sidebarData);

    $this->call->view('company_detail', $data);
}
    /**
 * Get jobs for a specific company - IMPROVED VERSION
 */
private function getCompanyJobs($companyName)
{
    if (!$this->jobModel) {
        error_log("Job model not available for company jobs");
        return [];
    }

    // Method 1: Try to get jobs by company name directly
    if (method_exists($this->jobModel, 'getJobsByCompany')) {
        error_log("Using getJobsByCompany method for: " . $companyName);
        $jobs = $this->jobModel->getJobsByCompany($companyName);
        error_log("Found " . count($jobs) . " jobs using getJobsByCompany");
        return $jobs;
    }

    // Method 2: Try to search jobs by company name
    if (method_exists($this->jobModel, 'searchJobs')) {
        error_log("Using searchJobs method for company: " . $companyName);
        $jobs = $this->jobModel->searchJobs(['search' => $companyName]);
        error_log("Found " . count($jobs) . " jobs using searchJobs");
        return $jobs;
    }

    // Method 3: Get all jobs and filter by company (fallback)
    if (method_exists($this->jobModel, 'getAllJobs')) {
        error_log("Using getAllJobs and filtering for company: " . $companyName);
        $allJobs = $this->jobModel->getAllJobs();
        $filteredJobs = array_filter($allJobs, function($job) use ($companyName) {
            $match = isset($job['company']) && 
                    stripos($job['company'], $companyName) !== false;
            return $match;
        });
        error_log("Found " . count($filteredJobs) . " jobs using getAllJobs filter");
        return array_values($filteredJobs); // Reindex array
    }

    error_log("No method available to get company jobs");
    return [];
}

    /**
     * Load sidebar data
     */
    private function loadSidebarData($user)
    {
        $data = [];
        
        if ($user['role'] == 'job_seeker') {
            // Load application model for job seeker stats
            try {
                $applicationModel = new Application_model();
                $data['total_applications'] = $applicationModel ? $applicationModel->countUserApplications($user['id']) : 0;
                
                $jobModel = new Job_model();
                $data['saved_jobs_count'] = $jobModel ? $jobModel->countSavedJobs($user['id']) : 0;
            } catch (Exception $e) {
                error_log("Error loading sidebar models: " . $e->getMessage());
                $data['total_applications'] = 0;
                $data['saved_jobs_count'] = 0;
            }
        } else {
            // Load employer stats
            try {
                $applicationModel = new Application_model();
                $data['total_applications'] = $applicationModel ? $applicationModel->countEmployerApplications($user['id']) : 0;
                
                $jobModel = new Job_model();
                $data['total_jobs'] = $jobModel ? $jobModel->countEmployerJobs($user['id']) : 0;
            } catch (Exception $e) {
                error_log("Error loading sidebar models: " . $e->getMessage());
                $data['total_applications'] = 0;
                $data['total_jobs'] = 0;
            }
        }
        
        return $data;
    }

    /**
     * Check if user is logged in
     */
    private function isLoggedIn()
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    /**
     * Get current user
     */
    private function getCurrentUser()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            return null;
        }

        // Return session user data
        return $_SESSION['user'];
    }
}