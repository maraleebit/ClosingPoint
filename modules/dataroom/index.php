<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Data Room virtuelle';

$projectId = (int)($_GET['project_id'] ?? 0);

// --- Aucun projet sélectionné : liste des projets accessibles ---
if (!$projectId) {
    if (in_array($user['role'], ['admin', 'conseiller'], true)) {
        $projects = $pdo->query('SELECT * FROM ma_projects ORDER BY nom_projet')->fetchAll();
    } else {
        $stmt = $pdo->prepare(
            'SELECT p.* FROM ma_projects p JOIN project_team pt ON pt.project_id = p.id WHERE pt.user_id = :u ORDER BY p.nom_projet'
        );
        $stmt->execute([':u' => $user['id']]);
        $projects = $stmt->fetchAll();
    }
    require __DIR__ . '/../../includes/header.php';
    ?>
    <p class="text-muted">Sélectionnez un projet pour accéder à sa data room virtuelle.</p>
    <div class="list-group">
      <?php foreach ($projects as $p): ?>
        <a href="index.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between">
          <span><i class="bi bi-folder2-open"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></span>
          <?= projectStatusBadge($p['statut']) ?>
        </a>
      <?php endforeach; ?>
      <?php if (!$projects): ?><p class="text-muted">Aucun projet accessible.</p><?php endif; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; ?>
    <?php exit; ?>
<?php }

requireProjectAccess($pdo, $user, $projectId);

$stmt = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Data Room — ' . $project['nom_projet'];

$folderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== '' ? (int)$_GET['folder_id'] : null;

// --- Création d'un sous-dossier ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_folder']) && in_array($user['role'], ['admin', 'conseiller'], true)) {
    csrf_verify($_POST['csrf_token'] ?? null);
    $nom = trim($_POST['nom_dossier'] ?? '');
    if ($nom !== '') {
        $pdo->prepare('INSERT INTO dataroom_folders (project_id, parent_id, nom, created_by) VALUES (:p, :parent, :nom, :u)')
            ->execute([':p' => $projectId, ':parent' => $folderId, ':nom' => $nom, ':u' => $user['id']]);
        logAudit($pdo, (int)$user['id'], 'creation_dossier', 'dataroom_folders', (int)$pdo->lastInsertId(), $nom);
    }
    $qs = 'project_id=' . $projectId . ($folderId ? '&folder_id=' . $folderId : '');
    header('Location: index.php?' . $qs);
    exit;
}

// --- Fil d'Ariane ---
$breadcrumb = [];
$cursor = $folderId;
while ($cursor) {
    $f = $pdo->prepare('SELECT id, parent_id, nom FROM dataroom_folders WHERE id = :id');
    $f->execute([':id' => $cursor]);
    $f = $f->fetch();
    if (!$f) break;
    array_unshift($breadcrumb, $f);
    $cursor = $f['parent_id'];
}

// --- Sous-dossiers du niveau courant ---
$foldersStmt = $pdo->prepare(
    'SELECT * FROM dataroom_folders WHERE project_id = :p AND ' . ($folderId ? 'parent_id = :folder' : 'parent_id IS NULL') . ' ORDER BY nom'
);
$foldersStmt->bindValue(':p', $projectId);
if ($folderId) $foldersStmt->bindValue(':folder', $folderId);
$foldersStmt->execute();
$folders = $foldersStmt->fetchAll();

// --- Documents du niveau courant (recherche + filtre catégorie) ---
$search = trim($_GET['q'] ?? '');
$categorie = $_GET['categorie'] ?? '';
$docWhere = ['project_id = :p', $folderId ? 'folder_id = :folder' : 'folder_id IS NULL'];
$docParams = [':p' => $projectId];
if ($folderId) $docParams[':folder'] = $folderId;
if ($search !== '') { $docWhere[] = 'nom_original LIKE :q'; $docParams[':q'] = '%' . $search . '%'; }
if ($categorie !== '') { $docWhere[] = 'categorie = :cat'; $docParams[':cat'] = $categorie; }
$docStmt = $pdo->prepare('SELECT d.*, u.full_name AS uploader FROM dataroom_documents d JOIN users u ON u.id = d.uploaded_by WHERE ' . implode(' AND ', $docWhere) . ' ORDER BY d.created_at DESC');
$docStmt->execute($docParams);
$documents = $docStmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="index.php?project_id=<?= $projectId ?>"><i class="bi bi-house"></i> <?= e($project['code_projet']) ?></a></li>
    <?php foreach ($breadcrumb as $b): ?>
      <li class="breadcrumb-item"><a href="index.php?project_id=<?= $projectId ?>&folder_id=<?= (int)$b['id'] ?>"><?= e($b['nom']) ?></a></li>
    <?php endforeach; ?>
  </ol>
