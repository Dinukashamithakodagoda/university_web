<?php
$page_title = 'Search Student';
require_once 'includes/db.php';
require_once 'includes/header.php';

$courses = course_options($conn);
if (empty($courses)) {
    $courses = [];
}

$results = [];
$searched = false;
$query = trim($_GET['q'] ?? '');
$course_id = (int) ($_GET['course_id'] ?? 0);

if ($query !== '' || $course_id > 0) {
    $searched = true;
    $sql = 'SELECT * FROM students WHERE 1=1';
    $types = '';
    $params = [];

    if ($query !== '') {
        $sql .= ' AND (nic LIKE ? OR full_name LIKE ?)' ;
        $like = '%' . $query . '%';
        $types .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    if ($course_id > 0) {
        $course_name = '';
        foreach ($courses as $course) {
            if ((int) $course['id'] === $course_id) {
                $course_name = $course['course_name'];
                break;
            }
        }

        if ($course_name !== '') {
            $sql .= ' AND course = ?';
            $types .= 's';
            $params[] = $course_name;
        }
    }

    $sql .= ' ORDER BY full_name ASC';
    $stmt = $conn->prepare($sql);

    if ($stmt instanceof mysqli_stmt) {
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<div class="page-wrapper">
    <div class="page-header fade-in">
        <h1>Search Students</h1>
        <div class="gold-line"></div>
        <p>Find a student by NIC, name, or course</p>
    </div>

    <div class="card fade-in fade-in-delay-1">
        <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <h2>Student Search</h2>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="search-bar">
                    <input type="text" name="q" value="<?= e($query) ?>" placeholder="Search by NIC or name..." autofocus>
                    <select name="course_id">
                        <option value="0">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= e($course['id']) ?>" <?= $course_id === (int) $course['id'] ? 'selected' : '' ?>>
                                <?= e($course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-navy btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Search
                    </button>
                </div>
            </form>

            <?php if ($searched): ?>
                <?php if (count($results) > 0): ?>
                    <div class="alert alert-info" style="margin-bottom:1rem;">
                        Found <strong><?= count($results) ?></strong> result(s)
                    </div>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>NIC</th>
                                    <th>Full Name</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                    <th>Course</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $s): ?>
                                    <tr>
                                        <td><strong><?= e($s['student_id']) ?></strong></td>
                                        <td><code><?= e($s['nic']) ?></code></td>
                                        <td><strong><?= e($s['full_name']) ?></strong></td>
                                        <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= e($s['gender']) ?></span></td>
                                        <td><?= e($s['phone']) ?></td>
                                        <td><?= e($s['course']) ?></td>
                                        <td>
                                            <a href="students.php?view=<?= urlencode($s['nic']) ?>" class="btn btn-outline btn-sm">View</a>
                                            <a href="students.php?edit=<?= urlencode($s['nic']) ?>" class="btn btn-outline btn-sm">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <p>No students found for the selected criteria.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <p>Enter a NIC or name above, then optionally choose a course.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>