<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin']);
$pageTitle = 'Nouvel utilisateur';

$errors = [];
$data = ['full_name' => '', 'email' => '', 'role' => 'client', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $data = [
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email'     => trim($_POST['email'] ?? ''),
        'role'      => $_POST['role'] ?? 'client',
        'phone'     => trim($_POST['phone'] ?? ''),
    ];
    $password = $_POST['password'] ?? '';

    if ($data['full_name'] === '') $errors[] = 'Le nom complet est obligatoire.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';
    if (!in_array($data['role'], ['admin','conseiller','client'], true)) $errors[] = 'Rôle invalide.';
    if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (:n, :e, :p, :r, :ph)');
            $stmt->execute([
                ':n' => $data['full_name'], ':e' => $data['email'],
                ':p' => password_hash($password, PASSWORD_DEFAULT), // hachage bcrypt obligatoire
                ':r' => $data['role'], ':ph' => $data['phone'] ?: null,
            ]);
            logAudit($pdo, (int)$user['id'], 'creation_utilisateur', 'users', (int)$pdo->lastInsertId(), $data['email']);
            header('Location: list.php?created=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = ($e->getCode() === '23000') ? 'Cette adresse email est déjà utilisée.' : 'Erreur technique.';
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="row g-3">
  <?php csrf_field(); ?>
  <div class="col-md-6"><label class="form-label">Nom complet *</label><input type="text" name="full_name" class="form-control" required value="<?= e($data['full_name']) ?>"></div>
  <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="<?= e($data['email']) ?>"></div>
  <div class="col-md-4"><label class="form-label">Rôle *</label>
    <select name="role" class="form-select" required>
      <option value="admin" <?= $data['role']==='admin'?'selected':'' ?>>Administrateur</option>
      <option value="conseiller" <?= $data['role']==='conseiller'?'selected':'' ?>>Conseiller M&amp;A</option>
      <option value="client" <?= $data['role']==='client'?'selected':'' ?>>Client / Investisseur</option>
    </select>
  </div>
  <div class="col-md-4"><label class="form-label">Téléphone</label><input type="text" name="phone" class="form-control" value="<?= e($data['phone']) ?>"></div>
  <div class="col-md-4"><label class="form-label">Mot de passe *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
  <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Créer</button> <a href="list.php" class="btn btn-outline-secondary">Annuler</a></div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
