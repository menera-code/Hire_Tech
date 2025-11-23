<?php
if (!defined('PREVENT_DIRECT_SCRIPT_ACCESS')) {
    define('PREVENT_DIRECT_SCRIPT_ACCESS', true);
}

defined('PREVENT_DIRECT_SCRIPT_ACCESS') OR exit('No direct script access allowed');

class DashboardController extends Controller
{
    protected $userModel;
    protected $jobModel;
    protected $applicationModel;
    protected $savedJobModel; // ADD THIS PROPERTY
    protected $companyModel; // ADD THIS PROPERTY


    public function __construct()
    {
        parent::__construct();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in
        if (!$this->isLoggedIn()) {
            redirect('/login');
            exit;
        }
        
        // Initialize models
        $this->initializeModels();
    }

public function initializeModels()
{
    try {
        $this->userModel = new User_model();
        error_log("User_model loaded successfully");
    } catch (Exception $e) {
        error_log("Failed to load User_model: " . $e->getMessage());
    }
    
    try {
        $this->jobModel = new Job_model();
        error_log("Job_model loaded successfully");
    } catch (Exception $e) {
        error_log("Failed to load Job_model: " . $e->getMessage());
    }
    
    try {
        $this->applicationModel = new Application_model();
        error_log("Application_model loaded successfully");
    } catch (Exception $e) {
        error_log("Failed to load Application_model: " . $e->getMessage());
    }

    try {
        $this->savedJobModel = new Savedjob_model();
        error_log("Savedjob_model loaded successfully");
    } catch (Exception $e) {
        error_log("Failed to load Savedjob_model: " . $e->getMessage());
    }

    // ADD THIS SECTION FOR Company_model
    try {
        $this->companyModel = new Company_model();
        error_log("Company_model loaded successfully");
    } catch (Exception $e) {
        error_log("Failed to load Company_model: " . $e->getMessage());
    }
}

    /**
     * Default route - redirect to overview
     */
    public function index()
    {
        $this->overview();
    }
public function overview()
{
    if (!$this->isLoggedIn()) {
        redirect('/login');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        redirect('/login');
        return;
    }

    // Handle remove application request
    if (isset($_GET['remove_application'])) {
        $application_id = $_GET['application_id'] ?? null;
        
        if ($application_id) {
            $result = $this->removeApplication($application_id, $user['id']);
            
            if ($result) {
                $_SESSION['success'] = "Application removed successfully!";
            } else {
                $_SESSION['error'] = "Failed to remove application. Please try again.";
            }
            
            // Redirect to clear URL parameters
            header("Location: /dashboard/overview");
            exit();
        }
    }

    // Build the complete data array
    $data = [
        'user' => $user,
        'current_page' => 'overview',
        'title' => $this->getPageTitle('overview', $user['role'])
    ];

    // Add sidebar data
    $sidebarData = $this->loadSidebarData($user);
    $data = array_merge($data, $sidebarData);

    // Add overview-specific data
    $pageData = $this->loadOverviewData($user);
    $data = array_merge($data, $pageData);

    // Handle modal data - pass job data through controller instead of accessing model in view
    $data = $this->handleModalData($data, $user);

    // Handle applicant modal data for employers
    if ($user['role'] === 'employer') {
        $data = $this->handleApplicantModalData($data, $user);
    }

    $this->call->view('overview', $data);
}

    /**
     * Handle modal data for overview page
     */
    private function handleModalData($data, $user)
    {
        // Check if we need to show a modal
        $show_apply_modal = isset($_GET['apply_job']);
        $show_save_modal = isset($_GET['save_job']);
        $modal_job_id = $_GET['job_id'] ?? null;

        // Check if we need to show remove application confirmation modal
        $show_remove_modal = isset($_GET['remove_application_confirm']);
        $remove_application_id = $_GET['application_id'] ?? null;

        // Get job details for modal if needed
        $modal_job = null;
        if ($modal_job_id && ($show_apply_modal || $show_save_modal) && $this->jobModel) {
            $modal_job = $this->jobModel->getJobById($modal_job_id);
            
            // Check if already applied
            if ($show_apply_modal && $modal_job) {
                $already_applied = $this->applicationModel ? 
                    $this->applicationModel->hasUserApplied($user['id'], $modal_job_id) : false;
            }
            
            // Check if already saved
            if ($show_save_modal && $modal_job) {
                $already_saved = $this->jobModel->isJobSaved($user['id'], $modal_job_id);
            }
        }

        // Get application details for remove modal
        $remove_application = null;
        if ($show_remove_modal && $remove_application_id && $this->applicationModel) {
            $remove_application = $this->applicationModel->getApplicationByIdAndUser($remove_application_id, $user['id']);
        }

        // Add modal data to the data array
        $data['show_apply_modal'] = $show_apply_modal;
        $data['show_save_modal'] = $show_save_modal;
        $data['show_remove_modal'] = $show_remove_modal;
        $data['modal_job_id'] = $modal_job_id;
        $data['modal_job'] = $modal_job;
        $data['remove_application_id'] = $remove_application_id;
        $data['remove_application'] = $remove_application;
        
        if (isset($already_applied)) {
            $data['already_applied'] = $already_applied;
        }
        
        if (isset($already_saved)) {
            $data['already_saved'] = $already_saved;
        }

        return $data;
    }

    /**
     * Remove application
     */
    private function removeApplication($application_id, $user_id)
    {
        if (!$this->applicationModel) {
            return false;
        }
        
        // Verify the application belongs to the user
        $application = $this->applicationModel->getApplicationByIdAndUser($application_id, $user_id);
        
        if (!$application) {
            return false;
        }
        
        // Delete the application
        return $this->applicationModel->deleteApplication($application_id, $user_id);
    }

