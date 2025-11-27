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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #dbeafe;
            --secondary: #7c3aed;
            --success: #059669;
            --info: #0891b2;
            --warning: #d97706;
            --danger: #dc2626;
            --light: #f8fafc;
            --dark: #1e293b;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --border-radius-sm: 8px;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--gray-700);
            line-height: 1.6;
            overflow-x: hidden;
            font-weight: 400;
        }

        .main-content {
            margin-left: 280px;
            min-height: 100vh;
            background: transparent;
        }

        .dashboard-container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Messages */
        .alert {
            padding: 16px 20px;
            margin-bottom: 24px;
            border-radius: var(--border-radius);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            box-shadow: var(--shadow-md);
        }

        .alert-success {
            background: white;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: white;
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* Header Section */
        .header-section {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 40px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            margin-bottom: 32px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .header-section::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .header-section h1 {
            font-size: 2.5rem;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }

        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 32px;
            margin-bottom: 40px;
            height: calc(100vh - 200px);
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        .section {
            background: white;
            padding: 32px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-200);
            transition: var(--transition);
        }

        .section:hover {
            box-shadow: var(--shadow-xl);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--gray-100);
            flex-shrink: 0;
        }

        .section-header h2 {
            margin: 0;
            color: var(--gray-900);
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: -0.025em;
        }

        /* Scrollable Content Areas */
        .scrollable-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 12px;
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
            gap: 32px;
            margin-bottom: 32px;
            position: relative;
            z-index: 2;
        }

        .company-logo {
            width: 140px;
            height: 140px;
            border-radius: var(--border-radius-lg);
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: white;
            box-shadow: var(--shadow-lg);
            flex-shrink: 0;
        }

        .company-logo img {
            width: 100%;
            height: 100%;
            border-radius: var(--border-radius);
            object-fit: cover;
        }

        .company-info {
            flex: 1;
        }

        .company-info h1 {
            margin: 0 0 12px 0;
            color: white;
            font-weight: 700;
            font-size: 2.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .company-industry {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin: 0 0 20px 0;
            font-size: 1.25rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            backdrop-filter: blur(10px);
        }

        .company-description {
            color: rgba(255, 255, 255, 0.9);
            margin: 0 0 24px 0;
            line-height: 1.7;
            font-size: 1.1rem;
            max-width: 600px;
        }

        /* Company Stats */
        .company-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-item {
            text-align: center;
            padding: 24px 20px;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }

        .stat-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
            line-height: 1;
        }

        .stat-label {
            color: var(--gray-600);
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Company Details */
        .company-details {
            margin-bottom: 32px;
        }

        .detail-section {
            margin-bottom: 32px;
        }

        .detail-section h3 {
            color: var(--gray-900);
            margin-bottom: 16px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }

        .detail-section h3 i {
            font-size: 1.1rem;
            color: var(--primary);
        }

        .detail-content {
            background: var(--gray-50);
            padding: 24px;
            border-radius: var(--border-radius);
            border-left: 4px solid var(--primary);
            line-height: 1.7;
            border: 1px solid var(--gray-200);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-value {
            color: var(--gray-900);
            font-weight: 500;
            font-size: 1rem;
        }

        /* Recent Jobs */
        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .job-card {
            border: 1px solid var(--gray-200);
            padding: 24px;
            border-radius: var(--border-radius);
            transition: var(--transition);
            background: white;
            position: relative;
            overflow: hidden;
        }

        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary);
            transform: scaleY(0);
            transition: var(--transition);
        }

        .job-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .job-card:hover::before {
            transform: scaleY(1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .job-header h4 {
            margin: 0;
            color: var(--gray-900);
            font-weight: 600;
            font-size: 1.25rem;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 16px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .meta-item i {
            color: var(--primary);
            width: 16px;
        }

        .job-description {
            color: var(--gray-700);
            margin: 0 0 20px 0;
            line-height: 1.6;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 16px;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid var(--gray-100);
            flex-shrink: 0;
        }

        .btn {
            padding: 14px 24px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            letter-spacing: 0.025em;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: var(--transition);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            box-shadow: var(--shadow-sm);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-success:hover {
            background: #047857;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
            box-shadow: var(--shadow-md);
        }

        .btn-warning:hover {
            background: #b45309;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-sm {
            padding: 10px 16px;
            font-size: 0.85rem;
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
            padding: 60px 40px;
            background: var(--gray-50);
            border-radius: var(--border-radius);
            border: 2px dashed var(--gray-300);
        }

        .no-data i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: var(--gray-400);
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            width: 44px;
            height: 44px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
        }

        .mobile-menu-toggle:hover {
            background: var(--primary-dark);
            transform: scale(1.05);
        }

        /* ========== MAP STYLES ========== */
        .map-container {
            height: 300px;
            width: 100%;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-300);
            margin-bottom: 16px;
            overflow: hidden;
            position: relative;
            background: var(--gray-100);
            box-shadow: var(--shadow-sm);
        }

        #companyMap, #jobDetailsMap {
            height: 100% !important;
            width: 100% !important;
            min-height: 300px;
            position: relative;
            z-index: 1;
        }

        .leaflet-container {
            height: 100% !important;
            width: 100% !important;
            background: var(--gray-100) !important;
            border-radius: var(--border-radius);
            font-family: 'Inter', sans-serif;
        }

        .map-coordinates {
            background: var(--gray-50);
            padding: 12px 16px;
            border-radius: var(--border-radius-sm);
            font-size: 0.8rem;
            color: var(--gray-600);
            margin-bottom: 12px;
            border: 1px solid var(--gray-200);
            font-family: 'Monaco', 'Consolas', monospace;
        }

        .map-instructions {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 12px;
            font-style: italic;
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
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.95);
            background: white;
            padding: 32px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease forwards;
            border: 1px solid var(--gray-200);
        }

        @keyframes modalSlideIn {
            to {
                transform: translate(-50%, -50%) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--gray-100);
        }

        .modal-header h3 {
            margin: 0;
            color: var(--gray-900);
            font-size: 1.5rem;
            line-height: 1.3;
            flex: 1;
            margin-right: 20px;
            font-weight: 600;
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray-500);
            padding: 8px;
            border-radius: var(--border-radius);
            transition: var(--transition-fast);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            color: var(--danger);
            background: var(--gray-100);
        }

        .job-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid var(--gray-100);
        }

        /* Badges and Tags */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.025em;
        }

        .badge-success {
            background: #d1fae5;
            color: var(--success);
        }

        .badge-warning {
            background: #fef3c7;
            color: var(--warning);
        }

        /* Loading States */
        .skeleton {
            background: linear-gradient(90deg, var(--gray-200) 25%, var(--gray-300) 50%, var(--gray-200) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: var(--border-radius);
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .dashboard-container {
                padding: 20px;
            }
            
            .header-section {
                padding: 30px 24px;
            }
            
            .header-section h1 {
                font-size: 2rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 24px;
                height: auto;
            }
            
            .company-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 20px;
            }
            
            .company-logo {
                width: 120px;
                height: 120px;
                font-size: 3rem;
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
                padding: 24px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .job-info-grid {
                grid-template-columns: 1fr;
            }

            .modal-header {
                flex-direction: column;
                gap: 12px;
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

            .section {
                padding: 24px;
            }
        }

        /* Print Styles */
        @media print {
            .action-buttons,
            .mobile-menu-toggle,
            .modal-actions {
                display: none !important;
            }
            
            .section {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
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
            <div style="margin-bottom: 24px;">
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
                                <img src="/<?= htmlspecialchars($company['company_logo']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" onerror="this.style.display='none'; this.parentNode.innerHTML='<i class=\"fas fa-building\"></i>
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
                            <span class="badge"><?= $company['active_jobs'] ?? 0 ?> Active</span>
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
                                    <div class="stat-label">Employees</div>
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
                                        <p style="margin-bottom: 16px; font-weight: 500;"><?= htmlspecialchars($company['company_address']) ?></p>
                                        
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
                                        <a href="<?= htmlspecialchars($company['company_website']) ?>" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                            <i class="fas fa-external-link-alt" style="margin-right: 8px;"></i>
                                            Visit Website
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($company['google_form_url'])): ?>
                                <div class="detail-section">
                                    <h3><i class="fas fa-envelope"></i> Contact Company</h3>
                                    <div class="detail-content">
                                        <p style="margin-bottom: 16px;">Interested in working at <?= htmlspecialchars($company['company_name']) ?>? Send them a message!</p>
                                        
                                        <!-- Contact Button -->
                                        <button class="btn btn-primary btn-block" id="showContactForm" style="width: 100%;">
                                            <i class="fas fa-paper-plane"></i> Contact Company
                                        </button>
                                        
                                        <div style="margin-top: 12px; font-size: 0.8rem; color: var(--gray-500); text-align: center;">
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
                            <?php if (!$is_employer): ?>
                            <a href="/jobs" class="btn btn-primary">
                                <i class="fas fa-search"></i> Find Jobs
                            </a>
                            <?php endif; ?>
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
                                    <span style="color: var(--gray-600); font-size: 0.9rem; font-weight: 500;">
                                        <?= count($company['recent_jobs'] ?? []) ?> job<?= count($company['recent_jobs'] ?? []) !== 1 ? 's' : '' ?> available
                                    </span>
                                </div>

                                <?php if (!empty($company['recent_jobs'])): ?>
                                    <div class="jobs-list">
                                        <?php foreach ($company['recent_jobs'] as $job): ?>
                                        <div class="job-card">
                                            <div class="job-header">
                                                <h4><?= htmlspecialchars($job['title']) ?></h4>
                                                <span style="color: var(--success); font-weight: 600; font-size: 1.1rem;">
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
                                            <div class="action-buttons" style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
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
                                                <a href="/dashboard/apply_job?job_id=<?= $job['id'] ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-paper-plane"></i> Apply Now
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="no-data">
                                        <i class="fas fa-briefcase"></i>
                                        <h3 style="color: var(--gray-600); margin-bottom: 8px;">No Current Openings</h3>
                                        <p style="margin-bottom: 20px;">This company doesn't have any job openings at the moment.</p>
                                        <p style="font-size: 0.9rem; color: var(--gray-500);">Check back later for new opportunities!</p>
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
                        <i class="fas fa-building"></i>
                        <h3 style="color: var(--gray-600); margin-bottom: 12px;">Company Not Available</h3>
                        <p style="margin-bottom: 24px;">The company you're trying to view is not available or may have been removed.</p>
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
            <div id="formLoading" style="padding: 60px 40px; text-align: center; background: var(--gray-50); border-radius: 0 0 var(--border-radius) var(--border-radius);">
                <i class="fas fa-spinner fa-spin" style="font-size: 2.5rem; color: var(--primary); margin-bottom: 20px;"></i>
                <h4 style="color: var(--gray-700); margin-bottom: 8px;">Loading Contact Form</h4>
                <p style="color: var(--gray-500);">Please wait while we load the contact form...</p>
            </div>
            
            <!-- Google Form Iframe -->
            <iframe id="googleFormIframe" 
                    src="" 
                    width="100%" 
                    height="500" 
                    frameborder="0" 
                    marginheight="0" 
                    marginwidth="0"
                    style="display: none; border-radius: 0 0 var(--border-radius) var(--border-radius);"
                    onload="document.getElementById('formLoading').style.display='none'; this.style.display='block';">
            </iframe>
        </div>
        
        <div class="modal-actions">
            
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