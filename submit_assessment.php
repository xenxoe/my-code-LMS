<?php
session_start();  // Start the session at the top of the script

// Database connection
$host = 'localhost';
$db_name = 'lms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Check if the student_id is set in the session
if (!isset($_SESSION['student_id'])) {
    die("Error: Student ID not found in session.");
}

// Fetch assessment_id from POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $assessment_id = $_POST['assessment_id'];
    $student_id = $_SESSION['student_id'];  // Get student_id from session
    $submission_text = $_POST['submission_text'];

    // Ensure student_id is not null before proceeding
    if (empty($student_id)) {
        die("Error: Student ID cannot be empty.");
    }

    // Prepare SQL to insert the submission
    $sql = "INSERT INTO assessment_submissions (assessment_id, student_id, submission_text) VALUES (:assessment_id, :student_id, :submission_text)";
    $stmt = $pdo->prepare($sql);

    // Bind parameters
    $stmt->bindParam(':assessment_id', $assessment_id);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':submission_text', $submission_text);

    // Execute the statement
    if ($stmt->execute()) {
        echo "Submission successful.";
    } else {
        echo "Error: Could not submit the assessment.";
    }
}
?>
