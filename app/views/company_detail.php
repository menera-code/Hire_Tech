<?php
// Use session data directly if user data is not available
$user = $data['user'] ?? $_SESSION['user'] ?? [];
$user_role = $user['role'] ?? 'job_seeker';
$is_employer = ($user_role === 'employer');

// Set variables with proper fallbacks
$company = $data['company'] ?? [];
$current_page = $data['current_page'] ?? 'companies';
$title = $data['title'] ?? 'Company - HireTech';

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Helper function to format text with line breaks
function formatText($text) {
    if (empty($text)) return '<p class="text-muted">Not specified</p>';
    // Convert line breaks to HTML and preserve bullet points
    $formatted = nl2br(htmlspecialchars($text));
    return $formatted;
}

// Helper function to format date
function formatDate($dateString) {
    if (empty($dateString)) return 'Date not available';
    try {
        $date = new DateTime($dateString);
        return $date->format('M j, Y g:i A');
    } catch (Exception $e) {
        return 'Date not available';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
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

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
            margin-bottom: 30px;
            height: calc(100vh - 200px);
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            flex-direction: column;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-200);
            flex-shrink: 0;
        }

        .section-header h2 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4rem;
        }

        /* Scrollable Content Areas */
        .scrollable-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Custom Scrollbar */
        .scrollable-content::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-content::-webkit-scrollbar-track {
            background: var(--gray-100);
            border-radius: 10px;
        }

        .scrollable-content::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 10px;
        }

        .scrollable-content::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        /* Company Header */
        .company-header {
            display: flex;
            align-items: flex-start;
            gap: 25px;
            margin-bottom: 25px;
        }

        .company-logo {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid var(--gray-200);
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            border-radius: 12px;
            object-fit: cover;
        }

        .company-info {
            flex: 1;
        }

        .company-info h1 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-weight: 700;
            font-size: 2rem;
        }

        .company-industry {
            color: var(--primary);
            font-weight: 600;
            margin: 0 0 15px 0;
            font-size: 1.1rem;
        }

        .company-description {
            color: var(--gray-700);
            margin: 0 0 20px 0;
            line-height: 1.6;
            font-size: 1rem;
        }

        /* Company Stats */
        .company-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--gray-100);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Company Details */
        .company-details {
            margin-bottom: 25px;
        }

        .detail-section {
            margin-bottom: 25px;
        }

        .detail-section h3 {
            color: var(--primary);
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .detail-section h3 i {
            font-size: 1rem;
        }

        .detail-content {
            background: var(--gray-100);
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
            line-height: 1.6;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--dark);
            font-weight: 500;
        }

        /* Recent Jobs */
        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .job-card {
            border: 1px solid var(--gray-200);
            padding: 20px;
            border-radius: 10px;
            transition: var(--transition);
            background: white;
        }

        .job-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .job-header h4 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .meta-item i {
            color: var(--primary);
        }

        .job-description {
            color: var(--gray-700);
            margin: 0 0 15px 0;
            line-height: 1.5;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
            flex-shrink: 0;
        }

        .btn {
            padding: 12px 20px;
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

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e6167a;
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 0.8rem;
        }

        /* Text Muted */
        .text-muted {
            color: var(--gray-500) !important;
            font-style: italic;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 40px;
            background: var(--gray-100);
            border-radius: 8px;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-200);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--dark);
            font-size: 1.4rem;
            line-height: 1.3;
            flex: 1;
            margin-right: 20px;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-500);
            padding: 5px;
            transition: var(--transition);
        }

        .close-modal:hover {
            color: var(--danger);
        }

        .job-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        /* ========== MAP STYLES ========== */
        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            margin-bottom: 15px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
        }

        /* Map containers MUST have explicit dimensions */
        #companyMap, #jobDetailsMap {
            height: 100% !important;
            width: 100% !important;
            min-height: 300px;
            position: relative;
            z-index: 1;
        }

        /* Leaflet container overrides */
        .leaflet-container {
            height: 100% !important;
            width: 100% !important;
            background: #f8f9fa !important;
            border-radius: 8px;
        }

        .leaflet-tile-container {
            position: absolute;
            left: 0;
            top: 0;
        }

        .leaflet-map-pane {
            z-index: 1;
        }

        .map-coordinates {
            background: var(--gray-100);
            padding: 10px;
            border-radius: 6px;
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-bottom: 10px;
        }

        .map-instructions {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 10px;
            font-style: italic;
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
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
                height: auto;
            }
            
            .company-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 15px;
            }
            
            .company-logo {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            .company-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }

            .scrollable-content {
                overflow-y: visible;
                padding-right: 0;
            }

            .modal-content {
                width: 95%;
                padding: 20px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .job-info-grid {
                grid-template-columns: 1fr;
            }

            .modal-header {
                flex-direction: column;
                gap: 10px;
            }

            .modal-header h3 {
                margin-right: 0;
            }

            .map-container {
                height: 250px;
            }
            
            #companyMap, #jobDetailsMap {
                min-height: 250px;
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
    <?php 
    if (isset($this) && method_exists($this, 'view')) {
        $this->view('sidebar');
    } else {
        include 'sidebar.php';
    }
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="dashboard-container">
            <!-- Display Messages -->
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Back Button -->
            <div style="margin-bottom: 20px;">
                <a href="/companies" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i>
                    Back to Companies
                </a>
            </div>

            <?php if (!empty($company)): ?>
                <!-- Company Header -->
                <div class="header-section">
                    <div class="company-header">
                        <div class="company-logo">
                            <?php if (!empty($company['company_logo'])): ?>
                                <img src="/<?= htmlspecialchars($company['company_logo']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-building"></i>
                            <?php endif; ?>
                        </div>
                        <div class="company-info">
                            <h1><?= htmlspecialchars($company['company_name']) ?></h1>
                            <?php if (!empty($company['company_industry'])): ?>
                                <p class="company-industry"><?= htmlspecialchars($company['company_industry']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($company['company_description'])): ?>
                                <p class="company-description"><?= htmlspecialchars($company['company_description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="content-grid">
                    <!-- Company Sidebar -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Company Overview</h2>
                        </div>

                        <!-- Scrollable Company Content -->
                        <div class="scrollable-content">
                            <!-- Company Stats -->
                            <div class="company-stats">
                                <div class="stat-item">
                                    <div class="stat-number"><?= $company['active_jobs'] ?? 0 ?></div>
                                    <div class="stat-label">Active Jobs</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?= $company['total_jobs'] ?? 0 ?></div>
                                    <div class="stat-label">Total Jobs</div>
                                </div>
                                <?php if (!empty($company['company_size'])): ?>
                                <div class="stat-item">
                                    <div class="stat-number"><?= htmlspecialchars($company['company_size']) ?></div>
                                    <div class="stat-label">Company Size</div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Company Details -->
                            <div class="company-details">
                                <div class="detail-section">
                                    <h3><i class="fas fa-info-circle"></i> Company Information</h3>
                                    <div class="info-grid">
                                        <?php if (!empty($company['company_industry'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Industry</span>
                                            <span class="info-value"><?= htmlspecialchars($company['company_industry']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($company['company_size'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Company Size</span>
                                            <span class="info-value"><?= htmlspecialchars($company['company_size']) ?> employees</span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($company['contact_name'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Contact Person</span>
                                            <span class="info-value"><?= htmlspecialchars($company['contact_name']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($company['contact_email'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Contact Email</span>
                                            <span class="info-value"><?= htmlspecialchars($company['contact_email']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($company['phone'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Phone</span>
                                            <span class="info-value"><?= htmlspecialchars($company['phone']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($company['member_since'])): ?>
                                        <div class="info-item">
                                            <span class="info-label">Member Since</span>
                                            <span class="info-value"><?= date('M Y', strtotime($company['member_since'])) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if (!empty($company['company_address'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-map-marker-alt"></i> Location</h3>
                                    <div class="detail-content">
                                        <?= htmlspecialchars($company['company_address']) ?>
                                        
                                        <!-- Company Location Map -->
                                        <div class="map-container">
                                            <div id="companyMap"></div>
                                        </div>
                                        <div class="map-coordinates">
                                            <span id="companyCoordinates">Loading location...</span>
                                        </div>
                                       
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($company['company_website'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-globe"></i> Website</h3>
                                    <div class="detail-content">
                                        <a href="<?= htmlspecialchars($company['company_website']) ?>" target="_blank" style="color: var(--primary); text-decoration: none;">
                                            <?= htmlspecialchars($company['company_website']) ?>
                                            <i class="fas fa-external-link-alt" style="margin-left: 5px; font-size: 0.8rem;"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>


                                <?php if (!empty($company['google_form_url'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-envelope"></i> Contact Company</h3>
                                    <div class="detail-content">
                                        <p>Interested in working at <?= htmlspecialchars($company['company_name']) ?>? Send them a message!</p>
                                        
                                        <!-- Contact Button -->
                                        <button class="btn btn-primary btn-block" id="showContactForm" style="margin-top: 15px;">
                                            <i class="fas fa-paper-plane"></i> Contact Company
                                        </button>
                                        
                                        <div style="margin-top: 10px; font-size: 0.8rem; color: var(--gray-500); text-align: center;">
                                            <i class="fas fa-shield-alt"></i> Your information is secure
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="/companies" class="btn btn-secondary">
                                <i class="fas fa-building"></i> Browse Companies
                            </a>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="section">
                        <!-- Scrollable Main Content -->
                        <div class="scrollable-content">
                            <?php if (!empty($company['company_description'])): ?>
                            <div class="detail-section">
                                <h3><i class="fas fa-file-alt"></i> About Company</h3>
                                <div class="detail-content">
                                    <?= formatText($company['company_description']) ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Recent Jobs -->
                            <div class="detail-section">
                                <div class="section-header">
                                    <h2>All Jobs</h2>
                                    <span style="color: var(--gray-600); font-size: 0.9rem;">
                                        <?= count($company['recent_jobs'] ?? []) ?> job<?= count($company['recent_jobs'] ?? []) !== 1 ? 's' : '' ?>
                                    </span>
                                </div>

                                <?php if (!empty($company['recent_jobs'])): ?>
                                    <div class="jobs-list">
                                        <?php foreach ($company['recent_jobs'] as $job): ?>
                                        <div class="job-card">
                                            <div class="job-header">
                                                <h4><?= htmlspecialchars($job['title']) ?></h4>
                                                <span style="color: var(--success); font-weight: 600;">
                                                    <?= htmlspecialchars($job['salary'] ?? 'Salary not specified') ?>
                                                </span>
                                            </div>
                                            <div class="job-meta">
                                                <span class="meta-item">
                                                    <i class="fas fa-briefcase"></i>
                                                    <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>
                                                </span>
                                                <span class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($job['location']) ?>
                                                </span>
                                                <?php if (!empty($job['category'])): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-tag"></i>
                                                    <?= htmlspecialchars($job['category']) ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (!empty($job['description'])): ?>
                                            <p class="job-description">
                                                <?= htmlspecialchars(substr($job['description'], 0, 150)) ?><?= strlen($job['description']) > 150 ? '...' : '' ?>
                                            </p>
                                            <?php endif; ?>
                                            <div class="action-buttons" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--gray-200);">
                                                <!-- View Details Button -->
                                                <button class="btn btn-primary view-job-details" 
                                                        data-job-id="<?= $job['id'] ?>" 
                                                        data-job-title="<?= htmlspecialchars($job['title']) ?>"
                                                        data-job-company="<?= htmlspecialchars($job['company']) ?>"
                                                        data-job-location="<?= htmlspecialchars($job['location']) ?>"
                                                        data-job-type="<?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>"
                                                        data-job-salary="<?= htmlspecialchars($job['salary'] ?? '') ?>"
                                                        data-job-category="<?= htmlspecialchars($job['category'] ?? '') ?>"
                                                        data-job-description="<?= htmlspecialchars($job['description'] ?? '') ?>"
                                                        data-job-requirements="<?= htmlspecialchars($job['requirements'] ?? '') ?>"
                                                        data-job-benefits="<?= htmlspecialchars($job['benefits'] ?? '') ?>"
                                                        data-job-created-at="<?= htmlspecialchars($job['created_at'] ?? '') ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                                
                                                <?php if (!$is_employer): ?>
                                                <a href="/dashboard/apply_job?job_id=<?= $job['id'] ?>" class="btn btn-success btn-sm" style="padding: 8px 12px; font-size: 0.8rem;">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                                        <p>No current job openings at the moment.</p>
                                        <p style="font-size: 0.9rem; margin-top: 10px;">Check back later for new opportunities!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Company Not Found -->
                <div class="header-section">
                    <h1>Company Not Found</h1>
                    <p class="subtitle">The company you're looking for doesn't exist or has been removed.</p>
                </div>
                <div class="section" style="text-align: center;">
                    <div class="no-data">
                        <i class="fas fa-building" style="font-size: 4rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                        <h3 style="color: var(--gray-600); margin-bottom: 10px;">Company Not Available</h3>
                        <p style="margin-bottom: 20px;">The company you're trying to view is not available.</p>
                        <a href="/companies" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Companies
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<!-- Google Form Modal -->
<div id="googleFormModal" class="modal">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h3>Contact <?= htmlspecialchars($company['company_name'] ?? 'Company') ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        
        <div class="modal-body" style="padding: 0;">
            <!-- Loading State -->
            <div id="formLoading" style="padding: 40px; text-align: center; background: var(--gray-100);">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary); margin-bottom: 15px;"></i>
                <p style="color: var(--gray-600);">Loading contact form...</p>
            </div>
            
            <!-- Google Form Iframe -->
            <iframe id="googleFormIframe" 
                    src="" 
                    width="100%" 
                    height="600" 
                    frameborder="0" 
                    marginheight="0" 
                    marginwidth="0"
                    style="display: none; border-radius: 0 0 var(--border-radius) var(--border-radius);"
                    onload="document.getElementById('formLoading').style.display='none'; this.style.display='block';">
            </iframe>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary close-modal">
                <i class="fas fa-times"></i> Close
            </button>
            <a href="<?= htmlspecialchars($company['google_form_url'] ?? '#') ?>" 
               target="_blank" 
               class="btn btn-primary" 
               id="openInNewTab">
                <i class="fas fa-external-link-alt"></i> Open in New Tab
            </a>
        </div>
    </div>
</div>

    <!-- Job Details Modal -->
    <div id="jobModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalJobTitle">Job Title</h3>
                <button class="close-modal">&times;</button>
            </div>
            
            <div class="job-details">
                <div class="job-info-grid">
                    <div class="info-item">
                        <span class="info-label">Company</span>
                        <span class="info-value" id="modalCompany"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Location</span>
                        <span class="info-value" id="modalLocation"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Job Type</span>
                        <span class="info-value" id="modalJobType"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Salary</span>
                        <span class="info-value" id="modalSalary"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Category</span>
                        <span class="info-value" id="modalCategory"></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Posted</span>
                        <span class="info-value" id="modalPostedDate"></span>
                    </div>
                </div>

                <!-- Job Location Map -->
                <div class="detail-section">
                    <h4><i class="fas fa-map-marked-alt"></i> Job Location</h4>
                    <div class="map-container">
                        <div id="jobDetailsMap"></div>
                    </div>
                    <div class="map-coordinates">
                        <span id="jobDetailsCoordinates">Loading location...</span>
                    </div>
                    
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-file-alt"></i> Job Description</h4>
                    <div class="detail-content" id="modalDescription"></div>
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-list-check"></i> Requirements & Qualifications</h4>
                    <div class="detail-content" id="modalRequirements"></div>
                </div>

                <div class="detail-section">
                    <h4><i class="fas fa-gift"></i> Benefits & Perks</h4>
                    <div class="detail-content" id="modalBenefits"></div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="#" class="btn btn-primary" id="modalApplyBtn">
                    <i class="fas fa-paper-plane"></i> Apply Now
                </a>
                <a href="#" class="btn btn-warning" id="modalSaveBtn">
                    <i class="far fa-bookmark"></i> Save Job
                </a>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // Map variables
    let companyMap = null, jobDetailsMap = null;
    let companyMarker = null, jobDetailsMarker = null;
    let companyLat = 14.5995, companyLng = 120.9842;
    let jobDetailsLat = 14.5995, jobDetailsLng = 120.9842;

    // Initialize company map
    function initializeCompanyMap() {
        console.log('Initializing Company Map...');
        
        const mapContainer = document.getElementById('companyMap');
        if (!mapContainer) {
            console.error('Company map container not found');
            return;
        }

        // Clear any existing map
        if (companyMap) {
            companyMap.remove();
            companyMap = null;
        }

        // Wait for container to be visible
        setTimeout(() => {
            try {
                // Initialize map
                companyMap = L.map('companyMap', {
                    center: [companyLat, companyLng],
                    zoom: 13,
                    zoomControl: true,
                    dragging: true,
                    scrollWheelZoom: true
                });

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(companyMap);

                // Create marker
                companyMarker = L.marker([companyLat, companyLng], {
                    draggable: false
                }).addTo(companyMap);

                // Add popup with company name
                const companyName = '<?= addslashes($company["company_name"] ?? "Company") ?>';
                const companyAddress = '<?= addslashes($company["company_address"] ?? "Address not specified") ?>';
                companyMarker.bindPopup(`
                    <strong>${companyName}</strong><br>
                    ${companyAddress}
                `).openPopup();

                // Force resize after a short delay
                setTimeout(() => {
                    if (companyMap) {
                        companyMap.invalidateSize();
                        companyMap.setView([companyLat, companyLng], 13);
                        console.log('Company Map initialized successfully');
                    }
                }, 300);

            } catch (error) {
                console.error('Error initializing company map:', error);
            }
        }, 100);
        
        // Update coordinates display
        updateCompanyCoordinates(companyLat, companyLng);
    }

    // JOB DETAILS MAP FUNCTION - WITH GEOCODING
    function initializeJobDetailsMap(lat, lng, locationText) {
        console.log('Initializing Job Details Map with:', { lat, lng, locationText });
        
        const mapContainer = document.getElementById('jobDetailsMap');
        if (!mapContainer) {
            console.error('Job details map container not found');
            return;
        }

        // Clear any existing map
        if (jobDetailsMap) {
            jobDetailsMap.remove();
            jobDetailsMap = null;
        }

        // If coordinates are provided, use them directly
        if (lat && lng) {
            console.log('Using provided coordinates');
            jobDetailsLat = parseFloat(lat);
            jobDetailsLng = parseFloat(lng);
            createJobDetailsMap(jobDetailsLat, jobDetailsLng, locationText);
        } else if (locationText) {
            // If no coordinates but we have location text, geocode it
            console.log('No coordinates, geocoding location text:', locationText);
            geocodeJobLocation(locationText);
        } else {
            // Fallback to default coordinates
            console.log('No coordinates or location text, using defaults');
            createJobDetailsMap(jobDetailsLat, jobDetailsLng, 'Default Location');
        }
    }

    function createJobDetailsMap(lat, lng, locationText) {
        try {
            // Initialize map
            jobDetailsMap = L.map('jobDetailsMap', {
                center: [lat, lng],
                zoom: 13,
                zoomControl: true,
                dragging: true,
                scrollWheelZoom: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(jobDetailsMap);

            // Create marker
            jobDetailsMarker = L.marker([lat, lng], {
                draggable: false
            }).addTo(jobDetailsMap);

            // Add popup with job location
            jobDetailsMarker.bindPopup(`
                <strong>Job Location</strong><br>
                ${locationText || 'Location not specified'}
            `).openPopup();

            // Force resize after a short delay
            setTimeout(() => {
                if (jobDetailsMap) {
                    jobDetailsMap.invalidateSize();
                    jobDetailsMap.setView([lat, lng], 13);
                    console.log('Job Details Map created successfully');
                }
            }, 300);

        } catch (error) {
            console.error('Error creating job details map:', error);
        }
        
        // Update coordinates display
        updateJobDetailsCoordinates(lat, lng);
    }

    function geocodeJobLocation(locationText) {
        if (!locationText || locationText === 'Not specified') {
            // Use default coordinates if no valid location
            createJobDetailsMap(jobDetailsLat, jobDetailsLng, 'Default Location');
            return;
        }

        console.log('Geocoding location:', locationText);
        
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(locationText)}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    
                    console.log('Geocoded coordinates:', lat, lng);
                    createJobDetailsMap(lat, lng, locationText);
                } else {
                    console.log('No geocoding results found, using default coordinates');
                    createJobDetailsMap(jobDetailsLat, jobDetailsLng, locationText);
                }
            })
            .catch(error => {
                console.error('Error geocoding location:', error);
                // Fallback to default coordinates
                createJobDetailsMap(jobDetailsLat, jobDetailsLng, locationText);
            });
    }

    // Coordinate update functions
    function updateCompanyCoordinates(lat, lng) {
        companyLat = lat;
        companyLng = lng;
        $('#companyCoordinates').text(`Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`);
    }

    function updateJobDetailsCoordinates(lat, lng) {
        jobDetailsLat = lat;
        jobDetailsLng = lng;
        $('#jobDetailsCoordinates').text(`Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`);
    }

    // Geocode company address on page load
    function geocodeCompanyAddress() {
        const companyAddress = '<?= addslashes($company["company_address"] ?? "") ?>';
        if (!companyAddress) {
            console.log('No company address to geocode');
            return;
        }

        console.log('Geocoding company address:', companyAddress);
        
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(companyAddress)}&limit=1`)
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    const result = data[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    
                    console.log('Company geocoded coordinates:', lat, lng);
                    companyLat = lat;
                    companyLng = lng;
                    
                    // Reinitialize map with new coordinates
                    if (companyMap) {
                        companyMap.setView([lat, lng], 13);
                        companyMarker.setLatLng([lat, lng]);
                        updateCompanyCoordinates(lat, lng);
                        
                        // Update marker popup
                        const companyName = '<?= addslashes($company["company_name"] ?? "Company") ?>';
                        companyMarker.bindPopup(`
                            <strong>${companyName}</strong><br>
                            ${companyAddress}
                        `).openPopup();
                    }
                } else {
                    console.log('No geocoding results found for company address');
                }
            })
            .catch(error => {
                console.error('Error geocoding company address:', error);
            });
    }

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

        // Initialize company map if company address exists
        <?php if (!empty($company['company_address'])): ?>
        setTimeout(() => {
            initializeCompanyMap();
            // Try to geocode the company address for better accuracy
            setTimeout(geocodeCompanyAddress, 1000);
        }, 500);
        <?php endif; ?>

        // Job Details Modal Functionality
        const jobModal = $('#jobModal');
        
        // View job details modal - UPDATED TO INCLUDE MAP
        $('.view-job-details').on('click', function() {
            const jobId = $(this).data('job-id');
            const jobTitle = $(this).data('job-title');
            const jobCompany = $(this).data('job-company');
            const jobLocation = $(this).data('job-location');
            const jobType = $(this).data('job-type');
            const jobSalary = $(this).data('job-salary');
            const jobCategory = $(this).data('job-category');
            const jobDescription = $(this).data('job-description');
            const jobRequirements = $(this).data('job-requirements');
            const jobBenefits = $(this).data('job-benefits');
            const jobCreatedAt = $(this).data('job-created-at');
            
            // Populate modal with job data
            $('#modalJobTitle').text(jobTitle || 'No title');
            $('#modalCompany').text(jobCompany || 'Not specified');
            $('#modalLocation').text(jobLocation || 'Not specified');
            $('#modalJobType').text(jobType || 'Not specified');
            $('#modalSalary').text(jobSalary || 'Not specified');
            $('#modalCategory').text(jobCategory || 'Not specified');
            $('#modalDescription').html(formatText(jobDescription));
            $('#modalRequirements').html(formatText(jobRequirements));
            $('#modalBenefits').html(formatText(jobBenefits));
            
            // Format and display date
            if (jobCreatedAt) {
                $('#modalPostedDate').text(formatDate(jobCreatedAt));
            } else {
                $('#modalPostedDate').text('Date not available');
            }
            
            // Set modal action buttons
            $('#modalApplyBtn').attr('href', '/dashboard/apply_job?job_id=' + jobId);
            $('#modalSaveBtn').attr('href', '/dashboard/save_job?job_id=' + jobId);
            
            // Show modal
            jobModal.fadeIn(300);
            $('body').css('overflow', 'hidden');
            
            // Initialize map with job location
            setTimeout(() => {
                const jobLat = null; // No coordinates in job data, will use geocoding
                const jobLng = null;
                const locationText = jobLocation || 'Not specified';
                
                console.log('Job location data:', { jobLat, jobLng, locationText });
                initializeJobDetailsMap(jobLat, jobLng, locationText);
            }, 400);
        });

        // Common modal functionality - UPDATED TO INCLUDE MAP CLEANUP
        const closeModal = $('.close-modal');

        // Close modal
        closeModal.on('click', function() {
            // Remove job details map when modal closes
            if (jobDetailsMap) {
                jobDetailsMap.remove();
                jobDetailsMap = null;
            }
            
            $('.modal').fadeOut(300);
            $('body').css('overflow', 'auto');
        });

        // Close modal when clicking outside
        $('.modal').on('click', function(e) {
            if (e.target === this) {
                // Remove job details map when modal closes
                if (jobDetailsMap) {
                    jobDetailsMap.remove();
                    jobDetailsMap = null;
                }
                
                $(this).fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });

        // Close modal with Escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('.modal:visible').length) {
                // Remove job details map when modal closes
                if (jobDetailsMap) {
                    jobDetailsMap.remove();
                    jobDetailsMap = null;
                }
                
                $('.modal').fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });

        // Helper function to format text with line breaks
        function formatText(text) {
            if (!text || text.trim() === '') {
                return '<p class="text-muted">Not specified</p>';
            }
            const formattedText = text.replace(/\n/g, '<br>');
            return formattedText;
        }

        // Helper function to format date
        function formatDate(dateString) {
            try {
                const date = new Date(dateString);
                if (isNaN(date.getTime())) {
                    return 'Invalid Date';
                }
                return date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (e) {
                console.error('Error formatting date:', e);
                return 'Date not available';
            }
        }
    });

    // Debug function to check map status
    function debugMapStatus() {
        console.log('=== MAP DEBUG INFO ===');
        console.log('Company Map:', companyMap);
        console.log('Job Details Map:', jobDetailsMap);
        console.log('Company Container:', document.getElementById('companyMap'));
        console.log('Job Details Container:', document.getElementById('jobDetailsMap'));
        
        if (companyMap) {
            console.log('Company Map Center:', companyMap.getCenter());
            console.log('Company Map Zoom:', companyMap.getZoom());
        }
        if (jobDetailsMap) {
            console.log('Job Details Map Center:', jobDetailsMap.getCenter());
            console.log('Job Details Map Zoom:', jobDetailsMap.getZoom());
        }
        console.log('=== END DEBUG ===');
    }

    // Manual refresh for debugging
    function refreshMap(type) {
        if (type === 'company' && companyMap) {
            companyMap.invalidateSize(true);
            console.log('Company map refreshed');
        } else if (type === 'job' && jobDetailsMap) {
            jobDetailsMap.invalidateSize(true);
            console.log('Job details map refreshed');
        }
    }

    // Google Form Modal Functionality
$(document).ready(function() {
    const googleFormModal = $('#googleFormModal');
    const googleFormIframe = $('#googleFormIframe');
    const formLoading = $('#formLoading');
    const openInNewTab = $('#openInNewTab');
    
    // Show Google Form Modal
    $('#showContactForm').on('click', function() {
        const googleFormUrl = '<?= htmlspecialchars(addslashes($company['google_form_url'] ?? '')) ?>';
        
        if (!googleFormUrl) {
            alert('Contact form not available.');
            return;
        }
        
        // Show loading state
        formLoading.show();
        googleFormIframe.hide();
        
        // Set iframe source
        googleFormIframe.attr('src', googleFormUrl);
        openInNewTab.attr('href', googleFormUrl);
        
        // Show modal
        googleFormModal.fadeIn(300);
        $('body').css('overflow', 'hidden');
    });
    
    // Close modal functionality for Google Form modal
    googleFormModal.find('.close-modal').on('click', function() {
        closeGoogleFormModal();
    });
    
    // Close modal when clicking outside
    googleFormModal.on('click', function(e) {
        if (e.target === this) {
            closeGoogleFormModal();
        }
    });
    
    // Close with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && googleFormModal.is(':visible')) {
            closeGoogleFormModal();
        }
    });
    
    function closeGoogleFormModal() {
        googleFormModal.fadeOut(300);
        $('body').css('overflow', 'auto');
        
        // Reset iframe to stop any ongoing form submissions
        setTimeout(() => {
            googleFormIframe.attr('src', '');
        }, 300);
    }
    
    // Handle iframe loading errors
    googleFormIframe.on('error', function() {
        formLoading.html(`
            <div style="color: var(--danger);">
                <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 15px;"></i>
                <p>Failed to load contact form.</p>
                <p style="font-size: 0.9rem; margin-top: 10px;">Please try opening in a new tab instead.</p>
                <button onclick="window.open('<?= htmlspecialchars(addslashes($company['google_form_url'] ?? '')) ?>', '_blank')" 
                        class="btn btn-primary" style="margin-top: 15px;">
                    <i class="fas fa-external-link-alt"></i> Open in New Tab
                </button>
            </div>
        `);
    });
});
    </script>
</body>
</html>