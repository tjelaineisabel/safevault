<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user'] === 'jelaine123') {
    header("Location: login.php");
    exit();
}

$currentUser = $_SESSION['user'];
$message = "";

// --- 1. DYNAMIC CATEGORY FETCHING ---
// Kinukuha ang lahat ng tables sa db maliban sa users at inquiries
$tableRes = $conn->query("SHOW TABLES");
$checkCats = [];
$dbname_var = "Tables_in_" . $dbname; 
while($tRow = $tableRes->fetch_assoc()) {
    $tableName = $tRow[$dbname_var];
    if ($tableName != 'users' && $tableName != 'inquiries') {
        $checkCats[] = $tableName;
    }
}

$currentCategory = isset($_GET['cat']) && in_array($_GET['cat'], $checkCats) ? mysqli_real_escape_string($conn, $_GET['cat']) : ($checkCats[0] ?? 'personal');
$viewDeleted = isset($_GET['view']) && $_GET['view'] === 'trash';

// --- 2. LOGIC FOR CREATING NEW FOLDER (CATEGORY) ---
if (isset($_POST['create_folder'])) {
    $folderName = preg_replace('/[^a-z0-9_]/', '', strtolower($_POST['folder_name']));
    if (!empty($folderName)) {
        $createSql = "CREATE TABLE IF NOT EXISTS `$folderName` (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50),
            file_name VARCHAR(255),
            expiry_date DATE,
            is_deleted TINYINT(1) DEFAULT 0
        )";
        if ($conn->query($createSql)) {
            header("Location: user_dashboard.php?cat=$folderName&msg=folder_created");
            exit();
        }
    }
}

// --- Status Messages ---
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'deleted') $message = "<div class='alert alert-warning'>File moved to trash.</div>";
    if ($_GET['msg'] == 'recovered') $message = "<div class='alert alert-success'>File recovered successfully!</div>";
    if ($_GET['msg'] == 'folder_created') $message = "<div class='alert alert-success'>New category folder created!</div>";
}

// --- Logic for Delete/Recover ---
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $conn->query("UPDATE `$currentCategory` SET is_deleted = 1 WHERE id = $id AND username = '$currentUser'");
    header("Location: user_dashboard.php?cat=$currentCategory&msg=deleted"); exit();
}

if (isset($_GET['recover_id'])) {
    $id = (int)$_GET['recover_id'];
    $conn->query("UPDATE `$currentCategory` SET is_deleted = 0 WHERE id = $id AND username = '$currentUser'");
    header("Location: user_dashboard.php?cat=$currentCategory&view=trash&msg=recovered"); exit();
}

// --- Logic for Creating New File ---
if (isset($_POST['create_file'])) {
    $fileName = mysqli_real_escape_string($conn, $_POST['file_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']); 
    $expiryDate = $_POST['expiry_date'];
    $conn->query("INSERT INTO `$category` (username, file_name, expiry_date, is_deleted) VALUES ('$currentUser', '$fileName', '$expiryDate', 0)");
    $message = "<div class='alert alert-success'>File successfully vaulted!</div>";
}

// --- Logic for Inquiry (Message Admin) ---
if (isset($_POST['send_inquiry'])) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $msg_body = mysqli_real_escape_string($conn, $_POST['message_body']);
    $conn->query("INSERT INTO inquiries (username, subject, message, status, date_sent) VALUES ('$currentUser', '$subject', '$msg_body', 'unread', NOW())");
    $message = "<div class='alert alert-success'>Inquiry sent to admin!</div>";
}
$userInquiries = $conn->query("SELECT * FROM inquiries WHERE username = '$currentUser' ORDER BY date_sent DESC");

