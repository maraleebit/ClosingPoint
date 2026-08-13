<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Due Diligence';

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
    <p class="text-muted">Sélectionnez un projet pour consulter sa checklist de due diligence.</p>
    <div class="list-group">
      <?php foreach ($projects as $p): ?>
        <a href="list.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
          <span><i class="bi bi-clipboard-check"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></span>
          <?= projectStatusBadge($p['statut']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php }

requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Due Diligence — ' . $project['nom_projet'];

$domaineFiltre = $_GET['domaine'] ?? '';
$statutFiltre = $_GET['statut'] ?? '';
$redFlagOnly = isset($_GET['red_flag']);

$where = ['project_id = :p'];
$params = [':p' => $projectId];
if ($domaineFiltre !== '') { $where[] = 'domaine = :domaine'; $params[':domaine'] = $domaineFiltre; }
if ($statutFiltre !== '') { $where[] = 'statut = :statut'; $params[':statut'] = $statutFiltre; }
if ($redFlagOnly) { $where[] = 'red_flag = 1'; }
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM due_diligence_items WHERE $whereSql");
$countStmt->execute($params);
$pageInfo = paginate((int)$countStmt->fetchColumn());

$stmt = $pdo->prepare(
    "SELECT dd.*, u.full_name AS responsable FROM due_diligence_items dd
     LEFT JOIN users u ON u.id = dd.responsable_id
     WHERE $whereSql ORDER BY red_flag DESC, dd.date_limite ASC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit', $pageInfo['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pageInfo['offset'], PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="<?= e(BASE_URL) ?>/modules/projects/view.php?id=<?= $projectId ?>">&larr; <?= e($project['code_projet']) ?> — <?= e($project['nom_projet']) ?></a></p>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
  <form class="row g-2" method="get">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <div class="col-auto">
      <select name="domaine" class="form-select">
        <option value="">Tous domaines</option>
        <?php foreach (['juridique','fiscal','financier','commercial','rh','it'] as $d): ?>
          <option value="<?= $d ?>" <?= $domaineFiltre === $d ? 'selected' : '' ?>><?= e(domaineLabel($d)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="statut" class="form-select">
        <option value="">Tous statuts</option>
        <?php foreach (['a_verifier','en_cours','valide','alerte'] as $s): ?>
          <option value="<?= $s ?>" <?= $statutFiltre === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto form-check mt-2">
      <input type="checkbox" name="red_flag" value="1" class="form-check-input" id="rf" <?= $redFlagOnly ? 'checked' : '' ?> onchange="this.form.submit()">
      <label class="form-check-label" for="rf">Red flags uniquement</label>
    </div>
    <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Filtrer</button></div>
  </form>
  <div class="btn-group">
    <a href="<?= e(BASE_URL) ?>/exports/export_duediligence_pdf.php?project_id=<?= $projectId ?>" class="btn btn-outline-danger" target="_blank"><i class="bi bi-file-earmark-pdf"></i> Export PDF</a>
    <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
      <a href="add.php?project_id=<?= $projectId ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouveau point de contrôle</a>
    <?php endif; ?>
  </div>
</div>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead class="table-dark"><tr><th>Domaine</th><th>Libellé</th><th>Statut</th><th></th><th>Impact estimé</th><th>Responsable</th><th>Échéance</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($items as $it): ?>
      <tr class="<?= $it['red_flag'] ? 'table-danger' : '' ?>">
        <td><?= e(domaineLabel($it['domaine'])) ?></td>
        <td><?= e($it['libelle']) ?></td>
        <td><?= ddStatusBadge($it['statut']) ?></td>
        <td><?= redFlagBadge((bool)$it['red_flag']) ?></td>
        <td><?= $it['impact_estime'] ? formatMoney($it['impact_estime'], $project['devise']) : '—' ?></td>
        <td><?= e($it['responsable'] ?: '—') ?></td>
        <td><?= formatDate($it['date_limite']) ?></td>
        <td class="text-end">
          <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
            <a href="edit.php?id=<?= (int)$it['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Supprimer ce point de contrôle ?');">
              <?php csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="project_id" value="<?= $projectId ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="8" class="text-center text-muted py-3">Aucun point de contrôle.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php renderPagination($pageInfo, 'list.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