    /**
     * Main dashboard loader
     */
    public function load($page = 'overview')
    {
        error_log("DashboardController::load() called with page: " . $page);
        
        if (!$this->isLoggedIn()) {
            error_log("User not logged in, redirecting to login");
            redirect('/login');
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            error_log("Could not get current user, redirecting to login");
            redirect('/login');
            return;
        }

        error_log("Loading dashboard for user: " . $user['name'] . " (ID: " . $user['id'] . ", Role: " . $user['role'] . ")");

        // Handle reject application request for application page
        if ($page === 'application' && isset($_GET['reject_application'])) {
            $application_id = $_GET['application_id'] ?? null;
            
            if ($application_id) {
                $result = $this->applicationModel->rejectApplication($application_id);
                
                if ($result) {
                    $_SESSION['success'] = "Application rejected successfully!";
                } else {
                    $_SESSION['error'] = "Failed to reject application. Please try again.";
                }
                
                // Redirect to clear URL parameters
                header("Location: /dashboard/load/application");
                exit();
            }
        }

        // Build the complete data array
        $data = [];
        
        // Add user and page info first
        $data['user'] = $user;
        $data['current_page'] = $page;
        $data['title'] = $this->getPageTitle($page, $user['role']);
        
        // Add sidebar data
        $sidebarData = $this->loadSidebarData($user);
        $data = array_merge($data, $sidebarData);
        
        // Add page-specific data (this might override some sidebar data, which is fine)
        $pageData = $this->loadPageData($user, $page);
        $data = array_merge($data, $pageData);

        // Handle remove modal for application page
        if ($page === 'application') {
            $show_remove_modal = isset($_GET['remove_application_confirm']);
            $remove_application_id = $_GET['application_id'] ?? null;
            $remove_application = null;

            if ($show_remove_modal && $remove_application_id) {
                $applications = $pageData['applications'] ?? [];
                foreach ($applications as $app) {
                    if ($app['id'] == $remove_application_id) {
                        $remove_application = $app;
                        break;
                    }
                }
            }

            $data['show_remove_modal'] = $show_remove_modal;
            $data['remove_application_id'] = $remove_application_id;
            $data['remove_application'] = $remove_application;
        }

        // Load the view
        $this->call->view($page, $data);
    }

    /**
     * Jobs page - handle both display and form submissions
     */
    public function jobs()
    {
        if (!$this->isLoggedIn()) {
            redirect('/login');
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            redirect('/login');
            return;
        }

        // Handle form submissions for employers (both modal and regular forms)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'employer') {
            $this->handleJobFormSubmission($user);
        }

        $data = [
            'user' => $user,
            'current_page' => 'jobs',
            'title' => $this->getPageTitle('jobs', $user['role'])
        ];

        // Add sidebar data
        $sidebarData = $this->loadSidebarData($user);
        $data = array_merge($data, $sidebarData);

        // Add jobs-specific data
        $jobsData = $this->loadJobsData($user);
        $data = array_merge($data, $jobsData);

        $this->call->view('jobs', $data);
    }

    /**
     * Handle job form submissions
     */
    private function handleJobFormSubmission($user)
    {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'post_job':
                $this->handlePostJobForm($user);
                break;
                
            case 'edit_job':
                $this->handleEditJobForm($user);
                break;
                
            case 'delete_job':
                $this->handleDeleteJobForm($user);
                break;
        }
    }
