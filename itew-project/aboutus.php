<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (isset($_COOKIE["user"])) {
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $_COOKIE["user"];
    } else {
        header("location: index.php");
        exit;
    }
}
setcookie("user", $_SESSION["username"], time() + (86400 * 30), "/");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supreme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <script src="script.js"></script> 
    <style>
        .team-member {
            margin-bottom: 30px;
        }
        .card-img-top {
            width: 100%;
            height: 400px; /* Adjust this value to control the image height */
            object-fit: cover;
            aspect-ratio: 1 / 1; /* Ensures the image is 1:1 ratio */
        }
    </style>
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
            <!-- Display the username as a clickable link -->
            <span class="navbar-text" style="margin-right: 20px;">
                <a href="profile.php" style="color: #CE1126; text-decoration: none;">
                    <?php echo htmlspecialchars($_SESSION["username"]); ?>
                </a>
            </span>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    <h1 style="color: #980d1c" class="text-center mb-5">Meet The Team</h1>

    <div class="row">
        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="Images/nataya.png" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Your Name</h5>
                    <p class="card-text">CEO & Founder</p>
                    <p class="card-text">Brief description about yourself.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="Images/niko.jpg" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Member 2 Name</h5>
                    <p class="card-text">Role</p>
                    <p class="card-text">Brief description about member 2.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="Images/monter.jpg" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Member 3 Name</h5>
                    <p class="card-text">Role</p>
                    <p class="card-text">Brief description about member 3.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="Images/niala.jpg" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Member 4 Name</h5>
                    <p class="card-text">Role</p>
                    <p class="card-text">Brief description about member 4.</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="Images/NUGUID.png" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Member 5 Name</h5>
                    <p class="card-text">Role</p>
                    <p class="card-text">Brief description about member 5.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 team-member">
            <div class="card">
                <img src="https://via.placeholder.com/300" class="card-img-top" alt="Member Image">
                <div class="card-body text-center">
                    <h5 class="card-title">Member 6 Name</h5>
                    <p class="card-text">Role</p>
                    <p class="card-text">Brief description about member 5.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
