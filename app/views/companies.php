<?php
// Use session data directly if user data is not available
$user = $data['user'] ?? $_SESSION['user'] ?? [];
$user_role = $user['role'] ?? 'job_seeker';
$is_employer = ($user_role === 'employer');

// Set variables with proper fallbacks
$companies = $data['companies'] ?? [];
$total_companies = $data['total_companies'] ?? count($companies);
$current_page = $data['current_page'] ?? 'companies';
$title = $data['title'] ?? 'Companies - HireTech';

// Get filter values from URL
$search_filter = $_GET['search'] ?? '';
$industry_filter = $_GET['industry'] ?? '';
$company_size_filter = $_GET['company_size'] ?? '';
$location_filter = $_GET['location'] ?? '';

// Get available filters
$industries = $data['industries'] ?? [];
$company_sizes = $data['company_sizes'] ?? [];

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

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );

    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
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
        /* Reuse the same CSS styles from jobs.php */
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
            grid-template-columns: 1fr 3fr;
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

        /* Company Cards */
        .companies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .company-card {
            border: 1px solid var(--gray-200);
            padding: 25px;
            border-radius: 10px;
            transition: var(--transition);
            background: white;
            position: relative;
        }

        .company-card:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .company-header {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
        }

        .company-logo {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid var(--gray-200);
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            border-radius: 8px;
            object-fit: cover;
        }

        .company-info {
            flex: 1;
        }

        .company-info h3 {
            margin: 0 0 5px 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .company-industry {
            color: var(--primary);
            font-weight: 500;
            margin: 0 0 8px 0;
            font-size: 0.9rem;
        }

        .company-description {
            color: var(--gray-700);
            margin: 0 0 15px 0;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Company Meta */
        .company-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--gray-200);
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

        .jobs-count {
            background: var(--primary);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .company-actions {
            display: flex;
            gap: 10px;
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

        /* No Data State */
        .no-data {
            text-align: center;
            color: var(--gray-500);
            font-style: italic;
            padding: 40px;
            background: var(--gray-100);
            border-radius: 8px;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 6px;
            text-decoration: none;
            color: var(--gray-700);
            transition: var(--transition);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .current {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
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

        /* ========== MAP STYLES ========== */
        .map-container {
            height: 200px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            margin-bottom: 15px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
        }

        /* Map containers MUST have explicit dimensions */
        #companiesMap, .company-mini-map {
            height: 100% !important;
            width: 100% !important;
            min-height: 200px;
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
            padding: 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            color: var(--gray-600);
            margin-bottom: 10px;
        }

        .map-instructions {
            font-size: 0.7rem;
            color: var(--gray-500);
            margin-bottom: 10px;
            font-style: italic;
        }

        /* Map View Toggle */
        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: var(--gray-100);
            padding: 10px;
            border-radius: 8px;
        }

        .view-toggle-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--gray-300);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
            text-align: center;
            font-weight: 500;
        }

        .view-toggle-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .view-toggle-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Map View Styles */
        .map-view-container {
            height: 600px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            margin-bottom: 20px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
        }

        #companiesMap {
            height: 100% !important;
            width: 100% !important;
        }

        .company-marker-popup {
            max-width: 250px;
        }

        .company-marker-popup h4 {
            margin: 0 0 5px 0;
            color: var(--primary);
            font-size: 1rem;
        }

        .company-marker-popup p {
            margin: 0 0 5px 0;
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .company-marker-popup .jobs-count {
            display: inline-block;
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-top: 5px;
        }

        /* Mini Map in Cards */
        .company-mini-map {
            height: 120px !important;
            border-radius: 6px;
            margin-bottom: 10px;
        }

        .mini-map-coordinates {
            font-size: 0.6rem;
            color: var(--gray-500);
            text-align: center;
            margin-top: 5px;
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
            
            .companies-grid {
                grid-template-columns: 1fr;
            }
            
            .company-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .company-actions {
                flex-direction: column;
            }

            .map-view-container {
                height: 400px;
            }

            .map-container {
                height: 150px;
            }

            .company-mini-map {
                height: 100px !important;
            }

            .view-toggle {
                flex-direction: column;
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

            <!-- Header Section -->
            <div class="header-section">
                <h1>Browse Companies</h1>
                <p class="subtitle">Discover amazing companies and their career opportunities</p>
            </div>

            <div class="content-grid">
                <!-- Filters Sidebar -->
                <div class="filter-section">
                    <div class="section-header">
                        <h2>Filters 
                            <?php if ($search_filter || $industry_filter || $company_size_filter || $location_filter): ?>
                                <span class="filter-badge">Active</span>
                            <?php endif; ?>
                        </h2>
                    </div>
                    
                    <!-- Active Filters Display -->
                    <?php if ($search_filter || $industry_filter || $company_size_filter || $location_filter): ?>
                    <div class="active-filters">
                        <strong>Active Filters:</strong>
                        <?php if ($search_filter): ?>
                            <span class="active-filter-item">
                                Search: "<?= htmlspecialchars($search_filter) ?>"
                                <button type="button" class="remove-filter" data-filter="search">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if ($industry_filter): ?>
                            <span class="active-filter-item">
                                Industry: "<?= htmlspecialchars($industry_filter) ?>"
                                <button type="button" class="remove-filter" data-filter="industry">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if ($company_size_filter): ?>
                            <span class="active-filter-item">
                                Size: <?= htmlspecialchars($company_size_filter) ?>
                                <button type="button" class="remove-filter" data-filter="company_size">×</button>
                            </span>
                        <?php endif; ?>
                        <?php if ($location_filter): ?>
                            <span class="active-filter-item">
                                Location: "<?= htmlspecialchars($location_filter) ?>"
                                <button type="button" class="remove-filter" data-filter="location">×</button>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="GET" action="/companies" id="filterForm">
                        <div class="filter-group">
                            <label for="searchInput" class="form-label">Search Companies</label>
                            <input type="text" id="searchInput" name="search" class="form-control" placeholder="Company name, industry, keywords..." value="<?= htmlspecialchars($search_filter) ?>">
                        </div>
                        <div class="filter-group">
                            <label for="industryFilter" class="form-label">Industry</label>
                            <select id="industryFilter" name="industry" class="form-control">
                                <option value="">All Industries</option>
                                <?php foreach ($industries as $industry): ?>
                                    <option value="<?= htmlspecialchars($industry) ?>" <?= $industry_filter == $industry ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($industry) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="companySizeFilter" class="form-label">Company Size</label>
                            <select id="companySizeFilter" name="company_size" class="form-control">
                                <option value="">All Sizes</option>
                                <?php foreach ($company_sizes as $size): ?>
                                    <option value="<?= htmlspecialchars($size) ?>" <?= $company_size_filter == $size ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($size) ?> employees
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="locationFilter" class="form-label">Location</label>
                            <input type="text" id="locationFilter" name="location" class="form-control" placeholder="City, province..." value="<?= htmlspecialchars($location_filter) ?>">
                        </div>
                        <div class="company-actions">
                            <a href="/companies" class="btn btn-secondary" style="width: 100%; text-align: center;">
                                <i class="fas fa-times"></i> Clear All Filters
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Companies List -->
                <div class="section">
                    <div class="section-header">
                        <h2>Companies (<?= $total_companies ?>)</h2>
                        <span style="color: var(--gray-600); font-size: 0.9rem;" id="companyCount">
                            <?= $total_companies ?> compan<?= $total_companies !== 1 ? 'ies' : 'y' ?> found
                            <?php if ($search_filter || $industry_filter || $company_size_filter || $location_filter): ?>
                                <span style="color: var(--primary);">• Filtered</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <!-- View Toggle -->
                    <div class="view-toggle">
                        <button class="view-toggle-btn active" data-view="grid">
                            <i class="fas fa-th"></i> Grid View
                        </button>
                        <button class="view-toggle-btn" data-view="map">
                            <i class="fas fa-map"></i> Map View
                        </button>
                    </div>

                    <!-- Map View Container -->
                    <div id="mapView" style="display: none;">
                        <div class="map-view-container">
                            <div id="companiesMap"></div>
                        </div>
                        <div class="map-coordinates">
                            <span id="companiesMapCoordinates">Loading companies map...</span>
                        </div>
                    </div>

                    <!-- Grid View Container -->
                    <div id="gridView">
                        <div id="companiesList">
                            <?php if (!empty($companies)): ?>
                                <div class="companies-grid">
                                    <?php foreach ($companies as $company): ?>
                                    <div class="company-card">
                                        <div class="company-header">
                                            <div class="company-logo">
                                                <?php if (!empty($company['company_logo'])): ?>
                                                    <img src="/<?= htmlspecialchars($company['company_logo']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>">
                                                <?php else: ?>
                                                    <i class="fas fa-building"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="company-info">
                                                <h3><?= htmlspecialchars($company['company_name']) ?></h3>
                                                <?php if (!empty($company['company_industry'])): ?>
                                                    <p class="company-industry"><?= htmlspecialchars($company['company_industry']) ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($company['company_description'])): ?>
                                            <p class="company-description"><?= htmlspecialchars($company['company_description']) ?></p>
                                        <?php endif; ?>

                                        <!-- Company Mini Map -->
                                        <?php if (!empty($company['company_address'])): ?>
                                        <div class="map-container">
                                            <div class="company-mini-map" id="companyMiniMap-<?= $company['id'] ?>"></div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="company-meta">
                                            <?php if (!empty($company['company_size'])): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-users"></i>
                                                    <?= htmlspecialchars($company['company_size']) ?> employees
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($company['company_address'])): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($company['company_address']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($company['active_jobs'] > 0): ?>
                                                <span class="meta-item">
                                                    <i class="fas fa-briefcase"></i>
                                                    <span class="jobs-count"><?= $company['active_jobs'] ?> job<?= $company['active_jobs'] !== 1 ? 's' : '' ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="company-actions">
                                            <a href="/companies/view/<?= $company['id'] ?>" class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View Company
                                            </a>
                                            <?php if ($company['active_jobs'] > 0): ?>
                                                <a href="/jobs?search=<?= urlencode($company['company_name']) ?>" class="btn btn-secondary">
                                                    <i class="fas fa-briefcase"></i> View Jobs
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination -->
                                <?php if ($data['total_pages'] > 1): ?>
                                    <div class="pagination">
                                        <?php if ($data['current_page_num'] > 1): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $data['current_page_num'] - 1])) ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        <?php endif; ?>

                                        <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                                            <?php if ($i == $data['current_page_num']): ?>
                                                <span class="current"><?= $i ?></span>
                                            <?php else: ?>
                                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>

                                        <?php if ($data['current_page_num'] < $data['total_pages']): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $data['current_page_num'] + 1])) ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                            <?php else: ?>
                                <div class="no-data">
                                    <i class="fas fa-building" style="font-size: 3rem; margin-bottom: 1rem; color: var(--gray-300);"></i>
                                    <p>
                                        <?php if ($search_filter || $industry_filter || $company_size_filter || $location_filter): ?>
                                            No companies found matching your filters. 
                                            <a href="/companies" style="color: var(--primary); text-decoration: underline;">Clear filters</a> to see all companies.
                                        <?php else: ?>
                                            No companies available at the moment. Check back later!
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    // Map variables
    let companiesMap = null;
    let companyMarkers = [];
    const defaultLat = 14.5995, defaultLng = 120.9842;

    // Initialize companies map
    function initializeCompaniesMap() {
        console.log('Initializing Companies Map...');
        
        const mapContainer = document.getElementById('companiesMap');
        if (!mapContainer) {
            console.error('Companies map container not found');
            return;
        }

        // Clear any existing map
        if (companiesMap) {
            companiesMap.remove();
            companiesMap = null;
            companyMarkers = [];
        }

        // Wait for container to be visible
        setTimeout(() => {
            try {
                // Initialize map
                companiesMap = L.map('companiesMap', {
                    center: [defaultLat, defaultLng],
                    zoom: 11,
                    zoomControl: true,
                    dragging: true,
                    scrollWheelZoom: true
                });

                // Add tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(companiesMap);

                // Add companies to map
                addCompaniesToMap();

                // Force resize after a short delay
                setTimeout(() => {
                    if (companiesMap) {
                        companiesMap.invalidateSize();
                        console.log('Companies Map initialized successfully');
                    }
                }, 300);

            } catch (error) {
                console.error('Error initializing companies map:', error);
            }
        }, 100);
    }

    // Add companies to the map
    function addCompaniesToMap() {
        if (!companiesMap) return;

        // Clear existing markers
        companyMarkers.forEach(marker => companiesMap.removeLayer(marker));
        companyMarkers = [];

        // Get company data from the page
        const companyCards = document.querySelectorAll('.company-card');
        let bounds = L.latLngBounds();

        companyCards.forEach((card, index) => {
            const companyId = card.querySelector('a[href*="/companies/view/"]')?.href.split('/').pop();
            const companyName = card.querySelector('h3')?.textContent || 'Company';
            const companyIndustry = card.querySelector('.company-industry')?.textContent || '';
            const companyAddress = card.querySelector('.meta-item i.fa-map-marker-alt')?.parentNode?.textContent?.replace('📍', '').trim() || '';
            const jobsCount = card.querySelector('.jobs-count')?.textContent || '0 jobs';

            if (companyAddress) {
                // Geocode company address
                geocodeCompanyAddress(companyAddress).then(coords => {
                    if (coords) {
                        const marker = L.marker([coords.lat, coords.lng]).addTo(companiesMap);
                        
                        // Create popup content
                        const popupContent = `
                            <div class="company-marker-popup">
                                <h4>${companyName}</h4>
                                ${companyIndustry ? `<p><strong>Industry:</strong> ${companyIndustry}</p>` : ''}
                                <p><strong>Location:</strong> ${companyAddress}</p>
                                <p><strong>Jobs:</strong> <span class="jobs-count">${jobsCount}</span></p>
                                <a href="/companies/view/${companyId}" class="btn btn-primary btn-sm" style="display: block; text-align: center; margin-top: 10px; padding: 5px 10px;">
                                    <i class="fas fa-eye"></i> View Company
                                </a>
                            </div>
                        `;
                        
                        marker.bindPopup(popupContent);
                        companyMarkers.push(marker);
                        
                        // Extend bounds to include this marker
                        bounds.extend([coords.lat, coords.lng]);
                        
                        // Fit map to bounds after all markers are added
                        if (index === companyCards.length - 1) {
                            setTimeout(() => {
                                if (bounds.isValid()) {
                                    companiesMap.fitBounds(bounds, { padding: [20, 20] });
                                }
                            }, 500);
                        }
                    }
                });
            }
        });

        // If no companies with addresses, set default view
        if (companyCards.length === 0) {
            companiesMap.setView([defaultLat, defaultLng], 11);
        }
    }

    // Initialize mini maps for company cards
    function initializeCompanyMiniMaps() {
        const companyCards = document.querySelectorAll('.company-card');
        
        companyCards.forEach(card => {
            const companyId = card.querySelector('a[href*="/companies/view/"]')?.href.split('/').pop();
            const companyAddress = card.querySelector('.meta-item i.fa-map-marker-alt')?.parentNode?.textContent?.replace('📍', '').trim() || '';
            const mapContainer = card.querySelector(`#companyMiniMap-${companyId}`);
            
            if (mapContainer && companyAddress) {
                // Wait for card to be in view
                setTimeout(() => {
                    initializeMiniMap(mapContainer, companyAddress, companyId);
                }, 100 * companyCards.length);
            }
        });
    }

    // Initialize a mini map for a company card
    function initializeMiniMap(container, address, companyId) {
        if (!container) return;

        // Clear existing map
        const existingMap = container._leaflet_map;
        if (existingMap) {
            existingMap.remove();
        }

        // Geocode address
        geocodeCompanyAddress(address).then(coords => {
            if (coords) {
                try {
                    const miniMap = L.map(container, {
                        center: [coords.lat, coords.lng],
                        zoom: 13,
                        zoomControl: false,
                        dragging: false,
                        scrollWheelZoom: false,
                        doubleClickZoom: false,
                        boxZoom: false,
                        keyboard: false,
                        tap: false,
                        touchZoom: false
                    });

                    // Add tile layer
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: ''
                    }).addTo(miniMap);

                    // Add marker
                    L.marker([coords.lat, coords.lng], {
                        icon: L.divIcon({
                            className: 'company-mini-marker',
                            html: '<i class="fas fa-building" style="color: #4361ee; font-size: 16px;"></i>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        })
                    }).addTo(miniMap);

                    // Store reference
                    container._leaflet_map = miniMap;

                } catch (error) {
                    console.error('Error initializing mini map:', error);
                }
            }
        });
    }

    // Geocode company address
    async function geocodeCompanyAddress(address) {
        if (!address) return null;

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}&limit=1`);
            const data = await response.json();
            
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon)
                };
            }
        } catch (error) {
            console.error('Error geocoding address:', error);
        }
        
        return null;
    }

    // View toggle functionality
    function setupViewToggle() {
        const gridViewBtn = document.querySelector('[data-view="grid"]');
        const mapViewBtn = document.querySelector('[data-view="map"]');
        const gridView = document.getElementById('gridView');
        const mapView = document.getElementById('mapView');

        gridViewBtn.addEventListener('click', function() {
            gridViewBtn.classList.add('active');
            mapViewBtn.classList.remove('active');
            gridView.style.display = 'block';
            mapView.style.display = 'none';
        });

        mapViewBtn.addEventListener('click', function() {
            mapViewBtn.classList.add('active');
            gridViewBtn.classList.remove('active');
            gridView.style.display = 'none';
            mapView.style.display = 'block';
            
            // Initialize map if not already initialized
            if (!companiesMap) {
                initializeCompaniesMap();
            } else {
                companiesMap.invalidateSize();
            }
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

        // Auto-submit functionality
        let searchTimeout;
        const searchInput = $('#searchInput');
        const locationInput = $('#locationFilter');
        const industrySelect = $('#industryFilter');
        const companySizeSelect = $('#companySizeFilter');

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
        setupInstantSubmit(industrySelect);
        setupInstantSubmit(companySizeSelect);

        // Remove individual filter
        $('.remove-filter').on('click', function() {
            const filterName = $(this).data('filter');
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(filterName);
            window.location.href = '/companies?' + urlParams.toString();
        });

        // Initialize view toggle
        setupViewToggle();

        // Initialize mini maps for company cards
        setTimeout(() => {
            initializeCompanyMiniMaps();
        }, 1000);
    });

    // Debug function
    function debugMapStatus() {
        console.log('=== COMPANIES MAP DEBUG INFO ===');
        console.log('Companies Map:', companiesMap);
        console.log('Company Markers:', companyMarkers.length);
        console.log('Companies Container:', document.getElementById('companiesMap'));
        console.log('=== END DEBUG ===');
    }
    </script>
</body>
</html>