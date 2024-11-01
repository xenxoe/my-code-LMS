
<?php
// Include database connection file
require 'db_connection.php';
session_start(); // Start a session

// Check if instructor is logged in
if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php"); // Redirect to login if not logged in
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

// Fetch courses assigned to the instructor
$courses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$courses->execute([$instructor_id]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

// Fetch students enrolled in each course
$students_by_course = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $students = $pdo->prepare("SELECT s.id, s.name FROM students s 
                                JOIN enrollments e ON s.id = e.student_id 
                                WHERE e.course_id = ?");
    $students->execute([$course_id]);
    $students_by_course[$course_id] = $students->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch modules or content related to each course
$modules_by_course = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
    $modules->execute([$course_id]);
    $modules_by_course[$course_id] = $modules->fetchAll(PDO::FETCH_ASSOC);
}

// Handle sending assessment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_assessment'])) {
    $course_id = $_POST['course_id'];
    $assessment_title = htmlspecialchars($_POST['assessment_title']);
    $assessment_description = htmlspecialchars($_POST['assessment_description']);

    // Save the assessment to the database
    $insert_assessment = $pdo->prepare("INSERT INTO assessments (course_id, instructor_id, assessment_title, assessment_description, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())");
    $insert_assessment->execute([$course_id, $instructor_id, $assessment_title, $assessment_description]);

    echo "<script>alert('Assessment sent successfully for course ID: $course_id');</script>";
}

// Handle feedback submission for assessments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $submission_id = $_POST['submission_id'];
    $feedback_text = htmlspecialchars($_POST['feedback_text']);

    // Save feedback to the database
    $insert_feedback = $pdo->prepare("INSERT INTO assessment_feedback (submission_id, user_id, user_type, comment, created_at) 
                                       VALUES (?, ?, 'instructor', ?, NOW())");
    $insert_feedback->execute([$submission_id, $instructor_id, $feedback_text]);

    echo "<script>alert('Feedback submitted successfully for submission ID: $submission_id');</script>";
}

// Fetch feedback for each assessment submission
$feedback_by_submission = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $submissions = $pdo->prepare("SELECT asmt.id AS submission_id, asmt.student_id, asmt.submission_text, s.name AS student_name 
                                   FROM assessment_submissions asmt 
                                   JOIN students s ON asmt.student_id = s.id 
                                   WHERE asmt.assessment_id IN (SELECT id FROM assessments WHERE course_id = ?)");
    $submissions->execute([$course_id]);
    $feedback_by_submission[$course_id] = $submissions->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Ittetsu Takeda</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f7f8fc;
        }

        header {
            background-color: #ffffff;
            border-bottom: 1px solid #e0e0e0;
            padding: 10px 20px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav ul {
            list-style: none;
            display: flex;
            gap: 15px;
        }

        nav ul li a {
            text-decoration: none;
            color: #000;
        }

        .profile {
            display: flex;
            align-items: center;
        }

        .profile img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .profile-section {
            display: flex;
            align-items: center;
            background-color: #ffffff;
            padding: 20px;
            margin: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-image: url('./images/background.jpg'); /* Replace with your background image */
            background-size: cover;
            background-position: center;
            color: #fff;
        }

        .profile-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-right: 20px;
        }

        .profile-info h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }

        .profile-info p {
            margin: 5px 0;
            color: #666;
        }

        .profile-info .assigned-course {
            color: #333;
            font-weight: bold;
        }

        .tabs {
            display: flex;
            background-color: #f1f1f1;
            margin: 20px;
            border-radius: 8px;
            overflow: hidden;
        }

        .tabs button {
            flex: 1;
            padding: 10px;
            border: none;
            background-color: #e0e0e0;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .tabs button.active {
            background-color: #2563eb;
            color: #fff;
        }

        .tab-content {
            background-color: #ffffff;
            padding: 20px;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .tab-content.hidden {
           display: none;
        }

        .tab-content h3 {
            margin-top: 0;
            font-size: 18px;
            color: #333;
        }

        .tab-content p {
            color: #666;
            line-height: 1.6;
        }
        .profile-section {
    display: flex;
    align-items: center;
    background-color: #ffffff;
    padding: 20px;
    margin: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    background-image: url('./images/newswriting.png'); /* Replace with your background image path */
    background-size: cover;
    background-position: center;
    color: #fff;
}


           

    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <img src="./images/logo.png" alt="Logo">
            </div>
            <nav>
    <ul>
        <li><a href="#">Home</a></li>
        <li><a href="#">About Us</a></li>
        <li class="profile">
            <img src="./images/instructor.png" alt="Profile">
            <span>Takeda</span>
        </li>
        <li><a href="logout_instructor.php">Logout</a></li> <!-- Add this logout link -->
    </ul>
</nav>
        </div>
    </header>

    <main>
        <section class="profile-section">
            <img src="./images/instructor.png" alt="Ittetsu Takeda" class="profile-img">
            <div class="profile-info">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['instructor_name']); ?></h2>
            <p>Instructor</p>
                <p class="assigned-course">Assigned Course: Editorial Writing</p>
            </div>
        </section>

        <section class="tabs">
    <button class="active" onclick="showTabContent('assigned-course', event)">ASSIGNED COURSE</button>
    <button onclick="showTabContent('my-learners', event)">MY LEARNERS</button>
    <button onclick="showTabContent('evaluate', event)">EVALUATE</button>
