<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Controller: HomeController
 * 
 * Automatically generated via CLI.
 */
class HomeController extends Controller {
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->call->view('landing_page');
    }
  
 
}