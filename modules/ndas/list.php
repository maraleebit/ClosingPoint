<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Accords de confidentialité (NDA)';

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    if (in_array($user['role'], ['admin', 'conseiller'], true)) {
        $projects = $pdo->query('SELECT * FROM ma_projects ORDER BY nom_projet')->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT p.* FROM ma_projects p JOIN project_team pt ON pt.project_id = p.id WHERE pt.user_id = :u ORDER BY p.nom_projet');
        $stmt->execute([':u' => $user['id']]);
        $projects = $stmt->fetchAll();
    }
    require __DIR__ . '/../../includes/header.php';
    ?>
    <p class="text-muted">Sélectionnez un projet.</p>
    <div class="list-group">
      <?php foreach ($projects as $p): ?>
        <a href="list.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action"><i class="bi bi-file-earmark-lock"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php }

requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'NDA — ' . $project['nom_projet'];

$ndas = $pdo->prepare('SELECT * FROM ndas WHERE project_id = :p ORDER BY signed_at DESC');
$ndas->execute([':p' => $projectId]);
$ndas = $ndas->fetchAll();

$dejaSigne = false;
foreach ($ndas as $n) { if ((int)$n['user_id'] === (int)$user['id']) { $dejaSigne = true; break; } }

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="<?= e(BASE_URL) ?>/modules/projects/view.php?id=<?= $projectId ?>">&larr; <?= e($project['code_projet']) ?></a></p>

<?php if (!$dejaSigne): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span>Vous n'avez pas encore signé l'accord de confidentialité (NDA) de ce projet.</span>
    <a href="sign.php?project_id=<?= $projectId ?>" class="btn btn-sm btn-primary">Signer le NDA</a>
  </div>
<?php else: ?>
  <div class="alert alert-success">Vous avez signé le NDA de ce projet.</div>
<?php endif; ?>

<table class="table table-striped">
  <thead class="table-dark"><tr><th>Signataire</th><th>Email</th><th>Empreinte de signature (SHA-256)</th><th>Adresse IP</th><th>Date de signature</th></tr></thead>
  <tbody>
    <?php foreach ($ndas as $n): ?>
      <tr>
        <td><?= e($n['nom_signataire']) ?></td>
        <td><?= e($n['email_signataire']) ?></td>
        <td><code class="small"><?= e(substr($n['hash_signature'], 0, 24)) ?>…</code></td>
        <td><code><?= e($n['adresse_ip']) ?></code></td>
        <td><?= formatDate($n['signed_at'], 'd/m/Y H:i:s') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$ndas): ?><tr><td colspan="5" class="text-center text-muted">Aucun NDA signé pour ce projet.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