</section>

<div id="assigned-course" class="tab-content"> <!-- Removed hidden class here -->
    <h3>Editorial Writing</h3>
    <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
                <div class="course">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p><?php echo htmlspecialchars($course['course_description']); ?></p>

                    </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No courses available.</p>
        <?php endif; ?>
    </div>
</div>


    <div id="my-learners" class="tab-content hidden">
    <h3>My Learners</h3>
    <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
            <div class="course">
                <h4>Course: <?php echo htmlspecialchars($course['course_name']); ?></h4>
                <div class="students">
                    <h5>Enrolled Students:</h5>
                    <?php
                    if (!empty($students_by_course[$course['id']])) {
                        foreach ($students_by_course[$course['id']] as $student): ?>
                            <div class="student"><?php echo htmlspecialchars($student['name']); ?></div>
                        <?php endforeach; 
                    } else { ?>
                        <div class="student">No students enrolled in this course.</div>
                    <?php } ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div>No courses found.</div>
    <?php endif; ?>
</div>

</div>

<div id="evaluate" class="tab-content hidden">
    <h3>Evaluate</h3>
    <div class="modules">
                        <h4>Course Content:</h4>
                        <?php
                        if (!empty($modules_by_course[$course['id']])) {
                            foreach ($modules_by_course[$course['id']] as $module): ?>
                                <div class="module"><?php echo htmlspecialchars($module['title']); ?></div>
                            <?php endforeach; ?>
                        <?php } else { ?>
                            <div class='module'>No content available for this course.</div>
                        <?php } ?>
                    </div>

                    <!-- Assessment Sending Form -->
                    <div class="assessment-form">
                        <h4>Send Assessment:</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                            <label for="assessment_title">Assessment Title:</label>
                            <input type="text" id="assessment_title" name="assessment_title" required>
                            <label for="assessment_description">Assessment Description:</label>
                            <textarea id="assessment_description" name="assessment_description" rows="4" required></textarea>
                            <input type="submit" name="send_assessment" value="Send Assessment">
                        </form>
                    </div>
                    <div class="submissions">
                        <h4>Assessment Submissions:</h4>
                        <?php
                        if (!empty($feedback_by_submission[$course['id']])) {
                            foreach ($feedback_by_submission[$course['id']] as $submission): ?>
                                <div class="submission">
                                    <h5>Submission by: <?php echo htmlspecialchars($submission['student_name']); ?></h5>
                                    <p><?php echo htmlspecialchars($submission['submission_text']); ?></p>
                                    <form method="POST" action="">
                                        <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['submission_id']); ?>">
                                        <label for="feedback_text_<?php echo $submission['submission_id']; ?>">Feedback:</label>
                                        <textarea id="feedback_text_<?php echo $submission['submission_id']; ?>" name="feedback_text" rows="3" required></textarea>
                                        <input type="submit" name="submit_feedback" value="Submit Feedback">
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php } else { ?>
                            <div class='submission'>No submissions available for this assessment.</div>
                        <?php } ?>
                    </div>
</div>

<script>
    function showTabContent(tabId, event) {
        // Hide all tab content
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.add('hidden'));

        // Remove active class from all buttons
        const buttons = document.querySelectorAll('.tabs button');
        buttons.forEach(button => button.classList.remove('active'));

        // Show the selected tab content and add active class to the button
        document.getElementById(tabId).classList.remove('hidden');
        event.target.classList.add('active');
    }
</script>

</body>
</html>
