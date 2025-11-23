<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
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

        .nav-btn.active {
            background: rgba(255, 255, 255, 0.3);
        }

        .dashboard-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
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
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
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
            flex-wrap: wrap;
            gap: 15px;
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
            background: var(--gray-100);
            font-weight: 600;
            color: var(--gray-700);
            position: sticky;
            top: 0;
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
            display: inline-block;
        }

        .status-badge.admin {
            background: rgba(114, 9, 183, 0.15);
            color: var(--secondary);
            border: 1px solid rgba(114, 9, 183, 0.3);
        }

        .status-badge.employer {
            background: rgba(67, 97, 238, 0.15);
            color: var(--primary);
            border: 1px solid rgba(67, 97, 238, 0.3);
        }

        .status-badge.job_seeker {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .status-badge.active {
            background: rgba(76, 201, 240, 0.15);
            color: var(--success);
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .status-badge.suspended {
            background: rgba(230, 57, 70, 0.15);
            color: var(--danger);
            border: 1px solid rgba(230, 57, 70, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            font-size: 0.8rem;
            display: inline-flex;
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
            transform: translateY(-2px);
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #e6166a;
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #3ab8d8;
            transform: translateY(-2px);
        }

        .btn-info {
            background: var(--info);
            color: white;
        }

        .btn-info:hover {
            background: #3a84d6;
            transform: translateY(-2px);
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid var(--gray-300);
            color: var(--gray-700);
            transition: var(--transition);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px);
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
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .search-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .search-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
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

        .no-data h3 {
            margin-bottom: 10px;
            color: var(--gray-600);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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

        .user-you {
            background: rgba(67, 97, 238, 0.1);
            border-left: 3px solid var(--primary);
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
                display: block;
                overflow-x: auto;
            }

            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            .search-input {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-content">
            <div>
                <h1><i class="fas fa-shield-alt"></i> User Management</h1>
                <p>Manage all users on the HireTech platform</p>
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
                <h3><?php echo $total_users; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($users, function($user) { return $user['role'] === 'employer'; })); ?></h3>
                <p>Employers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($users, function($user) { return $user['role'] === 'job_seeker'; })); ?></h3>
                <p>Job Seekers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($users, function($user) { return $user['role'] === 'admin'; })); ?></h3>
                <p>Admins</p>
            </div>
        </div>

        <!-- Users Table -->
        <div class="admin-section">
            <div class="section-header">
                <h3>All Users</h3>
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search users by name or email" id="searchInput">
                    <button class="search-btn" id="searchBtn">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <button class="search-btn" id="resetSearch" style="background: var(--gray-600); display: none;">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>

            <?php if (!empty($users)): ?>
                <table class="data-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="<?php echo ($user['id'] == $_SESSION['user']['id']) ? 'user-you' : ''; ?>">
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                    <?php if ($user['id'] == $_SESSION['user']['id']): ?>
                                        <span style="color: var(--primary); font-size: 0.8rem; display: block;">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
    <?php if ($user['id'] != $_SESSION['user']['id']): ?>
       
        
        
         <a href="/admin/users?action=delete&id=<?php echo $user['id']; ?>" 
           class="btn btn-danger btn-sm"
           title="Delete User">
            <i class="fas fa-trash"></i> Delete
        </a>
   
        
    <?php endif; ?>
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
                            <a href="/admin/users?page=<?php echo $current_page - 1; ?>">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $current_page): ?>
                                <span class="current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="/admin/users?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <a href="/admin/users?page=<?php echo $current_page + 1; ?>">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-users"></i>
                    <h3>No Users Found</h3>
                    <p>There are no users registered on the platform yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const resetBtn = document.getElementById('resetSearch');
            const usersTable = document.getElementById('usersTable');

            function performSearch() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                
                if (!usersTable) return;
                
                const rows = usersTable.querySelectorAll('tbody tr');
                let hasResults = false;
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                        hasResults = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Show/hide reset button
                if (searchTerm) {
                    resetBtn.style.display = 'flex';
                } else {
                    resetBtn.style.display = 'none';
                }
                
                // Show no results message
                const existingNoResults = document.getElementById('noResultsMessage');
                if (existingNoResults) {
                    existingNoResults.remove();
                }
                
                if (!hasResults && searchTerm) {
                    const noResults = document.createElement('tr');
                    noResults.id = 'noResultsMessage';
                    noResults.innerHTML = `
                        <td colspan="7" style="text-align: center; padding: 40px; color: var(--gray-500);">
                            <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            <h3>No users found</h3>
                            <p>No users match your search for "<strong>${searchTerm}</strong>"</p>
                        </td>
                    `;
                    usersTable.querySelector('tbody').appendChild(noResults);
                }
            }

            function resetSearch() {
                searchInput.value = '';
                resetBtn.style.display = 'none';
                
                if (usersTable) {
                    const rows = usersTable.querySelectorAll('tbody tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                    
                    const noResults = document.getElementById('noResultsMessage');
                    if (noResults) {
                        noResults.remove();
                    }
                }
            }

            // Event listeners
            searchBtn.addEventListener('click', performSearch);
            resetBtn.addEventListener('click', resetSearch);
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // Show reset button when typing
            searchInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    resetBtn.style.display = 'flex';
                } else {
                    resetBtn.style.display = 'none';
                }
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