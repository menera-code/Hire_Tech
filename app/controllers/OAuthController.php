<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class OAuthController extends Controller
{
    public function google_proxy()
    {
        // This is a clean URL that doesn't have encoded slashes
        $code = $this->io->get('code');
        $scope = $this->io->get('scope');
        $authuser = $this->io->get('authuser');
        $prompt = $this->io->get('prompt');
        
        if ($code) {
            // Redirect to the actual callback with proper parameters
            $params = http_build_query([
                'code' => $code,
                'scope' => $scope,
                'authuser' => $authuser,
                'prompt' => $prompt
            ]);
            
            header('Location: /HireTech/auth/google_callback?' . $params);
            exit;
        } else {
            $_SESSION['error'] = "Google authentication failed.";
            redirect('/login');
        }
    }
}