private function handlePostJobForm($user)
{
    // Get and validate form data
    $title = $this->io->post('title');
    $location = $this->io->post('location');
    $description = $this->io->post('description');
    $job_type = $this->io->post('job_type') ?? 'Full-time';
    $salary = $this->io->post('salary') ?? '';
    $category = $this->io->post('category') ?? '';
    $requirements = $this->io->post('requirements') ?? '';
    $benefits = $this->io->post('benefits') ?? '';

    if (empty($title) || empty($location) || empty($description)) {
        $_SESSION['error'] = 'All required fields must be filled';
        return;
    }

    // Get company name from company profile instead of form
    $company_name = $this->getUserCompanyName($user['id']);
    
    if (empty($company_name)) {
        $_SESSION['error'] = 'Please set up your company profile first before posting jobs';
        return;
    }

    $jobData = [
        'user_id' => $user['id'],
        'title' => $title,
        'company' => $company_name, // Use company from profile, not form
        'location' => $location,
        'description' => $description,
        'job_type' => $job_type,
        'salary' => $salary,
        'category' => $category,
        'requirements' => $requirements,
        'benefits' => $benefits,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $result = $this->jobModel->createJob($jobData);

    if ($result) {
        $_SESSION['success'] = 'Job posted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to post job. Please try again.';
    }
}

private function handleEditJobForm($user)
{
    $jobId = $_POST['job_id'] ?? null;
    
    if (!$jobId) {
        $_SESSION['error'] = 'Job ID is required';
        return;
    }

    // Check if job exists and belongs to this employer
    $job = $this->jobModel->getJobById($jobId);
    if (!$job || $job['user_id'] != $user['id']) {
        $_SESSION['error'] = 'Job not found or access denied';
        return;
    }

    // Get and validate form data
    $title = $this->io->post('title');
    $location = $this->io->post('location');
    $description = $this->io->post('description');
    $job_type = $this->io->post('job_type') ?? 'Full-time';
    $salary = $this->io->post('salary') ?? '';
    $category = $this->io->post('category') ?? '';
    $requirements = $this->io->post('requirements') ?? '';
    $benefits = $this->io->post('benefits') ?? '';

    if (empty($title) || empty($location) || empty($description)) {
        $_SESSION['error'] = 'All required fields must be filled';
        return;
    }

    // Get company name from company profile
    $company_name = $this->getUserCompanyName($user['id']);
    
    if (empty($company_name)) {
        $_SESSION['error'] = 'Company profile not found';
        return;
    }

    $jobData = [
        'title' => $title,
        'company' => $company_name, // Use company from profile
        'location' => $location,
        'description' => $description,
        'job_type' => $job_type,
        'salary' => $salary,
        'category' => $category,
        'requirements' => $requirements,
        'benefits' => $benefits,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $result = $this->jobModel->updateJob($jobId, $jobData);

    if ($result) {
        $_SESSION['success'] = 'Job updated successfully!';
    } else {
        $_SESSION['error'] = 'Failed to update job. Please try again.';
    }
}
/**
 * Get user's company name - IMPROVED VERSION
 */
private function getUserCompanyName($userId)
{
    // Try Company_model first
    if ($this->companyModel && method_exists($this->companyModel, 'getCompanyByUserId')) {
        $companyProfile = $this->companyModel->getCompanyByUserId($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile['company_name'];
        }
    }
    
    // Try User_model as fallback
    if ($this->userModel && method_exists($this->userModel, 'getCompanyProfile')) {
        $companyProfile = $this->userModel->getCompanyProfile($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile['company_name'];
        }
    }
    
    // Try Profile_model as another fallback
    if (class_exists('Profile_model')) {
        $profileModel = new Profile_model();
        $companyProfile = $profileModel->getCompanyProfileByUserId($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile['company_name'];
        }
    }
    
    // Final fallback - check if there's a company field in user data
    $user = $this->getCurrentUser();
    if (!empty($user['company_name'])) {
        return $user['company_name'];
    }
    
    return '';
}
    /**
     * Handle delete job form submission (modal-based)
     */
    private function handleDeleteJobForm($user)
    {
        $jobId = $_POST['job_id'] ?? null;
        
        if (!$jobId) {
            $_SESSION['error'] = 'Job ID is required';
            return;
        }

        // Check if job exists and belongs to this employer
        $job = $this->jobModel->getJobById($jobId);
        if (!$job || $job['user_id'] != $user['id']) {
            $_SESSION['error'] = 'Job not found or access denied';
            return;
        }

        $result = $this->jobModel->deleteJob($jobId);

        if ($result) {
            $_SESSION['success'] = 'Job deleted successfully!';
        } else {
            $_SESSION['error'] = 'Failed to delete job. Please try again.';
        }
    }

    /**
     * Get job data for AJAX requests
     */
    public function get_job($jobId)
    {
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            return;
        }

        $user = $this->getCurrentUser();
        $job = $this->jobModel->getJobById($jobId);
        
        if (!$job) {
            echo json_encode(['success' => false, 'message' => 'Job not found']);
            return;
        }

        // Check if user owns the job (for employers)
        if ($user['role'] == 'employer' && $job['user_id'] != $user['id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        echo json_encode([
            'success' => true,
            'job' => $job
        ]);
    }

    /**
     * Load page-specific data
     */
    private function loadPageData($user, $page)
    {
        $data = [];
        
        switch ($page) {
            case 'overview':
                $data = $this->loadOverviewData($user);
                break;
            case 'jobs':
                $data = $this->loadJobsData($user);
                break;
            case 'applications':
                $data = $this->loadApplicationsData($user);
                break;
            case 'profile':
                $data = $this->loadProfileData($user);
                break;
            case 'saved':
                $data = $this->loadSavedData($user);
                break;
            case 'company':
                $data = $this->loadCompanyData($user);
                break;
        }
        
        return $data;
    }

    /**
     * Load sidebar-specific data
     */
    private function loadSidebarData($user)
    {
        $data = [];
        
        if ($user['role'] == 'job_seeker') {
            $data['total_applications'] = $this->applicationModel ? $this->applicationModel->countUserApplications($user['id']) : 0;
            $data['saved_jobs_count'] = $this->jobModel ? $this->jobModel->countSavedJobs($user['id']) : 0;
        } else {
            $data['total_applications'] = $this->applicationModel ? $this->applicationModel->countEmployerApplications($user['id']) : 0;
            $data['total_jobs'] = $this->jobModel ? $this->jobModel->countEmployerJobs($user['id']) : 0;
        }
        
        return $data;
    }

    /**
     * Get page title
     */
    private function getPageTitle($page, $role)
    {
        $titles = [
            'overview' => 'Dashboard Overview',
            'jobs' => $role == 'job_seeker' ? 'Browse Jobs' : 'Manage Jobs',
            'applications' => $role == 'job_seeker' ? 'My Applications' : 'Job Applications',
            'profile' => 'My Profile',
            'saved' => 'Saved Jobs',
            'company' => 'Company Profile'
        ];
        
        return ($titles[$page] ?? 'Dashboard') . ' - ' . ucfirst($role);
    }

    /**
     * Load overview page data
     */
    private function loadOverviewData($user)
    {
        $data = [];
        
        if ($user['role'] == 'job_seeker') {
            $data = [
                'total_applications' => $this->applicationModel ? $this->applicationModel->countUserApplications($user['id']) : 0,
                'interview_scheduled' => $this->applicationModel ? $this->applicationModel->countInterviews($user['id']) : 0,
                'saved_jobs_count' => $this->jobModel ? $this->jobModel->countSavedJobs($user['id']) : 0,
                'saved_jobs' => $this->jobModel ? $this->jobModel->getUserSavedJobs($user['id']) : [],
                'profile_views' => 0,
                'recent_applications' => $this->applicationModel ? $this->applicationModel->getRecentApplications($user['id'], 5) : [],
                'recent_jobs' => $this->jobModel ? array_slice($this->jobModel->getRecentJobs(10), 0, 3) : []
            ];
        } else {
            $data = [
                'total_jobs' => $this->jobModel ? $this->jobModel->countEmployerJobs($user['id']) : 0,
                'total_applications' => $this->applicationModel ? $this->applicationModel->countEmployerApplications($user['id']) : 0,
                'interview_scheduled' => $this->applicationModel ? $this->applicationModel->countEmployerInterviews($user['id']) : 0,
                'new_applicants' => $this->applicationModel ? $this->applicationModel->countNewApplicants($user['id']) : 0,
                'recent_applications' => $this->applicationModel ? $this->applicationModel->getEmployerRecentApplications($user['id'], 5) : [],
                'recent_jobs' => $this->jobModel ? array_slice($this->jobModel->getEmployerRecentJobs($user['id'], 10), 0, 3) : []
            ];
        }
        
        return $data;
    }

 /**
 * Load jobs-specific data based on user role - FIXED VERSION
 */
private function loadJobsData($user)
{
    $is_employer = ($user['role'] === 'employer');
    
    // Handle search filters for job seekers
    $filters = [];
    if (!$is_employer && !empty($_GET)) {
        $filters = array_filter([
            'search' => $_GET['search'] ?? '',
            'location' => $_GET['location'] ?? '',
            'job_type' => $_GET['job_type'] ?? '',
            'category' => $_GET['category'] ?? '',
            'salary_range' => $_GET['salary_range'] ?? ''
        ]);
    }
    
    if ($is_employer) {
        // Employer view - get their own jobs
        $jobs = $this->jobModel->getEmployerJobs($user['id']);
        $total_jobs = $this->jobModel->countEmployerJobs($user['id']);
        
        // Get company profile data for the forms
        $company_profile = $this->getUserCompanyProfile($user['id']);
        
        return [
            'jobs' => $jobs,
            'total_jobs' => $total_jobs,
            'saved_jobs' => [],
            'applied_job_ids' => [],
            'company_profile' => $company_profile
        ];
    } else {
        // Job seeker view - get all jobs (filtered if search criteria exist)
        if (!empty($filters)) {
            $jobs = $this->jobModel->searchJobs($filters);
        } else {
            $jobs = $this->jobModel->getAllJobs();
        }
        
        // ENSURE ALL REQUIRED FIELDS ARE PRESENT
        $jobs = $this->ensureJobDataCompleteness($jobs);
        
        $saved_jobs = $this->jobModel->getUserSavedJobs($user['id']);
        $applied_job_ids = $this->applicationModel->getUserAppliedJobIds($user['id']);
        
        return [
            'jobs' => $jobs,
            'total_jobs' => count($jobs),
            'saved_jobs' => $saved_jobs,
            'applied_job_ids' => $applied_job_ids
        ];
    }
}

/**
 * Ensure job data has all required fields for the modal - NEW METHOD
 */
private function ensureJobDataCompleteness($jobs)
{
    if (empty($jobs)) {
        return $jobs;
    }
    
    $completeJobs = [];
    foreach ($jobs as $job) {
        // Ensure all required fields exist with default values
        $completeJob = array_merge([
            'id' => 0,
            'title' => 'No Title',
            'company' => 'No Company',
            'location' => 'No Location',
            'job_type' => 'Not Specified',
            'salary' => 'Not Specified',
            'category' => 'Not Specified',
            'description' => 'No description available.',
            'requirements' => 'No requirements specified.',
            'benefits' => 'No benefits specified.',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ], $job);
        
        $completeJobs[] = $completeJob;
    }
    
    return $completeJobs;
}
    /**
     * Load applications page data
     */
    private function loadApplicationsData($user)
    {
        if ($user['role'] == 'job_seeker') {
            return [
                'applications' => $this->applicationModel ? $this->applicationModel->getUserApplications($user['id']) : [],
                'stats' => [
                    'total' => $this->applicationModel ? $this->applicationModel->countUserApplications($user['id']) : 0,
                    'interviews' => $this->applicationModel ? $this->applicationModel->countInterviews($user['id']) : 0,
                    'rejected' => $this->applicationModel ? $this->applicationModel->countRejectedApplications($user['id']) : 0
                ]
            ];
        } else {
            return [
                'applications' => $this->applicationModel ? $this->applicationModel->getEmployerApplications($user['id']) : [],
                'stats' => [
                    'total' => $this->applicationModel ? $this->applicationModel->countEmployerApplications($user['id']) : 0,
                    'interviews' => $this->applicationModel ? $this->applicationModel->countEmployerInterviews($user['id']) : 0,
                    'new' => $this->applicationModel ? $this->applicationModel->countNewApplicants($user['id']) : 0
                ]
            ];
        }
    }

    /**
     * Load profile page data
     */
    private function loadProfileData($user)
    {
        $profile = null;
        if ($this->userModel && method_exists($this->userModel, 'getUserProfile')) {
            $profile = $this->userModel->getUserProfile($user['id']);
        }
        
        return [
            'profile' => $profile,
            'skills' => []
        ];
    }

    /**
     * Load saved jobs page data
     */
    private function loadSavedData($user)
    {
        return [
            'saved_jobs' => $this->jobModel ? $this->jobModel->getUserSavedJobs($user['id']) : [],
            'total_saved' => $this->jobModel ? $this->jobModel->countSavedJobs($user['id']) : 0
        ];
    }

    /**
     * Load company profile page data
     */
    private function loadCompanyData($user)
    {
        $company = null;
        if ($this->userModel && method_exists($this->userModel, 'getCompanyProfile')) {
            $company = $this->userModel->getCompanyProfile($user['id']);
        }
        
        return [
            'company' => $company,
            'total_jobs' => $this->jobModel ? $this->jobModel->countEmployerJobs($user['id']) : 0,
            'active_jobs' => $this->jobModel ? $this->jobModel->countActiveEmployerJobs($user['id']) : 0
        ];
    }

    // ==================== FORM ACTIONS ====================

    /**
     * Apply for a job
     */
    public function apply_job()
    {
        // Get job_id from query string
        $jobId = $_GET['job_id'] ?? null;
        
        error_log("DashboardController::apply_job called with jobId: " . $jobId);

        if (!$jobId) {
            $_SESSION['error'] = 'Job ID is required';
            redirect('/dashboard/overview');
            return;
        }

        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login first';
            redirect('/dashboard/overview');
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            $_SESSION['error'] = 'Session error';
            redirect('/dashboard/overview');
            return;
        }

        if (!$this->jobModel) {
            $_SESSION['error'] = 'System error';
            redirect('/dashboard/overview');
            return;
        }

        // Check if job exists
        $job = $this->jobModel->getJobById($jobId);
        if (!$job) {
            $_SESSION['error'] = 'Job not found';
            redirect('/dashboard/overview');
            return;
        }

        // Check if already applied
        $alreadyApplied = $this->applicationModel ? 
            $this->applicationModel->hasUserApplied($user['id'], $jobId) : false;

        if ($alreadyApplied) {
            $_SESSION['error'] = 'You have already applied for ' . $job['title'] . ' at ' . $job['company'];
            redirect('/dashboard/overview');
            return;
        }

        // Create application
        $result = $this->jobModel->createApplication([
            'user_id' => $user['id'],
            'job_id' => $jobId,
            'status' => 'Applied',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $_SESSION['success'] = 'Successfully applied for ' . $job['title'] . ' at ' . $job['company'] . '!';
        } else {
            $_SESSION['error'] = 'Failed to apply for this job. Please try again.';
        }
        
        redirect('/dashboard/overview');
    }

    /**
 * Save a job - FIXED VERSION
 */
public function save_job()
{
    // Get job_id from query string
    $jobId = $_GET['job_id'] ?? null;
    
    error_log("🔧 save_job called with jobId: " . $jobId);

    if (!$jobId) {
        $_SESSION['error'] = 'Job ID is required';
        redirect('/dashboard/overview');
        return;
    }

    if (!$this->isLoggedIn()) {
        $_SESSION['error'] = 'Please login first';
        redirect('/dashboard/overview');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        $_SESSION['error'] = 'System error';
        redirect('/dashboard/overview');
        return;
    }

    // Check if job exists
    $job = $this->jobModel->getJobById($jobId);
    if (!$job) {
        $_SESSION['error'] = 'Job not found';
        redirect('/dashboard/overview');
        return;
    }

    error_log("👤 User: " . $user['id'] . ", Job: " . $jobId);

    // Use Savedjob_model if available, otherwise fallback to Job_model
    if ($this->savedJobModel) {
        error_log("📁 Using Savedjob_model");
        $result = $this->savedJobModel->toggleSaveJob($user['id'], $jobId);
    } else {
        error_log("📁 Using Job_model fallback");
        // Fallback to Job_model if Savedjob_model not available
        $result = $this->jobModel->saveJob($user['id'], $jobId);
    }

    error_log("🔧 Save result: " . print_r($result, true));

    if ($result['success']) {
        if ($result['action'] === 'saved') {
            $_SESSION['success'] = 'Job saved successfully!';
        } else {
            $_SESSION['success'] = 'Job removed from saved jobs!';
        }
    } else {
        $_SESSION['error'] = 'Failed to save job. Please try again.';
    }
    
    redirect('/dashboard/overview');
}
    // ==================== HELPER METHODS ====================

    /**
     * Check if user is logged in
     */
    private function isLoggedIn()
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    /**
     * Get current user with fresh data from database
     */
    private function getCurrentUser()
    {
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
            redirect('/login');
            return null;
        }

        $user_id = $_SESSION['user']['id'];
        
        // Get fresh user data from database
        if ($this->userModel && method_exists($this->userModel, 'getUserById')) {
            try {
                $user = $this->userModel->getUserById($user_id);
                if ($user) {
                    // Update session with fresh data
                    $_SESSION['user'] = array_merge($_SESSION['user'], $user);
                    return $user;
                }
            } catch (Exception $e) {
                error_log("Error getting user from database: " . $e->getMessage());
            }
        }
        
        // Fallback to session data
        return $_SESSION['user'];
    }

    /**
     * Get job details for AJAX
     */
    public function get_job_details($jobId) {
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            return;
        }

        $job = $this->jobModel->getJobById($jobId);
        
        if ($job) {
            echo json_encode(['success' => true, 'data' => $job]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Job not found']);
        }
    }

    /**
     * Applications page
     */
    public function application()
    {
        if (!$this->isLoggedIn()) {
            redirect('/login');
            return;
        }

        $user = $this->getCurrentUser();
        if (!$user) {
            redirect('/login');
            return;
        }

        // Handle reject application from URL parameter
        if (isset($_GET['reject_application'])) {
            $applicationId = $_GET['application_id'] ?? null;
            
            if ($applicationId) {
                $result = $this->applicationModel->rejectApplication($applicationId);
                
                if ($result) {
                    $_SESSION['success'] = "Application rejected successfully!";
                } else {
                    $_SESSION['error'] = "Failed to reject application. Please try again.";
                }
                
                // Redirect to clear URL parameters
                header("Location: /dashboard/application");
                exit();
            }
        }

        // Handle remove application request
        if (isset($_GET['remove_application'])) {
            $application_id = $_GET['application_id'] ?? null;
            
            if ($application_id) {
                $result = $this->removeApplication($application_id, $user['id']);
                
                if ($result) {
                    $_SESSION['success'] = "Application removed successfully!";
                } else {
                    $_SESSION['error'] = "Failed to remove application. Please try again.";
                }
                
                // Redirect to clear URL parameters
                header("Location: /dashboard/application");
                exit();
            }
        }

        // Build the data array
        $data = [
            'user' => $user,
            'current_page' => 'application',
            'title' => $this->getPageTitle('application', $user['role'])
        ];

        // Add sidebar data
        $sidebarData = $this->loadSidebarData($user);
        $data = array_merge($data, $sidebarData);

        // Add applications-specific data
        $applicationsData = $this->loadApplicationsData($user);
        $data = array_merge($data, $applicationsData);

        // Load the view with data
        $this->call->view('application', $data);
    }

   /**
 * Get interview details for job seeker - DEBUG VERSION
 */
