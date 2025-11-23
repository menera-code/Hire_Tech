<?php
if (!defined('PREVENT_DIRECT_SCRIPT_ACCESS')) {
    define('PREVENT_DIRECT_SCRIPT_ACCESS', true);
}

defined('PREVENT_DIRECT_SCRIPT_ACCESS') OR exit('No direct script access allowed');

class AdminController extends Controller
{
    protected $adminModel;

    public function __construct()
    {
        parent::__construct();
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is admin
        if (!$this->isAdmin()) {
            redirect('/login');
            exit;
        }
        
        // Initialize admin model
        $this->initializeModels();
    }

    /**
     * Initialize admin model
     */
    public function initializeModels()
    {
        try {
            $this->adminModel = new Admin_model();
        } catch (Exception $e) {
            error_log("Failed to load Admin_model: " . $e->getMessage());
        }
    }

    /**
     * Check if user is admin
     */
    private function isAdmin()
    {
        return isset($_SESSION['user']) && 
               isset($_SESSION['user']['role']) && 
               $_SESSION['user']['role'] === 'admin';
    }

    /**
     * Admin dashboard overview
     */
    public function index()
    {
        $data = [
            'title' => 'Admin Dashboard - HireTech',
            'current_page' => 'dashboard'
        ];

        // Get admin stats
        $data['stats'] = $this->adminModel->getAdminStats();
        $data['recent_users'] = $this->adminModel->getRecentUsers(5);
        $data['recent_jobs'] = $this->adminModel->getRecentJobs(5);

        $this->call->view('admin', $data);
    }

    /**
     * User management page
     */
    public function users()
    {
        $data = [
            'title' => 'User Management - Admin',
            'current_page' => 'users'
        ];

        // Get all users with pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $data['users'] = $this->adminModel->getAllUsers($limit, $offset);
        $data['total_users'] = $this->adminModel->countAllUsers();
        $data['current_page'] = $page;
        $data['total_pages'] = ceil($data['total_users'] / $limit);

        // Handle user actions
        if (isset($_GET['action'])) {
            $this->handleUserAction();
        }

        $this->call->view('adminusers', $data);
    }

    /**
 * Job management page
 */
public function jobs()
{
    $data = [
        'title' => 'Job Management - Admin',
        'current_page' => 'jobs'
    ];

    // Get all jobs with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $data['jobs'] = $this->adminModel->getAllJobs($limit, $offset);
    $data['total_jobs'] = $this->adminModel->countAllJobs();
    $data['current_page'] = $page;
    $data['total_pages'] = ceil($data['total_jobs'] / $limit);

    // Handle job actions
    if (isset($_GET['action'])) {
        $this->handleJobAction();
    }

    $this->call->view('adminjobs', $data);
}
    /**
 * Handle user actions (delete, suspend, etc.)
 */
private function handleUserAction()
{
    $action = $_GET['action'];
    $userId = $_GET['id'] ?? null;

    if (!$userId) {
        $_SESSION['error'] = 'User ID is required';
        return;
    }

    // Validate user ID
    if (!is_numeric($userId)) {
        $_SESSION['error'] = 'Invalid user ID';
        return;
    }

    switch ($action) {
        case 'delete':
            $this->deleteUser($userId);
            break;
        case 'suspend':
            $this->suspendUser($userId);
            break;
        case 'activate':
            $this->activateUser($userId);
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            return;
    }

    // Redirect back to users page
    header('Location: /admin/users');
    exit();
}

   /**
 * Handle job actions (delete, feature, etc.)
 */
private function handleJobAction()
{
    $action = $_GET['action'];
    $jobId = $_GET['id'] ?? null;

    if (!$jobId) {
        $_SESSION['error'] = 'Job ID is required';
        return;
    }

    // Validate job ID
    if (!is_numeric($jobId)) {
        $_SESSION['error'] = 'Invalid job ID';
        return;
    }

    switch ($action) {
        case 'delete':
            $this->deleteJob($jobId);
            break;
        case 'feature':
            $this->featureJob($jobId);
            break;
        case 'unfeature':
            $this->unfeatureJob($jobId);
            break;
        default:
            $_SESSION['error'] = 'Invalid action';
            return;
    }

    // Redirect back to jobs page
    header('Location: /admin/jobs');
    exit();
}