// --- Notification Logic ---
$notifications = [];
$today = new DateTime();
foreach ($checkCats as $cat) {
    $nResult = $conn->query("SELECT file_name, expiry_date FROM `$cat` WHERE username = '$currentUser' AND is_deleted = 0");
    if ($nResult) {
        while ($nRow = $nResult->fetch_assoc()) {
            if (!empty($nRow['expiry_date']) && $nRow['expiry_date'] != '0000-00-00') {
                $diff = (int)$today->diff(new DateTime($nRow['expiry_date']))->format("%r%a");
                if ($diff >= 0 && $diff <= 7) $notifications[] = "<strong>{$nRow['file_name']}</strong> expires in $diff day(s)!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Safe-Vault | Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .user-nav { background-color: #0d2a4a; color: white; }
        .vault-card { border: none; border-radius: 15px; background: white; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .nav-pills .nav-link { color: #0d2a4a; font-weight: 500; border-radius: 10px; margin-right: 5px; }
        .nav-pills .nav-link.active { background-color: #0d2a4a; color: white; }
        .nav-icon { color: white; font-size: 1.2rem; cursor: pointer; position: relative; }
        .inquiry-bubble { background: #f8f9fa; border-radius: 12px; padding: 12px; margin-bottom: 15px; border-left: 4px solid #dee2e6; }
        .admin-reply-box { background: #e7f3ff; border-radius: 10px; padding: 10px; margin-top: 10px; border-left: 4px solid #0d6efd; font-size: 0.85rem; }
    </style>
</head>
<body>

<nav class="navbar user-nav shadow-sm mb-4">
    <div class="container">
        <span class="navbar-brand h1 text-white fw-bold mb-0">Safe-Vault</span>
        <div class="d-flex align-items-center">
            <i class="bi bi-chat-dots-fill nav-icon me-4" title="Message Admin" data-bs-toggle="modal" data-bs-target="#messageAdminModal"></i>
            
            <div class="dropdown me-4">
                <div class="nav-icon" data-bs-toggle="dropdown">
                    <i class="bi bi-bell-fill"></i>
                    <?php if (count($notifications) > 0): ?><span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle" style="font-size: 0.6rem;"><?=count($notifications)?></span><?php endif; ?>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="width: 280px;">
                    <h6 class="dropdown-header">Alerts</h6>
                    <?php foreach ($notifications as $note) echo "<li><div class='dropdown-item small text-wrap border-bottom'>$note</div></li>"; ?>
                    <?= empty($notifications) ? "<li><div class='dropdown-item small text-center text-muted'>No alerts</div></li>" : "" ?>
                </ul>
            </div>

            <a href="user_dashboard.php?cat=<?=$currentCategory?>&view=trash" class="text-white me-4" title="Trash Bin"><i class="bi bi-trash3-fill"></i></a>
            
            <div class="d-flex align-items-center border-start ps-4">
                <span class="me-3 small d-none d-md-inline">Welcome, <strong><?= htmlspecialchars($currentUser) ?></strong>!</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Sign Out</a>
            </div>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <?=$message?>

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h3 class="fw-bold mb-0"><?= $viewDeleted ? 'Trash Bin' : 'My Document Vault' ?></h3>
            <p class="text-muted small">Current Category: <strong><?=ucfirst($currentCategory)?></strong></p>
        </div>
        <div class="d-flex gap-2">
            <?php if(!$viewDeleted): ?>
            <button class="btn btn-outline-primary rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFolderModal">
                <i class="bi bi-folder-plus"></i> New Folder
            </button>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addFileModal">
                <i class="bi bi-plus-lg me-2"></i>New File
            </button>
            <?php else: ?>
            <a href="user_dashboard.php?cat=<?=$currentCategory?>" class="btn btn-secondary rounded-pill px-4">Back to Vault</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills overflow-auto flex-nowrap pb-2">
                <?php foreach($checkCats as $c): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentCategory == $c) ? 'active shadow-sm' : '' ?>" 
                           href="user_dashboard.php?cat=<?=$c?><?=$viewDeleted ? '&view=trash' : ''?>">
                           <?= ucfirst($c) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <div class="vault-card p-4">
        <table class="table align-middle">
            <thead>
                <tr class="text-muted small"><th>FILE NAME</th><th>EXPIRY</th><th>STATUS</th><th class="text-center">ACTION</th></tr>
            </thead>
            <tbody>
                <?php
                $statusFilter = $viewDeleted ? 1 : 0;
                $res = $conn->query("SELECT * FROM `$currentCategory` WHERE username = '$currentUser' AND is_deleted = $statusFilter");
                if ($res && $res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $diff = (int)$today->diff(new DateTime($row['expiry_date']))->format("%r%a");
                        $badge = ($diff < 0) ? "bg-secondary opacity-75'>Expired" : (($diff <= 7) ? "bg-danger'>Action Required" : "bg-success'>Secure");
                        echo "<tr>
                                <td class='fw-bold'><i class='bi bi-file-earmark-lock2 me-2 text-primary'></i>".htmlspecialchars($row['file_name'])."</td>
                                <td class='text-muted small'>{$row['expiry_date']}</td>
                                <td><span class='badge $badge</span></td>
                                <td class='text-center'>";
                        if ($viewDeleted) {
                            echo "<a href='user_dashboard.php?cat=$currentCategory&recover_id={$row['id']}' class='btn btn-sm btn-success rounded-pill px-3'>Recover</a>";
                        } else {
                            echo "<a href='user_dashboard.php?cat=$currentCategory&delete_id={$row['id']}' onclick=\"return confirm('Move to trash?')\" class='text-danger fs-5'><i class='bi bi-trash3'></i></a>";
                        }
                        echo "</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center py-5 text-muted small'>No documents in this folder.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="messageAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Admin Communication</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0">
                    <div class="col-md-6 border-end p-3" style="max-height: 450px; overflow-y: auto;">
                        <h6 class="fw-bold small border-bottom pb-2">MESSAGE HISTORY</h6>
                        <?php if ($userInquiries && $userInquiries->num_rows > 0): 
                            while($msg = $userInquiries->fetch_assoc()): ?>
                            <div class="inquiry-bubble small">
                                <strong><?= htmlspecialchars($msg['subject']) ?></strong><br>
                                <?= htmlspecialchars($msg['message']) ?>
                                <?php if(!empty($msg['admin_reply'])): ?>
                                    <div class="admin-reply-box"><strong>Reply:</strong> <?= htmlspecialchars($msg['admin_reply']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; else: ?>
                            <p class='text-center text-muted mt-5 small'>No inquiry history.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 p-4">
                        <form method="POST">
                            <div class="mb-3"><label class="small fw-bold">Subject</label><input type="text" name="subject" class="form-control" required></div>
                            <div class="mb-3"><label class="small fw-bold">Message</label><textarea name="message_body" class="form-control" rows="4" required></textarea></div>
                            <button type="submit" name="send_inquiry" class="btn btn-primary w-100 rounded-pill">Send Inquiry</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addFileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Vault New Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3"><label class="fw-bold small">File Name</label><input type="text" name="file_name" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="fw-bold small">Category</label>
                        <select name="category" class="form-select">
                            <?php foreach($checkCats as $c): ?>
                                <option value="<?=$c?>" <?=($currentCategory == $c)?'selected':''?>><?=ucfirst($c)?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="fw-bold small">Expiry Date</label><input type="date" name="expiry_date" class="form-control" required></div>
                </div>
                <div class="modal-footer"><button type="submit" name="create_file" class="btn btn-primary w-100">Secure Document</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="addFolderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">New Category Folder</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form method="POST">
                <div class="modal-body"><div class="mb-3"><label class="fw-bold small">Category Name</label><input type="text" name="folder_name" class="form-control" placeholder="e.g. finance" required></div></div>
                <div class="modal-footer"><button type="submit" name="create_folder" class="btn btn-primary w-100">Create Category</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>