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

// Fetch duty entries with student information
$entriesQuery = "
    SELECT de.*, u.username as student_name, d.duty_type 
    FROM duty_entries de 
    JOIN users u ON de.student_id = u.id 
    JOIN duties d ON de.duty_id = d.id 
    ORDER BY de.created_at DESC
";
$entriesStmt = $pdo->query($entriesQuery);
$dutyEntries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch students for filter dropdown
$studentsQuery = "SELECT id, username as name FROM users WHERE role = 'student'";
$studentsStmt = $pdo->query($studentsQuery);
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryId = $_POST['entry_id'];
    $action = $_POST['action'];
    $remarks = $_POST['remarks'];
    
    try {
        if ($action === 'approve') {
            $updateQuery = "UPDATE duty_entries SET status = 'approved', instructor_feedback = :remarks, approval_date = NOW() WHERE id = :id";
        } else {
            $updateQuery = "UPDATE duty_entries SET status = 'rejected', instructor_feedback = :remarks, approval_date = NOW() WHERE id = :id";
        }
        
        $stmt = $pdo->prepare($updateQuery);
        $stmt->execute([
            'remarks' => $remarks,
            'id' => $entryId
        ]);
        
        $successMessage = "Duty entry " . ($action === 'approve' ? 'approved' : 'rejected') . " successfully!";
        
        // Refresh data
        header("Location: approve-duty.php?success=" . urlencode($successMessage));
        exit();
    } catch (PDOException $e) {
        $errorMessage = "Error updating duty entry: " . $e->getMessage();
    }
}

