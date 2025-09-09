<?php
require_once 'config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$firstname = trim($_POST['firstname'] ?? '');
$middlename = trim($_POST['middlename'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$year_level = trim($_POST['year_level'] ?? '');
$gmail = trim($_POST['gmail'] ?? '');
$course = trim($_POST['course'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$school_year = trim($_POST['school_year'] ?? '');
$scholarship = trim($_POST['scholarship'] ?? '');

if ($firstname === '' || $lastname === '' || $gmail === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Firstname, lastname and gmail are required']);
    exit;
}

try {
    $insert = $pdo->prepare("INSERT INTO student_info (firstname, middlename, lastname, year_level, gmail, course, semester, school_year, scholarship) VALUES (:firstname, :middlename, :lastname, :year_level, :gmail, :course, :semester, :school_year, :scholarship)");
    $insert->execute([
        'firstname' => $firstname,
        'middlename' => $middlename,
        'lastname' => $lastname,
        'year_level' => $year_level,
        'gmail' => $gmail,
        'course' => $course,
        'semester' => $semester,
        'school_year' => $school_year,
        'scholarship' => $scholarship
    ]);

    $newId = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => $newId, 'firstname' => $firstname, 'lastname' => $lastname, 'gmail' => $gmail]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
