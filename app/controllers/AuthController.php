<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
    error_log("=== INITIALIZING GOOGLE CLIENT ===");
    
    $vendorPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
    error_log("Looking for autoload at: " . $vendorPath);
    
    if (!file_exists($vendorPath)) {
        $vendorPath = $_SERVER['DOCUMENT_ROOT'] . '/HireTech/vendor/autoload.php';
        error_log("Trying alternative path: " . $vendorPath);
    }
    
    if (!file_exists($vendorPath)) {
        error_log("COMPOSER AUTOLOAD NOT FOUND!");
        return;
    }
    
    error_log("Composer autoload found, requiring...");
    require_once $vendorPath;
    
    try {
        $config = $this->getGoogleConfig();
        error_log("Google config: " . print_r($config, true));
        
        $this->googleClient = new Google_Client();
        $this->googleClient->setClientId($config['client_id']);
        $this->googleClient->setClientSecret($config['client_secret']);
        $this->googleClient->setRedirectUri($config['redirect_uri']);
        $this->googleClient->addScope("email");
        $this->googleClient->addScope("profile");
        
        error_log("Google Client initialized successfully");
        
    } catch (Exception $e) {
        error_log("Google Client initialization failed: " . $e->getMessage());
    }
}


 private function getGoogleConfig() {
    // Environment variables are now loaded automatically via autoload.php
    return [
        'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
        'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
        'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'http://localhost:3000/auth/google_callback'
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
            header('Location: http://localhost:3000/login');
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
            header('Location: http://localhost:3000/login');
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

        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        error_log("Checking if user is registered...");
        
        // CHECK 1: Look for user by Google ID (already linked Google account)
        $user = $this->userModel->findByGoogleId($googleId);
        
        if ($user) {
            // User exists and has Google account linked - ALLOW LOGIN
            error_log("User found with Google ID: " . $user['email']);
            
        } else {
            // CHECK 2: Look for user by email (registered but not linked to Google)
            $user = $this->userModel->findByEmail($email);
            
            if ($user) {
                // User exists with email but Google account not linked
                error_log("User exists but Google account not linked: " . $email);
                $_SESSION['error'] = "This email is already registered with a password. Please use email/password login.";
                header('Location: http://localhost:3000/login');
                exit;
            } else {
                // USER NOT REGISTERED - Store Google data and redirect to registration
                error_log("User not registered: " . $email);
                
                // Store Google user data in session for registration form
                $_SESSION['google_registration_data'] = [
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getPicture()
                ];
                
                $_SESSION['info'] = "Please complete your registration. Your Google information has been pre-filled.";
                header('Location: http://localhost:3000/register');
                exit;
            }
        }

        // Only reach here if user is properly registered and Google-linked
        error_log("User successfully authenticated: " . $user['email']);
        
        // Store session data
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'job_seeker',
            'avatar' => $user['avatar'] ?? null
        ];

        $_SESSION['success'] = "Welcome back, {$user['name']}!";
        error_log("Session created successfully");
        error_log("Redirecting to dashboard...");
        
        // Redirect based on role
        $this->redirectBasedOnRole($user['role'] ?? 'job_seeker');
        
    } catch (Exception $e) {
        error_log("GOOGLE CALLBACK ERROR: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        $_SESSION['error'] = "Google authentication failed: " . $e->getMessage();
        header('Location: http://localhost:3000/login');
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
    
    // Google registration fields (optional)
    $googleId = $this->io->post('google_id');
    $avatar   = $this->io->post('avatar');

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

    // Check if email exists (but allow if it's the same Google email from session)
    $existingUser = $this->userModel->findByEmail($email);
    if ($existingUser) {
        // Check if this is a Google registration attempt for the same email
        $googleData = $_SESSION['google_registration_data'] ?? [];
        if (!empty($googleData['email']) && $googleData['email'] === $email) {
            // This is the same email from Google - allow registration
            error_log("Allowing Google registration for: " . $email);
        } else {
            $_SESSION['error'] = "Email already registered.";
            redirect('/register');
            return;
        }
    }

    // Create user data
    $data = [
        'name'     => $name,
        'email'    => $email,
        'password' => $password,
        'role'     => $role
    ];

    // Add Google data if provided
    if (!empty($googleId)) {
        $data['google_id'] = $googleId;
    }
    if (!empty($avatar)) {
        $data['avatar'] = $avatar;
    }

    $inserted = $this->userModel->create($data);

    if ($inserted) {
        // Clear Google registration data from session
        unset($_SESSION['google_registration_data']);
        
        // Auto-login after registration
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'avatar' => $user['avatar'] ?? null
            ];
            
            $_SESSION['success'] = "Account created successfully! Welcome to HireTech, {$user['name']}!";
            $this->redirectBasedOnRole($user['role']);
        } else {
            $_SESSION['success'] = "Account created successfully! Please log in.";
            redirect('/login');
        }
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
    
    $baseUrl = 'http://localhost:3000/';
    
    switch ($role) {
        case 'admin':
            header('Location: ' . $baseUrl . '/admin');
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