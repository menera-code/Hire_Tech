<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Include all the CSS from users.php */
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
            font-size: 2rem;
        }

        .admin-header p {
            margin: 5px 0 0 0;
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            color: var(--dark);
            font-size: 1.8rem;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0;
            color: var(--primary);
        }

        .stat-card p {
            margin: 5px 0 0 0;
            color: var(--gray-600);
            font-weight: 500;
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

        .section-header h3 {
            margin: 0;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.4rem;
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

        .status-badge.active {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
        }

        .status-badge.featured {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
        }

        .status-badge.inactive {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
        }

        .job-type-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 500;
            background: var(--gray-200);
            color: var(--gray-700);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 0.75rem;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #d13446;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e6166a;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3ab8d8;
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #3a84d6;
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #61099e;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
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

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: white;
            border: 1px solid var(--gray-300);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .filter-btn:hover {
            border-color: var(--primary);
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .alert-error {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
            border: 1px solid rgba(230, 57, 70, 0.3);
        }

        .job-title {
            font-weight: 600;
            color: var(--dark);
        }

        .job-company {
            color: var(--gray-600);
            font-size: 0.9rem;
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

            .action-buttons {
                flex-direction: column;
            }

            .data-table {
                font-size: 0.9rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }

            .filter-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <div>
                <h1><i class="fas fa-briefcase"></i> Job Management</h1>
                <p>Manage all job listings on the HireTech platform</p>
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
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3><?php echo $total_jobs; ?></h3>
                <p>Total Jobs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($jobs, function($job) { return $job['job_type'] === 'Full-time'; })); ?></h3>
                <p>Full-time Jobs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($jobs, function($job) { return $job['job_type'] === 'Part-time'; })); ?></h3>
                <p>Part-time Jobs</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($jobs, function($job) { return $job['job_type'] === 'Remote'; })); ?></h3>
                <p>Remote Jobs</p>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="admin-section">
            <div class="section-header">
                <h3>All Job Listings</h3>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search jobs...">
                    <button class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <button class="filter-btn active" data-filter="all">All Jobs</button>
                <button class="filter-btn" data-filter="Full-time">Full-time</button>
                <button class="filter-btn" data-filter="Part-time">Part-time</button>
               
                <button class="filter-btn" data-filter="Remote">Remote</button>
                
            </div>

            <?php if (!empty($jobs)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Job Details</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Salary</th>
                            <th>Posted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr data-type="<?php echo $job['job_type']; ?>">
                                <td><?php echo $job['id']; ?></td>
                                <td>
                                    <div class="job-title"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($job['company']); ?></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td>
                                    <span class="job-type-badge"><?php echo $job['job_type']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($job['salary'] ?? 'Not specified'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo ($job['featured'] ?? 0) ? 'featured' : 'active'; ?>">
                                        <?php echo ($job['featured'] ?? 0) ? 'Featured' : 'Active'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
    
    <a href="/admin/jobs?action=delete&id=<?php echo $job['id']; ?>" 
       class="btn btn-danger btn-sm">
        <i class="fas fa-trash"></i> Delete
    </a>
</div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($current_page > 1): ?>
                            <a href="/admin/jobs?page=<?php echo $current_page - 1; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="/admin/jobs?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="/admin/jobs?page=<?php echo $current_page + 1; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-briefcase"></i>
                    <h3>No Jobs Found</h3>
                    <p>There are no job listings on the platform yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.querySelector('.search-btn').addEventListener('click', function() {
            const searchTerm = document.querySelector('.search-input').value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Search on enter key
        document.querySelector('.search-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.search-btn').click();
            }
        });

        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const rows = document.querySelectorAll('.data-table tbody tr');
                
                rows.forEach(row => {
                    if (filter === 'all' || row.dataset.type === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Auto-hide flash messages after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000); // 5 seconds
    });
});
    </script>
</body>
</html>