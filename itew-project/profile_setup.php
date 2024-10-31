<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

require 'db_config.php';

// Initialize variables to hold user data
$firstname = $middlename = $lastname = $age = $email = $homeaddress = "";

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get new values from the form
    $firstname = trim($_POST['firstname']);
    $middlename = trim($_POST['middlename']);
    $lastname = trim($_POST['lastname']);
    $age = trim($_POST['age']);
    $email = trim($_POST['email']);
    $homeaddress = trim($_POST['homeaddress']);
    
    // First, get the user's id from the users table
    $user_id_query = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($user_id_query);
    $stmt->bind_param("s", $_SESSION["username"]);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user && isset($user['id'])) {
        $user_id = $user['id'];
        
        // Now insert the profile data in the database
        $sql = "INSERT INTO user_profile (id, firstname, middlename, lastname, age, email, homeaddress) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("isssiss", $user_id, $firstname, $middlename, $lastname, $age, $email, $homeaddress);
            
            if ($stmt->execute()) {
                // Successfully inserted; update session variables
                $_SESSION["firstname"] = $firstname;
                $_SESSION["middlename"] = $middlename;
                $_SESSION["lastname"] = $lastname;
                $_SESSION["age"] = $age;
                $_SESSION["email"] = $email;
                $_SESSION["homeaddress"] = $homeaddress;
                
                // Set a success message in the session
                $_SESSION["profile_setup_success"] = "Profile information successfully added!";
                
                header("location: profile_setup.php"); // Redirect to the same page to display the success message
                exit;
            } else {
                echo "<div class='alert alert-danger'>Error adding profile: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error: User ID not found.</div>";
    }
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Setup</title>
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
                    <a style="color: #CE1126;" class="nav-link active" aria-current="page" href="dashboard.php">Products</a>
                </li>
                <li class="nav-item">
                    <a style="color: #CE1126;" class="nav-link active" aria-current="page" href="aboutus.php">About Us</a>
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
    <?php
    // Display success message if it exists in the session
    if (isset($_SESSION["profile_setup_success"])) {
        echo "<div class='alert alert-success'>" . $_SESSION["profile_setup_success"] . "</div>";
        unset($_SESSION["profile_setup_success"]); // Remove the message from the session
    }
    ?>
    <h2>Profile Setup</h2>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="firstname" class="form-label">First Name</label>
            <input type="text" class="form-control" name="firstname" id="firstname" required>
        </div>
        <div class="mb-3">
            <label for="middlename" class="form-label">Middle Name</label>
            <input type="text" class="form-control" name="middlename" id="middlename" required>
        </div>
        <div class="mb-3">
            <label for="lastname" class="form-label">Last Name</label>
            <input type="text" class="form-control" name="lastname" id="lastname" required>
        </div>
        <div class="mb-3">
            <label for="age" class="form-label">Age</label>
            <input type="number" class="form-control" name="age" id="age" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="mb-3">
            <label for="homeaddress" class="form-label">Home Address</label>
            <input type="text" class="form-control" name="homeaddress" id="homeaddress" required>
        </div>
        <button type="submit" class="btn btn-danger">Submit</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
