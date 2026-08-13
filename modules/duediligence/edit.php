<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM due_diligence_items WHERE id = :id');
$stmt->execute([':id' => $id]);
$item = $stmt->fetch();
if (!$item) { http_response_code(404); die('Point de contrôle introuvable.'); }

$projectId = (int)$item['project_id'];
requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
$pageTitle = 'Modifier le point de due diligence';

$teamMembers = $pdo->prepare('SELECT u.id, u.full_name FROM users u JOIN project_team pt ON pt.user_id = u.id WHERE pt.project_id = :p ORDER BY u.full_name');
$teamMembers->execute([':p' => $projectId]);
$teamMembers = $teamMembers->fetchAll();

$errors = [];
$data = $item;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);
    $data = [
        'domaine'        => $_POST['domaine'] ?? '',
        'libelle'        => trim($_POST['libelle'] ?? ''),
        'description'    => trim($_POST['description'] ?? ''),
        'statut'         => $_POST['statut'] ?? 'a_verifier',
        'red_flag'       => isset($_POST['red_flag']) ? 1 : 0,
        'impact_estime'  => $_POST['impact_estime'] ?? '',
        'date_limite'    => $_POST['date_limite'] ?? '',
        'responsable_id' => $_POST['responsable_id'] ?? '',
    ];

    if ($data['libelle'] === '') $errors[] = 'Le libellé est obligatoire.';
    if ($data['impact_estime'] !== '' && !is_numeric($data['impact_estime'])) $errors[] = "L'impact estimé doit être un nombre.";

    if (!$errors) {
        $stmt = $pdo->prepare(
            'UPDATE due_diligence_items SET domaine=:domaine, libelle=:libelle, description=:description, statut=:statut,
             red_flag=:red_flag, impact_estime=:impact, responsable_id=:resp, date_limite=:date_limite WHERE id=:id'
        );
        $stmt->execute([
            ':domaine' => $data['domaine'], ':libelle' => $data['libelle'], ':description' => $data['description'] ?: null,
            ':statut' => $data['statut'], ':red_flag' => $data['red_flag'],
            ':impact' => $data['impact_estime'] !== '' ? $data['impact_estime'] : null,
            ':resp' => $data['responsable_id'] !== '' ? $data['responsable_id'] : null,
            ':date_limite' => $data['date_limite'] ?: null, ':id' => $id,
        ]);
        logAudit($pdo, (int)$user['id'], 'modification_due_diligence', 'due_diligence_items', $id, $data['libelle']);
        header('Location: list.php?project_id=' . $projectId);
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/_form_fields.php';
require __DIR__ . '/../../includes/footer.php';
