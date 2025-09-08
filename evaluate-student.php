<?php
session_start(); // Start the session

// Check if the user is not logged in (e.g., by checking a session variable set during login)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: login.php");
    exit;
}

// Prevent caching of the page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Rest of your protected page content
// ...
require_once 'config/config.php';
session_start();

// Check if user is logged in as an instructor
// if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'instructor') {
//     header("Location: login.php");
//     exit();
// }

// $instructorId = $_SESSION['user_id'];

// Fetch students with their current duties
$studentsQuery = "
    SELECT u.id, u.username as name, u.department as program, 
           d.id as duty_id, d.duty_type, d.required_hours,
           COALESCE(SUM(de.hours), 0) as completed_hours
    FROM users u
    LEFT JOIN duties d ON u.id = d.student_id AND d.status != 'completed'
    LEFT JOIN duty_entries de ON d.id = de.duty_id AND de.status = 'approved'
    WHERE u.role = 'student'
    GROUP BY u.id, d.id
    ORDER BY u.username
";
$studentsStmt = $pdo->query($studentsQuery);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_evaluations,
        COUNT(DISTINCT student_id) as students_evaluated,
        AVG(CASE 
            WHEN overall_performance = 'excellent' THEN 4
            WHEN overall_performance = 'good' THEN 3
            WHEN overall_performance = 'satisfactory' THEN 2
            WHEN overall_performance = 'needs_improvement' THEN 1
            ELSE NULL
        END) as avg_rating
    FROM evaluations
";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Fetch evaluation history
$evaluationsQuery = "
    SELECT e.*, u.username as student_name, u2.username as instructor_name
    FROM evaluations e
    JOIN users u ON e.student_id = u.id
    JOIN users u2 ON e.instructor_id = u2.id
    ORDER BY e.date DESC
    LIMIT 10
