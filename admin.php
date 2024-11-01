<?php
// Database connection
include 'db_connection.php';

// Handle course update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];
    $instructor_id = $_POST['instructor_id'];

    // Handle file upload for course image
    $course_image = null;
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['course_image']['tmp_name'];
        $name = basename($_FILES['course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    // Update course details along with the assigned instructor
    $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ?, course_image = ?, instructor_id = ? WHERE id = ?");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id, $course_id])) {
        echo "<p>Course updated successfully!</p>";
    } else {
        echo "<p>Error updating course.</p>";
    }
}

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $course_name = $_POST['new_course_name'];
    $course_description = $_POST['new_course_description'];
    $instructor_id = $_POST['instructor_id'];

    // Handle file upload for course image
    $course_image = null;
    if (isset($_FILES['new_course_image']) && $_FILES['new_course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['new_course_image']['tmp_name'];
        $name = basename($_FILES['new_course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    // Insert course with assigned instructor
    $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_description, course_image, instructor_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id])) {
        echo "<p>Course created successfully!</p>";
    } else {
        echo "<p>Error creating course.</p>";
    }
}

// Handle course deletion
if (isset($_GET['delete_course_id'])) {
    $course_id = $_GET['delete_course_id'];

    // Check for existing enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $enrollment_count = $stmt->fetchColumn();

    if ($enrollment_count > 0) {
        echo "<p>Cannot delete this course because there are existing enrollments.</p>";
    } else {
        // Now, delete the course
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        if ($stmt->execute([$course_id])) {
            echo "<p>Course deleted successfully!</p>";
        } else {
            echo "<p>Error deleting course.</p>";
        }
    }
}

// Fetch all courses and student count for each course
$courses = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
    FROM courses c
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all instructors
$instructors = $pdo->query("SELECT * FROM instructors")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students
$students = $pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);

// Count totals
$total_courses = count($courses);
$total_instructors = count($instructors);
$total_students = count($students);

