<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin']);
$pageTitle = 'Gestion des utilisateurs';

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    // Un nom de paramètre distinct par occurrence (PDO natif rejette la réutilisation d'un même :q).
    $where = 'WHERE full_name LIKE :q1 OR email LIKE :q2';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$pageInfo = paginate((int)$countStmt->fetchColumn());

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY full_name LIMIT :limit OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $pageInfo['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pageInfo['offset'], PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between mb-3">
  <form class="d-flex gap-2" method="get">
    <input type="text" name="q" class="form-control" placeholder="Rechercher..." value="<?= e($search) ?>">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
  <a href="add.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Nouvel utilisateur</a>
</div>

<table class="table table-striped align-middle">
  <thead class="table-dark"><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Actif</th><th>Dernière connexion</th><th></th></tr></thead>
  <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['full_name']) ?></td>
        <td><?= e($u['email']) ?></td>
        <td><span class="badge text-bg-secondary"><?= e(roleLabel($u['role'])) ?></span></td>
        <td><?= $u['is_active'] ? '<span class="badge text-bg-success">Actif</span>' : '<span class="badge text-bg-danger">Inactif</span>' ?></td>
        <td><?= formatDate($u['last_login'], 'd/m/Y H:i') ?></td>
        <td class="text-end">
          <a href="edit.php?id=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
          <?php if ((int)$u['id'] !== (int)$user['id']): ?>
            <form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Désactiver ce compte utilisateur ?');">
              <?php csrf_field(); ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-person-x"></i></button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php renderPagination($pageInfo, 'list.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
