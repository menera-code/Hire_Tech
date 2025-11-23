<?php
// Use session data directly if user data is not available
$user = $data['user'] ?? $_SESSION['user'] ?? [];
$user_role = $user['role'] ?? 'job_seeker';
$is_employer = ($user_role === 'employer');

// Debug output to check role detection
error_log("User role detected: " . $user_role);
error_log("Is employer: " . ($is_employer ? 'true' : 'false'));

// Set other variables with proper fallbacks
$jobs = $data['jobs'] ?? [];
$total_jobs = $data['total_jobs'] ?? count($jobs);
$current_page = $data['current_page'] ?? 'jobs';
$title = $data['title'] ?? 'Jobs - HireTech';
$saved_jobs = $data['saved_jobs'] ?? [];

// Get saved job IDs for job seekers - FIXED with proper validation
$saved_job_ids = [];
if (!$is_employer && !empty($saved_jobs)) {
    foreach ($saved_jobs as $saved_job) {
        if (isset($saved_job['job_id']) && !empty($saved_job['job_id'])) {
            $saved_job_ids[] = $saved_job['job_id'];
        }
    }
}

// Get applied job IDs for display
$applied_job_ids = $data['applied_job_ids'] ?? [];

// Get filter values from URL
$search_filter = $_GET['search'] ?? '';
$location_filter = $_GET['location'] ?? '';
$job_type_filter = $_GET['job_type'] ?? '';
$category_filter = $_GET['category'] ?? '';
$salary_range_filter = $_GET['salary_range'] ?? '';

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Helper function for time formatting
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    foreach ($string as $k => &$v) {
        if ($values[$k]) {
            $v = $values[$k] . ' ' . $v . ($values[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

// Helper function to format text with line breaks
function formatText($text) {
    if (empty($text)) return '<p class="text-muted">Not specified</p>';
    // Convert line breaks to HTML and preserve bullet points
    $formatted = nl2br(htmlspecialchars($text));
    return $formatted;
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

    /* Main content area */
    .main-content {
        margin-left: 280px;
        min-height: 100vh;
        background: #f5f7fb;
    }

    .dashboard-container {
        padding: 20px;
        max-width: 1400px;
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
        grid-template-columns: <?= $is_employer ? '1fr' : '1fr 3fr' ?>;
        gap: 25px;
        margin-bottom: 30px;
    }

    @media (max-width: 1024px) {
        .content-grid {
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

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--gray-700);
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid var(--gray-300);
        border-radius: 8px;
        font-size: 14px;
        transition: var(--transition);
        background: white;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
    }

    textarea.form-control {
        resize: vertical;
        min-height: 120px;
    }

    /* Job Cards */
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

    .job-header h3 {
        margin: 0;
        color: var(--dark);
        font-weight: 600;
        font-size: 1.2rem;
    }

    .company {
        font-weight: 600;
        color: var(--primary);
        margin: 0 0 5px 0;
    }

    .location, .job-date {
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
    }

    /* Job Meta */
    .job-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
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

    /* Action Buttons */
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

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background: #d13442;
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

    /* Status Badges */
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: capitalize;
    }

    .status-badge.active {
        background: rgba(76, 201, 240, 0.15);
        color: #0d6efd;
    }

    /* Filter Section */
    .filter-section {
        background: white;
        padding: 25px;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        position: sticky;
        top: 20px;
    }

    .filter-group {
        margin-bottom: 20px;
    }

    .filter-group:last-child {
        margin-bottom: 0;
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

    .job-details {
        margin-bottom: 25px;
    }

    .detail-section {
        margin-bottom: 20px;
    }

    .detail-section h4 {
        color: var(--primary);
        margin-bottom: 10px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .detail-section h4 i {
        font-size: 0.9rem;
    }

    .detail-content {
        background: var(--gray-100);
        padding: 15px;
        border-radius: 8px;
        border-left: 4px solid var(--primary);
        line-height: 1.6;
    }

    .job-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
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

    .modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid var(--gray-200);
    }

    .text-muted {
        color: var(--gray-500) !important;
        font-style: italic;
    }

    /* Filter Styles */
    .filter-active {
        background: var(--primary) !important;
        color: white !important;
    }
    
    .filter-badge {
        background: var(--primary);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        margin-left: 5px;
    }
    
    .active-filters {
        background: var(--gray-100);
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid var(--primary);
    }
    
    .active-filter-item {
        display: inline-flex;
        align-items: center;
        background: var(--primary);
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        margin: 5px;
    }
    
    .active-filter-item .remove-filter {
        background: none;
        border: none;
        color: white;
        margin-left: 8px;
        cursor: pointer;
        font-size: 0.9rem;
    }

    /* Post Job Button */
    .post-job-btn {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }
    
    .post-job-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(67, 97, 238, 0.4);
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    /* ========== MAP STYLES - FIXED ========== */
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
    #postJobMap, #editJobMap {
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

    .location-search {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
    }

    .location-search input {
        flex: 1;
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

    /* Delete confirmation */
    .delete-form {
        display: inline;
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
        }
        
        .job-actions {
            flex-direction: column;
        }
        
        .job-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .job-meta {
            flex-direction: column;
            gap: 8px;
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

        .form-grid {
            grid-template-columns: 1fr;
        }

        .map-container {
            height: 250px;
        }
        
        #postJobMap, #editJobMap {
            min-height: 250px;
        }
    }

    /* Debug styles - remove these after maps work */
    .map-container.debug {
        background: rgba(255, 0, 0, 0.1) !important;
        border: 2px dashed red !important;
    }
    
    #postJobMap.debug, #editJobMap.debug {
        background: rgba(0, 255, 0, 0.1) !important;
        border: 2px dashed green !important;
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

             
            <div class="form-group">
                <label class="form-label">Job Location</label>
                <div class="map-container">
                    <div id="jobDetailsMap"></div>
                </div>
                <div class="map-coordinates">
                    <span id="jobDetailsCoordinates">Latitude: 14.5995, Longitude: 120.9842</span>
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

 <!-- Post Job Modal -->
<div id="postJobModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Post a New Job</h3>
            <button class="close-modal">&times;</button>
        </div>
        
        <form method="POST" action="/dashboard/jobs" id="postJobForm">
            <input type="hidden" name="action" value="post_job">
            <input type="hidden" id="postJobLat" name="latitude" value="14.5995">
            <input type="hidden" id="postJobLng" name="longitude" value="120.9842">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="jobTitle" class="form-label">Job Title *</label>
                    <input type="text" id="jobTitle" name="title" required class="form-control" 
                           placeholder="e.g., Senior Web Developer">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Company *</label>
                    <?php
                    // Get company name - FIXED VERSION
                    $company_name = '';
                    $has_company_profile = false;
                    
                    // Check if company profile data is available from controller
                    if (isset($data['company_profile']) && !empty($data['company_profile']['company_name'])) {
                        $company_name = $data['company_profile']['company_name'];
                        $has_company_profile = true;
                    }
                    // Alternative: check if company data is directly available
                    elseif (isset($data['company']) && !empty($data['company'])) {
                        $company_name = $data['company'];
                        $has_company_profile = true;
                    }
                    // Final fallback: check session or user data
                    elseif (isset($user['company_name']) && !empty($user['company_name'])) {
                        $company_name = $user['company_name'];
                        $has_company_profile = true;
                    }
                    ?>
                    
                    <?php if ($has_company_profile): ?>
                        <input type="text" class="form-control" 
                               value="<?= htmlspecialchars($company_name) ?>" 
                               readonly style="background-color: #f8f9fa;">
                        <input type="hidden" name="company" value="<?= htmlspecialchars($company_name) ?>">
                        <small style="color: #28a745; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-check-circle"></i> 
                            Using company profile: <?= htmlspecialchars($company_name) ?>
                        </small>
                    <?php else: ?>
                        <!-- REMOVED MANUAL INPUT - ONLY SHOW ERROR MESSAGE -->
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="color: #721c24;"></i>
                            <strong style="color: #721c24;">Company Profile Required</strong>
                            <p style="margin: 5px 0 0 0; color: #721c24;">
                                You must set up your company profile before posting jobs.
                            </p>
                            <a href="/dashboard/company" class="btn btn-sm btn-danger mt-2">
                                <i class="fas fa-building"></i> Set Up Company Profile
                            </a>
                        </div>
                        <!-- Hidden field to prevent form validation errors -->
                        <input type="hidden" name="company" value="" required>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="location" class="form-label">Location *</label>
                <div class="location-search">
                    <input type="text" id="location" name="location" required class="form-control" 
                           placeholder="Search for location or click on the map...">
                    <button type="button" class="btn btn-secondary" onclick="useCurrentLocation('post')">
                        <i class="fas fa-location-arrow"></i>
                    </button>
                </div>
                
                <div class="map-instructions">
                    <i class="fas fa-info-circle"></i> Click on the map to set the exact location
                </div>
                
                <div class="map-container">
                    <div id="postJobMap"></div>
                </div>
                
                <div class="map-coordinates">
                    <span id="coordinatesDisplay">Latitude: 14.5995, Longitude: 120.9842</span>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="jobType" class="form-label">Job Type</label>
                    <select id="jobType" name="job_type" class="form-control">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Internship">Internship</option>
                        <option value="Remote">Remote</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="salary" class="form-label">Salary</label>
                    <input type="text" id="salary" name="salary" class="form-control" 
                           placeholder="e.g., ₱25,000 - ₱35,000">
                </div>
            </div>

            <div class="form-group">
                <label for="category" class="form-label">Job Category</label>
                <input type="text" id="category" name="category" class="form-control" 
                       placeholder="e.g., IT, Marketing, Sales, etc.">
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Job Description *</label>
                <textarea id="description" name="description" rows="5" required class="form-control" 
                          placeholder="Describe the role, responsibilities, and requirements..."></textarea>
            </div>

            <div class="form-group">
                <label for="requirements" class="form-label">Requirements & Qualifications</label>
                <textarea id="requirements" name="requirements" rows="4" class="form-control" 
                          placeholder="List the required skills, experience, and qualifications..."></textarea>
            </div>

            <div class="form-group">
                <label for="benefits" class="form-label">Benefits & Perks</label>
                <textarea id="benefits" name="benefits" rows="3" class="form-control" 
                          placeholder="List the benefits and perks offered..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="postJobSubmitBtn" <?= !$has_company_profile ? 'disabled' : '' ?>>
                    <i class="fas fa-plus"></i> Post Job
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Job Modal -->
<div id="editJobModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Job</h3>
            <button class="close-modal">&times;</button>
        </div>
        
        <form method="POST" action="/dashboard/jobs" id="editJobForm">
            <input type="hidden" name="action" value="edit_job">
            <input type="hidden" name="job_id" id="editJobId">
            <input type="hidden" id="editJobLat" name="latitude" value="14.5995">
            <input type="hidden" id="editJobLng" name="longitude" value="120.9842">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="editJobTitle" class="form-label">Job Title *</label>
                    <input type="text" id="editJobTitle" name="title" required class="form-control" 
                           placeholder="e.g., Senior Web Developer">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Company *</label>
                    <?php
                    // Get company name for edit form - FIXED VERSION
                    $edit_company_name = '';
                    $edit_has_company_profile = false;
                    
                    if (isset($data['company_profile']) && !empty($data['company_profile']['company_name'])) {
                        $edit_company_name = $data['company_profile']['company_name'];
                        $edit_has_company_profile = true;
                    }
                    elseif (isset($data['company']) && !empty($data['company'])) {
                        $edit_company_name = $data['company'];
                        $edit_has_company_profile = true;
                    }
                    elseif (isset($user['company_name']) && !empty($user['company_name'])) {
                        $edit_company_name = $user['company_name'];
                        $edit_has_company_profile = true;
                    }
                    ?>
                    
                    <?php if ($edit_has_company_profile): ?>
                        <input type="text" class="form-control" id="editCompanyDisplay" 
                               value="<?= htmlspecialchars($edit_company_name) ?>" 
                               readonly style="background-color: #f8f9fa;">
                        <input type="hidden" name="company" id="editCompanyInput" value="<?= htmlspecialchars($edit_company_name) ?>">
                        <small style="color: #28a745; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-check-circle"></i> 
                            Using company profile: <?= htmlspecialchars($edit_company_name) ?>
                        </small>
                    <?php else: ?>
                        <!-- REMOVED MANUAL INPUT - ONLY SHOW ERROR MESSAGE -->
                        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="color: #721c24;"></i>
                            <strong style="color: #721c24;">Company Profile Required</strong>
                            <p style="margin: 5px 0 0 0; color: #721c24;">
                                You must set up your company profile before editing jobs.
                            </p>
                            <a href="/dashboard/company" class="btn btn-sm btn-danger mt-2">
                                <i class="fas fa-building"></i> Set Up Company Profile
                            </a>
                        </div>
                        <!-- Hidden field to prevent form validation errors -->
                        <input type="hidden" name="company" value="" required>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label for="editLocation" class="form-label">Location *</label>
                <div class="location-search">
                    <input type="text" id="editLocation" name="location" required class="form-control" 
                           placeholder="Search for location or click on the map...">
                    <button type="button" class="btn btn-secondary" onclick="useCurrentLocation('edit')">
                        <i class="fas fa-location-arrow"></i>
                    </button>
                </div>
                
                <div class="map-instructions">
                    <i class="fas fa-info-circle"></i> Click on the map to set the exact location
                </div>
                
                <div class="map-container">
                    <div id="editJobMap"></div>
                </div>
                
                <div class="map-coordinates">
                    <span id="editCoordinatesDisplay">Latitude: 14.5995, Longitude: 120.9842</span>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="editJobType" class="form-label">Job Type</label>
                    <select id="editJobType" name="job_type" class="form-control">
                        <option value="Full-time">Full-time</option>
                        <option value="Part-time">Part-time</option>
                        <option value="Contract">Contract</option>
                        <option value="Internship">Internship</option>
                        <option value="Remote">Remote</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editSalary" class="form-label">Salary</label>
                    <input type="text" id="editSalary" name="salary" class="form-control" 
                           placeholder="e.g., ₱25,000 - ₱35,000">
                </div>
            </div>

            <div class="form-group">
                <label for="editCategory" class="form-label">Job Category</label>
                <input type="text" id="editCategory" name="category" class="form-control" 
                       placeholder="e.g., IT, Marketing, Sales, etc.">
            </div>
            
            <div class="form-group">
                <label for="editDescription" class="form-label">Job Description *</label>
                <textarea id="editDescription" name="description" rows="5" required class="form-control" 
                          placeholder="Describe the role, responsibilities, and requirements..."></textarea>
            </div>

            <div class="form-group">
                <label for="editRequirements" class="form-label">Requirements & Qualifications</label>
                <textarea id="editRequirements" name="requirements" rows="4" class="form-control" 
                          placeholder="List the required skills, experience, and qualifications..."></textarea>
            </div>

            <div class="form-group">
                <label for="editBenefits" class="form-label">Benefits & Perks</label>
                <textarea id="editBenefits" name="benefits" rows="3" class="form-control" 
                          placeholder="List the benefits and perks offered..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary close-modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary" id="editJobSubmitBtn" <?= !$edit_has_company_profile ? 'disabled' : '' ?>>
                    <i class="fas fa-save"></i> Update Job
                </button>
            </div>
        </form>
    </div>
</div>

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

            <!-- Header Section -->
            <div class="header-section">
                <h1><?= $is_employer ? 'Manage Your Job Posts' : 'Browse Jobs' ?></h1>
                <p class="subtitle">
                    <?= $is_employer ? 'Create and manage your job listings' : 'Find your next career opportunity' ?>
                </p>
            </div>

            <div class="content-grid">
                <?php if ($is_employer): ?>
                    <!-- Employer View -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Your Job Posts (<?= $total_jobs ?>)</h2>
                            <button class="post-job-btn" id="openPostJobModal">
                                <i class="fas fa-plus"></i> Post New Job
                            </button>
                        </div>
                        
                        <div id="employerJobsList">
                            <?php if (!empty($jobs)): ?>
                                <div class="jobs-list">
                                    <?php foreach ($jobs as $job): ?>
                                    <div class="job-card" data-job-id="<?= $job['id'] ?>">
                                        <div class="job-header">
                                            <h3><?= htmlspecialchars($job['title']) ?></h3>
                                            <span class="status-badge active">Active</span>
                                        </div>
                                        <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                                        <div class="job-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($job['location']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-briefcase"></i>
                                                <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>
                                            </span>
                                            <?php if (!empty($job['salary'])): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <?= htmlspecialchars($job['salary']) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($job['category'])): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-tag"></i>
                                                <?= htmlspecialchars($job['category']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="description"><?= htmlspecialchars($job['description']) ?></p>
                                        <p class="job-date">
                                            <i class="far fa-clock"></i>
                                            Posted on <?= date('M j, Y g:i A', strtotime($job['created_at'])) ?>
                                            <?php if ($job['updated_at'] != $job['created_at']): ?>
                                                <br><small>Updated: <?= date('M j, Y g:i A', strtotime($job['updated_at'])) ?></small>
                                            <?php endif; ?>
                                        </p>
                                        <div class="job-actions">
                                            <!-- Edit Button -->
                                            <button class="btn btn-primary btn-sm edit-job-btn" 
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
                                                    data-job-latitude="<?= htmlspecialchars($job['latitude'] ?? '') ?>"
                                                    data-job-longitude="<?= htmlspecialchars($job['longitude'] ?? '') ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            
                                            <!-- Delete Form -->
                                            <form method="POST" action="/dashboard/jobs" class="delete-form" 
                                                  onsubmit="return confirm('Are you sure you want to delete \"<?= addslashes($job['title']) ?>\"? This action cannot be undone.')" 
                                                  style="display: inline;">
                                                <input type="hidden" name="action" value="delete_job">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                            
                                            <!-- View Applications Button -->
                                            <a href="/application" class="btn btn-success btn-sm">
                                                <i class="fas fa-users"></i> View Applications
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                                    <p>No jobs posted yet. Create your first job posting!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Job Seeker View -->
                    <!-- Filters Sidebar -->
                    <div class="filter-section">
                        <div class="section-header">
                            <h2>Filters 
                                <?php if ($search_filter || $location_filter || $job_type_filter || $category_filter || $salary_range_filter): ?>
                                    <span class="filter-badge">Active</span>
                                <?php endif; ?>
                            </h2>
                        </div>
                        
                        <!-- Active Filters Display -->
                        <?php if ($search_filter || $location_filter || $job_type_filter || $category_filter || $salary_range_filter): ?>
                        <div class="active-filters">
                            <strong>Active Filters:</strong>
                            <?php if ($search_filter): ?>
                                <span class="active-filter-item">
                                    Search: "<?= htmlspecialchars($search_filter) ?>"
                                    <button type="button" class="remove-filter" data-filter="search">×</button>
                                </span>
                            <?php endif; ?>
                            <?php if ($location_filter): ?>
                                <span class="active-filter-item">
                                    Location: "<?= htmlspecialchars($location_filter) ?>"
                                    <button type="button" class="remove-filter" data-filter="location">×</button>
                                </span>
                            <?php endif; ?>
                            <?php if ($job_type_filter): ?>
                                <span class="active-filter-item">
                                    Type: <?= htmlspecialchars($job_type_filter) ?>
                                    <button type="button" class="remove-filter" data-filter="job_type">×</button>
                                </span>
                            <?php endif; ?>
                            <?php if ($category_filter): ?>
                                <span class="active-filter-item">
                                    Category: "<?= htmlspecialchars($category_filter) ?>"
                                    <button type="button" class="remove-filter" data-filter="category">×</button>
                                </span>
                            <?php endif; ?>
                            <?php if ($salary_range_filter): ?>
                                <span class="active-filter-item">
                                    Salary: <?= htmlspecialchars($salary_range_filter) ?>
                                    <button type="button" class="remove-filter" data-filter="salary_range">×</button>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="GET" action="/dashboard/jobs" id="filterForm">
                            <div class="filter-group">
                                <label for="searchInput" class="form-label">Search Jobs</label>
                                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Job title, company, keywords..." value="<?= htmlspecialchars($search_filter) ?>">
                            </div>
                            <div class="filter-group">
                                <label for="locationFilter" class="form-label">Location</label>
                                <input type="text" id="locationFilter" name="location" class="form-control" placeholder="City, province, or remote..." value="<?= htmlspecialchars($location_filter) ?>">
                            </div>
                            <div class="filter-group">
                                <label for="jobTypeFilter" class="form-label">Job Type</label>
                                <select id="jobTypeFilter" name="job_type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="Full-time" <?= $job_type_filter == 'Full-time' ? 'selected' : '' ?>>Full-time</option>
                                    <option value="Part-time" <?= $job_type_filter == 'Part-time' ? 'selected' : '' ?>>Part-time</option>
                                    <option value="Contract" <?= $job_type_filter == 'Contract' ? 'selected' : '' ?>>Contract</option>
                                    <option value="Internship" <?= $job_type_filter == 'Internship' ? 'selected' : '' ?>>Internship</option>
                                    <option value="Remote" <?= $job_type_filter == 'Remote' ? 'selected' : '' ?>>Remote</option>
                                </select>
                            </div>
                            <div class="filter-group">
                                <label for="categoryFilter" class="form-label">Category</label>
                                <input type="text" id="categoryFilter" name="category" class="form-control" placeholder="e.g., IT, Marketing, Sales..." value="<?= htmlspecialchars($category_filter) ?>">
                            </div>
                            <div class="filter-group">
                                <label for="salaryFilter" class="form-label">Salary Range</label>
                                <select id="salaryFilter" name="salary_range" class="form-control">
                                    <option value="">All Ranges</option>
                                    <option value="0-20000" <?= $salary_range_filter == '0-20000' ? 'selected' : '' ?>>Under ₱20,000</option>
                                    <option value="20000-40000" <?= $salary_range_filter == '20000-40000' ? 'selected' : '' ?>>₱20,000 - ₱40,000</option>
                                    <option value="40000-60000" <?= $salary_range_filter == '40000-60000' ? 'selected' : '' ?>>₱40,000 - ₱60,000</option>
                                    <option value="60000-0" <?= $salary_range_filter == '60000-0' ? 'selected' : '' ?>>Over ₱60,000</option>
                                </select>
                            </div>
                            <div class="job-actions">
                                <a href="/dashboard/jobs" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                    <i class="fas fa-times"></i> Clear All Filters
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Job Listings -->
                    <div class="section">
                        <div class="section-header">
                            <h2>Available Jobs (<?= count($jobs) ?>)</h2>
                            <span style="color: var(--gray-600); font-size: 0.9rem;" id="jobCount">
                                <?= count($jobs) ?> job<?= count($jobs) !== 1 ? 's' : '' ?> found
                                <?php if ($search_filter || $location_filter || $job_type_filter || $category_filter || $salary_range_filter): ?>
                                    <span style="color: var(--primary);">• Filtered</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div id="seekerJobsList" style="max-height: 600px; overflow-y: auto; padding-right: 10px;">
                            <?php if (!empty($jobs)): ?>
                                <div class="jobs-list">
                                    <?php foreach ($jobs as $job): ?>
                                    <div class="job-card" data-job-id="<?= $job['id'] ?>">
                                        <div class="job-header">
                                            <h3><?= htmlspecialchars($job['title']) ?></h3>
                                            <span class="status-badge active">Active</span>
                                        </div>
                                        <p class="company"><?= htmlspecialchars($job['company']) ?></p>
                                        <div class="job-meta">
                                            <span class="meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= htmlspecialchars($job['location']) ?>
                                            </span>
                                            <span class="meta-item">
                                                <i class="fas fa-briefcase"></i>
                                                <?= htmlspecialchars($job['job_type'] ?? 'Full-time') ?>
                                            </span>
                                            <?php if (!empty($job['salary'])): ?>
                                            <span class="meta-item" style="color: var(--success); font-weight: 600;">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <?= htmlspecialchars($job['salary']) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if (!empty($job['category'])): ?>
                                            <span class="meta-item">
                                                <i class="fas fa-tag"></i>
                                                <?= htmlspecialchars($job['category']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="description"><?= htmlspecialchars($job['description']) ?></p>
                                        <p class="job-date">
                                            <i class="far fa-clock"></i>
                                            Posted <?= time_elapsed_string($job['created_at']) ?>
                                        </p>
                                        <div class="job-actions">
                                            <?php if (in_array($job['id'], $applied_job_ids)): ?>
                                                <button class="btn btn-success" disabled>
                                                    <i class="fas fa-check-circle"></i> Applied
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary view-job-details" 
                                                        data-job-id="<?= $job['id'] ?>" 
                                                        data-job-data="<?= htmlspecialchars(json_encode($job), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fas fa-eye"></i> View Details
                                                </button>
                                            <?php endif; ?>
                                            
                                            
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-briefcase" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                                    <p>
                                        <?php if ($search_filter || $location_filter || $job_type_filter || $category_filter || $salary_range_filter): ?>
                                            No jobs found matching your filters. 
                                            <a href="/dashboard/jobs" style="color: var(--primary); text-decoration: underline;">Clear filters</a> to see all jobs.
                                        <?php else: ?>
                                            No jobs available at the moment. Check back later!
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Map variables
let postJobMap = null, editJobMap = null, jobDetailsMap = null;
let postJobMarker = null, editJobMarker = null, jobDetailsMarker = null;
let postJobLat = 14.5995, postJobLng = 120.9842;
let editJobLat = 14.5995, editJobLng = 120.9842;
let jobDetailsLat = 14.5995, jobDetailsLng = 120.9842;

// Improved map initialization functions
function initializePostJobMap() {
    console.log('Initializing Post Job Map...');
    
    const mapContainer = document.getElementById('postJobMap');
    if (!mapContainer) {
        console.error('Post job map container not found');
        return;
    }

    // Clear any existing map
    if (postJobMap) {
        postJobMap.remove();
        postJobMap = null;
    }

    // Wait for container to be visible
    setTimeout(() => {
        try {
            // Initialize map
            postJobMap = L.map('postJobMap', {
                center: [postJobLat, postJobLng],
                zoom: 13,
                zoomControl: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(postJobMap);

            // Create marker
            postJobMarker = L.marker([postJobLat, postJobLng], {
                draggable: true
            }).addTo(postJobMap);

            // Marker drag event
            postJobMarker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updatePostJobCoordinates(position.lat, position.lng);
                reverseGeocode(position.lat, position.lng, 'post');
            });

            // Map click event
            postJobMap.on('click', function(e) {
                postJobMarker.setLatLng(e.latlng);
                updatePostJobCoordinates(e.latlng.lat, e.latlng.lng);
                reverseGeocode(e.latlng.lat, e.latlng.lng, 'post');
            });

            // Force resize after a short delay
            setTimeout(() => {
                if (postJobMap) {
                    postJobMap.invalidateSize();
                    postJobMap.setView([postJobLat, postJobLng], 13);
                    console.log('Post Job Map initialized successfully');
                }
            }, 300);

        } catch (error) {
            console.error('Error initializing post job map:', error);
        }
    }, 100);
    
    // Initialize coordinates display
    updatePostJobCoordinates(postJobLat, postJobLng);
}

function initializeEditJobMap() {
    console.log('Initializing Edit Job Map...');
    
    const mapContainer = document.getElementById('editJobMap');
    if (!mapContainer) {
        console.error('Edit job map container not found');
        return;
    }

    // Clear any existing map
    if (editJobMap) {
        editJobMap.remove();
        editJobMap = null;
    }

    // Wait for container to be visible
    setTimeout(() => {
        try {
            // Initialize map
            editJobMap = L.map('editJobMap', {
                center: [editJobLat, editJobLng],
                zoom: 13,
                zoomControl: true
            });

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(editJobMap);

            // Create marker
            editJobMarker = L.marker([editJobLat, editJobLng], {
                draggable: true
            }).addTo(editJobMap);

            // Marker drag event
            editJobMarker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updateEditJobCoordinates(position.lat, position.lng);
                reverseGeocode(position.lat, position.lng, 'edit');
            });

            // Map click event
            editJobMap.on('click', function(e) {
                editJobMarker.setLatLng(e.latlng);
                updateEditJobCoordinates(e.latlng.lat, e.latlng.lng);
                reverseGeocode(e.latlng.lat, e.latlng.lng, 'edit');
            });

            // Force resize after a short delay
            setTimeout(() => {
                if (editJobMap) {
                    editJobMap.invalidateSize();
                    editJobMap.setView([editJobLat, editJobLng], 13);
                    console.log('Edit Job Map initialized successfully');
                }
            }, 300);

        } catch (error) {
            console.error('Error initializing edit job map:', error);
        }
    }, 100);
    
    // Initialize coordinates display
    updateEditJobCoordinates(editJobLat, editJobLng);
}

// JOB DETAILS MAP FUNCTION - WITH GEOCODING
function initializeJobDetailsMap(lat, lng, locationText) {
    console.log('Initializing Job Details Map...');
    
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
        jobDetailsLat = parseFloat(lat);
        jobDetailsLng = parseFloat(lng);
        createJobDetailsMap(jobDetailsLat, jobDetailsLng, locationText);
    } else if (locationText) {
        // If no coordinates but we have location text, geocode it
        geocodeJobLocation(locationText);
    } else {
        // Fallback to default coordinates
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
function updatePostJobCoordinates(lat, lng) {
    postJobLat = lat;
    postJobLng = lng;
    $('#postJobLat').val(lat);
    $('#postJobLng').val(lng);
    $('#coordinatesDisplay').text(`Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`);
}

function updateEditJobCoordinates(lat, lng) {
    editJobLat = lat;
    editJobLng = lng;
    $('#editJobLat').val(lat);
    $('#editJobLng').val(lng);
    $('#editCoordinatesDisplay').text(`Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`);
}

// ADD JOB DETAILS COORDINATES FUNCTION
function updateJobDetailsCoordinates(lat, lng) {
    jobDetailsLat = lat;
    jobDetailsLng = lng;
    $('#jobDetailsCoordinates').text(`Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`);
}

// Location search function
function searchLocation(query, type = 'post') {
    if (!query || query.length < 3) return;
    
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const result = data[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);
                
                if (type === 'post' && postJobMap) {
                    postJobMap.setView([lat, lng], 15);
                    postJobMarker.setLatLng([lat, lng]);
                    updatePostJobCoordinates(lat, lng);
                    $('#location').val(result.display_name);
                } else if (type === 'edit' && editJobMap) {
                    editJobMap.setView([lat, lng], 15);
                    editJobMarker.setLatLng([lat, lng]);
                    updateEditJobCoordinates(lat, lng);
                    $('#editLocation').val(result.display_name);
                }
            }
        })
        .catch(error => console.error('Error searching location:', error));
}

// Current location function
function useCurrentLocation(type) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser');
        return;
    }

    // Show loading state
    const button = type === 'post' ? 
        $('.location-search .btn-secondary').first() : 
        $('.location-search .btn-secondary').last();
    const originalHtml = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i>');
    button.prop('disabled', true);

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            if (type === 'post' && postJobMap) {
                postJobMap.setView([lat, lng], 15);
                postJobMarker.setLatLng([lat, lng]);
                updatePostJobCoordinates(lat, lng);
                reverseGeocode(lat, lng, 'post');
            } else if (type === 'edit' && editJobMap) {
                editJobMap.setView([lat, lng], 15);
                editJobMarker.setLatLng([lat, lng]);
                updateEditJobCoordinates(lat, lng);
                reverseGeocode(lat, lng, 'edit');
            }
            
            // Restore button
            button.html('<i class="fas fa-location-arrow"></i>');
            button.prop('disabled', false);
        },
        function(error) {
            let errorMessage = 'Unable to retrieve your location: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage += 'Permission denied.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage += 'Position unavailable.';
                    break;
                case error.TIMEOUT:
                    errorMessage += 'Request timeout.';
                    break;
                default:
                    errorMessage += 'Unknown error.';
                    break;
            }
            alert(errorMessage);
            
            // Restore button
            button.html('<i class="fas fa-location-arrow"></i>');
            button.prop('disabled', false);
        },
        {
            timeout: 10000,
            enableHighAccuracy: true
        }
    );
}

