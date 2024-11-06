<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Upload</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
        }
        .table-container {
            margin-top: 30px;
        }
    </style>
</head>
<body class="container mt-5">

    <h2 class="mb-4 text-center">File Upload & Management</h2>
    
    <!-- File Upload Form -->
    <form action="upload.php" method="post" enctype="multipart/form-data" class="mb-4 p-4 border rounded bg-white shadow-sm">
        <div class="form-group">
            <label for="fileUpload">Choose File:</label>
            <small class="form-text text-muted">Accepted file types: JPG, PNG, PDF. Maximum file size: 2MB.</small>
            <input type="file" name="file" id="fileUpload" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Upload</button>
    </form>

    <!-- Uploaded Files Table -->
    <div class="table-container">
        <h3 class="text-center">Uploaded Files</h3>
        <table class="table table-striped table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>File Name</th>
                    <th>Upload Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $conn = new mysqli('localhost', 'root', '', 'FileUploads');
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                $result = $conn->query("SELECT * FROM uploaded_files ORDER BY upload_date DESC");
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row['file_name']) . "</td>
                                <td>" . htmlspecialchars($row['upload_date']) . "</td>
                                <td>
                                    <a href='" . htmlspecialchars($row['file_path']) . "' download class='btn btn-secondary btn-sm'>Download</a>
                                    <form action='delete.php' method='post' style='display:inline;'>
                                        <input type='hidden' name='file_id' value='" . $row['id'] . "'>
                                        <button type='submit' class='btn btn-danger btn-sm'>Delete</button>
                                    </form>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No files uploaded yet.</td></tr>";
                }

                $conn->close();
                ?>
            </tbody>
        </table>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" role="dialog" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="errorModalLabel">Upload Error</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php
                    if (isset($_GET['error'])) {
                        echo htmlspecialchars($_GET['error']);
                    }
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show modal if error exists in the URL
        <?php if (isset($_GET['error'])): ?>
            $(document).ready(function() {
                $('#errorModal').modal('show');
            });
        <?php endif; ?>
    </script>
</body>
</html>
