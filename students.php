<?php
$page_title = 'Students';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $error = 'Invalid session token. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $nic = trim($_POST['nic'] ?? '');

        if ($nic !== '') {
            $stmt = $conn->prepare('DELETE FROM students WHERE nic = ?');
            if ($stmt instanceof mysqli_stmt) {
                $stmt->bind_param('s', $nic);
                if ($stmt->execute()) {
                    $success = 'Student record deleted successfully.';
                } else {
                    $error = 'Delete failed: ' . e($conn->error);
                }
                $stmt->close();
            }
        } else {
            $error = 'Student NIC is required.';
        }
    } elseif (($_POST['action'] ?? '') === 'update') {
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

        $photo_path = $_POST['existing_photo'] ?? null;
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
                    $photo_name = preg_replace('/[^A-Za-z0-9_-]/', '', $nic) . '-' . time() . '.' . $allowed_types[$detected_type];
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
            $stmt = $conn->prepare('UPDATE students SET full_name = ?, gender = ?, address = ?, phone = ?, email = ?, course = ?, photo_path = ?, enrolled_date = ? WHERE nic = ?');
            if ($stmt instanceof mysqli_stmt) {
                $stmt->bind_param('sssssssss', $full_name, $gender, $address, $phone, $email, $course_name, $photo_path, $enrolled, $nic);
                if ($stmt->execute()) {
                    $success = 'Student record updated successfully.';
                } else {
                    $error = 'Update failed: ' . e($conn->error);
                }
                $stmt->close();
            }
        } else {
            $error = 'Please fill all required fields.';
        }
    }
}

$edit_student = null;
$view_student = null;

