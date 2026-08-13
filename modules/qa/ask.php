<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Poser une question — ' . $project['nom_projet'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $question = trim($_POST['question'] ?? '');
    if ($question === '') {
        $errors[] = 'Veuillez saisir votre question.';
    } elseif (mb_strlen($question) < 10) {
        $errors[] = 'Merci de préciser votre question (10 caractères minimum).';
    } else {
        $stmt = $pdo->prepare('INSERT INTO qa_questions (project_id, question, posee_par) VALUES (:p, :q, :u)');
        $stmt->execute([':p' => $projectId, ':q' => $question, ':u' => $user['id']]);
        logAudit($pdo, (int)$user['id'], 'creation_question_qa', 'qa_questions', (int)$pdo->lastInsertId());
        header('Location: list.php?project_id=' . $projectId);
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e2): ?><li><?= e($e2) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" class="row g-3">
  <?php csrf_field(); ?>
  <input type="hidden" name="project_id" value="<?= $projectId ?>">
  <div class="col-12">
    <label class="form-label">Votre question *</label>
    <textarea name="question" class="form-control" rows="4" required minlength="10"></textarea>
  </div>
  <div class="col-12">
    <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Envoyer</button>
    <a href="list.php?project_id=<?= $projectId ?>" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
