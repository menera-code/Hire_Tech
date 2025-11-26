<?php
$user = $data['user'];
$role = $user['role'];

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Get saved job IDs for display
$saved_job_ids = [];
if (isset($data['saved_jobs']) && is_array($data['saved_jobs'])) {
    foreach ($data['saved_jobs'] as $saved_job) {
        $saved_job_ids[] = $saved_job['id'];
    }
}

// Get applied job IDs for display
$applied_job_ids = $data['applied_job_ids'] ?? [];

// Get modal data from controller
$show_apply_modal = $data['show_apply_modal'] ?? false;
$show_save_modal = $data['show_save_modal'] ?? false;
$show_remove_modal = $data['show_remove_modal'] ?? false;
$modal_job_id = $data['modal_job_id'] ?? null;
$modal_job = $data['modal_job'] ?? null;
$remove_application_id = $data['remove_application_id'] ?? null;
$remove_application = $data['remove_application'] ?? null;
$already_applied = $data['already_applied'] ?? false;
$already_saved = $data['already_saved'] ?? false;

// Check if job details modal should be shown (from URL parameter)
$show_job_details_modal = isset($_GET['job_details']) && $_GET['job_details'] == '1';
$job_details_job_id = $_GET['job_id'] ?? null;

// Set modal_job if job details is requested
if ($show_job_details_modal && $job_details_job_id) {
    $modal_job_id = $job_details_job_id;
    
    // Try to find the job in recent applications first
    $modal_job = null;
    if (!empty($data['recent_applications'])) {
        foreach ($data['recent_applications'] as $application) {
            if (($application['job_id'] ?? $application['id']) == $job_details_job_id) {
                $modal_job = [
                    'id' => $application['job_id'] ?? $application['id'],
                    'title' => $application['job_title'] ?? 'No Title',
                    'company' => $application['job_company'] ?? 'Unknown Company',
                    'location' => $application['location'] ?? 'Location not specified',
                    'description' => $application['job_description'] ?? 'No description available.',
                    'job_type' => $application['job_type'] ?? '',
                    'salary' => $application['salary'] ?? '',
                    // These fields might not exist in your current database
                    'requirements' => $application['requirements'] ?? '',
                    'benefits' => $application['benefits'] ?? '',
                    'category' => $application['category'] ?? ''
                ];
                break;
            }
        }
    }
    
    // If still not found, try to find in recent jobs
    if (!$modal_job && !empty($data['recent_jobs'])) {
        foreach ($data['recent_jobs'] as $job) {
            if ($job['id'] == $job_details_job_id) {
                $modal_job = $job;
                break;
            }
        }
    }

    // Debug: Check if we found the job
    error_log("Job Details Modal Debug: show_job_details_modal=" . ($show_job_details_modal ? 'true' : 'false'));
    error_log("Job Details Modal Debug: job_details_job_id=" . $job_details_job_id);
    error_log("Job Details Modal Debug: modal_job=" . ($modal_job ? 'found' : 'not found'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Dashboard' ?></title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --danger: #e63946;
            --light: #f8f9fa;
            --dark: #212529;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fb;
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Main content area */
        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: #f5f7fb;
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Messages */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(76, 201, 240, 0.15);
            color: #0d6efd;
            border-left: 4px solid #4cc9f0;
        }

        .alert-error {
            background: rgba(230, 57, 70, 0.15);
            color: #e63946;
            border-left: 4px solid #e63946;
        }

        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .header-section h1 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 1000px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-500);
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 24px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .job-info {
            background: var(--gray-50);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .job-info h4 {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .job-meta {
            display: flex;
            flex-direction: column;
            gap: 6px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .job-meta i {
            width: 16px;
            margin-right: 8px;
        }

        .modal-message {
            text-align: center;
            padding: 20px 0;
            font-size: 1.1rem;
            color: var(--gray-700);
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            color: var(--primary);
        }

        .error-message {
            background: rgba(230, 57, 70, 0.1);
            border: 1px solid rgba(230, 57, 70, 0.2);
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            color: var(--danger);
            margin: 20px 0;
        }

        /* Leaflet Map Styles */
        .job-map {
            height: 200px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid var(--gray-300);
        }

        .map-placeholder {
            height: 200px;
            background: var(--gray-100);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-500);
            margin: 15px 0;
            border: 1px dashed var(--gray-300);
        }

        .map-placeholder i {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .location-details {
            background: var(--gray-50);
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .location-details strong {
            color: var(--dark);
        }

        /* Job Description Styles */
        .job-description {
            margin: 20px 0;
            line-height: 1.6;
        }

        .job-description h5 {
            color: var(--dark);
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .job-description p {
            color: var(--gray-700);
            margin-bottom: 15px;
        }

        .job-requirements, .job-benefits {
            margin: 15px 0;
        }

        .job-requirements ul, .job-benefits ul {
            padding-left: 20px;
            color: var(--gray-700);
        }

        .job-requirements li, .job-benefits li {
            margin-bottom: 8px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: var(--transition);
            border-left: 4px solid var(--primary);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .stat-info h3 {
            font-size: 2rem;
            margin: 0;
            color: var(--dark);
            font-weight: 700;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* Content Sections */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            .content-sections {
                grid-template-columns: 1fr;
            }
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4rem;
        }

        .section-header a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: var(--transition);
        }

        .section-header a:hover {
            color: var(--primary-dark);
        }

        /* Scrollable Lists */
        .scrollable-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .scrollable-list::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-list::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 10px;
        }

        .scrollable-list::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 10px;
        }

        .scrollable-list::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        .jobs-list, .applications-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .job-card, .application-card {
            border: 1px solid var(--gray-200);
            padding: 20px;
            border-radius: 10px;
            transition: var(--transition);
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .job-card:hover, .application-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .job-header, .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .job-header h4, .application-header h4 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .company, .job-title {
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 5px 0;
        }

        .location, .application-date {
            color: var(--gray-600);
            margin: 0 0 10px 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .description {
            color: var(--gray-700);
            margin: 0 0 15px 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.applied {
            background: rgba(76, 201, 240, 0.15);
            color: #0d6efd;
        }

        .status-badge.interview-scheduled {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        .status-badge.under-review {
            background: rgba(247, 37, 133, 0.15);
            color: var(--warning);
        }

        .status-badge.rejected {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
        }

        .status-badge.hired {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        /* Action Buttons */
        .job-actions, .application-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .btn-secondary:hover {
            background: var(--gray-300);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3ab3d4;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #3a84e6;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e6167a;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d13442;
        }

        /* Disabled Button Styles */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-success:disabled {
            background: var(--success);
            opacity: 0.8;
        }

        .btn-success:disabled:hover {
            background: var(--success);
            transform: none;
            box-shadow: none;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
        }

        .action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            background: white;
            border-radius: 10px;
            text-decoration: none;
            color: var(--gray-700);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            cursor: pointer;
            text-align: center;
        }

        .action-card:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        }

        .action-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .action-card:hover .action-icon {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 40px;
            background: var(--gray-100);
            border-radius: 8px;
        }

        /* Success Messages */
        .applied-badge, .saved-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .applied-badge {
            background: rgba(76, 201, 240, 0.15);
            color: #0d6efd;
        }

        .saved-badge {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #4361ee;
            color: white;
            border: none;
            border-radius: 8px;
            width: 40px;
            height: 40px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 70px;
            }
            
            .dashboard-container {
                padding: 15px;
            }
            
            .header-section {
                padding: 20px;
            }
            
            .header-section h1 {
                font-size: 1.8rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            }
            
            .job-actions, .application-actions {
                flex-direction: column;
            }
            
            .job-header, .application-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .modal-content {
                width: 95%;
                margin: 20px;
            }

            .modal-footer {
                flex-direction: column;
            }

            .job-map {
                height: 150px;
            }

            .scrollable-list {
                max-height: 350px;
            }
        }

        /* Additional styles for the resume preview modal */
#resumePreviewModal .modal-content {
    display: flex;
    flex-direction: column;
}

#pdfViewerContainer {
    position: relative;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #resumePreviewModal .modal-content {
        width: 95%;
        height: 95vh;
        margin: 10px;
    }
    
    .resume-actions {
        flex-direction: column;
    }
}
</style>
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Include Sidebar Component -->
    <?php $this->call->view('sidebar') ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Display Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Common Header -->
            <div class="header-section">
                <h1>Welcome back, <?= htmlspecialchars($user['name']) ?>! 👋</h1>
                <p class="subtitle">
                    <?php if ($role == 'job_seeker'): ?>
                        Here's your job search overview
                    <?php else: ?>
                        Here's your hiring dashboard overview
                    <?php endif; ?>
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <?php if ($role == 'job_seeker'): ?>
                    <!-- Job Seeker Stats -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['total_applications'] ?? 0) ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['interview_scheduled'] ?? 0) ?></h3>
                            <p>Interviews Scheduled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['saved_jobs_count'] ?? 0) ?></h3>
                            <p>Saved Jobs</p>
                        </div>
                    </div>

                    
                <?php else: ?>
                    <!-- Employer Stats -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['total_jobs'] ?? 0) ?></h3>
                            <p>Active Job Posts</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['total_applications'] ?? 0) ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($data['interview_scheduled'] ?? 0) ?></h3>
                            <p>Interviews Scheduled</p>
                        </div>
                    </div>

                
                <?php endif; ?>
            </div>

            <!-- Dynamic Content Sections -->
            <div class="content-sections">
                <?php if ($role == 'job_seeker'): ?>
                    <!-- Job Seeker Content -->
                    
                    <!-- Recent Applications -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Recent Applications</h2>
                            <a href="/application">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php if (!empty($data['recent_applications'])): ?>
                            <div class="scrollable-list">
                                <div class="applications-list">
                                    <?php foreach ($data['recent_applications'] as $application): ?>
                                        <div class="application-card">
                                            <div class="application-header">
                                                <h4><?= htmlspecialchars($application['job_title'] ?? 'No Title') ?></h4>
                                                <span class="status-badge <?= strtolower(str_replace(' ', '-', $application['status'])) ?>">
                                                    <?= $application['status'] ?>
                                                </span>
                                            </div>
                                            <p class="company"><?= htmlspecialchars($application['job_company'] ?? 'Unknown Company') ?></p>
                                            <p class="application-date"><i class="far fa-calendar"></i> <?= date('M j, Y g:i A', strtotime($application['created_at'])) ?></p>
                                            <div class="application-actions">
                                                <a href="/dashboard/overview?job_details=1&job_id=<?= $application['job_id'] ?? $application['id'] ?>" class="btn btn-primary">
                                                    <i class="fas fa-eye"></i> Job Details
                                                </a>
                                                <?php if (($application['status'] ?? '') != 'Interview Scheduled'): ?>
                                                 <?php if (($application['status'] ?? '') != 'Hired'): ?>
                                                <a href="/dashboard/overview?remove_application_confirm=1&application_id=<?= $application['id'] ?>" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i> Cancel Application
                                                </a>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">You haven't applied to any jobs yet. Start applying to see your applications here!</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recommended Jobs -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Jobs</h2>
                            <a href="/jobs">Browse All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php if (!empty($data['recent_jobs'])): ?>
                            <div class="scrollable-list">
                                <div class="jobs-list">
                                    <?php 
                                    // Limit to 4 recommended jobs initially
                                    $limited_jobs = array_slice($data['recent_jobs'], 0, 4);
                                    ?>
                                    <?php foreach ($limited_jobs as $job): ?>
                                        <div class="job-card">
                                            <div class="job-header">
                                                <h4><?= htmlspecialchars($job['title']) ?></h4>
                                                <span class="status-badge applied">Active</span>
                                            </div>
                                            <p class="company"><?= htmlspecialchars($job['company'] ?? $job['employer_name']) ?></p>
                                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></p>
                                            <p class="description"><?= substr(htmlspecialchars($job['description']), 0, 120) ?>...</p>
                                            <div class="job-actions">
                                                <?php if (in_array($job['id'], $applied_job_ids)): ?>
                                                    <button class="btn btn-success" disabled>
                                                        <i class="fas fa-check-circle"></i> Applied
                                                    </button>
                                                <?php else: ?>
                                                    <a href="/dashboard/overview?apply_job=1&job_id=<?= $job['id'] ?>" class="btn btn-primary">
                                                        <i class="fas fa-paper-plane"></i> Apply Now
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="/dashboard/overview?save_job=1&job_id=<?= $job['id'] ?>" class="btn <?= in_array($job['id'], $saved_job_ids) ? 'btn-warning' : 'btn-secondary' ?>">
                                                    <i class="<?= in_array($job['id'], $saved_job_ids) ? 'fas' : 'far' ?> fa-bookmark"></i>
                                                    <?= in_array($job['id'], $saved_job_ids) ? 'Saved' : 'Save Job' ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recommended jobs available. Check back later!</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- Employer Content -->
                    
                    <!-- Recent Applications -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Recent Applications</h2>
                            <a href="/application">View All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php if (!empty($data['recent_applications'])): ?>
                            <div class="scrollable-list">
                                <div class="applications-list">
                                    <?php foreach ($data['recent_applications'] as $application): ?>
                                        <div class="application-card">
                                            <div class="application-header">
                                                <h4><?= htmlspecialchars($application['applicant_name'] ?? 'Unknown Applicant') ?></h4>
                                                <span class="status-badge <?= strtolower(str_replace(' ', '-', $application['status'])) ?>">
                                                    <?= $application['status'] ?>
                                                </span>
                                            </div>
                                            <p class="job-title">Applied for: <?= htmlspecialchars($application['job_title'] ?? 'Unknown Job') ?></p>
                                            <p class="company"><?= htmlspecialchars($application['job_company'] ?? 'Unknown Company') ?></p>
                                            <p class="application-date"><i class="far fa-calendar"></i> <?= date('M j, Y g:i A', strtotime($application['created_at'])) ?></p>
                                            <div class="application-actions">
                                                <!-- Updated View Details button with query parameter -->
                                                <a href="/dashboard/overview?view_applicant=<?= $application['id'] ?>" 
                                                    class="btn btn-primary">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                               
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">No recent applications. Promote your jobs to get more applicants!</p>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Job Posts -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Your Job Posts</h2>
                            <a href="/dashboard/load/jobs">Manage All <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php if (!empty($data['recent_jobs'])): ?>
                            <div class="scrollable-list">
                                <div class="jobs-list">
                                    <?php 
                                    // Limit to 4 job posts for employers initially
                                    $limited_jobs = array_slice($data['recent_jobs'], 0, 4);
                                    ?>
                                    <?php foreach ($limited_jobs as $job): ?>
                                        <div class="job-card">
                                            <div class="job-header">
                                                <h4><?= htmlspecialchars($job['title']) ?></h4>
                                                <span class="status-badge applied">Active</span>
                                            </div>
                                            <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></p>
                                            <p class="description"><?= substr(htmlspecialchars($job['description']), 0, 120) ?>...</p>
                                            <div class="job-actions">
                                                <a href="/application" class="btn btn-primary">
                                                    <i class="fas fa-users"></i> View Applicants
                                                </a>
                                                <a href="/jobs" class="btn btn-secondary">
                                                    <i class="fas fa-edit"></i> Edit Post
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="no-data">You haven't posted any jobs yet. Create your first job post to get started!</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="section">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <?php if ($role == 'job_seeker'): ?>
                        <a href="/jobs" class="action-card">
                            <div class="action-icon"><i class="fas fa-search"></i></div>
                            <span>Browse Jobs</span>
                        </a>
                        <a href="/application" class="action-card">
                            <div class="action-icon"><i class="fas fa-file-alt"></i></div>
                            <span>My Applications</span>
                        </a>
                        <a href="/profile" class="action-card">
                            <div class="action-icon"><i class="fas fa-user-edit"></i></div>
                            <span>Update Profile</span>
                        </a>
                        <a href="/saved" class="action-card">
                            <div class="action-icon"><i class="fas fa-bookmark"></i></div>
                            <span>Saved Jobs</span>
                        </a>
                    <?php else: ?>
                        <a href="/jobs" class="action-card">
                            <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
                            <span>Post New Job</span>
                        </a>
                        <a href="/application" class="action-card">
                            <div class="action-icon"><i class="fas fa-users"></i></div>
                            <span>Manage Applications</span>
                        </a>
                        <a href="/profile" class="action-card">
                            <div class="action-icon"><i class="fas fa-building"></i></div>
                            <span>Company Profile</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

 <!-- Applicant Details Modal -->
