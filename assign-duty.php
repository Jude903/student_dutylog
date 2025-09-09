<?php
session_start();
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'config/config.php';

// Fetch students
$studentsQuery = "SELECT id, username as name, email, department as program FROM users WHERE role = 'student'";
$studentsStmt = $pdo->query($studentsQuery);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch supervisors
$supervisorsQuery = "SELECT id, username as name FROM users WHERE role IN ('scholarship_officer', 'superadmin')";
$supervisorsStmt = $pdo->query($supervisorsQuery);
$supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle single form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
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

// Handle batch upload for Student Facilitator
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['batch_file'])) {
    $file = $_FILES['batch_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "File upload failed with error code: " . $file['error'];
    } else {
        // Check file type
        $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array(strtolower($fileType), ['csv', 'xlsx', 'xls'])) {
            $errorMessage = "Only CSV and Excel files are allowed.";
        } else {
            // Process the file based on type
            if (strtolower($fileType) === 'csv') {
                $result = processCSVFile($file['tmp_name'], $pdo, $_SESSION['user_id']);
            } else {
                $result = processExcelFile($file['tmp_name'], $pdo, $_SESSION['user_id']);
            }
            
            if ($result['success']) {
                $successMessage = "Batch upload completed! " . $result['message'];
            } else {
                $errorMessage = "Batch upload failed: " . $result['message'];
            }
        }
    }
}

// Function to process CSV file for Student Facilitator duties
function processCSVFile($filePath, $pdo, $assignedBy) {
    $handle = fopen($filePath, 'r');
    if (!$handle) {
        return ['success' => false, 'message' => 'Could not open CSV file.'];
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $row = 0;
    
    // Skip header row
    fgetcsv($handle);
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        $row++;
        if (count($data) < 6) {
            $errors[] = "Row $row: Insufficient data columns";
            $errorCount++;
            continue;
        }
        
        $room = trim($data[0]);
        $section = trim($data[1]);
        $classType = trim($data[2]);
        $instructor = trim($data[3]);
        $schedule = trim($data[4]);
        $subjectCode = trim($data[5]);
        
        // Process the data for Student Facilitator duty
        $result = processFacilitatorDuty($room, $section, $classType, $instructor, $schedule, $subjectCode, $assignedBy, $pdo);
        
        if ($result['success']) {
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = "Row $row: " . $result['message'];
        }
    }
    
    fclose($handle);
    
    $message = "Successfully processed $successCount Student Facilitator assignments.";
    if ($errorCount > 0) {
        $message .= " $errorCount errors occurred: " . implode("; ", array_slice($errors, 0, 5));
        if (count($errors) > 5) {
            $message .= " and " . (count($errors) - 5) . " more errors.";
        }
    }
    
    return ['success' => true, 'message' => $message];
}

// Function to process Excel file (simplified - would need PHPExcel or PhpSpreadsheet)
function processExcelFile($filePath, $pdo, $assignedBy) {
    // This is a placeholder - you would need to implement Excel processing
    // using a library like PhpSpreadsheet
    return ['success' => false, 'message' => 'Excel processing not implemented in this example.'];
}