public function get_interview_details()
{
    error_log("🎯 get_interview_details called");
    
    // Set JSON header immediately
    header('Content-Type: application/json');
    
    if (!$this->isLoggedIn()) {
        error_log("❌ User not logged in");
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        return;
    }

    $user = $this->getCurrentUser();
    error_log("👤 User: " . $user['name'] . " (ID: " . $user['id'] . ", Role: " . $user['role'] . ")");
    
    if ($user['role'] !== 'job_seeker') {
        error_log("❌ User is not job seeker");
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    $applicationId = $_GET['application_id'] ?? null;
    error_log("📋 Application ID from GET: " . $applicationId);
    
    if (empty($applicationId)) {
        error_log("❌ No application ID provided");
        echo json_encode(['success' => false, 'message' => 'Application ID is required']);
        return;
    }

    // Debug: Check if models are loaded
    if (!$this->applicationModel) {
        error_log("❌ Application model not loaded");
        echo json_encode(['success' => false, 'message' => 'System error: Model not loaded']);
        return;
    }

    error_log("🔍 Calling getInterviewDetailsForJobSeeker with: " . $applicationId . ", " . $user['id']);
    
    try {
        $interview = $this->applicationModel->getInterviewDetailsForJobSeeker($applicationId, $user['id']);
        
        if ($interview) {
            error_log("✅ Interview found: " . print_r($interview, true));
            echo json_encode([
                'success' => true, 
                'interview' => $interview
            ]);
        } else {
            error_log("❌ No interview found for application ID: " . $applicationId);
            
            // Debug: Check if application exists at all
            $application = $this->applicationModel->getApplicationByIdAndUser($applicationId, $user['id']);
            if (!$application) {
                error_log("❌ Application doesn't exist or doesn't belong to user");
            } else {
                error_log("📋 Application exists but no interview data: " . print_r($application, true));
            }
            
            echo json_encode([
                'success' => false, 
                'message' => 'Interview details not found for this application'
            ]);
        }
    } catch (Exception $e) {
        error_log("💥 Exception in get_interview_details: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
}

 public function schedule_interview()
{
    error_log("=== SCHEDULE_INTERVIEW METHOD CALLED ===");
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!$this->isLoggedIn()) {
        $_SESSION['error'] = 'Please login first';
        header('Location: /dashboard/load/application');
        exit();
    }

    $user = $this->getCurrentUser();
    if ($user['role'] !== 'employer') {
        $_SESSION['error'] = 'Access denied';
        header('Location: /dashboard/load/application');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $_SESSION['error'] = 'Invalid request method';
        header('Location: /dashboard/load/application');
        exit();
    }

    try {
        // Extract data from POST
        $applicationId = $_POST['application_id'] ?? null;
        $interviewDate = $_POST['interview_date'] ?? null;
        $interviewType = $_POST['interview_type'] ?? null;
        $interviewNotes = $_POST['interview_notes'] ?? null;
        $interviewLocation = $_POST['interview_location'] ?? null;
        $interviewDuration = $_POST['interview_duration'] ?? 60;
        $isReschedule = isset($_POST['is_reschedule']) && $_POST['is_reschedule'] == '1';

        // Validation
        if (empty($applicationId) || empty($interviewDate)) {
            throw new Exception('Application ID and interview date are required');
        }

        // Verify application exists and belongs to this employer
        $application = $this->applicationModel->getApplicationById($applicationId);
        if (!$application) {
            throw new Exception('Application not found');
        }

        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $user['id']) {
            throw new Exception('Access denied');
        }

        // Prepare interview data
        $interviewData = [
            'interview_date' => $interviewDate,
            'interview_type' => $interviewType,
            'interview_notes' => $interviewNotes,
            'interview_location' => $interviewLocation,
            'interview_duration' => $interviewDuration,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Update database
        $result = $this->applicationModel->updateInterviewWithCalendar($applicationId, $interviewData);
        
        if (!$result) {
            throw new Exception('Failed to save interview details');
        }

        $action = $isReschedule ? 'rescheduled' : 'scheduled';
        $message = "Interview {$action} successfully!";
        
        // Set success message in session
        $_SESSION['success'] = $message;
        
        error_log("✅ Success: " . $message);
        
        // Return JSON response for AJAX
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
        } else {
            header('Location: /application');
        }
        exit();

    } catch (Exception $e) {
        error_log("💥 ERROR: " . $e->getMessage());
        
        // Set error message in session
        $_SESSION['error'] = $e->getMessage();
        
        // Return JSON response for AJAX
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } else {
            header('Location: /application');
        }
        exit();
    }
}

