<?php
session_start();
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    if (isset($_COOKIE["user"])) {
        // Restore session from cookie
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $_COOKIE["user"];
    } else {
        header("location: index.php");
        exit;
    }
}
setcookie("user", $_SESSION["username"], time() + (86400 * 30), "/"); // Renew for another 30 days

// Check if the "Remember Me" cookies are set 
if (isset($_COOKIE['username']) && isset($_COOKIE['password'])) { 
    echo "Welcome, " . $_SESSION['username'] . "! You have been remembered through cookies.<br>"; 
} else { 
    echo "Welcome, " . $_SESSION['username'] . "!<br>"; 
}
?>

<a href="logout.php">Logout</a> 
