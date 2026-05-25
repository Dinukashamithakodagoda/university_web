<?php
$page_title = 'Dashboard';
require_once 'includes/db.php';
require_once 'includes/header.php';

$total_students = (int) ($conn->query('SELECT COUNT(*) AS c FROM students')->fetch_assoc()['c'] ?? 0);
$total_courses = 0;
if (table_exists($conn, 'courses')) {
    $course_count_result = $conn->query('SELECT COUNT(*) AS c FROM courses WHERE is_active = 1');
    if ($course_count_result instanceof mysqli_result) {
        $total_courses = (int) ($course_count_result->fetch_assoc()['c'] ?? 0);
    }
}
$this_month = (int) ($conn->query('SELECT COUNT(*) AS c FROM students WHERE YEAR(enrolled_date) = YEAR(CURDATE()) AND MONTH(enrolled_date) = MONTH(CURDATE())')->fetch_assoc()['c'] ?? 0);
$total_admins = (int) ($conn->query('SELECT COUNT(*) AS c FROM admins')->fetch_assoc()['c'] ?? 0);

$recent = $conn->query('SELECT * FROM students ORDER BY created_at DESC LIMIT 5');
$monthly_rows = [];
$monthly_query = $conn->query("SELECT DATE_FORMAT(enrolled_date, '%Y-%m') AS month_key, COUNT(*) AS total FROM students WHERE enrolled_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(enrolled_date, '%Y-%m') ORDER BY month_key ASC");
if ($monthly_query instanceof mysqli_result) {
    while ($row = $monthly_query->fetch_assoc()) {
        $monthly_rows[] = $row;
    }
}

$course_rows = [];
$course_query = $conn->query('SELECT course, COUNT(*) AS total FROM students GROUP BY course ORDER BY total DESC LIMIT 5');
if ($course_query instanceof mysqli_result) {
    while ($row = $course_query->fetch_assoc()) {
        $course_rows[] = $row;
    }
}

$month_labels = [];
$month_values = [];
foreach ($monthly_rows as $row) {
    $month_labels[] = date('M Y', strtotime($row['month_key'] . '-01'));
    $month_values[] = (int) $row['total'];
}

$course_labels = [];
$course_values = [];
foreach ($course_rows as $row) {
    $course_labels[] = $row['course'];
    $course_values[] = (int) $row['total'];
}

$chart_payload = [
    'monthLabels' => $month_labels,
    'monthValues' => $month_values,
    'courseLabels' => $course_labels,
    'courseValues' => $course_values,
];
?>

