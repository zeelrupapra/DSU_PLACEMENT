<?php
require_once __DIR__ . '/../config/db.php';
requireRecruiter();
header("Location: dashboard.php");
exit;
