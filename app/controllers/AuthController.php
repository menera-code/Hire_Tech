<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class AuthController extends Controller
{
    protected $userModel;
    protected $googleClient;

    public function __construct()
    {
        parent::__construct();
        $this->call->model('User_model');
        $this->userModel = new User_model();
        session_start();
        
        $this->initializeGoogleClient();
    }

    private function initializeGoogleClient() {
        // Use the correct path for your framework
        $vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
        
        if (!file_exists($vendorPath)) {
            // Alternative path for XAMPP
            $vendorPath = $_SERVER['DOCUMENT_ROOT'] . '/HireTech/vendor/autoload.php';
        }
        
        if (!file_exists($vendorPath)) {
            error_log("Composer autoload not found at: " . $vendorPath);
            return;
        }
        
        require_once $vendorPath;
        
        $config = $this->getGoogleConfig();
        
        $this->googleClient = new Google_Client();
        $this->googleClient->setClientId($config['client_id']);
        $this->googleClient->setClientSecret($config['client_secret']);
        $this->googleClient->setRedirectUri($config['redirect_uri']);
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
    }

    private function getGoogleConfig() {
        return [
            'client_id' => '255304170297-ami5oh90p34p7atbh95g35gc3omabtmj.apps.googleusercontent.com',
            'client_secret' => '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',
            'redirect_uri' => 'http://localhost:3000/HireTech/auth/google_callback'
        ];
    }

    /**
     * Show login page
     */
    public function login_page()
    {
        $this->call->view('login');
    }

    /**
     * Show register page
     */
    public function register_page()
    {
        $this->call->view('register');
    }

    /**
     * Initiate Google OAuth
     */
    public function google_login()
    {
        if (!$this->googleClient) {
            $_SESSION['error'] = "Google authentication is not configured properly.";
            redirect('/login');
            return;
        }

        $authUrl = $this->googleClient->createAuthUrl();
        header('Location: ' . $authUrl);
        exit;
    }

    /**
     * Handle Google OAuth callback
     */
    public function google_callback()
{
    try {
        error_log("=== GOOGLE CALLBACK REACHED ===");
        error_log("GET parameters: " . print_r($_GET, true));
        
        // Check if we have the authorization code
        if (!isset($_GET['code'])) {
            error_log("No authorization code found");
            $_SESSION['error'] = "No authorization code received from Google.";
            header('Location: http://localhost:3000/HireTech/login');
            exit;
        }

        error_log("Authorization code received");

        // Check if Google Client is initialized
        if (!$this->googleClient) {
            error_log("Google Client not initialized - initializing now");
            $this->initializeGoogleClient();
        }

        if (!$this->googleClient) {
            error_log("Google Client still not initialized after attempt");
            $_SESSION['error'] = "Google authentication not configured.";
            header('Location: http://localhost:3000/HireTech/login');
            exit;
        }

        error_log("Exchanging code for token...");
        
        // Exchange code for access token
        $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
        error_log("Token response: " . print_r($token, true));
        
        if (isset($token['error'])) {
            throw new Exception($token['error_description'] ?? 'Token exchange failed: ' . $token['error']);
        }

        $this->googleClient->setAccessToken($token);
        error_log("Access token set successfully");

        // Get user info from Google
        $googleService = new Google_Service_Oauth2($this->googleClient);
        $googleUser = $googleService->userinfo->get();

        error_log("Google user info retrieved:");
        error_log(" - ID: " . $googleUser->getId());
        error_log(" - Name: " . $googleUser->getName());
        error_log(" - Email: " . $googleUser->getEmail());
        error_log(" - Picture: " . $googleUser->getPicture());

        $userData = [
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getPicture()
        ];

        error_log("Creating/updating user in database...");
        
        // Create or update user in database
        $user = $this->userModel->createOrUpdateGoogleUser($userData);

        if ($user) {
            error_log("User successfully created/updated. User ID: " . $user['id']);
            
            // Store session data
            $_SESSION['user'] = [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'job_seeker',
                'avatar' => $user['avatar'] ?? null
            ];

            $_SESSION['success'] = "Welcome to HireTech, {$user['name']}!";
            error_log("Session created successfully");
            error_log("Redirecting to dashboard...");
            
            // Redirect based on role
            $this->redirectBasedOnRole($user['role'] ?? 'job_seeker');
            
        } else {
            error_log("User creation/update failed");
            $_SESSION['error'] = "Failed to create user account.";
            header('Location: http://localhost:3000/HireTech/login');
            exit;
        }

    } catch (Exception $e) {
        error_log("GOOGLE CALLBACK ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Google authentication failed: " . $e->getMessage();
        header('Location: http://localhost:3000/HireTech/login');
        exit;
    }
}

    /**
     * Handle user registration
     */
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/register');
            return;
        }

        $name     = trim($this->io->post('name'));
        $email    = trim($this->io->post('email'));
        $password = $this->io->post('password');
        $confirm  = $this->io->post('confirm_password');
        $role     = $this->io->post('role');

        // Validate inputs
        if (empty($name) || empty($email) || empty($password) || empty($confirm) || empty($role)) {
            $_SESSION['error'] = "All fields are required.";
            redirect('/register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            redirect('/register');
            return;
        }

        if ($password !== $confirm) {
            $_SESSION['error'] = "Passwords do not match.";
            redirect('/register');
            return;
        }

        if ($this->userModel->emailExists($email)) {
            $_SESSION['error'] = "Email already registered.";
            redirect('/register');
            return;
        }

        // Create user
        $data = [
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role
        ];

        $inserted = $this->userModel->create($data);

        if ($inserted) {
            $_SESSION['success'] = "Account created successfully! You can now log in.";
            redirect('/login');
        } else {
            $_SESSION['error'] = "Registration failed. Please try again.";
            redirect('/register');
        }
    }

    /**
     * Handle login
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/login');
            return;
        }

        $email    = trim($this->io->post('email'));
        $password = $this->io->post('password');

        // Validate inputs
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = "Both email and password are required.";
            redirect('/login');
            return;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            $_SESSION['error'] = "Email not found.";
            redirect('/login');
            return;
        }

        // Check if user registered with Google (no password)
        if (empty($user['password'])) {
            $_SESSION['error'] = "This email is registered with Google. Please use Google Sign-In.";
            redirect('/login');
            return;
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['error'] = "Incorrect password.";
            redirect('/login');
            return;
        }

        // Store session data
        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'avatar' => $user['avatar'] ?? null
        ];

        $_SESSION['success'] = "Welcome back, {$user['name']}!";
        $this->redirectBasedOnRole($user['role']);
    }

    private function redirectBasedOnRole($role) {
    error_log("Redirecting based on role: " . $role);
    
    $baseUrl = 'http://localhost:3000/HireTech';
    
    switch ($role) {
        case 'admin':
            header('Location: ' . $baseUrl . '/dashboard');
            break;
        case 'employer':
            header('Location: ' . $baseUrl . '/dashboard');
            break;
        case 'job_seeker':
            header('Location: ' . $baseUrl . '/dashboard');
            break;
        default:
            header('Location: ' . $baseUrl . '/dashboard');
            break;
    }
    exit;
}

    /**
     * Logout
     */
    public function logout()
    {
        // Revoke Google token if exists
        if (isset($this->googleClient) && isset($_SESSION['user']['google_id'])) {
            try {
                $this->googleClient->revokeToken();
            } catch (Exception $e) {
                // Ignore revocation errors
            }
        }
        
        session_destroy();
        redirect('/');
    }
}