/**
 * Check if request is AJAX
 */

    /**
     * Helper method for JSON responses
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    /**
     * Reject application - POST endpoint
     */
    public function reject_application()
    {
        if (!$this->isLoggedIn()) {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Please login first']);
                return;
            }
            redirect('/login');
            return;
        }

        $user = $this->getCurrentUser();
        if ($user['role'] !== 'employer') {
            if ($this->isAjaxRequest()) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            $_SESSION['error'] = 'Access denied';
            redirect('/dashboard/application');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $applicationId = $this->io->post('application_id');

            if (empty($applicationId)) {
                $_SESSION['error'] = 'Application ID is required';
                redirect('/dashboard/application');
                return;
            }

            // Verify the application belongs to this employer's job
            $application = $this->applicationModel->getApplicationById($applicationId);
            if (!$application) {
                $_SESSION['error'] = 'Application not found';
                redirect('/dashboard/application');
                return;
            }

            // Verify the job belongs to this employer
            $job = $this->jobModel->getJobById($application['job_id']);
            if (!$job || $job['user_id'] != $user['id']) {
                $_SESSION['error'] = 'Access denied';
                redirect('/dashboard/application');
                return;
            }

            $result = $this->applicationModel->rejectApplication($applicationId);

            if ($result) {
                $_SESSION['success'] = 'Application rejected successfully!';
            } else {
                $_SESSION['error'] = 'Failed to reject application. Please try again.';
            }
            
            redirect('/application');
        } else {
            $_SESSION['error'] = 'Invalid request method';
            redirect('/dashboard/application');
        }
    }

    /**
     * Check if request is AJAX
     */
    private function isAjaxRequest()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Hire applicant
     */
    public function hire_applicant()
    {
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            return;
        }

        $user = $this->getCurrentUser();
        if ($user['role'] !== 'employer') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $applicationId = $this->io->post('application_id');

            if (empty($applicationId)) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required']);
                return;
            }

            // Verify the application belongs to this employer's job
            $application = $this->applicationModel->getApplicationById($applicationId);
            if (!$application) {
                echo json_encode(['success' => false, 'message' => 'Application not found']);
                return;
            }

            // Verify the job belongs to this employer
            $job = $this->jobModel->getJobById($application['job_id']);
            if (!$job || $job['user_id'] != $user['id']) {
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            $result = $this->applicationModel->hireApplicant($applicationId);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Applicant hired successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to hire applicant']);
            }
        }
    }

    /**
     * Remove application - AJAX endpoint
     */
    public function remove_application()
    {
        // Set JSON header
        header('Content-Type: application/json');
        
        if (!$this->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Please login first']);
            return;
        }

        $user = $this->getCurrentUser();
        if ($user['role'] !== 'job_seeker') {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $application_id = $this->io->post('application_id');

            if (empty($application_id)) {
                echo json_encode(['success' => false, 'message' => 'Application ID is required']);
                return;
            }

            // Verify the application belongs to this user
            $application = $this->applicationModel->getApplicationByIdAndUser($application_id, $user['id']);
            if (!$application) {
                echo json_encode(['success' => false, 'message' => 'Application not found or access denied']);
                return;
            }

            // Delete the application
            $result = $this->applicationModel->deleteApplication($application_id, $user['id']);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Application removed successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove application. Please try again.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        }
    }

  /**
 * Get interview details for rescheduling - Employer only
 */
