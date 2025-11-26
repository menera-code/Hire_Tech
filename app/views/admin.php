<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <!-- Google Analytics 4 -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        
        gtag('config', 'GA_MEASUREMENT_ID', {
            page_title: 'Admin Dashboard',
            page_location: window.location.href,
            user_id: 'admin_user' // Replace with actual admin user ID if available
        });
    </script>
    
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

        .admin-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px;
            margin-bottom: 30px;
        }

        .admin-header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 2.5rem;
        }

        .admin-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .admin-nav {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .admin-section {
            background: white;
            padding: 25px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 25px;
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

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            transition: var(--transition);
        }

        .activity-item:hover {
            background: var(--gray-50);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 4px;
        }

        .activity-meta {
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .activity-time {
            color: var(--gray-500);
            font-size: 0.8rem;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .data-table th {
            background: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
        }

        .data-table tr:hover {
            background: var(--gray-50);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: capitalize;
            white-space: nowrap;
        }

        .status-badge.admin {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        .status-badge.employer {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
        }

        .status-badge.job_seeker {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        .btn {
            padding: 8px 16px;
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

        @media (max-width: 768px) {
            .admin-header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .admin-nav {
                flex-wrap: wrap;
                justify-content: center;
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
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <div>
                <h1><i class="fas fa-shield-alt"></i> Admin Dashboard</h1>
                <p>Welcome, Administrator! Manage your HireTech platform efficiently</p>
            </div>
            <div class="admin-nav">
                <a href="/admin" class="nav-btn">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="/admin/users" class="nav-btn">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="/admin/jobs" class="nav-btn">
                    <i class="fas fa-briefcase"></i> Jobs
                </a>
                <a href="/admin/applications" class="nav-btn">
                    <i class="fas fa-file-alt"></i> Applications
                </a>
                <!-- Logout Button -->
                <a href="/admin/logout" class="nav-btn" style="background: rgba(255, 255, 255, 0.1);">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="dashboard-container">
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_users'] ?? 0; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_employers'] ?? 0; ?></h3>
                    <p>Employers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_job_seekers'] ?? 0; ?></h3>
                    <p>Job Seekers</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_jobs'] ?? 0; ?></h3>
                    <p>Active Jobs</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['total_applications'] ?? 0; ?></h3>
                    <p>Applications</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $stats['recent_users'] ?? 0; ?></h3>
                    <p>New Users (7 days)</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="/admin/users" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div>
                    <strong>Manage Users</strong>
                    <p>View and manage all users</p>
                </div>
            </a>
            <a href="/admin/jobs" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div>
                    <strong>Manage Jobs</strong>
                    <p>View and manage job listings</p>
                </div>
            </a>
            <a href="/admin/applications" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <strong>View Applications</strong>
                    <p>Monitor all job applications</p>
                </div>
            </a>
        </div>

        <!-- Recent Users -->
        <div class="admin-section">
            <div class="section-header">
                <h2>Recent Users</h2>
                <a href="/admin/users" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            
            <?php if (!empty($recent_users)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>User data will appear here once users register.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Jobs -->
        <div class="admin-section">
            <div class="section-header">
                <h2>Recent Jobs</h2>
                <a href="/admin/jobs" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View All
                </a>
            </div>
            
            <?php if (!empty($recent_jobs)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Posted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_jobs as $job): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><?php echo htmlspecialchars($job['company']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td><?php echo htmlspecialchars($job['job_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Found</h3>
                    <p>Job listings will appear here once employers post jobs.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Track admin dashboard load with enhanced data
            gtag('event', 'admin_dashboard_view', {
                total_users: <?php echo $stats['total_users'] ?? 0; ?>,
                total_employers: <?php echo $stats['total_employers'] ?? 0; ?>,
                total_job_seekers: <?php echo $stats['total_job_seekers'] ?? 0; ?>,
                total_jobs: <?php echo $stats['total_jobs'] ?? 0; ?>,
                total_applications: <?php echo $stats['total_applications'] ?? 0; ?>,
                recent_users: <?php echo $stats['recent_users'] ?? 0; ?>,
                platform: 'HireTech Admin'
            });

            // Track navigation menu clicks
            const navLinks = document.querySelectorAll('.nav-btn');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    const menuItem = this.textContent.trim();
                    const menuUrl = this.getAttribute('href');
                    
                    gtag('event', 'admin_navigation', {
                        menu_item: menuItem,
                        menu_url: menuUrl,
                        menu_category: 'main_navigation'
                    });
                });
            });

            // Track quick action clicks
            const quickActions = document.querySelectorAll('.quick-action-btn');
            quickActions.forEach(action => {
                action.addEventListener('click', function(e) {
                    const actionName = this.querySelector('strong').textContent;
                    const actionUrl = this.getAttribute('href');
                    const actionDescription = this.querySelector('p').textContent;
                    
                    gtag('event', 'admin_quick_action', {
                        action_name: actionName,
                        action_description: actionDescription,
                        action_url: actionUrl,
                        action_type: 'quick_action'
                    });
                });
            });

            // Track view all buttons
            const viewAllButtons = document.querySelectorAll('.btn-primary');
            viewAllButtons.forEach(button => {
                if (button.textContent.includes('View All')) {
                    button.addEventListener('click', function(e) {
                        const section = this.closest('.admin-section').querySelector('h2').textContent;
                        
                        gtag('event', 'admin_view_all_click', {
                            section: section,
                            button_text: this.textContent.trim(),
                            button_type: 'view_all'
                        });
                    });
                }
            });

            // Track stat card interactions (hover and clicks)
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const statTitle = this.querySelector('p').textContent;
                    const statValue = this.querySelector('h3').textContent;
                    
                    gtag('event', 'admin_stat_hover', {
                        stat_title: statTitle,
                        stat_value: statValue
                    });
                });
            });

            // Enhanced logout tracking
            const logoutBtn = document.querySelector('a[href="/admin/logout"]');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', function(e) {
                    // Track logout attempt
                    gtag('event', 'admin_logout_attempt', {
                        logout_method: 'button_click',
                        logout_location: 'dashboard'
                    });
                    
                    // Show confirmation dialog
                    if (!confirm('Are you sure you want to logout?')) {
                        e.preventDefault();
                        // Track logout cancellation
                        gtag('event', 'admin_logout_cancelled', {
                            cancellation_reason: 'user_choice'
                        });
                    } else {
                        // Track confirmed logout
                        gtag('event', 'admin_logout_confirmed', {
                            logout_time: new Date().toISOString()
                        });
                    }
                });
            }

            // Track table row interactions
            const tableRows = document.querySelectorAll('.data-table tr');
            tableRows.forEach((row, index) => {
                if (index > 0) { // Skip header row
                    row.addEventListener('click', function() {
                        const table = this.closest('table');
                        const section = this.closest('.admin-section').querySelector('h2').textContent;
                        const rowData = Array.from(this.cells).map(cell => cell.textContent.trim());
                        
                        gtag('event', 'admin_table_row_click', {
                            section: section,
                            row_data: rowData.slice(0, 2), // First two columns for context
                            row_index: index
                        });
                    });
                }
            });

            // Track page visibility changes (when admin switches tabs/windows)
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    gtag('event', 'admin_dashboard_refocus', {
                        time_away: 'unknown', // You could track this with more complex logic
                        refocus_time: new Date().toISOString()
                    });
                }
            });

            // Track time spent on dashboard (basic implementation)
            let startTime = new Date();
            window.addEventListener('beforeunload', function() {
                const endTime = new Date();
                const timeSpent = Math.round((endTime - startTime) / 1000); // seconds
                
                gtag('event', 'admin_session_duration', {
                    session_duration_seconds: timeSpent,
                    page: 'dashboard'
                });
            });
        });

        // Function to track custom admin events from other parts of the application
        function trackAdminEvent(eventName, eventParams) {
            gtag('event', eventName, {
                ...eventParams,
                admin_platform: 'HireTech',
                user_role: 'administrator'
            });
        }

        // Example usage for future enhancements:
        // trackAdminEvent('admin_bulk_action', { action: 'user_delete', count: 5 });
    </script>
</body>
</html>