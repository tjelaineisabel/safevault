<?php
include 'db.php';
session_start();

// 1. Security & Connection Check
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'jelaine123') { header("Location: login.php"); exit(); }
if (!$conn) die("Database connection failed.");

// 2. Combined Action Handler
if ($id = ($_GET['approve_id'] ?? $_GET['reactivate_id'] ?? null)) {
    $id = intval($id);
    $msg = isset($_GET['approve_id']) ? "Approved" : "Reactivated";
    $conn->query("UPDATE users SET status = 'active', attempts = 0 WHERE id = $id");
    header("Location: users.php?msg=$msg"); exit();
}

// 3. Counter Logic for Sidebar Badges
$unreadInquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetch_row()[0] ?? 0;
$pendingCount = $conn->query("SELECT COUNT(*) FROM users WHERE status != 'active'")->fetch_row()[0] ?? 0;

// 4. Fetch Users (excluding admin)
$result = $conn->query("SELECT * FROM users WHERE username != 'jelaine123' ORDER BY status DESC, id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Safe-Vault | Users</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: #0d2a4a; min-height: 100vh; color: white; position: fixed; width: 16.6%; z-index: 100; }
        .sidebar a { color: #adb5bd; padding: 12px 25px; display: block; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #1a3e61; color: white; border-left: 4px solid #3498db; }
        .main-content { margin-left: 16.6%; padding: 40px; } 
        .vault-card { border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white; border: none; }
    </style>
</head>
<body>

<div class="sidebar shadow">
    <div class="p-4 text-center">
        <h4 class="fw-bold mb-0">Safe-Vault</h4>
        <small class="text-info opacity-75">Admin Control</small>
    </div>
    <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    
    <a href="users.php" class="active d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i> User Management</span>
        <?php if($pendingCount > 0): ?><span class="badge bg-danger rounded-pill"><?=$pendingCount?></span><?php endif; ?>
    </a>

    <a href="admin_inquiries.php" class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-left-text me-2"></i> User Inquiries</span>
        <?php if($unreadInquiries > 0): ?><span class="badge bg-primary rounded-pill"><?=$unreadInquiries?></span><?php endif; ?>
    </a>

    <a href="reports.php"><i class="bi bi-graph-up-arrow me-2"></i> Reports</a>

    <div class="position-absolute bottom-0 w-100 p-3">
        <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div><h2 class="fw-bold mb-0">User Management</h2><p class="text-muted small">Manage account access and security logs.</p></div>
        <div class="bg-white p-2 px-3 rounded-pill shadow-sm border small">Welcome, <strong>jelaine123</strong></div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> User <strong><?=htmlspecialchars($_GET['msg'])?></strong>!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="vault-card p-4">
        <table class="table align-middle">
            <thead class="table-light small text-muted">
                <tr><th>USERNAME</th><th>STATUS</th><th>SECURITY LOG</th><th class="text-end">ACTION</th></tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="fw-bold"><i class="bi bi-person-circle me-2 text-secondary"></i><?=htmlspecialchars($row['username'])?></td>
                    <td>
                        <?php if($row['status'] == 'active'): ?><span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">Active</span>
                        <?php elseif($row['attempts'] >= 3): ?><span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2">Deactivated</span>
                        <?php else: ?><span class="badge bg-warning-subtle text-warning rounded-pill px-3 py-2">Pending</span><?php endif; ?>
                    </td>
                    <td class="small text-muted"><i class="bi bi-shield-exclamation me-1"></i><?=$row['attempts']?> failed attempts</td>
                    <td class="text-end">
                        <?php if($row['status'] !== 'active'): ?>
                            <a href="users.php?<?=($row['attempts'] >= 3 ? 'reactivate_id' : 'approve_id')?>=<?=$row['id']?>" 
                               class="btn btn-sm <?=($row['attempts'] >= 3 ? 'btn-dark' : 'btn-primary')?> px-3 rounded-pill">
                               <i class="bi <?=($row['attempts'] >= 3 ? 'bi-unlock' : 'bi-check-lg')?> me-1"></i><?=($row['attempts'] >= 3 ? 'Reactivate' : 'Approve')?>
                            </a>
                        <?php else: ?><span class="text-muted small"><i class="bi bi-patch-check-fill text-primary me-1"></i>Verified</span><?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center py-5 text-muted">No user records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>