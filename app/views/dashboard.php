<?php
if (session_status() == PHP_SESSION_NONE) session_start();

require __DIR__ . '/../config/database.php';
try {
    $conn = new PDO(
        "mysql:host={$database['main']['hostname']};dbname={$database['main']['database']};charset={$database['main']['charset']}",
        $database['main']['username'],
        $database['main']['password']
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$user_id = $_SESSION['user']['id'];
$role    = $_SESSION['user']['role'];
$userName = $_SESSION['user']['name'];

if($role == 'job_seeker'){
    $jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    $totalApplications = $conn->query("SELECT COUNT(*) FROM applications WHERE user_id = $user_id")->fetchColumn();
    $interviewsScheduled = $conn->query("SELECT COUNT(*) FROM applications WHERE user_id = $user_id AND status='Interview Scheduled'")->fetchColumn();
    $jobsSaved = $conn->query("SELECT COUNT(*) FROM saved_jobs WHERE user_id = $user_id")->fetchColumn();
}
elseif($role == 'employer'){
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(['user_id'=>$user_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPosted = count($jobs);
    $applicationsReceived = $conn->prepare("SELECT COUNT(*) FROM applications WHERE job_id IN (SELECT id FROM jobs WHERE user_id=:user_id)");
    $applicationsReceived->execute(['user_id'=>$user_id]);
    $applicationsReceived = $applicationsReceived->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HireTech Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- ✅ Google Maps API -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA40K1kOwUEdegY6Y6ub0i2pzgsyT6sSJU"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<style>
* {margin:0; padding:0; box-sizing:border-box;}
body {
  font-family:'Poppins',sans-serif;
  background:#f8f9fb;
  color:#333;
}

/* Navbar */
.navbar {
  position:fixed;
  top:0; left:0;
  width:100%;
  background:rgba(13,110,253,0.9);
  backdrop-filter:blur(10px);
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:1rem 2rem;
  z-index:1000;
  color:#fff;
  box-shadow:0 4px 20px rgba(0,0,0,0.05);
}
.brand {
  font-size:1.4rem;
  font-weight:600;
}
.user-dropdown {position:relative;}
.user-btn {
  background: #fff;
  color: #0d6efd;
  border: none;
  padding:0.5rem 1rem;
  border-radius:0.5rem;
  cursor: pointer;
  transition:0.3s;
  font-size: 1rem;
}
.user-btn:hover {background:#e9f1ff;}
.dropdown-menu {
  display:none;
  position:absolute;
  right:0; top:120%;
  background:#fff;
  border-radius:0.5rem;
  box-shadow:0 4px 16px rgba(0,0,0,0.1);
  overflow:hidden;
  min-width:180px;
  z-index:100;
}
.dropdown-menu li {list-style:none;}
.dropdown-menu li a {
  display:block;
  padding:0.7rem 1rem;
  color:#333;
  text-decoration:none;
  transition:0.3s;
}
.dropdown-menu li a:hover {background:#f5f5f5;}
.user-dropdown.active .dropdown-menu {display:block;}

/* Layout */
.dashboard {
  display:flex;
  min-height:100vh;
  padding-top:70px;
}
.sidebar {
  width:250px;
  background:#fff;
  border-right:1px solid #eee;
  padding:1.5rem 1rem;
  transition:0.3s;
}
.sidebar a {
  display:block;
  color:#333;
  text-decoration:none;
  padding:0.8rem 1rem;
  border-radius:0.5rem;
  margin-bottom:0.4rem;
  font-weight:500;
  transition:0.3s;
}
.sidebar a:hover, .sidebar a.active {
  background:#0d6efd;
  color:#fff;
}
.sidebar-toggle {
  display:none;
  font-size:1.4rem;
  cursor:pointer;
}

/* Main */
.main {
  flex:1;
  padding:2rem;
}
h2 {color:#0d6efd; margin-bottom:0.5rem;}
.lead {font-size:1.1rem; margin-bottom:1.5rem;}
.grid {
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(260px,1fr));
  gap:1.5rem;
}
.card {
  background:#fff;
  border-radius:1rem;
  padding:1.5rem;
  box-shadow:0 4px 16px rgba(0,0,0,0.05);
  transition:0.3s;
}
.card:hover {
  transform:translateY(-4px);
  box-shadow:0 8px 24px rgba(0,0,0,0.08);
}
.card h5 {color:#0d6efd; margin-bottom:0.5rem;}
.card p {margin-bottom:0.4rem;}
.card a {
  display:inline-block;
  text-decoration:none;
  background:#0d6efd;
  color:#fff;
  padding:0.5rem 1rem;
  border-radius:0.4rem;
  font-size:0.9rem;
  transition:0.3s;
}
.card a:hover {background:#0b5ed7;}

/* Filter */
.filter-bar {
  display:flex;
  gap:0.5rem;
  margin-bottom:1.5rem;
  flex-wrap:wrap;
}
.filter-bar input {
  flex:1;
  padding:0.5rem 0.8rem;
  border:1px solid #ccc;
  border-radius:0.5rem;
}
.filter-bar button {
  padding:0.5rem 1rem;
  background:#0d6efd;
  border:none;
  color:#fff;
  border-radius:0.5rem;
  cursor:pointer;
  transition:0.3s;
}
.filter-bar button:hover {background:#0b5ed7;}

/* Summary */
.summary {
  margin-top:2rem;
  background:#fff;
  border-radius:1rem;
  padding:1.5rem;
  box-shadow:0 4px 16px rgba(0,0,0,0.05);
}
.summary ul {list-style:none;}
.summary li {margin-bottom:0.5rem;}

/* Responsive */
@media(max-width:900px){
  .sidebar {position:fixed; left:-260px; top:70px; height:100%; box-shadow:0 4px 16px rgba(0,0,0,0.1);}
  .sidebar.open {left:0;}
  .sidebar-toggle {display:block; color:#fff;}
}

/* Modal */
/* Modal */
.modal-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.4);
  display: none;
  justify-content: center;
  align-items: flex-start; /* align to top instead of center */
  z-index: 2000;
  padding: 1rem;
  overflow-y: auto; /* allow scrolling if modal is taller than viewport */
}

.modal-content {
  background: #fff;
  width: 90%;
  max-width: 900px;
  border-radius: 1rem;
  overflow: hidden;
  max-height: calc(100vh - 2rem); /* fit viewport */
  display: flex;
  flex-direction: column;
  margin-top: 1rem; /* small spacing from top */
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2); /* professional shadow */
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.modal-body h3 {
  color: #0d6efd;
  margin-bottom: 0.5rem;
}

.modal-body h4 {
  margin-top: 1rem;
  color: #0d6efd;
}

.modal-body p {
  margin-bottom: 0.8rem;
  color: #333;
}

.close-btn {
  align-self: flex-end;
  padding: 0.8rem 1rem;
  font-size: 1.5rem;
  cursor: pointer;
  color: #0d6efd;
  transition: 0.2s;
}

.close-btn:hover { color: #084298; }

.modal-actions {
  display: flex;
  justify-content: center;
  margin-top: 1rem;
}

#confirm-apply {
  background: #0d6efd;
  color: white;
  padding: 0.6rem 1.2rem;
  border-radius: 0.5rem;
  text-decoration: none;
  font-weight: 500;
}

#confirm-apply:hover { background: #0b5ed7; }

/* Modal grid */
.modal-body.modal-grid {
  display: flex;
  flex-direction: row;
  gap: 1.5rem;  /* more spacing */
  overflow-y: hidden;
}

.modal-details {
  flex: 2;          /* smaller width */
  overflow-y: auto;
}

.modal-map {
  flex: 3;          /* wider map for rectangular look */
  height: 350px;    /* slightly taller */
  border-radius: 0.5rem;
}

@media(max-width:768px){
  .modal-body.modal-grid { flex-direction: column; }
  .modal-map { height: 250px; margin-top: 1rem; }
}

</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
  <div class="brand">HireTech</div>
  <div class="sidebar-toggle" id="toggleSidebar">&#9776;</div>
  <div class="user-dropdown">
    <button class="user-btn"><?= htmlspecialchars($userName) ?></button>
    <ul class="dropdown-menu">
      <li><a href="settings.php">Profile Settings</a></li>
      <li><a href="../">Logout</a></li>
    </ul>
  </div>
</nav>

<div class="dashboard">
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <a href="dashboard.php" class="active">Dashboard Home</a>
    <?php if($role=='job_seeker'): ?>
        <a href="jobs.php">Job Listings</a>
        <a href="applications.php">My Applications</a>
        <a href="messages.php">Messages</a>
        <a href="resources.php">Career Resources</a>
    <?php elseif($role=='employer'): ?>
        <a href="posted_jobs.php">Posted Jobs</a>
        <a href="applications_received.php">Applications Received</a>
    <?php endif; ?>
    <a href="settings.php">Settings</a>
  </div>

  <!-- Main content -->
  <main class="main">
    <h2>Dashboard</h2>
    <p class="lead">Welcome back, <?= htmlspecialchars($userName) ?>!</p>

    <?php if($role=='job_seeker'): ?>
      <div class="filter-bar">
        <input type="text" id="location-filter" placeholder="Search by location...">
        <button id="filter-btn">Filter</button>
      </div>

      <div class="grid" id="job-cards">
        <?php foreach($jobs as $job): ?>
        <div class="card">
          <h5><?= htmlspecialchars($job['title']) ?></h5>
          <p><strong>Company:</strong> <?= htmlspecialchars($job['company']) ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
          <a href="apply.php?job_id=<?= $job['id'] ?>" 
            class="apply-btn" 
            data-job-id="<?= $job['id'] ?>" 
            data-title="<?= htmlspecialchars($job['title']) ?>"
            data-company="<?= htmlspecialchars($job['company']) ?>"
            data-location="<?= htmlspecialchars($job['location']) ?>"
            data-description="<?= htmlspecialchars($job['description']) ?>">
            Apply Now
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="summary">
        <h4>Application Summary</h4>
        <ul>
          <li>Total Applications: <?= $totalApplications ?></li>
          <li>Interviews Scheduled: <?= $interviewsScheduled ?></li>
          <li>Jobs Saved: <?= $jobsSaved ?></li>
        </ul>
      </div>

    <?php elseif($role=='employer'): ?>
      <div class="grid">
        <?php foreach($jobs as $job): ?>
        <div class="card">
          <h5><?= htmlspecialchars($job['title']) ?></h5>
          <p><strong>Applicants:</strong> <?= $conn->query("SELECT COUNT(*) FROM applications WHERE job_id=".$job['id'])->fetchColumn() ?></p>
          <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
          <a href="view_applications.php?job_id=<?= $job['id'] ?>">View Applications</a>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="summary">
        <h4>Employer Summary</h4>
        <ul>
          <li>Total Jobs Posted: <?= $totalPosted ?></li>
          <li>Total Applications Received: <?= $applicationsReceived ?></li>
        </ul>
      </div>
    <?php endif; ?>
  </main>
</div>

<!-- Apply Modal -->
<div id="applyModal" class="modal-overlay">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <div class="modal-body modal-grid">
      <div class="modal-details">
        <h3 id="modal-title"></h3>
        <p><strong>Company:</strong> <span id="modal-company"></span></p>
        <p><strong>Location:</strong> <span id="modal-location"></span></p>
        <h4>Description</h4>
        <p id="modal-description"></p>
        <div class="modal-actions">
          <a href="#" id="confirm-apply" class="apply-btn">Confirm Apply</a>
        </div>
      </div>
      <div id="modal-map" class="modal-map"></div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
$(document).ready(function() {
    const sidebar = $('#sidebar');
    $('#toggleSidebar').on('click', () => sidebar.toggleClass('open'));

    const userDropdown = $('.user-dropdown');
    $('.user-btn').on('click', e => {
        e.stopPropagation();
        userDropdown.toggleClass('active');
    });
    $(document).on('click', e => {
        if (!userDropdown.is(e.target) && userDropdown.has(e.target).length === 0) {
            userDropdown.removeClass('active');
        }
    });

    $('#filter-btn').on('click', function() {
        const location = $('#location-filter').val();
        $.post('filter_jobs.php', { location }, function(data) {
            $('#job-cards').html(data);
        });
    });

    let jobMap = null;
    const geocoder = new google.maps.Geocoder(); // ✅ Added geocoder instance

    // Apply button click
    $(document).on('click', '.apply-btn', function(e) {
        e.preventDefault();

        const title = $(this).data('title');
        const company = $(this).data('company');
        const location = $(this).data('location');
        const description = $(this).data('description');
        const jobId = $(this).data('job-id');

        $('#modal-title').text(title);
        $('#modal-company').text(company);
        $('#modal-location').text(location);
        $('#modal-description').text(description);
        $('#confirm-apply').attr('href', 'apply.php?job_id=' + jobId);

        $('#applyModal').fadeIn(200).css('display', 'flex');

        const mapContainer = document.getElementById('modal-map');
        mapContainer.innerHTML = "";

        const fullLocation = location + ", Philippines";

        geocoder.geocode({ address: fullLocation }, function(results, status) {
            if (status === 'OK' && results[0]) {
                const lat = results[0].geometry.location.lat();
                const lng = results[0].geometry.location.lng();

                jobMap = new google.maps.Map(mapContainer, {
                    zoom: 13,
                    center: { lat, lng },
                });

                new google.maps.Marker({
                    position: { lat, lng },
                    map: jobMap,
                    title: title
                });

                // ✅ Fix: trigger map resize after modal animation
                setTimeout(() => {
                    google.maps.event.trigger(jobMap, 'resize');
                    jobMap.setCenter({ lat, lng });
                }, 300);

            } else {
                // ⚠️ Fallback: show Calapan City + red message
                const fallback = { lat: 13.4146, lng: 121.1803 };
                jobMap = new google.maps.Map(mapContainer, {
                    zoom: 12,
                    center: fallback,
                });
                new google.maps.Marker({ position: fallback, map: jobMap });

                const msg = document.createElement('p');
                msg.style.color = 'red';
                msg.style.textAlign = 'center';
                msg.style.marginTop = '10px';
                msg.textContent = "⚠️ Location not found — showing default city map.";
                mapContainer.appendChild(msg);
            }
        });
    });

    // Modal close logic
    $('#applyModal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('close-btn')) {
            $('#applyModal').fadeOut(200, function() {
                jobMap = null;
            });
        }
    });
});
</script>


</body>
</html>
