<?php
// Database connection (same as in admin.php)
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

// Check if course_id is set in the URL
if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Fetch course data
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if course exists
    if (!$course) {
        die("Course not found.");
    }

    // Fetch enrolled students
    $students_stmt = $pdo->prepare("SELECT s.id AS student_id, s.name AS student_name FROM enrollments e
    JOIN students s ON e.student_id = s.id
    WHERE e.course_id = ?");
    $students_stmt->execute([$course_id]);
    $enrolled_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle unenrollment of students
    if (isset($_GET['unenroll_student_id'])) {
        $unenroll_student_id = $_GET['unenroll_student_id'];

        // Prepare and execute the deletion statement
        $unenroll_stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
        if ($unenroll_stmt->execute([$unenroll_student_id, $course_id])) {
            echo "<p>Student unenrolled successfully!</p>";
        } else {
            echo "<p>Error unenrolling student.</p>";
        }

        // Refresh the page to show the updated list of enrolled students
        header("Location: edit_course.php?course_id=$course_id");
        exit();
    }

    // Handle course update
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
        $course_name = $_POST['course_name'];
        $course_description = $_POST['course_description'];

        // Update course details
        $update_stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ? WHERE id = ?");
        if ($update_stmt->execute([$course_name, $course_description, $course_id])) {
            echo "<p>Course updated successfully!</p>";
        } else {
            echo "<p>Error updating course.</p>";
        }

        // Handle file uploads
        if (!empty($_FILES['module_file']['name'][0]) || !empty($_FILES['video_file']['name'][0])) {
            $target_dir = "uploads/";

            foreach ($_FILES['module_file']['name'] as $key => $value) {
                if ($_FILES['module_file']['error'][$key] == 0) {
                    $target_file = $target_dir . basename($_FILES['module_file']['name'][$key]);
                    move_uploaded_file($_FILES['module_file']['tmp_name'][$key], $target_file);
                    
                    $module_title = $_POST['module_title'][$key];
                    $insert_stmt = $pdo->prepare("INSERT INTO modules (course_id, module_file, title) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$course_id, $target_file, $module_title]);
                }
            }

            foreach ($_FILES['video_file']['name'] as $key => $value) {
                if ($_FILES['video_file']['error'][$key] == 0) {
                    $target_file = $target_dir . basename($_FILES['video_file']['name'][$key]);
                    move_uploaded_file($_FILES['video_file']['tmp_name'][$key], $target_file);
                    
                    $video_title = $_POST['video_title'][$key];
                    $insert_stmt = $pdo->prepare("INSERT INTO modules (course_id, video_file, title) VALUES (?, ?, ?)");
                    $insert_stmt->execute([$course_id, $target_file, $video_title]);
                }
            }
        }
    }

    // Handle title and file updates for uploaded modules
    if (isset($_POST['update_title'])) {
        foreach ($_POST['module_id'] as $index => $module_id) {
            $new_title = $_POST['module_title'][$index];
            $new_file = $_FILES['module_file_update']['name'][$index];

            // Update the title
            $update_title_stmt = $pdo->prepare("UPDATE modules SET title = ? WHERE id = ?");
            $update_title_stmt->execute([$new_title, $module_id]);

            // Update the file if a new file is uploaded
            if (!empty($new_file)) {
                $target_dir = "uploads/";
                $target_file = $target_dir . basename($new_file);
                move_uploaded_file($_FILES['module_file_update']['tmp_name'][$index], $target_file);
                
                $update_file_stmt = $pdo->prepare("UPDATE modules SET module_file = ? WHERE id = ?");
                $update_file_stmt->execute([$target_file, $module_id]);
            }
        }
    }

    // Handle deletion of modules
    if (isset($_GET['delete_module_id'])) {
        $module_id = $_GET['delete_module_id'];

        // Fetch the module to get file path before deletion
        $delete_stmt = $pdo->prepare("SELECT module_file, video_file FROM modules WHERE id = ?");
        $delete_stmt->execute([$module_id]);
        $module = $delete_stmt->fetch(PDO::FETCH_ASSOC);

        // Delete the file from the server
        if ($module) {
            if ($module['module_file'] && file_exists($module['module_file'])) {
                unlink($module['module_file']);
            }
            if ($module['video_file'] && file_exists($module['video_file'])) {
                unlink($module['video_file']);
            }
        }

        // Delete the module record from the database
        $delete_stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
        if ($delete_stmt->execute([$module_id])) {
            echo "<p>Module deleted successfully!</p>";
        } else {
            echo "<p>Error deleting module.</p>";
        }
    }

    // Fetch uploaded files and videos
    $modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
    $modules->execute([$course_id]);
    $uploaded_modules = $modules->fetchAll(PDO::FETCH_ASSOC);
} else {
    die("Course ID is required.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Course</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f3f3f3; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1); }
        input[type="text"], textarea { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 15px; background-color: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background-color: #0056b3; }
        .file-upload { margin-top: 20px; }
        .uploaded-files { margin-top: 20px; }
        .delete-btn { background-color: #dc3545; color: #fff; text-decoration: none; border-radius: 5px; padding: 5px 10px; margin-left: 10px; }
        .delete-btn:hover { background-color: #c82333; }
        .update-btn { background-color: #28a745; color: #fff; border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Course</h2>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
            <label for="course_name">Course Name:</label>
            <input type="text" name="course_name" id="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
            <label for="course_description">Course Description:</label>
            <textarea name="course_description" id="course_description" rows="4" required><?php echo htmlspecialchars($course['course_description']); ?></textarea>
            <div class="file-upload">
                <label for="module_file">Upload Module Files:</label>
                <input type="file" name="module_file[]" id="module_file" multiple>
                <label for="module_title">Module Titles:</label>
                <input type="text" name="module_title[]" placeholder="Title for each module file" multiple>
                <label for="video_file">Upload Video Files:</label>
                <input type="file" name="video_file[]" id="video_file" multiple>
                <label for="video_title">Video Titles:</label>
                <input type="text" name="video_title[]" placeholder="Title for each video file" multiple>
            </div>
            <button type="submit" name="update_course">Update Course</button>
        </form>

        <div class="uploaded-files">
            <h3>Uploaded Modules:</h3>
            <ul>
                <?php foreach ($uploaded_modules as $module): ?>
                    <li>
                        <?php echo htmlspecialchars($module['title']); ?>
                        <?php if ($module['module_file']): ?>
                            <a href="<?php echo htmlspecialchars($module['module_file']); ?>" target="_blank">View File</a>
                        <?php endif; ?>
                        <?php if ($module['video_file']): ?>
                            <a href="<?php echo htmlspecialchars($module['video_file']); ?>" target="_blank">View Video</a>
                        <?php endif; ?>
                        <a class="delete-btn" href="?course_id=<?php echo $course_id; ?>&delete_module_id=<?php echo $module['id']; ?>">Delete</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="enrolled-students">
            <h3>Enrolled Students:</h3>
            <ul>
                <?php if ($enrolled_students): ?>
                    <?php foreach ($enrolled_students as $student): ?>
                        <li>
                            <?php echo htmlspecialchars($student['student_name']); ?> (ID: <?php echo htmlspecialchars($student['student_id']); ?>)
                            <a class="delete-btn" href="?course_id=<?php echo $course_id; ?>&unenroll_student_id=<?php echo $student['student_id']; ?>" onclick="return confirm('Are you sure you want to unenroll this student?');">Unenroll</a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No students enrolled in this course.</p>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
</html>
