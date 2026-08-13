<?php
require_once __DIR__ . '/../includes/bootstrap.php';

logoutCurrentUser($pdo);

header('Location: ' . BASE_URL . '/public/login.php');
exit;