if (isset($_GET['edit'])) {
    $nic = trim($_GET['edit']);
    $stmt = $conn->prepare('SELECT * FROM students WHERE nic = ?');
    if ($stmt instanceof mysqli_stmt) {
        $stmt->bind_param('s', $nic);
        $stmt->execute();
        $edit_student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (isset($_GET['view'])) {
    $nic = trim($_GET['view']);
    $stmt = $conn->prepare('SELECT * FROM students WHERE nic = ?');
    if ($stmt instanceof mysqli_stmt) {
        $stmt->bind_param('s', $nic);
        $stmt->execute();
        $view_student = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

$students = $conn->query('SELECT * FROM students ORDER BY created_at DESC');
?>

<div class="page-wrapper">
    <div class="page-header fade-in">
        <h1>All Students</h1>
        <div class="gold-line"></div>
        <p>Manage student records, view details, update profiles, or remove entries</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success fade-in"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error fade-in"><?= e($error) ?></div>
    <?php endif; ?>

    <div style="display:flex; gap:10px; justify-content:flex-end; margin-bottom:1rem; flex-wrap:wrap;">
        <a href="register.php" class="btn btn-gold">+ Register New Student</a>
    </div>

    <div class="card fade-in fade-in-delay-1">
        <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <h2>Student Records</h2>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Student ID</th>
                            <th>NIC</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Enrolled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($students && $students->num_rows > 0): ?>
                            <?php while ($s = $students->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <img src="<?= e(student_photo_url($s['photo_path'] ?? null)) ?>" alt="Student photo" style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:1px solid var(--border);">
                                    </td>
                                    <td><strong><?= e($s['student_id']) ?></strong></td>
                                    <td><code><?= e($s['nic']) ?></code></td>
                                    <td>
                                        <strong><?= e($s['full_name']) ?></strong><br>
                                        <small style="color:var(--text-light)"><?= e($s['email']) ?></small>
                                    </td>
                                    <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= e($s['gender']) ?></span></td>
                                    <td><?= e($s['phone']) ?></td>
                                    <td style="max-width:180px;"><?= e($s['course']) ?></td>
                                    <td><?= date('M d, Y', strtotime($s['enrolled_date'])) ?></td>
                                    <td>
                                        <button type="button" class="btn btn-outline btn-sm" onclick='openView(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>View</button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick='openEdit(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete('<?= e($s['nic']) ?>', '<?= e($s['full_name']) ?>')">Delete</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9">
                                <div class="empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    <p>No students registered yet.</p>
                                </div>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="viewModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Student Profile</h3>
            <button class="modal-close" type="button" onclick="closeModal('viewModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="display:flex; gap:1rem; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
                <img id="view_photo" src="" alt="Student photo" style="width:96px;height:96px;border-radius:20px;object-fit:cover;border:1px solid var(--border);">
                <div>
                    <div style="font-size:1.25rem;font-weight:700;color:var(--navy);" id="view_name"></div>
                    <div style="color:var(--text-mid);" id="view_student_id"></div>
                    <div style="margin-top:6px;" id="view_badge"></div>
                </div>
            </div>
            <div class="form-grid single" style="gap:0.85rem;">
                <div><strong>NIC:</strong> <span id="view_nic"></span></div>
                <div><strong>Email:</strong> <span id="view_email"></span></div>
                <div><strong>Phone:</strong> <span id="view_phone"></span></div>
                <div><strong>Course:</strong> <span id="view_course"></span></div>
                <div><strong>Enrolled:</strong> <span id="view_enrolled"></span></div>
                <div><strong>Address:</strong> <span id="view_address"></span></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
            <button type="button" class="btn btn-gold" onclick="closeModal('viewModal'); openEdit(window.currentStudent)">Edit</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Student Record</h3>
            <button class="modal-close" type="button" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="existing_photo" id="edit_existing_photo">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>NIC</label>
                        <input type="text" name="nic" id="edit_nic" readonly style="opacity:0.7">
                    </div>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" id="edit_gender" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Phone *</label>
                        <input type="tel" name="phone" id="edit_phone" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" id="edit_email" required>
                    </div>
                    <div class="form-group">
                        <label>Course *</label>
                        <select name="course_id" id="edit_course" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= e($c['id']) ?>"><?= e($c['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Enrolled Date *</label>
                        <input type="date" name="enrolled_date" id="edit_enrolled_date" required>
                    </div>
                    <div class="form-group form-full">
                        <label>Address *</label>
                        <textarea name="address" id="edit_address" required></textarea>
                    </div>
                    <div class="form-group form-full">
                        <label>Replace Photo</label>
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                <button type="submit" class="btn btn-gold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <button class="modal-close" type="button" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="nic" id="delete_nic">
            <div class="modal-body">
                <div class="alert alert-error" style="margin:0;">
                    Are you sure you want to delete <strong id="delete_name"></strong>? This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
window.currentStudent = null;

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openView(student) {
    window.currentStudent = student;
    document.getElementById('view_photo').src = student.photo_path ? student.photo_path : 'https://via.placeholder.com/120x120.png?text=Student';
    document.getElementById('view_name').textContent = student.full_name;
    document.getElementById('view_student_id').textContent = student.student_id;
    document.getElementById('view_nic').textContent = student.nic;
    document.getElementById('view_email').textContent = student.email;
    document.getElementById('view_phone').textContent = student.phone;
    document.getElementById('view_course').textContent = student.course;
    document.getElementById('view_enrolled').textContent = student.enrolled_date;
    document.getElementById('view_address').textContent = student.address;
    document.getElementById('view_badge').innerHTML = '<span class="badge badge-' + student.gender.toLowerCase() + '">' + student.gender + '</span>';
    document.getElementById('viewModal').classList.add('active');
}

function openEdit(student) {
    window.currentStudent = student;
    document.getElementById('edit_nic').value = student.nic;
    document.getElementById('edit_full_name').value = student.full_name;
    document.getElementById('edit_gender').value = student.gender;
    document.getElementById('edit_address').value = student.address;
    document.getElementById('edit_phone').value = student.phone;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_enrolled_date').value = student.enrolled_date;
    document.getElementById('edit_existing_photo').value = student.photo_path || '';

    var courseSelect = document.getElementById('edit_course');
    for (var i = 0; i < courseSelect.options.length; i++) {
        if (courseSelect.options[i].text === student.course) {
            courseSelect.selectedIndex = i;
            break;
        }
    }

    document.getElementById('editModal').classList.add('active');
}

function confirmDelete(nic, name) {
    document.getElementById('delete_nic').value = nic;
    document.getElementById('delete_name').textContent = name;
    document.getElementById('deleteModal').classList.add('active');
}

document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (event) {
        if (event.target === this) {
            closeModal(this.id);
        }
    });
});

<?php if ($view_student): ?>
openView(<?= json_encode($view_student, JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
<?php elseif ($edit_student): ?>
openEdit(<?= json_encode($edit_student, JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>