";
$evaluationsStmt = $pdo->query($evaluationsQuery);
$evaluationHistory = $evaluationsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $date = $_POST['date'];
    $punctuality = $_POST['punctuality'];
    $quality = $_POST['quality'];
    $initiative = $_POST['initiative'];
    $teamwork = $_POST['teamwork'];
    $overall = $_POST['overall'];
    $remarks = $_POST['remarks'];
    
    try {
        $insertQuery = "INSERT INTO evaluations (student_id, instructor_id, punctuality, quality, initiative, teamwork, overall_performance, remarks, date) 
                       VALUES (:student_id, :instructor_id, :punctuality, :quality, :initiative, :teamwork, :overall, :remarks, :date)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'student_id' => $studentId,
            'instructor_id' => $instructorId,
            'punctuality' => $punctuality,
            'quality' => $quality,
            'initiative' => $initiative,
            'teamwork' => $teamwork,
            'overall' => $overall,
            'remarks' => $remarks,
            'date' => $date
        ]);
        
        $successMessage = "Evaluation submitted successfully!";
        
        // Refresh page to show updated data
        header("Location: evaluate-student.php?success=" . urlencode($successMessage));
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Error submitting evaluation: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale="1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Evaluation System">
  <meta name="keywords" content="PHINMA COC, student evaluation, duty performance, college management, Cagayan de Oro">

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
    
    .evaluation-section {
      background-color: #f8f9fa;
      padding: 30px;
      border-radius: 8px;
      margin-bottom: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .form-section {
      margin-bottom: 25px;
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
    
    .rating-scale {
      display: flex;
      justify-content: space-between;
      margin-bottom: 15px;
    }
    
    .rating-item {
      text-align: center;
      flex: 1;
      padding: 10px;
      border-radius: 5px;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .rating-item:hover {
      background-color: #f8f9fa;
    }
    
    .rating-item.selected {
      background-color: #e9ecef;
      font-weight: 600;
    }
    
    .rating-item.excellent {
      color: var(--success-color);
    }
    
    .rating-item.good {
      color: var(--secondary-color);
    }
    
    .rating-item.satisfactory {
      color: var(--warning-color);
    }
    
    .rating-item.needs-improvement {
      color: var(--danger-color);
    }
    
    .criteria-card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 20px;
    }
    
    .criteria-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .performance-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    
    .performance-excellent {
      background-color: #d4edda;
      color: #155724;
    }
    
    .performance-good {
      background-color: #cce5ff;
      color: #004085;
    }
    
    .performance-satisfactory {
      background-color: #fff3cd;
      color: #856404;
    }
    
    .performance-needs-improvement {
      background-color: #f8d7da;
      color: #721c24;
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
    
    .student-avatar {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background-color: var(--secondary-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      font-size: 1.5rem;
      margin-right: 15px;
    }
    
    .progress {
      height: 10px;
      margin: 10px 0;
    }
    
    .evaluation-history {
      max-height: 400px;
      overflow-y: auto;
    }
    
    .hidden {
      display: none;
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
                  <li><a href="view-duty.php"></i>View Duty</a></li>
                  <li><a href="monitor-duty.php"></i>Monitor Duty</a></li>
              </ul>
          </li>
            <li><a href="evaluate-student.php" class="active">Evaluate Student</a></li>
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
        <h1 class="mb-2 mb-lg-0">Evaluate Student Performance</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Evaluate Student</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Evaluation Section -->
    <section id="evaluation" class="evaluation section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo htmlspecialchars($_GET['success']); ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $errorMessage; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- Stats Overview -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="totalEvaluations"><?php echo $stats['total_evaluations']; ?></p>
              <p class="stats-label">Total Evaluations</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="avgRating"><?php echo number_format($stats['avg_rating'], 1); ?></p>
              <p class="stats-label">Average Rating</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="studentsEvaluated"><?php echo $stats['students_evaluated']; ?></p>
              <p class="stats-label">Students Evaluated</p>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-lg-8">
            <div class="evaluation-section">
              <h3 class="section-title">Student Evaluation</h3>
              
              <form method="POST" action="evaluate-student.php" id="evaluationForm">
                <div class="form-section">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="studentSelect" class="form-label">Select Student</label>
                        <select class="form-control" id="studentSelect" name="student_id" required>
                          <option value="">-- Select Student --</option>
                          <?php foreach ($students as $student): ?>
                          <option value="<?php echo $student['id']; ?>" data-duty-type="<?php echo htmlspecialchars($student['duty_type'] ?? 'No duty'); ?>" 
                                  data-program="<?php echo htmlspecialchars($student['program']); ?>"
                                  data-completed-hours="<?php echo $student['completed_hours']; ?>"
                                  data-required-hours="<?php echo $student['required_hours']; ?>">
                            <?php echo htmlspecialchars($student['name']); ?> - <?php echo htmlspecialchars($student['duty_type'] ?? 'No duty'); ?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="evaluationDate" class="form-label">Evaluation Date</label>
                        <input type="date" class="form-control" id="evaluationDate" name="date" required>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Student Information Card -->
                <div class="student-info-card" id="studentInfo" style="display: none;">
                  <div class="d-flex align-items-center">
                    <div class="student-avatar" id="studentAvatar">S</div>
                    <div>
                      <h4 id="studentName">Student Name</h4>
                      <p class="mb-1" id="studentProgram">Program: </p>
                      <p class="mb-0" id="studentDuty">Current Duty: </p>
                    </div>
                  </div>
                  <div class="mt-3">
                    <div class="d-flex justify-content-between">
                      <span>Duty Progress: <span id="completedHours">0</span>/<span id="requiredHours">0</span> hours</span>
                      <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Evaluation Criteria -->
                <div class="form-section">
                  <h5>Performance Evaluation</h5>
                  <p class="text-muted">Rate the student's performance on each criteria</p>
                  
                  <!-- Rating Scale -->
                  <div class="rating-scale mb-4">
                    <div class="rating-item excellent" data-value="excellent">
                      <div>Excellent</div>
                      <small>4 points</small>
                    </div>
                    <div class="rating-item good" data-value="good">
                      <div>Good</div>
                      <small>3 points</small>
                    </div>
                    <div class="rating-item satisfactory" data-value="satisfactory">
                      <div>Satisfactory</div>
                      <small>2 points</small>
                    </div>
                    <div class="rating-item needs-improvement" data-value="needs_improvement">
                      <div>Needs Improvement</div>
                      <small>1 point</small>
                    </div>
                  </div>
                  
                  <!-- Evaluation Criteria -->
                  <div class="criteria-card">
                    <div class="criteria-header">
                      <h6 class="mb-0">Punctuality and Attendance</h6>
                      <span class="performance-badge" id="punctualityRating">Not rated</span>
                    </div>
                    <p class="text-muted small">Evaluates the student's timeliness and consistency in attending assigned duties.</p>
                    <div class="rating-scale">
                      <div class="rating-item excellent" data-criteria="punctuality" data-value="excellent">
                        <div>Excellent</div>
                      </div>
                      <div class="rating-item good" data-criteria="punctuality" data-value="good">
                        <div>Good</div>
                      </div>
                      <div class="rating-item satisfactory" data-criteria="punctuality" data-value="satisfactory">
                        <div>Satisfactory</div>
                      </div>
                      <div class="rating-item needs-improvement" data-criteria="punctuality" data-value="needs_improvement">
                        <div>Needs Improvement</div>
                      </div>
                    </div>
                    <input type="hidden" id="punctualityInput" name="punctuality">
                  </div>
                  
                  <div class="criteria-card">
                    <div class="criteria-header">
                      <h6 class="mb-0">Quality of Work</h6>
                      <span class="performance-badge" id="qualityRating">Not rated</span>
                    </div>
                    <p class="text-muted small">Evaluates the accuracy, thoroughness, and excellence of the completed tasks.</p>
                    <div class="rating-scale">
                      <div class="rating-item excellent" data-criteria="quality" data-value="excellent">
                        <div>Excellent</div>
                      </div>
                      <div class="rating-item good" data-criteria="quality" data-value="good">
                        <div>Good</div>
                      </div>
                      <div class="rating-item satisfactory" data-criteria="quality" data-value="satisfactory">
                        <div>Satisfactory</div>
                      </div>
                      <div class="rating-item needs-improvement" data-criteria="quality" data-value="needs_improvement">
                        <div>Needs Improvement</div>
                      </div>
                    </div>
                    <input type="hidden" id="qualityInput" name="quality">
                  </div>
                  
                  <div class="criteria-card">
                    <div class="criteria-header">
                      <h6 class="mb-0">Initiative and Proactivity</h6>
                      <span class="performance-badge" id="initiativeRating">Not rated</span>
                    </div>
                    <p class="text-muted small">Evaluates the student's willingness to take on responsibilities and seek additional tasks.</p>
                    <div class="rating-scale">
                      <div class="rating-item excellent" data-criteria="initiative" data-value="excellent">
                        <div>Excellent</div>
                      </div>
                      <div class="rating-item good" data-criteria="initiative" data-value="good">
                        <div>Good</div>
                      </div>
                      <div class="rating-item satisfactory" data-criteria="initiative" data-value="satisfactory">
                        <div>Satisfactory</div>
                      </div>
                      <div class="rating-item needs-improvement" data-criteria="initiative" data-value="needs_improvement">
                        <div>Needs Improvement</div>
                      </div>
                    </div>
                    <input type="hidden" id="initiativeInput" name="initiative">
                  </div>
                  
                  <div class="criteria-card">
                    <div class="criteria-header">
                      <h6 class="mb-0">Teamwork and Communication</h6>
                      <span class="performance-badge" id="teamworkRating">Not rated</span>
                    </div>
                    <p class="text-muted small">Evaluates the student's ability to collaborate with others and communicate effectively.</p>
                    <div class="rating-scale">
                      <div class="rating-item excellent" data-criteria="teamwork" data-value="excellent">
                        <div>Excellent</div>
                      </div>
                      <div class="rating-item good" data-criteria="teamwork" data-value="good">
                        <div>Good</div>
                      </div>
                      <div class="rating-item satisfactory" data-criteria="teamwork" data-value="satisfactory">
                        <div>Satisfactory</div>
                      </div>
                      <div class="rating-item needs-improvement" data-criteria="teamwork" data-value="needs_improvement">
                        <div>Needs Improvement</div>
                      </div>
                    </div>
                    <input type="hidden" id="teamworkInput" name="teamwork">
                  </div>
                  
                  <div class="criteria-card">
                    <div class="criteria-header">
                      <h6 class="mb-0">Overall Performance</h6>
                      <span class="performance-badge" id="overallRating">Not rated</span>
                    </div>
                    <p class="text-muted small">Overall evaluation of the student's performance considering all aspects.</p>
                    <div class="rating-scale">
                      <div class="rating-item excellent" data-criteria="overall" data-value="excellent">
                        <div>Excellent</div>
                      </div>
                      <div class="rating-item good" data-criteria="overall" data-value="good">
                        <div>Good</div>
                      </div>
                      <div class="rating-item satisfactory" data-criteria="overall" data-value="satisfactory">
                        <div>Satisfactory</div>
                      </div>
                      <div class="rating-item needs-improvement" data-criteria="overall" data-value="needs_improvement">
                        <div>Needs Improvement</div>
                      </div>
                    </div>
                    <input type="hidden" id="overallInput" name="overall">
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Remarks and Feedback</h5>
                  <div class="mb-3">
                    <label for="evaluationRemarks" class="form-label">Additional comments and recommendations</label>
                    <textarea class="form-control" id="evaluationRemarks" name="remarks" rows="4" placeholder="Provide constructive feedback for the student..." required></textarea>
                  </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg" id="submitEvaluationBtn">
                    <i class="bi bi-check-circle"></i> Submit Evaluation
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="evaluation-section">
              <h3 class="section-title">Evaluation History</h3>
              <div class="evaluation-history" id="evaluationHistory">
                <?php if (empty($evaluationHistory)): ?>
                <div class="text-center py-4">
                  <i class="bi bi-inbox display-4 text-muted"></i>
                  <p class="mt-2">No evaluation history</p>
                </div>
                <?php else: ?>
                  <?php foreach ($evaluationHistory as $evaluation): 
                    $performanceClass = 'performance-' . $evaluation['overall_performance'];
                    $performanceText = ucfirst(str_replace('_', ' ', $evaluation['overall_performance']));
                  ?>
                  <div class="criteria-card mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <h6 class="mb-0"><?php echo htmlspecialchars($evaluation['student_name']); ?></h6>
                      <span class="performance-badge <?php echo $performanceClass; ?>"><?php echo $performanceText; ?></span>
                    </div>
                    <p class="small text-muted mb-2">Evaluated on <?php echo date('M j, Y', strtotime($evaluation['date'])); ?> by <?php echo htmlspecialchars($evaluation['instructor_name']); ?></p>
                    <p class="mb-0"><?php echo htmlspecialchars($evaluation['remarks']); ?></p>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- /Evaluation Section -->

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
      // Set default date to today
      const today = new Date();
      document.getElementById('evaluationDate').valueAsDate = today;
      
      // Student selection handler
      document.getElementById('studentSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
          showStudentInfo(selectedOption);
        } else {
          document.getElementById('studentInfo').style.display = 'none';
        }
      });
      
      // Rating selection handler
      document.querySelectorAll('.rating-item[data-criteria]').forEach(item => {
        item.addEventListener('click', function() {
          const criteria = this.getAttribute('data-criteria');
          const value = this.getAttribute('data-value');
          
          // Update visual selection
          document.querySelectorAll(`.rating-item[data-criteria="${criteria}"]`).forEach(i => {
            i.classList.remove('selected');
          });
          this.classList.add('selected');
          
          // Update rating badge
          const badge = document.getElementById(`${criteria}Rating`);
          badge.textContent = value.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
          badge.className = `performance-badge performance-${value}`;
          
          // Update hidden input
          document.getElementById(`${criteria}Input`).value = value;
        });
      });
      
      // Form validation
      document.getElementById('evaluationForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
        }
      });
    });
    
    function showStudentInfo(option) {
      document.getElementById('studentInfo').style.display = 'block';
      document.getElementById('studentAvatar').textContent = option.text.split(' - ')[0].charAt(0);
      document.getElementById('studentName').textContent = option.text.split(' - ')[0];
      document.getElementById('studentProgram').textContent = 'Program: ' + option.getAttribute('data-program');
      document.getElementById('studentDuty').textContent = 'Current Duty: ' + option.getAttribute('data-duty-type');
      
      const completedHours = option.getAttribute('data-completed-hours');
      const requiredHours = option.getAttribute('data-required-hours');
      
      document.getElementById('completedHours').textContent = completedHours;
      document.getElementById('requiredHours').textContent = requiredHours;
      
      const progressPercent = requiredHours > 0 ? (completedHours / requiredHours) * 100 : 0;
      document.getElementById('progressPercent').textContent = `${Math.round(progressPercent)}%`;
      document.getElementById('progressBar').style.width = `${progressPercent}%`;
    }
    
    function validateForm() {
      const studentSelect = document.getElementById('studentSelect');
      const date = document.getElementById('evaluationDate');
      const remarks = document.getElementById('evaluationRemarks');
      
      if (!studentSelect.value) {
        alert('Please select a student to evaluate.');
        studentSelect.focus();
        return false;
      }
      
      if (!date.value) {
        alert('Please select an evaluation date.');
        date.focus();
        return false;
      }
      
      // Check if all criteria are rated
      const criteria = ['punctuality', 'quality', 'initiative', 'teamwork', 'overall'];
      for (const criterion of criteria) {
        const input = document.getElementById(`${criterion}Input`);
        if (!input.value) {
          alert(`Please rate ${criterion.replace('_', ' ')}.`);
          return false;
        }
      }
      
      if (!remarks.value) {
        alert('Please provide remarks for the evaluation.');
        remarks.focus();
        return false;
      }
      
      return true;
    }
  </script>

</body>

</html>