<?php
require_once 'includes/db.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$format = $_GET['format'] ?? 'csv';
$students_result = $conn->query('SELECT * FROM students ORDER BY full_name ASC');
$students = [];

if ($students_result instanceof mysqli_result) {
    while ($row = $students_result->fetch_assoc()) {
        $students[] = $row;
    }
}

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students-export.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student ID', 'NIC', 'Full Name', 'Gender', 'Address', 'Phone', 'Email', 'Course', 'Enrolled Date', 'Photo Path']);

    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_id'],
            $student['nic'],
            $student['full_name'],
            $student['gender'],
            $student['address'],
            $student['phone'],
            $student['email'],
            $student['course'],
            $student['enrolled_date'],
            $student['photo_path'],
        ]);
    }

    fclose($output);
    exit();
}

$page_title = 'Export Students';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background: #f5f0e8;
        }
        .print-sheet {
            max-width: 1200px;
            margin: 24px auto;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 12px 40px rgba(13,27,42,0.15);
        }
        .print-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            margin-bottom: 20px;
        }
        .print-header h1 {
            font-family: 'Playfair Display', serif;
            color: #0d1b2a;
            margin-bottom: 6px;
        }
        .print-header p {
            color: #4a5568;
        }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d4c9b8; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #0d1b2a; color: #e8c96b; }
        .actions { display: flex; gap: 10px; margin-bottom: 18px; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .print-sheet { box-shadow: none; margin: 0; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="print-sheet">
        <div class="print-header">
            <div>
                <h1>Students Export Report</h1>
                <p>IMBS Green Campus Student Management System</p>
            </div>
            <div>
                <div style="font-weight:700;color:#0d1b2a;">Generated on <?= date('M d, Y H:i') ?></div>
                <div style="color:#4a5568;">Rows: <?= count($students) ?></div>
            </div>
        </div>

        <div class="actions">
            <a href="export_students.php?format=csv" class="btn btn-gold">Download Excel CSV</a>
            <button type="button" class="btn btn-primary" onclick="window.print()">Print / Save as PDF</button>
            <a href="students.php" class="btn btn-outline">Back to Students</a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>NIC</th>
                    <th>Name</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Course</th>
                    <th>Enrolled</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?= e($student['student_id']) ?></td>
                        <td><?= e($student['nic']) ?></td>
                        <td><?= e($student['full_name']) ?></td>
                        <td><?= e($student['gender']) ?></td>
                        <td><?= e($student['phone']) ?></td>
                        <td><?= e($student['email']) ?></td>
                        <td><?= e($student['course']) ?></td>
                        <td><?= e($student['enrolled_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <script>
        window.onload = function () {
            if (new URLSearchParams(window.location.search).get('format') === 'pdf') {
                window.print();
            }
        };
    </script>
</body>
</html>
