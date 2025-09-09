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
require_once 'config/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department = $_POST['department'];
    
    try {
        $insertQuery = "INSERT INTO users (username, email, password, role, department) 
                       VALUES (:username, :email, :password, 'student', :department)";
        $stmt = $pdo->prepare($insertQuery);
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'department' => $department
        ]);
        
        $successMessage = "Student account created successfully!";
    } catch (PDOException $e) {
        $errorMessage = "Error creating student account: " . $e->getMessage();
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

        <a href="index_admin.php" class="logo d-flex align-items-center">
          <img src="assets/img/CSDL logo.png" alt="">
          <h1 class="sitename">CSDL</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="index_admin.php">Home</a></li>
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
        <h1 class="mb-2 mb-lg-0">Create Student Account</h1>
        <nav class="breadcrumbs">
          <ol>
            <li><a href="index.php">Home</a></li>
            <li class="current">Create Student</li>
          </ol>
        </nav>
      </div>
    </div><!-- End Page Title -->

    <!-- Create Section -->
    <section id="create" class="assignment section">
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
        
        <form method="POST" action="create-student.php">
          <div class="row">
            <div class="col-lg-8 offset-lg-2">
              <div class="duty-details">
                <h3 class="section-title">Student Account Information</h3>
                
                <div class="form-section">
                  <h5>Basic Information</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="username" class="form-label">Username</label>
                      <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-6">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Security</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="password" class="form-label">Password</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                          <i class="bi bi-eye" id="passwordIcon"></i>
                        </button>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label for="confirm_password" class="form-label">Confirm Password</label>
                      <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                          <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="form-section">
                  <h5>Academic Information</h5>
                  <div class="row">
                    <div class="col-md-6">
                      <label for="department" class="form-label">Department/Program</label>
                      <select class="form-control" id="department" name="department" required>
                        <option value="">Select Department</option>
                        <option value="Engineering & Architecture">Engineering & Architecture</option>
                        <option value="Business & Accountancy">Business & Accountancy</option>
                        <option value="Education">Education</option>
                        <option value="Health Sciences">Health Sciences</option>
                        <option value="Liberal Arts & Sciences">Liberal Arts & Sciences</option>
                        <option value="Computer Studies">Computer Studies</option>
                      </select>
                    </div>
                  </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary btn-lg" id="createStudentBtn">Create Student Account</button>
                </div>
              </div>
            </div>
          </div>
        </form>

      </div>
    </section><!-- /Create Section -->

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
      // Form validation
      document.querySelector('form').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
          e.preventDefault();
          alert('Passwords do not match.');
          return;
        }
      });

      // Password toggle functionality
      const togglePassword = document.getElementById('togglePassword');
      const passwordInput = document.getElementById('password');
      const passwordIcon = document.getElementById('passwordIcon');

      const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

      togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        passwordIcon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
      });

      toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        confirmPasswordIcon.className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
      });
    });
  </script>

</body>

</html>
