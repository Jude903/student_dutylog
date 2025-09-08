<?php
require_once 'config/config.php';

// Fetch statistics for the homepage
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
    (SELECT COUNT(*) FROM duties WHERE status = 'completed') as completed_duties,
    (SELECT COUNT(DISTINCT u.department) FROM users u WHERE u.role = 'student') as departments_count";

$statsStmt = $pdo->query($statsQuery);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="description" content="PHINMA Cagayan de Oro College Student Duty Log Management System">
  <meta name="keywords" content="PHINMA COC, student duty, duty log, college management, Cagayan de Oro">

  <!-- Favicons -->
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
    <li><a href="index.php" class="active">Home</a></li>
    <li><a href="dashboard.php">Dashboard</a></li>
    <li class="dropdown">
      <a href="#">Duty Options</a>
      <ul class="dropdown-menu">
        <li><a href="assign-duty.php">Assign Duty</a></li>
        <li><a href="approve-duty.php">Approve Duty</a></li>
        <li><a href="log-duty.php">Log Duty</a></li>
        <li><a href="view-duty.php">View Duty</a></li>
        <li><a href="monitor-duty.php">Monitor Duty</a></li>
      </ul>
    </li>
    <li><a href="evaluate-student.php">Evaluate Student</a></li>
    <!-- ✅ Logout Button -->
    <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
  </ul>
  <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
</nav>
      </div>
    </div>
  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-center">
          <div class="col-lg-12 text-center">
            <div class="hero-content" data-aos="fade-up" data-aos-delay="200">
              <span class="subtitle">PHINMA Cagayan de Oro College</span>
              <h1>Student Duty Log </h1>
              <p>Comprehensive digital platform for managing, tracking, and evaluating student duties across all departments at PHINMA COC. Enhancing accountability, streamlining workflows, and promoting student engagement in Cagayan de Oro City.</p>

              <div class="trust-badges">
                <div class="badge-item">
                  <i class="bi bi-people-fill"></i>
                  <div class="badge-text">
                    <span class="count"><?php echo number_format($stats['total_students']); ?>+</span>
                    <span class="label">Enrolled Students</span>
                  </div>
                </div>
                <div class="badge-item">
                  <i class="bi bi-journal-check"></i>
                  <div class="badge-text">
                    <span class="count"><?php echo number_format($stats['completed_duties']); ?>+</span>
                    <span class="label">Duties Completed</span>
                  </div>
                </div>
                <div class="badge-item">
                  <i class="bi bi-building"></i>
                  <div class="badge-text">
                    <span class="count"><?php echo $stats['departments_count']; ?></span>
                    <span class="label">Academic Departments</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about section">
      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row align-items-center g-5">
          <div class="col-lg-12">
            <div class="about-content text-center" data-aos="fade-up" data-aos-delay="200">
              <h2>Serving PHINMA COC Since 2020</h2>
              <p class="lead">The Student Duty Log system at PHINMA Cagayan de Oro College revolutionizes how our institution manages student responsibilities, tracks academic progress, and fosters accountability through innovative digital solutions tailored for our Filipino students.</p>
              <p>From duty assignments in our Engineering, Business, Education, and Health Sciences departments to performance evaluations, our comprehensive platform streamlines administrative processes while providing students with clear visibility into their academic obligations and achievements.</p>

              <div class="achievement-boxes row g-4 mt-4 justify-content-center">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                  <div class="achievement-box">
                    <h3>5+</h3>
                    <p>Years of Service</p>
                  </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="400">
                  <div class="achievement-box">
                    <h3><?php echo number_format($stats['total_students']); ?>+</h3>
                    <p>Active Students</p>
                  </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="500">
                  <div class="achievement-box">
                    <h3>95%</h3>
                    <p>Completion Rate</p>
                  </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="600">
                  <div class="achievement-box">
                    <h3>150+</h3>
                    <p>Faculty Members</p>
                  </div>
                </div>
              </div>

              <div class="certifications mt-5" data-aos="fade-up" data-aos-delay="700">
                <h5>Accreditations & Partnerships</h5>
                <p>PHINMA Education Network | PACUCOA Accredited | CHED Recognized Programs</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

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