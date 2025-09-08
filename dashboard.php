<?php
require_once 'config/config.php';

// For demonstration, we'll use student ID 7 (from your sample data)
$studentId = 7;

// Fetch student statistics - UPDATED QUERY
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM duties WHERE student_id = :student_id) as total_duties,
    (SELECT SUM(de.hours) FROM duty_entries de 
     JOIN duties d ON de.duty_id = d.id 
     WHERE d.student_id = :student_id AND de.status = 'approved') as total_hours,
    (SELECT COUNT(*) FROM duty_entries de 
     JOIN duties d ON de.duty_id = d.id 
     WHERE d.student_id = :student_id AND de.status = 'pending') as pending_approvals,
    (SELECT COUNT(*) FROM evaluations e 
     WHERE e.student_id = :student_id) as evaluation_count";

$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute(['student_id' => $studentId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Calculate average rating based on performance levels
$evaluationQuery = "SELECT overall_performance FROM evaluations WHERE student_id = :student_id";
$evaluationStmt = $pdo->prepare($evaluationQuery);
$evaluationStmt->execute(['student_id' => $studentId]);
$evaluations = $evaluationStmt->fetchAll(PDO::FETCH_ASSOC);

// Convert performance levels to numerical values for average calculation
$performanceScores = [
    'excellent' => 4,
    'good' => 3,
    'satisfactory' => 2,
    'needs_improvement' => 1
];

$totalScore = 0;
$evaluationCount = count($evaluations);

foreach ($evaluations as $evaluation) {
    $totalScore += $performanceScores[$evaluation['overall_performance']] ?? 2; // Default to satisfactory if unknown
}

$avgRating = $evaluationCount > 0 ? $totalScore / $evaluationCount : 0;

// Add the calculated average rating to the stats array
$stats['avg_rating'] = $avgRating;
$stats['evaluation_count'] = $evaluationCount;

// Fetch current duties
$dutiesQuery = "SELECT d.duty_type, d.created_at as assigned_date, d.required_hours, d.status 
                FROM duties d 
                WHERE d.student_id = :student_id 
                ORDER BY d.created_at DESC 
                LIMIT 3";
$dutiesStmt = $pdo->prepare($dutiesQuery);
$dutiesStmt->execute(['student_id' => $studentId]);
$duties = $dutiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activities
$activitiesQuery = "SELECT de.task_description as description, de.date as log_date, de.status, d.duty_type 
                   FROM duty_entries de 
                   JOIN duties d ON de.duty_id = d.id 
                   WHERE d.student_id = :student_id 
                   ORDER BY de.date DESC, de.created_at DESC 
                   LIMIT 4";
$activitiesStmt = $pdo->prepare($activitiesQuery);
$activitiesStmt->execute(['student_id' => $studentId]);
$activities = $activitiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch duty progress
$progressQuery = "SELECT d.duty_type, d.required_hours, 
                 COALESCE(SUM(CASE WHEN de.status = 'approved' THEN de.hours ELSE 0 END), 0) as completed_hours
                 FROM duties d 
                 LEFT JOIN duty_entries de ON d.id = de.duty_id 
                 WHERE d.student_id = :student_id 
                 GROUP BY d.id, d.duty_type, d.required_hours";
$progressStmt = $pdo->prepare($progressQuery);
$progressStmt->execute(['student_id' => $studentId]);
$progressData = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Duty Log System - Dashboard</title>
  <meta name="description" content="Duty tracking and management system">
  <meta name="keywords" content="duty, log, tracking, management">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="dashboard-page">
  <header id="header" class="header sticky-top">
    <div class="branding d-flex align-items-center">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.php" class="logo d-flex align-items-center">
          <img src="assets/img/CSDL logo.png" alt="">
          <h1 class="sitename">CSDL</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="dashboard.php" class="active">Dashboard</a></li>
            <li class="dropdown">
              <a href="#"></i>Duty Options</a>
              <ul class="dropdown-menu">
                  <li><a href="assign-duty.php"></i>Assign Duty</a></li>
                  <li><a href="approve-duty.php"></i>Approve Duty</a></li>
                  <li><a href="log-duty.php"></i>Log Duty</a></li>
                  <li><a href="view-duty.php"></i>View Duty</a></li>
                  <li><a href="monitor-duty.php"></i>Monitor Duty</a></li>
              </ul>
          </li>
            <li><a href="evaluate-student.php">Evaluate Student</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>

  <main class="main">
    <!-- Page Title -->
    <div class="page-title light-background">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Dashboard</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Dashboard</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Dashboard Section -->
    <section id="dashboard" class="dashboard section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        
        <!-- Stats Overview -->
        <div class="row g-4 mb-5">
          <div class="col-md-6 col-lg-3">
            <div class="stats-card bg-white p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h3><?php echo $stats['total_duties'] ?? 0; ?></h3>
                  <p>Total Duties</p>
                </div>
                <div class="stats-icon bg-primary text-white rounded-circle p-3">
                  <i class="bi bi-clipboard-check"></i>
                </div>
              </div>
              <div class="progress mt-2">
                <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small class="text-muted"><?php echo floor(($stats['total_duties'] ?? 0) * 0.6); ?> completed this month</small>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="stats-card bg-white p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h3><?php echo number_format($stats['total_hours'] ?? 0, 1); ?></h3>
                  <p>Hours Logged</p>
                </div>
                <div class="stats-icon bg-success text-white rounded-circle p-3">
                  <i class="bi bi-clock-history"></i>
                </div>
              </div>
              <div class="progress mt-2">
                <div class="progress-bar bg-success" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small class="text-muted">8.5 hrs avg per week</small>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="stats-card bg-white p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h3><?php echo $stats['pending_approvals'] ?? 0; ?></h3>
                  <p>Pending Approvals</p>
                </div>
                <div class="stats-icon bg-warning text-white rounded-circle p-3">
                  <i class="bi bi-hourglass-split"></i>
                </div>
              </div>
              <div class="progress mt-2">
                <div class="progress-bar bg-warning" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <small class="text-muted">2 awaiting review</small>
            </div>
          </div>
          
          <div class="col-md-6 col-lg-3">
            <div class="stats-card bg-white p-4">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h3><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></h3>
                  <p>Avg. Rating</p>
                </div>
                <div class="stats-icon bg-info text-white rounded-circle p-3">
                  <i class="bi bi-star-fill"></i>
                </div>
              </div>
              <div class="progress mt-2">
                <div class="progress-bar bg-info" role="progressbar" 
                     style="width: <?php echo ($stats['avg_rating'] ?? 0) / 4 * 100; ?>%" 
                     aria-valuenow="<?php echo ($stats['avg_rating'] ?? 0) / 4 * 100; ?>" 
                     aria-valuemin="0" 
                     aria-valuemax="100">
                </div>
              </div>
              <small class="text-muted">Based on <?php echo $stats['evaluation_count'] ?? 0; ?> evaluations</small>
            </div>
          </div>
        </div>
        
        <div class="row g-4">
          <!-- Current Duties -->
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Current Duties</h5>
                <a href="duties.php" class="btn btn-sm btn-outline-primary">View All</a>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Duty Type</th>
                        <th>Assigned</th>
                        <th>Hours</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($duties as $duty): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($duty['duty_type']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($duty['assigned_date'])); ?></td>
                        <td><?php echo $duty['required_hours']; ?></td>
                        <td>
                          <span class="status-badge status-<?php echo str_replace('_', '-', $duty['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $duty['status'])); ?>
                          </span>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Activities -->
          <div class="col-lg-6">
            <div class="card h-100">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Activities</h5>
                <a href="entries.php" class="btn btn-sm btn-outline-primary">View All</a>
              </div>
              <div class="card-body recent-activities">
                <?php foreach ($activities as $activity): ?>
                <div class="activity-item">
                  <div class="d-flex justify-content-between">
                    <h6><?php echo htmlspecialchars($activity['duty_type']); ?></h6>
                    <small class="text-muted"><?php echo time_elapsed_string($activity['log_date']); ?></small>
                  </div>
                  <p><?php echo htmlspecialchars($activity['description']); ?></p>
                  <div class="d-flex justify-content-between">
                    <span class="status-badge status-<?php echo $activity['status']; ?>">
                      <?php echo ucfirst($activity['status']); ?>
                    </span>
                    <small><?php echo date('M j, Y', strtotime($activity['log_date'])); ?></small>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Progress Overview -->
        <div class="row mt-4">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h5 class="card-title mb-0">Duty Progress Overview</h5>
              </div>
              <div class="card-body">
                <div class="row text-center">
                  <?php foreach ($progressData as $progress): 
                    $percentage = $progress['required_hours'] > 0 ? 
                                 min(100, ($progress['completed_hours'] / $progress['required_hours']) * 100) : 0;
                    $bgClass = '';
                    if ($percentage >= 100) $bgClass = 'bg-info';
                    elseif ($percentage >= 70) $bgClass = 'bg-success';
                    elseif ($percentage >= 40) $bgClass = '';
                    else $bgClass = 'bg-danger';
                  ?>
                  <div class="col-md-3">
                    <h4><?php echo htmlspecialchars($progress['duty_type']); ?></h4>
                    <div class="progress mx-auto" style="height: 20px; width: 80%;">
                      <div class="progress-bar <?php echo $bgClass; ?>" role="progressbar" 
                           style="width: <?php echo $percentage; ?>%;" 
                           aria-valuenow="<?php echo $percentage; ?>" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                        <?php echo round($percentage, 1); ?>%
                      </div>
                    </div>
                    <p class="mt-2">
                      <?php echo $progress['completed_hours']; ?> of <?php echo $progress['required_hours']; ?> hours completed
                    </p>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        
      </div>
    </section><!-- /Dashboard Section -->

  </main>

  <footer id="footer" class="footer dark-background">
    <div class="container footer-top">
      <div class="row gy-4">
        <div class="col-lg-5 col-md-12 footer-about">
          <a href="index.php" class="logo d-flex align-items-center">
            <span class="sitename">Student Duty Log</span>
          </a>
          <p>Empowering PHINMA Cagayan de Oro College with innovative student duty management solutions. Streamlining workflows, enhancing accountability, and fostering academic excellence through technology tailored for Filipino students.</p>
          <div class="social-links d-flex mt-4">
            <a href=""><i class="bi bi-twitter-x"></i></a>
            <a href=""><i class="bi bi-facebook"></i></a>
            <a href=""><i class="bi bi-instagram"></i></a>
            <a href=""><i class="bi bi-linkedin"></i></a>
          </div>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="#">Student Portal</a></li>
            <li><a href="#">Help Center</a></li>
            <li><a href="#">Contact Support</a></li>
          </ul>
        </div>

        <div class="col-lg-2 col-6 footer-links">
          <h4>Colleges</h4>
          <ul>
            <li><a href="#">Engineering & Architecture</a></li>
            <li><a href="#">Business & Accountancy</a></li>
            <li><a href="#">Education</a></li>
            <li><a href="#">Health Sciences</a></li>
            <li><a href="#">Liberal Arts & Sciences</a></li>
            <li><a href="#">Computer Studies</a></li>
          </ul>
        </div>

        <div class="col-lg-3 col-md-12 footer-contact text-center text-md-start">
          <h4>PHINMA COC Campus</h4>
          <p>Kauswagan Highway</p>
          <p>Cagayan de Oro City</p>
          <p>Misamis Oriental 9000, Philippines</p>
          <p class="mt-4"><strong>Phone:</strong> <span>(088) 562-6731</span></p>
          <p><strong>Email:</strong> <span>registrar@phinmacoc.edu.ph</span></p>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span>Copyright</span> <strong class="px-1 sitename">PHINMA Cagayan de Oro College</strong> <span>All Rights Reserved</span></p>
      <div class="credits">
        Part of the PHINMA Education Network
      </div>
    </div>

  </footer>

  <!-- Scroll Top -->
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Preloader -->
  <div id="preloader"></div>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

  <!-- Main JS File -->
  <script src="assets/js/main.js"></script>

</body>

</html>

<?php
// Helper function to display time elapsed
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
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