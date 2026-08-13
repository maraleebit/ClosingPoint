<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (currentUser()) {
    header('Location: ' . BASE_URL . '/public/dashboard.php');
    exit;
}

$error = null;

// Protection anti brute-force simplifiée (verrouillage temporaire après 5 échecs)
$_SESSION['login_attempts'] = $_SESSION['login_attempts'] ?? 0;
$locked = isset($_SESSION['login_lock_until']) && time() < $_SESSION['login_lock_until'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    csrf_verify($_POST['csrf_token'] ?? null);

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez renseigner votre email et votre mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $user = attemptLogin($pdo, $email, $password);
        if ($user) {
            unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
            header('Location: ' . BASE_URL . '/public/dashboard.php');
            exit;
        }
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] >= 5) {
            $_SESSION['login_lock_until'] = time() + 60;
            $error = 'Trop de tentatives échouées. Réessayez dans 60 secondes.';
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
} elseif ($locked) {
    $error = 'Compte temporairement bloqué suite à plusieurs échecs. Réessayez dans quelques instants.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — <?= e(SITE_NAME) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= e(BASE_URL) ?>/public/assets/css/style.css">
</head>
<body class="login-page d-flex align-items-center justify-content-center">
  <div class="card shadow login-card">
    <div class="card-body p-4">
      <div class="text-center mb-4">
        <i class="bi bi-briefcase-fill fs-1 text-primary"></i>
        <h1 class="h4 mt-2 mb-0"><?= e(SITE_NAME) ?></h1>
        <p class="text-muted small">Pilotez vos opérations de fusion-acquisition, de la valorisation au closing.</p>
      </div>

      <?php if (isset($_GET['expired'])): ?>
        <div class="alert alert-warning">Votre session a expiré après 20 minutes d'inactivité. Veuillez vous reconnecter.</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate id="loginForm">
        <?php csrf_field(); ?>
        <div class="mb-3">
          <label class="form-label">Adresse email</label>
          <input type="email" name="email" class="form-control" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Mot de passe</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary w-100" <?= $locked ? 'disabled' : '' ?>>
          <i class="bi bi-box-arrow-in-right"></i> Se connecter
        </button>
      </form>
    </div>
  </div>
</body>
</html>
