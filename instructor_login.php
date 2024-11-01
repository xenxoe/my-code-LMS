<?php
// Include database connection file
require 'db_connection.php'; // Update with your actual DB connection file

session_start(); // Start a session

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize email input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Prepare and execute the query to fetch instructor by email
        $stmt = $pdo->prepare("SELECT * FROM instructors WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password and log the user in
        if ($instructor && password_verify($password, $instructor['password'])) {
            // Set session variables
            $_SESSION['instructor_id'] = $instructor['id'];
            $_SESSION['instructor_name'] = $instructor['name'];
            header("Location: instructor.php"); // Redirect to the instructor dashboard
            exit();
        } else {
            echo "Invalid credentials.";
        }
    } catch (PDOException $e) {
        // Handle any potential errors
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instructor Login</title>
</head>
<body>
    <h2>Instructor Login</h2>
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Login">
    </form>
</body>
</html>
