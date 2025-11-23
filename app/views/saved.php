<?php
$user = $data['user'];
$role = $user['role'];

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Get saved jobs data
$saved_jobs = $data['saved_jobs'] ?? [];
$total_saved = $data['total_saved'] ?? 0;

// Get applied job IDs for display
$applied_job_ids = $data['applied_job_ids'] ?? [];

// Get modal data from controller
$show_apply_modal = $data['show_apply_modal'] ?? false;
$show_unsave_modal = $data['show_unsave_modal'] ?? false;
$show_job_details_modal = $data['show_job_details_modal'] ?? false;
$modal_job_id = $data['modal_job_id'] ?? null;
$modal_job = $data['modal_job'] ?? null;
$already_applied = $data['already_applied'] ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['title'] ?? 'Saved Jobs' ?></title>
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

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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

        /* Bulk Actions */
        .bulk-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            align-items: center;
            flex-wrap: wrap;
        }

        .bulk-select {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            color: var(--gray-700);
        }

        .select-all-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .bulk-action-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        /* Jobs Grid */
        .jobs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .job-card {
            background: white;
            border: 1px solid var(--gray-200);
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
        }

        .job-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }

        .job-card.with-checkbox {
            padding-left: 45px;
        }

        /* Checkbox for bulk actions */
        .job-checkbox {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 18px;
            height: 18px;
            cursor: pointer;
            z-index: 2;
        }

        /* Quick Unsave Button */
        .quick-unsave {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(230, 57, 70, 0.1);
            color: var(--danger);
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            font-size: 1rem;
        }

        .quick-unsave:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            padding-right: 50px;
        }

        .job-header h3 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.3rem;
            line-height: 1.3;
        }

        .saved-badge {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .job-badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-full-time { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .badge-part-time { background: rgba(76, 201, 240, 0.1); color: var(--success); }
        .badge-remote { background: rgba(114, 9, 183, 0.1); color: var(--secondary); }
        .badge-contract { background: rgba(247, 37, 133, 0.1); color: var(--warning); }
        .badge-internship { background: rgba(230, 57, 70, 0.1); color: var(--danger); }

        .company {
            font-weight: 600;
            color: var(--primary);
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }

        .location {
            color: var(--gray-600);
            margin: 0 0 12px 0;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .description {
            color: var(--gray-700);
            margin: 0 0 20px 0;
            line-height: 1.5;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .job-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-200);
        }

        .salary {
            font-weight: 600;
            color: var(--success);
            font-size: 1rem;
        }

        .posted-date {
            color: var(--gray-500);
            font-size: 0.85rem;
        }

        .job-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            flex: 1;
            justify-content: center;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d13442;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 60px 40px;
            background: var(--gray-100);
            border-radius: 12px;
            grid-column: 1 / -1;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: var(--gray-400);
        }

        .no-data h3 {
            margin: 0 0 10px 0;
            color: var(--gray-600);
        }

        .no-data p {
            margin: 0;
            font-size: 1rem;
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
            max-width: 700px;
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

        /* Quick Action Row in Modals */
        .quick-actions-row {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .quick-action-btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-decoration: none;
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
            
            .jobs-grid {
                grid-template-columns: 1fr;
            }
            
            .job-actions {
                flex-direction: column;
            }
            
            .job-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding-right: 0;
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

            .quick-actions-row {
                flex-direction: column;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .job-card.with-checkbox {
                padding-left: 40px;
            }

            .job-checkbox {
                top: 15px;
                left: 15px;
            }

            .quick-unsave {
                top: 15px;
                right: 15px;
                width: 32px;
                height: 32px;
                font-size: 0.9rem;
            }
        }
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

            <!-- Header Section -->
            <div class="header-section">
                <h1>Saved Jobs 💼</h1>
                <p class="subtitle">
                    Your collection of interesting job opportunities - manage them easily
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
                    <div class="stat-info">
                        <h3><?= htmlspecialchars($total_saved) ?></h3>
                        <p>Total Saved Jobs</p>
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
            </div>

            <!-- Bulk Actions -->
            <?php if (!empty($saved_jobs)): ?>
            <div class="bulk-actions">
                <div class="bulk-select">
                    <input type="checkbox" id="selectAll" class="select-all-checkbox">
                    <label for="selectAll">Select all <?= count($saved_jobs) ?> jobs</label>
                </div>
                <button class="bulk-action-btn btn-danger" id="bulkUnsave" style="display: none;">
                    <i class="fas fa-bookmark"></i> Unsave Selected
                </button>
                <span id="selectedCount" style="color: var(--gray-600); font-size: 0.9rem; margin-left: auto;"></span>
            </div>
            <?php endif; ?>

            <!-- Saved Jobs Grid -->
            <div class="jobs-grid">
                <?php if (!empty($saved_jobs)): ?>
                    <?php foreach ($saved_jobs as $job): ?>
                        <div class="job-card with-checkbox">
                            <!-- Checkbox for bulk actions -->
                            <input type="checkbox" class="job-checkbox" value="<?= $job['id'] ?>" 
                                   onchange="updateBulkActions()">
                            
                            <!-- Quick Unsave Button -->
                            <button class="quick-unsave" title="Unsave this job" 
                                    onclick="showUnsaveConfirmation(<?= $job['id'] ?>, '<?= addslashes($job['title']) ?>')">
                                <i class="fas fa-bookmark"></i>
                            </button>
                            
                            <div class="job-header">
                                <h3><?= htmlspecialchars($job['title']) ?></h3>
                                <div style="display: flex; flex-direction: column; gap: 5px; align-items: flex-end;">
                                    <span class="saved-badge">
                                        <i class="fas fa-bookmark"></i> Saved
                                    </span>
                                    <span class="job-badge badge-<?= strtolower(str_replace('-', '_', $job['job_type'] ?? 'full-time')) ?>">
                                        <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>
                                    </span>
                                </div>
                            </div>
                            <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                            <p class="location"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($job['location']) ?></p>
                            <p class="description"><?= substr(htmlspecialchars($job['description']), 0, 150) ?>...</p>
                            
                            <div class="job-meta">
                                <?php if (!empty($job['salary'])): ?>
                                    <span class="salary"><?= htmlspecialchars($job['salary']) ?></span>
                                <?php else: ?>
                                    <span class="salary">Salary not specified</span>
                                <?php endif; ?>
                                <span class="posted-date">Saved <?= date('M j, Y', strtotime($job['created_at'])) ?></span>
                            </div>
                            
                            <div class="job-actions">
                                <a href="/dashboard/saved?job_details=1&job_id=<?= $job['id'] ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                
                                <?php if (in_array($job['id'], $applied_job_ids)): ?>
                                    <button class="btn btn-success" disabled>
                                        <i class="fas fa-check-circle"></i> Applied
                                    </button>
                                <?php else: ?>
                                    
                                <?php endif; ?>
                                
                                <button class="btn btn-danger" onclick="showUnsaveConfirmation(<?= $job['id'] ?>, '<?= addslashes($job['title']) ?>')">
                                    <i class="fas fa-bookmark"></i> Unsave
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="far fa-bookmark"></i>
                        <h3>No Saved Jobs Yet</h3>
                        <p>Start browsing jobs and save the ones you're interested in!</p>
                        <div style="margin-top: 20px;">
                            <a href="/jobs" class="btn btn-primary" style="display: inline-flex; width: auto; padding: 12px 24px;">
                                <i class="fas fa-search"></i> Browse Jobs
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Job Details Modal -->
    <?php if ($show_job_details_modal && $modal_job): ?>
    <div id="jobDetailsModal" class="modal active">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Job Details</h3>
                <a href="/dashboard/saved" class="modal-close">&times;</a>
            </div>
            <div class="modal-body">
                <!-- Quick Action Row -->
                <div class="quick-actions-row">
                    <?php if (in_array($modal_job['id'], $applied_job_ids)): ?>
                        <button class="quick-action-btn btn-success" disabled>
                            <i class="fas fa-check-circle"></i> Already Applied
                        </button>
                    <?php else: ?>
                        <a href="/dashboard/saved?apply_job=1&job_id=<?= $modal_job['id'] ?>" class="quick-action-btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Apply Now
                        </a>
                    <?php endif; ?>
                    <button class="quick-action-btn btn-danger" onclick="showUnsaveConfirmation(<?= $modal_job['id'] ?>, '<?= addslashes($modal_job['title']) ?>')">
                        <i class="fas fa-bookmark"></i> Unsave Job
                    </button>
                </div>

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
                    <div id="jobDetailsMap" class="job-map"></div>
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
            </div>
            <div class="modal-footer">
                <a href="/dashboard/saved" class="btn btn-secondary">Close</a>
                <?php if (in_array($modal_job['id'], $applied_job_ids)): ?>
                    <button class="btn btn-success" disabled>
                        <i class="fas fa-check-circle"></i> Already Applied
                    </button>
                <?php else: ?>
                    <a href="/dashboard/saved?apply_job=1&job_id=<?= $modal_job['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Apply Now
                    </a>
                <?php endif; ?>
                <button class="btn btn-danger" onclick="showUnsaveConfirmation(<?= $modal_job['id'] ?>, '<?= addslashes($modal_job['title']) ?>')">
                    <i class="fas fa-bookmark"></i> Unsave Job
                </button>
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
                <a href="/dashboard/saved" class="modal-close">&times;</a>
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
                    </div>
                </div>

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
                <a href="/dashboard/saved" class="btn btn-secondary">Cancel</a>
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

    <!-- Unsave Confirmation Modal (JavaScript) -->
    <div id="unsaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="unsaveTitle">Unsave Job</h3>
                <button class="modal-close" onclick="closeUnsaveModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-message">
                    <div class="modal-icon" style="color: var(--danger);">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <p id="unsaveMessage">Are you sure you want to unsave this job?</p>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                        This job will be removed from your saved jobs. You can save it again later if needed.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeUnsaveModal()">Cancel</button>
                <button class="btn btn-danger" id="confirmUnsaveBtn">
                    <i class="fas fa-bookmark"></i> Yes, Unsave
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Unsave Confirmation Modal -->
    <div id="bulkUnsaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Unsave Multiple Jobs</h3>
                <button class="modal-close" onclick="closeBulkUnsaveModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="modal-message">
                    <div class="modal-icon" style="color: var(--danger);">
                        <i class="fas fa-bookmark"></i>
                    </div>
                    <p id="bulkUnsaveMessage">Are you sure you want to unsave <span id="selectedJobsCount">0</span> jobs?</p>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                        These jobs will be removed from your saved jobs. You can save them again later if needed.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeBulkUnsaveModal()">Cancel</button>
                <button class="btn btn-danger" id="confirmBulkUnsaveBtn">
                    <i class="fas fa-bookmark"></i> Yes, Unsave All
                </button>
            </div>
        </div>
    </div>

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

        // Close modal when clicking outside
        $(document).on('click', function(e) {
            if ($(e.target).hasClass('modal')) {
                window.location.href = '/dashboard/saved';
            }
        });

        // Escape key to close modals
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                window.location.href = '/dashboard/saved';
            }
        });

        // Bulk actions functionality
        $('#selectAll').on('change', function() {
            $('.job-checkbox').prop('checked', this.checked);
            updateBulkActions();
        });

        // Initialize bulk actions
        updateBulkActions();

        // Initialize maps when modals are active
        <?php if ($show_job_details_modal && $modal_job): ?>
            setTimeout(() => {
                initializeMap('jobDetailsMap', '<?= addslashes($modal_job['location']) ?>');
            }, 100);
        <?php endif; ?>
    });

    // Bulk actions functions
    function updateBulkActions() {
        const selectedJobs = $('.job-checkbox:checked');
        const selectedCount = selectedJobs.length;
        
        if (selectedCount > 0) {
            $('#bulkUnsave').show().html(`<i class="fas fa-bookmark"></i> Unsave Selected (${selectedCount})`);
            $('#selectedCount').text(`${selectedCount} job${selectedCount !== 1 ? 's' : ''} selected`);
        } else {
            $('#bulkUnsave').hide();
            $('#selectedCount').text('');
        }
        
        // Update select all checkbox
        const totalCheckboxes = $('.job-checkbox').length;
        $('#selectAll').prop('checked', totalCheckboxes > 0 && selectedCount === totalCheckboxes);
    }

    // Bulk unsave functionality
    $('#bulkUnsave').on('click', function() {
        const selectedJobs = $('.job-checkbox:checked');
        const selectedCount = selectedJobs.length;
        
        if (selectedCount > 0) {
            $('#selectedJobsCount').text(selectedCount);
            $('#bulkUnsaveMessage').text(`Are you sure you want to unsave ${selectedCount} job${selectedCount !== 1 ? 's' : ''}?`);
            
// Bulk unsave functionality
$('#bulkUnsave').on('click', function() {
    const selectedJobs = $('.job-checkbox:checked');
    const selectedCount = selectedJobs.length;
    
    if (selectedCount > 0) {
        $('#selectedJobsCount').text(selectedCount);
        $('#bulkUnsaveMessage').text(`Are you sure you want to unsave ${selectedCount} job${selectedCount !== 1 ? 's' : ''}?`);
        
        $('#confirmBulkUnsaveBtn').off('click').on('click', function() {
            const jobIds = selectedJobs.map(function() {
                return $(this).val();
            }).get().join(',');
            
            // Use the new bulk_unsave_job route
            window.location.href = `/dashboard/bulk_unsave_job?job_ids=${jobIds}`;
        });
        
        $('#bulkUnsaveModal').addClass('active');
    }
});
        }
});

    function closeBulkUnsaveModal() {
        $('#bulkUnsaveModal').removeClass('active');
    }

   // Individual unsave functionality
function showUnsaveConfirmation(jobId, jobTitle) {
    $('#unsaveMessage').text(`Are you sure you want to unsave "${jobTitle}"?`);
    $('#confirmUnsaveBtn').off('click').on('click', function() {
        // Use the new unsave_job route
        window.location.href = `/dashboard/unsave_job?job_id=${jobId}`;
    }); 
    $('#unsaveModal').addClass('active');
}

function closeUnsaveModal() {
    $('#unsaveModal').removeClass('active');
}

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
    </script>
</body>
</html>