public function get_interview_details_for_reschedule()
{
    error_log("🔄 get_interview_details_for_reschedule called");
    
    // Set JSON header immediately
    header('Content-Type: application/json');
    
    if (!$this->isLoggedIn()) {
        error_log("❌ User not logged in");
        echo json_encode(['success' => false, 'message' => 'Please login first']);
        return;
    }

    $user = $this->getCurrentUser();
    if ($user['role'] !== 'employer') {
        error_log("❌ User is not employer");
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }

    $applicationId = $_GET['application_id'] ?? null;
    error_log("📋 Application ID from GET: " . $applicationId);
    
    if (empty($applicationId)) {
        error_log("❌ No application ID provided");
        echo json_encode(['success' => false, 'message' => 'Application ID is required']);
        return;
    }

    try {
        // Get application with verification that it belongs to this employer
        $application = $this->applicationModel->getApplicationById($applicationId);
        
        if (!$application) {
            error_log("❌ Application not found");
            echo json_encode(['success' => false, 'message' => 'Application not found']);
            return;
        }

        // Verify the job belongs to this employer
        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $user['id']) {
            error_log("❌ Job doesn't belong to employer");
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }

        error_log("✅ Interview data found for reschedule: " . print_r($application, true));
        echo json_encode([
            'success' => true, 
            'interview' => $application
        ]);
        
    } catch (Exception $e) {
        error_log("💥 Exception in get_interview_details_for_reschedule: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'System error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Saved jobs page
 */
/**
 * Saved jobs page - FIXED VERSION
 */
/**
 * Saved jobs page - FIXED VERSION
 */
public function saved()
{
    if (!$this->isLoggedIn()) {
        redirect('/login');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        redirect('/login');
        return;
    }

    // Handle individual unsave job request
    if (isset($_GET['unsave_job'])) {
        $jobId = $_GET['job_id'] ?? null;
        
        if ($jobId && $this->savedJobModel) {
            $result = $this->savedJobModel->toggleSaveJob($user['id'], $jobId);
            
            if ($result['success']) {
                if ($result['action'] === 'unsaved') {
                    $_SESSION['success'] = "Job unsaved successfully!";
                } else {
                    $_SESSION['success'] = "Job saved successfully!";
                }
            } else {
                $_SESSION['error'] = "Failed to unsave job. Please try again.";
            }
            
            // Redirect to clear URL parameters
            header("Location: /dashboard/saved");
            exit();
        }
    }

    // Handle bulk unsave
    if (isset($_GET['bulk_unsave'])) {
        $jobIds = $_GET['job_ids'] ?? '';
        if (!empty($jobIds)) {
            $jobIdArray = explode(',', $jobIds);
            $successCount = 0;
            
            foreach ($jobIdArray as $jobId) {
                if (!empty(trim($jobId)) && $this->savedJobModel) {
                    $result = $this->savedJobModel->removeSavedJob($user['id'], trim($jobId));
                    if ($result) {
                        $successCount++;
                    }
                }
            }
            
            if ($successCount > 0) {
                $_SESSION['success'] = "Successfully unsaved {$successCount} job(s)!";
            } else {
                $_SESSION['error'] = "Failed to unsave selected jobs.";
            }
            
            header("Location: /dashboard/saved");
            exit();
        }
    }

    // Build the data array
    $data = [
        'user' => $user,
        'current_page' => 'saved',
        'title' => $this->getPageTitle('saved', $user['role'])
    ];

    // Add sidebar data
    $sidebarData = $this->loadSidebarData($user);
    $data = array_merge($data, $sidebarData);

    // Add saved jobs specific data
    $savedData = $this->loadSavedData($user);
    $data = array_merge($data, $savedData);

    // Handle modal data
    $data = $this->handleSavedModalData($data, $user);

    $this->call->view('saved', $data);
}

/**
 * Handle modal data for saved jobs page
 */
private function handleSavedModalData($data, $user)
{
    // Check if we need to show a modal
    $show_apply_modal = isset($_GET['apply_job']);
    $show_unsave_modal = isset($_GET['unsave_confirm']);
    $show_job_details_modal = isset($_GET['job_details']);
    $modal_job_id = $_GET['job_id'] ?? null;

    // Get job details for modal if needed
    $modal_job = null;
    if ($modal_job_id && ($show_apply_modal || $show_unsave_modal || $show_job_details_modal) && $this->jobModel) {
        $modal_job = $this->jobModel->getJobById($modal_job_id);
        
        // Check if already applied
        if ($show_apply_modal && $modal_job) {
            $already_applied = $this->applicationModel ? 
                $this->applicationModel->hasUserApplied($user['id'], $modal_job_id) : false;
        }
    }

    // Add modal data to the data array
    $data['show_apply_modal'] = $show_apply_modal;
    $data['show_unsave_modal'] = $show_unsave_modal;
    $data['show_job_details_modal'] = $show_job_details_modal;
    $data['modal_job_id'] = $modal_job_id;
    $data['modal_job'] = $modal_job;
    
    if (isset($already_applied)) {
        $data['already_applied'] = $already_applied;
    }

    return $data;
}

/**
 * Unsave a job - SPECIFIC FOR SAVED JOBS PAGE
 */
public function unsave_job()
{
    error_log("🔧 unsave_job called");
    
    // Get job_id from query string
    $jobId = $_GET['job_id'] ?? null;
    
    error_log("📋 Job ID: " . $jobId);

    if (!$jobId) {
        $_SESSION['error'] = 'Job ID is required';
        redirect('/dashboard/saved');
        return;
    }

    if (!$this->isLoggedIn()) {
        $_SESSION['error'] = 'Please login first';
        redirect('/dashboard/saved');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        $_SESSION['error'] = 'System error';
        redirect('/dashboard/saved');
        return;
    }

    // Check if job exists
    $job = $this->jobModel->getJobById($jobId);
    if (!$job) {
        $_SESSION['error'] = 'Job not found';
        redirect('/dashboard/saved');
        return;
    }

    error_log("👤 User: " . $user['id'] . ", Job: " . $jobId);

    // Remove from saved jobs
    if ($this->savedJobModel) {
        error_log("📁 Using Savedjob_model to remove saved job");
        $result = $this->savedJobModel->removeSavedJob($user['id'], $jobId);
        
        error_log("🔧 Remove result: " . ($result ? 'true' : 'false'));
        
        if ($result) {
            $_SESSION['success'] = 'Job removed from saved jobs!';
            error_log("✅ Job successfully unsaved");
        } else {
            $_SESSION['error'] = 'Failed to remove job from saved jobs. Please try again.';
            error_log("❌ Failed to unsave job");
        }
    } else {
        error_log("❌ Savedjob_model not available");
        $_SESSION['error'] = 'System error: Cannot process unsave request';
    }
    
    redirect('/saved');
}

/**
 * Bulk unsave jobs - SPECIFIC FOR SAVED JOBS PAGE
 */
public function bulk_unsave_job()
{
    error_log("🔧 bulk_unsave_job called");
    
    // Get job_ids from query string
    $jobIds = $_GET['job_ids'] ?? '';
    
    error_log("📋 Job IDs: " . $jobIds);

    if (empty($jobIds)) {
        $_SESSION['error'] = 'No jobs selected';
        redirect('/dashboard/saved');
        return;
    }

    if (!$this->isLoggedIn()) {
        $_SESSION['error'] = 'Please login first';
        redirect('/dashboard/saved');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        $_SESSION['error'] = 'System error';
        redirect('/dashboard/saved');
        return;
    }

    $jobIdArray = explode(',', $jobIds);
    $successCount = 0;
    $totalCount = 0;
    
    error_log("👤 User: " . $user['id'] . ", Jobs to unsave: " . count($jobIdArray));

    foreach ($jobIdArray as $jobId) {
        $jobId = trim($jobId);
        if (!empty($jobId)) {
            $totalCount++;
            
            // Remove from saved jobs
            if ($this->savedJobModel) {
                $result = $this->savedJobModel->removeSavedJob($user['id'], $jobId);
                if ($result) {
                    $successCount++;
                    error_log("✅ Unsaved job ID: " . $jobId);
                } else {
                    error_log("❌ Failed to unsave job ID: " . $jobId);
                }
            }
        }
    }

    error_log("📊 Bulk unsave results: " . $successCount . "/" . $totalCount . " successful");
    
    if ($successCount > 0) {
        $_SESSION['success'] = "Successfully unsaved {$successCount} job(s)!";
    } else {
        $_SESSION['error'] = "Failed to unsave selected jobs.";
    }
    
    redirect('/saved');
}


/**
 * Handle applicant details modal
 */
private function handleApplicantModalData($data, $user)
{
    // Check if we need to show applicant modal
    $show_applicant_modal = isset($_GET['view_applicant']);
    $applicant_application_id = $_GET['view_applicant'] ?? null;

    $applicant_data = null;
    if ($show_applicant_modal && $applicant_application_id) {
        $applicant_data = $this->getApplicantData($applicant_application_id, $user['id']);
    }

    // Add modal data to the data array
    $data['show_applicant_modal'] = $show_applicant_modal;
    $data['applicant_data'] = $applicant_data;

    return $data;
}

/**
 * Get applicant data for modal
 */
private function getApplicantData($applicationId, $employerId)
{
    if (!$this->applicationModel || !$this->jobModel || !$this->userModel) {
        return null;
    }

    try {
        // Get application details
        $application = $this->applicationModel->getApplicationById($applicationId);
        
        if (!$application) {
            return null;
        }

        // Verify the job belongs to this employer
        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $employerId) {
            return null;
        }

        // Get applicant user data
        $applicant = $this->userModel->getUserById($application['user_id']);
        if (!$applicant) {
            return null;
        }

        // Get applicant profile - try different methods
        $applicantProfile = null;
        
        // Try using Profile_model if available
        if (class_exists('Profile_model')) {
            $profileModel = new Profile_model();
            $applicantProfile = $profileModel->getUserProfileByUserId($application['user_id']);
        } 
        // Fallback: try to get profile directly from user model
        else if (method_exists($this->userModel, 'getUserProfile')) {
            $applicantProfile = $this->userModel->getUserProfile($application['user_id']);
        }
        // Final fallback: check if user model has the method with different name
        else if (method_exists($this->userModel, 'getUserProfileByUserId')) {
            $applicantProfile = $this->userModel->getUserProfileByUserId($application['user_id']);
        }

        return [
            'application' => $application,
            'job' => $job,
            'applicant' => array_merge($applicant, ['profile' => $applicantProfile ?: []])
        ];

    } catch (Exception $e) {
        error_log("Error getting applicant data: " . $e->getMessage());
        return null;
    }
}

/**
 * Applicant details page - for use across multiple pages
 */
public function applicant()
{
    if (!$this->isLoggedIn()) {
        redirect('/login');
        return;
    }

    $user = $this->getCurrentUser();
    if (!$user) {
        redirect('/login');
        return;
    }

    // Only employers can view applicant details
    if ($user['role'] !== 'employer') {
        $_SESSION['error'] = 'Access denied';
        redirect('/dashboard/overview');
        return;
    }

    // Check if view_applicant parameter is set
    $applicationId = $_GET['view_applicant'] ?? null;
    
    if (!$applicationId) {
        $_SESSION['error'] = 'Application ID is required';
        redirect('/dashboard/overview');
        return;
    }

    // Build the data array
    $data = [
        'user' => $user,
        'current_page' => 'overview', // or whatever page you're coming from
        'title' => 'Applicant Details'
    ];

    // Add sidebar data
    $sidebarData = $this->loadSidebarData($user);
    $data = array_merge($data, $sidebarData);

    // Add overview-specific data (or data from the referring page)
    $pageData = $this->loadOverviewData($user);
    $data = array_merge($data, $pageData);

    // Handle applicant modal data
    $data = $this->handleApplicantModalData($data, $user);

    // Load the same overview view (or whatever view contains your modal)
    $this->call->view('application', $data);
}
/**
 * Get user company profile - NEW HELPER METHOD
 */
/**
 * Get user company profile - IMPROVED VERSION
 */
private function getUserCompanyProfile($userId)
{
    // Try Company_model first
    if ($this->companyModel && method_exists($this->companyModel, 'getCompanyByUserId')) {
        $companyProfile = $this->companyModel->getCompanyByUserId($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile;
        }
    }
    
    // Try User_model as fallback
    if ($this->userModel && method_exists($this->userModel, 'getCompanyProfile')) {
        $companyProfile = $this->userModel->getCompanyProfile($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile;
        }
    }
    
    // Try Profile_model as another fallback
    if (class_exists('Profile_model')) {
        $profileModel = new Profile_model();
        $companyProfile = $profileModel->getCompanyProfileByUserId($userId);
        if ($companyProfile && !empty($companyProfile['company_name'])) {
            return $companyProfile;
        }
    }
    
    return null;
}

/**
 * Cancel application - for job seekers to withdraw their applications (Non-AJAX)
 */
public function cancel_application()
{
    error_log("🔧 cancel_application called");
    
    if (!$this->isLoggedIn()) {
        $_SESSION['error'] = 'Please login first';
        redirect('/dashboard/applications');
        return;
    }

    $user = $this->getCurrentUser();
    if ($user['role'] !== 'job_seeker') {
        $_SESSION['error'] = 'Access denied';
        redirect('/dashboard/applications');
        return;
    }

    $application_id = $_GET['application_id'] ?? null;
    
    if (empty($application_id)) {
        $_SESSION['error'] = 'Application ID is required';
        redirect('/dashboard/applications');
        return;
    }

    // Verify the application belongs to this user
    $application = $this->applicationModel->getApplicationByIdAndUser($application_id, $user['id']);
    if (!$application) {
        $_SESSION['error'] = 'Application not found or access denied';
        redirect('/dashboard/applications');
        return;
    }

    // Check if application can be cancelled (not already rejected/hired/cancelled)
    if ($application['status'] === 'Rejected') {
        $_SESSION['error'] = 'This application has already been rejected and cannot be cancelled.';
        redirect('/dashboard/applications');
        return;
    }

    if ($application['status'] === 'Hired') {
        $_SESSION['error'] = 'This application has already been accepted and cannot be cancelled.';
        redirect('/dashboard/applications');
        return;
    }

    if ($application['status'] === 'Cancelled') {
        $_SESSION['error'] = 'This application has already been cancelled.';
        redirect('/dashboard/applications');
        return;
    }

    // Update application status to "Cancelled"
    $result = $this->applicationModel->updateApplicationStatus($application_id, 'Cancelled');

    if ($result) {
        $_SESSION['success'] = 'Application cancelled successfully!';
    } else {
        $_SESSION['error'] = 'Failed to cancel application. Please try again.';
    }
    
    redirect('/dashboard/applications');
}

}