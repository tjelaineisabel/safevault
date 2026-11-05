<?php
include 'db.php';
session_start();

// 1. Security & Connection Check
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'jelaine123') { header("Location: login.php"); exit(); }
if (!$conn) die("Database connection failed.");

// 2. Data Logic: Counters
$totalDocs = $urgentCount = 0;
foreach (['personal', 'work', 'government', 'medical'] as $t) {
    if ($conn->query("SHOW TABLES LIKE '$t'")->num_rows > 0) {
        $totalDocs += $conn->query("SELECT COUNT(*) FROM $t")->fetch_row()[0];
        $urgentCount += $conn->query("SELECT COUNT(*) FROM $t WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND expiry_date != '0000-00-00'")->fetch_row()[0];
    }
}

$activeUsers = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetch_row()[0];
$unreadInquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetch_row()[0] ?? 0;
$pendingCount = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'deactivated' OR status = 'pending'")->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Safe-Vault | Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: #0d2a4a; min-height: 100vh; color: white; position: fixed; width: 16.6%; z-index: 100; }
        .sidebar a { color: #adb5bd; padding: 12px 25px; display: block; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #1a3e61; color: white; border-left: 4px solid #3498db; }
        .main-content { margin-left: 16.6%; padding: 40px; } 
        .report-card { border: none; border-radius: 20px; padding: 30px; color: white; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .report-card i { position: absolute; right: 20px; bottom: 10px; font-size: 4rem; opacity: 0.2; }
        .bg-navy { background: linear-gradient(135deg, #0d2a4a, #1e4b7a); }
        .bg-urgent { background: linear-gradient(135deg, #d63031, #eb4d4b); }
        .bg-success-grad { background: linear-gradient(135deg, #198754, #27ae60); }
        @media print { .sidebar, .btn-print { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="p-4 text-center">
        <h4 class="fw-bold mb-0">Safe-Vault</h4>
        <small class="text-info opacity-75">Admin Control</small>
    </div>
    
    <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    
    <a href="users.php" class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i> User Management</span>
        <?php if($pendingCount > 0): ?><span class="badge bg-danger rounded-pill"><?=$pendingCount?></span><?php endif; ?>
    </a>
    
    <a href="admin_inquiries.php" class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-left-text me-2"></i> User Inquiries</span>
        <?php if($unreadInquiries > 0): ?><span class="badge bg-primary rounded-pill"><?=$unreadInquiries?></span><?php endif; ?>
    </a>
    
    <a href="reports.php" class="active"><i class="bi bi-graph-up-arrow me-2"></i> Reports</a>
    
    <div class="position-absolute bottom-0 w-100 p-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between mb-5">
        <div>
            <h2 class="fw-bold mb-0">Vault Analytics</h2>
            <p class="text-muted">System data summary and document status.</p>
        </div>
        <button class="btn btn-white shadow-sm rounded-pill px-4 border btn-print" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Print Report
        </button>
    </div>

    <div class="row g-4 text-white">
        <div class="col-md-4">
            <div class="report-card bg-navy">
                <i class="bi bi-files"></i>
                <small class="fw-bold opacity-75">TOTAL DOCUMENTS</small>
                <h1 class="display-4 fw-bold mt-2"><?=$totalDocs?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card bg-urgent">
                <i class="bi bi-exclamation-triangle"></i>
                <small class="fw-bold opacity-75">EXPIRING SOON</small>
                <h1 class="display-4 fw-bold mt-2"><?=$urgentCount?></h1>
            </div>
        </div>
        <div class="col-md-4">
            <div class="report-card bg-success-grad">
                <i class="bi bi-person-check"></i>
                <small class="fw-bold opacity-75">ACTIVE USERS</small>
                <h1 class="display-4 fw-bold mt-2"><?=$activeUsers?></h1>
            </div>
        </div>
    </div>

    <div class="mt-5 p-4 bg-white rounded-4 shadow-sm border text-center">
        <div class="row">
            <div class="col-3 border-end">
                <h6 class="text-muted small">PENDING APPROVALS</h6>
                <h4 class="fw-bold"><?=$pendingCount?></h4>
            </div>
            <div class="col-3 border-end">
                <h6 class="text-muted small">UNREAD INQUIRIES</h6>
                <h4 class="fw-bold text-primary"><?=$unreadInquiries?></h4>
            </div>
            <div class="col-3 border-end">
                <h6 class="text-muted small">SYSTEM STATUS</h6>
                <h4 class="fw-bold text-success">Online</h4>
            </div>
            <div class="col-3">
                <h6 class="text-muted small">REPORT DATE</h6>
                <h6 class="fw-bold"><?=date('M d, Y')?></h6>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>