// Reverse geocoding function
function reverseGeocode(lat, lng, type) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                if (type === 'post') {
                    $('#location').val(data.display_name);
                } else {
                    $('#editLocation').val(data.display_name);
                }
            }
        })
        .catch(error => console.error('Error reverse geocoding:', error));
}

// Debounce utility function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Manual refresh for debugging
function refreshMap(type) {
    if (type === 'post' && postJobMap) {
        postJobMap.invalidateSize(true);
        console.log('Post job map refreshed');
    } else if (type === 'edit' && editJobMap) {
        editJobMap.invalidateSize(true);
        console.log('Edit job map refreshed');
    } else if (type === 'details' && jobDetailsMap) {
        jobDetailsMap.invalidateSize(true);
        console.log('Job details map refreshed');
    }
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

    <?php if ($is_employer): ?>
    // Employer-specific functionality
    const postJobModal = $('#postJobModal');
    const editJobModal = $('#editJobModal');

    // Open post job modal
    $('#openPostJobModal').on('click', function() {
        postJobModal.fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Initialize map after modal is fully shown
        setTimeout(() => {
            initializePostJobMap();
        }, 400);
    });

    // Open edit job modal
    $('.edit-job-btn').on('click', function() {
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
        const jobLat = $(this).data('job-latitude');
        const jobLng = $(this).data('job-longitude');
        
        // Populate edit form
        $('#editJobId').val(jobId);
        $('#editJobTitle').val(jobTitle);
        $('#editCompanyInput').val(jobCompany);
        $('#editLocation').val(jobLocation);
        $('#editJobType').val(jobType);
        $('#editSalary').val(jobSalary);
        $('#editCategory').val(jobCategory);
        $('#editDescription').val(jobDescription);
        $('#editRequirements').val(jobRequirements);
        $('#editBenefits').val(jobBenefits);
        
        // Set coordinates if available
        if (jobLat && jobLng) {
            editJobLat = parseFloat(jobLat);
            editJobLng = parseFloat(jobLng);
        }
        
        editJobModal.fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Initialize map after modal is fully shown
        setTimeout(() => {
            initializeEditJobMap();
        }, 400);
    });
    <?php else: ?>
    // Job seeker-specific functionality
    // Auto-submit functionality
    let searchTimeout;
    const searchInput = $('#searchInput');
    const locationInput = $('#locationFilter');
    const categoryInput = $('#categoryFilter');
    const jobTypeSelect = $('#jobTypeFilter');
    const salarySelect = $('#salaryFilter');

    // Function to submit form
    function submitForm() {
        $('#filterForm').submit();
    }

    // Debounced submit for text inputs
    function setupAutoSubmit(inputElement) {
        inputElement.on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(submitForm, 800);
        });
    }

    // Immediate submit for selects
    function setupInstantSubmit(selectElement) {
        selectElement.on('change', function() {
            clearTimeout(searchTimeout);
            submitForm();
        });
    }

    // Set up auto-submit for all filter inputs
    setupAutoSubmit(searchInput);
    setupAutoSubmit(locationInput);
    setupAutoSubmit(categoryInput);
    setupInstantSubmit(jobTypeSelect);
    setupInstantSubmit(salarySelect);

    // Remove individual filter
    $('.remove-filter').on('click', function() {
        const filterName = $(this).data('filter');
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete(filterName);
        window.location.href = '/dashboard/jobs?' + urlParams.toString();
    });

    // Modal functionality for job seekers
    const jobModal = $('#jobModal');

