<?php
// Database connection parameters
$host = 'localhost:3306';
$username = 'root';
$password = '';
$dbname = 'lms'; // Make sure to use your actual database name

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if form data is submitted
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the submission text, assessment ID, and student ID from the form
        $submission_text = trim($_POST['submission_text']);
        $assessment_id = intval($_POST['assessment_id']);
        $student_id = intval($_POST['student_id']);

        // Prepare the SQL statement to insert the submission
        $stmt = $pdo->prepare("
            INSERT INTO assessment_submissions (assessment_id, student_id, submission_text)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$assessment_id, $student_id, $submission_text]);

        // Redirect back to the course page after successful submission
        header("Location: courses.php?course_id=" . $_GET['course_id'] . "&message=Submission successful");
        exit;
    } else {
        // Redirect back if accessed without POST request
        header("Location: courses.php?course_id=" . $_GET['course_id'] . "&error=Invalid submission");
        exit;
    }
} catch (PDOException $e) {
    // Handle database connection errors
    echo "Error: " . $e->getMessage();
}
?>
