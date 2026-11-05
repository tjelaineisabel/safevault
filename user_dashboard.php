<?php
session_start();
include('db.php');

// Siguraduhing naka-login ang user
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];

// Handle File Upload kung may ipinasa
$upload_msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_file'])) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = basename($_FILES["fileToUpload"]["name"]);
    $uniqueFileName = time() . "_" . $fileName;
    $targetFilePath = $targetDir . $uniqueFileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);

    $allowedTypes = array('jpg', 'png', 'jpeg', 'pdf', 'docx', 'txt');
    if (in_array(strtolower($fileType), $allowedTypes)) {
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $targetFilePath)) {
            try {
                // I-save sa database gamit ang PDO at is_deleted = 0
                $stmt = $conn->prepare("INSERT INTO files (file_name, file_path, is_deleted) VALUES (:file_name, :file_path, 0)");
                $stmt->execute([
                    ':file_name' => $fileName,
                    ':file_path' => $targetFilePath
                ]);
                $upload_msg = "Matagumpay na na-upload ang file!";
            } catch (PDOException $e) {
                $upload_msg = "Database Error: " . $e->getMessage();
            }
        } else {
            $upload_msg = "May error sa paglipat ng file.";
        }
    } else {
        $upload_msg = "Hindi pinapayagan ang ganitong uri ng file.";
    }
}

// Kunin ang mga active files gamit ang PDO (hindi kasama ang mga na-soft delete)
try {
    $stmt = $conn->prepare("SELECT * FROM files WHERE is_deleted = 0 ORDER BY id DESC");
    $stmt->execute();
    $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $files = [];
}
$totalFiles = count($files);
?>
<!DOCTYPE html>
<html lang="tl">
<head>
    <meta charset="UTF-8">
    <title>Safe-Vault - User Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; }
        .logout-btn { float: right; background: #d9534f; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; }
        .alert { background: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        form { margin-top: 20px; background: #fafafa; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        .badge { background: #5bc0de; color: white; padding: 3px 8px; border-radius: 10px; font-size: 14px; }
        .search-box { width: 100%; padding: 8px; margin-top: 10px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>

<div class="container">
    <a href="logout.php" class="logout-btn">Mag-log out</a>
    <h2>Maligayang Pagdating, <?php echo htmlspecialchars($username); ?>!</h2>
    <p>Ito ang iyong Safe-Vault User Dashboard.</p>

    <hr>

    <!-- Bagong Feature: File Upload Section -->
    <h3>Mag-upload ng Bagong File</h3>
    <?php if (!empty($upload_msg)): ?>
        <div class="alert"><?php echo htmlspecialchars($upload_msg); ?></div>
    <?php endif; ?>
    <form action="" method="POST" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" required style="margin-bottom: 10px;"><br>
        <button type="submit" name="upload_file" style="background: #5bc0de; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">I-upload ang File</button>
    </form>

    <hr>

    <!-- Listahan ng mga Files na may Counter at Search Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Mga Naka-store na Files <span class="badge"><?php echo $totalFiles; ?></span></h3>
    </div>

    <!-- Bagong Feature: Live Search Input -->
    <input type="text" id="searchInput" class="search-box" placeholder="Maghanap ng file sa listahan..." onkeyup="searchFiles()">

    <table id="fileTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Pangalan ng File</th>
                <th>Aksyon</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($totalFiles > 0): ?>
                <?php foreach ($files as $file): ?>
                    <tr>
                        <td><?php echo $file['id']; ?></td>
                        <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank">Tingnan / I-download</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align: center;">Walang makitang mga file sa vault.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- JavaScript para sa Live Search Feature -->
<script>
function searchFiles() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toLowerCase();
    let table = document.getElementById('fileTable');
    let tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) {
        let td = tr[i].getElementsByTagName('td')[1];
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>

</body>
</html>