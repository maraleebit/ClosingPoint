<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Évaluation DCF — ' . $project['nom_projet'];

$errors = [];
$resultat = null;
$fcfAn1 = $croissance = $wacc = $gTerminal = $horizon = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $fcfAn1 = (float)($_POST['fcf_an1'] ?? 0);
    $croissance = (float)($_POST['croissance'] ?? 0) / 100;
    $wacc = (float)($_POST['wacc'] ?? 0) / 100;
    $gTerminal = (float)($_POST['g_terminal'] ?? 0) / 100;
    $horizon = (int)($_POST['horizon'] ?? 5);

    if ($fcfAn1 <= 0) $errors[] = "Le flux de trésorerie de l'année 1 doit être positif.";
    if ($wacc <= 0 || $wacc >= 1) $errors[] = 'Le WACC doit être compris entre 0 et 100 %.';
    if ($gTerminal >= $wacc) $errors[] = 'Le taux de croissance à l\'infini doit être strictement inférieur au WACC.';
    if ($horizon < 1 || $horizon > 15) $errors[] = "L'horizon de projection doit être compris entre 1 et 15 ans.";

    if (!$errors) {
        $resultat = computeDCF($fcfAn1, $croissance, $wacc, $gTerminal, $horizon);

        if (isset($_POST['enregistrer'])) {
            $hypotheses = json_encode(compact('fcfAn1', 'croissance', 'wacc', 'gTerminal', 'horizon'));
            $pdo->prepare(
                'INSERT INTO valuations (project_id, methode, hypotheses, valeur_calculee, devise, created_by)
                 VALUES (:p, "dcf", :hyp, :val, :dev, :u)'
            )->execute([
                ':p' => $projectId, ':hyp' => $hypotheses, ':val' => round($resultat['valeur_entreprise'], 2),
                ':dev' => $project['devise'], ':u' => $user['id'],
            ]);
            logAudit($pdo, (int)$user['id'], 'creation_valorisation_dcf', 'valuations', (int)$pdo->lastInsertId());
            header('Location: list.php?project_id=' . $projectId . '&saved=1');
            exit;
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="list.php?project_id=<?= $projectId ?>">&larr; Retour aux évaluations</a></p>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

<form method="post" class="row g-3 mb-4" id="dcfForm">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <div class="col-md-3"><label class="form-label">FCF année 1 (<?= e($project['devise']) ?>) *</label><input type="number" step="0.01" min="0.01" name="fcf_an1" class="form-control" required value="<?= e((string)$fcfAn1) ?>"></div>
  <div class="col-md-3"><label class="form-label">Croissance FCF annuelle (%) *</label><input type="number" step="0.01" name="croissance" class="form-control" required value="<?= $croissance !== null ? e((string)($croissance*100)) : '5' ?>"></div>
  <div class="col-md-2"><label class="form-label">WACC (%) *</label><input type="number" step="0.01" min="0.01" max="99" name="wacc" class="form-control" required value="<?= $wacc !== null ? e((string)($wacc*100)) : '12' ?>"></div>
  <div class="col-md-2"><label class="form-label">Croissance terminale g (%) *</label><input type="number" step="0.01" name="g_terminal" class="form-control" required value="<?= $gTerminal !== null ? e((string)($gTerminal*100)) : '3' ?>"></div>
  <div class="col-md-2"><label class="form-label">Horizon (années) *</label><input type="number" min="1" max="15" name="horizon" class="form-control" required value="<?= e((string)($horizon ?: 5)) ?>"></div>
  <div class="col-12">
    <button class="btn btn-outline-primary" type="submit" name="calculer"><i class="bi bi-calculator"></i> Calculer</button>
    <?php if ($resultat): ?><button class="btn btn-primary" type="submit" name="enregistrer" value="1"><i class="bi bi-save"></i> Calculer et enregistrer</button><?php endif; ?>
  </div>
</form>

<?php if ($resultat): ?>
<div class="card">
  <div class="card-header">Résultat de la valorisation DCF</div>
  <div class="card-body">
    <table class="table table-sm">
      <thead><tr><th>Année</th><th class="text-end">FCF projeté</th><th class="text-end">FCF actualisé</th></tr></thead>
      <tbody>
        <?php foreach ($resultat['flux'] as $f): ?>
          <tr><td><?= $f['annee'] ?></td><td class="text-end"><?= formatMoney($f['fcf'], $project['devise']) ?></td><td class="text-end"><?= formatMoney($f['fcf_actualise'], $project['devise']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <dl class="row mb-0">
      <dt class="col-sm-6">Valeur terminale (Gordon-Shapiro)</dt><dd class="col-sm-6"><?= formatMoney($resultat['valeur_terminale'], $project['devise']) ?></dd>
      <dt class="col-sm-6">Valeur terminale actualisée</dt><dd class="col-sm-6"><?= formatMoney($resultat['valeur_terminale_actualisee'], $project['devise']) ?></dd>
      <dt class="col-sm-6 fw-bold">Valeur d'entreprise (VE)</dt><dd class="col-sm-6 fw-bold fs-5"><?= formatMoney($resultat['valeur_entreprise'], $project['devise']) ?></dd>
    </dl>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
