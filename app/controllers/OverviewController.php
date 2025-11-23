<?php
if (!defined('PREVENT_DIRECT_SCRIPT_ACCESS')) {
    define('PREVENT_DIRECT_SCRIPT_ACCESS', true);
}

defined('PREVENT_DIRECT_SCRIPT_ACCESS') OR exit('No direct script access allowed');

class OverviewController extends Controller
{
    protected $userModel;
    protected $jobModel;
    protected $applicationModel;

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

    /**
     * Initialize required models
     */
    private function initializeModels()
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
    }

    /**
     * Overview page with query parameter support
     */
    public function index()
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

        // Add modal data to the data array
        $data['show_apply_modal'] = $show_apply_modal;
        $data['show_save_modal'] = $show_save_modal;
        $data['modal_job_id'] = $modal_job_id;
        $data['modal_job'] = $modal_job;
        
        if (isset($already_applied)) {
            $data['already_applied'] = $already_applied;
        }
        
        if (isset($already_saved)) {
            $data['already_saved'] = $already_saved;
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
     * Confirm apply job action
     */
    public function confirm_apply_job($jobId)
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login first';
            redirect('/overview');
            return;
        }

        $user = $this->getCurrentUser();
        $job = $this->jobModel->getJobById($jobId);
        
        if (!$job) {
            $_SESSION['error'] = 'Job not found';
            redirect('/overview');
            return;
        }

        $result = $this->jobModel->createApplication([
            'user_id' => $user['id'],
            'job_id' => $jobId,
            'status' => 'Applied',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($result) {
            $_SESSION['success'] = 'Successfully applied for ' . $job['title'] . ' at ' . $job['company'] . '!';
        } else {
            $_SESSION['error'] = 'You have already applied for this job';
        }
        
        redirect('/overview');
    }

    /**
     * Confirm save job action
     */
    public function confirm_save_job($jobId)
    {
        if (!$this->isLoggedIn()) {
            $_SESSION['error'] = 'Please login first';
            redirect('/overview');
            return;
        }

        $user = $this->getCurrentUser();
        $job = $this->jobModel->getJobById($jobId);
        
        if (!$job) {
            $_SESSION['error'] = 'Job not found';
            redirect('/overview');
            return;
        }

        $result = $this->jobModel->saveJob($user['id'], $jobId);

        if ($result['success']) {
            if ($result['action'] === 'saved') {
                $_SESSION['success'] = 'Job saved successfully! You can view it in your saved jobs.';
            } else {
                $_SESSION['success'] = 'Job removed from saved jobs.';
            }
        } else {
            $_SESSION['error'] = 'Failed to save job';
        }
        
        redirect('/overview');
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
}