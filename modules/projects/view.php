<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();
if (!$project) {
    http_response_code(404);
    die('Projet introuvable.');
}
$pageTitle = 'Projet : ' . $project['nom_projet'];

// --- Ajout d'un membre à l'équipe projet (admin / conseiller) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member']) && in_array($user['role'], ['admin', 'conseiller'], true)) {
    csrf_verify($_POST['csrf_token'] ?? null);
    $memberId = (int)($_POST['user_id'] ?? 0);
    $roleProjet = $_POST['role_projet'] ?? 'analyste';
    try {
        $pdo->prepare('INSERT INTO project_team (project_id, user_id, role_projet) VALUES (:p, :u, :r)')
            ->execute([':p' => $id, ':u' => $memberId, ':r' => $roleProjet]);
        logAudit($pdo, (int)$user['id'], 'ajout_membre_equipe', 'project_team', $memberId, "Ajout à l'équipe du projet #$id");
    } catch (PDOException $e) {
        // Doublon (contrainte unique) ignoré silencieusement
    }
    header('Location: view.php?id=' . $id);
    exit;
}

$team = $pdo->prepare(
    'SELECT pt.*, u.full_name, u.email, u.role AS user_role
     FROM project_team pt JOIN users u ON u.id = pt.user_id WHERE pt.project_id = :id ORDER BY pt.date_ajout'
);
$team->execute([':id' => $id]);
$team = $team->fetchAll();

$allUsers = $pdo->query('SELECT id, full_name, email, role FROM users WHERE is_active = 1 ORDER BY full_name')->fetchAll();

// Compteurs pour les onglets/liens rapides (requêtes préparées)
function countForProject(PDO $pdo, string $table, int $projectId, string $extraWhere = ''): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE project_id = :id $extraWhere");
    $stmt->execute([':id' => $projectId]);
    return (int)$stmt->fetchColumn();
}
$counts = [
    'documents'  => countForProject($pdo, 'dataroom_documents', $id),
    'dd'         => countForProject($pdo, 'due_diligence_items', $id),
    'dd_alertes' => countForProject($pdo, 'due_diligence_items', $id, 'AND red_flag = 1'),
    'qa'         => countForProject($pdo, 'qa_questions', $id),
    'offers'     => countForProject($pdo, 'offers', $id),
    'ndas'       => countForProject($pdo, 'ndas', $id),
];

require __DIR__ . '/../../includes/header.php';
?>

<?php if (isset($_GET['created'])): ?><div class="alert alert-success">Projet créé avec succès.</div><?php endif; ?>
<?php if (isset($_GET['updated'])): ?><div class="alert alert-success">Projet mis à jour.</div><?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-kanban"></i> <?= e($project['code_projet']) ?> — <?= e($project['nom_projet']) ?></span>
        <?php echo projectStatusBadge($project['statut']); ?>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Société cible</dt><dd class="col-sm-8"><?= e($project['societe_cible']) ?></dd>
          <dt class="col-sm-4">Société acquéreur</dt><dd class="col-sm-8"><?= e($project['societe_acquereur']) ?></dd>
          <dt class="col-sm-4">Secteur</dt><dd class="col-sm-8"><?= e($project['secteur'] ?: '—') ?></dd>
          <dt class="col-sm-4">Valeur estimée</dt><dd class="col-sm-8"><?= formatMoney($project['valeur_estimee'], $project['devise']) ?></dd>
          <dt class="col-sm-4">Date de début</dt><dd class="col-sm-8"><?= formatDate($project['date_debut']) ?></dd>
          <dt class="col-sm-4">Closing cible</dt><dd class="col-sm-8"><?= formatDate($project['date_cible_closing']) ?></dd>
          <dt class="col-sm-4">Description</dt><dd class="col-sm-8"><?= nl2br(e($project['description'] ?: '—')) ?></dd>
        </dl>
      </div>
      <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
      <div class="card-footer">
        <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i> Modifier</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Accès rapide</div>
      <div class="list-group list-group-flush">
        <a href="<?= e(BASE_URL) ?>/modules/dataroom/index.php?project_id=<?= $id ?>" class="list-group-item d-flex justify-content-between">
          <span><i class="bi bi-folder2-open"></i> Data room</span><span class="badge text-bg-secondary"><?= $counts['documents'] ?></span>
        </a>
        <a href="<?= e(BASE_URL) ?>/modules/duediligence/list.php?project_id=<?= $id ?>" class="list-group-item d-flex justify-content-between">
          <span><i class="bi bi-clipboard-check"></i> Due diligence</span>
          <span><span class="badge text-bg-secondary"><?= $counts['dd'] ?></span> <span class="badge text-bg-danger"><?= $counts['dd_alertes'] ?> red flag</span></span>
        </a>
        <a href="<?= e(BASE_URL) ?>/modules/qa/list.php?project_id=<?= $id ?>" class="list-group-item d-flex justify-content-between">
          <span><i class="bi bi-chat-dots"></i> Q&amp;A</span><span class="badge text-bg-secondary"><?= $counts['qa'] ?></span>
        </a>
        <a href="<?= e(BASE_URL) ?>/modules/ndas/list.php?project_id=<?= $id ?>" class="list-group-item d-flex justify-content-between">
          <span><i class="bi bi-file-earmark-lock"></i> NDA</span><span class="badge text-bg-secondary"><?= $counts['ndas'] ?></span>
        </a>
        <a href="<?= e(BASE_URL) ?>/modules/valuation/list.php?project_id=<?= $id ?>" class="list-group-item"><i class="bi bi-calculator"></i> Valorisation</a>
        <a href="<?= e(BASE_URL) ?>/modules/offers/list.php?project_id=<?= $id ?>" class="list-group-item d-flex justify-content-between">
          <span><i class="bi bi-cash-coin"></i> Offres</span><span class="badge text-bg-secondary"><?= $counts['offers'] ?></span>
        </a>
      </div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">Équipe projet (droits d'accès différenciés)</div>
  <div class="card-body">
    <table class="table table-sm">
      <thead><tr><th>Nom</th><th>Email</th><th>Rôle plateforme</th><th>Rôle sur le projet</th><th>Ajouté le</th></tr></thead>
      <tbody>
        <?php foreach ($team as $t): ?>
          <tr>
            <td><?= e($t['full_name']) ?></td>
            <td><?= e($t['email']) ?></td>
            <td><?= e(roleLabel($t['user_role'])) ?></td>
            <td><?= e(str_replace('_', ' ', $t['role_projet'])) ?></td>
            <td><?= formatDate($t['date_ajout'], 'd/m/Y H:i') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (in_array($user['role'], ['admin', 'conseiller'], true)): ?>
    <form method="post" class="row g-2 mt-2">
      <?php csrf_field(); ?>
      <input type="hidden" name="add_member" value="1">
      <div class="col-auto">
        <select name="user_id" class="form-select" required>
          <option value="">— Choisir un utilisateur —</option>
          <?php foreach ($allUsers as $u): ?>
            <option value="<?= (int)$u['id'] ?>"><?= e($u['full_name']) ?> (<?= e(roleLabel($u['role'])) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <select name="role_projet" class="form-select">
          <option value="chef_projet">Chef de projet</option>
          <option value="analyste">Analyste</option>
          <option value="conseiller_juridique">Conseiller juridique</option>
          <option value="conseiller_financier">Conseiller financier</option>
          <option value="observateur_cible">Observateur (cible)</option>
        </select>
      </div>
      <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-person-plus"></i> Ajouter à l'équipe</button></div>
    </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