<?php if (isset($data['show_applicant_modal']) && $data['show_applicant_modal'] && isset($data['applicant_data'])): ?>
<div id="applicantDetailsModal" class="modal active">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Applicant Details</h3>
            <a href="/dashboard/overview" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <?php
            $applicant = $data['applicant_data']['applicant'];
            $application = $data['applicant_data']['application'];
            $job = $data['applicant_data']['job'];
            ?>
            
            <div class="applicant-info">
                <!-- Basic Applicant Information -->
                <div class="job-info" style="margin-bottom: 20px;">
                    <h4><?= htmlspecialchars($applicant['name'] ?? 'Unknown Applicant') ?></h4>
                    <div class="job-meta">
                        <div>
                            <i class="fas fa-envelope"></i> 
                            <?= htmlspecialchars($applicant['email'] ?? 'No email provided') ?>
                        </div>
                        <?php if (!empty($applicant['profile']['phone'])): ?>
                            <div>
                                <i class="fas fa-phone"></i> 
                                <?= htmlspecialchars($applicant['profile']['phone']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($applicant['profile']['address'])): ?>
                            <div>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?= htmlspecialchars($applicant['profile']['address']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Application Details -->
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Application Details</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <div class="job-meta">
                            <div>
                                <i class="fas fa-briefcase"></i> 
                                <strong>Position:</strong> <?= htmlspecialchars($job['title']) ?>
                            </div>
                            <div>
                                <i class="fas fa-building"></i> 
                                <strong>Company:</strong> <?= htmlspecialchars($job['company']) ?>
                            </div>
                            <div>
                                <i class="fas fa-calendar"></i> 
                                <strong>Applied:</strong> <?= date('F j, Y \a\t g:i A', strtotime($application['created_at'])) ?>
                            </div>
                            <div>
                                <i class="fas fa-info-circle"></i> 
                                <strong>Status:</strong> 
                                <span class="status-badge <?= strtolower(str_replace(' ', '-', $application['status'])) ?>">
                                    <?= $application['status'] ?>
                                </span>
                            </div>
                            <?php if (!empty($application['interview_date'])): ?>
                                <div>
                                    <i class="fas fa-clock"></i> 
                                    <strong>Interview:</strong> <?= date('F j, Y \a\t g:i A', strtotime($application['interview_date'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Professional Summary -->
                <?php if (!empty($applicant['profile']['professional_summary'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Professional Summary</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($applicant['profile']['professional_summary'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if (!empty($applicant['profile']['skills'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Skills & Expertise</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($applicant['profile']['skills'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Work Experience -->
                <?php if (!empty($applicant['profile']['work_experience'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Work Experience</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($applicant['profile']['work_experience'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Education -->
                <?php if (!empty($applicant['profile']['education'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Education</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($applicant['profile']['education'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Resume Download -->
                <?php if (!empty($applicant['profile']['resume_file'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Resume</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200); text-align: center;">
                        
                    
                    
                    <a href="/<?= htmlspecialchars($applicant['profile']['resume_file']) ?>" 
                           class="btn btn-primary" 
                           target="_blank" 
                           download="resume_<?= htmlspecialchars($applicant['name']) ?>.pdf">
                            <i class="fas fa-download"></i> Download Resume
                        </a>
                    <div>-------------------------------------------------------OR-------------------------------------------------------</div>

                                    <!-- Preview Button -->
                        <a class="btn btn-info" onclick="openResumePreview('<?= htmlspecialchars($applicant['profile']['resume_file']) ?>', '<?= htmlspecialchars($applicant['name']) ?>')">
                            <i class="fas fa-eye"></i> Preview Resume
                        </a>
                        <p style="margin-top: 10px; color: var(--gray-600); font-size: 0.9rem;">
                            Click to download or preview the applicant's resume
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div style="margin: 20px 0; text-align: center; color: var(--gray-500);">
                    <i class="fas fa-file-alt fa-2x" style="margin-bottom: 10px;"></i>
                    <p>No resume uploaded by applicant</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <a href="/dashboard/overview" class="btn btn-secondary">Close</a>
        </div>
    </div>
</div>
<?php endif; ?>



<!-- Resume Preview Modal -->
<div id="resumePreviewModal" class="modal">
    <div class="modal-content" style="max-width: 900px; height: 90vh;">
        <div class="modal-header">
            <h3>Resume Preview - <span id="resumeApplicantName"></span></h3>
            <button class="modal-close" onclick="closeResumePreview()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 0; height: calc(100% - 120px);">
            <!-- PDF Viewer Container -->
            <div id="pdfViewerContainer" style="height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; background: var(--gray-100);">
                <div id="pdfViewer" style="width: 100%; height: 100%;">
                    <!-- PDF will be displayed here -->
                    <iframe id="pdfFrame" style="width: 100%; height: 100%; border: none; border-radius: 0 0 8px 8px;" 
                            frameborder="0"></iframe>
                </div>
                
                <!-- Fallback for non-PDF files -->
                <div id="fileFallback" style="display: none; text-align: center; padding: 40px;">
                    <i class="fas fa-file fa-3x" style="color: var(--gray-400); margin-bottom: 15px;"></i>
                    <h4 style="color: var(--gray-600); margin-bottom: 10px;">File Preview Not Available</h4>
                    <p style="color: var(--gray-500);">This file type cannot be previewed in the browser.</p>
                    <a href="#" id="fallbackDownload" class="btn btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-download"></i> Download File
                    </a>
                </div>
                
                <!-- Loading State -->
                <div id="pdfLoading" style="display: none; text-align: center;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary); margin-bottom: 15px;"></i>
                    <p style="color: var(--gray-600);">Loading resume...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeResumePreview()">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <a href="#" id="previewDownloadBtn" class="btn btn-primary" target="_blank" download>
                <i class="fas fa-download"></i> Download Resume
            </a>
        </div>
    </div>
</div>


    <!-- Remove Application Confirmation Modal -->
    <?php if ($show_remove_modal && $remove_application): ?>
    <div id="removeApplicationModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cancel Application</h3>
                <a href="/dashboard/overview" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="job-info">
                    <h4><?= htmlspecialchars($remove_application['job_title'] ?? 'No Title') ?></h4>
                    <div class="job-meta">
                        <div><i class="fas fa-building"></i> <?= htmlspecialchars($remove_application['job_company'] ?? 'Unknown Company') ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($remove_application['location'] ?? 'Location not specified') ?></div>
                        <div><i class="fas fa-calendar"></i> Applied on: <?= date('M j, Y g:i A', strtotime($remove_application['created_at'])) ?></div>
                        <div><i class="fas fa-info-circle"></i> Status: <span class="status-badge <?= strtolower(str_replace(' ', '-', $remove_application['status'])) ?>"><?= $remove_application['status'] ?></span></div>
                    </div>
                </div>

                <div class="modal-message">
                    <div class="modal-icon" style="color: var(--danger);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p>Are you sure you want to cancel this application?</p>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                        This action cannot be undone. You will need to re-apply if you change your mind.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/overview" class="btn btn-secondary">Cancel</a>
                <a href="/dashboard/overview?remove_application=1&application_id=<?= $remove_application['id'] ?>" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Yes, Cancel Application
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Job Details Modal -->
    <?php if ($show_job_details_modal && $modal_job): ?>
    <div id="jobDetailsModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Job Details</h3>
                <a href="/dashboard/overview" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="job-info">
                    <h4><?= htmlspecialchars($modal_job['title'] ?? $modal_job['job_title'] ?? 'No Title') ?></h4>
                    <div class="job-meta">
                        <div><i class="fas fa-building"></i> <?= htmlspecialchars($modal_job['company'] ?? $modal_job['job_company'] ?? 'Unknown Company') ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($modal_job['location'] ?? 'Location not specified') ?></div>
                        <?php if (!empty($modal_job['job_type'])): ?>
                            <div><i class="fas fa-briefcase"></i> <?= htmlspecialchars($modal_job['job_type']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($modal_job['salary'])): ?>
                            <div><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($modal_job['salary']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Location Map -->
                <div class="location-section">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Location</h5>
                    <div id="jobDetailsMap" class="job-map"></div>
                    <div class="location-details">
                        <strong>Location:</strong> <?= htmlspecialchars($modal_job['location'] ?? 'Location not specified') ?>
                    </div>
                </div>

                <!-- Job Description -->
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Description</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['description'] ?? $modal_job['job_description'] ?? 'No description available.')) ?>
                        </p>
                    </div>
                </div>

                <!-- Only show requirements/benefits if they exist in the database -->
                <?php if (!empty($modal_job['requirements'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Requirements</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['requirements'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($modal_job['benefits'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Benefits</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['benefits'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/overview" class="btn btn-secondary">Close</a>
              </div>
        </div>
    </div>
    <?php endif; ?>

     <!-- Apply Job Modal -->
    <?php if ($show_apply_modal && $modal_job): ?>
    <div id="applyModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Apply for Job</h3>
                <a href="/dashboard/overview" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="job-info">
                    <h4><?= htmlspecialchars($modal_job['title']) ?></h4>
                    <div class="job-meta">
                        <div><i class="fas fa-building"></i> <?= htmlspecialchars($modal_job['company']) ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($modal_job['location']) ?></div>
                        <?php if (!empty($modal_job['job_type'])): ?>
                            <div><i class="fas fa-briefcase"></i> <?= htmlspecialchars($modal_job['job_type']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($modal_job['salary'])): ?>
                            <div><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($modal_job['salary']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($modal_job['category'])): ?>
                            <div><i class="fas fa-tag"></i> <?= htmlspecialchars($modal_job['category']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Location Map -->
                <div class="location-section">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Location</h5>
                    <div id="applyJobMap" class="job-map"></div>
                    <div class="location-details">
                        <strong>Location:</strong> <?= htmlspecialchars($modal_job['location']) ?>
                    </div>
                </div>

                <!-- Job Description -->
                <?php if (!empty($modal_job['description'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Description</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['description'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Job Requirements -->
                <?php if (!empty($modal_job['requirements'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Requirements</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['requirements'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Job Benefits -->
                <?php if (!empty($modal_job['benefits'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Benefits</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['benefits'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal-message">
                    <div class="modal-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <?php if ($already_applied): ?>
                        <p>You have already applied for this position.</p>
                        <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                            You cannot apply for the same job multiple times.
                        </p>
                    <?php else: ?>
                        <p>Are you sure you want to apply for this position?</p>
                        <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                            Your application will be sent to the employer for review.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/overview" class="btn btn-secondary">Cancel</a>
                <?php if ($already_applied): ?>
                    <button class="btn btn-success" disabled>
                        <i class="fas fa-check-circle"></i> Already Applied
                    </button>
                <?php else: ?>
                    <a href="/dashboard/apply_job?job_id=<?= $modal_job['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Yes, Apply Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

      <!-- Save Job Modal -->
    <?php if ($show_save_modal && $modal_job): ?>
    <div id="saveModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?= $already_saved ? 'Remove Saved Job' : 'Save Job' ?></h3>
                <a href="/dashboard/overview" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="job-info">
                    <h4><?= htmlspecialchars($modal_job['title']) ?></h4>
                    <div class="job-meta">
                        <div><i class="fas fa-building"></i> <?= htmlspecialchars($modal_job['company']) ?></div>
                        <div><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($modal_job['location']) ?></div>
                        <?php if (!empty($modal_job['job_type'])): ?>
                            <div><i class="fas fa-briefcase"></i> <?= htmlspecialchars($modal_job['job_type']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($modal_job['salary'])): ?>
                            <div><i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($modal_job['salary']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($modal_job['category'])): ?>
                            <div><i class="fas fa-tag"></i> <?= htmlspecialchars($modal_job['category']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Location Map -->
                <div class="location-section">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Location</h5>
                    <div id="saveJobMap" class="job-map"></div>
                    <div class="location-details">
                        <strong>Location:</strong> <?= htmlspecialchars($modal_job['location']) ?>
                    </div>
                </div>

                <!-- Job Description -->
                <?php if (!empty($modal_job['description'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Job Description</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['description'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Job Requirements -->
                <?php if (!empty($modal_job['requirements'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Requirements</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['requirements'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Job Benefits -->
                <?php if (!empty($modal_job['benefits'])): ?>
                <div style="margin: 20px 0;">
                    <h5 style="margin-bottom: 10px; color: var(--dark);">Benefits</h5>
                    <div style="background: var(--gray-50); padding: 16px; border-radius: 8px; border: 1px solid var(--gray-200);">
                        <p style="color: var(--gray-700); line-height: 1.6; margin: 0;">
                            <?= nl2br(htmlspecialchars($modal_job['benefits'])) ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal-message">
                    <div class="modal-icon">
                        <i class="<?= $already_saved ? 'fas' : 'far' ?> fa-bookmark" style="color: <?= $already_saved ? 'var(--warning)' : 'var(--primary)' ?>;"></i>
                    </div>
                    <?php if ($already_saved): ?>
                        <p>Would you like to remove this job from your saved jobs?</p>
                        <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                            This job will be removed from your saved jobs list.
                        </p>
                    <?php else: ?>
                        <p>Would you like to save this job for later?</p>
                        <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                            You can view saved jobs in your dashboard anytime.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/overview" class="btn btn-secondary">Cancel</a>
                <a href="/dashboard/save_job?job_id=<?= $modal_job['id'] ?>" class="btn <?= $already_saved ? 'btn-warning' : 'btn-primary' ?>">
                    <?= $already_saved ? 'Yes, Remove' : 'Yes, Save Job' ?>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Modal -->
    <?php if (($show_apply_modal || $show_save_modal || $show_job_details_modal || $show_remove_modal) && !$modal_job && !$remove_application): ?>
    <div id="errorModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Error</h3>
                <a href="/dashboard/overview" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Job/Application not found or you don't have permission to access this.</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/overview" class="btn btn-primary">OK</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    $(document).ready(function() {
        // Mobile menu toggle
        $('.mobile-menu-toggle').on('click', function() {
            $('.sidebar').toggleClass('mobile-open');
        });
        
        // Close sidebar when clicking outside on mobile
        $(document).on('click', function(e) {
            if ($(window).width() <= 768) {
                if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.mobile-menu-toggle').length) {
                    $('.sidebar').removeClass('mobile-open');
                }
            }
        });

        // Close modal when clicking outside (for modals that might be added via JavaScript)
        $(document).on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                window.location.href = '/dashboard/overview';
            }
        });

        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = '/dashboard/overview';
            }
        });

        // Initialize maps when modals are shown
        function initializeMap(mapId, location) {
            // Default coordinates for Oriental Mindoro, Philippines
            let defaultCoords = [13.0000, 121.0833];
            let zoomLevel = 12;

            // Try to geocode the location
            geocodeLocation(location).then(coords => {
                const map = L.map(mapId).setView(coords, zoomLevel);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Add a marker for the job location
                L.marker(coords)
                    .addTo(map)
                    .bindPopup(`<strong>${location}</strong><br>Job Location`)
                    .openPopup();

            }).catch(error => {
                console.error('Geocoding error:', error);
                // Fallback to default coordinates
                const map = L.map(mapId).setView(defaultCoords, 10);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Add a marker with the location name
                L.marker(defaultCoords)
                    .addTo(map)
                    .bindPopup(`<strong>${location}</strong><br>Approximate Location`)
                    .openPopup();
            });
        }

        // Simple geocoding function using OpenStreetMap Nominatim
        function geocodeLocation(location) {
            return new Promise((resolve, reject) => {
                // Add Philippines to the search query for better results
                const searchQuery = location.includes('Philippines') ? location : location + ', Philippines';
                
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.length > 0) {
                            const coords = [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                            resolve(coords);
                        } else {
                            reject('Location not found');
                        }
                    })
                    .catch(error => reject(error));
            });
        }

        // Initialize maps when modals are active
        <?php if ($show_job_details_modal && $modal_job): ?>
            setTimeout(() => {
                initializeMap('jobDetailsMap', '<?= addslashes($modal_job['location']) ?>');
            }, 100);
        <?php endif; ?>

        <?php if ($show_apply_modal && $modal_job): ?>
            setTimeout(() => {
                initializeMap('applyJobMap', '<?= addslashes($modal_job['location']) ?>');
            }, 100);
        <?php endif; ?>

        <?php if ($show_save_modal && $modal_job): ?>
            setTimeout(() => {
                initializeMap('saveJobMap', '<?= addslashes($modal_job['location']) ?>');
            }, 100);
        <?php endif; ?>
    });

// Simple contact functionality
$(document).ready(function() {
    // Contact applicant button in cards
    $('.contact-applicant').on('click', function() {
        const email = $(this).data('email');
        if (email) {
            const jobTitle = $(this).closest('.application-card').find('.job-title').text().replace('Applied for: ', '');
            const subject = `Regarding Your Application for ${jobTitle}`;
            window.location.href = `mailto:${email}?subject=${encodeURIComponent(subject)}`;
        } else {
            alert('No email address available for this applicant.');
        }
    });

    // Close modal when clicking outside
    $(document).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            window.location.href = '/dashboard/overview';
        }
    });

    // Escape key to close modal
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('.modal.active').length) {
            window.location.href = '/dashboard/overview';
        }
    });
});



function openResumePreview(resumeFile, applicantName) {
    const modal = document.getElementById('resumePreviewModal');
    const pdfFrame = document.getElementById('pdfFrame');
    const fallbackSection = document.getElementById('fileFallback');
    const pdfViewer = document.getElementById('pdfViewer');
    const loadingSection = document.getElementById('pdfLoading');
    const applicantNameSpan = document.getElementById('resumeApplicantName');
    const downloadBtn = document.getElementById('previewDownloadBtn');
    const fallbackDownload = document.getElementById('fallbackDownload');
    
    // Set applicant name
    applicantNameSpan.textContent = applicantName;
    
    // Show loading state
    pdfViewer.style.display = 'none';
    fallbackSection.style.display = 'none';
    loadingSection.style.display = 'block';
    
    // Show modal
    modal.classList.add('active');
    
    // Check if file is PDF
    const fileExtension = resumeFile.split('.').pop().toLowerCase();
    
    if (fileExtension === 'pdf') {
        // For PDF files, use iframe embedding
        setTimeout(() => {
            pdfFrame.src = '/' + resumeFile + '#view=FitH';
            pdfViewer.style.display = 'block';
            loadingSection.style.display = 'none';
        }, 500);
        
        // Set download link
        downloadBtn.href = '/' + resumeFile;
        downloadBtn.setAttribute('download', 'resume_' + applicantName + '.pdf');
    } else {
        // For non-PDF files, show fallback
        setTimeout(() => {
            pdfViewer.style.display = 'none';
            fallbackSection.style.display = 'block';
            loadingSection.style.display = 'none';
            
            // Set fallback download link
            fallbackDownload.href = '/' + resumeFile;
            fallbackDownload.setAttribute('download', 'resume_' + applicantName + '.' + fileExtension);
        }, 500);
        
        // Set download link for non-PDF as well
        downloadBtn.href = '/' + resumeFile;
        downloadBtn.setAttribute('download', 'resume_' + applicantName + '.' + fileExtension);
    }
}

function closeResumePreview() {
    const modal = document.getElementById('resumePreviewModal');
    const pdfFrame = document.getElementById('pdfFrame');
    
    // Hide modal
    modal.classList.remove('active');
    
    // Reset iframe source
    setTimeout(() => {
        pdfFrame.src = '';
    }, 300);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('resumePreviewModal');
    if (e.target === modal) {
        closeResumePreview();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeResumePreview();
    }
});
    
    </script>
</body>
</html>