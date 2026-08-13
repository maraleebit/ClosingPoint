<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$admin = requireRole(['admin']);
$pageTitle = 'Modifier l\'utilisateur';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute([':id' => $id]);
$targetUser = $stmt->fetch();
if (!$targetUser) { http_response_code(404); die('Utilisateur introuvable.'); }

$errors = [];
$data = $targetUser;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['role'] = $_POST['role'] ?? 'client';
    $data['phone'] = trim($_POST['phone'] ?? '');
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $newPassword = $_POST['password'] ?? '';

    if ($data['full_name'] === '') $errors[] = 'Le nom complet est obligatoire.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';
    if ($newPassword !== '' && strlen($newPassword) < 8) $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';

    if (!$errors) {
        if ($newPassword !== '') {
            $pdo->prepare('UPDATE users SET full_name=:n, email=:e, role=:r, phone=:ph, is_active=:a, password_hash=:pw WHERE id=:id')
                ->execute([':n'=>$data['full_name'], ':e'=>$data['email'], ':r'=>$data['role'], ':ph'=>$data['phone'] ?: null, ':a'=>$data['is_active'], ':pw'=>password_hash($newPassword, PASSWORD_DEFAULT), ':id'=>$id]);
        } else {
            $pdo->prepare('UPDATE users SET full_name=:n, email=:e, role=:r, phone=:ph, is_active=:a WHERE id=:id')
                ->execute([':n'=>$data['full_name'], ':e'=>$data['email'], ':r'=>$data['role'], ':ph'=>$data['phone'] ?: null, ':a'=>$data['is_active'], ':id'=>$id]);
        }
        logAudit($pdo, (int)$admin['id'], 'modification_utilisateur', 'users', $id, $data['email']);
        header('Location: list.php?updated=1');
        exit;
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
  <div class="col-md-4 d-flex align-items-end">
    <div class="form-check mb-2"><input type="checkbox" name="is_active" class="form-check-input" id="ia" <?= $data['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="ia">Compte actif</label></div>
  </div>
  <div class="col-md-6"><label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label><input type="password" name="password" class="form-control" minlength="8"></div>
  <div class="col-12"><button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Enregistrer</button> <a href="list.php" class="btn btn-outline-secondary">Annuler</a></div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
