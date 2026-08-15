<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);
$pageTitle = 'Valorisation';

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    $projects = $pdo->query('SELECT * FROM ma_projects ORDER BY nom_projet')->fetchAll();
    require __DIR__ . '/../../includes/header.php';
    ?>
    <p class="text-muted">Sélectionnez un projet.</p>
    <div class="list-group">
      <?php foreach ($projects as $p): ?>
        <a href="list.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action"><i class="bi bi-calculator"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php }

$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Valorisation — ' . $project['nom_projet'];

$valuations = $pdo->prepare('SELECT v.*, u.full_name FROM valuations v JOIN users u ON u.id = v.created_by WHERE v.project_id = :p ORDER BY v.created_at DESC');
$valuations->execute([':p' => $projectId]);
$valuations = $valuations->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="<?= e(BASE_URL) ?>/modules/projects/view.php?id=<?= $projectId ?>">&larr; <?= e($project['code_projet']) ?></a></p>

<div class="btn-group mb-3">
  <a href="dcf.php?project_id=<?= $projectId ?>" class="btn btn-primary"><i class="bi bi-graph-up"></i> Nouvelle évaluation DCF</a>
  <a href="multiples.php?project_id=<?= $projectId ?>" class="btn btn-outline-primary"><i class="bi bi-bar-chart"></i> Méthode des multiples</a>
  <a href="ancc.php?project_id=<?= $projectId ?>" class="btn btn-outline-primary"><i class="bi bi-building"></i> Actif Net Comptable Corrigé</a>
</div>

<table class="table table-striped">
  <thead class="table-dark"><tr><th>Méthode</th><th class="text-end">Valeur calculée</th><th>Réalisée par</th><th>Date</th></tr></thead>
  <tbody>
    <?php foreach ($valuations as $v): ?>
      <tr>
        <td><?= e(['dcf'=>'DCF (flux actualisés)','multiples'=>'Multiples boursiers','ancc'=>'Actif net comptable corrigé'][$v['methode']] ?? $v['methode']) ?></td>
        <td class="text-end fw-bold"><?= formatMoney($v['valeur_calculee'], $v['devise']) ?></td>
        <td><?= e($v['full_name']) ?></td>
        <td><?= formatDate($v['created_at'], 'd/m/Y H:i') ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$valuations): ?><tr><td colspan="4" class="text-center text-muted">Aucune évaluation réalisée.</td></tr><?php endif; ?>
  </tbody>
</table>

<?php if (count($valuations) >= 2): ?>
<div class="card"><div class="card-header">Football field — comparaison des méthodes</div>
<div class="card-body"><canvas id="chartFootball" height="120"></canvas></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartFootball'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($v) => ['dcf'=>'DCF','multiples'=>'Multiples','ancc'=>'ANCC'][$v['methode']] ?? $v['methode'], $valuations)) ?>,
    datasets: [{ label: 'Valeur (<?= e($project['devise']) ?>)', data: <?= json_encode(array_map(fn($v) => (float)$v['valeur_calculee'], $valuations)) ?>, backgroundColor: <?= json_encode(array_map(fn($v) => ['dcf'=>'#79573C','multiples'=>'#C9A579','ancc'=>'#3E7C94'][$v['methode']] ?? '#8A7361', $valuations)) ?> }]
  },
  options: { indexAxis: 'y', responsive: true }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
