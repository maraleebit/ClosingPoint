<?php
/**
 * En-tête HTML commun à toutes les pages protégées.
 * Variables attendues (optionnelles) avant l'include : $pageTitle
 */
$user = currentUser();
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/public/assets/css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="<?= e(BASE_URL) ?>/public/dashboard.php">
      <i class="bi bi-briefcase-fill"></i> <?= e(SITE_NAME) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto">
        <?php if ($user): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle"></i> <?= e($user['full_name']) ?>
            <span class="badge text-bg-secondary ms-1"><?= e(roleLabel($user['role'])) ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text text-muted small"><?= e($user['email']) ?></span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="<?= e(BASE_URL) ?>/public/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
          </ul>
        </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container-fluid">
  <div class="row">
    <?php if ($user): ?><?php require __DIR__ . '/sidebar.php'; ?><?php endif; ?>
    <main class="<?= $user ? 'col-md-10 ms-sm-auto' : 'col-12' ?> px-md-4 py-4">
      <h1 class="h3 mb-4 border-bottom pb-2"><?= e($pageTitle) ?></h1>
