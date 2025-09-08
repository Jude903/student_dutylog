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

// Fetch students from database
$studentsQuery = "SELECT id, username as name, email, department as program FROM users WHERE role = 'student'";
$studentsStmt = $pdo->query($studentsQuery);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch supervisors (scholarship officers and admins)
$supervisorsQuery = "SELECT id, username as name FROM users WHERE role IN ('scholarship_officer', 'superadmin')";
$supervisorsStmt = $pdo->query($supervisorsQuery);
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = $_POST['student_id'];
    $dutyType = $_POST['duty_type'];
    $requiredHours = $_POST['required_hours'];
    $report_on = $_POST['report_on'];
    $description = $_POST['description'];
    $assignedBy = $_POST['assigned_by'];
    
    try {
        $insertQuery = "INSERT INTO duties (student_id, duty_type, required_hours, assigned_by, status) 
                       VALUES (:student_id, :duty_type, :required_hours, :assigned_by, 'assigned')";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'student_id' => $studentId,
            'duty_type' => $dutyType,
            'required_hours' => $requiredHours,
            'assigned_by' => $assignedBy
        ]);
        
        $successMessage = "Duty successfully assigned!";
    } catch (PDOException $e) {
        $errorMessage = "Error assigning duty: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Duty Assignment System">
  <meta name="keywords" content="PHINMA COC, student duty, duty assignment, college management, Cagayan de Oro">

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
  <link href="assets/css/assign-duty.css" rel="stylesheet">
  
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
                  <li><a href="assign-duty.php" class="active"></i>Assign Duty</a></li>
                  <li><a href="approve-duty.php"></i>Approve Duty</a></li>
                  <li><a href="log-duty.php"></i>Log Duty</a></li>
                  <li><a href="view-duty.php"></i>View Duty</a></li>
                  <li><a href="monitor-duty.php"></i>Monitor Duty</a></li>
              </ul>
          </li>
            <li><a href="evaluate-student.php">Evaluate Student</a></li>
            <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
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
        <h1 class="mb-2 mb-lg-0">Assign Duty</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Assign Duty</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Assignment Section -->
    <section id="assignment" class="assignment section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        
        <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $successMessage; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $errorMessage; ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="assign-duty.php">
          <div class="row">
            <div class="col-lg-4">
              <div class="assignment-section">
                <h3 class="section-title">Select Student</h3>
                <div class="form-group mb-3">
                  <input type="text" class="form-control" id="studentSearch" placeholder="Search students...">
                </div>
                <div class="student-list" id="studentList">
                  <?php foreach ($students as $student): ?>
                  <div class="student-item" data-id="<?php echo $student['id']; ?>">
                    <h6 class="mb-1"><?php echo htmlspecialchars($student['name']); ?></h6>
                    <small class="text-muted"><?php echo htmlspecialchars($student['program']); ?> • <?php echo htmlspecialchars($student['email']); ?></small>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
              
              <div class="summary-card mt-4">
                <h4>Duty Summary</h4>
                <div id="dutySummary">
                  <p class="mb-1">No student selected</p>
                  <p class="mb-1">No duty type chosen</p>
                  <p class="mb-0">0 hours required</p>
                </div>
              </div>
            </div>
            
            <div class="col-lg-8">
              <div class="duty-details">
                <h3 class="section-title">Duty Information</h3>
                
                <input type="hidden" id="selectedStudentId" name="student_id">
                
                <div class="form-section">
                  <h5>Duty Type</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="idStation" value="ID Station" required>
                        <label class="form-check-label" for="idStation">
                          ID Station
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="libraryAssistant" value="Library Assistant">
                        <label class="form-check-label" for="libraryAssistant">
                          Library Assistant
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="officeAssistant" value="Office Assistant">
                        <label class="form-check-label" for="officeAssistant">
                          Office Assistant
                        </label>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="eventSupport" value="Event Support">
                        <label class="form-check-label" for="eventSupport">
                          Event Support
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="labAssistant" value="Lab Assistant">
                        <label class="form-check-label" for="labAssistant">
                          Lab Assistant
                        </label>
                      </div>
                      <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="duty_type" id="other" value="Other">
                        <label class="form-check-label" for="other">
                          Other
                        </label>
                      </div>
                    </div>
                  </div>
                  
                  <div id="customDutyType" class="mt-3" style="display: none;">
                    <label for="otherDutyType" class="form-label">Specify Duty Type</label>
                    <input type="text" class="form-control" id="otherDutyType" name="custom_duty_type">
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Hours Requirement</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="requiredHours" class="form-label">Required Hours</label>
                      <input type="number" class="form-control" id="requiredHours" name="required_hours" min="1" value="40" required>
                    </div>
                    <div class="col-md-6">
                      <label for="report_on" class="form-label">Report On</label>
                      <input type="date" class="form-control" id="report_on" name="report_on" required>
                    </div>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Duty Description</h5>
                  <div class="mb-3">
                    <label for="dutyDescription" class="form-label">Responsibilities and Tasks</label>
                    <textarea class="form-control" id="dutyDescription" name="description" rows="4" placeholder="Describe the duties, responsibilities, and expectations..."></textarea>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Supervisor Information</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="supervisor" class="form-label">Assigned By</label>
                      <select class="form-control" id="supervisor" name="assigned_by" required>
                        <?php foreach ($supervisors as $supervisor): ?>
                        <option value="<?php echo $supervisor['id']; ?>"><?php echo htmlspecialchars($supervisor['name']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg" id="assignDutyBtn">Assign Duty</button>
                </div>
              </div>
            </div>
          </div>
        </form>

      </div>
    </section><!-- /Assignment Section -->

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
      // Set default report_on to two weeks from now
      const defaultReport_on = new Date();
      defaultReport_on.setDate(defaultReport_on.getDate() + 14);
      document.getElementById('report_on').valueAsDate = defaultReport_on;
      
      // Student selection
      let selectedStudentId = null;
      const studentList = document.getElementById('studentList');
      studentList.addEventListener('click', function(e) {
        const studentItem = e.target.closest('.student-item');
        if (studentItem) {
          // Remove previous selection
          document.querySelectorAll('.student-item.selected').forEach(item => {
            item.classList.remove('selected');
          });
          
          // Add selection to clicked item
          studentItem.classList.add('selected');
          selectedStudentId = studentItem.dataset.id;
          document.getElementById('selectedStudentId').value = selectedStudentId;
          
          // Update summary
          const studentName = studentItem.querySelector('h6').textContent;
          updateSummary(studentName);
        }
      });
      
      // Student search
      document.getElementById('studentSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll('.student-item').forEach(item => {
          const studentText = item.textContent.toLowerCase();
          item.style.display = studentText.includes(searchTerm) ? 'block' : 'none';
        });
      });
      
      // Duty type selection
      document.querySelectorAll('input[name="duty_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
          // Show/hide custom duty type field
          document.getElementById('customDutyType').style.display = 
            this.value === 'Other' ? 'block' : 'none';
            
          // Update summary if a student is selected
          if (selectedStudentId) {
            const studentItem = document.querySelector('.student-item.selected');
            const studentName = studentItem.querySelector('h6').textContent;
            updateSummary(studentName, this.value);
          }
        });
      });
      
      // Hours input change
      document.getElementById('requiredHours').addEventListener('input', function() {
        if (selectedStudentId) {
          const studentItem = document.querySelector('.student-item.selected');
          const studentName = studentItem.querySelector('h6').textContent;
          const dutyType = document.querySelector('input[name="duty_type"]:checked');
          updateSummary(studentName, dutyType ? dutyType.value : null, this.value);
        }
      });
      
      // Form submission validation
      document.querySelector('form').addEventListener('submit', function(e) {
        if (!selectedStudentId) {
          e.preventDefault();
          alert('Please select a student first.');
          return;
        }
        
        const dutyTypeEl = document.querySelector('input[name="duty_type"]:checked');
        if (!dutyTypeEl) {
          e.preventDefault();
          alert('Please select a duty type.');
          return;
        }
        
        if (dutyTypeEl.value === 'Other') {
          const customDutyType = document.getElementById('otherDutyType').value;
          if (!customDutyType) {
            e.preventDefault();
            alert('Please specify the duty type.');
            return;
          }
        }
      });
      
      // Update summary function
      function updateSummary(studentName = null, dutyType = null, hours = null) {
        const summaryEl = document.getElementById('dutySummary');
        
        if (!studentName) {
          summaryEl.innerHTML = `
            <p class="mb-1">No student selected</p>
            <p class="mb-1">No duty type chosen</p>
            <p class="mb-0">0 hours required</p>
          `;
          return;
        }
        
        let html = `<p class="mb-1"><strong>Student:</strong> ${studentName}</p>`;
        
        if (dutyType) {
          html += `<p class="mb-1"><strong>Duty Type:</strong> ${dutyType}</p>`;
        } else {
          html += `<p class="mb-1">No duty type chosen</p>`;
        }
        
        if (hours) {
          html += `<p class="mb-0"><strong>Hours Required:</strong> ${hours}</p>`;
        } else {
          html += `<p class="mb-0">0 hours required</p>`;
        }
        
        summaryEl.innerHTML = html;
      }
    });
  </script>

</body>

</html>
