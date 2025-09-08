<?php
require_once 'config/config.php';
// session_start();

// Check if user is logged in
// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

// Fetch all duties with student information
$dutiesQuery = "
    SELECT d.*, u.username as student_name, u2.username as assigned_by_name 
    FROM duties d 
    JOIN users u ON d.student_id = u.id 
    JOIN users u2 ON d.assigned_by = u2.id 
    ORDER BY d.created_at DESC
";
$dutiesStmt = $pdo->query($dutiesQuery);
$duties = $dutiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students for filter dropdown
$studentsQuery = "SELECT id, username as name FROM users WHERE role = 'student'";
$studentsStmt = $pdo->query($studentsQuery);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_duties,
        SUM(CASE WHEN d.status = 'completed' THEN 1 ELSE 0 END) as completed_duties,
        SUM(CASE WHEN d.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_duties,
        COALESCE(SUM(de.hours), 0) as total_hours
    FROM duties d
    LEFT JOIN duty_entries de ON d.id = de.duty_id AND de.status = 'approved'
";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
// Get duty types for filter
$dutyTypesQuery = "SELECT DISTINCT duty_type FROM duties ORDER BY duty_type";
$dutyTypesStmt = $pdo->query($dutyTypesQuery);
$dutyTypes = $dutyTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// Function to get duty entries with completed hours calculation
function getDutyWithEntries($pdo, $dutyId) {
    $query = "
        SELECT d.*, u.username as student_name, u2.username as assigned_by_name,
               COALESCE(SUM(CASE WHEN de.status = 'approved' THEN de.hours ELSE 0 END), 0) as completed_hours
        FROM duties d 
        JOIN users u ON d.student_id = u.id 
        JOIN users u2 ON d.assigned_by = u2.id 
        LEFT JOIN duty_entries de ON d.id = de.duty_id 
        WHERE d.id = :duty_id
        GROUP BY d.id
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['duty_id' => $dutyId]);
    $duty = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($duty) {
        $entriesQuery = "SELECT * FROM duty_entries WHERE duty_id = :duty_id ORDER BY date DESC";
        $entriesStmt = $pdo->prepare($entriesQuery);
        $entriesStmt->execute(['duty_id' => $dutyId]);
        $duty['entries'] = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    return $duty;
}

// Handle AJAX request for duty details
if (isset($_GET['action']) && $_GET['action'] === 'get_duty_details' && isset($_GET['duty_id'])) {
    $dutyId = $_GET['duty_id'];
    $duty = getDutyWithEntries($pdo, $dutyId);
    
    if ($duty) {
        header('Content-Type: application/json');
        echo json_encode($duty);
        exit();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Duty not found']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Duty Viewing System">
  <meta name="keywords" content="PHINMA COC, student duty, duty tracking, college management, Cagayan de Oro">

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
  
  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #3498db;
      --success-color: #27ae60;
      --warning-color: #f39c12;
      --danger-color: #e74c3c;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
    }
    
    .view-section {
      background-color: #f8f9fa;
      padding: 30px;
      border-radius: 8px;
      margin-bottom: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .filter-section {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .section-title {
      border-bottom: 2px solid #f1f1f1;
      padding-bottom: 10px;
      margin-bottom: 20px;
      font-weight: 600;
      color: #2c3e50;
    }
    
    .student-info-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 25px;
    }
    
    .duty-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      border-left: 4px solid var(--secondary-color);
      transition: transform 0.3s ease;
      cursor: pointer;
    }
    
    .duty-card:hover {
      transform: translateY(-3px);
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .status-assigned {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-in_progress {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .status-completed {
      background-color: #d4edda;
      color: #155724;
    }
    
    .student-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background-color: var(--secondary-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.2rem;
      margin-right: 15px;
    }
    
    .progress {
      height: 10px;
      margin: 10px 0;
    }
    
    .time-badge {
      background-color: #e9ecef;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.85rem;
    }
    
    .stats-card {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      text-align: center;
    }
    
    .stats-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0;
    }
    
    .stats-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }
    
    .duty-details {
      max-height: 500px;
      overflow-y: auto;
    }
    
    .detail-item {
      padding: 15px;
      border-bottom: 1px solid #eee;
    }
    
    .detail-item:last-child {
      border-bottom: none;
    }
    
    .entry-status {
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .status-pending {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .status-approved {
      background-color: #d4edda;
      color: #155724;
    }
    
    .status-rejected {
      background-color: #f8d7da;
      color: #721c24;
    }
    
    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid #f3f3f3;
      border-top: 3px solid #3498db;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-right: 10px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
      .time-inputs {
        flex-direction: column;
        gap: 10px;
      }
    }
  </style>
</head>

<body class="index-page">

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
            <li><a href="dashboard.php">Dashboard</a></li>
            <li class="dropdown">
              <a href="#"></i>Duty Options</a>
              <ul class="dropdown-menu">
                  <li><a href="assign-duty.php"></i>Assign Duty</a></li>
                  <li><a href="approve-duty.php"></i>Approve Duty</a></li>
                  <li><a href="log-duty.php"></i>Log Duty</a></li>
                  <li><a href="view-duty.php" class="active"></i>View Duty</a></li>
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
        <h1 class="mb-2 mb-lg-0">View Student Duties</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">View Duties</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- View Section -->
    <section id="view" class="view section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        
        <!-- Stats Overview -->
        <div class="row mb-4">
          <div class="col-md-3">
            <div class="stats-card">
              <p class="stats-number" id="totalDuties"><?php echo $stats['total_duties']; ?></p>
              <p class="stats-label">Total Duties</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card">
              <p class="stats-number" id="completedDuties"><?php echo $stats['completed_duties']; ?></p>
              <p class="stats-label">Completed</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card">
              <p class="stats-number" id="inProgressDuties"><?php echo $stats['in_progress_duties']; ?></p>
              <p class="stats-label">In Progress</p>
            </div>
          </div>
          <div class="col-md-3">
            <div class="stats-card">
              <p class="stats-number" id="totalHours"><?php echo number_format($stats['total_hours'], 1); ?></p>
              <p class="stats-label">Total Hours</p>
            </div>
          </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label for="statusFilter">Status</label>
                <select class="form-control" id="statusFilter">
                  <option value="all">All Statuses</option>
                  <option value="assigned">Assigned</option>
                  <option value="in_progress">In Progress</option>
                  <option value="completed">Completed</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="studentFilter">Student</label>
                <select class="form-control" id="studentFilter">
                  <option value="all">All Students</option>
                  <?php foreach ($students as $student): ?>
                  <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="dutyTypeFilter">Duty Type</label>
                <select class="form-control" id="dutyTypeFilter">
                  <option value="all">All Types</option>
                  <?php foreach ($dutyTypes as $dutyType): ?>
                  <option value="<?php echo htmlspecialchars($dutyType); ?>"><?php echo htmlspecialchars($dutyType); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button class="btn btn-primary w-100" id="applyFilters">Apply Filters</button>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-lg-7">
            <div class="view-section">
              <h3 class="section-title">Student Duties</h3>
              <div id="dutiesContainer">
                <?php if (empty($duties)): ?>
                <div class="text-center py-5">
                  <i class="bi bi-inbox display-4 text-muted"></i>
                  <p class="mt-2">No duties found</p>
                  <p class="text-muted">Try adjusting your filters to see more results</p>
                </div>
                <?php else: ?>
                  <?php foreach ($duties as $duty): 
                    $statusClass = 'status-' . $duty['status'];
                    $statusText = ucfirst(str_replace('_', ' ', $duty['status']));
                    $completedHours = getDutyWithEntries($pdo, $duty['id'])['completed_hours'] ?? 0;
                    $progressPercent = $duty['required_hours'] > 0 ? 
                                     min(100, ($completedHours / $duty['required_hours']) * 100) : 0;
                  ?>
                  <div class="duty-card" data-duty-id="<?php echo $duty['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <div>
                        <h5 class="card-title"><?php echo htmlspecialchars($duty['duty_type']); ?></h5>
                        <p class="card-subtitle text-muted">Assigned on: <?php echo date('M j, Y', strtotime($duty['created_at'])); ?></p>
                      </div>
                      <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                    
                    <div class="student-info d-flex align-items-center mb-3">
                      <div class="student-avatar"><?php echo substr($duty['student_name'], 0, 1); ?></div>
                      <div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($duty['student_name']); ?></h6>
                        <small class="text-muted">Assigned by: <?php echo htmlspecialchars($duty['assigned_by_name']); ?></small>
                      </div>
                    </div>
                    
                    <div class="duty-progress">
                      <div class="d-flex justify-content-between">
                        <span>Progress: <?php echo $completedHours; ?> / <?php echo $duty['required_hours']; ?> hours</span>
                        <span><?php echo min(100, round($progressPercent)); ?>%</span>
                      </div>
                      <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progressPercent; ?>%" 
                          aria-valuenow="<?php echo $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                      </div>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                      <span class="time-badge"><i class="bi bi-clock-history"></i> 
                        <?php 
                        $entriesCount = count(getDutyWithEntries($pdo, $duty['id'])['entries'] ?? []);
                        echo $entriesCount . ' entr' . ($entriesCount === 1 ? 'y' : 'ies');
                        ?>
                      </span>
                      <button class="btn btn-outline-primary btn-sm view-details-btn" data-duty-id="<?php echo $duty['id']; ?>">
                        <i class="bi bi-eye"></i> View Details
                      </button>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          
          <div class="col-lg-5">
            <div class="view-section">
              <h3 class="section-title">Duty Details</h3>
              <div class="duty-details" id="dutyDetails">
                <div class="text-center py-5">
                  <i class="bi bi-info-circle display-4 text-muted"></i>
                  <p class="mt-2">Select a duty to view details</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- /View Section -->

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

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Filter functionality
      document.getElementById('applyFilters').addEventListener('click', function() {
        const statusFilter = document.getElementById('statusFilter').value;
        const studentFilter = document.getElementById('studentFilter').value;
        const dutyTypeFilter = document.getElementById('dutyTypeFilter').value;
        
        const dutyCards = document.querySelectorAll('.duty-card');
        
        dutyCards.forEach(card => {
          let showCard = true;
          const status = card.querySelector('.status-badge').textContent.toLowerCase().replace(' ', '_');
          const studentId = card.getAttribute('data-duty-id');
          const dutyType = card.querySelector('.card-title').textContent;
          
          if (statusFilter !== 'all' && status !== statusFilter) {
            showCard = false;
          }
          
          if (studentFilter !== 'all' && !studentId.includes(studentFilter)) {
            showCard = false;
          }
          
          if (dutyTypeFilter !== 'all' && dutyType !== dutyTypeFilter) {
            showCard = false;
          }
          
          card.style.display = showCard ? 'block' : 'none';
        });
      });
      
      // Add event listeners to view details buttons
      document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function() {
          const dutyId = this.getAttribute('data-duty-id');
          showDutyDetails(dutyId);
        });
      });
      
      // Add event listeners to duty cards
      document.querySelectorAll('.duty-card').forEach(card => {
        card.addEventListener('click', function(e) {
          if (!e.target.closest('.view-details-btn')) {
            const dutyId = this.getAttribute('data-duty-id');
            showDutyDetails(dutyId);
          }
        });
      });
    });
    
    function showDutyDetails(dutyId) {
      const detailsContainer = document.getElementById('dutyDetails');
      detailsContainer.innerHTML = `
        <div class="text-center py-4">
          <div class="loading-spinner"></div>
          <p class="mt-2">Loading duty details...</p>
        </div>
      `;
      
      // Fetch duty details via AJAX
      fetch(`view-duty.php?action=get_duty_details&duty_id=${dutyId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(duty => {
          renderDutyDetails(duty);
        })
        .catch(error => {
          detailsContainer.innerHTML = `
            <div class="alert alert-danger">
              <i class="bi bi-exclamation-triangle"></i> Error loading duty details: ${error.message}
            </div>
          `;
        });
    }
    
    function renderDutyDetails(duty) {
      const detailsContainer = document.getElementById('dutyDetails');
      const statusClass = `status-${duty.status}`;
      const statusText = duty.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
      const progressPercent = duty.required_hours > 0 ? 
                           min(100, (duty.completed_hours / duty.required_hours) * 100) : 0;
      
      let entriesHTML = '';
      if (duty.entries && duty.entries.length > 0) {
        duty.entries.forEach(entry => {
          const entryStatusClass = `status-${entry.status}`;
          const entryStatusText = entry.status.charAt(0).toUpperCase() + entry.status.slice(1);
          const approvalDate = entry.approval_date ? new Date(entry.approval_date).toLocaleDateString() : 'Pending approval';
          
          entriesHTML += `
            <div class="detail-item">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">${entry.date}</h6>
                <span class="entry-status ${entryStatusClass}">${entryStatusText}</span>
              </div>
              <p class="mb-1">${entry.task_description}</p>
              <div class="d-flex justify-content-between">
                <span class="time-badge"><i class="bi bi-clock"></i> ${entry.hours} hours</span>
                <small class="text-muted">${approvalDate}</small>
              </div>
              ${entry.instructor_feedback ? 
                `<div class="mt-2 p-2 bg-light rounded"><small><strong>Feedback:</strong> ${entry.instructor_feedback}</small></div>` : 
                ''
              }
            </div>
          `;
        });
      } else {
        entriesHTML = '<p class="text-muted">No duty entries yet.</p>';
      }
      
      detailsContainer.innerHTML = `
        <div class="student-info-card">
          <div class="d-flex align-items-center">
            <div class="student-avatar">${duty.student_name.charAt(0)}</div>
            <div>
              <h4>${duty.student_name}</h4>
              <p class="mb-0">${duty.duty_type}</p>
            </div>
          </div>
        </div>
        
        <div class="duty-info mb-4">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Duty Details</h5>
            <span class="status-badge ${statusClass}">${statusText}</span>
          </div>
          
          <div class="row">
            <div class="col-6">
              <p class="mb-1"><strong>Required Hours:</strong></p>
              <p>${duty.required_hours}</p>
            </div>
            <div class="col-6">
              <p class="mb-1"><strong>Completed Hours:</strong></p>
              <p>${duty.completed_hours}</p>
            </div>
          </div>
          
          <div class="progress mb-3">
            <div class="progress-bar" role="progressbar" style="width: ${progressPercent}%" 
              aria-valuenow="${progressPercent}" aria-valuemin="0" aria-valuemax="100"></div>
          </div>
          
          <p class="mb-1"><strong>Assigned by:</strong> ${duty.assigned_by_name}</p>
          <p class="mb-0"><strong>Assigned on:</strong> ${new Date(duty.created_at).toLocaleDateString()}</p>
        </div>
        
        <div class="duty-entries">
          <h5 class="mb-3">Duty Entries</h5>
          ${entriesHTML}
        </div>
      `;
    }
    
    function min(a, b) {
      return a < b ? a : b;
    }
  </script>

</body>

</html>