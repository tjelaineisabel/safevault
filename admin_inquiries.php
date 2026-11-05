<?php
include 'db.php';
session_start();

// 1. Security Check
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'jelaine123') {
    header("Location: login.php"); exit();
}

// 2. Action Handlers
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM inquiries WHERE id = $id");
    header("Location: admin_inquiries.php"); exit();
}

if (isset($_POST['send_reply'])) {
    $id = (int)$_POST['inquiry_id'];
    $msg = mysqli_real_escape_string($conn, $_POST['reply_message']);
    $conn->query("UPDATE inquiries SET status = 'read', admin_reply = '$msg' WHERE id = $id");
    header("Location: admin_inquiries.php"); exit();
}

// 3. Counter Logic for Sidebar
$unreadInquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetch_row()[0] ?? 0;
$pendingCount = $conn->query("SELECT COUNT(*) FROM users WHERE status = 'deactivated'")->fetch_row()[0] ?? 0;

$allInquiries = $conn->query("SELECT * FROM inquiries ORDER BY status DESC, date_sent DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Safe-Vault | Inquiries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { background: #f4f7f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: #0d2a4a; min-height: 100vh; position: fixed; width: 16.6%; color: white; z-index: 100; }
        .sidebar a { color: #adb5bd; padding: 12px 25px; display: block; text-decoration: none; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: #1a3e61; color: white; border-left: 4px solid #3498db; }
        .main-content { margin-left: 16.6%; padding: 40px; }
        .vault-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: none; overflow: hidden; }
        .unread-row { border-left: 4px solid #3498db; background: #f8fbff; }
        .reply-box { background: #f8f9fa; border-radius: 10px; padding: 12px; border-left: 3px solid #6c757d; font-size: 0.9rem; }
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

    <a href="admin_inquiries.php" class="active d-flex justify-content-between align-items-center">
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
        <div><h2 class="fw-bold mb-0">User Inquiries</h2><p class="text-muted small">Respond to user feedback and concerns.</p></div>
        <div class="bg-white p-2 px-3 rounded-pill shadow-sm border small">Welcome, <strong>jelaine123</strong></div>
    </div>

    <div class="vault-card">
        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-inbox-fill me-2 text-primary"></i>Inbox</h5>
            <span class="badge bg-dark rounded-pill"><?=$allInquiries->num_rows?> Total</span>
        </div>

        <?php if ($allInquiries->num_rows > 0): while($row = $allInquiries->fetch_assoc()): ?>
            <div class="p-4 border-bottom <?=($row['status'] == 'unread') ? 'unread-row' : ''?>">
                <div class="row align-items-start">
                    <div class="col-md-9">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-bold text-dark"><?=htmlspecialchars($row['username'])?></span>
                            <span class="text-muted small">• <?=date('M d, Y', strtotime($row['date_sent']))?></span>
                            <?php if($row['status'] == 'unread'): ?><span class="badge bg-primary rounded-pill small">NEW</span><?php endif; ?>
                        </div>
                        <h6 class="fw-bold text-primary"><?=htmlspecialchars($row['subject'])?></h6>
                        <p class="text-secondary small mb-3"><?=nl2br(htmlspecialchars($row['message']))?></p>
                        
                        <?php if($row['admin_reply']): ?>
                            <div class="reply-box mt-2">
                                <strong class="text-dark small"><i class="bi bi-reply-fill"></i> Your Reply:</strong><br>
                                <?=htmlspecialchars($row['admin_reply'])?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 text-end">
                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#replyModal" 
                                data-id="<?=$row['id']?>" data-user="<?=$row['username']?>">Reply</button>
                        <a href="admin_inquiries.php?delete=<?=$row['id']?>" class="btn btn-sm btn-outline-danger rounded-pill ms-1" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </div>
                </div>
            </div>
        <?php endwhile; else: ?>
            <div class="text-center py-5 text-muted">No messages found.</div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="admin_inquiries.php" method="POST" class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold">Reply to <span id="replyUser" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="inquiry_id" id="modalInquiryId">
                <textarea name="reply_message" class="form-control rounded-3" rows="4" placeholder="Type your response..." required></textarea>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="send_reply" class="btn btn-primary rounded-pill px-4">Send Reply</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const replyModal = document.getElementById('replyModal');
    replyModal.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('modalInquiryId').value = btn.getAttribute('data-id');
        document.getElementById('replyUser').textContent = btn.getAttribute('data-user');
    });
</script>
</body>
</html>