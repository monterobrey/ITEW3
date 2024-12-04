<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'db_config.php';

// Fetch the logged-in user's email from the session
$email = $_SESSION["email"];

// Initialize variables to hold user data
$firstname = $middlename = $lastname = $age = $homeaddress = "";

// Fetch user profile data
$sql = "SELECT firstname, middlename, lastname, age, homeaddress FROM user_profile WHERE email = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($firstname, $middlename, $lastname, $age, $homeaddress);
        $stmt->fetch();
    }
    $stmt->close();
} else {
    echo "<div class='alert alert-danger'>Error fetching profile: " . $conn->error . "</div>";
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get updated values from the form
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $age = trim($_POST['age']);
    $homeaddress = trim($_POST['homeaddress']);

    // Update user profile in the database
    $update_sql = "UPDATE user_profile SET firstname = ?, middlename = ?, lastname = ?, age = ?, homeaddress = ? WHERE email = ?";
    if ($update_stmt = $conn->prepare($update_sql)) {
        $update_stmt->bind_param("sssiss", $firstname, $middlename, $lastname, $age, $homeaddress, $email);

        if ($update_stmt->execute()) {
            echo "<div class='alert alert-success'>Profile updated successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error updating profile: " . $conn->error . "</div>";
        }
        $update_stmt->close();
    } else {
        echo "<div class='alert alert-danger'>Error preparing update statement: " . $conn->error . "</div>";
    }

    

}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <img style="width: 100px; cursor: pointer;" src="Images/logo.jpg" class="logo">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link active" href="dashboard.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a style="color: #CE1126;" class="nav-link active" href="aboutus.php">About Us</a>
                    </li>
                </ul>
                <span class="navbar-text" style="margin-right: 20px;">
                    <a href="profile.php" style="color: #CE1126; text-decoration: none;">
                        <?php echo htmlspecialchars($_SESSION["username"]); ?>
                    </a>
                </span>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <h2>User Profile</h2>
        <ul class="list-group">
            <li class="list-group-item"><strong>First Name:</strong> <?php echo htmlspecialchars($firstname); ?></li>
            <li class="list-group-item"><strong>Middle Name:</strong> <?php echo htmlspecialchars($middlename); ?></li>
            <li class="list-group-item"><strong>Last Name:</strong> <?php echo htmlspecialchars($lastname); ?></li>
            <li class="list-group-item"><strong>Age:</strong> <?php echo htmlspecialchars($age); ?></li>
            <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></li>
            <li class="list-group-item"><strong>Home Address:</strong> <?php echo htmlspecialchars($homeaddress); ?></li>
        </ul><br>
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile</button>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Edit profile form -->
                    <form id="editProfileForm" method="POST" action="">
                        <div class="mb-3">
                            <label for="firstname" class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" id="firstname" value="<?php echo htmlspecialchars($firstname); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="middlename" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" name="middlename" id="middlename" value="<?php echo htmlspecialchars($middlename); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastname" class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname" id="lastname" value="<?php echo htmlspecialchars($lastname); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" name="age" id="age" value="<?php echo htmlspecialchars($age); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="homeaddress" class="form-label">Home Address</label>
                            <input type="text" class="form-control" name="homeaddress" id="homeaddress" value="<?php echo htmlspecialchars($homeaddress); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