// Function to process Student Facilitator duty data
function processFacilitatorDuty($room, $section, $classType, $instructor, $schedule, $subjectCode, $assignedBy, $pdo) {
    try {
        // Create duty description based on the data
        $description = "Student Facilitator for $classType class in Room $room\n";
        $description .= "Section: $section\n";
        $description .= "Instructor: $instructor\n";
        $description .= "Schedule: $schedule\n";
        $description .= "Subject Code: $subjectCode";
        
        // For this example, we'll need to assign to students based on some logic
        // You might want to modify this to match students by section or other criteria
        
        // Find a student to assign (this is simplified - you might want to implement better logic)
        $studentQuery = "SELECT id FROM users WHERE role = 'student' AND department LIKE ? LIMIT 1";
        $stmt = $pdo->prepare($studentQuery);
        $stmt->execute(["%$section%"]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['success' => false, 'message' => "No suitable student found for section $section"];
        }
        
        // Insert the duty assignment
        $insertQuery = "INSERT INTO duties (student_id, duty_type, required_hours, assigned_by, status, description) 
                       VALUES (:student_id, 'Student Facilitator', 40, :assigned_by, 'assigned', :description)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'student_id' => $student['id'],
            'assigned_by' => $assignedBy,
            'description' => $description
        ]);
        
        return ['success' => true, 'message' => 'Student Facilitator duty assigned successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
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
        <?php
        // Get user role for navigation
        $userRole = $_SESSION['role'] ?? 'student';

        // Function to check if user has access to a specific page
        function hasAccess($userRole, $page) {
            $accessMatrix = [
                'student' => ['home', 'dashboard', 'log-duty', 'view-duty'],
                'instructor' => ['home', 'dashboard', 'approve-duty', 'monitor-duty', 'evaluate-student'],
                'scholarship_officer' => ['home', 'dashboard', 'assign-duty', 'approve-duty', 'monitor-duty', 'evaluate-student'],
                'superadmin' => ['home', 'dashboard', 'assign-duty', 'approve-duty', 'log-duty', 'view-duty', 'monitor-duty', 'evaluate-student', 'create-student', 'create-instructor', 'create-employee']
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

            <?php
            // Create Accounts dropdown - show only accessible options
            $createOptions = [];
            if (hasAccess($userRole, 'create-student')) $createOptions[] = ['url' => 'create-student.php', 'text' => 'Create Student'];
            if (hasAccess($userRole, 'create-instructor')) $createOptions[] = ['url' => 'create-instructor.php', 'text' => 'Create Instructor'];
            if (hasAccess($userRole, 'create-employee')) $createOptions[] = ['url' => 'create-employee.php', 'text' => 'Create Employee'];

            if (!empty($createOptions)):
            ?>
            <li class="dropdown">
              <a href="#">Create Accounts</a>
              <ul class="dropdown-menu">
                <?php foreach ($createOptions as $option): ?>
                <li><a href="<?php echo $option['url']; ?>"><?php echo $option['text']; ?></a></li>
                <?php endforeach; ?>
              </ul>
            </li>
            <?php endif; ?>

            <?php if (hasAccess($userRole, 'evaluate-student')): ?>
            <li><a href="evaluate-student.php">Evaluate Student</a></li>
            <?php endif; ?>

            <li><a href="logout.php"> Logout</a></li>          </ul>
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
        <div class="d-flex justify-content-end mb-4">
          <button type="button" class="btn batch-upload-btn" data-bs-toggle="modal" data-bs-target="#batchUploadModal">
            <i class="bi bi-upload me-2"></i>Batch Upload
          </button>
        </div>
        
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
                <input type="hidden" id="selectedDutyType" name="duty_type" required>
                
                <div class="form-section">
                  <h5>Duty Type</h5>
                  <div class="searchable-dropdown" id="dutyTypeDropdown">
                    <div class="dropdown-input" id="dutyTypeInput">
                      Select duty type...
                    </div>
                    <div class="dropdown-options" id="dutyTypeOptions">
                      <input type="text" class="search-input" placeholder="Search duty types..." id="dutyTypeSearch">
                      <div class="dropdown-option" data-value="ID Station">ID Station</div>
                      <div class="dropdown-option" data-value="Library Assistant">Library Assistant</div>
                      <div class="dropdown-option" data-value="Office Assistant">Office Assistant</div>
                      <div class="dropdown-option" data-value="Checker">Checker</div>
                      <div class="dropdown-option" data-value="Student Marshall">Student Marshall</div>
                      <div class="dropdown-option" data-value="Student Facilitator">Student Facilitator</div>
                    </div>
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

  <!-- Batch Upload Modal -->
  <div class="modal fade" id="batchUploadModal" tabindex="-1" aria-labelledby="batchUploadModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="batchUploadModalLabel">
            <i class="bi bi-upload me-2"></i>Batch Upload - Student Facilitator
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3">File Format Required:</h6>
            <p class="text-muted mb-3">
              Upload a CSV or Excel file with the following column structure. Make sure your file includes all required columns in the exact order shown below:
            </p>
            
            <div class="table-responsive">
              <table class="table table-bordered format-table">
                <thead class="table-light">
                  <tr>
                    <th>Room</th>
                    <th>Section</th>
                    <th>Class Type</th>
                    <th>Instructor</th>
                    <th>Schedule</th>
                    <th>Subject Code</th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="text-muted">
                    <td>Room number or location</td>
                    <td>Class section identifier</td>
                    <td>Rad or Flex</td>
                    <td>Instructor name</td>
                    <td>Time & day schedule</td>
                    <td>Subject code</td>
                  </tr>
                </tbody>
              </table>
            </div>
            
            <div class="alert alert-info d-flex align-items-center" role="alert">
              <i class="bi bi-info-circle-fill me-2"></i>
              <div>
                <strong>Note:</strong> This batch upload will automatically assign "Student Facilitator" duty type to students with 80 hours requirement.
              </div>
            </div>
          </div>
          
          <form method="POST" enctype="multipart/form-data" id="batchUploadForm">
            <div class="upload-area" id="uploadArea">
              <i class="bi bi-cloud-upload fs-1 text-primary mb-3"></i>
              <h6 class="mb-2">Drag and drop your file here</h6>
              <p class="text-muted mb-3">or</p>
              <input type="file" class="form-control" id="batchFile" name="batch_file" accept=".csv,.xlsx,.xls" required style="display: none;">
              <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('batchFile').click();">
                <i class="bi bi-folder2-open me-1"></i>Browse Files
              </button>
              <p class="text-muted mt-2 mb-0">
                <small>Supported formats: CSV, Excel (.xlsx, .xls)</small>
              </p>
            </div>
            
            <div id="selectedFile" class="mt-3" style="display: none;">
              <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-file-earmark-check-fill me-2"></i>
                <div>
                  <strong>Selected file:</strong> <span id="fileName"></span>
                </div>
              </div>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <a href="#" class="btn btn-outline-primary me-2" id="downloadTemplate">
            <i class="bi bi-download me-1"></i>Download Template
          </a>
          <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
            <i class="bi bi-upload me-1"></i>Upload and Process
          </button>
          </form>
        </div>
      </div>
    </div>
  </div>

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
      
      // Searchable dropdown functionality
      const dropdown = document.getElementById('dutyTypeDropdown');
      const dropdownInput = document.getElementById('dutyTypeInput');
      const dropdownOptions = document.getElementById('dutyTypeOptions');
      const dutyTypeSearch = document.getElementById('dutyTypeSearch');
      const hiddenInput = document.getElementById('selectedDutyType');
      
      // Toggle dropdown
      dropdownInput.addEventListener('click', function() {
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
          dutyTypeSearch.focus();
        }
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target)) {
          dropdown.classList.remove('open');
        }
      });
      
      // Search functionality
      dutyTypeSearch.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const options = document.querySelectorAll('.dropdown-option');
        
        options.forEach(option => {
          const optionText = option.textContent.toLowerCase();
          option.style.display = optionText.includes(searchTerm) ? 'block' : 'none';
        });
      });
      
      // Option selection
      document.querySelectorAll('.dropdown-option').forEach(option => {
        option.addEventListener('click', function() {
          const value = this.dataset.value;
          const text = this.textContent;
          
          // Update display and hidden input
          dropdownInput.textContent = text;
          hiddenInput.value = value;
          
          // Remove selected class from all options
          document.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.classList.remove('selected');
          });
          
          // Add selected class to clicked option
          this.classList.add('selected');
          
          // Close dropdown
          dropdown.classList.remove('open');
          
          // Clear search
          dutyTypeSearch.value = '';
          document.querySelectorAll('.dropdown-option').forEach(opt => {
            opt.style.display = 'block';
          });
          
          // Update summary if a student is selected
          if (selectedStudentId) {
            const studentItem = document.querySelector('.student-item.selected');
            const studentName = studentItem.querySelector('h6').textContent;
            updateSummary(studentName, value);
          }
        });
      });
      
      // Hours input change
      document.getElementById('requiredHours').addEventListener('input', function() {
        if (selectedStudentId) {
            const studentItem = document.querySelector('.student-item.selected');
            const studentName = studentItem.querySelector('h6').textContent;
            const dutyType = hiddenInput.value;
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
        
        if (!hiddenInput.value) {
            e.preventDefault();
            alert('Please select a duty type.');
            return;
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
            const defaultHours = document.getElementById('requiredHours').value || '0';
            html += `<p class="mb-0"><strong>Hours Required:</strong> ${defaultHours}</p>`;
        }
        
        summaryEl.innerHTML = html;
      }
      
      // Batch upload modal functionality
      const uploadArea = document.getElementById('uploadArea');
      const batchFile = document.getElementById('batchFile');
      const selectedFileDiv = document.getElementById('selectedFile');
      const fileName = document.getElementById('fileName');
      const uploadBtn = document.getElementById('uploadBtn');
      
      // Drag and drop functionality
      uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
      });
      
      uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
      });
      
      uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const file = files[0];
            if (validateFile(file)) {
            batchFile.files = files;
            showSelectedFile(file.name);
            }
        }
      });
      
      // File input change
      batchFile.addEventListener('change', function() {
        if (this.files.length > 0) {
            const file = this.files[0];
            if (validateFile(file)) {
            showSelectedFile(file.name);
            }
        }
      });
      
      // Validate file type
      function validateFile(file) {
        const allowedTypes = ['.csv', '.xlsx', '.xls'];
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        
        if (!allowedTypes.includes(fileExtension)) {
            alert('Please select a valid file type: CSV, Excel (.xlsx, .xls)');
            return false;
        }
        
        return true;
      }
      
      // Show selected file
      function showSelectedFile(name) {
        fileName.textContent = name;
        selectedFileDiv.style.display = 'block';
        uploadBtn.disabled = false;
      }
      
      // Template download
      document.getElementById('downloadTemplate').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Create CSV content with format only
        const csvContent = "Room,Section,Class Type,Instructor,Schedule,Subject Code\n";
        
        // Create blob and download
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', 'student_facilitator_template.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      });
      
      // Reset modal when closed
      document.getElementById('batchUploadModal').addEventListener('hidden.bs.modal', function() {
        document.getElementById('batchUploadForm').reset();
        selectedFileDiv.style.display = 'none';
        uploadBtn.disabled = true;
        uploadArea.classList.remove('dragover');
      });
    });
  </script>

</body>

</html>