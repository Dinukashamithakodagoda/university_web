<?php
$page_title = 'Register Student';
require_once 'includes/db.php';
require_once 'includes/header.php';

$success = '';
$error = '';
$courses = course_options($conn);

if (empty($courses)) {
    $courses = [
        ['id' => 1, 'course_name' => 'Diploma in Information Technology'],
        ['id' => 2, 'course_name' => 'Diploma in Business Management'],
        ['id' => 3, 'course_name' => 'Diploma in Accounting'],
        ['id' => 4, 'course_name' => 'Diploma in English'],
        ['id' => 5, 'course_name' => 'Diploma in Marketing'],
        ['id' => 6, 'course_name' => 'Higher Diploma in IT'],
        ['id' => 7, 'course_name' => 'Higher Diploma in Business Management'],
    ];
}

$student_id_preview = generate_student_id($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Refresh the page and try again.';
    } else {
        $student_id = generate_student_id($conn);
        $nic = trim($_POST['nic'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $course_id = (int) ($_POST['course_id'] ?? 0);
        $enrolled = $_POST['enrolled_date'] ?? '';

        $course_name = '';
        $course_stmt = $conn->prepare('SELECT course_name FROM courses WHERE id = ? AND is_active = 1 LIMIT 1');
        if ($course_stmt instanceof mysqli_stmt) {
            $course_stmt->bind_param('i', $course_id);
            $course_stmt->execute();
            $course_result = $course_stmt->get_result();
            if ($course_result instanceof mysqli_result && ($course_row = $course_result->fetch_assoc())) {
                $course_name = $course_row['course_name'];
            }
            $course_stmt->close();
        }

        $photo_path = null;
        $photo_error = '';

        if (!empty($_FILES['photo']['name']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $detected_type = $file_info ? finfo_file($file_info, $_FILES['photo']['tmp_name']) : null;
                if ($file_info) {
                    finfo_close($file_info);
                }

                if (!isset($allowed_types[$detected_type])) {
                    $photo_error = 'Only JPG, PNG, or WEBP images are allowed.';
                } elseif (($_FILES['photo']['size'] ?? 0) > 2 * 1024 * 1024) {
                    $photo_error = 'Photo size must be 2 MB or less.';
                } else {
                    $photo_name = $student_id . '.' . $allowed_types[$detected_type];
                    $photo_directory = __DIR__ . '/uploads/student-photos/';

                    if (!is_dir($photo_directory)) {
                        mkdir($photo_directory, 0775, true);
                    }

                    $target_path = $photo_directory . $photo_name;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                        $photo_path = 'uploads/student-photos/' . $photo_name;
                    } else {
                        $photo_error = 'Photo upload failed. Please try again.';
                    }
                }
            } else {
                $photo_error = 'Photo upload failed. Please try again.';
            }
        }

        if ($photo_error !== '') {
            $error = $photo_error;
        } elseif ($nic && $full_name && $gender && $address && $phone && $email && $course_name && $enrolled) {
            $stmt = $conn->prepare('INSERT INTO students (student_id, nic, full_name, gender, address, phone, email, course, photo_path, enrolled_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            if ($stmt instanceof mysqli_stmt) {
                $stmt->bind_param('ssssssssss', $student_id, $nic, $full_name, $gender, $address, $phone, $email, $course_name, $photo_path, $enrolled);

                if ($stmt->execute()) {
                    $success = 'Student <strong>' . e($full_name) . '</strong> registered successfully with ID <strong>' . e($student_id) . '</strong>.';
                    $_POST = [];
                    $student_id_preview = generate_student_id($conn);
                } else {
                    if ($conn->errno === 1062) {
                        $error = 'A student with this NIC, email, or generated student ID already exists.';
                    } else {
                        $error = 'Database error: ' . e($conn->error);
                    }
                }

                $stmt->close();
            } else {
                $error = 'Database query failed.';
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}
?>

<div class="page-wrapper">
    <div class="page-header fade-in">
        <h1>Register New Student</h1>
        <div class="gold-line"></div>
        <p>Add a new student to the IMBS Green Campus portal</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success fade-in">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            <?= $success ?>
            &nbsp; <a href="students.php" style="color:inherit;font-weight:600;">View all students →</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error fade-in">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card fade-in fade-in-delay-1">
        <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            <h2>Student Registration Form</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" value="<?= e($student_id_preview) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nic">NIC Number <span style="color:var(--error)">*</span></label>
                        <input type="text" id="nic" name="nic" placeholder="e.g. 199512345678" value="<?= e($_POST['nic'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name <span style="color:var(--error)">*</span></label>
                        <input type="text" id="full_name" name="full_name" placeholder="e.g. Kasun Perera" value="<?= e($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender <span style="color:var(--error)">*</span></label>
                        <select id="gender" name="gender" required>
                            <option value="">— Select gender —</option>
                            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number <span style="color:var(--error)">*</span></label>
                        <input type="tel" id="phone" name="phone" placeholder="e.g. 0771234567" value="<?= e($_POST['phone'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address <span style="color:var(--error)">*</span></label>
                        <input type="email" id="email" name="email" placeholder="e.g. kasun@email.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="course">Programme / Course <span style="color:var(--error)">*</span></label>
                        <select id="course" name="course_id" required>
                            <option value="">— Select programme —</option>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= e($c['id']) ?>" <?= (string) ($_POST['course_id'] ?? '') === (string) $c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="enrolled_date">Enrolled Date <span style="color:var(--error)">*</span></label>
                        <input type="date" id="enrolled_date" name="enrolled_date" value="<?= e($_POST['enrolled_date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group form-full">
                        <label for="address">Address <span style="color:var(--error)">*</span></label>
                        <textarea id="address" name="address" placeholder="Full residential address" required><?= e($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label for="photo">Student Photo</label>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div style="margin-top:1.75rem; display:flex; gap:10px; justify-content:flex-end;">
                    <a href="dashboard.php" class="btn btn-outline">Cancel</a>
                    <button type="submit" class="btn btn-gold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Register Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>