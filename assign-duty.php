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
    if (isset($_POST['batch_upload'])) {
        // Handle batch upload
        if (isset($_FILES['batch_file']) && $_FILES['batch_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['batch_file']['tmp_name'];
            $fileName = $_FILES['batch_file']['name'];
            $fileSize = $_FILES['batch_file']['size'];
            $fileType = $_FILES['batch_file']['type'];
            
            // Check if it's a CSV file
            $allowedExtensions = ['csv'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            if (in_array($fileExtension, $allowedExtensions)) {
                // Process the CSV file
                $successCount = 0;
                $errorCount = 0;
                $errors = [];
                
                // Open the file
                if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
                    // Skip the header row
                    fgetcsv($handle);
                    
                    // Process each row
                    $rowNumber = 1;
                    while (($row = fgetcsv($handle)) !== FALSE) {
                        $rowNumber++;
                        
                        // Validate row data
                        if (count($row) < 6 || empty($row[0]) || empty($row[3]) || empty($row[4])) {
                            $errors[] = "Row $rowNumber: Incomplete data";
                            $errorCount++;
                            continue;
                        }
                        
                        // Extract data from row
                        $room = trim($row[0]);
                        $section = trim($row[1]);
                        $classType = trim($row[2]);
                        $instructor = trim($row[3]);
                        $schedule = trim($row[4]);
                        $subjectCode = trim($row[5]);
                        
                        // Find student by section (you might need to adjust this logic)
                        $studentId = null;
                        foreach ($students as $student) {
                            if (strpos($student['name'], $section) !== false) {
                                $studentId = $student['id'];
                                break;
                            }
                        }
                        
                        if (!$studentId) {
                            $errors[] = "Row $rowNumber: No student found for section " . htmlspecialchars($section);
                            $errorCount++;
                            continue;
                        }
                        
                        // Insert into database
                        try {
                            $insertQuery = "INSERT INTO duties (student_id, duty_type, required_hours, assigned_by, status, room, section, instructor, schedule, subject_code) 
                                           VALUES (:student_id, :duty_type, :required_hours, :assigned_by, 'assigned', :room, :section, :instructor, :schedule, :subject_code)";
                            $stmt = $pdo->prepare($insertQuery);
                            $stmt->execute([
                                'student_id' => $studentId,
                                'duty_type' => 'Student Facilitator',
                                'required_hours' => 40, // Default value
                                'assigned_by' => $_SESSION['user_id'],
                                'room' => $room,
                                'section' => $section,
                                'instructor' => $instructor,
                                'schedule' => $schedule,
                                'subject_code' => $subjectCode
                            ]);
                            
                            $successCount++;
                        } catch (PDOException $e) {
                            $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
                            $errorCount++;
                        }
                    }
                    fclose($handle);
                    
                    if ($successCount > 0) {
                        $successMessage = "Successfully uploaded $successCount duties!";
                    }
                    
                    if ($errorCount > 0) {
                        $errorMessage = "Failed to upload $errorCount duties. " . implode("<br>", array_slice($errors, 0, 5));
                        if (count($errors) > 5) {
                            $errorMessage .= "<br>... and " . (count($errors) - 5) . " more errors";
                        }
                    }
                } else {
                    $errorMessage = "Error opening the uploaded file.";
                }
            } else {
                $errorMessage = "Invalid file type. Please upload a CSV file.";
            }
        } else {
            $errorMessage = "Please select a file to upload.";
        }
    } else {
        // Handle single duty assignment (existing code)
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
  
  <style>
    .batch-upload-btn {
      margin-top: 15px;
    }
    .format-example {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 5px;
      margin-top: 15px;
    }
    .format-example table {
      width: 100%;
      border-collapse: collapse;
    }
    .format-example th, .format-example td {
      border: 1px solid #dee2e6;
      padding: 8px;
      text-align: left;
    }
    .format-example th {
      background-color: #e9ecef;
    }
    .download-template {
      margin-top: 15px;
    }
    
    /* Custom styles for searchable dropdown */
    .dropdown-menu {
      max-height: 200px;
      overflow-y: auto;
    }
    .duty-search-container {
      position: relative;
    }
    .duty-search-container .form-control {
      padding-left: 35px;
    }
    .duty-search-container .bi {
      position: absolute;
      left: 12px;
      top: 10px;
      color: #6c757d;
    }
  </style>
</head>

<body class="index-page">

  <header id="header" class="header sticky-top">
    <div class="branding d-flex align-items-center">
      <div class="container position-relative d-flex align-items-center justify-content-between">
        <?php
        // Get user role for navigation
        $userRole = $_SESSION['role'] ?? 'student';

        // Function to check if user has access to a specific page
        function hasAccess($userRole, $page) {
            $accessMatrix = [
                'student' => ['home', 'dashboard', 'log-duty', 'view-duty'],
                'instructor' => ['home', 'dashboard', 'approve-duty', 'monitor-duty', 'evaluate-student'],
                'scholarship_officer' => ['home', 'dashboard', 'assign-duty', 'approve-duty', 'monitor-duty', 'evaluate-student'],
                'superadmin' => ['home', 'dashboard', 'assign-duty', 'approve-duty', 'log-duty', 'view-duty', 'monitor-duty', 'evaluate-student']
            ];

            return in_array($page, $accessMatrix[$userRole] ?? []);
        }
        ?>

        <a href="index_user.php" class="logo d-flex align-items-center">
          <img src="assets/img/CSDL logo.png" alt="">
          <h1 class="sitename">CSDL</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index_user.php">Home</a></li>
            <li><a href="dashboard.php">Dashboard</a></li>

            <?php
            // Duty Options dropdown - show only accessible options
            $dutyOptions = [];
            if (hasAccess($userRole, 'assign-duty')) $dutyOptions[] = ['url' => 'assign-duty.php', 'text' => 'Assign Duty'];
            if (hasAccess($userRole, 'approve-duty')) $dutyOptions[] = ['url' => 'approve-duty.php', 'text' => 'Approve Duty'];
            if (hasAccess($userRole, 'log-duty')) $dutyOptions[] = ['url' => 'log-duty.php', 'text' => 'Log Duty'];
            if (hasAccess($userRole, 'view-duty')) $dutyOptions[] = ['url' => 'view-duty.php', 'text' => 'View Duty'];
            if (hasAccess($userRole, 'monitor-duty')) $dutyOptions[] = ['url' => 'monitor-duty.php', 'text' => 'Monitor Duty'];

            if (!empty($dutyOptions)):
            ?>
            <li class="dropdown">
              <a href="#">Duty Options</a>
              <ul class="dropdown-menu">
                <?php foreach ($dutyOptions as $option): ?>
                <li><a href="<?php echo $option['url']; ?>"><?php echo $option['text']; ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
            <?php endif; ?>

            <?php if (hasAccess($userRole, 'evaluate-student')): ?>
            <li><a href="evaluate-student.php">Evaluate Student</a></li>
            <?php endif; ?>

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
        
        <!-- Batch Upload Button -->
        <div class="text-end mb-4">
          <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#batchUploadModal">
            <i class="bi bi-upload me-2"></i>Batch Upload
          </button>
        </div>
        
        <form method="POST" action="assign-duty.php" enctype="multipart/form-data">
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
                  <div class="duty-search-container mb-3">
                    <i class="bi bi-search"></i>
                    <input type="text" class="form-control" id="dutySearch" placeholder="Search duty types...">
                  </div>
                  
                  <select class="form-select" id="dutyType" name="duty_type" required>
                    <option value="" selected disabled>Select a duty type</option>
                    <option value="ID Station">ID Station</option>
                    <option value="Library Assistant">Library Assistant</option>
                    <option value="Office Assistant">Office Assistant</option>
                    <option value="Checker">Checker</option>
                    <option value="Student Marshall">Student Marshall</option>
                    <option value="Student Facilitator">Student Facilitator</option>
                    <option value="Other">Other (Please specify)</option>
                  </select>
                  
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

    <!-- Batch Upload Modal -->
    <div class="modal fade" id="batchUploadModal" tabindex="-1" aria-labelledby="batchUploadModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="batchUploadModalLabel">Batch Upload Duties</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <p>Upload a CSV file with the following format to assign multiple duties at once:</p>
            
            <div class="format-example">
              <h6>Required Format:</h6>
              <table>
                <thead>
                  <tr>
                    <th>Room</th>
                    <th>Section</th>
                    <th>Class Type (Rad, Flex)</th>
                    <th>Instructor</th>
                    <th>Schedule (Time & Date)</th>
                    <th>Subject Code</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>1</td>
                    <td>Fa-coc-peurto 1</td>
                    <td>Flex</td>
                    <td>Mr. Amihan</td>
                    <td>1-2:30pm Every Thursday</td>
                    <td>App - 001</td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Fa2-ML-Puerto</td>
                    <td>Flex</td>
                    <td>Mr.Morales</td>
                    <td>7-9am Every Friday</td>
                    <td>ML -101</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="download-template">
              <a href="data:text/csv;charset=utf-8,Room,Section,Class Type (Rad, Flex),Instructor,Schedule (Time & Date),Subject Code
1,Fa-coc-peurto 1,Flex,Mr. Amihan,1-2:30pm Every Thursday,App - 001
2,Fa2-ML-Puerto,Flex,Mr.Morales,7-9am Every Friday,ML - 101
3,Fa4-Graduate,Flex,Mr. Amihan,8-10:30am Every Monday,Eng - 001" download="duty_template.csv" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i>Download CSV Template
              </a>
            </div>
            
            <div class="mt-4">
              <form method="POST" action="assign-duty.php" enctype="multipart/form-data" id="batchUploadForm">
                <input type="hidden" name="batch_upload" value="1">
                <div class="mb-3">
                  <label for="batchFile" class="form-label">Select CSV File</label>
                  <input class="form-control" type="file" id="batchFile" name="batch_file" accept=".csv" required>
                  <div class="form-text">Only CSV files are accepted. Maximum file size: 2MB</div>
                </div>
              </form>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('batchUploadForm').submit();">Upload File</button>
          </div>
        </div>
      </div>
    </div>

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
      const dutyTypeSelect = document.getElementById('dutyType');
      dutyTypeSelect.addEventListener('change', function() {
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
      
      // Duty type search functionality
      const dutySearch = document.getElementById('dutySearch');
      dutySearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const options = dutyTypeSelect.options;
        
        for (let i = 0; i < options.length; i++) {
          const option = options[i];
          if (option.text.toLowerCase().includes(searchTerm)) {
            option.style.display = '';
          } else {
            option.style.display = 'none';
          }
        }
      });
      
      // Hours input change
      document.getElementById('requiredHours').addEventListener('input', function() {
        if (selectedStudentId) {
          const studentItem = document.querySelector('.student-item.selected');
          const studentName = studentItem.querySelector('h6').textContent;
          const dutyType = dutyTypeSelect.value;
          updateSummary(studentName, dutyType, this.value);
        }
      });
      
      // Form submission validation
      document.querySelector('form').addEventListener('submit', function(e) {
        if (!selectedStudentId) {
          e.preventDefault();
          alert('Please select a student first.');
          return;
        }
        
        if (!dutyTypeSelect.value) {
          e.preventDefault();
          alert('Please select a duty type.');
          return;
        }
        
        if (dutyTypeSelect.value === 'Other') {
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