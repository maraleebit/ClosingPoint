<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Questions / Réponses';

$projectId = (int)($_GET['project_id'] ?? 0);
if (!$projectId) {
    if (in_array($user['role'], ['admin', 'conseiller'], true)) {
        $projects = $pdo->query('SELECT * FROM ma_projects ORDER BY nom_projet')->fetchAll();
    } else {
        $stmt = $pdo->prepare('SELECT p.* FROM ma_projects p JOIN project_team pt ON pt.project_id = p.id WHERE pt.user_id = :u ORDER BY p.nom_projet');
        $stmt->execute([':u' => $user['id']]);
        $projects = $stmt->fetchAll();
    }
    require __DIR__ . '/../../includes/header.php';
    ?>
    <p class="text-muted">Sélectionnez un projet.</p>
    <div class="list-group">
      <?php foreach ($projects as $p): ?>
        <a href="list.php?project_id=<?= (int)$p['id'] ?>" class="list-group-item list-group-item-action"><i class="bi bi-chat-dots"></i> <?= e($p['code_projet']) ?> — <?= e($p['nom_projet']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php require __DIR__ . '/../../includes/footer.php'; exit; ?>
<?php }

requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Q&A — ' . $project['nom_projet'];

$statutFiltre = $_GET['statut'] ?? '';
$where = ['q.project_id = :p'];
$params = [':p' => $projectId];
if ($statutFiltre !== '') { $where[] = 'q.statut = :statut'; $params[':statut'] = $statutFiltre; }
$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM qa_questions q WHERE $whereSql");
$countStmt->execute($params);
$pageInfo = paginate((int)$countStmt->fetchColumn());

$stmt = $pdo->prepare(
    "SELECT q.*, ua.full_name AS demandeur, ur.full_name AS repondant
     FROM qa_questions q JOIN users ua ON ua.id = q.posee_par LEFT JOIN users ur ON ur.id = q.repondu_par
     WHERE $whereSql ORDER BY q.created_at DESC LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $pageInfo['perPage'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pageInfo['offset'], PDO::PARAM_INT);
$stmt->execute();
$questions = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<p><a href="<?= e(BASE_URL) ?>/modules/projects/view.php?id=<?= $projectId ?>">&larr; <?= e($project['code_projet']) ?></a></p>

<div class="d-flex justify-content-between mb-3">
  <form class="row g-2" method="get">
    <input type="hidden" name="project_id" value="<?= $projectId ?>">
    <div class="col-auto">
      <select name="statut" class="form-select" onchange="this.form.submit()">
        <option value="">Tous statuts</option>
        <option value="ouverte" <?= $statutFiltre === 'ouverte' ? 'selected' : '' ?>>Ouverte</option>
        <option value="repondue" <?= $statutFiltre === 'repondue' ? 'selected' : '' ?>>Répondue</option>
        <option value="fermee" <?= $statutFiltre === 'fermee' ? 'selected' : '' ?>>Fermée</option>
      </select>
    </div>
  </form>
  <a href="ask.php?project_id=<?= $projectId ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Poser une question</a>
</div>

<div class="accordion" id="qaAccordion">
<?php foreach ($questions as $i => $q): ?>
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#q<?= $i ?>">
        <?= $q['statut'] === 'ouverte' ? '<span class="badge text-bg-warning me-2">Ouverte</span>' : ($q['statut'] === 'repondue' ? '<span class="badge text-bg-success me-2">Répondue</span>' : '<span class="badge text-bg-secondary me-2">Fermée</span>') ?>
        <?= e(mb_substr($q['question'], 0, 90)) ?><?= mb_strlen($q['question']) > 90 ? '…' : '' ?>
      </button>
    </h2>
    <div id="q<?= $i ?>" class="accordion-collapse collapse">
      <div class="accordion-body">
        <p><strong>Question de <?= e($q['demandeur']) ?> le <?= formatDate($q['created_at'], 'd/m/Y H:i') ?> :</strong><br><?= nl2br(e($q['question'])) ?></p>
        <?php if ($q['reponse']): ?>
          <hr><p><strong>Réponse de <?= e($q['repondant']) ?> le <?= formatDate($q['answered_at'], 'd/m/Y H:i') ?> :</strong><br><?= nl2br(e($q['reponse'])) ?></p>
        <?php elseif (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
          <a href="answer.php?id=<?= (int)$q['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-reply"></i> Répondre</a>
        <?php else: ?>
          <p class="text-muted mb-0">En attente de réponse du conseiller.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>
<?php if (!$questions): ?><p class="text-muted text-center py-3">Aucune question pour ce filtre.</p><?php endif; ?>
</div>
<?php renderPagination($pageInfo, 'list.php'); ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
