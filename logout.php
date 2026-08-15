<?php
require_once __DIR__ . '/config/db.php';
session_unset();
session_destroy();
header("Location: index.php?success=" . urlencode("Logged out successfully."));
exit;
?>
