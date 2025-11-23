<?php
// Get user data from session
$user = $_SESSION['user'] ?? [];
$role = $user['role'] ?? 'job_seeker';
$currentPage = $_SESSION['current_page'] ?? '';

// Get counts from session or set defaults
$total_applications = $_SESSION['total_applications'] ?? 0;
$saved_jobs = $_SESSION['saved_jobs'] ?? 0;
$total_jobs = $_SESSION['total_jobs'] ?? 0;

// Debug information
$debug_info = [
    'user_set' => !empty($user),
    'user_name' => $user['name'] ?? 'NOT SET',
    'user_role' => $role,
    'current_page' => $currentPage,
    'total_applications' => $total_applications,
    'saved_jobs' => $saved_jobs,
    'total_jobs' => $total_jobs
];
?>
<!-- Sticky Sidebar -->
<div class="sidebar">
    
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar">
                <?= substr(htmlspecialchars($user['name'] ?? 'User'), 0, 1) ?>
            </div>
            <div class="user-details">
                <h3><?= htmlspecialchars($user['name'] ?? 'User') ?></h3>
                <p><?= $role == 'job_seeker' ? 'Job Seeker' : 'Employer' ?></p>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-links">
            <!-- Dashboard -->
            <li class="nav-item <?= $currentPage == 'overview' ? 'active' : '' ?>">
                <a href="/dashboard" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <?php if ($role == 'job_seeker'): ?>
                <!-- Job Seeker Links -->
                <li class="nav-item <?= $currentPage == 'jobs' ? 'active' : '' ?>">
                    <a href="/jobs" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Browse Jobs</span>
                    </a>
                </li>

                <li class="nav-item <?= $currentPage == 'companies' ? 'active' : '' ?>">
                    <a href="/companies" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Browse Company</span>
                    </a>
                </li>
                
                <li class="nav-item <?= $currentPage == 'applications' ? 'active' : '' ?>">
                    <a href="/application" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>My Applications</span>
                       
                    </a>
                </li>
                
                <li class="nav-item <?= $currentPage == 'saved' ? 'active' : '' ?>">
                    <a href="/saved" class="nav-link">
                        <i class="fas fa-bookmark"></i>
                        <span>Saved Jobs</span>
                        
                    </a>
                </li>
                
                <li class="nav-item <?= $currentPage == 'profile' ? 'active' : '' ?>">
                    <a href="/profile" class="nav-link">
                        <i class="fas fa-user-edit"></i>
                        <span>My Profile</span>
                    </a>
                </li>

            <?php else: ?>
                <!-- Employer Links -->
                <li class="nav-item <?= $currentPage == 'jobs' ? 'active' : '' ?>">
                    <a href="/jobs" class="nav-link">
                        <i class="fas fa-briefcase"></i>
                        <span>My Job Posts</span>
                       
                    </a>
                </li>
                
                <li class="nav-item <?= $currentPage == 'applications' ? 'active' : '' ?>">
                    <a href="/application" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Applications</span>
                       
                    </a>
                </li>
                
                <li class="nav-item <?= $currentPage == 'company' ? 'active' : '' ?>">
                    <a href="/profile" class="nav-link">
                        <i class="fas fa-building"></i>
                        <span>Company Profile</span>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="/jobs" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Post New Job</span>
                    </a>
                </li>
                
            <?php endif; ?>
            
            <li class="nav-item">
                <a href="/logout" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<style>
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 280px;
    background: white;
    box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    overflow-y: auto;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 30px 25px 25px;
    border-bottom: 1px solid #f0f0f0;
    background: linear-gradient(135deg, #4361ee, #7209b7);
    color: white;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
}

.user-details h3 {
    margin: 0 0 5px 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.user-details p {
    margin: 0;
    opacity: 0.8;
    font-size: 0.9rem;
}

.sidebar-nav {
    padding: 20px 0;
}

.nav-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 5px;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 25px;
    color: #4b5563;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    background: #f8fafc;
    color: #4361ee;
    border-left-color: #4361ee;
}

.nav-item.active .nav-link {
    background: #f0f4ff;
    color: #4361ee;
    border-left-color: #4361ee;
    font-weight: 600;
}

.nav-link i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.nav-link .badge {
    margin-left: auto;
    background: #4361ee;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.nav-link.logout {
    color: #ef4444;
}

.nav-link.logout:hover {
    background: #fef2f2;
    color: #dc2626;
}

/* FIX: Main content area adjustment */
.main-content {
    margin-left: 280px;
    min-height: 100vh;
    background: #f5f7fb;
    padding: 0;
    width: calc(100% - 280px);
    position: relative;
    z-index: 1;
}

/* FIX: Dashboard container spacing */
.dashboard-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        width: 280px;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
        z-index: 1001;
    }
    
    .mobile-menu-toggle {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1002;
        background: #4361ee;
        color: white;
        border: none;
        border-radius: 8px;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.2rem;
    }
    
    /* FIX: Mobile main content */
    .main-content {
        margin-left: 0;
        padding-top: 70px;
        width: 100%;
    }
}

/* Ensure body has proper background */
body {
    background: #f5f7fb;
    margin: 0;
    padding: 0;
    overflow-x: hidden;
}
</style>

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
});
</script>