<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Nouvelle offre — ' . $project['nom_projet'];

$errors = [];
$data = ['type_offre' => 'offre_initiale', 'devise' => $project['devise']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $data = [
        'type_offre' => $_POST['type_offre'] ?? '',
        'montant'    => $_POST['montant'] ?? '',
        'devise'     => $_POST['devise'] ?? $project['devise'],
        'conditions' => trim($_POST['conditions'] ?? ''),
        'date_offre' => $_POST['date_offre'] ?? date('Y-m-d'),
    ];

    if (!in_array($data['type_offre'], ['offre_initiale','contre_offre','offre_finale'], true)) $errors[] = "Type d'offre invalide.";
    if ($data['montant'] === '' || !is_numeric($data['montant']) || (float)$data['montant'] <= 0) $errors[] = 'Le montant doit être un nombre positif.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO offers (project_id, type_offre, montant, devise, conditions, statut, emise_par, date_offre)
             VALUES (:p, :type, :montant, :devise, :cond, "proposee", :u, :date)'
        );
        $stmt->execute([
            ':p' => $projectId, ':type' => $data['type_offre'], ':montant' => $data['montant'], ':devise' => $data['devise'],
            ':cond' => $data['conditions'] ?: null, ':u' => $user['id'], ':date' => $data['date_offre'],
        ]);
        logAudit($pdo, (int)$user['id'], 'creation_offre', 'offers', (int)$pdo->lastInsertId());
        header('Location: list.php?project_id=' . $projectId);
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form method="post" class="row g-3">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <div class="col-md-4">
    <label class="form-label">Type *</label>
    <select name="type_offre" class="form-select" required>
      <?php foreach (['offre_initiale'=>'Offre initiale','contre_offre'=>'Contre-offre','offre_finale'=>'Offre finale'] as $val=>$label): ?>
        <option value="<?= $val ?>" <?= $data['type_offre'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-4"><label class="form-label">Montant *</label><input type="number" step="0.01" min="0.01" name="montant" class="form-control" required value="<?= e($data['montant'] ?? '') ?>"></div>
  <div class="col-md-4"><label class="form-label">Devise</label>
    <select name="devise" class="form-select"><?php foreach (['FCFA','EUR','USD'] as $d): ?><option value="<?= $d ?>" <?= $data['devise'] === $d ? 'selected' : '' ?>><?= $d ?></option><?php endforeach; ?></select>
  </div>
  <div class="col-md-6"><label class="form-label">Date de l'offre</label><input type="date" name="date_offre" class="form-control" value="<?= e($data['date_offre'] ?? date('Y-m-d')) ?>"></div>
  <div class="col-12"><label class="form-label">Conditions</label><textarea name="conditions" class="form-control" rows="3"><?= e($data['conditions'] ?? '') ?></textarea></div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Enregistrer</button>
    <a href="list.php?project_id=<?= $projectId ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
