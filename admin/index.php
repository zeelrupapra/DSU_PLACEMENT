<?php
require_once __DIR__ . '/../config/db.php';

if (!isAuthenticated()) {
    header("Location: ../index.php");
    exit;
}

if (isStudent()) {
    header("Location: ../student/dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
