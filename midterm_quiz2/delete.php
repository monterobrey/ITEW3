<?php
$conn = new mysqli('localhost', 'root', '', 'FileUploads');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_id'])) {
    $fileId = intval($_POST['file_id']);

    // Fetch file path
    $stmt = $conn->prepare("SELECT file_path FROM uploaded_files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($filePath);
    $stmt->fetch();
    $stmt->close();

    // Delete file from server and database
    if ($filePath && file_exists($filePath)) {
        unlink($filePath);
    }

    $stmt = $conn->prepare("DELETE FROM uploaded_files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->close();
}

$conn->close();

// Redirect back to index.php
header("Location: index.php");
exit;