</nav>

<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
  <form class="row g-2" method="get">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <?php if ($folderId): ?><input type="hidden" name="folder_id" value="<?= $folderId ?>"><?php endif; ?>
    <div class="col-auto"><input type="text" name="q" class="form-control" placeholder="Rechercher un document..." value="<?= e($search) ?>"></div>
    <div class="col-auto">
      <select name="categorie" class="form-select">
        <option value="">Toutes catégories</option>
        <?php foreach (['juridique','fiscal','financier','commercial','rh','it','autre'] as $c): ?>
          <option value="<?= $c ?>" <?= $categorie === $c ? 'selected' : '' ?>><?= e(domaineLabel($c) !== $c ? domaineLabel($c) : ucfirst($c)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button></div>
  </form>

  <div class="btn-group">
    <a href="<?= e(BASE_URL) ?>/exports/export_dataroom_csv.php?project_id=<?= $projectId ?>" class="btn btn-outline-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
    <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
      <a href="upload.php?project_id=<?= $projectId ?><?= $folderId ? '&folder_id=' . $folderId : '' ?>" class="btn btn-primary"><i class="bi bi-upload"></i> Déposer un document</a>
      <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalFolder"><i class="bi bi-folder-plus"></i> Nouveau dossier</button>
    <?php endif; ?>
  </div>
</div>

<div class="row">
  <?php foreach ($folders as $f): ?>
    <div class="col-md-3 mb-3">
      <a href="index.php?project_id=<?= $projectId ?>&folder_id=<?= (int)$f['id'] ?>" class="text-decoration-none">
        <div class="card text-center py-3"><i class="bi bi-folder-fill fs-1 text-warning"></i><div class="mt-1"><?= e($f['nom']) ?></div></div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead class="table-dark">
    <tr><th>Document</th><th>Catégorie</th><th>Confidentiel</th><th>Taille</th><th>Déposé par</th><th>Date</th><th></th></tr>
  </thead>
  <tbody>
    <?php if (!$documents): ?><tr><td colspan="7" class="text-center text-muted py-3">Aucun document dans ce dossier.</td></tr><?php endif; ?>
    <?php foreach ($documents as $d): ?>
      <tr>
        <td><i class="bi bi-file-earmark-text"></i> <?= e($d['nom_original']) ?></td>
        <td><span class="badge text-bg-light border"><?= e(domaineLabel($d['categorie']) !== $d['categorie'] ? domaineLabel($d['categorie']) : ucfirst($d['categorie'])) ?></span></td>
        <td><?= $d['confidentiel'] ? '<i class="bi bi-lock-fill text-danger" title="Confidentiel"></i>' : '<i class="bi bi-unlock text-muted"></i>' ?></td>
        <td><?= formatFileSize((int)$d['taille_octets']) ?></td>
        <td><?= e($d['uploader']) ?></td>
        <td><?= formatDate($d['created_at'], 'd/m/Y H:i') ?></td>
        <td class="text-end">
          <a href="download.php?id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Télécharger (consultation tracée)"><i class="bi bi-download"></i></a>
          <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
            <a href="log.php?document_id=<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Journal de consultation"><i class="bi bi-clock-history"></i></a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<!-- Modal création de dossier -->
<div class="modal fade" id="modalFolder" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <?php csrf_field(); ?>
      <input type="hidden" name="add_folder" value="1">
      <div class="modal-header"><h5 class="modal-title">Nouveau dossier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body"><input type="text" name="nom_dossier" class="form-control" required placeholder="Nom du dossier"></div>
      <div class="modal-footer"><button type="submit" class="btn btn-primary">Créer</button></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
