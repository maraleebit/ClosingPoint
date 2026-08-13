<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin']);
$pageTitle = "Journal d'audit";

$search = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    // Un nom de paramètre distinct par occurrence (PDO natif rejette la réutilisation d'un même :q).
    $where[] = '(a.action LIKE :q1 OR a.details LIKE :q2 OR u.full_name LIKE :q3)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $whereSql");
$countStmt->execute($params);
$pageInfo = paginate((int)$countStmt->fetchColumn());

$stmt = $pdo->prepare(
    "SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
     $whereSql ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $pageInfo['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pageInfo['offset'], PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<form class="d-flex gap-2 mb-3" method="get">
  <input type="text" name="q" class="form-control" placeholder="Rechercher une action, un utilisateur..." value="<?= e($search) ?>">
  <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  <a href="<?= e(BASE_URL) ?>/exports/export_audit_csv.php?<?= e(http_build_query(['q'=>$search])) ?>" class="btn btn-outline-success ms-auto"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
</form>

<table class="table table-striped table-sm">
  <thead class="table-dark"><tr><th>Date/heure</th><th>Utilisateur</th><th>Action</th><th>Table</th><th>Détails</th><th>IP</th></tr></thead>
  <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= formatDate($l['created_at'], 'd/m/Y H:i:s') ?></td>
        <td><?= e($l['full_name'] ?: 'Système') ?></td>
        <td><span class="badge text-bg-secondary"><?= e(str_replace('_', ' ', $l['action'])) ?></span></td>
        <td><?= e($l['table_concernee'] ?: '—') ?></td>
        <td><?= e($l['details'] ?: '—') ?></td>
        <td><code><?= e($l['adresse_ip']) ?></code></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-3">Aucune entrée.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php renderPagination($pageInfo, 'list.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
