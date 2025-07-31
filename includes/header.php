<?php
// Start session early and securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load dependencies
require_once $_SERVER['DOCUMENT_ROOT'] . "/project-management/config/database.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/project-management/config/auth.php";
require_once $_SERVER['DOCUMENT_ROOT'] . "/project-management/includes/functions.php";

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isLoggedIn()) {
    header("Location: /project-management/auth/login.php");
    exit;
}

$current_user = $auth->getCurrentUser();

// Make sure no output or whitespace before this PHP tag ends

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Interior Project Management</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />

    <!-- Custom CSS -->
    <link href="/project-management/assets/css/custom.css" rel="stylesheet" />

    <!-- jQuery (Must load before any JS using $) -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold"
                href="/project-management/dashboard/<?= htmlspecialchars($current_user['role']) ?>">
                <i class="fas fa-building"></i> Interior Project Management
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i>
                            <span class="ms-2"><?= htmlspecialchars($current_user['name']) ?></span>
                            <span
                                class="badge bg-light text-dark ms-2"><?= ucfirst(htmlspecialchars($current_user['role'])) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/project-management/profile.php"><i
                                        class="fas fa-user-edit"></i> Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="/project-management/auth/logout.php"><i
                                        class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Begin main content container -->
    <div class="container-fluid">
        <div class="row">