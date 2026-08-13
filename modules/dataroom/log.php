<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);
$pageTitle = 'Journal de consultation';

$docId = (int)($_GET['document_id'] ?? 0);
$stmt = $pdo->prepare('SELECT d.*, p.id AS project_id, p.nom_projet FROM dataroom_documents d JOIN ma_projects p ON p.id = d.project_id WHERE d.id = :id');
$stmt->execute([':id' => $docId]);
$doc = $stmt->fetch();
if (!$doc) { http_response_code(404); die('Document introuvable.'); }

requireProjectAccess($pdo, $user, (int)$doc['project_id']);

$logs = $pdo->prepare(
    'SELECT l.*, u.full_name FROM document_access_log l JOIN users u ON u.id = l.user_id
     WHERE l.document_id = :id ORDER BY l.date_action DESC'
);
$logs->execute([':id' => $docId]);
$logs = $logs->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<p><strong>Document :</strong> <?= e($doc['nom_original']) ?> — <strong>Projet :</strong> <?= e($doc['nom_projet']) ?></p>
<table class="table table-striped">
  <thead class="table-dark"><tr><th>Utilisateur</th><th>Action</th><th>Adresse IP</th><th>Date/heure</th></tr></thead>
  <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= e($l['full_name']) ?></td>
        <td><span class="badge text-bg-secondary"><?= e(str_replace('_', ' ', $l['action'])) ?></span></td>
        <td><code><?= e($l['adresse_ip']) ?></code></td>
        <td><?= formatDate($l['date_action'], 'd/m/Y H:i:s') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="4" class="text-center text-muted">Aucune consultation enregistrée.</td></tr><?php endif; ?>
  </tbody>
</table>
<a href="index.php?project_id=<?= (int)$doc['project_id'] ?>" class="btn btn-outline-secondary">Retour à la data room</a>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
