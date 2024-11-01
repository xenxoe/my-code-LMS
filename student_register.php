<?php
// Include database connection file
require 'db_connection.php'; // Ensure to replace with your actual DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $name = htmlspecialchars($_POST['name']); // New input for name
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert data into students table
    $stmt = $pdo->prepare("INSERT INTO students (name, username, email, password) VALUES (?, ?, ?, ?)"); // Include name in query
    if ($stmt->execute([$name, $username, $email, $password])) {
        echo "Registration successful!";
    } else {
        echo "Registration failed!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Registration</title>
</head>
<body>
    <h2>Student Registration</h2>
    <form method="POST" action="">
        <label for="name">Name:</label> <!-- New label for name -->
        <input type="text" id="name" name="name" required> <!-- New input for name -->
        <br>
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <input type="submit" value="Register">
    </form>
</body>
</html>
