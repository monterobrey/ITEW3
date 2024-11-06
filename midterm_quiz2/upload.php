<?php
$conn = new mysqli('localhost', 'root', '', 'FileUploads');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];

        // Validate file size (2MB limit)
        if ($file['size'] > 2 * 1024 * 1024) {
            header("Location: index.php?error=" . urlencode("File size exceeds the 2MB limit."));
            exit;
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            header("Location: index.php?error=" . urlencode("Invalid file type. Only JPG, PNG, and PDF files are allowed."));
            exit;
        }

        // Sanitize file name
        $fileName = basename($file['name']);
        $fileName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $fileName);

        // Generate unique file path
        $destination = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            // Store file data in the database
            $stmt = $conn->prepare("INSERT INTO uploaded_files (file_name, file_path) VALUES (?, ?)");
            $stmt->bind_param("ss", $fileName, $destination);
            $stmt->execute();
            $stmt->close();

            echo "File uploaded successfully!";
        } else {
            header("Location: index.php?error=" . urlencode("Error: There was an error uploading your file."));
            exit;
        }
    } else {
        header("Location: index.php?error=" . urlencode("Error: No file uploaded or file upload failed."));
        exit;
    }

    header("Location: index.php");
    exit;
} else {
    echo "Invalid request.";
}

$conn->close();
?>
