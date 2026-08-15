<?php
require_once __DIR__ . '/../config/db.php';

if (!isAuthenticated()) {
    header("Location: ../index.php");
    exit;
}

if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit;
}

header("Location: dashboard.php");
exit;
