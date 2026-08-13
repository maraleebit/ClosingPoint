<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Actif Net Comptable Corrigé — ' . $project['nom_projet'];

$errors = [];
$valeur = null;
$actifComptable = $plusValues = $moinsValues = $passifExigible = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $actifComptable = (float)($_POST['actif_comptable'] ?? 0);
    $plusValues = (float)($_POST['plus_values'] ?? 0);
    $moinsValues = (float)($_POST['moins_values'] ?? 0);
    $passifExigible = (float)($_POST['passif_exigible'] ?? 0);

    if ($actifComptable <= 0) $errors[] = "L'actif comptable doit être positif.";

    if (!$errors) {
        // ANCC = Actif comptable + plus-values latentes - moins-values latentes - passif exigible
        $valeur = $actifComptable + $plusValues - $moinsValues - $passifExigible;

        if (isset($_POST['enregistrer'])) {
            $hyp = json_encode(compact('actifComptable', 'plusValues', 'moinsValues', 'passifExigible'));
            $pdo->prepare('INSERT INTO valuations (project_id, methode, hypotheses, valeur_calculee, devise, created_by) VALUES (:p,"ancc",:h,:v,:d,:u)')
                ->execute([':p' => $projectId, ':h' => $hyp, ':v' => round($valeur, 2), ':d' => $project['devise'], ':u' => $user['id']]);
            logAudit($pdo, (int)$user['id'], 'creation_valorisation_ancc', 'valuations', (int)$pdo->lastInsertId());
            header('Location: list.php?project_id=' . $projectId . '&saved=1');
            exit;
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="list.php?project_id=<?= $projectId ?>">&larr; Retour aux évaluations</a></p>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form method="post" class="row g-3">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <div class="col-md-3"><label class="form-label">Actif net comptable *</label><input type="number" step="0.01" min="0.01" name="actif_comptable" class="form-control" required value="<?= e((string)$actifComptable) ?>"></div>
  <div class="col-md-3"><label class="form-label">Plus-values latentes (immo., stocks...)</label><input type="number" step="0.01" name="plus_values" class="form-control" value="<?= e((string)$plusValues) ?>"></div>
  <div class="col-md-3"><label class="form-label">Moins-values latentes</label><input type="number" step="0.01" name="moins_values" class="form-control" value="<?= e((string)$moinsValues) ?>"></div>
  <div class="col-md-3"><label class="form-label">Passif exigible non comptabilisé</label><input type="number" step="0.01" name="passif_exigible" class="form-control" value="<?= e((string)$passifExigible) ?>"></div>
  <div class="col-12">
    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-calculator"></i> Calculer</button>
    <?php if ($valeur !== null): ?><button class="btn btn-primary" type="submit" name="enregistrer" value="1"><i class="bi bi-save"></i> Enregistrer</button><?php endif; ?>
  </div>
</form>

<?php if ($valeur !== null): ?>
  <div class="alert alert-info mt-3">ANCC = <strong><?= formatMoney($valeur, $project['devise']) ?></strong></div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
