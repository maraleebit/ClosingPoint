<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Méthode des multiples — ' . $project['nom_projet'];

$errors = [];
$valeur = null;
$ebitda = $multiple = $dette = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $ebitda = (float)($_POST['ebitda'] ?? 0);
    $multiple = (float)($_POST['multiple'] ?? 0);
    $dette = (float)($_POST['dette_nette'] ?? 0);

    if ($ebitda <= 0) $errors[] = "L'EBITDA doit être positif.";
    if ($multiple <= 0) $errors[] = 'Le multiple VE/EBITDA doit être positif.';

    if (!$errors) {
        $ve = $ebitda * $multiple;
        $valeur = $ve - $dette; // Valeur des fonds propres = VE - dette nette

        if (isset($_POST['enregistrer'])) {
            $hyp = json_encode(compact('ebitda', 'multiple', 'dette'));
            $pdo->prepare('INSERT INTO valuations (project_id, methode, hypotheses, valeur_calculee, devise, created_by) VALUES (:p,"multiples",:h,:v,:d,:u)')
                ->execute([':p' => $projectId, ':h' => $hyp, ':v' => round($valeur, 2), ':d' => $project['devise'], ':u' => $user['id']]);
            logAudit($pdo, (int)$user['id'], 'creation_valorisation_multiples', 'valuations', (int)$pdo->lastInsertId());
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
  <div class="col-md-4"><label class="form-label">EBITDA (<?= e($project['devise']) ?>) *</label><input type="number" step="0.01" min="0.01" name="ebitda" class="form-control" required value="<?= e((string)$ebitda) ?>"></div>
  <div class="col-md-4"><label class="form-label">Multiple VE/EBITDA sectoriel *</label><input type="number" step="0.1" min="0.1" name="multiple" class="form-control" required value="<?= $multiple !== null ? e((string)$multiple) : '6.5' ?>"></div>
  <div class="col-md-4"><label class="form-label">Dette nette</label><input type="number" step="0.01" name="dette_nette" class="form-control" value="<?= e((string)$dette) ?>"></div>
  <div class="col-12">
    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-calculator"></i> Calculer</button>
    <?php if ($valeur !== null): ?><button class="btn btn-primary" type="submit" name="enregistrer" value="1"><i class="bi bi-save"></i> Enregistrer</button><?php endif; ?>
  </div>
</form>

<?php if ($valeur !== null): ?>
  <div class="alert alert-info mt-3">
    Valeur d'entreprise (VE) = <?= formatMoney($ebitda * $multiple, $project['devise']) ?><br>
    Valeur des fonds propres (VE − dette nette) = <strong><?= formatMoney($valeur, $project['devise']) ?></strong>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