// Count entries by status
$statsQuery = "
    SELECT 
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM duty_entries
";
$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Duty Approval System">
  <meta name="keywords" content="PHINMA COC, student duty, duty approval, college management, Cagayan de Oro">

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
    
    .filter-section {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .duty-card {
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
      margin-bottom: 20px;
      border-left: 4px solid #6c757d;
      background: white;
    }
    
    .duty-card.pending {
      border-left-color: var(--warning-color);
    }
    
    .duty-card.approved {
      border-left-color: var(--success-color);
    }
    
    .duty-card.rejected {
      border-left-color: var(--danger-color);
    }
    
    .status-badge {
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
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
    
    .student-info {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .student-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: var(--secondary-color);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: bold;
      margin-right: 10px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }
    
    .modal-content {
      border-radius: 10px;
      border: none;
      box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    }
    
    .modal-header {
      background-color: var(--primary-color);
      color: white;
      border-top-left-radius: 10px;
      border-top-right-radius: 10px;
    }
    
    .details-row {
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }
    
    .time-badge {
      background-color: #e9ecef;
      padding: 5px 10px;
      border-radius: 15px;
      font-size: 0.85rem;
    }
    
    .remarks-section {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      margin-top: 15px;
    }
    
    .btn-approve {
      background-color: var(--success-color);
      border-color: var(--success-color);
    }
    
    .btn-reject {
      background-color: var(--danger-color);
      border-color: var(--danger-color);
    }
    
    .btn-approve:hover {
      background-color: #219653;
      border-color: #219653;
    }
    
    .btn-reject:hover {
      background-color: #c0392b;
      border-color: #c0392b;
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
                  <li><a href="approve-duty.php" class="active"></i>Approve Duty</a></li>
                  <li><a href="log-duty.php"></i>Log Duty</a></li>
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
        <h1 class="mb-2 mb-lg-0">Approve Duty Hours</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Approve Duty</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Approval Section -->
    <section id="approval" class="approval section">
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
              <p class="stats-number" id="pendingCount"><?php echo $stats['pending_count'] ?? 0; ?></p>
              <p class="stats-label">Pending Approvals</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="approvedCount"><?php echo $stats['approved_count'] ?? 0; ?></p>
              <p class="stats-label">Approved Duties</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stats-card">
              <p class="stats-number" id="rejectedCount"><?php echo $stats['rejected_count'] ?? 0; ?></p>
              <p class="stats-label">Rejected Duties</p>
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
                  <option value="pending" selected>Pending</option>
                  <option value="approved">Approved</option>
                  <option value="rejected">Rejected</option>
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
                <label for="dateFilter">Date</label>
                <input type="date" class="form-control" id="dateFilter">
              </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button class="btn btn-primary w-100" id="applyFilters">Apply Filters</button>
            </div>
          </div>
        </div>

        <div class="row" id="dutiesContainer">
          <?php foreach ($dutyEntries as $entry): ?>
          <div class="col-12 duty-card <?php echo $entry['status']; ?>" data-entry-id="<?php echo $entry['id']; ?>">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="card-title"><?php echo htmlspecialchars($entry['duty_type']); ?></h5>
                  <p class="card-subtitle text-muted"><?php echo $entry['date']; ?></p>
                </div>
                <span class="status-badge status-<?php echo $entry['status']; ?>">
                  <?php echo ucfirst($entry['status']); ?>
                </span>
              </div>
              
              <div class="student-info">
                <div class="student-avatar"><?php echo substr($entry['student_name'], 0, 1); ?></div>
                <div>
                  <h6 class="mb-0"><?php echo htmlspecialchars($entry['student_name']); ?></h6>
                  <small class="text-muted">Submitted: <?php echo date('M j, Y', strtotime($entry['created_at'])); ?></small>
                </div>
              </div>
              
              <div class="mt-3">
                <p class="card-text"><?php echo htmlspecialchars($entry['task_description']); ?></p>
              </div>
              
              <div class="d-flex justify-content-between align-items-center mt-3">
                <span class="time-badge"><i class="bi bi-clock"></i> <?php echo $entry['hours']; ?> hours</span>
                <?php if ($entry['status'] === 'pending'): ?>
                  <button class="btn btn-outline-primary review-btn" data-entry-id="<?php echo $entry['id']; ?>">
                    <i class="bi bi-eye"></i> Review
                  </button>
                <?php else: ?>
                  <div>
                    <small class="text-muted"><?php echo ucfirst($entry['status']); ?> on <?php echo date('M j, Y', strtotime($entry['approval_date'])); ?></small>
                    <?php if (!empty($entry['instructor_feedback'])): ?>
                      <div class="remarks-section mt-2">
                        <h6 class="mb-1">Instructor Remarks:</h6>
                        <p class="mb-0 small"><?php echo htmlspecialchars($entry['instructor_feedback']); ?></p>
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </section><!-- /Approval Section -->

  </main>

  <!-- Approval Modal -->
  <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="approve-duty.php">
          <input type="hidden" id="modalEntryId" name="entry_id">
          <input type="hidden" id="modalAction" name="action">
          
          <div class="modal-header">
            <h5 class="modal-title" id="approvalModalLabel">Review Duty Entry</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="details-row">
              <div class="student-info">
                <div class="student-avatar" id="modalStudentAvatar">S</div>
                <div>
                  <h5 id="modalStudentName">Student Name</h5>
                  <div class="d-flex gap-3">
                    <span class="time-badge"><i class="bi bi-calendar me-1"></i> <span id="modalDate">2023-10-15</span></span>
                    <span class="time-badge"><i class="bi bi-clock me-1"></i> <span id="modalHours">0</span> hours</span>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="details-row">
              <h6>Duty Information</h6>
              <p><strong>Duty Type:</strong> <span id="modalDutyType">ID Station</span></p>
              <p><strong>Task Description:</strong></p>
              <div class="alert alert-light" id="modalDescription">
                No description provided.
              </div>
            </div>
            
            <div class="details-row">
              <h6>Instructor Remarks</h6>
              <div class="mb-3">
                <label for="remarksInput" class="form-label">Provide your remarks (required for rejection)</label>
                <textarea class="form-control" id="remarksInput" name="remarks" rows="3" placeholder="Enter your remarks here..."></textarea>
                <div class="form-text">For rejected entries, please provide specific feedback for improvement.</div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-reject text-white" id="rejectBtn">Reject</button>
            <button type="button" class="btn btn-approve text-white" id="approveBtn">Approve</button>
          </div>
        </form>
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
      // Filter functionality
      document.getElementById('applyFilters').addEventListener('click', function() {
        const statusFilter = document.getElementById('statusFilter').value;
        const studentFilter = document.getElementById('studentFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;
        
        const dutyCards = document.querySelectorAll('.duty-card');
        
        dutyCards.forEach(card => {
          let showCard = true;
          const status = card.classList.contains('pending') ? 'pending' : 
                        card.classList.contains('approved') ? 'approved' : 'rejected';
          const studentId = card.querySelector('.review-btn')?.getAttribute('data-entry-id') || '';
          
          if (statusFilter !== 'all' && status !== statusFilter) {
            showCard = false;
          }
          
          if (studentFilter !== 'all' && !studentId.includes(studentFilter)) {
            showCard = false;
          }
          
          if (dateFilter) {
            const dateElement = card.querySelector('.card-subtitle');
            if (dateElement && dateElement.textContent !== dateFilter) {
              showCard = false;
            }
          }
          
          card.style.display = showCard ? 'block' : 'none';
        });
      });
      
      // Set up modal handlers
      const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
      let currentEntryId = null;
      
      // Add event listeners to review buttons
      document.querySelectorAll('.review-btn').forEach(button => {
        button.addEventListener('click', function() {
          const entryId = this.getAttribute('data-entry-id');
          openReviewModal(entryId);
        });
      });
      
      // Approve button handler
      document.getElementById('approveBtn').addEventListener('click', function() {
        document.getElementById('modalAction').value = 'approve';
        document.getElementById('approvalModal').querySelector('form').submit();
      });
      
      // Reject button handler
      document.getElementById('rejectBtn').addEventListener('click', function() {
        const remarks = document.getElementById('remarksInput').value;
        if (!remarks) {
          alert('Please provide remarks before rejecting.');
          return;
        }
        
        document.getElementById('modalAction').value = 'reject';
        document.getElementById('approvalModal').querySelector('form').submit();
      });
    });
    
    function openReviewModal(entryId) {
      const card = document.querySelector(`.duty-card[data-entry-id="${entryId}"]`);
      if (!card) return;
      
      // Get data from the card
      const studentName = card.querySelector('h6').textContent;
      const date = card.querySelector('.card-subtitle').textContent;
      const hours = card.querySelector('.time-badge').textContent.match(/(\d+\.?\d*)/)[0];
      const dutyType = card.querySelector('.card-title').textContent;
      const description = card.querySelector('.card-text').textContent;
      
      // Populate modal with entry data
      document.getElementById('modalStudentAvatar').textContent = studentName.charAt(0);
      document.getElementById('modalStudentName').textContent = studentName;
      document.getElementById('modalDate').textContent = date;
      document.getElementById('modalHours').textContent = hours;
      document.getElementById('modalDutyType').textContent = dutyType;
      document.getElementById('modalDescription').textContent = description;
      document.getElementById('remarksInput').value = '';
      document.getElementById('modalEntryId').value = entryId;
      
      // Store current entry ID
      currentEntryId = entryId;
      
      // Show modal
      const approvalModal = new bootstrap.Modal(document.getElementById('approvalModal'));
      approvalModal.show();
    }
  </script>

</body>

</html>