<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Projets M&A';

// --- Recherche et filtres multi-critères ---
$search = trim($_GET['q'] ?? '');
$statutFiltre = $_GET['statut'] ?? '';
$secteurFiltre = $_GET['secteur'] ?? '';

$where = [];
$params = [];
if ($search !== '') {
    // NB : PDO (requêtes préparées natives) n'autorise pas de réutiliser le même
    // paramètre nommé plusieurs fois dans une requête -> un nom distinct par occurrence.
    $where[] = '(nom_projet LIKE :q1 OR societe_cible LIKE :q2 OR societe_acquereur LIKE :q3 OR code_projet LIKE :q4)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
}
if ($statutFiltre !== '') {
    $where[] = 'statut = :statut';
    $params[':statut'] = $statutFiltre;
}
if ($secteurFiltre !== '') {
    $where[] = 'secteur = :secteur';
    $params[':secteur'] = $secteurFiltre;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM ma_projects $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pageInfo = paginate($total);

$stmt = $pdo->prepare(
    "SELECT * FROM ma_projects $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $pageInfo['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pageInfo['offset'], PDO::PARAM_INT);
$stmt->execute();
$projects = $stmt->fetchAll();

$secteurs = $pdo->query("SELECT DISTINCT secteur FROM ma_projects WHERE secteur IS NOT NULL ORDER BY secteur")->fetchAll(PDO::FETCH_COLUMN);

require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="row g-2" method="get">
    <div class="col-auto">
      <input type="text" name="q" class="form-control" placeholder="Rechercher (nom, cible, acquéreur, code)..." value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
      <select name="statut" class="form-select">
        <option value="">Tous statuts</option>
        <?php foreach (['prospection','nda_signe','due_diligence','negociation','closing','abandonne'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $statutFiltre === $s ? 'selected' : '' ?>><?= e(ucfirst(str_replace('_',' ',$s))) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <select name="secteur" class="form-select">
        <option value="">Tous secteurs</option>
        <?php foreach ($secteurs as $s): ?>
          <option value="<?= e($s) ?>" <?= $secteurFiltre === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Filtrer</button>
    </div>
  </form>
  <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
    <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Nouveau projet</a>
  <?php endif; ?>
</div>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
  <thead class="table-dark">
    <tr>
      <th>Code</th><th>Projet</th><th>Cible</th><th>Acquéreur</th><th>Secteur</th>
      <th>Statut</th><th class="text-end">Valeur estimée</th><th>Closing cible</th><th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$projects): ?>
      <tr><td colspan="9" class="text-center text-muted py-4">Aucun projet trouvé pour ces critères.</td></tr>
    <?php endif; ?>
    <?php foreach ($projects as $p): ?>
      <tr>
        <td><code><?= e($p['code_projet']) ?></code></td>
        <td><a href="view.php?id=<?= (int)$p['id'] ?>"><?= e($p['nom_projet']) ?></a></td>
        <td><?= e($p['societe_cible']) ?></td>
        <td><?= e($p['societe_acquereur']) ?></td>
        <td><?= e($p['secteur']) ?></td>
        <td><?= projectStatusBadge($p['statut']) ?></td>
        <td class="text-end"><?= formatMoney($p['valeur_estimee'], $p['devise']) ?></td>
        <td><?= formatDate($p['date_cible_closing']) ?></td>
        <td class="text-end">
          <a href="view.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
          <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
            <a href="edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <?php endif; ?>
          <?php if ($user['role'] === 'admin'): ?>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Supprimer définitivement ce projet et toutes ses données liées (data room, due diligence, offres) ?');">
              <?php csrf_field(); ?>
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php renderPagination($pageInfo, 'list.php'); ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
