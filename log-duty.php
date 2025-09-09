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
// Check if user is logged in as a student
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'student') {
  // Not a logged-in student; use default/test student id
  $studentId = 7; // Default student ID for testing
} else {
  $studentId = $_SESSION['user_id'];
}

// Fetch student's duties
$dutiesQuery = "SELECT id, duty_type, required_hours FROM duties WHERE student_id = :student_id AND status != 'completed'";
$dutiesStmt = $pdo->prepare($dutiesQuery);
$dutiesStmt->execute(['student_id' => $studentId]);
$duties = $dutiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student's recent duty logs
$logsQuery = "
    SELECT de.*, d.duty_type 
    FROM duty_entries de 
    JOIN duties d ON de.duty_id = d.id 
    WHERE de.student_id = :student_id 
    ORDER BY de.created_at DESC 
    LIMIT 5
";
$logsStmt = $pdo->prepare($logsQuery);
$logsStmt->execute(['student_id' => $studentId]);
$recentLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$statsQuery = "
    SELECT 
        COALESCE(SUM(de.hours), 0) as total_hours,
        SUM(CASE WHEN de.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN de.status = 'approved' THEN 1 ELSE 0 END) as completed_count
    FROM duty_entries de 
    WHERE de.student_id = :student_id
";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute(['student_id' => $studentId]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dutyId = $_POST['duty_id'];
    $date = $_POST['date'];
    $timeIn = $_POST['time_in'];
    $timeOut = $_POST['time_out'];
    $hours = $_POST['hours'];
    $taskDescription = $_POST['task_description'];
    $signatureData = $_POST['signature_data'];
    
    // Convert base64 signature to binary
    if ($signatureData) {
        $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
        $signatureData = str_replace(' ', '+', $signatureData);
        $signatureBinary = base64_decode($signatureData);
    } else {
        $signatureBinary = null;
    }
    
    try {
        $insertQuery = "INSERT INTO duty_entries (duty_id, student_id, hours, task_description, date, status, signature_data) 
                       VALUES (:duty_id, :student_id, :hours, :task_description, :date, 'pending', :signature_data)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'duty_id' => $dutyId,
            'student_id' => $studentId,
            'hours' => $hours,
            'task_description' => $taskDescription,
            'date' => $date,
            'signature_data' => $signatureBinary
        ]);
        
        $successMessage = "Duty log submitted successfully! It is now pending approval.";
        
        // Refresh page to show updated data
        header("Location: log-duty.php?success=" . urlencode($successMessage));
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Error submitting duty log: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Duty Log System">
  <meta name="keywords" content="PHINMA COC, student duty, duty log, college management, Cagayan de Oro">

  <link href="assets/img/CSDL logo.png" rel="icon">
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
  <link href="assets/css/log-duty.css" rel="stylesheet">
  
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
                  <li><a href="log-duty.php" class="active"></i>Log Duty</a></li>
                  <li><a href="view-duty.php"></i>View Duty</a></li>
                  <li><a href="monitor-duty.php"></i>Monitor Duty</a></li>
              </ul>
          </li>
            <li><a href="evaluate-student.php">Evaluate Student</a></li>
            <li><a href="logout.php">Logout</a></li>           </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>
    </div>
  </header>


  <main class="main">

    <!-- Page Title -->
    <div class="page-title light-background">
      <div class="container d-lg-flex justify-content-between align-items-center">
        <h1 class="mb-2 mb-lg-0">Log Duty Hours</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Log Duty</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Log Section -->
    <section id="log" class="log section">
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
              <p class="stats-number" id="totalHours"><?php echo number_format($stats['total_hours'], 1); ?></p>
              <p class="stats-label">Total Hours Logged</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="pendingCount"><?php echo $stats['pending_count']; ?></p>
              <p class="stats-label">Pending Approval</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="completedCount"><?php echo $stats['completed_count']; ?></p>
              <p class="stats-label">Completed Duties</p>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col-lg-8">
            <div class="log-section">
              <h3 class="section-title">Duty Information</h3>
              
              <form method="POST" action="log-duty.php" id="dutyForm">
                <div class="form-section">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="dutySelect" class="form-label">Select Duty</label>
                        <select class="form-control" id="dutySelect" name="duty_id" required>
                          <option value="">-- Select Duty --</option>
                          <?php foreach ($duties as $duty): ?>
                          <option value="<?php echo $duty['id']; ?>">
                            <?php echo htmlspecialchars($duty['duty_type']); ?> (<?php echo $duty['required_hours']; ?> hours required)
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group mb-3">
                        <label for="dutyDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="dutyDate" name="date" required>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Time Log</h5>
                  <div class="time-inputs">
                    <div class="form-group">
                      <label for="timeIn" class="form-label">Time In</label>
                      <input type="time" class="form-control" id="timeIn" name="time_in" required>
                    </div>
                    <div class="form-group">
                      <label for="timeOut" class="form-label">Time Out</label>
                      <input type="time" class="form-control" id="timeOut" name="time_out" required>
                    </div>
                  </div>
                  <div class="hours-calculated" id="hoursCalculated">
                    Total hours: 0.00
                  </div>
                  <input type="hidden" id="hoursInput" name="hours" value="0">
                </div>
                
                <div class="form-section">
                  <h5>Task Description</h5>
                  <div class="mb-3">
                    <label for="taskDescription" class="form-label">Describe tasks performed during this duty period</label>
                    <textarea class="form-control" id="taskDescription" name="task_description" rows="4" placeholder="Describe the duties and tasks you performed..." required></textarea>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Signature</h5>
                  <p class="text-muted">Please provide your signature to verify this duty log</p>
                  
                  <div class="signature-pad-container">
                    <div class="signature-actions">
                      <button type="button" class="btn btn-outline-secondary btn-sm" id="clearSignature">
                        <i class="bi bi-x-circle"></i> Clear
                      </button>
                    </div>
                    <canvas id="signaturePad" class="signature-pad" width="500" height="200"></canvas>
                    <input type="hidden" id="signatureData" name="signature_data">
                  </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg" id="submitDutyBtn">
                    <i class="bi bi-check-circle"></i> Submit Duty Log
                  </button>
                </div>
              </form>
            </div>
          </div>
          
          <div class="col-lg-4">
            <div class="log-section">
              <h3 class="section-title">Recent Logs</h3>
              <div id="recentLogs">
                <?php if (empty($recentLogs)): ?>
                <div class="text-center py-4">
                  <i class="bi bi-inbox display-4 text-muted"></i>
                  <p class="mt-2">No recent duty logs</p>
                </div>
                <?php else: ?>
                  <?php foreach ($recentLogs as $log): ?>
                  <div class="duty-card">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="card-title"><?php echo htmlspecialchars($log['duty_type']); ?></h6>
                        <span class="status-badge status-<?php echo $log['status']; ?>">
                          <?php echo ucfirst($log['status']); ?>
                        </span>
                      </div>
                      
                      <p class="card-text small"><?php echo htmlspecialchars($log['task_description']); ?></p>
                      
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="time-badge"><i class="bi bi-calendar"></i> <?php echo $log['date']; ?></span>
                        <span class="time-badge"><i class="bi bi-clock"></i> <?php echo $log['hours']; ?>h</span>
                      </div>
                    </div>
                  </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section><!-- /Log Section -->

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
      document.getElementById('dutyDate').valueAsDate = today;
      
      // Initialize signature pad
      const canvas = document.getElementById('signaturePad');
      const ctx = canvas.getContext('2d');
      let isDrawing = false;
      let lastX = 0;
      let lastY = 0;
      
      // Set up signature pad
      ctx.lineWidth = 2;
      ctx.lineJoin = 'round';
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#000';
      
      canvas.addEventListener('mousedown', startDrawing);
      canvas.addEventListener('mousemove', draw);
      canvas.addEventListener('mouseup', stopDrawing);
      canvas.addEventListener('mouseout', stopDrawing);
      
      // Touch events for mobile devices
      canvas.addEventListener('touchstart', handleTouchStart);
      canvas.addEventListener('touchmove', handleTouchMove);
      canvas.addEventListener('touchend', handleTouchEnd);
      
      // Clear signature button
      document.getElementById('clearSignature').addEventListener('click', clearSignature);
      
      // Calculate hours when time inputs change
      document.getElementById('timeIn').addEventListener('change', calculateHours);
      document.getElementById('timeOut').addEventListener('change', calculateHours);
      
      // Form validation
      document.getElementById('dutyForm').addEventListener('submit', function(e) {
        if (!validateForm()) {
          e.preventDefault();
        }
      });
    });
    
    function calculateHours() {
      const timeIn = document.getElementById('timeIn').value;
      const timeOut = document.getElementById('timeOut').value;
      
      if (!timeIn || !timeOut) {
        document.getElementById('hoursCalculated').textContent = 'Total hours: 0.00';
        document.getElementById('hoursInput').value = 0;
        return 0;
      }
      
      // Calculate hours between time in and time out
      const [inHours, inMinutes] = timeIn.split(':').map(Number);
      const [outHours, outMinutes] = timeOut.split(':').map(Number);
      
      let totalMinutes = (outHours * 60 + outMinutes) - (inHours * 60 + inMinutes);
      
      // Handle overnight duties (if time out is earlier than time in, assume next day)
      if (totalMinutes < 0) {
        totalMinutes += 24 * 60; // Add 24 hours
      }
      
      const hours = totalMinutes / 60;
      document.getElementById('hoursCalculated').textContent = `Total hours: ${hours.toFixed(2)}`;
      document.getElementById('hoursInput').value = hours.toFixed(2);
      
      return hours;
    }
    
    function validateForm() {
      const dutySelect = document.getElementById('dutySelect');
      const date = document.getElementById('dutyDate');
      const timeIn = document.getElementById('timeIn');
      const timeOut = document.getElementById('timeOut');
      const taskDescription = document.getElementById('taskDescription');
      const signatureData = document.getElementById('signatureData');
      const hours = calculateHours();
      
      if (!dutySelect.value) {
        alert('Please select a duty.');
        dutySelect.focus();
        return false;
      }
      
      if (!date.value) {
        alert('Please select a date.');
        date.focus();
        return false;
      }
      
      if (!timeIn.value) {
        alert('Please enter time in.');
        timeIn.focus();
        return false;
      }
      
      if (!timeOut.value) {
        alert('Please enter time out.');
        timeOut.focus();
        return false;
      }
      
      if (hours <= 0) {
        alert('Time out must be after time in.');
        timeOut.focus();
        return false;
      }
      
      if (!taskDescription.value) {
        alert('Please describe the tasks you performed.');
        taskDescription.focus();
        return false;
      }
      
      if (!signatureData.value) {
        alert('Please provide your signature.');
        return false;
      }
      
      return true;
    }
    
    // Signature pad functions
    function startDrawing(e) {
      isDrawing = true;
      [lastX, lastY] = [e.offsetX, e.offsetY];
    }
    
    function draw(e) {
      if (!isDrawing) return;
      
      const canvas = document.getElementById('signaturePad');
      const ctx = canvas.getContext('2d');
      
      ctx.beginPath();
      ctx.moveTo(lastX, lastY);
      ctx.lineTo(e.offsetX, e.offsetY);
      ctx.stroke();
      
      [lastX, lastY] = [e.offsetX, e.offsetY];
      
      // Save signature data
      document.getElementById('signatureData').value = canvas.toDataURL();
    }
    
    function stopDrawing() {
      isDrawing = false;
    }
    
    function handleTouchStart(e) {
      e.preventDefault();
      const touch = e.touches[0];
      const rect = e.target.getBoundingClientRect();
      const mouseEvent = new MouseEvent('mousedown', {
        clientX: touch.clientX,
        clientY: touch.clientY,
        offsetX: touch.clientX - rect.left,
        offsetY: touch.clientY - rect.top
      });
      document.getElementById('signaturePad').dispatchEvent(mouseEvent);
    }
    
    function handleTouchMove(e) {
      e.preventDefault();
      const touch = e.touches[0];
      const rect = e.target.getBoundingClientRect();
      const mouseEvent = new MouseEvent('mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY,
        offsetX: touch.clientX - rect.left,
        offsetY: touch.clientY - rect.top
      });
      document.getElementById('signaturePad').dispatchEvent(mouseEvent);
    }
    
    function handleTouchEnd(e) {
      e.preventDefault();
      const mouseEvent = new MouseEvent('mouseup');
      document.getElementById('signaturePad').dispatchEvent(mouseEvent);
    }
    
    function clearSignature() {
      const canvas = document.getElementById('signaturePad');
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      document.getElementById('signatureData').value = '';
    }
  </script>

</body>

</html>