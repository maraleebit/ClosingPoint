<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);
$pageTitle = 'Déposer un document';

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$folderId = isset($_GET['folder_id']) && $_GET['folder_id'] !== '' ? (int)$_GET['folder_id'] : (isset($_POST['folder_id']) && $_POST['folder_id'] !== '' ? (int)$_POST['folder_id'] : null);

requireProjectAccess($pdo, $user, $projectId);
$stmt = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$stmt->execute([':id' => $projectId]);
$project = $stmt->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $categorie = $_POST['categorie'] ?? 'autre';
    $confidentiel = isset($_POST['confidentiel']) ? 1 : 0;

    if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Veuillez sélectionner un fichier à déposer.';
    } elseif ($_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erreur lors du téléversement (code ' . $_FILES['fichier']['error'] . ').';
    } else {
        $file = $_FILES['fichier'];
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Le fichier dépasse la taille maximale autorisée (' . formatFileSize(MAX_UPLOAD_SIZE) . ').';
        }
        if (!isAllowedExtension($file['name'])) {
            $errors[] = 'Extension de fichier non autorisée. Extensions acceptées : ' . implode(', ', ALLOWED_EXTENSIONS) . '.';
        }
        if (!$errors) {
            $dir = ensureUploadDirForProject($projectId);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
            $destination = $dir . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $errors[] = "Impossible d'enregistrer le fichier sur le serveur.";
            } else {
                $mime = mime_content_type($destination) ?: $file['type'];
                $insert = $pdo->prepare(
                    'INSERT INTO dataroom_documents (project_id, folder_id, nom_original, nom_fichier_stocke, chemin_relatif, taille_octets, type_mime, categorie, confidentiel, uploaded_by)
                     VALUES (:p, :f, :orig, :stocke, :chemin, :taille, :mime, :cat, :conf, :u)'
                );
                $insert->execute([
                    ':p' => $projectId,
                    ':f' => $folderId,
                    ':orig' => basename($file['name']),
                    ':stocke' => $storedName,
                    ':chemin' => 'projet_' . $projectId . '/' . $storedName,
                    ':taille' => $file['size'],
                    ':mime' => $mime,
                    ':cat' => $categorie,
                    ':conf' => $confidentiel,
                    ':u' => $user['id'],
                ]);
                $docId = (int)$pdo->lastInsertId();

                $pdo->prepare('INSERT INTO document_access_log (document_id, user_id, action, adresse_ip) VALUES (:d, :u, "upload", :ip)')
                    ->execute([':d' => $docId, ':u' => $user['id'], ':ip' => clientIp()]);
                logAudit($pdo, (int)$user['id'], 'upload_document', 'dataroom_documents', $docId, basename($file['name']));

                $success = true;
            }
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<?php if ($success): ?>
  <div class="alert alert-success">Document déposé avec succès.
    <a href="index.php?project_id=<?= $projectId ?><?= $folderId ? '&folder_id=' . $folderId : '' ?>">Retour à la data room</a>
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3" id="uploadForm">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <input type="hidden" name="folder_id" value="<?= e((string)$folderId) ?>">

  <div class="col-md-6">
    <label class="form-label">Fichier *</label>
    <input type="file" name="fichier" class="form-control" required>
    <div class="form-text">Extensions autorisées : <?= e(implode(', ', ALLOWED_EXTENSIONS)) ?> — taille max <?= formatFileSize(MAX_UPLOAD_SIZE) ?>.</div>
  </div>
  <div class="col-md-3">
    <label class="form-label">Catégorie</label>
    <select name="categorie" class="form-select">
      <?php foreach (['juridique','fiscal','financier','commercial','rh','it','autre'] as $c): ?>
        <option value="<?= $c ?>"><?= e(domaineLabel($c) !== $c ? domaineLabel($c) : ucfirst($c)) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-3 d-flex align-items-center">
    <div class="form-check mt-4">
      <input type="checkbox" name="confidentiel" class="form-check-input" id="conf" checked>
      <label class="form-check-label" for="conf">Document confidentiel</label>
    </div>
  </div>
  <div class="col-12">
    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Déposer</button>
    <a href="index.php?project_id=<?= $projectId ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
