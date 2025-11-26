<?php
if (!defined('PREVENT_DIRECT_SCRIPT_ACCESS')) {
    define('PREVENT_DIRECT_SCRIPT_ACCESS', true);
}

defined('PREVENT_DIRECT_SCRIPT_ACCESS') OR exit('No direct script access allowed');

class ProfileController extends Controller
{
    protected $profileModel;
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
    public function initializeModels()
    {
        try {
            $this->profileModel = new Profile_model();
            error_log("Profile_model loaded successfully");
        } catch (Exception $e) {
            error_log("Failed to load Profile_model: " . $e->getMessage());
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

// In your ProfileController.php - Add these methods

/**
 * Main overview method - enhanced to handle applicant modal
 */
public function index()
{
    if (!$this->isLoggedIn()) {
        redirect('/login');
        return;
    }

    $user = $this->getCurrentUser();
    
    // Handle applicant modal request
    if (isset($_GET['view_applicant']) && is_numeric($_GET['view_applicant'])) {
        return $this->showApplicantModal($user, $_GET['view_applicant']);
    }

    // Your existing index method continues here...
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $this->handleProfileUpdate($user);
        redirect('/profile');
        return;
    }

    // Build data array
    $data = [
        'user' => $user,
        'current_page' => 'profile',
        'title' => $this->getPageTitle($user['role'])
    ];

    // Load profile data based on role
    $profileData = $this->loadProfileData($user);
    $data = array_merge($data, $profileData);

    // Load sidebar data
    $sidebarData = $this->loadSidebarData($user);
    $data = array_merge($data, $sidebarData);

    $this->call->view('profile', $data);
}

    /**
     * Handle profile form submission
     */
    private function handleProfileUpdate($user)
    {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_profile') {
            $this->updateUserProfile($user);
        } elseif ($action === 'update_company') {
            $this->updateCompanyProfile($user);
        }
    }

    /**
     * Update user profile (job seeker)
     */
    private function updateUserProfile($user)
    {
        // Basic user data
        $name = $this->io->post('name');
        $email = $this->io->post('email');
        $phone = $this->io->post('phone');
        $address = $this->io->post('address');

        // Profile data
        $professional_headline = $this->io->post('headline');
        $professional_summary = $this->io->post('bio');
        $skills = $this->io->post('skills');
        $work_experience = $this->io->post('experience');
        $education = $this->io->post('education');

        // Validate required fields
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Name and email are required fields.';
            return;
        }

        // Check if email is unique
        if (!$this->profileModel->isEmailUnique($email, $user['id'])) {
            $_SESSION['error'] = 'This email is already taken. Please use a different email.';
            return;
        }

        // Update basic user info
        $userData = [
            'name' => $name,
            'email' => $email
        ];

