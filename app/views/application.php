<?php
// Safely access data with null checks
$user = $data['user'] ?? [];
$role = $user['role'] ?? 'job_seeker';
$applications = $data['applications'] ?? [];
$stats = $data['stats'] ?? [];

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Get filter parameters from URL
$status_filter = $_GET['status'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Handle remove application request
if (isset($_GET['remove_application'])) {
    $application_id = $_GET['application_id'] ?? null;
    
    if ($application_id) {
        $result = $this->removeApplication($application_id, $user['id']);
        
        if ($result) {
            $_SESSION['success'] = "Application removed successfully!";
        } else {
            $_SESSION['error'] = "Failed to remove application. Please try again.";
        }
        
        // Redirect to clear URL parameters
        header("Location: /dashboard/load/application");
        exit();
    }
}

// Get modal data
$show_remove_modal = isset($_GET['remove_application_confirm']);
$remove_application_id = $_GET['application_id'] ?? null;
$remove_application = null;

// Get application details for remove modal
if ($show_remove_modal && $remove_application_id) {
    foreach ($applications as $app) {
        if ($app['id'] == $remove_application_id) {
            $remove_application = $app;
            break;
        }
    }
}

// Status options - different for job seeker vs employer
if ($role == 'job_seeker') {
    $status_options = [
        'all' => 'All Applications',
        'Applied' => 'Applied',
        'Interview Scheduled' => 'Interview Scheduled',
        'Rejected' => 'Rejected'
    ];
} else {
    $status_options = [
        'all' => 'All Applications',
        'Applied' => 'New Applications',
        'Interview Scheduled' => 'Interviews',
        'Rejected' => 'Rejected',
        'Hired' => 'Hired'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['title'] ?? 'Applications') ?></title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        <?php if ($role == 'employer'): ?>
        .stat-card {
            cursor: pointer;
        }
        .stat-card.active {
            border-left-color: var(--secondary);
            background: linear-gradient(135deg, #f8f9ff, #ffffff);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        <?php endif; ?>

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 1.3rem;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin: 0;
            color: var(--dark);
            font-weight: 700;
        }

        .stat-info p {
            margin: 5px 0 0 0;
            color: var(--gray-600);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Quick Actions - Employer Only */
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            background: white;
            padding: 15px 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--gray-700);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            flex: 1;
            min-width: 200px;
        }

        .quick-action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .quick-action-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-size: 1.1rem;
        }

        /* Filters Section */
        .filters-section {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 180px;
        }

        .filter-group label {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .filter-select, .search-input {
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 0.9rem;
            width: 100%;
            transition: var(--transition);
        }

        .search-input {
            padding-left: 40px;
        }

        .search-wrapper {
            position: relative;
            flex: 2;
            min-width: 250px;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            z-index: 1;
        }

        .filter-select:focus, .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            height: fit-content;
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

        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Employer Search Filters */
        .employer-search-filters {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .search-filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 200px;
        }

        .search-filter-group label {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.9rem;
        }

        .search-filter-select {
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background: white;
            color: var(--gray-700);
            font-size: 0.9rem;
            width: 100%;
            transition: var(--transition);
        }

        .search-filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        /* Applications List */
        .applications-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h2 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4rem;
        }

        .applications-count {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .application-card {
            border: 1px solid var(--gray-200);
            padding: 25px;
            border-radius: 10px;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .application-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        <?php if ($role == 'employer'): ?>
        .application-card.new {
            border-left: 4px solid var(--success);
            background: linear-gradient(135deg, #f8fdff, #ffffff);
        }
        <?php endif; ?>

        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
        }

        .application-info {
            flex: 1;
        }

        .application-info h3 {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.2rem;
        }

        .company-name {
            color: var(--primary);
            font-weight: 600;
            margin: 0 0 5px 0;
            font-size: 1rem;
        }

        .application-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 10px 0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .meta-item i {
            width: 14px;
            color: var(--gray-500);
        }

        .application-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-badge.applied {
            background: rgba(76, 201, 240, 0.15);
            color: #0d6efd;
        }

        .status-badge.interview-scheduled {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        .status-badge.rejected {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
        }

        .status-badge.hired {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        /* Applicant Info (Employer View) */
        .applicant-info {
            background: var(--gray-50);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }

        .applicant-name {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .applicant-email {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .applicant-phone {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        /* Skills Tags (Employer View) */
        .skills-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }

        .skill-tag {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Resume Preview (Employer View) */
        .resume-preview {
            background: var(--gray-50);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 3px solid var(--primary);
        }

        .resume-preview h4 {
            margin: 0 0 8px 0;
            color: var(--dark);
            font-size: 1rem;
        }

        .resume-description {
            color: var(--gray-700);
            line-height: 1.5;
            font-size: 0.9rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* No Data State */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        .no-data h3 {
            margin: 0 0 10px 0;
            color: var(--gray-600);
        }

        .no-data p {
            margin: 0;
            font-size: 0.95rem;
        }

        /* Action Buttons */
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
            padding: 30px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            max-width: 1000px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-200);
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

        .modal-message {
            text-align: center;
            padding: 20px 0;
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 16px;
            color: var(--danger);
        }

        /* Interview Details Styles */
        .interview-details-section {
            margin-bottom: 20px;
        }

        .interview-detail-item {
            display: flex;
            margin-bottom: 12px;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            border-left: 3px solid var(--primary);
        }

        .interview-detail-icon {
            width: 40px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .interview-detail-content {
            flex: 1;
        }

        .interview-detail-label {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 0.9rem;
            margin-bottom: 4px;
        }

        .interview-detail-value {
            color: var(--gray-800);
            font-size: 1rem;
        }

        .interview-notes {
            background: var(--gray-50);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 3px solid var(--info);
        }

        .interview-notes h4 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1rem;
        }

        .interview-notes-content {
            color: var(--gray-700);
            line-height: 1.5;
            white-space: pre-line;
        }

        .job-info-card {
            background: var(--gray-50);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 3px solid var(--success);
        }

        .job-info-card h4 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1.1rem;
        }

        .company-contact {
            background: var(--gray-50);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 3px solid var(--warning);
        }

        .company-contact h4 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1rem;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
            color: var(--gray-700);
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: var(--primary);
        }

        .error-message {
            text-align: center;
            padding: 40px;
            color: var(--danger);
        }

        .error-message i {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
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
                flex-direction: column;
            }
            
            .quick-action-btn {
                min-width: auto;
            }
            
            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group, .search-wrapper {
                width: 100%;
            }
            
            .filter-select, .search-input {
                width: 100%;
            }
            
            .filter-actions {
                width: 100%;
                justify-content: stretch;
            }
            
            .filter-actions .btn {
                flex: 1;
                justify-content: center;
            }
            
            .employer-search-filters {
                flex-direction: column;
            }
            
            .search-filter-group {
                width: 100%;
            }
            
            .application-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .application-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        .hidden {
            display: none !important;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1.2rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }

        /* Google Calendar Integration Styles */
        .calendar-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 15px;
            background: rgba(67, 97, 238, 0.05);
            border-radius: 8px;
            border: 1px solid rgba(67, 97, 238, 0.2);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: var(--primary);
            cursor: pointer;
        }

        .form-help {
            display: block;
            margin-top: 5px;
            font-size: 0.8rem;
            color: var(--gray-600);
        }

        .calendar-preview {
            background: var(--gray-50);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            margin: 15px 0;
        }

        .calendar-preview h4 {
            margin: 0 0 10px 0;
            color: var(--dark);
            font-size: 1rem;
        }

        .preview-content p {
            margin: 5px 0;
            font-size: 0.9rem;
        }

        .calendar-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-top: 10px;
        }

        .calendar-link:hover {
            color: var(--primary-dark);
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

/* Calendar highlight styles */
.calendar-highlight {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border: 2px solid var(--primary);
}

.calendar-header {
    text-align: center;
    margin-bottom: 15px;
}

.calendar-header .month-year {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 5px;
}

.calendar-header .interview-date {
    font-size: 0.9rem;
    color: var(--primary);
    font-weight: 500;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 5px;
    margin-bottom: 15px;
}

.calendar-day-header {
    text-align: center;
    font-weight: 600;
    color: var(--gray-600);
    font-size: 0.8rem;
    padding: 5px;
}

.calendar-day {
    text-align: center;
    padding: 8px;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.calendar-day.other-month {
    color: var(--gray-400);
}

.calendar-day.today {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    font-weight: 600;
}

.calendar-day.interview-day {
    background: var(--primary);
    color: white;
    font-weight: 600;
    transform: scale(1.1);
    box-shadow: 0 2px 8px rgba(67, 97, 238, 0.3);
}

.calendar-day:hover {
    background: var(--gray-100);
}

/* Map container styles */
.interview-map-container {
    margin: 20px 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.interview-map {
    height: 200px;
    width: 100%;
    background: var(--gray-100);
}

.map-placeholder {
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
    color: var(--gray-500);
    flex-direction: column;
}

.map-placeholder i {
    font-size: 2rem;
    margin-bottom: 10px;
}

/* Interview timeline */
.interview-timeline {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    border-left: 4px solid var(--primary);
}

.timeline-item {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(67, 97, 238, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: var(--primary);
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
}

.timeline-label {
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.timeline-value {
    color: var(--dark);
    font-size: 1rem;
    font-weight: 500;
}

/* Countdown timer */
.interview-countdown {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
}

.countdown-title {
    font-size: 1rem;
    margin-bottom: 10px;
    opacity: 0.9;
}

.countdown-timer {
    font-size: 1.5rem;
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.countdown-unit {
    font-size: 0.8rem;
    opacity: 0.8;
    margin-top: 5px;
}


/* Enhanced Interview Details Modal */
#interviewDetailsModal .modal-content {
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}

.interview-header {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    padding: 25px;
    border-radius: 12px 12px 0 0;
    margin: -30px -30px 25px -30px;
    position: relative;
    overflow: hidden;
}

.interview-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200px;
    height: 200px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.interview-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.interview-subtitle {
    opacity: 0.9;
    font-size: 1rem;
}

.interview-status-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(10px);
}

/* Interview Timeline */
.interview-timeline {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin: 25px 0;
}

.timeline-item {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.timeline-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: rgba(67, 97, 238, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.1rem;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
    padding-bottom: 20px;
    border-left: 2px solid var(--gray-200);
    padding-left: 20px;
    position: relative;
}

.timeline-content::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 8px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--primary);
}

.timeline-label {
    font-weight: 600;
    color: var(--gray-700);
    font-size: 0.9rem;
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.timeline-value {
    color: var(--dark);
    font-size: 1.1rem;
    font-weight: 500;
    line-height: 1.4;
}

.timeline-note {
    color: var(--gray-600);
    font-size: 0.9rem;
    margin-top: 5px;
    font-style: italic;
}

/* Interview Actions Grid */
.interview-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 25px 0;
}

.interview-action-card {
    background: var(--gray-50);
    padding: 20px;
    border-radius: 10px;
    text-align: center;
    border: 2px solid transparent;
    transition: all 0.3s ease;
    cursor: pointer;
}

.interview-action-card:hover {
    border-color: var(--primary);
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.interview-action-icon {
    width: 50px;
    height: 50px;
    margin: 0 auto 12px;
    background: rgba(67, 97, 238, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--primary);
    font-size: 1.3rem;
}

.interview-action-title {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 5px;
}

.interview-action-desc {
    color: var(--gray-600);
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Preparation Tips */
.preparation-tips {
    background: linear-gradient(135deg, #f8f9ff, #f0f4ff);
    border: 1px solid rgba(67, 97, 238, 0.2);
    border-radius: 12px;
    padding: 25px;
    margin: 25px 0;
}

.preparation-tips h4 {
    color: var(--primary);
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.tip-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.tip-icon {
    color: var(--primary);
    font-size: 0.9rem;
    margin-top: 2px;
}

.tip-content {
    flex: 1;
}

.tip-title {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.9rem;
    margin-bottom: 4px;
}

.tip-desc {
    color: var(--gray-600);
    font-size: 0.8rem;
    line-height: 1.4;
}

/* Map Container */
.interview-map-container {
    margin: 25px 0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.interview-map {
    height: 250px;
    width: 100%;
    background: var(--gray-100);
}

.map-placeholder {
    height: 250px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--gray-100);
    color: var(--gray-500);
    flex-direction: column;
    gap: 10px;
}

.map-placeholder i {
    font-size: 2.5rem;
    opacity: 0.5;
}

/* Notes Section */
.interview-notes-section {
    background: var(--gray-50);
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    border-left: 4px solid var(--info);
}

.notes-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    color: var(--dark);
}

.notes-content {
    color: var(--gray-700);
    line-height: 1.6;
    white-space: pre-line;
    background: white;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}

/* Contact Information */
.contact-section {
    background: var(--gray-50);
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
    border-left: 4px solid var(--warning);
}

.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid var(--gray-200);
}

.contact-icon {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    border-radius: 50%;
    font-size: 0.9rem;
}

.contact-details {
    flex: 1;
}

.contact-label {
    font-size: 0.8rem;
    color: var(--gray-600);
    margin-bottom: 2px;
}

.contact-value {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.9rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .interview-header {
        padding: 20px;
        margin: -20px -20px 20px -20px;
    }
    
    .interview-status-badge {
        position: static;
        display: inline-block;
        margin-top: 10px;
    }
    
    .timeline-item {
        flex-direction: column;
        gap: 10px;
    }
    
    .timeline-content {
        border-left: none;
        padding-left: 0;
        padding-bottom: 15px;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .timeline-content::before {
        display: none;
    }
    
    .interview-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-grid {
        grid-template-columns: 1fr;
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
                <h1>
                    <?php if ($role == 'job_seeker'): ?>
                        My Applications
                    <?php else: ?>
                        Job Applications
                    <?php endif; ?>
                </h1>
                <p class="subtitle">
                    <?php if ($role == 'job_seeker'): ?>
                        Track and manage your job applications
                    <?php else: ?>
                        Review and manage applications for your job posts
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
                            <h3><?= htmlspecialchars($stats['total'] ?? 0) ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['interviews'] ?? 0) ?></h3>
                            <p>Interviews</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['rejected'] ?? 0) ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Employer Stats -->
                    <div class="stat-card" data-filter="all">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['total'] ?? 0) ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-filter="Interview Scheduled">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['interviews'] ?? 0) ?></h3>
                            <p>Interviews Scheduled</p>
                        </div>
                    </div>

                    <div class="stat-card" data-filter="Hired">
                        <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['Hired'] ?? 0) ?></h3>
                            <p>Hired Candidates</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            
            <!-- Employer Search Filters -->
            <?php if ($role == 'employer'): ?>
            <div class="employer-search-filters">
                <!-- Search by Name/Email -->
                <div class="search-filter-group">
                    <label for="applicantSearch">Search Applicants</label>
                    <div style="position: relative;">
                        <i class="fas fa-user search-icon"></i>
                        <input 
                            type="text" 
                            id="applicantSearch" 
                            class="search-input" 
                            placeholder="Search by name or email..." 
                            value="<?= htmlspecialchars($search_query) ?>"
                        >
                    </div>
                </div>
                <!-- Filter by Job Title -->
                <div class="search-filter-group">
                    <label for="jobTitleFilter">Job Title</label>
                    <div style="position: relative;">
                        <i class="fas fa-briefcase search-icon"></i>
                        <input 
                            type="text" 
                            id="jobTitleFilter" 
                            class="search-input" 
                            placeholder="Filter by job title..."
                        >
                    </div>
                </div>

                <!-- Filter by Application Date -->
                <div class="search-filter-group">
                    <label for="dateFilter">Application Date</label>
                    <select id="dateFilter" class="search-filter-select">
                        <option value="all">Any Time</option>
                        <option value="today">Today</option>
                        <option value="week">Past Week</option>
                        <option value="month">Past Month</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button id="applyEmployerFilters" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button id="clearEmployerFilters" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear All
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Job Seeker Filters Section -->
            <?php if ($role == 'job_seeker'): ?>
            <div class="filters-section">
                <!-- Search Input -->
                <div class="search-wrapper">
                    <label for="searchInput">Search</label>
                    <div style="position: relative;">
                        <i class="fas fa-search search-icon"></i>
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input" 
                            placeholder="Search jobs or companies..." 
                            value="<?= htmlspecialchars($search_query) ?>"
                        >
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter" class="filter-select">
                        <?php foreach ($status_options as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $status_filter == $value ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="filter-actions">
                    <button id="applyFilters" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <button id="clearFilters" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Applications List -->
            <div class="applications-section">
                <div class="section-header">
                    <h2>
                        <?php if ($role == 'job_seeker'): ?>
                            Your Applications
                        <?php else: ?>
                            All Applications
                        <?php endif; ?>
                    </h2>
                    <div class="applications-count" id="applicationsCount">
                        <?= count($applications) ?> application(s) found
                        <?php if ($search_query): ?>
                            for "<?= htmlspecialchars($search_query) ?>"
                        <?php endif; ?>
                    </div>
                </div>

                <div class="applications-list" id="applicationsList">
                    <?php if (!empty($applications)): ?>
                        <?php foreach ($applications as $application): ?>
                            <?php
                            // Prepare search text for data attribute
                            if ($role == 'job_seeker') {
                                $search_text = ($application['job_title'] ?? '') . ' ' . ($application['job_company'] ?? '');
                                $status = $application['status'] ?? '';
                            } else {
                                // Employer search data includes more fields
                                $search_text = ($application['applicant_name'] ?? '') . ' ' . 
                                             ($application['applicant_email'] ?? '') . ' ' .
                                             ($application['job_title'] ?? '') . ' ' . 
                                             ($application['applicant_skills'] ?? '') . ' ' .
                                             ($application['applicant_experience'] ?? '');
                                $status = $application['status'] ?? '';
                                $job_title = $application['job_title'] ?? '';
                                $experience = $application['applicant_experience'] ?? '';
                                $application_date = $application['created_at'] ?? '';
                            }
                            ?>
                            <div class="application-card <?= ($role == 'employer' && ($application['status'] ?? '') == 'Applied') ? 'new' : '' ?>" 
                                 data-status="<?= htmlspecialchars($status) ?>" 
                                 data-search-text="<?= htmlspecialchars(strtolower($search_text)) ?>"
                                 <?php if ($role == 'employer'): ?>
                                 data-job-title="<?= htmlspecialchars($job_title) ?>"
                                 data-experience="<?= htmlspecialchars($experience) ?>"
                                 data-application-date="<?= htmlspecialchars($application_date) ?>"
                                 <?php endif; ?>>
                                <div class="application-header">
                                    <div class="application-info">
                                        <?php if ($role == 'job_seeker'): ?>
                                            <!-- Job Seeker View -->
                                            <h3><?= htmlspecialchars($application['job_title'] ?? 'No Title') ?></h3>
                                            <p class="company-name"><?= htmlspecialchars($application['job_company'] ?? 'Unknown Company') ?></p>
                                            
                                            <div class="application-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($application['location'] ?? 'Location not specified') ?>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-briefcase"></i>
                                                    <?= htmlspecialchars($application['job_type'] ?? 'Full-time') ?>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="far fa-calendar"></i>
                                                    Applied <?= date('M j, Y', strtotime($application['created_at'] ?? 'now')) ?>
                                                </div>
                                                <?php if (!empty($application['salary'])): ?>
                                                    <div class="meta-item">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                        <?= htmlspecialchars($application['salary']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($application['job_description'])): ?>
                                                <div class="job-preview">
                                                    <h4>Job Description</h4>
                                                    <p class="job-description">
                                                        <?= nl2br(htmlspecialchars($application['job_description'])) ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <!-- Employer View -->
                                            <h3><?= htmlspecialchars($application['applicant_name'] ?? 'Unknown Applicant') ?></h3>
                                            <p class="company-name">Applied for: <?= htmlspecialchars($application['job_title'] ?? 'Unknown Job') ?></p>
                                            
                                            <div class="applicant-info">
                                                <div class="applicant-name">
                                                    <i class="fas fa-user"></i>
                                                    <?= htmlspecialchars($application['applicant_name'] ?? 'Unknown Applicant') ?>
                                                </div>
                                                <div class="applicant-email">
                                                    <i class="fas fa-envelope"></i>
                                                    <?= htmlspecialchars($application['applicant_email'] ?? 'No email provided') ?>
                                                </div>
                                                <?php if (!empty($application['applicant_phone'])): ?>
                                                <div class="applicant-phone">
                                                    <i class="fas fa-phone"></i>
                                                    <?= htmlspecialchars($application['applicant_phone']) ?>
                                                </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if (!empty($application['applicant_skills'])): ?>
                                            <div class="skills-tags">
                                                <?php 
                                                $skills = explode(',', $application['applicant_skills']);
                                                foreach (array_slice($skills, 0, 5) as $skill): 
                                                    if (trim($skill)): 
                                                ?>
                                                    <span class="skill-tag"><?= htmlspecialchars(trim($skill)) ?></span>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                                <?php if (count($skills) > 5): ?>
                                                    <span class="skill-tag">+<?= count($skills) - 5 ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                            <?php endif; ?>

                                            <?php if (!empty($application['applicant_resume'])): ?>
                                            <div class="resume-preview">
                                                <h4>Resume Summary</h4>
                                                <p class="resume-description">
                                                    <?= nl2br(htmlspecialchars($application['applicant_resume'])) ?>
                                                </p>
                                            </div>
                                            <?php endif; ?>

                                            <div class="application-meta">
                                                <div class="meta-item">
                                                    <i class="far fa-calendar"></i>
                                                    Applied <?= date('M j, Y g:i A', strtotime($application['created_at'] ?? 'now')) ?>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-briefcase"></i>
                                                    <?= htmlspecialchars($application['job_type'] ?? 'Full-time') ?>
                                                </div>
                                                <?php if (!empty($application['applicant_experience'])): ?>
                                                <div class="meta-item">
                                                    <i class="fas fa-chart-line"></i>
                                                    <?= htmlspecialchars($application['applicant_experience']) ?> experience
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="application-status">
                                        <?php if (!empty($application['status'])): ?>
                                        <span class="status-badge <?= strtolower(str_replace(' ', '-', $application['status'])) ?>">
                                            <?= htmlspecialchars($application['status']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="application-actions">
                                    <?php if ($role == 'job_seeker'): ?>
                                        <!-- Job Seeker Actions -->
                                        <a href="/dashboard/overview?job_details=1&job_id=<?= $application['job_id'] ?? ($application['id'] ?? '') ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Job
                                        </a>
                                        
                                        <?php if (($application['status'] ?? '') == 'Interview Scheduled'): ?>
                                            <button class="btn btn-success view-interview-details-btn" data-application-id="<?= $application['id'] ?>">
                                                <i class="fas fa-calendar-check"></i> View Interview Details
                                            </button>
                                        <?php endif; ?>
                                         <?php if (($application['status'] ?? '') != 'Interview Scheduled'): ?>
                                          <?php if (($application['status'] ?? '') != 'Hired'): ?>
                                        <!-- Remove Application Button -->
 <a href="/dashboard/application?remove_application_confirm=1&application_id=<?= $application['id'] ?>" class="btn btn-danger">
            <i class="fas fa-trash"></i> Cancel Application
        </a>
                                         <?php endif; ?>   
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Employer Actions -->
                                        <a href="/dashboard/applicant?view_applicant=<?= $application['id'] ?>" 
                                        class="btn btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>                                 
                                        <?php if (($application['status'] ?? '') == 'Applied'): ?>
                                            <button class="btn btn-success schedule-interview-btn" data-application-id="<?= $application['id'] ?? '' ?>">
                                                <i class="fas fa-calendar-plus"></i> Schedule Interview
                                            </button>
                                            <button class="btn btn-danger reject-application-btn" data-application-id="<?= $application['id'] ?? '' ?>">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        <?php elseif (($application['status'] ?? '') == 'Interview Scheduled'): ?>
                                            <button class="btn btn-info reschedule-interview-btn" data-application-id="<?= $application['id'] ?? '' ?>">
                                                <i class="fas fa-edit"></i> Reschedule
                                            </button>
                                            <button class="btn btn-success hire-applicant-btn" data-application-id="<?= $application['id'] ?? '' ?>">
                                                <i class="fas fa-check"></i> Hire
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-secondary contact-applicant-btn" data-email="<?= htmlspecialchars($application['applicant_email'] ?? '') ?>">
                                            <i class="fas fa-envelope"></i> Contact
                                        </button>

                                        <?php if (!empty($application['applicant_resume_file'])): ?>
                                        <a href="<?= htmlspecialchars($application['applicant_resume_file']) ?>" class="btn btn-info" target="_blank">
                                            <i class="fas fa-download"></i> Download Resume
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- No Applications State -->
                        <div class="no-data">
                            <i class="fas fa-file-alt"></i>
                            <h3>No applications found</h3>
                            <p>
                                <?php if ($search_query || $status_filter != 'all'): ?>
                                    No applications match your current filters. Try adjusting your search criteria.
                                <?php else: ?>
                                    <?php if ($role == 'job_seeker'): ?>
                                        You haven't applied to any jobs yet. Start browsing jobs to apply!
                                    <?php else: ?>
                                        No applications have been submitted to your job posts yet.
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                            <div style="margin-top: 20px;">
                                <?php if ($role == 'job_seeker'): ?>
                                    <a href="/jobs" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Browse Jobs
                                    </a>
                                <?php else: ?>
                                    <a href="/jobs/create" class="btn btn-primary">
                                        <i class="fas fa-plus-circle"></i> Post a Job
                                    </a>
                                    <a href="/jobs" class="btn btn-secondary">
                                        <i class="fas fa-eye"></i> View Your Jobs
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
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
       <div class="modal-footer" style="display: flex; gap: 10px; justify-content: space-between; align-items: center; padding: 20px 24px; border-top: 1px solid var(--gray-200);">
    <button class="btn btn-secondary" onclick="closeResumePreview()" style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; background: var(--gray-200); color: var(--gray-700); cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    
    <a href="#" id="previewDownloadBtn" class="btn btn-primary" target="_blank" download style="display: flex; align-items: center; gap: 8px; padding: 10px 20px; border: none; border-radius: 8px; background: var(--primary); color: white; text-decoration: none; cursor: pointer; font-weight: 500; transition: all 0.3s ease;">
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
                <h3>Remove Application</h3>
                <a href="/dashboard/load/application" class="modal-close">&times;</a>
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
                    <div class="modal-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p>Are you sure you want to remove this application?</p>
                    <p style="font-size: 0.9rem; color: var(--gray-600); margin-top: 10px;">
                        This action cannot be undone. You will need to re-apply if you change your mind.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="/dashboard/load/application" class="btn btn-secondary">Cancel</a>
                <a href="/dashboard/overview?remove_application_confirm=1&application_id=<?= $application['id'] ?>" class="btn btn-danger">
    <i class="fas fa-trash"></i> Yes, Remove Application
</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Enhanced Interview Modal with Google Calendar - Employer Only -->
    <?php if ($role == 'employer'): ?>
    <div class="modal" id="interviewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Schedule Interview</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="interviewForm">
                <input type="hidden" id="modalApplicationId">
                
                <!-- Interview Details -->
                <div class="filter-group">
                    <label for="interviewDate">Interview Date & Time *</label>
                    <input type="datetime-local" id="interviewDate" class="filter-select" required>
                </div>
                
                <div class="filter-group">
                    <label for="interviewDuration">Duration (minutes) *</label>
                    <select id="interviewDuration" class="filter-select" required>
                        <option value="30">30 minutes</option>
                        <option value="45">45 minutes</option>
                        <option value="60" selected>60 minutes</option>
                        <option value="90">90 minutes</option>
                        <option value="120">120 minutes</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="interviewType">Interview Type *</label>
                    <select id="interviewType" class="filter-select" required>
                        <option value="phone">Phone Interview</option>
                        <option value="video">Video Call</option>
                        <option value="in_person">In-Person</option>
                    </select>
                </div>
                
                <div class="filter-group" id="locationField">
                    <label for="interviewLocation">
                        <span id="locationLabel">Meeting Link / Location *</span>
                    </label>
                    <input type="text" id="interviewLocation" class="filter-select" 
                           placeholder="Enter meeting link or physical address">
                    <small id="locationHelp" class="form-help">
                        For video: Enter Zoom/Meet link. For in-person: Enter address.
                    </small>
                </div>
                
                <div class="filter-group">
                    <label for="interviewNotes">Interview Notes & Agenda</label>
                    <textarea id="interviewNotes" class="filter-select" 
                              placeholder="Add interview agenda, topics to cover, or special instructions..." 
                              rows="4"></textarea>
                </div>
                
                <!-- Google Calendar Integration -->
                <div class="filter-group">
                    <div class="calendar-option">
                        <input type="checkbox" id="addToCalendar" name="add_to_calendar" value="1" checked>
                        <label for="addToCalendar" class="checkbox-label">
                            <i class="fas fa-calendar-plus"></i>
                            Add to Google Calendar
                        </label>
                        <small class="form-help">
                            Creates a calendar event and sends invites to both parties
                        </small>
                    </div>
                </div>
                
                <!-- Calendar Preview (hidden by default) -->
                <div id="calendarPreview" class="calendar-preview" style="display: none;">
                    <h4>Calendar Preview</h4>
                    <div class="preview-content">
                        <p><strong>Event:</strong> <span id="previewTitle"></span></p>
                        <p><strong>When:</strong> <span id="previewTime"></span></p>
                        <p><strong>Where:</strong> <span id="previewLocation"></span></p>
                    </div>
                </div>

                <div class="filter-actions" style="margin-top: 25px; display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <i class="fas fa-calendar-plus"></i> Schedule Interview
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelInterview" style="flex: 1;">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
                
                <div id="calendarStatus" style="display: none; margin-top: 15px; padding: 10px; border-radius: 5px;"></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<!-- Interview Details Modal - Job Seeker Only -->
<?php if ($role == 'job_seeker'): ?>
<div class="modal" id="interviewDetailsModal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h3>Interview Details</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="interviewDetailsContent">
                <!-- Interview details will be loaded here via AJAX -->
                <div class="loading-spinner" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin fa-2x" style="color: var(--primary);"></i>
                    <p style="margin-top: 15px;">Loading interview details...</p>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn btn-secondary" id="closeInterviewDetails">
                <i class="fas fa-times"></i> Close
            </button>
            <a href="#" id="addToCalendarBtn" class="btn btn-primary" target="_blank" style="display: none;">
                <i class="fas fa-calendar-plus"></i> Add to Calendar
            </a>
        </div>
    </div>
</div>

    <?php endif; ?>

    <script>
        $(document).ready(function() {
            console.log('Page loaded - total application cards:', $('.application-card').length);
            
            // Mobile menu toggle
            $('.mobile-menu-toggle').on('click', function() {
                $('.sidebar').toggleClass('mobile-open');
            });

            // Close modal when clicking outside
            $(document).on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $(e.target).removeClass('active');
                    <?php if ($role == 'employer'): ?>
                    $('#interviewForm')[0].reset();
                    $('#calendarStatus').hide();
                    $('#calendarPreview').hide();
                    <?php endif; ?>
                }
            });

            // Escape key to close modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.modal').removeClass('active');
                    <?php if ($role == 'employer'): ?>
                    $('#interviewForm')[0].reset();
                    $('#calendarStatus').hide();
                    $('#calendarPreview').hide();
                    <?php endif; ?>
                }
            });

            <?php if ($role == 'employer'): ?>
            // Employer Filter Applications Function
            function filterEmployerApplications() {
                console.log('=== FILTERING EMPLOYER APPLICATIONS ===');
                
                const searchTerm = $('#applicantSearch').val().trim().toLowerCase();
                const jobTitleFilter = $('#jobTitleFilter').val().trim().toLowerCase();
                const dateFilter = $('#dateFilter').val();
                
                console.log('Employer Filters:', { 
                    searchTerm, 
                    jobTitleFilter, 
                    dateFilter 
                });

                let visibleCount = 0;
                const today = new Date();
                
                $('.application-card').each(function(index) {
                    const $card = $(this);
                    const searchText = ($card.attr('data-search-text') || '').toLowerCase();
                    const jobTitle = ($card.attr('data-job-title') || '').toLowerCase();
                    const applicationDate = $card.attr('data-application-date') || '';
                    
                    let shouldShow = true;

                    // Search filter
                    if (searchTerm && searchText.indexOf(searchTerm) === -1) {
                        shouldShow = false;
                    }

                    // Job title filter - text input version
                    if (shouldShow && jobTitleFilter) {
                        if (!jobTitle.includes(jobTitleFilter)) {
                            shouldShow = false;
                        }
                    }

                    // Date filter
                    if (shouldShow && dateFilter !== 'all' && applicationDate) {
                        const appDate = new Date(applicationDate);
                        const diffTime = today - appDate;
                        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        
                        switch(dateFilter) {
                            case 'today':
                                if (diffDays > 1) shouldShow = false;
                                break;
                            case 'week':
                                if (diffDays > 7) shouldShow = false;
                                break;
                            case 'month':
                                if (diffDays > 30) shouldShow = false;
                                break;
                        }
                    }

                    // Show/hide card
                    if (shouldShow) {
                        $card.removeClass('hidden');
                        visibleCount++;
                    } else {
                        $card.addClass('hidden');
                    }
                });

                // Update applications count
                updateApplicationsCount(visibleCount, searchTerm);

                // Show/hide no results message
                if (visibleCount === 0) {
                    showNoResults();
                } else {
                    hideNoResults();
                }
                
                console.log('Visible employer applications after filtering:', visibleCount);
            }

            <?php else: ?>
            // Job Seeker Filter Applications Function
            function filterApplications() {
                console.log('=== FILTERING JOB SEEKER APPLICATIONS ===');
                
                const searchTerm = $('#searchInput').val().trim().toLowerCase();
                const statusFilter = $('#statusFilter').val();
                
                console.log('Job Seeker Filters:', { searchTerm, statusFilter });

                let visibleCount = 0;
                
                $('.application-card').each(function(index) {
                    const $card = $(this);
                    const status = $card.attr('data-status') || '';
                    const searchText = ($card.attr('data-search-text') || '').toLowerCase();

                    let shouldShow = true;

                    // Status filter
                    if (statusFilter !== 'all') {
                        if (status !== statusFilter) {
                            shouldShow = false;
                        }
                    }

                    // Search filter
                    if (shouldShow && searchTerm) {
                        if (searchText.indexOf(searchTerm) === -1) {
                            shouldShow = false;
                        }
                    }

                    // Show/hide card
                    if (shouldShow) {
                        $card.removeClass('hidden');
                        visibleCount++;
                    } else {
                        $card.addClass('hidden');
                    }
                });

                // Update applications count
                updateApplicationsCount(visibleCount, searchTerm);

                // Show/hide no results message
                if (visibleCount === 0) {
                    showNoResults();
                } else {
                    hideNoResults();
                }
                
                console.log('Visible job seeker applications after filtering:', visibleCount);
            }
            <?php endif; ?>

            // Update applications count display
            function updateApplicationsCount(count, searchTerm) {
                let countText = count + ' application(s) found';
                if (searchTerm) {
                    countText += ' for "' + searchTerm + '"';
                }
                $('#applicationsCount').text(countText);
            }

            // Show no results message
            function showNoResults() {
                if ($('#noResultsMessage').length === 0) {
                    const noResultsHtml = `
                        <div class="no-data" id="noResultsMessage">
                            <i class="fas fa-file-alt"></i>
                            <h3>No applications found</h3>
                            <p>No applications match your current filters. Try adjusting your search criteria.</p>
                            <div style="margin-top: 20px;">
                                <?php if ($role == 'job_seeker'): ?>
                                <a href="/jobs" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Browse Jobs
                                </a>
                                <?php else: ?>
                                <a href="/jobs/create" class="btn btn-primary">
                                    <i class="fas fa-plus-circle"></i> Post a Job
                                </a>
                                <a href="/jobs" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Your Jobs
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    `;
                    $('#applicationsList').append(noResultsHtml);
                }
            }

            // Hide no results message
            function hideNoResults() {
                $('#noResultsMessage').remove();
            }

            <?php if ($role == 'employer'): ?>
            // Quick filter by clicking stats cards
            $('.stat-card[data-filter]').on('click', function() {
                const filter = $(this).attr('data-filter');
                console.log('Stat card clicked, filter:', filter);
                // For employer, we'll just update the URL to show filtered results
                window.location.href = '/dashboard/load/application?status=' + filter;
            });

            // Apply employer filters
            $('#applyEmployerFilters').on('click', function() {
                filterEmployerApplications();
            });

            // Clear employer filters
            $('#clearEmployerFilters').on('click', function() {
                $('#applicantSearch').val('');
                $('#jobTitleFilter').val('');
                $('#dateFilter').val('all');
                $('.stat-card').removeClass('active');
                
                // Show all applications
                $('.application-card').removeClass('hidden');
                
                // Update count to show all
                const totalCount = $('.application-card').length;
                updateApplicationsCount(totalCount, '');
                
                // Hide no results message
                hideNoResults();
                
                console.log('Employer filters cleared - showing all applications');
            });

            // Enhanced Interview Scheduling with Google Calendar
            $(document).on('click', '.schedule-interview-btn', function() {
                const applicationId = $(this).attr('data-application-id');
                $('#modalApplicationId').val(applicationId);
                
                // Set minimum date to today
                const today = new Date().toISOString().slice(0, 16);
                $('#interviewDate').attr('min', today);
                
                // Reset form
                $('#interviewForm')[0].reset();
                $('#addToCalendar').prop('checked', true);
                $('#calendarPreview').hide();
                $('#calendarStatus').hide();
                
                // Trigger location field update
                $('#interviewType').trigger('change');
                
                $('#interviewModal').addClass('active');
            });

            // Dynamic location field based on interview type
            $('#interviewType').on('change', function() {
                const type = $(this).val();
                const $locationField = $('#interviewLocation');
                const $locationLabel = $('#locationLabel');
                const $locationHelp = $('#locationHelp');
                
                switch(type) {
                    case 'video':
                        $locationLabel.text('Video Meeting Link *');
                        $locationField.attr('placeholder', 'https://zoom.us/j/... or https://meet.google.com/...');
                        $locationHelp.text('Enter Zoom, Google Meet, or other video conference link');
                        $('#locationField').show();
                        break;
                    case 'in_person':
                        $locationLabel.text('Physical Address *');
                        $locationField.attr('placeholder', 'Enter full address for in-person interview');
                        $locationHelp.text('Provide complete address with building and room number if applicable');
                        $('#locationField').show();
                        break;
                    case 'phone':
                        $locationLabel.text('Phone Number *');
                        $locationField.attr('placeholder', '+63 XXX XXX XXXX');
                        $locationHelp.text('Enter phone number for the interview call');
                        $('#locationField').show();
                        break;
                }
                
                updateCalendarPreview();
            });

            // Update calendar preview when fields change
            $('#interviewDate, #interviewDuration, #interviewType, #interviewLocation').on('change input', function() {
                updateCalendarPreview();
            });

            function updateCalendarPreview() {
                const interviewDate = $('#interviewDate').val();
                const duration = $('#interviewDuration').val();
                const type = $('#interviewType').val();
                const location = $('#interviewLocation').val();
                
                if (interviewDate) {
                    const startTime = new Date(interviewDate);
                    const endTime = new Date(startTime.getTime() + duration * 60000);
                    
                    $('#previewTitle').text(`Interview - ${type.charAt(0).toUpperCase() + type.slice(1)}`);
                    $('#previewTime').text(`${formatDateTime(startTime)} - ${formatTime(endTime)} (${duration} mins)`);
                    $('#previewLocation').text(location || 'To be determined');
                    
                    $('#calendarPreview').show();
                } else {
                    $('#calendarPreview').hide();
                }
            }

            function formatDateTime(date) {
                return date.toLocaleDateString('en-US', { 
                    weekday: 'short', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function formatTime(date) {
                return date.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit'
                });
            }

 // SIMPLIFIED interview form submission - NO LOADING STATE
$('#interviewForm').on('submit', function(e) {
    e.preventDefault();
    
    console.log('=== INTERVIEW FORM SUBMISSION STARTED ===');
    
    const applicationId = $('#modalApplicationId').val();
    const interviewDate = $('#interviewDate').val();
    const interviewType = $('#interviewType').val();
    const interviewNotes = $('#interviewNotes').val();
    const interviewLocation = $('#interviewLocation').val();
    const interviewDuration = $('#interviewDuration').val();
    
    const isReschedule = $('.modal-header h3').text().includes('Reschedule');
    
    console.log('Form data:', {
        applicationId, 
        interviewDate, 
        interviewType, 
        interviewLocation,
        interviewDuration,
        isReschedule
    });

    // Basic validation
    if (!applicationId || !interviewDate) {
        alert('Please fill in all required fields');
        return;
    }
    
    if ((interviewType === 'video' || interviewType === 'in_person' || interviewType === 'phone') && !interviewLocation) {
        alert('Please provide ' + (interviewType === 'video' ? 'meeting link' : interviewType === 'phone' ? 'phone number' : 'location'));
        return;
    }

    // Prepare data
    const formData = {
        application_id: applicationId,
        interview_date: interviewDate,
        interview_type: interviewType,
        interview_notes: interviewNotes,
        interview_location: interviewLocation,
        interview_duration: interviewDuration,
        is_reschedule: isReschedule ? 1 : 0
    };
    
    console.log('Sending AJAX request to /dashboard/schedule_interview');

    // Send AJAX request - NO LOADING STATE
    $.ajax({
        url: '/dashboard/schedule_interview',
        type: 'POST',
        data: formData,
        dataType: 'json',
        timeout: 10000, // 10 second timeout
        success: function(response) {
            console.log('AJAX Success:', response);
            
            if (response && response.success) {
                const action = isReschedule ? 'rescheduled' : 'scheduled';
                
                // Close modal immediately
                $('#interviewModal').removeClass('active');
                $('#interviewForm')[0].reset();
                $('.modal-header h3').text('Schedule Interview');
                $('#calendarStatus').hide();
                $('#calendarPreview').hide();
                
                // Show success message using your existing notification system
                // This will be handled by the session message on page reload
                
                // Redirect to applications page to show the success message
                window.location.href = '/application';
                
            } else {
                const errorMsg = response ? response.message : 'Unknown error occurred';
                alert('Error: ' + errorMsg);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            
            let errorMsg = 'An error occurred while ' + (isReschedule ? 'rescheduling' : 'scheduling') + ' the interview. ';
            
            if (status === 'timeout') {
                errorMsg += 'Request timed out. Please try again.';
            } else if (xhr.status === 0) {
                errorMsg += 'Network error. Please check your connection.';
            } else {
                errorMsg += 'Error: ' + error;
            }
            
            alert(errorMsg);
        }
    });
});
            // Cancel interview modal
            $('#cancelInterview').on('click', function() {
                $('#interviewModal').removeClass('active');
                $('#interviewForm')[0].reset();
                $('#calendarStatus').hide();
                $('#calendarPreview').hide();
            });

            // Reject application - Form submission approach
            $(document).on('click', '.reject-application-btn', function(e) {
                e.preventDefault();
                
                const applicationId = $(this).attr('data-application-id');
                const button = $(this);
                
                // Show loading state
                const originalText = button.html();
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Rejecting...');

                // Create and submit a form to the reject_application endpoint
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/dashboard/reject_application';
                
                const applicationIdInput = document.createElement('input');
                applicationIdInput.type = 'hidden';
                applicationIdInput.name = 'application_id';
                applicationIdInput.value = applicationId;
                
                form.appendChild(applicationIdInput);
                document.body.appendChild(form);
                form.submit();
            });

            // Hire applicant
            $(document).on('click', '.hire-applicant-btn', function() {
                const applicationId = $(this).attr('data-application-id');
                const button = $(this);
                
               
                    // Show loading state
                    const originalText = button.html();
                    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Hiring...');

                    $.ajax({
                        url: '/dashboard/hire_applicant',
                        type: 'POST',
                        data: { application_id: applicationId },
                        dataType: 'json',
                        success: function(response) {
                            if (response && response.success) {
                                
                                location.reload();
                            } else {
                                alert('Error: ' + (response ? response.message : 'Unknown error'));
                                button.prop('disabled', false).html(originalText);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', error);
                            alert('An error occurred while hiring the applicant. Please try again.');
                            button.prop('disabled', false).html(originalText);
                        }
                    });
                
            });

            // Contact applicant
            $(document).on('click', '.contact-applicant-btn', function() {
                const email = $(this).attr('data-email');
                if (email) {
                    window.location.href = 'mailto:' + email;
                } else {
                    alert('No email address available for this applicant.');
                }
            });

            // Enter key support for employer search filters
            $('#applicantSearch, #jobTitleFilter').on('keypress', function(e) {
                if (e.which === 13) { // Enter key
                    filterEmployerApplications();
                }
            });

            <?php else: ?>
            // Job Seeker Event handlers for filters
            $('#applyFilters').on('click', function() {
                filterApplications();
            });

            // Clear job seeker filters
            $('#clearFilters').on('click', function() {
                $('#searchInput').val('');
                $('#statusFilter').val('all');
                
                // Show all applications
                $('.application-card').removeClass('hidden');
                
                // Update count to show all
                const totalCount = $('.application-card').length;
                updateApplicationsCount(totalCount, '');
                
                // Hide no results message
                hideNoResults();
                
                console.log('Job seeker filters cleared - showing all applications');
            });

           // View Interview Details for Job Seeker
$(document).on('click', '.view-interview-details-btn', function() {
    const applicationId = $(this).attr('data-application-id');
    console.log('Loading interview details for application:', applicationId);
    
    // Show loading state
    $('#interviewDetailsContent').html(`
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 15px;">Loading interview details...</p>
        </div>
    `);
    
    // Hide calendar button initially
    $('#addToCalendarBtn').hide();
    
    // Show modal
    $('#interviewDetailsModal').addClass('active');
    
    // Load interview details via AJAX - FIXED: using GET parameters instead of URL parameter
    $.ajax({
        url: '/dashboard/get_interview_details',
        type: 'GET',
        data: { 
            application_id: applicationId 
        },
        dataType: 'json',
        success: function(response) {
            console.log('Interview details response:', response);
            
            if (response.success && response.interview) {
                const interview = response.interview;
                displayInterviewDetails(interview);
            } else {
                showInterviewError(response.message || 'Failed to load interview details');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading interview details:', {
                status: status,
                error: error,
                responseText: xhr.responseText
            });
            
            let errorMsg = 'An error occurred while loading interview details. ';
            
            // Try to get more specific error message
            try {
                const errorResponse = JSON.parse(xhr.responseText);
                if (errorResponse && errorResponse.message) {
                    errorMsg = errorResponse.message;
                }
            } catch (e) {
                // If we can't parse JSON, use generic message
                if (xhr.status === 404) {
                    errorMsg = 'Interview details not found.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please try again later.';
                }
            }
            
            showInterviewError(errorMsg);
        }
    });
});
            // Display interview details in modal
            function displayInterviewDetails(interview) {
                let html = '';
                
                // Job Information
                html += `
                    <div class="job-info-card">
                        <h4>Job Information</h4>
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">Position</div>
                                <div class="interview-detail-value">${escapeHtml(interview.job_title || 'N/A')}</div>
                            </div>
                        </div>
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">Company</div>
                                <div class="interview-detail-value">${escapeHtml(interview.company || 'N/A')}</div>
                            </div>
                        </div>
                    </div>
                `;

                // Interview Details
                html += `
                    <div class="interview-details-section">
                        <h4 style="margin-bottom: 15px; color: var(--dark);">Interview Details</h4>
                `;

                // Interview Date & Time
                if (interview.interview_date) {
                    const interviewDate = new Date(interview.interview_date);
                    const formattedDate = interviewDate.toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    const formattedTime = interviewDate.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    html += `
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="far fa-calendar"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">Date & Time</div>
                                <div class="interview-detail-value">${formattedDate} at ${formattedTime}</div>
                            </div>
                        </div>
                    `;
                }

                // Interview Type
                if (interview.interview_type) {
                    const typeMap = {
                        'phone': 'Phone Interview',
                        'video': 'Video Call',
                        'in_person': 'In-Person'
                    };
                    const typeText = typeMap[interview.interview_type] || interview.interview_type;
                    
                    html += `
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">Interview Type</div>
                                <div class="interview-detail-value">${typeText}</div>
                            </div>
                        </div>
                    `;
                }

                // Interview Location/Contact
                if (interview.interview_location) {
                    let locationIcon = 'fas fa-map-marker-alt';
                    let locationLabel = 'Location';
                    
                    if (interview.interview_type === 'phone') {
                        locationIcon = 'fas fa-phone';
                        locationLabel = 'Phone Number';
                    } else if (interview.interview_type === 'video') {
                        locationIcon = 'fas fa-video';
                        locationLabel = 'Meeting Link';
                    }
                    
                    html += `
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="${locationIcon}"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">${locationLabel}</div>
                                <div class="interview-detail-value">${escapeHtml(interview.interview_location)}</div>
                            </div>
                        </div>
                    `;
                }

                // Interview Duration
                if (interview.interview_duration) {
                    html += `
                        <div class="interview-detail-item">
                            <div class="interview-detail-icon">
                                <i class="far fa-clock"></i>
                            </div>
                            <div class="interview-detail-content">
                                <div class="interview-detail-label">Duration</div>
                                <div class="interview-detail-value">${interview.interview_duration} minutes</div>
                            </div>
                        </div>
                    `;
                }

                html += `</div>`; // Close interview-details-section

                // Interview Notes
                if (interview.interview_notes) {
                    html += `
                        <div class="interview-notes">
                            <h4>Interview Notes & Agenda</h4>
                            <div class="interview-notes-content">${escapeHtml(interview.interview_notes)}</div>
                        </div>
                    `;
                }

                // Company Contact Information
                html += `
                    <div class="company-contact">
                        <h4>Contact Information</h4>
                        <div class="contact-item">
                            <i class="fas fa-user"></i>
                            <span>Employer: ${escapeHtml(interview.employer_name || 'N/A')}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>Email: ${escapeHtml(interview.employer_email || 'N/A')}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Location: ${escapeHtml(interview.job_location || 'N/A')}</span>
                        </div>
                    </div>
                `;

                // Google Calendar Link
                if (interview.calendar_link) {
                    $('#addToCalendarBtn')
                        .attr('href', interview.calendar_link)
                        .show();
                } else {
                    $('#addToCalendarBtn').hide();
                }

                $('#interviewDetailsContent').html(html);
            }

            // Show error in interview details modal
            function showInterviewError(message) {
                $('#interviewDetailsContent').html(`
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Unable to Load Details</h3>
                        <p>${message}</p>
                    </div>
                `);
                $('#addToCalendarBtn').hide();
            }

            // Close interview details modal
            $('#closeInterviewDetails').on('click', function() {
                $('#interviewDetailsModal').removeClass('active');
            });

            // Utility function to escape HTML
            function escapeHtml(unsafe) {
                if (unsafe === null || unsafe === undefined) return 'N/A';
                return unsafe
                    .toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
            <?php endif; ?>

            // Close sidebar when clicking outside on mobile
            $(document).on('click', function(e) {
                if ($(window).width() <= 768) {
                    if (!$(e.target).closest('.sidebar').length && !$(e.target).closest('.mobile-menu-toggle').length) {
                        $('.sidebar').removeClass('mobile-open');
                    }
                }
            });

            // Initialize - show all applications, no auto-filtering
            console.log('Initializing - showing all applications');
            const totalCount = $('.application-card').length;
            updateApplicationsCount(totalCount, '');
            
            // Ensure all applications are visible on load
            $('.application-card').removeClass('hidden');
        });

        // Reschedule Interview - Employer Only
$(document).on('click', '.reschedule-interview-btn', function() {
    const applicationId = $(this).attr('data-application-id');
    console.log('Rescheduling interview for application:', applicationId);
    
    // First, get the current interview details
    $.ajax({
        url: '/dashboard/get_interview_details_for_reschedule',
        type: 'GET',
        data: { 
            application_id: applicationId 
        },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.interview) {
                // Populate the modal with existing data
                populateRescheduleModal(response.interview);
            } else {
                alert('Error: ' + (response.message || 'Failed to load interview details'));
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading interview details:', error);
            alert('An error occurred while loading interview details.');
        }
    });
});

// Populate reschedule modal with existing data
function populateRescheduleModal(interview) {
    console.log('Populating reschedule modal with:', interview);
    
    // Set the application ID
    $('#modalApplicationId').val(interview.id);
    
    // Populate form fields with existing data
    if (interview.interview_date) {
        // Convert datetime to local datetime format for input
        const interviewDate = new Date(interview.interview_date);
        const localDateTime = interviewDate.toISOString().slice(0, 16);
        $('#interviewDate').val(localDateTime);
    }
    
    if (interview.interview_type) {
        $('#interviewType').val(interview.interview_type);
    }
    
    if (interview.interview_duration) {
        $('#interviewDuration').val(interview.interview_duration);
    }
    
    if (interview.interview_location) {
        $('#interviewLocation').val(interview.interview_location);
    }
    
    if (interview.interview_notes) {
        $('#interviewNotes').val(interview.interview_notes);
    }
    
    // Update location field based on type
    $('#interviewType').trigger('change');
    
    // Show the modal with reschedule title
    $('.modal-header h3').text('Reschedule Interview');
    $('#interviewModal').addClass('active');
}

// Reset modal title when closing
$('.modal-close, #cancelInterview').on('click', function() {
    $('.modal-header h3').text('Schedule Interview'); // Reset to default title
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


// Enhanced displayInterviewDetails function with calendar and map
function displayInterviewDetails(interview) {
    let html = '';
    
    // Countdown timer section
    if (interview.interview_date) {
        const interviewDate = new Date(interview.interview_date);
        const now = new Date();
        const timeDiff = interviewDate - now;
        
        if (timeDiff > 0) {
            const days = Math.floor(timeDiff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
            
            html += `
                <div class="interview-countdown">
                    <div class="countdown-title">Interview in</div>
                    <div class="countdown-timer">
                        ${days}<span class="countdown-unit">days</span> 
                        ${hours}<span class="countdown-unit">hrs</span> 
                        ${minutes}<span class="countdown-unit">min</span>
                    </div>
                </div>
            `;
        }
    }

    // Calendar highlight section
    if (interview.interview_date) {
        const interviewDate = new Date(interview.interview_date);
        html += generateCalendarHighlight(interviewDate);
    }

    // Job Information
    html += `
        <div class="job-info-card">
            <h4>Job Information</h4>
            <div class="interview-detail-item">
                <div class="interview-detail-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="interview-detail-content">
                    <div class="interview-detail-label">Position</div>
                    <div class="interview-detail-value">${escapeHtml(interview.job_title || 'N/A')}</div>
                </div>
            </div>
            <div class="interview-detail-item">
                <div class="interview-detail-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="interview-detail-content">
                    <div class="interview-detail-label">Company</div>
                    <div class="interview-detail-value">${escapeHtml(interview.company || 'N/A')}</div>
                </div>
            </div>
        </div>
    `;

    // Interview Timeline
    html += `
        <div class="interview-timeline">
            <h4 style="margin-bottom: 15px; color: var(--dark);">Interview Schedule</h4>
    `;

    // Interview Date & Time
    if (interview.interview_date) {
        const interviewDate = new Date(interview.interview_date);
        const formattedDate = interviewDate.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const formattedTime = interviewDate.toLocaleTimeString('en-US', {
            hour: '2-digit',
            minute: '2-digit'
        });
        
        html += `
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="far fa-calendar"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-label">Date & Time</div>
                    <div class="timeline-value">${formattedDate} at ${formattedTime}</div>
                </div>
            </div>
        `;
    }

    // Interview Type
    if (interview.interview_type) {
        const typeMap = {
            'phone': 'Phone Interview',
            'video': 'Video Call',
            'in_person': 'In-Person'
        };
        const typeText = typeMap[interview.interview_type] || interview.interview_type;
        
        html += `
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="fas fa-video"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-label">Interview Type</div>
                    <div class="timeline-value">${typeText}</div>
                </div>
            </div>
        `;
    }

    // Interview Duration
    if (interview.interview_duration) {
        html += `
            <div class="timeline-item">
                <div class="timeline-icon">
                    <i class="far fa-clock"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-label">Duration</div>
                    <div class="timeline-value">${interview.interview_duration} minutes</div>
                </div>
            </div>
        `;
    }

    html += `</div>`; // Close interview-timeline

    // Google Map for location
    if (interview.interview_location && (interview.interview_type === 'in_person' || interview.interview_type === 'video')) {
        html += `
            <div class="interview-map-container">
                <h4 style="margin-bottom: 10px; color: var(--dark);">Location</h4>
                <div id="interviewMap" class="interview-map"></div>
                <div style="padding: 12px; background: var(--gray-50); border-top: 1px solid var(--gray-200);">
                    <strong>Address:</strong> ${escapeHtml(interview.interview_location)}
                </div>
            </div>
        `;
    } else if (interview.interview_location && interview.interview_type === 'phone') {
        html += `
            <div class="interview-detail-item">
                <div class="interview-detail-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="interview-detail-content">
                    <div class="interview-detail-label">Phone Number</div>
                    <div class="interview-detail-value">${escapeHtml(interview.interview_location)}</div>
                </div>
            </div>
        `;
    }

    // Interview Notes
    if (interview.interview_notes) {
        html += `
            <div class="interview-notes">
                <h4>Interview Notes & Agenda</h4>
                <div class="interview-notes-content">${escapeHtml(interview.interview_notes)}</div>
            </div>
        `;
    }

    // Company Contact Information
    html += `
        <div class="company-contact">
            <h4>Contact Information</h4>
            <div class="contact-item">
                <i class="fas fa-user"></i>
                <span>Employer: ${escapeHtml(interview.employer_name || 'N/A')}</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>Email: ${escapeHtml(interview.employer_email || 'N/A')}</span>
            </div>
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <span>Location: ${escapeHtml(interview.job_location || 'N/A')}</span>
            </div>
        </div>
    `;

    $('#interviewDetailsContent').html(html);

    // Initialize map if location exists
    if (interview.interview_location && (interview.interview_type === 'in_person' || interview.interview_type === 'video')) {
        setTimeout(() => {
            initializeInterviewMap(interview.interview_location);
        }, 100);
    }

    // Google Calendar Link
    if (interview.calendar_link) {
        $('#addToCalendarBtn')
            .attr('href', interview.calendar_link)
            .show();
    } else {
        $('#addToCalendarBtn').hide();
    }
}

// Generate calendar highlight
function generateCalendarHighlight(interviewDate) {
    const today = new Date();
    const year = interviewDate.getFullYear();
    const month = interviewDate.getMonth();
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDay = firstDay.getDay();
    
    const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
    ];
    
    let calendarHTML = `
        <div class="calendar-highlight">
            <div class="calendar-header">
                <div class="month-year">${monthNames[month]} ${year}</div>
                <div class="interview-date">Interview: ${interviewDate.toLocaleDateString()}</div>
            </div>
            <div class="calendar-grid">
    `;
    
    // Day headers
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(day => {
        calendarHTML += `<div class="calendar-day-header">${day}</div>`;
    });
    
    // Empty cells for days before the first day of the month
    for (let i = 0; i < startingDay; i++) {
        calendarHTML += `<div class="calendar-day other-month"></div>`;
    }
    
    // Days of the month
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, month, day);
        let dayClass = 'calendar-day';
        
        // Check if today
        if (currentDate.toDateString() === today.toDateString()) {
            dayClass += ' today';
        }
        
        // Check if interview day
        if (currentDate.toDateString() === interviewDate.toDateString()) {
            dayClass += ' interview-day';
        }
        
        calendarHTML += `<div class="${dayClass}">${day}</div>`;
    }
    
    calendarHTML += `
            </div>
        </div>
    `;
    
    return calendarHTML;
}

// Initialize interview location map
function initializeInterviewMap(location) {
    const mapElement = document.getElementById('interviewMap');
    if (!mapElement) return;
    
    // Default coordinates for Philippines
    let defaultCoords = [12.8797, 121.7740];
    let zoomLevel = 12;
    
    // Try to geocode the location
    geocodeLocation(location).then(coords => {
        const map = L.map('interviewMap').setView(coords, zoomLevel);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add a custom icon for interview location
        const interviewIcon = L.divIcon({
            html: '<i class="fas fa-map-marker-alt" style="color: #e63946; font-size: 24px;"></i>',
            iconSize: [24, 24],
            className: 'interview-marker'
        });
        
        // Add marker for interview location
        L.marker(coords, {icon: interviewIcon})
            .addTo(map)
            .bindPopup(`<strong>Interview Location</strong><br>${location}`)
            .openPopup();
            
    }).catch(error => {
        console.error('Geocoding error:', error);
        // Fallback to default coordinates with message
        mapElement.innerHTML = `
            <div class="map-placeholder">
                <i class="fas fa-map-marker-alt"></i>
                <p>Unable to load map</p>
                <small>Location: ${location}</small>
            </div>
        `;
    });
}

// Simple geocoding function
function geocodeLocation(location) {
    return new Promise((resolve, reject) => {
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

// Update the view interview details button click handler
$(document).on('click', '.view-interview-details-btn', function() {
    const applicationId = $(this).attr('data-application-id');
    console.log('Loading interview details for application:', applicationId);
    
    // Show loading state
    $('#interviewDetailsContent').html(`
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top: 15px;">Loading interview details...</p>
        </div>
    `);
    
    // Hide calendar button initially
    $('#addToCalendarBtn').hide();
    
    // Show modal
    $('#interviewDetailsModal').addClass('active');
    
    // Load interview details via AJAX
    $.ajax({
        url: '/dashboard/get_interview_details',
        type: 'GET',
        data: { 
            application_id: applicationId 
        },
        dataType: 'json',
        success: function(response) {
            console.log('Interview details response:', response);
            
            if (response.success && response.interview) {
                displayInterviewDetails(response.interview);
            } else {
                showInterviewError(response.message || 'Failed to load interview details');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading interview details:', error);
            showInterviewError('An error occurred while loading interview details.');
        }
    });
});
    </script>
</body>
</html>