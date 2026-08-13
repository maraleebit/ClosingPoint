<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Offres et contre-offres';

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
        <a href="list.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action"><i class="bi bi-cash-coin"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php }

requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Offres — ' . $project['nom_projet'];

// --- Mise à jour rapide du statut d'une offre ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && in_array($user['role'], ['admin', 'conseiller'], true)) {
    csrf_verify($_POST['csrf_token'] ?? null);
    $offerId = (int)$_POST['offer_id'];
    $nouveauStatut = $_POST['statut'] ?? '';
    if (in_array($nouveauStatut, ['proposee','en_negociation','acceptee','refusee'], true)) {
        $pdo->prepare('UPDATE offers SET statut = :s WHERE id = :id AND project_id = :p')
            ->execute([':s' => $nouveauStatut, ':id' => $offerId, ':p' => $projectId]);
        logAudit($pdo, (int)$user['id'], 'maj_statut_offre', 'offers', $offerId, $nouveauStatut);
    }
    header('Location: list.php?project_id=' . $projectId);
    exit;
}

$offers = $pdo->prepare('SELECT o.*, u.full_name FROM offers o JOIN users u ON u.id = o.emise_par WHERE o.project_id = :p ORDER BY o.date_offre ASC, o.id ASC');
$offers->execute([':p' => $projectId]);
$offers = $offers->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="<?= e(BASE_URL) ?>/modules/projects/view.php?id=<?= $projectId ?>">&larr; <?= e($project['code_projet']) ?></a></p>

<?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
  <a href="add.php?project_id=<?= $projectId ?>" class="btn btn-primary mb-3"><i class="bi bi-plus-circle"></i> Nouvelle offre / contre-offre</a>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead class="table-dark"><tr><th>Type</th><th class="text-end">Montant</th><th>Conditions</th><th>Émise par</th><th>Date</th><th>Statut</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($offers as $o): ?>
      <tr>
        <td><?= e(['offre_initiale'=>'Offre initiale','contre_offre'=>'Contre-offre','offre_finale'=>'Offre finale'][$o['type_offre']] ?? $o['type_offre']) ?></td>
        <td class="text-end fw-bold"><?= formatMoney($o['montant'], $o['devise']) ?></td>
        <td style="max-width:320px;"><?= e($o['conditions'] ?? '') ?></td>
        <td><?= e($o['full_name']) ?></td>
        <td><?= formatDate($o['date_offre']) ?></td>
        <td>
          <?php $map = ['proposee'=>['secondary','Proposée'],'en_negociation'=>['warning','En négociation'],'acceptee'=>['success','Acceptée'],'refusee'=>['danger','Refusée']]; [$c,$l]=$map[$o['statut']] ?? ['secondary',$o['statut']]; ?>
          <span class="badge text-bg-<?= $c ?>"><?= e($l) ?></span>
        </td>
        <td>
          <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
            <form method="post" class="d-inline">
              <?php csrf_field(); ?>
              <input type="hidden" name="update_status" value="1">
              <input type="hidden" name="offer_id" value="<?= (int)$o['id'] ?>">
              <select name="statut" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                <?php foreach ($map as $val => $info): ?>
                  <option value="<?= $val ?>" <?= $o['statut'] === $val ? 'selected' : '' ?>><?= e($info[1]) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$offers): ?><tr><td colspan="7" class="text-center text-muted py-3">Aucune offre enregistrée.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