<div class="page-wrapper">
    <div class="page-header fade-in">
        <h1>Welcome, <?= e($_SESSION['admin_name']) ?></h1>
        <div class="gold-line"></div>
        <p>Student Management Dashboard for IMBS Green Campus</p>
    </div>

    <div class="stats-grid fade-in fade-in-delay-1">
        <div class="stat-card">
            <div class="stat-icon navy">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
            </div>
            <div class="stat-info">
                <h3><?= $total_students ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
            </div>
            <div class="stat-info">
                <h3><?= $total_courses ?></h3>
                <p>Total Courses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l8-4 6 3v15"/><path d="M9 21v-8h6v8"/></svg>
            </div>
            <div class="stat-info">
                <h3><?= $this_month ?></h3>
                <p>Monthly Registrations</p>
            </div>
        </div>
    </div>

    <div class="stats-grid fade-in fade-in-delay-2" style="grid-template-columns:repeat(2,1fr);">
        <div class="stat-card">
            <div class="stat-icon navy">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 7V3h8v4"/><rect x="6" y="7" width="12" height="14" rx="2"/><path d="M9 12h6"/></svg>
            </div>
            <div class="stat-info">
                <h3><?= $total_admins ?></h3>
                <p>Admin Accounts</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon gold">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/></svg>
            </div>
            <div class="stat-info">
                <h3><?= count($course_rows) ?></h3>
                <p>Courses with Students</p>
            </div>
        </div>
    </div>

    <div class="card fade-in fade-in-delay-2" style="margin-bottom:2rem;">
        <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 15l4-4 3 3 5-6"/></svg>
            <h2>Registration Analytics</h2>
        </div>
        <div class="card-body">
            <div class="chart-grid">
                <div class="chart-card">
                    <h3>Monthly Registrations</h3>
                    <canvas id="registrationsChart" height="220"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Top Courses</h3>
                    <canvas id="coursesChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex; gap:10px; margin-bottom:1rem; flex-wrap:wrap;">
        <a href="register.php" class="btn btn-gold">+ Register New Student</a>
        <a href="students.php" class="btn btn-primary">View All Students</a>
        <a href="export_students.php?format=csv" class="btn btn-outline">Export to Excel</a>
        <a href="export_students.php?format=pdf" class="btn btn-outline">Export to PDF</a>
    </div>

    <div class="card fade-in fade-in-delay-3">
        <div class="card-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <h2>Recently Registered Students</h2>
        </div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>NIC</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>Course</th>
                            <th>Enrolled</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent && $recent->num_rows > 0): ?>
                            <?php while ($s = $recent->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= e($s['student_id']) ?></strong></td>
                                    <td><code><?= e($s['nic']) ?></code></td>
                                    <td><strong><?= e($s['full_name']) ?></strong></td>
                                    <td><span class="badge badge-<?= strtolower($s['gender']) ?>"><?= e($s['gender']) ?></span></td>
                                    <td><?= e($s['course']) ?></td>
                                    <td><?= date('M d, Y', strtotime($s['enrolled_date'])) ?></td>
                                    <td>
                                        <a href="students.php?view=<?= urlencode($s['nic']) ?>" class="btn btn-outline btn-sm">View</a>
                                        <a href="students.php?edit=<?= urlencode($s['nic']) ?>" class="btn btn-outline btn-sm">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7">
                                <div class="empty-state">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    <p>No students registered yet. <a href="register.php">Register the first student</a></p>
                                </div>
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
const dashboardData = <?= json_encode($chart_payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

function drawBarChart(canvasId, labels, values, color) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !canvas.getContext) {
        return;
    }

    const context = canvas.getContext('2d');
    const width = canvas.width = canvas.clientWidth;
    const height = canvas.height = canvas.getAttribute('height') || 220;
    const padding = 32;
    const chartWidth = width - padding * 2;
    const chartHeight = height - padding * 2;
    const max = Math.max(...values, 1);
    const barWidth = labels.length ? chartWidth / labels.length : chartWidth;

    context.clearRect(0, 0, width, height);
    context.fillStyle = '#f5f0e8';
    context.fillRect(0, 0, width, height);

    context.strokeStyle = '#d4c9b8';
    context.lineWidth = 1;
    context.beginPath();
    context.moveTo(padding, padding);
    context.lineTo(padding, height - padding);
    context.lineTo(width - padding, height - padding);
    context.stroke();

    context.font = '12px DM Sans, sans-serif';
    context.fillStyle = '#4a5568';

    labels.forEach((label, index) => {
        const value = values[index] || 0;
        const barHeight = (value / max) * (chartHeight - 20);
        const x = padding + index * barWidth + 10;
        const y = height - padding - barHeight;

        context.fillStyle = color;
        context.fillRect(x, y, Math.max(barWidth - 20, 12), barHeight);

        context.fillStyle = '#4a5568';
        context.save();
        context.translate(x, height - padding + 14);
        context.rotate(-Math.PI / 8);
        context.fillText(label, 0, 0);
        context.restore();

        context.fillText(String(value), x, y - 6);
    });
}

drawBarChart('registrationsChart', dashboardData.monthLabels, dashboardData.monthValues, '#0d1b2a');
drawBarChart('coursesChart', dashboardData.courseLabels, dashboardData.courseValues, '#c9a84c');
</script>

<?php require_once 'includes/footer.php'; ?>