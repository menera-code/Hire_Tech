<?php
$user = $data['user'] ?? [];
$profile = $data['profile'] ?? [];
$company_profile = $data['company_profile'] ?? [];
$is_employer = $data['is_employer'] ?? false;
$stats = $data['stats'] ?? [];
$profile_completion = $data['profile_completion'] ?? 0;
$title = $data['title'] ?? 'Profile';

// Display success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - HireTech</title>
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

        /* Profile Completion */
        .completion-card {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
            border-left: 4px solid var(--success);
        }

        .completion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .completion-header h3 {
            margin: 0;
            color: var(--dark);
            font-size: 1.3rem;
        }

        .completion-percentage {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--success);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: var(--gray-200);
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), var(--info));
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Content Sections */
        .content-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
            margin-bottom: 30px;
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
            padding-bottom: 15px;
            border-bottom: 2px solid var(--gray-100);
        }

        .section-header h2 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h2 i {
            color: var(--primary);
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
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
            font-size: 1rem;
            transition: var(--transition);
            background: var(--gray-100);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            background: white;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        select.form-control {
            cursor: pointer;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload-input {
            position: absolute;
            left: -9999px;
        }

        .file-upload-label {
            display: block;
            padding: 20px;
            border: 2px dashed var(--gray-300);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--gray-100);
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            background: var(--gray-200);
        }

        .current-file {
            margin-top: 8px;
            font-size: 0.9rem;
            color: var(--gray-600);
            padding: 8px 12px;
            background: var(--gray-100);
            border-radius: 6px;
            border-left: 3px solid var(--success);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-info i {
            color: var(--success);
        }

        /* Map Styles */
        .map-container {
            margin-top: 15px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        #locationMap {
            height: 300px;
            width: 100%;
            border-radius: var(--border-radius);
        }

        .map-controls {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .btn-map {
            padding: 10px 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-weight: 500;
        }

        .btn-map:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-map:disabled {
            background: var(--gray-400);
            cursor: not-allowed;
            transform: none;
        }

        .btn-map.secondary {
            background: var(--gray-500);
        }

        .btn-map.secondary:hover {
            background: var(--gray-600);
        }

        .btn-map.success {
            background: var(--success);
        }

        .btn-map.success:hover {
            background: #3ab0d9;
        }

        .location-permission {
            margin-top: 10px;
            padding: 12px;
            background: rgba(76, 201, 240, 0.1);
            border-radius: 6px;
            border-left: 3px solid var(--success);
            font-size: 0.9rem;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .location-permission i {
            color: var(--success);
        }

        .location-error {
            margin-top: 10px;
            padding: 12px;
            background: rgba(230, 57, 70, 0.1);
            border-radius: 6px;
            border-left: 3px solid var(--danger);
            font-size: 0.9rem;
            color: var(--gray-700);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .location-error i {
            color: var(--danger);
        }

        /* Buttons */
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-block {
            display: block;
            width: 100%;
            justify-content: center;
        }

        .form-actions {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .section {
                padding: 20px;
            }

            .map-controls {
                flex-direction: column;
            }

            .btn-map {
                justify-content: center;
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

            <!-- Common Header -->
            <div class="header-section">
                <h1><?= htmlspecialchars($title) ?> 🚀</h1>
                <p class="subtitle">
                    <?php if ($is_employer): ?>
                        Manage your company information and hiring preferences
                    <?php else: ?>
                        Showcase your skills and experience to employers
                    <?php endif; ?>
                </p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <?php if ($is_employer): ?>
                    <!-- Employer Stats -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-briefcase"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['total_jobs'] ?? 0) ?></h3>
                            <p>Total Jobs Posted</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-rocket"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['active_jobs'] ?? 0) ?></h3>
                            <p>Active Jobs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['total_applications'] ?? 0) ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Job Seeker Stats -->
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['total_applications'] ?? 0) ?></h3>
                            <p>Applications Sent</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['interview_scheduled'] ?? 0) ?></h3>
                            <p>Interviews Scheduled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-bookmark"></i></div>
                        <div class="stat-info">
                            <h3><?= htmlspecialchars($stats['saved_jobs'] ?? 0) ?></h3>
                            <p>Saved Jobs</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Completion Section -->
            <div class="completion-card">
                <div class="completion-header">
                    <h3><i class="fas fa-tasks"></i> Profile Completion</h3>
                    <div class="completion-percentage"><?= $profile_completion ?>%</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $profile_completion ?>%"></div>
                </div>
                <p style="margin-top: 10px; color: var(--gray-600); font-size: 0.9rem;">
                    <?php if ($profile_completion < 50): ?>
                        <strong style="color: var(--danger);">Profile needs more information.</strong> Complete your profile to increase visibility.
                    <?php elseif ($profile_completion < 80): ?>
                        <strong style="color: var(--warning);">Good start!</strong> Add more details to improve your profile.
                    <?php elseif ($profile_completion < 100): ?>
                        <strong style="color: var(--info);">Almost there!</strong> Just a few more details needed.
                    <?php else: ?>
                        <strong style="color: var(--success);">Excellent! Your profile is complete.</strong>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Profile Form -->
            <div class="content-sections">
                <form method="POST" enctype="multipart/form-data" class="section">
                    <input type="hidden" name="action" value="<?= $is_employer ? 'update_company' : 'update_profile' ?>">

                    <!-- Basic Information Section -->
                    <div class="form-section">
                        <div class="section-header">
                            <h2><i class="fas fa-user"></i> Basic Information</h2>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars(($is_employer ? ($company_profile['phone'] ?? '') : ($profile['phone'] ?? ''))) ?>">
                            </div>
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" class="form-control" 
                                       placeholder="Leave blank to keep current password">
                                <small style="color: var(--gray-500); font-size: 0.8rem; margin-top: 5px; display: block;">
                                    Minimum 6 characters
                                </small>
                            </div>
                        </div>
                    </div>

                    <?php if ($is_employer): ?>
                        <!-- Company Information Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2><i class="fas fa-building"></i> Company Information</h2>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="company_name">Company Name *</label>
                                    <input type="text" id="company_name" name="company_name" class="form-control" 
                                           value="<?= htmlspecialchars($company_profile['company_name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="company_website">Website</label>
                                    <input type="url" id="company_website" name="company_website" class="form-control" 
                                           value="<?= htmlspecialchars($company_profile['company_website'] ?? '') ?>"
                                           placeholder="https://example.com">
                                </div>
                                <div class="form-group">
                                    <label for="company_size">Company Size</label>
                                    <select id="company_size" name="company_size" class="form-control">
                                        <option value="">Select Company Size</option>
                                        <option value="1-10" <?= ($company_profile['company_size'] ?? '') == '1-10' ? 'selected' : '' ?>>1-10 employees</option>
                                        <option value="11-50" <?= ($company_profile['company_size'] ?? '') == '11-50' ? 'selected' : '' ?>>11-50 employees</option>
                                        <option value="51-200" <?= ($company_profile['company_size'] ?? '') == '51-200' ? 'selected' : '' ?>>51-200 employees</option>
                                        <option value="201-500" <?= ($company_profile['company_size'] ?? '') == '201-500' ? 'selected' : '' ?>>201-500 employees</option>
                                        <option value="501+" <?= ($company_profile['company_size'] ?? '') == '501+' ? 'selected' : '' ?>>501+ employees</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="company_industry">Industry</label>
                                    <input type="text" id="company_industry" name="company_industry" class="form-control" 
                                           value="<?= htmlspecialchars($company_profile['company_industry'] ?? '') ?>"
                                           placeholder="e.g. Technology, Healthcare, Finance">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="company_description">Company Description</label>
                                <textarea id="company_description" name="company_description" class="form-control" 
                                          placeholder="Tell us about your company culture, mission, and values..."><?= htmlspecialchars($company_profile['company_description'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="company_address">Company Address</label>
                                <textarea id="company_address" name="company_address" class="form-control" 
                                          placeholder="Full company address including city and country"><?= htmlspecialchars($company_profile['company_address'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Company Location Map -->
                            <div class="form-group">
                                <label>Company Location</label>
                                <div class="location-permission">
                                    <i class="fas fa-info-circle"></i>
                                    Allow location access to automatically detect your company address
                                </div>
                                <div class="map-container">
                                    <div id="locationMap"></div>
                                </div>
                                <div class="map-controls">
                                    <button type="button" id="getLocationNow" class="btn-map success">
                                        <i class="fas fa-location-arrow"></i>
                                        Get Location Now
                                    </button>
                                    <button type="button" id="locateOnMap" class="btn-map">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Locate Address on Map
                                    </button>
                                    <button type="button" id="resetMap" class="btn-map secondary">
                                        <i class="fas fa-sync-alt"></i>
                                        Reset Map
                                    </button>
                                </div>
                                <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($company_profile['latitude'] ?? '') ?>">
                                <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($company_profile['longitude'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label>Company Logo</label>
                                <div class="file-upload">
                                    <input type="file" id="company_logo" name="company_logo" class="file-upload-input" 
                                           accept="image/*">
                                    <label for="company_logo" class="file-upload-label">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; margin-bottom: 10px; display: block; color: var(--primary);"></i>
                                        <span style="font-weight: 500;">Click to upload company logo</span>
                                        <br>
                                        <small style="color: var(--gray-500);">PNG, JPG, GIF up to 2MB</small>
                                    </label>
                                </div>
                                <?php if (!empty($company_profile['company_logo'])): ?>
                                    <div class="current-file">
                                        <div class="file-info">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Current logo: <?= htmlspecialchars(basename($company_profile['company_logo'])) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Job Seeker Information Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2><i class="fas fa-briefcase"></i> Professional Information</h2>
                            </div>
                            <div class="form-group">
                                <label for="headline">Professional Headline</label>
                                <input type="text" id="headline" name="headline" class="form-control" 
                                       value="<?= htmlspecialchars($profile['professional_headline'] ?? '') ?>" 
                                       placeholder="e.g. Senior Frontend Developer | React Specialist">
                            </div>
                            <div class="form-group">
                                <label for="bio">Professional Summary</label>
                                <textarea id="bio" name="bio" class="form-control" 
                                          placeholder="Describe your professional background, key achievements, and career objectives..."><?= htmlspecialchars($profile['professional_summary'] ?? '') ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="skills">Skills & Expertise</label>
                                <textarea id="skills" name="skills" class="form-control" 
                                          placeholder="List your key skills and technologies (separated by commas)
Example: JavaScript, React, Node.js, Python, UI/UX Design"><?= htmlspecialchars($profile['skills'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Experience & Education Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2><i class="fas fa-graduation-cap"></i> Experience & Education</h2>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="experience">Work Experience</label>
                                    <textarea id="experience" name="experience" class="form-control" 
                                              placeholder="Detail your work experience, including company names, positions, and key responsibilities..."><?= htmlspecialchars($profile['work_experience'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="education">Education</label>
                                    <textarea id="education" name="education" class="form-control" 
                                              placeholder="List your educational background, degrees, and certifications..."><?= htmlspecialchars($profile['education'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Resume Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2><i class="fas fa-file-pdf"></i> Resume</h2>
                            </div>
                            <div class="form-group">
                                <label for="resume">Upload Resume</label>
                                <div class="file-upload">
                                    <input type="file" id="resume" name="resume" class="file-upload-input" 
                                           accept=".pdf,.doc,.docx,.txt">
                                    <label for="resume" class="file-upload-label">
                                        <i class="fas fa-file-upload" style="font-size: 2rem; margin-bottom: 10px; display: block; color: var(--primary);"></i>
                                        <span style="font-weight: 500;">Click to upload your resume</span>
                                        <br>
                                        <small style="color: var(--gray-500);">PDF, DOC, DOCX, TXT up to 5MB</small>
                                    </label>
                                </div>
                                <?php if (!empty($profile['resume_file'])): ?>
                                    <div class="current-file">
                                        <div class="file-info">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Current resume: <?= htmlspecialchars(basename($profile['resume_file'])) ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Address Section -->
                        <div class="form-section">
                            <div class="section-header">
                                <h2><i class="fas fa-map-marker-alt"></i> Location</h2>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" class="form-control" 
                                          placeholder="Your current address (city, province, country)"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea>
                            </div>
                            
                            <!-- Location Map -->
                            <div class="form-group">
                                <label>Your Location</label>
                                <div class="location-permission">
                                    <i class="fas fa-info-circle"></i>
                                    Allow location access to automatically detect your current address
                                </div>
                                <div class="map-container">
                                    <div id="locationMap"></div>
                                </div>
                                <div class="map-controls">
                                    <button type="button" id="getLocationNow" class="btn-map success">
                                        <i class="fas fa-location-arrow"></i>
                                        Get Location Now
                                    </button>
                                    <button type="button" id="locateOnMap" class="btn-map">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Locate Address on Map
                                    </button>
                                    <button type="button" id="resetMap" class="btn-map secondary">
                                        <i class="fas fa-sync-alt"></i>
                                        Reset Map
                                    </button>
                                </div>
                                <input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($profile['latitude'] ?? '') ?>">
                                <input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($profile['longitude'] ?? '') ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Submit Button -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </div>
                </form>
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

            // File upload preview
            $('.file-upload-input').on('change', function() {
                var fileName = $(this).val().split('\\').pop();
                if (fileName) {
                    $(this).siblings('.file-upload-label').html(
                        '<i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 10px; display: block; color: var(--success);"></i>' +
                        '<span style="font-weight: 500;">' + fileName + '</span>' +
                        '<br><small style="color: var(--gray-500);">File selected</small>'
                    );
                }
            });

            // Form validation
            $('form').on('submit', function(e) {
                var isValid = true;
                var firstError = null;
                
                // Basic validation
                $('input[required]').each(function() {
                    if (!$(this).val().trim()) {
                        isValid = false;
                        $(this).css('border-color', 'var(--danger)');
                        if (!firstError) firstError = this;
                    } else {
                        $(this).css('border-color', '');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    if (firstError) {
                        $(firstError).focus();
                    }
                }
            });

            // Real-time validation for password
            $('#password').on('input', function() {
                var password = $(this).val();
                if (password.length > 0 && password.length < 6) {
                    $(this).css('border-color', 'var(--warning)');
                } else if (password.length >= 6) {
                    $(this).css('border-color', 'var(--success)');
                } else {
                    $(this).css('border-color', '');
                }
            });

            // Leaflet Map Implementation
            let map, marker;
            const defaultLat = <?= $is_employer ? ($company_profile['latitude'] ?? '51.505') : ($profile['latitude'] ?? '51.505') ?>;
            const defaultLng = <?= $is_employer ? ($company_profile['longitude'] ?? '-0.09') : ($profile['longitude'] ?? '-0.09') ?>;
            const addressFieldId = <?= $is_employer ? "'company_address'" : "'address'"?>;

            // Initialize map
            function initMap() {
                map = L.map('locationMap').setView([defaultLat, defaultLng], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // Add marker if coordinates exist
                if (defaultLat && defaultLng) {
                    marker = L.marker([defaultLat, defaultLng]).addTo(map)
                        .bindPopup('<?= $is_employer ? "Company Location" : "Your Location" ?>')
                        .openPopup();
                }

                // Add click event to map to set marker
                map.on('click', function(e) {
                    setMarker(e.latlng);
                    updateCoordinates(e.latlng.lat, e.latlng.lng);
                    reverseGeocode(e.latlng.lat, e.latlng.lng);
                });
            }

            // Set marker on map
            function setMarker(latlng) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker(latlng).addTo(map)
                    .bindPopup('<?= $is_employer ? "Company Location" : "Your Location" ?>')
                    .openPopup();
            }

            // Update coordinate fields
            function updateCoordinates(lat, lng) {
                $('#latitude').val(lat);
                $('#longitude').val(lng);
            }

            // Get current location using browser geolocation
            function getCurrentLocation() {
                if (!navigator.geolocation) {
                    showLocationError('Geolocation is not supported by this browser.');
                    return;
                }

                $('#getLocationNow').html('<i class="fas fa-spinner fa-spin"></i> Detecting...').prop('disabled', true);

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        
                        // Update map
                        map.setView([lat, lng], 15);
                        setMarker([lat, lng]);
                        updateCoordinates(lat, lng);
                        
                        // Reverse geocode to get address
                        reverseGeocode(lat, lng);
                        
                        $('#getLocationNow').html('<i class="fas fa-location-arrow"></i> Get Location Now').prop('disabled', false);
                    },
                    function(error) {
                        $('#getLocationNow').html('<i class="fas fa-location-arrow"></i> Get Location Now').prop('disabled', false);
                        
                        let errorMessage = 'Unable to retrieve your location. ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Please allow location access in your browser settings.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Location information is unavailable.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Location request timed out.';
                                break;
                            default:
                                errorMessage += 'An unknown error occurred.';
                        }
                        showLocationError(errorMessage);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            }

            // Reverse geocode coordinates to get address
            function reverseGeocode(lat, lng) {
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            const address = data.display_name;
                            $(`#${addressFieldId}`).val(address);
                            
                            // Show success message
                            showLocationSuccess('Location detected successfully! Address has been filled automatically.');
                        }
                    })
                    .catch(error => {
                        console.error('Reverse geocoding error:', error);
                    });
            }

            // Geocode address and update map
            function locateAddress() {
                const address = $(`#${addressFieldId}`).val().trim();
                
                if (!address) {
                    alert('Please enter an address first.');
                    return;
                }

                $('#locateOnMap').html('<i class="fas fa-spinner fa-spin"></i> Locating...').prop('disabled', true);

                // Use OpenStreetMap Nominatim for geocoding
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                    .then(response => response.json())
                    .then(data => {
                        $('#locateOnMap').html('<i class="fas fa-map-marker-alt"></i> Locate Address on Map').prop('disabled', false);
                        
                        if (data && data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lon = parseFloat(data[0].lon);
                            
                            // Update map view
                            map.setView([lat, lon], 15);
                            setMarker([lat, lon]);
                            updateCoordinates(lat, lon);
                            
                            showLocationSuccess('Address located successfully on map!');
                        } else {
                            showLocationError('Address not found. Please try a more specific address.');
                        }
                    })
                    .catch(error => {
                        $('#locateOnMap').html('<i class="fas fa-map-marker-alt"></i> Locate Address on Map').prop('disabled', false);
                        console.error('Geocoding error:', error);
                        showLocationError('Error locating address. Please try again.');
                    });
            }

            // Reset map to default position
            function resetMap() {
                map.setView([defaultLat, defaultLng], 13);
                
                if (marker) {
                    map.removeLayer(marker);
                    marker = null;
                }
                
                $('#latitude').val('');
                $('#longitude').val('');
                $(`#${addressFieldId}`).val('');
                
                // Clear any location messages
                $('.location-success, .location-error').remove();
            }

            // Show location success message
            function showLocationSuccess(message) {
                $('.location-error').remove();
                if (!$('.location-success').length) {
                    $('.map-controls').after(`<div class="location-permission location-success"><i class="fas fa-check-circle"></i> ${message}</div>`);
                } else {
                    $('.location-success').html(`<i class="fas fa-check-circle"></i> ${message}`);
                }
            }

            // Show location error message
            function showLocationError(message) {
                $('.location-success').remove();
                if (!$('.location-error').length) {
                    $('.map-controls').after(`<div class="location-error"><i class="fas fa-exclamation-triangle"></i> ${message}</div>`);
                } else {
                    $('.location-error').html(`<i class="fas fa-exclamation-triangle"></i> ${message}`);
                }
            }

            // Initialize map when page loads
            initMap();

            // Event listeners for map controls
            $('#getLocationNow').on('click', getCurrentLocation);
            $('#locateOnMap').on('click', locateAddress);
            $('#resetMap').on('click', resetMap);

            // Auto-locate when address field loses focus (if address is entered)
            $(`#${addressFieldId}`).on('blur', function() {
                if ($(this).val().trim() && !$('#latitude').val()) {
                    locateAddress();
                }
            });

            // Add keyboard shortcut for get location (Ctrl+G)
            $(document).on('keydown', function(e) {
                if (e.ctrlKey && e.key === 'g') {
                    e.preventDefault();
                    getCurrentLocation();
                }
            });
        });
    </script>
</body>
</html>