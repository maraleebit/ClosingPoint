<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (currentUser()) {
    header('Location: ' . BASE_URL . '/public/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/public/login.php');
}
exit;
