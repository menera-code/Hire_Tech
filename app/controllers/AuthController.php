<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
error_reporting(E_ALL);
ini_set('display_errors', 1);

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');
class AuthController extends Controller
{
    protected $userModel;
    protected $googleClient;
    protected $fb;

    public function __construct()
    {
        parent::__construct();
        $this->call->model('User_model');
        $this->userModel = new User_model();
        session_start();
        
        $this->initializeGoogleClient();
        $this->initializeFacebook();
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

private function initializeFacebook() {
    error_log("=== INITIALIZING FACEBOOK SDK ===");
    
    // Path to the downloaded SDK
    $sdkPath = 'C:/xampp/htdocs/HireTech/libraries/facebook-sdk-master/src/Facebook/';
    
    if (!file_exists($sdkPath)) {
        error_log("❌ Facebook SDK not found. Please download from: https://github.com/facebook/php-graph-sdk");
        return;
    }
    
    error_log("✅ Facebook SDK found at: " . $sdkPath);
    
    try {
        // Load the autoloader
        require_once $sdkPath . 'autoload.php';
        error_log("✅ Facebook autoload loaded");
        
        $config = $this->getFacebookConfig();
        
        $this->fb = new \Facebook\Facebook([
            'app_id' => $config['app_id'],
            'app_secret' => $config['app_secret'],
            'default_graph_version' => 'v18.0',
        ]);
        
        error_log("✅ Facebook Client initialized successfully");
        
    } catch (Exception $e) {
        error_log("❌ Facebook init failed: " . $e->getMessage());
    }
}
/**
 * Fallback method for manual file loading
 */
private function initializeFacebookManual() {
    error_log("=== TRYING MANUAL FILE LOADING ===");
    
    $sdkPath = __DIR__ . '/../../libraries/php-graph-sdk-5.x/src/Facebook/';
    
    // Manually require the essential files for v5.x
    $requiredFiles = [
        'Facebook.php',
        'Exceptions/FacebookResponseException.php',
        'Exceptions/FacebookSDKException.php', 
        'Helpers/FacebookRedirectLoginHelper.php',
        'Authentication/AccessToken.php',
        'FacebookResponse.php',
        'FacebookRequest.php',
        'HttpClients/FacebookCurlHttpClient.php',
        'HttpClients/FacebookHttpable.php',
    ];
    
    foreach ($requiredFiles as $file) {
        $filePath = $sdkPath . $file;
        if (file_exists($filePath)) {
            require_once $filePath;
            error_log("✅ Loaded: " . $file);
        } else {
            error_log("❌ Missing: " . $file);
        }
    }
    
    try {
        $config = $this->getFacebookConfig();
        
        $this->fb = new \Facebook\Facebook([
            'app_id' => $config['app_id'],
            'app_secret' => $config['app_secret'],
            'default_graph_version' => 'v18.0',
        ]);
        
        error_log("✅ Facebook Client initialized via manual loading");
        
    } catch (Exception $e) {
        error_log("❌ Manual loading also failed: " . $e->getMessage());
    }
}

    private function getGoogleConfig() {
        return [
            'client_id' => $_ENV['GOOGLE_CLIENT_ID'] ?? '',
            'client_secret' => $_ENV['GOOGLE_CLIENT_SECRET'] ?? '',
            'redirect_uri' => $_ENV['GOOGLE_REDIRECT_URI'] ?? 'https://hire-tech.onrender.com/auth/google_callback'
        ];
    }
private function getFacebookConfig() {
    $currentHost = $_SERVER['HTTP_HOST'] ?? '';
    
    if (strpos($currentHost, 'serveo.net') !== false) {
        // Serveo development
        return [
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
            'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? '',
            'redirect_uri' => 'https://' . $currentHost . '/auth/facebook_callback'
        ];
    } elseif (strpos($currentHost, 'ngrok.io') !== false) {
        // ngrok development
        return [
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
            'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? '',
            'redirect_uri' => 'https://' . $currentHost . '/auth/facebook_callback'
        ];
    } else {
        // Production
        return [
            'app_id' => $_ENV['FACEBOOK_APP_ID'] ?? '',
            'app_secret' => $_ENV['FACEBOOK_APP_SECRET'] ?? '',
            'redirect_uri' => 'https://hiretech.infinityfree.me/auth/facebook_callback'
        ];
    }
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
     * Initiate Facebook OAuth
     */
    public function facebook_login()
    {
        if (!$this->fb) {
            $_SESSION['error'] = "Facebook authentication is not configured properly.";
            redirect('/login');
            return;
        }

        $helper = $this->fb->getRedirectLoginHelper();
        $permissions = ['email', 'public_profile'];
        $loginUrl = $helper->getLoginUrl($this->getFacebookConfig()['redirect_uri'], $permissions);
        
        header('Location: ' . $loginUrl);
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
            
            if (!isset($_GET['code'])) {
                error_log("No authorization code found");
                $_SESSION['error'] = "No authorization code received from Google.";
                header('Location: https://hire-tech.onrender.com/login');
                exit;
            }

            error_log("Authorization code received");

            if (!$this->googleClient) {
                error_log("Google Client not initialized - initializing now");
                $this->initializeGoogleClient();
            }

            if (!$this->googleClient) {
                error_log("Google Client still not initialized after attempt");
                $_SESSION['error'] = "Google authentication not configured.";
                header('Location: https://hire-tech.onrender.com/login');
                exit;
            }

            error_log("Exchanging code for token...");
            
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
            error_log("Token response: " . print_r($token, true));
            
            if (isset($token['error'])) {
                throw new Exception($token['error_description'] ?? 'Token exchange failed: ' . $token['error']);
            }

            $this->googleClient->setAccessToken($token);
            error_log("Access token set successfully");

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
            
            $user = $this->userModel->findByGoogleId($googleId);
            
            if ($user) {
                error_log("User found with Google ID: " . $user['email']);
                
            } else {
                $user = $this->userModel->findByEmail($email);
                
                if ($user) {
                    error_log("User exists but Google account not linked: " . $email);
                    $_SESSION['error'] = "This email is already registered with a password. Please use email/password login.";
                    header('Location: https://hire-tech.onrender.com/login');
                    exit;
                } else {
                    error_log("User not registered: " . $email);
                    
                    $_SESSION['google_registration_data'] = [
                        'name' => $googleUser->getName(),
                        'email' => $googleUser->getEmail(),
                        'google_id' => $googleUser->getId(),
                        'avatar' => $googleUser->getPicture()
                    ];
                    
                    $_SESSION['info'] = "Please complete your registration. Your Google information has been pre-filled.";
                    header('Location: https://hire-tech.onrender.com/register');
                    exit;
                }
            }

            error_log("User successfully authenticated: " . $user['email']);
            
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
            
            $this->redirectBasedOnRole($user['role'] ?? 'job_seeker');
            
        } catch (Exception $e) {
            error_log("GOOGLE CALLBACK ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = "Google authentication failed: " . $e->getMessage();
            header('Location: https://hire-tech.onrender.com/login');
            exit;
        }
    }

    /**
     * Handle Facebook OAuth callback
     */
    public function facebook_callback()
    {
        try {
            error_log("=== FACEBOOK CALLBACK REACHED ===");
            
            if (!$this->fb) {
                error_log("Facebook Client not initialized - initializing now");
                $this->initializeFacebook();
            }

            if (!$this->fb) {
                error_log("Facebook Client still not initialized after attempt");
                $_SESSION['error'] = "Facebook authentication not configured.";
                header('Location: https://hire-tech.onrender.com/login');
                exit;
            }

            $helper = $this->fb->getRedirectLoginHelper();
            
            try {
                $accessToken = $helper->getAccessToken();
            } catch(Facebook\Exceptions\FacebookResponseException $e) {
                throw new Exception('Graph returned an error: ' . $e->getMessage());
            } catch(Facebook\Exceptions\FacebookSDKException $e) {
                throw new Exception('Facebook SDK returned an error: ' . $e->getMessage());
            }

            if (!isset($accessToken)) {
                error_log("No access token received from Facebook");
                $_SESSION['error'] = "No access token received from Facebook.";
                header('Location: https://hire-tech.onrender.com/login');
                exit;
            }

            error_log("Access token received from Facebook");

            // Get user info from Facebook
            $response = $this->fb->get('/me?fields=id,name,email,picture.type(large)', $accessToken);
            $fbUser = $response->getGraphUser();

            error_log("Facebook user info retrieved:");
            error_log(" - ID: " . $fbUser->getId());
            error_log(" - Name: " . $fbUser->getName());
            error_log(" - Email: " . $fbUser->getEmail());
            
            $facebookId = $fbUser->getId();
            $email = $fbUser->getEmail();
            $name = $fbUser->getName();
            $avatar = $fbUser->getPicture() ? $fbUser->getPicture()->getUrl() : '';

            error_log("Checking if user is registered...");
            
            // CHECK 1: Look for user by Facebook ID
            $user = $this->userModel->findByFacebookId($facebookId);
            
            if ($user) {
                // User exists and has Facebook account linked - ALLOW LOGIN
                error_log("User found with Facebook ID: " . $user['email']);
                
            } else {
                // CHECK 2: Look for user by email
                $user = $this->userModel->findByEmail($email);
                
                if ($user) {
                    // User exists with email but Facebook account not linked
                    error_log("User exists but Facebook account not linked: " . $email);
                    $_SESSION['error'] = "This email is already registered with a password. Please use email/password login.";
                    header('Location: https://hire-tech.onrender.com/login');
                    exit;
                } else {
                    // USER NOT REGISTERED - Store Facebook data and redirect to registration
                    error_log("User not registered: " . $email);
                    
                    $_SESSION['facebook_registration_data'] = [
                        'name' => $name,
                        'email' => $email,
                        'facebook_id' => $facebookId,
                        'avatar' => $avatar
                    ];
                    
                    $_SESSION['info'] = "Please complete your registration. Your Facebook information has been pre-filled.";
                    header('Location: https://hire-tech.onrender.com/register');
                    exit;
                }
            }

            // Only reach here if user is properly registered and Facebook-linked
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
            
            $this->redirectBasedOnRole($user['role'] ?? 'job_seeker');
            
        } catch (Exception $e) {
            error_log("FACEBOOK CALLBACK ERROR: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $_SESSION['error'] = "Facebook authentication failed: " . $e->getMessage();
            header('Location: https://hire-tech.onrender.com/login');
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
        
        // Social registration fields
        $googleId = $this->io->post('google_id');
        $facebookId = $this->io->post('facebook_id');
        $avatar   = $this->io->post('avatar');

        // Validate inputs
        if (empty($name) || empty($email) || empty($role)) {
            $_SESSION['error'] = "Name, email, and role are required.";
            redirect('/register');
            return;
        }

        // Only require password for non-social registration
        if (empty($googleId) && empty($facebookId)) {
            if (empty($password) || empty($confirm)) {
                $_SESSION['error'] = "Password is required for email registration.";
                redirect('/register');
                return;
            }
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            redirect('/register');
            return;
        }

        if (!empty($password) && $password !== $confirm) {
            $_SESSION['error'] = "Passwords do not match.";
            redirect('/register');
            return;
        }

        // Check if email exists (but allow if it's the same social email from session)
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            $googleData = $_SESSION['google_registration_data'] ?? [];
            $facebookData = $_SESSION['facebook_registration_data'] ?? [];
            
            if ((!empty($googleData['email']) && $googleData['email'] === $email) || 
                (!empty($facebookData['email']) && $facebookData['email'] === $email)) {
                error_log("Allowing social registration for: " . $email);
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

        // Add social data if provided
        if (!empty($googleId)) {
            $data['google_id'] = $googleId;
        }
        if (!empty($facebookId)) {
            $data['facebook_id'] = $facebookId;
        }
        if (!empty($avatar)) {
            $data['avatar'] = $avatar;
        }

        $inserted = $this->userModel->create($data);

        if ($inserted) {
            // Clear social registration data from session
            unset($_SESSION['google_registration_data'], $_SESSION['facebook_registration_data']);
            
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

        // Check if user registered with social account (no password)
        if (empty($user['password'])) {
            if (!empty($user['google_id'])) {
                $_SESSION['error'] = "This email is registered with Google. Please use Google Sign-In.";
            } elseif (!empty($user['facebook_id'])) {
                $_SESSION['error'] = "This email is registered with Facebook. Please use Facebook Sign-In.";
            } else {
                $_SESSION['error'] = "This email is registered with social account. Please use social sign-in.";
            }
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
        
        $baseUrl = 'https://hire-tech.onrender.com';
        
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

    /**
 * Facebook Data Deletion Instructions Page
 * This is what users see when they visit the URL directly
 */
public function facebook_data_deletion() 
{
    echo '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Data Deletion Instructions - HireTech</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { 
                font-family: Arial, sans-serif; 
                max-width: 600px; 
                margin: 50px auto; 
                padding: 20px;
                line-height: 1.6;
            }
            h1 { color: #3B5998; border-bottom: 2px solid #3B5998; padding-bottom: 10px; }
            .steps { 
                background: #f9f9f9; 
                padding: 20px; 
                border-radius: 5px;
                border-left: 4px solid #3B5998;
            }
            .important { 
                background: #fff3cd; 
                padding: 15px; 
                border-radius: 5px;
                border: 1px solid #ffeaa7;
                margin: 20px 0;
            }
            code {
                background: #f4f4f4;
                padding: 2px 5px;
                border-radius: 3px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <h1>Data Deletion Instructions</h1>
        
        <div class="important">
            <strong>🔒 Important:</strong> This page explains how to delete your data from HireTech.
        </div>
        
        <div class="steps">
            <h3>How to Delete Your HireTech Data:</h3>
            <ol>
                <li><strong>Login to your HireTech account</strong> at: <code>https://hiretech.infinityfree.me/login</code></li>
                <li><strong>Go to "Account Settings"</strong> in your dashboard</li>
                <li><strong>Click "Delete My Account"</strong> at the bottom of the page</li>
                <li><strong>Confirm the deletion</strong> when prompted</li>
                <li><strong>All your data will be permanently removed</strong> from our systems</li>
            </ol>
            
            <h3>Need Help?</h3>
            <p>If you can\'t access your account, contact us directly:</p>
            <p><strong>Email:</strong> <a href="mailto:menerazen@gmail.com">menerazen@gmail.com</a></p>
            <p>Include your account email address and the phrase "Data Deletion Request" in the subject.</p>
            
            <h3>Confirmation Code:</h3>
            <p><code>HT_' . uniqid() . '_' . time() . '</code></p>
            <small>Reference this code when contacting us about data deletion.</small>
        </div>
        
        <p><small>This page complies with Facebook\'s data deletion requirements for app verification.</small></p>
    </body>
    </html>
    ';
}

/**
 * Facebook Data Deletion Callback (for automated requests)
 * This handles the actual deletion requests from Facebook
 */
public function facebook_data_deletion_callback() 
{
    error_log("=== FACEBOOK DATA DELETION CALLBACK ===");
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Facebook sends a signed_request in POST
        $signed_request = $_POST['signed_request'] ?? '';
        
        error_log("Signed request received: " . (!empty($signed_request) ? 'YES' : 'NO'));
        
        if (!empty($signed_request)) {
            try {
                // Parse the signed request
                list($encoded_sig, $payload) = explode('.', $signed_request, 2);
                $sig = $this->base64_url_decode($encoded_sig);
                $data = json_decode($this->base64_url_decode($payload), true);
                
                $user_id = $data['user_id'] ?? null;
                $algorithm = $data['algorithm'] ?? null;
                
                error_log("Facebook user ID for deletion: " . $user_id);
                error_log("Algorithm: " . $algorithm);
                
                if ($user_id) {
                    // Delete user data from your database
                    $deleted = $this->userModel->deleteUserDataByFacebookId($user_id);
                    
                    // Generate confirmation code
                    $confirmation_code = 'HT_' . uniqid() . '_' . time();
                    
                    // Return the required JSON response for Facebook
                    header('Content-Type: application/json');
                    $response = [
                        'url' => 'https://hiretech.infinityfree.me/data-deletion-instructions',
                        'confirmation_code' => $confirmation_code
                    ];
                    
                    echo json_encode($response);
                    error_log("Data deletion response: " . json_encode($response));
                    
                    error_log("Data deletion processed for Facebook user: " . $user_id);
                    return;
                }
            } catch (Exception $e) {
                error_log("Error processing data deletion: " . $e->getMessage());
            }
        }
    }
    
    // If not a POST request or invalid, show instructions
    $this->facebook_data_deletion();
}

/**
 * Helper function to decode base64 URL
 */
private function base64_url_decode($input) 
{
    return base64_decode(strtr($input, '-_', '+/'));
}

public function test_facebook_debug() {
    echo "<h3>Facebook SDK Path Debug</h3>";
    
    $possiblePaths = [
        'Path 1' => __DIR__ . '/../../libraries/php-graph-sdk-5.x/src/Facebook/',
        'Path 2' => __DIR__ . '/../../libraries/php-graph-sdk-5.x/',
        'Path 3' => $_SERVER['DOCUMENT_ROOT'] . '/HireTech/libraries/php-graph-sdk-5.x/src/Facebook/',
        'Path 4' => $_SERVER['DOCUMENT_ROOT'] . '/HireTech/libraries/php-graph-sdk-5.x/',
        'Path 5' => 'C:/xampp/htdocs/HireTech/libraries/php-graph-sdk-5.x/src/Facebook/',
        'Path 6' => 'C:/xampp/htdocs/HireTech/libraries/php-graph-sdk-5.x/',
    ];
    
    foreach ($possiblePaths as $name => $path) {
        echo "<strong>$name:</strong> " . $path . "<br>";
        echo "Exists: " . (file_exists($path) ? '✅ YES' : '❌ NO') . "<br><br>";
    }
    
    echo "<hr>";
    echo "<a href='/login'>Back to Login</a>";
}

public function find_facebook_sdk() {
    echo "<h3>Searching for Facebook SDK...</h3>";
    
    $searchPaths = [
        'C:/xampp/htdocs/HireTech/',
        'C:/xampp/htdocs/HireTech/app/',
        'C:/xampp/htdocs/HireTech/libraries/',
        'C:/xampp/htdocs/HireTech/vendor/',
        'C:/xampp/htdocs/HireTech/assets/',
        'C:/xampp/htdocs/HireTech/system/',
        __DIR__ . '/', // Current controllers directory
    ];
    
    foreach ($searchPaths as $basePath) {
        echo "<h4>Searching in: " . $basePath . "</h4>";
        
        // Look for common SDK folder names
        $sdkNames = [
            'php-graph-sdk-5.x',
            'php-graph-sdk',
            'facebook-php-sdk',
            'facebook-sdk',
            'Facebook',
        ];
        
        foreach ($sdkNames as $sdkName) {
            $fullPath = $basePath . $sdkName . '/';
            if (file_exists($fullPath)) {
                echo "✅ FOUND: " . $fullPath . "<br>";
                
                // Check if it has the Facebook folder structure
                if (file_exists($fullPath . 'src/Facebook/')) {
                    echo "📁 Has src/Facebook/ structure<br>";
                }
                if (file_exists($fullPath . 'Facebook.php')) {
                    echo "📁 Has Facebook.php in root<br>";
                }
                
                // List some files to confirm
                $files = scandir($fullPath);
                echo "Files: " . implode(', ', array_slice($files, 0, 10)) . "<br>";
            } else {
                echo "❌ Not found: " . $fullPath . "<br>";
            }
        }
        echo "<br>";
    }
}

public function check_facebook_folder() {
    $facebookPath = 'C:/xampp/htdocs/HireTech/vendor/Facebook/';
    
    echo "<h3>Contents of Facebook folder:</h3>";
    echo "Path: " . $facebookPath . "<br><br>";
    
    if (file_exists($facebookPath)) {
        $items = scandir($facebookPath);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..') {
                $fullPath = $facebookPath . $item;
                echo "<strong>" . $item . "</strong><br>";
                if (is_dir($fullPath)) {
                    $subItems = scandir($fullPath);
                    echo "Contents: " . implode(', ', array_slice($subItems, 0, 10)) . "<br>";
                }
                echo "<br>";
            }
        }
    } else {
        echo "Folder not found!";
    }
}
}