// Initialize variables for filtered results
$filtered_instructors = $instructors; // Default to all instructors
$filtered_students = $students; // Default to all students

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_instructor'])) {
    $instructor_name = $_POST['instructor_name'];
    $instructor_email = $_POST['instructor_email'];
    $instructor_password = $_POST['instructor_password'];

    // Hash the password before storing it
    $hashed_password = password_hash($instructor_password, PASSWORD_DEFAULT);

    // Insert new instructor into the database
    $stmt = $pdo->prepare("INSERT INTO instructors (name, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$instructor_name, $instructor_email, $hashed_password])) {
        echo "<p>Instructor registered successfully!</p>";
    } else {
        echo "<p>Error registering instructor.</p>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        /* Basic reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            padding: 20px;
            background-color: #f5f5f5;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        header img {
            height: 50px; /* Adjust height as needed */
            margin-right: 20px;
        }

        header div {
            text-align: right;
        }

        header span {
            display: block;
            color: #555;
            font-weight: bold;
        }

        #dashboard-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: white;
            padding: 20px;
            text-align: center;
            width: 30%;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .stat-box img {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }

        .stat-box div:first-child {
            font-size: 16px;
            color: #333;
        }

        .stat-box div:last-child {
            font-size: 30px;
            color: #222;
            font-weight: bold;
        }

        nav {
            margin-bottom: 20px;
        }

        nav ul {
            list-style: none;
            display: flex;
            justify-content: start;
        }

        nav ul li {
            margin-right: 10px;
        }

        nav ul li a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0056d9;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            cursor: pointer;
        }

        nav ul li a:hover {
            background-color: #003f99;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 22px;
            color: #333;
        }

        select {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        button {
            padding: 10px 20px;
            background-color: #ccc;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background-color: #999;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f1f1f1;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: #333;
        }

        .course {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .course img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }

        .course-info {
            flex: 1;
        }

        .course-info h3 {
            margin: 0 0 5px;
            font-size: 16px;
        }

        .course-info p {
            margin: 0;
            font-size: 14px;
        }

        .instructor-list, .student-list {
            margin-top: 20px;
        }

        .form-container {
            margin-top: 20px;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-container input {
            width: calc(100% - 22px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <header>
        <img src="../images/logo.png" alt="Logo" />
        <div>
            <span>Welcome, Admin <img src="../images/admin_logo.jpg" alt="Profile Logo" class="profile-logo" /></span>
            <span>Last Login: <?php echo date('Y-m-d H:i:s'); ?></span>
        </div>
    </header>
    <div class="container">
    <div id="dashboard-stats">
        <div class="stat-box">
            <img src="../images/admin_user.png" alt="Courses Icon" /> <!-- Replace with your icon path -->
            <div>Total Courses</div>
            <div><?php echo $total_courses; ?></div>
        </div>
        <div class="stat-box">
            <img src="../images/admin_courses.png" alt="Instructors Icon" /> <!-- Replace with your icon path -->
            <div>Total Instructors</div>
            <div><?php echo $total_instructors; ?></div>
        </div>
        <div class="stat-box">
            <img src="../images/admin_instrutors.png" alt="Students Icon" /> <!-- Replace with your icon path -->
            <div>Total Students</div>
            <div><?php echo $total_students; ?></div>
        </div>
    </div>
</div>
        <nav>
            <ul>
                <li><a href="#" class="tab-link" data-tab="manage-courses">Manage Courses</a></li>
                <li><a href="#" class="tab-link" data-tab="create-course">Create Course</a></li>
                <li><a href="#" class="tab-link" data-tab="instructors">Instructors</a></li>
                <li><a href="#" class="tab-link" data-tab="students">Students</a></li>
                <li><a href="#" class="tab-link" data-tab="register-instructor">Register New Instructor</a></li> <!-- New tab for registering instructors -->
            </ul>
        </nav>

        <section id="manage-courses" class="tab-content active">
            <h2>Manage Courses</h2>
            <div class="course-list">
                <?php foreach ($courses as $course): ?>
                    <div class="course">
                        <img src="<?php echo $course['course_image']; ?>" alt="<?php echo $course['course_name']; ?>" />
                        <div class="course-info">
                            <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <p><?php echo htmlspecialchars($course['course_description']); ?></p>
                            <p>Enrolled Students: <?php echo $course['student_count']; ?></p>
                        </div>
                        <a class="edit-btn" href="edit_course.php?course_id=<?php echo $course['id']; ?>">Edit</a>
                        <a class="delete-btn" href="?delete_course_id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="create-course" class="tab-content">
            <h2>Create Course</h2>
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="new_course_name" placeholder="Course Name" required>
                    <input type="text" name="new_course_description" placeholder="Course Description" required>
                    <input type="file" name="new_course_image" accept="image/*">
                    <select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_course">Create Course</button>
                </form>
            </div>
        </section>

        <section id="instructors" class="tab-content">
    <h2>Instructors</h2>
    <div class="instructor-list">
        <table>
            <thead>
                <tr>
                    <th>Instructor Name</th>
                    <th>Email</th>
                    <th>Assigned Course</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instructors as $instructor): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                        <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                        <td>
                            <?php
                                $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE instructor_id = ?");
                                $stmt->execute([$instructor['id']]);
                                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                                echo $course ? htmlspecialchars($course['course_name']) : 'Not Assigned';
                            ?>
                        </td>
                        <td>
                            <!-- Edit Instructor Button -->
                            <button onclick="openEditModal(<?php echo $instructor['id']; ?>)">Edit</button>
                            
                            <!-- Delete Instructor Button -->
                            <form method="POST" action="delete_instructor.php" style="display:inline;">
                                <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this instructor?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal for editing an instructor -->
    <div id="editInstructorModal" style="display:none;">
        <form method="POST" action="edit_instructor.php">
            <input type="hidden" name="instructor_id" id="edit_instructor_id">
            <label for="edit_instructor_name">Instructor Name</label>
            <input type="text" id="edit_instructor_name" name="instructor_name" required>
            
            <label for="edit_instructor_email">Email</label>
            <input type="email" id="edit_instructor_email" name="instructor_email" required>

            <label for="course_assignment">Assign Course</label>
            <select id="course_assignment" name="course_id">
                <option value="">Select a course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Update Instructor</button>
        </form>
    </div>
</section>

<script>
    function openEditModal(instructorId) {
        // Fetch instructor data using AJAX or any other preferred method
        // Then populate the form with instructor data
        document.getElementById('editInstructorModal').style.display = 'block';
        document.getElementById('edit_instructor_id').value = instructorId;

        // You should also populate instructor's current details (name, email, course) here
        // If needed, make an AJAX request to fetch instructor data
    }
</script>


        <section id="students" class="tab-content">
            <h2>Students</h2>
            <div class="student-list">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo $student['id']; ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="register-instructor" class="tab-content">
    <h2>Register New Instructor</h2>
    <div class="form-container">
        <form method="POST">
            <input type="text" name="instructor_name" placeholder="Instructor Name" required>
            <input type="email" name="instructor_email" placeholder="Instructor Email" required>
            <input type="password" name="instructor_password" placeholder="Instructor Password" required>
            <button type="submit" name="register_instructor">Register Instructor</button>
        </form>
    </div>
</section>


    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();

                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Remove active class from all tabs
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                });

                // Show the clicked tab content
                const tabId = e.target.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');

                // Add active class to clicked tab
                e.target.classList.add('active');
            });
        });
    </script>
</body>
</html>
