<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare(
    'SELECT q.*, u.full_name AS demandeur_nom, u.email AS demandeur_email FROM qa_questions q JOIN users u ON u.id = q.posee_par WHERE q.id = :id'
);
$stmt->execute([':id' => $id]);
$question = $stmt->fetch();
if (!$question) { http_response_code(404); die('Question introuvable.'); }

$projectId = (int)$question['project_id'];
requireProjectAccess($pdo, $user, $projectId);
$pageTitle = 'Répondre à la question';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $reponse = trim($_POST['reponse'] ?? '');
    if ($reponse === '') {
        $errors[] = 'La réponse ne peut pas être vide.';
    } else {
        $pdo->prepare('UPDATE qa_questions SET reponse=:r, repondu_par=:u, statut="repondue", answered_at=NOW() WHERE id=:id')
            ->execute([':r' => $reponse, ':u' => $user['id'], ':id' => $id]);
        logAudit($pdo, (int)$user['id'], 'reponse_question_qa', 'qa_questions', $id);

        // --- Envoi automatique d'un email de notification au demandeur ---
        $corpsEmail = '<p>Bonjour ' . e($question['demandeur_nom']) . ',</p>'
            . '<p>Votre question dans le module Q&amp;A a reçu une réponse :</p>'
            . '<blockquote>' . nl2br(e($reponse)) . '</blockquote>'
            . '<p>Connectez-vous à la plateforme pour consulter le fil complet.</p>';
        sendAppMail($question['demandeur_email'], 'Nouvelle réponse à votre question — ' . SITE_NAME, $corpsEmail);

        header('Location: list.php?project_id=' . $projectId . '&answered=1');
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $e2): ?><?= e($e2) ?><?php endforeach; ?></div><?php endif; ?>
<div class="card mb-3"><div class="card-body">
  <p class="mb-1 text-muted small">Question de <?= e($question['demandeur_nom']) ?> le <?= formatDate($question['created_at'], 'd/m/Y H:i') ?></p>
  <p class="mb-0"><?= nl2br(e($question['question'])) ?></p>
</div></div>
<form method="post">
  <?php csrf_field(); ?>
  <input type="hidden" name="id" value="<?= $id ?>">
  <div class="mb-3">
    <label class="form-label">Votre réponse *</label>
    <textarea name="reponse" class="form-control" rows="5" required></textarea>
  </div>
  <button class="btn btn-success" type="submit"><i class="bi bi-reply"></i> Envoyer la réponse</button>
  <a href="list.php?project_id=<?= $projectId ?>" class="btn btn-outline-secondary">Annuler</a>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
