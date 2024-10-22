<?php 
require 'db_config.php';
session_start();

// Check if the user is already logged in, if so, redirect to the dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

// Enable detailed error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $remember = isset($_POST['remember']); 

    // Check if email and password are empty
    if (empty($email) || empty($password)) {
        echo "Email and password cannot be empty.";
        exit;
    }

    // SQL query to fetch user details
    $sql = "SELECT id, username, password FROM users WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $stmt->store_result();

            // Check if a user was found
            if ($stmt->num_rows == 1) {
                $stmt->bind_result($id, $username, $hashed_password); 
                if ($stmt->fetch()) {
                    // Verify the password
                    if (password_verify($password, $hashed_password)) {
                        // Set session variables after successful login
                        $_SESSION["loggedin"] = true;
                        $_SESSION["id"] = $id;
                        $_SESSION["username"] = $username;
                        $_SESSION["user_id"] = $id;  // Store user_id in session

                        // If "Remember Me" is checked, store username, email, and password in cookies
                        if ($remember) {
                            setcookie("username", $username, time() + (86400 * 30), "/"); // 30 days
                            setcookie("email", $email, time() + (86400 * 30), "/"); // 30 days
                            setcookie("password", $password, time() + (86400 * 30), "/"); // 30 days (NOT RECOMMENDED)
                        }

                        // Redirect to dashboard
                        header("location: dashboard.php");
                        exit;
                    } else {
                        echo "Invalid email or password. <a href='index.php'>Go back</a>";
                    }
                }
            } else {
                echo "Invalid email or password. <a href='index.php'>Go back</a>";
            }
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
    $conn->close();
}

// Handle "Remember Me" functionality
if (isset($_COOKIE["user"])) {
    $_SESSION["loggedin"] = true;
    $_SESSION["username"] = $_COOKIE["user"];
    
    // Fetch the user_id based on the username
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_COOKIE["user"]);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $_SESSION["user_id"] = $user['id'];  // Store user_id in session when logged in via cookie
    }
    $stmt->close();
}
?>
