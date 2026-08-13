<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Signature du NDA';

$texteAccord = "ACCORD DE CONFIDENTIALITÉ (NDA)\n\n"
    . "Dans le cadre du projet \"" . $project['nom_projet'] . "\" impliquant " . $project['societe_cible']
    . " et " . $project['societe_acquereur'] . ", le signataire s'engage à garder strictement confidentielles "
    . "toutes les informations consultées dans la data room virtuelle, à ne pas les divulguer à des tiers non "
    . "autorisés et à ne les utiliser qu'aux fins strictes de l'évaluation de l'opération.";

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $nom = trim($_POST['nom_signataire'] ?? '');
    $accepte = isset($_POST['accepte']);

    if ($nom === '') $errors[] = 'Veuillez saisir votre nom complet pour signer.';
    if (!$accepte) $errors[] = "Vous devez cocher la case d'acceptation pour signer le NDA.";

    if (!$errors) {
        $timestamp = date('Y-m-d H:i:s');
        // Signature électronique simplifiée : empreinte cryptographique non-répudiable
        // liant identité, email, texte de l'accord et horodatage.
        $hash = hash('sha256', $nom . '|' . $user['email'] . '|' . $texteAccord . '|' . $timestamp);

        $pdo->prepare(
            'INSERT INTO ndas (project_id, user_id, nom_signataire, email_signataire, hash_signature, adresse_ip)
             VALUES (:p, :u, :nom, :email, :hash, :ip)'
        )->execute([
            ':p' => $projectId, ':u' => $user['id'], ':nom' => $nom, ':email' => $user['email'],
            ':hash' => $hash, ':ip' => clientIp(),
        ]);
        logAudit($pdo, (int)$user['id'], 'signature_nda', 'ndas', (int)$pdo->lastInsertId(), "NDA signé pour le projet #$projectId");

        header('Location: list.php?project_id=' . $projectId . '&signed=1');
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<div class="card mb-3"><div class="card-body"><pre class="mb-0" style="white-space: pre-wrap;"><?= e($texteAccord) ?></pre></div></div>

<form method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <div class="mb-3">
    <label class="form-label">Nom complet du signataire *</label>
    <input type="text" name="nom_signataire" class="form-control" required value="<?= e($user['full_name']) ?>">
  </div>
  <div class="form-check mb-3">
    <input type="checkbox" name="accepte" class="form-check-input" id="accepte" required>
    <label class="form-check-label" for="accepte">Je déclare avoir lu et j'accepte les termes de cet accord de confidentialité.</label>
  </div>
  <button class="btn btn-primary" type="submit"><i class="bi bi-pen"></i> Signer électroniquement</button>
  <a href="list.php?project_id=<?= $projectId ?>" class="btn btn-outline-secondary">Annuler</a>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
