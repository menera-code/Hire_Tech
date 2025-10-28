<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class DashboardController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        session_start();

        // Protect this page — redirect if not logged in
        if (!isset($_SESSION['user'])) {
            $_SESSION['error'] = "Please log in to access your dashboard.";
            redirect('/');
        }
    }

    /**
     * Dashboard Page
     */
    public function index()
    {
        // You can pass user info to the view if needed
        $data['user'] = $_SESSION['user'];

        $this->call->view('dashboard', $data);
    }
}
