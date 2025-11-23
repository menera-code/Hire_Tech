<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
/**
 * ------------------------------------------------------------------
 * LavaLust - an opensource lightweight PHP MVC Framework
 * ------------------------------------------------------------------
 *
 * MIT License
 *
 * Copyright (c) 2020 Ronald M. Marasigan
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package LavaLust
 * @author Ronald M. Marasigan <ronald.marasigan@yahoo.com>
 * @since Version 1
 * @link https://github.com/ronmarasigan/LavaLust
 * @license https://opensource.org/licenses/MIT MIT License
 */

/*
| -------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------
| Here is where you can register web routes for your application.
|
|
*/


// routes.php


// routes.php


// routes.php

$router->get('/', 'HomeController@index');
$router->get('/admin', 'AdminController@index');
$router->get('/admin/users', 'AdminController@users');
$router->get('/admin/jobs', 'AdminController@jobs');
$router->get('/admin/applications', 'AdminController@applications');
$router->get('/admin/logout', 'AdminController@logout');

// Authentication Routes
$router->get('/login', 'AuthController@login_page');
$router->get('/register', 'AuthController@register_page');
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/login', 'AuthController@login');
$router->get('/auth/google', 'AuthController@google_login');
$router->get('/auth/google_callback', 'AuthController@google_callback');
$router->get('/logout', 'AuthController@logout');

// ========== DASHBOARD ROUTES ==========

// Dashboard Main Routes
$router->get('/dashboard', 'DashboardController@index');
$router->get('/dashboard/overview', 'DashboardController@overview');
$router->get('/dashboard/jobs', 'DashboardController@jobs');
$router->get('/dashboard/applications', 'DashboardController@load');
$router->get('/dashboard/profile', 'DashboardController@load');
$router->get('/dashboard/saved', 'DashboardController@load');
$router->get('/dashboard/company', 'DashboardController@load');

// Dynamic page loader - MUST BE AFTER SPECIFIC ROUTES
$router->get('/dashboard/load/(:any)', 'DashboardController@load');

// ========== JOB ACTIONS ROUTES ==========

// Job Application & Saving (GET routes for modal actions)
$router->get('/dashboard/apply_job', 'DashboardController@apply_job');
$router->get('/dashboard/save_job', 'DashboardController@save_job');

// Job CRUD Operations (POST routes for form submissions)
$router->post('/dashboard/jobs', 'DashboardController@jobs'); // Handles all job form submissions
$router->post('/dashboard/post_job', 'DashboardController@post_job'); // Legacy route
$router->post('/dashboard/edit_job/(:num)', 'DashboardController@edit_job'); // Legacy route
$router->post('/dashboard/delete_job/(:num)', 'DashboardController@delete_job'); // Legacy route

// AJAX/API Routes
$router->get('/dashboard/get_job/(:num)', 'DashboardController@get_job');
$router->get('/dashboard/get_job_details/(:num)', 'DashboardController@get_job_details');

// Modal Confirmation Routes
$router->get('/dashboard/confirm_apply_job/(:num)', 'DashboardController@confirm_apply_job');
$router->get('/dashboard/confirm_save_job/(:num)', 'DashboardController@confirm_save_job');

// ========== PUBLIC JOBS ROUTES ==========
$router->get('/jobs', 'DashboardController@jobs');

// ========== TEST ROUTES ==========
$router->get('/test', 'TestController@index');

$router->get('/application', 'DashboardController@application');
$router->post('/dashboard/schedule_interview', 'DashboardController@schedule_interview');
$router->post('/dashboard/reject_application', 'DashboardController@reject_application');
$router->post('/dashboard/hire_applicant', 'DashboardController@hire_applicant');

// Dashboard routes
// Add this to your routes.php file
$route['dashboard/application'] = 'DashboardController/application';
$route['dashboard/application/(:any)'] = 'DashboardController/application/$1';
// In your routes file
$router->get('/dashboard/get_interview_details/(:num)', 'DashboardController@get_interview_details/$1');
// In your routes file
$router->get('dashboard/get_interview_details', 'DashboardController@get_interview_details');
$router->get('dashboard/get_interview_details_for_reschedule', 'DashboardController@get_interview_details_for_reschedule');
$router->get('/saved', 'DashboardController@saved');

// In your routes configuration (usually in app/config/routes.php)
$router->get('/dashboard/saved', 'DashboardController@saved');
$router->get('/dashboard/apply_job', 'DashboardController@apply_job');
// Unsave job specifically for saved jobs page
$router->get('/dashboard/unsave_job', 'DashboardController@unsave_job');
// Bulk unsave jobs for saved jobs page
$router->get('/dashboard/bulk_unsave_job', 'DashboardController@bulk_unsave_job');
// ========== PROFILE ROUTES ==========
$router->get('/profile', 'ProfileController@index');
$router->post('/profile', 'ProfileController@index');
// In your routes file
// Add this route for the applicant details AJAX call
$router->get('profile/getApplicantDetails/(:num)', 'ProfileController@getApplicantDetailsAjax');
$router->get('/dashboard/applicant', 'DashboardController@applicant');

// Company Routes
// Company Routes
$router->get('/companies', 'CompanyController@index');
$router->get('/companies/view/{id}', 'CompanyController@view');
// Cancel application route
$router->get('/dashboard/cancel_application', 'DashboardController@cancel_application');
// Add this route
// Add this route for the application page
$router->get('/dashboard/application', 'DashboardController@application');