// View job details modal - UPDATED TO INCLUDE MAP
$('.view-job-details').on('click', function() {
    const jobDataStr = $(this).data('job-data');
    const jobId = $(this).data('job-id');
    
    let jobData;
    try {
        if (typeof jobDataStr === 'string') {
            jobData = JSON.parse(jobDataStr);
        } else {
            jobData = jobDataStr;
        }
    } catch (e) {
        console.error('Error parsing job data:', e);
        jobData = {};
    }
    
    // Populate modal with job data
    $('#modalJobTitle').text(jobData.title || 'No title');
    $('#modalCompany').text(jobData.company || 'Not specified');
    $('#modalLocation').text(jobData.location || 'Not specified');
    $('#modalJobType').text(jobData.job_type || 'Not specified');
    $('#modalSalary').text(jobData.salary || 'Not specified');
    $('#modalCategory').text(jobData.category || 'Not specified');
    $('#modalDescription').html(formatText(jobData.description));
    $('#modalRequirements').html(formatText(jobData.requirements));
    $('#modalBenefits').html(formatText(jobData.benefits));
    
    if (jobData.created_at) {
        $('#modalPostedDate').text(formatDate(jobData.created_at));
    } else {
        $('#modalPostedDate').text('Date not available');
    }
    
    // Set modal action buttons
    $('#modalApplyBtn').attr('href', '/dashboard/apply_job?job_id=' + jobId);
    $('#modalSaveBtn').attr('href', '/dashboard/save_job?job_id=' + jobId);
    
    // Update save button state
    const isSaved = <?= json_encode($saved_job_ids) ?>.includes(parseInt(jobId));
    const saveBtn = $('#modalSaveBtn');
    if (isSaved) {
        saveBtn.removeClass('btn-secondary').addClass('btn-warning');
        saveBtn.html('<i class="fas fa-bookmark"></i> Remove Saved');
    } else {
        saveBtn.removeClass('btn-warning').addClass('btn-secondary');
        saveBtn.html('<i class="far fa-bookmark"></i> Save Job');
    }
    
    // Show modal
    jobModal.fadeIn(300);
    $('body').css('overflow', 'hidden');
    
    // Initialize map with job coordinates (if available) - FIXED LINE
    setTimeout(() => {
        const jobLat = jobData.latitude;
        const jobLng = jobData.longitude;
        const locationText = jobData.location || 'Not specified';
        
        console.log('Job location data:', { jobLat, jobLng, locationText });
       initializeJobDetailsMap(jobLat, jobLng, jobData.location || 'Not specified');
    }, 400);
});
    <?php endif; ?>

    // Common modal functionality - UPDATED TO INCLUDE JOB DETAILS MAP
    const closeModal = $('.close-modal');

    // Close modal
    closeModal.on('click', function() {
        // Remove maps when modals close to prevent conflicts
        if (postJobMap) {
            postJobMap.remove();
            postJobMap = null;
        }
        if (editJobMap) {
            editJobMap.remove();
            editJobMap = null;
        }
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
            // Remove maps when modals close to prevent conflicts
            if (postJobMap) {
                postJobMap.remove();
                postJobMap = null;
            }
            if (editJobMap) {
                editJobMap.remove();
                editJobMap = null;
            }
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
            // Remove maps when modals close to prevent conflicts
            if (postJobMap) {
                postJobMap.remove();
                postJobMap = null;
            }
            if (editJobMap) {
                editJobMap.remove();
                editJobMap = null;
            }
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

    // Add debounced event listeners for location search
    $('#location').on('input', debounce(function() {
        searchLocation($(this).val(), 'post');
    }, 500));
    
    $('#editLocation').on('input', debounce(function() {
        searchLocation($(this).val(), 'edit');
    }, 500));

    // Prevent form submission when no company profile exists
    $('#postJobForm, #editJobForm').on('submit', function(e) {
        const companyInput = $(this).find('input[name="company"]');
        const submitBtn = $(this).find('button[type="submit"]');
        
        // Check if company input is empty or the submit button is disabled
        if (!companyInput.val() || companyInput.val().trim() === '' || submitBtn.is(':disabled')) {
            e.preventDefault();
            alert('Please set up your company profile first before posting or editing jobs.');
            return false;
        }
    });

    // Disable/enable submit buttons based on company profile status
    function updateSubmitButtons() {
        const hasCompanyProfile = <?= $has_company_profile ? 'true' : 'false' ?>;
        const hasEditCompanyProfile = <?= $edit_has_company_profile ? 'true' : 'false' ?>;
        
        if (!hasCompanyProfile) {
            $('#postJobSubmitBtn').prop('disabled', true);
        }
        if (!hasEditCompanyProfile) {
            $('#editJobSubmitBtn').prop('disabled', true);
        }
    }
    
    // Run on page load
    updateSubmitButtons();
});

// Debug function to check map status
function debugMapStatus() {
    console.log('=== MAP DEBUG INFO ===');
    console.log('Post Job Map:', postJobMap);
    console.log('Edit Job Map:', editJobMap);
    console.log('Job Details Map:', jobDetailsMap);
    console.log('Post Job Container:', document.getElementById('postJobMap'));
    console.log('Edit Job Container:', document.getElementById('editJobMap'));
    console.log('Job Details Container:', document.getElementById('jobDetailsMap'));
    
    if (postJobMap) {
        console.log('Post Map Center:', postJobMap.getCenter());
        console.log('Post Map Zoom:', postJobMap.getZoom());
    }
    if (editJobMap) {
        console.log('Edit Map Center:', editJobMap.getCenter());
        console.log('Edit Map Zoom:', editJobMap.getZoom());
    }
    if (jobDetailsMap) {
        console.log('Job Details Map Center:', jobDetailsMap.getCenter());
        console.log('Job Details Map Zoom:', jobDetailsMap.getZoom());
    }
    console.log('=== END DEBUG ===');
}
</script>
</body>
</html>