   /**
 * Delete user
 */
private function deleteUser($userId)
{
    try {
        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['error'] = 'You cannot delete your own account';
            return false;
        }

        $result = $this->adminModel->deleteUser($userId);
        
        if ($result) {
            $_SESSION['success'] = 'User deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete user';
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error deleting user: " . $e->getMessage());
        $_SESSION['error'] = 'Error deleting user: ' . $e->getMessage();
        return false;
    }
}
    /**
 * Suspend user
 */
private function suspendUser($userId)
{
    try {
        // Prevent admin from suspending themselves
        if ($userId == $_SESSION['user']['id']) {
            $_SESSION['error'] = 'You cannot suspend your own account';
            return false;
        }

        $result = $this->adminModel->updateUserStatus($userId, 'suspended');
        
        if ($result) {
            $_SESSION['success'] = 'User suspended successfully';
        } else {
            $_SESSION['error'] = 'Failed to suspend user';
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error suspending user: " . $e->getMessage());
        $_SESSION['error'] = 'Error suspending user: ' . $e->getMessage();
        return false;
    }
}

    /**
 * Activate user
 */
private function activateUser($userId)
{
    try {
        $result = $this->adminModel->updateUserStatus($userId, 'active');
        
        if ($result) {
            $_SESSION['success'] = 'User activated successfully';
        } else {
            $_SESSION['error'] = 'Failed to activate user';
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error activating user: " . $e->getMessage());
        $_SESSION['error'] = 'Error activating user: ' . $e->getMessage();
        return false;
    }
}
/**
 * Delete job
 */
private function deleteJob($jobId)
{
    try {
        $result = $this->adminModel->deleteJob($jobId);
        
        if ($result) {
            $_SESSION['success'] = 'Job deleted successfully';
        } else {
            $_SESSION['error'] = 'Failed to delete job';
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Error deleting job: " . $e->getMessage());
        $_SESSION['error'] = 'Error deleting job: ' . $e->getMessage();
        return false;
    }
}


    /**
     * Feature job
     */
    private function featureJob($jobId)
    {
        try {
            $result = $this->adminModel->updateJobFeatured($jobId, 1);
            
            if ($result) {
                $_SESSION['success'] = 'Job featured successfully';
            } else {
                $_SESSION['error'] = 'Failed to feature job';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error featuring job: " . $e->getMessage());
            $_SESSION['error'] = 'Error featuring job: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Unfeature job
     */
    private function unfeatureJob($jobId)
    {
        try {
            $result = $this->adminModel->updateJobFeatured($jobId, 0);
            
            if ($result) {
                $_SESSION['success'] = 'Job unfeatured successfully';
            } else {
                $_SESSION['error'] = 'Failed to unfeature job';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error unfeaturing job: " . $e->getMessage());
            $_SESSION['error'] = 'Error unfeaturing job: ' . $e->getMessage();
            return false;
        }
    }

    /**
 * Application management page
 */
public function applications()
{
    $data = [
        'title' => 'Application Management - Admin',
        'current_page' => 'applications'
    ];

    // Get search and filter parameters
    $searchTerm = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    
    // Get all applications with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Use search if parameters provided, otherwise get all
    if (!empty($searchTerm) || $status !== 'all') {
        $data['applications'] = $this->adminModel->searchApplications($searchTerm, $status, $limit, $offset);
        $data['total_applications'] = $this->adminModel->countSearchApplications($searchTerm, $status);
    } else {
        $data['applications'] = $this->adminModel->getAllApplications($limit, $offset);
        $data['total_applications'] = $this->adminModel->countAllApplications();
    }
    
    $data['current_page'] = $page;
    $data['total_pages'] = ceil($data['total_applications'] / $limit);
    $data['search_term'] = $searchTerm;
    $data['filter_status'] = $status;

    // Handle application actions
    if (isset($_GET['action'])) {
        $this->handleApplicationAction();
    }

    $this->call->view('adminapplications', $data);
}

    /**
     * Handle application actions
     */
    private function handleApplicationAction()
    {
        $action = $_GET['action'];
        $applicationId = $_GET['id'] ?? null;

        if (!$applicationId) {
            $_SESSION['error'] = 'Application ID is required';
            return;
        }

        switch ($action) {
            case 'delete':
                $this->deleteApplication($applicationId);
                break;
            case 'approve':
                $this->approveApplication($applicationId);
                break;
            case 'reject':
                $this->rejectApplication($applicationId);
                break;
            case 'schedule_interview':
                $this->scheduleInterview($applicationId);
                break;
            case 'view':
                $this->viewApplication($applicationId);
                return; // Don't redirect for view action
        }

        // Redirect back to applications page
        header('Location: /admin/applications');
        exit();
    }

    /**
     * Delete application
     */
    private function deleteApplication($applicationId)
    {
        try {
            $result = $this->adminModel->deleteApplication($applicationId);
            
            if ($result) {
                $_SESSION['success'] = 'Application deleted successfully';
            } else {
                $_SESSION['error'] = 'Failed to delete application';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting application: " . $e->getMessage());
            $_SESSION['error'] = 'Error deleting application: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Approve application
     */
    private function approveApplication($applicationId)
    {
        try {
            $result = $this->adminModel->updateApplicationStatus($applicationId, 'Approved');
            
            if ($result) {
                $_SESSION['success'] = 'Application approved successfully';
            } else {
                $_SESSION['error'] = 'Failed to approve application';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error approving application: " . $e->getMessage());
            $_SESSION['error'] = 'Error approving application: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Reject application
     */
    private function rejectApplication($applicationId)
    {
        try {
            $result = $this->adminModel->updateApplicationStatus($applicationId, 'Rejected');
            
            if ($result) {
                $_SESSION['success'] = 'Application rejected successfully';
            } else {
                $_SESSION['error'] = 'Failed to reject application';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error rejecting application: " . $e->getMessage());
            $_SESSION['error'] = 'Error rejecting application: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Schedule interview for application
     */
    private function scheduleInterview($applicationId)
    {
        try {
            $result = $this->adminModel->updateApplicationStatus($applicationId, 'Interview Scheduled');
            
            if ($result) {
                $_SESSION['success'] = 'Interview scheduled successfully';
            } else {
                $_SESSION['error'] = 'Failed to schedule interview';
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error scheduling interview: " . $e->getMessage());
            $_SESSION['error'] = 'Error scheduling interview: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * View application details
     */
    private function viewApplication($applicationId)
    {
        $data = [
            'title' => 'Application Details - Admin',
            'current_page' => 'applications'
        ];

        $data['application'] = $this->adminModel->getApplicationById($applicationId);
        
        if (!$data['application']) {
            $_SESSION['error'] = 'Application not found';
            header('Location: /admin/applications');
            exit();
        }

        $this->call->view('adminapplicationdetails', $data);
    }

    /**
     * Debug method
     */
    public function debug()
    {
        $debugInfo = $this->adminModel->debugDatabase();
        echo "<pre>";
        print_r($debugInfo);
        echo "</pre>";
        die();
    }

    /**
     * Get all applications with pagination
     */
    public function getAllApplications($limit = 10, $offset = 0)
    {
        try {
            $result = $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, u.name as applicant_name, u.email as applicant_email, emp.name as employer_name')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->join('users emp', 'j.user_id = emp.id')
                ->order_by('a.created_at', 'DESC')
                ->get_all();

            if (is_array($result)) {
                return array_slice($result, $offset, $limit);
            }
            return [];
        } catch (Exception $e) {
            error_log("Error in getAllApplications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Count all applications
     */
    public function countAllApplications()
    {
        try {
            $result = $this->db->table('applications')->get_all();
            return is_array($result) ? count($result) : 0;
        } catch (Exception $e) {
            error_log("Error in countAllApplications: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update application status
     */
    public function updateApplicationStatus($applicationId, $status)
    {
        try {
            return $this->db->table('applications')
                ->where('id', $applicationId)
                ->update([
                    'status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        } catch (Exception $e) {
            error_log("Error in updateApplicationStatus: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete application
     */


    /**
     * Get application details by ID
     */
    public function getApplicationById($applicationId)
    {
        try {
            return $this->db->table('applications a')
                ->select('a.*, j.title as job_title, j.company as job_company, j.description as job_description, u.name as applicant_name, u.email as applicant_email, emp.name as employer_name, emp.email as employer_email')
                ->join('jobs j', 'a.job_id = j.id')
                ->join('users u', 'a.user_id = u.id')
                ->join('users emp', 'j.user_id = emp.id')
                ->where('a.id', $applicationId)
                ->get();
        } catch (Exception $e) {
            error_log("Error in getApplicationById: " . $e->getMessage());
            return null;
        }
    }

    /**
 * Search applications
 */
public function searchApplications()
{
    $searchTerm = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    
    $data = [
        'title' => 'Application Management - Admin',
        'current_page' => 'applications'
    ];

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Get filtered applications
    $data['applications'] = $this->adminModel->searchApplications($searchTerm, $status, $limit, $offset);
    $data['total_applications'] = $this->adminModel->countSearchApplications($searchTerm, $status);
    $data['current_page'] = $page;
    $data['total_pages'] = ceil($data['total_applications'] / $limit);
    $data['search_term'] = $searchTerm;
    $data['filter_status'] = $status;

    $this->call->view('adminapplications', $data);
}

/**
 * Get application statistics
 */
public function getApplicationStats()
{
    try {
        $stats = $this->adminModel->getApplicationStats();
        return $stats;
    } catch (Exception $e) {
        error_log("Error getting application stats: " . $e->getMessage());
        return [];
    }
}

/**
 * Logout admin user
 */
public function logout()
{
    // Clear all session data
    session_destroy();
    
    // Redirect to login page
    header('Location: /');
    exit();
}
}