        // Only update password if provided
        $password = $this->io->post('password');
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['error'] = 'Password must be at least 6 characters long.';
                return;
            }
            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $userUpdated = $this->profileModel->updateUser($user['id'], $userData);

        // Update profile data for job seeker
        $profileData = [
            'professional_headline' => $professional_headline,
            'professional_summary' => $professional_summary,
            'skills' => $skills,
            'work_experience' => $work_experience,
            'education' => $education,
            'phone' => $phone,
            'address' => $address,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle resume file upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadResume($user['id']);
            if ($uploadResult['success']) {
                $profileData['resume_file'] = $uploadResult['file_path'];
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                return;
            }
        }

        // Update profile
        $profileUpdated = $this->profileModel->saveUserProfile($user['id'], $profileData);
        
        if ($userUpdated || $profileUpdated) {
            $_SESSION['success'] = 'Profile updated successfully!';
            $this->refreshUserSession();
        } else {
            $_SESSION['error'] = 'Failed to update profile. Please try again.';
        }
    }

    /**
     * Update company profile (employer)
     */
    private function updateCompanyProfile($user)
    {
        // Basic user data
        $name = $this->io->post('name');
        $email = $this->io->post('email');
        $phone = $this->io->post('phone');

        // Company data
        $company_name = $this->io->post('company_name');
        $company_website = $this->io->post('company_website');
        $company_size = $this->io->post('company_size');
        $company_industry = $this->io->post('company_industry');
        $company_description = $this->io->post('company_description');
        $company_address = $this->io->post('company_address');

        $google_form_url = $this->io->post('google_form_url');

        // Validate required fields
        if (empty($name) || empty($email) || empty($company_name)) {
            $_SESSION['error'] = 'Name, email, and company name are required fields.';
            return;
        }

        // Check if email is unique
        if (!$this->profileModel->isEmailUnique($email, $user['id'])) {
            $_SESSION['error'] = 'This email is already taken. Please use a different email.';
            return;
        }

        // Update basic user info
        $userData = [
            'name' => $name,
            'email' => $email
        ];

        // Only update password if provided
        $password = $this->io->post('password');
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $_SESSION['error'] = 'Password must be at least 6 characters long.';
                return;
            }
            $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $userUpdated = $this->profileModel->updateUser($user['id'], $userData);

        // Update company profile data
        $companyData = [
            'company_name' => $company_name,
            'company_description' => $company_description,
            'company_website' => $company_website,
            'company_size' => $company_size,
            'company_industry' => $company_industry,
            'company_address' => $company_address,
            'phone' => $phone,
            'updated_at' => date('Y-m-d H:i:s')
        ];

          if (!empty($google_form_url)) {
            $companyData['google_form_url'] = $google_form_url;
        }

        // Handle company logo upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->uploadCompanyLogo($user['id']);
            if ($uploadResult['success']) {
                $companyData['company_logo'] = $uploadResult['file_path'];
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                return;
            }
        }

        // Update company profile
        $companyUpdated = $this->profileModel->saveCompanyProfile($user['id'], $companyData);
        
        if ($userUpdated || $companyUpdated) {
            $_SESSION['success'] = 'Company profile updated successfully!';
            $this->refreshUserSession();
        } else {
            $_SESSION['error'] = 'Failed to update company profile. Please try again.';
        }
    }

    /**
     * Upload resume file
     */
    private function uploadResume($userId)
    {
        $uploadDir = 'uploads/resumes/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'resume_' . $userId . '_' . time() . '_' . $_FILES['resume']['name'];
        $filePath = $uploadDir . $fileName;

        $allowedTypes = ['pdf', 'doc', 'docx', 'txt'];
        $fileExtension = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only PDF, DOC, DOCX, TXT are allowed.'];
        }

        if ($_FILES['resume']['size'] > 5 * 1024 * 1024) { // 5MB limit
            return ['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.'];
        }

        if (move_uploaded_file($_FILES['resume']['tmp_name'], $filePath)) {
            return ['success' => true, 'file_path' => $filePath];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }

    /**
     * Upload company logo
     */
    private function uploadCompanyLogo($userId)
    {
        $uploadDir = 'uploads/logos/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'logo_' . $userId . '_' . time() . '_' . $_FILES['company_logo']['name'];
        $filePath = $uploadDir . $fileName;

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.'];
        }

        if ($_FILES['company_logo']['size'] > 2 * 1024 * 1024) { // 2MB limit
            return ['success' => false, 'message' => 'File size too large. Maximum 2MB allowed.'];
        }

        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $filePath)) {
            return ['success' => true, 'file_path' => $filePath];
        } else {
            return ['success' => false, 'message' => 'Failed to upload file.'];
        }
    }

    /**
     * Load profile data based on user role
     */
    private function loadProfileData($user)
    {
        $data = [
            'profile' => null,
            'company_profile' => null,
            'is_employer' => ($user['role'] == 'employer'),
            'profile_completion' => 0,
            'stats' => []
        ];

        // Load profile data based on role
        if ($this->profileModel) {
            if ($user['role'] == 'job_seeker') {
                $profile = $this->profileModel->getUserProfileByUserId($user['id']);
                $data['profile'] = $profile;
            } else {
                $companyProfile = $this->profileModel->getCompanyProfileByUserId($user['id']);
                $data['company_profile'] = $companyProfile;
            }
            
            // Calculate profile completion
            $data['profile_completion'] = $this->profileModel->getProfileCompletion($user['id'], $user['role']);
        }

        // Load stats based on role
        if ($user['role'] == 'job_seeker') {
            $data['stats'] = [
                'total_applications' => $this->applicationModel ? $this->applicationModel->countUserApplications($user['id']) : 0,
                'interview_scheduled' => $this->applicationModel ? $this->applicationModel->countInterviews($user['id']) : 0,
                'saved_jobs' => $this->jobModel ? $this->jobModel->countSavedJobs($user['id']) : 0
            ];
        } else {
            $data['stats'] = [
                'total_jobs' => $this->jobModel ? $this->jobModel->countEmployerJobs($user['id']) : 0,
                'active_jobs' => $this->jobModel ? $this->jobModel->countActiveEmployerJobs($user['id']) : 0,
                'total_applications' => $this->applicationModel ? $this->applicationModel->countEmployerApplications($user['id']) : 0
            ];
        }

        return $data;
    }

    /**
     * Load sidebar data
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
     * Get page title based on role
     */
    private function getPageTitle($role)
    {
        return $role == 'job_seeker' ? 'My Profile' : 'Company Profile';
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

        $user_id = $_SESSION['user']['id'];
        
        // Get fresh user data from database
        if ($this->profileModel && method_exists($this->profileModel, 'getUserById')) {
            try {
                $user = $this->profileModel->getUserById($user_id);
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
     * Refresh user session data after profile update
     */
    private function refreshUserSession()
    {
        if (isset($_SESSION['user']['id'])) {
            $user_id = $_SESSION['user']['id'];
            if ($this->profileModel && method_exists($this->profileModel, 'getUserById')) {
                $user = $this->profileModel->getUserById($user_id);
                if ($user) {
                    $_SESSION['user'] = array_merge($_SESSION['user'], $user);
                }
            }
        }
    }


    // Add this method to your ProfileController.php

/**
 * Get applicant details for employer view
 */
public function getApplicantDetails($applicationId)
{
    if (!$this->isLoggedIn()) {
        return ['error' => 'Not logged in'];
    }

    $user = $this->getCurrentUser();
    
    if ($user['role'] !== 'employer') {
        return ['error' => 'Unauthorized access'];
    }

    try {
        // Get application details
        $application = $this->applicationModel->getApplicationById($applicationId);
        
        if (!$application) {
            return ['error' => 'Application not found'];
        }

        // Verify the job belongs to this employer
        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $user['id']) {
            return ['error' => 'Unauthorized access to this application'];
        }

        // Get applicant user details
        $applicantUser = $this->profileModel->getUserById($application['user_id']);
        
        // Get applicant profile
        $applicantProfile = $this->profileModel->getUserProfileByUserId($application['user_id']);

        return [
            'success' => true,
            'application' => $application,
            'applicant' => array_merge($applicantUser, ['profile' => $applicantProfile]),
            'job' => $job
        ];

    } catch (Exception $e) {
        error_log("Error getting applicant details: " . $e->getMessage());
        return ['error' => 'Failed to load applicant details'];
    }
}
// Add this method to your ProfileController.php

/**
 * Get applicant details for AJAX request
 */
public function getApplicantDetailsAjax($applicationId)
{
    // Set JSON header
    header('Content-Type: application/json');
    
    if (!$this->isLoggedIn()) {
        echo json_encode(['error' => 'Not logged in']);
        exit;
    }

    $user = $this->getCurrentUser();
    
    if ($user['role'] !== 'employer') {
        echo json_encode(['error' => 'Unauthorized access']);
        exit;
    }

    try {
        // Get application details
        $application = $this->applicationModel->getApplicationById($applicationId);
        
        if (!$application) {
            echo json_encode(['error' => 'Application not found']);
            exit;
        }

        // Verify the job belongs to this employer
        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $user['id']) {
            echo json_encode(['error' => 'Unauthorized access to this application']);
            exit;
        }

        // Get applicant user details
        $applicantUser = $this->profileModel->getUserById($application['user_id']);
        
        // Get applicant profile
        $applicantProfile = $this->profileModel->getUserProfileByUserId($application['user_id']);

        echo json_encode([
            'success' => true,
            'application' => $application,
            'applicant' => array_merge($applicantUser, ['profile' => $applicantProfile]),
            'job' => $job
        ]);

    } catch (Exception $e) {
        error_log("Error getting applicant details: " . $e->getMessage());
        echo json_encode(['error' => 'Failed to load applicant details: ' . $e->getMessage()]);
    }
    exit;
}

private function showApplicantModal($user, $applicationId)
{
    // Only employers can view applicant details
    if ($user['role'] !== 'employer') {
        $_SESSION['error'] = 'Unauthorized access';
        redirect('/dashboard/overview');
        return;
    }

    try {
        // Get application details
        $application = $this->applicationModel->getApplicationById($applicationId);
        
        if (!$application) {
            $_SESSION['error'] = 'Application not found';
            redirect('/dashboard/overview');
            return;
        }

        // Verify the job belongs to this employer
        $job = $this->jobModel->getJobById($application['job_id']);
        if (!$job || $job['user_id'] != $user['id']) {
            $_SESSION['error'] = 'Unauthorized access to this application';
            redirect('/dashboard/overview');
            return;
        }

        // Get applicant details
        $applicantUser = $this->profileModel->getUserById($application['user_id']);
        $applicantProfile = $this->profileModel->getUserProfileByUserId($application['user_id']);

        // Prepare data for the overview page with modal
        $data = [
            'user' => $user,
            'current_page' => 'overview',
            'title' => 'Dashboard',
            'show_applicant_modal' => true,
            'applicant_data' => [
                'application' => $application,
                'applicant' => array_merge($applicantUser, ['profile' => $applicantProfile]),
                'job' => $job
            ]
        ];

        // Load the overview page
        $this->call->view('overview', $data);

    } catch (Exception $e) {
        error_log("Error showing applicant modal: " . $e->getMessage());
        $_SESSION['error'] = 'Failed to load applicant details';
        redirect('/dashboard/overview');
    }
}
}