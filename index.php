<?php 
include 'db.php'; 
session_start();

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'jelaine123') {
    header("Location: login.php"); exit();
}

// 2. Dashboard Statistics Logic (Nasa dash database ang users at inquiries)
$unreadInquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetch_row()[0] ?? 0;
$totalUsers = $conn->query("SELECT COUNT(*) FROM users WHERE username != 'jelaine123'")->fetch_row()[0] ?? 0;
$pendingUsers = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'deactivated'")->fetch_row()[0] ?? 0;

// 3. User & Category Filters (Pinagsamang kategorya mula sa dash at ang records mula sa rec)
$dynamicCategories = ['government', 'medical', 'school', 'personal', 'work', 'records'];

$search = mysqli_real_escape_string($conn, $_GET['search'] ?? '');
$viewUser = mysqli_real_escape_string($conn, $_GET['view_user'] ?? '');
$currentCat = (isset($_GET['cat']) && in_array($_GET['cat'], $dynamicCategories)) ? $_GET['cat'] : ($dynamicCategories[0] ?? '');

$userSQL = "SELECT username FROM users WHERE status = 'active' AND username != 'jelaine123'";
if ($search) $userSQL .= " AND username LIKE '%$search%'";
$userList = $conn->query($userSQL);

function getFileStatus($expiryDate) {
    $diff = (int)(new DateTime())->diff(new DateTime($expiryDate))->format("%r%a");
    if ($diff < 0) return ['bg' => 'bg-secondary', 'text' => 'Expired'];
    return $diff <= 7 ? ['bg' => 'bg-danger', 'text' => 'Urgent'] : ['bg' => 'bg-success', 'text' => 'Secure'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>Safe-Vault | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: #0d2a4a; min-height: 100vh; position: fixed; width: 16.6%; color: white; z-index: 100; box-shadow: 4px 0 10px rgba(0,0,0,0.05); }
        .sidebar a { color: #adb5bd; padding: 14px 25px; display: block; text-decoration: none; transition: 0.3s; font-weight: 500; }
        .sidebar a:hover, .sidebar a.active { background: #1a3e61; color: white; border-left: 4px solid #3498db; }
        .main-content { margin-left: 16.6%; padding: 40px; }
        .vault-card { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.04); }
        .user-pill { border-radius: 30px; padding: 8px 16px; font-size: 0.85rem; text-decoration: none; display: inline-block; font-weight: 500; transition: all 0.2s; }
        .stats-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-size: 1.3rem; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="p-4 text-center">
        <h4 class="fw-bold mb-0 text-white">Safe-Vault</h4>
        <small class="text-info opacity-75">Admin Control</small>
    </div>
    <hr class="border-secondary opacity-25 mx-3">
    <a href="index.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="users.php" class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-people me-2"></i> User Management</span>
        <?php if($pendingUsers > 0): ?><span class="badge bg-danger rounded-pill"><?=$pendingUsers?></span><?php endif; ?>
    </a>
    <a href="admin_inquiries.php" class="d-flex justify-content-between align-items-center">
        <span><i class="bi bi-chat-dots me-2"></i> User Inquiries</span>
        <?php if($unreadInquiries > 0): ?><span class="badge bg-primary rounded-pill"><?=$unreadInquiries?></span><?php endif; ?>
    </a>
    <a href="reports.php"><i class="bi bi-graph-up-arrow me-2"></i> Reports</a>

    <div class="position-absolute bottom-0 w-100 p-3">
        <hr class="opacity-25">
        <a href="logout.php" class="text-danger text-decoration-none d-block px-4 py-2"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a>
    </div>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Admin Dashboard</h2>
            <p class="text-muted small mb-0">Welcome back, manage system users and vault records efficiently.</p>
        </div>
        <div class="bg-white px-3 py-2 rounded-pill shadow-sm border small d-flex align-items-center">
            <i class="bi bi-person-circle text-primary me-2 fs-5"></i> <span><strong>jelaine123</strong></span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="vault-card p-4 d-flex align-items-center">
                <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3"><i class="bi bi-people"></i></div>
                <div><h3 class="mb-0 fw-bold"><?=$totalUsers?></h3><small class="text-muted fw-semibold">Total Active Users</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vault-card p-4 d-flex align-items-center">
                <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3"><i class="bi bi-clock-history"></i></div>
                <div><h3 class="mb-0 fw-bold"><?=$pendingUsers?></h3><small class="text-muted fw-semibold">Pending Approvals</small></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="vault-card p-4 d-flex align-items-center">
                <div class="stats-icon bg-info bg-opacity-10 text-info me-3"><i class="bi bi-chat-text"></i></div>
                <div><h3 class="mb-0 fw-bold"><?=$unreadInquiries?></h3><small class="text-muted fw-semibold">New Inquiries</small></div>
            </div>
        </div>
    </div>

    <!-- User Filter & Search Section -->
    <div class="vault-card p-4 mb-4">
        <form method="GET" action="index.php" class="row align-items-center g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control rounded-start-pill ps-3" placeholder="Search user..." value="<?=htmlspecialchars($search)?>">
                    <button type="submit" class="btn btn-primary rounded-end-pill px-3"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="col-md-8">
                <div class="d-flex flex-wrap justify-content-md-end gap-2 align-items-center">
                    <span class="text-muted small me-2">Select User:</span>
                    <?php 
                    $userList->data_seek(0);
                    if($userList->num_rows > 0): while($u = $userList->fetch_assoc()): 
                    ?>
                        <a href="index.php?view_user=<?=urlencode($u['username'])?>&search=<?=urlencode($search)?>" 
                           class="user-pill <?=($viewUser == $u['username']) ? 'bg-primary text-white shadow-sm' : 'bg-light text-dark border'?>">
                           <?=htmlspecialchars($u['username'])?>
                        </a>
                    <?php endwhile; else: ?>
                        <span class="text-muted small italic">No other users found.</span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Vault Contents Display -->
    <?php if ($viewUser): ?>
        <div class="vault-card overflow-hidden mb-4">
            <div class="bg-light px-4 pt-3 border-bottom">
                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-folder2-open me-2"></i>Vault Files for: <?=htmlspecialchars($viewUser)?></h5>
                <ul class="nav nav-tabs border-0">
                    <?php foreach($dynamicCategories as $cat): ?>
                        <li class="nav-item">
                            <a class="nav-link border-0 px-4 py-2 <?=($currentCat == $cat) ? 'active fw-bold bg-white border-bottom border-primary border-3 text-dark shadow-sm rounded-top' : 'text-muted'?>" 
                               href="index.php?view_user=<?=urlencode($viewUser)?>&cat=<?=$cat?>&search=<?=urlencode($search)?>">
                               <?=ucfirst($cat)?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="p-4 bg-white">
                <table class="table align-middle mb-0">
                    <thead class="table-light text-muted small uppercase">
                        <tr><th>FILE NAME</th><th>EXPIRY DATE</th><th>STATUS</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        // Pagkuha ng records mula sa dash (para sa gov, med, school, personal, work) o sa rec (para sa records)
                        $tableName = ($currentCat === 'records') ? 'rec.records' : "`$currentCat`";
                        $files = $conn->query("SELECT file_name, expiry_date FROM $tableName WHERE username = '$viewUser'");
                        if ($files && $files->num_rows > 0):
                            while($f = $files->fetch_assoc()): 
                                $status = getFileStatus($f['expiry_date']);
                        ?>
                            <tr>
                                <td class="fw-bold"><i class="bi bi-file-earmark-lock me-2 text-primary"></i><?=htmlspecialchars($f['file_name'])?></td>
                                <td class="text-muted"><?=$f['expiry_date']?></td>
                                <td><span class="badge <?=$status['bg']?> rounded-pill px-3 py-2"><?=$status['text']?></span></td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" class="text-center py-5 text-muted">No documents found in this folder category.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Active Users Directory Table (Integrated when no user is selected) -->
        <div class="vault-card p-4 mb-4 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Active Users Directory</h5>
                <span class="badge bg-primary rounded-pill px-3 py-2">Total: <?=$totalUsers?></span>
            </div>
            <p class="text-muted small mb-3">Select a user above or click "View Vault" below to inspect their files.</p>
            <table class="table align-middle mb-0">
                <thead class="table-light text-muted small uppercase">
                    <tr>
                        <th>USERNAME</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $activeUsersQuery = $conn->query("SELECT username, status FROM users WHERE status = 'active' AND username != 'jelaine123'");
                    if ($activeUsersQuery && $activeUsersQuery->num_rows > 0):
                        while($au = $activeUsersQuery->fetch_assoc()):
                    ?>
                        <tr>
                            <td class="fw-bold"><i class="bi bi-person-badge me-2 text-secondary"></i><?=htmlspecialchars($au['username'])?></td>
                            <td><span class="badge bg-success rounded-pill px-3 py-1">Active</span></td>
                            <td>
                                <a href="index.php?view_user=<?=urlencode($au['username'])?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="bi bi-folder2-open me-1"></i> View Vault
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="3" class="text-center py-4 text-muted">No active users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Quick Action / Export Panel -->
    <div class="vault-card p-4 d-flex justify-content-between align-items-center bg-white">
        <div>
            <h6 class="fw-bold mb-1 text-dark"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Admin Actions</h6>
            <p class="text-muted small mb-0">Need to broadcast a message or manage quick system settings? Access shortcuts instantly.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="users.php" class="btn btn-outline-primary btn-sm px-3 rounded-pill"><i class="bi bi-person-gear me-1"></i> Manage Users</a>
            <a href="admin_inquiries.php" class="btn btn-outline-dark btn-sm px-3 rounded-pill"><i class="bi bi-chat-left-text me-1"></i> View Inquiries</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>