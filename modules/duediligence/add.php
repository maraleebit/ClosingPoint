<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);
$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }
$pageTitle = 'Nouveau point de due diligence — ' . $project['nom_projet'];

$teamMembers = $pdo->prepare('SELECT u.id, u.full_name FROM users u JOIN project_team pt ON pt.user_id = u.id WHERE pt.project_id = :p ORDER BY u.full_name');
$teamMembers->execute([':p' => $projectId]);
$teamMembers = $teamMembers->fetchAll();

$errors = [];
$data = [];

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

    if (!in_array($data['domaine'], ['juridique','fiscal','financier','commercial','rh','it'], true)) {
        $errors[] = 'Domaine invalide.';
    }
    if ($data['libelle'] === '') {
        $errors[] = 'Le libellé est obligatoire.';
    }
    if ($data['impact_estime'] !== '' && !is_numeric($data['impact_estime'])) {
        $errors[] = "L'impact estimé doit être un nombre.";
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO due_diligence_items (project_id, domaine, libelle, description, statut, red_flag, impact_estime, responsable_id, date_limite, created_by)
             VALUES (:p, :domaine, :libelle, :description, :statut, :red_flag, :impact, :resp, :date_limite, :u)'
        );
        $stmt->execute([
            ':p' => $projectId,
            ':domaine' => $data['domaine'],
            ':libelle' => $data['libelle'],
            ':description' => $data['description'] ?: null,
            ':statut' => $data['statut'],
            ':red_flag' => $data['red_flag'],
            ':impact' => $data['impact_estime'] !== '' ? $data['impact_estime'] : null,
            ':resp' => $data['responsable_id'] !== '' ? $data['responsable_id'] : null,
            ':date_limite' => $data['date_limite'] ?: null,
            ':u' => $user['id'],
        ]);
        $newId = (int)$pdo->lastInsertId();
        logAudit($pdo, (int)$user['id'], 'creation_due_diligence', 'due_diligence_items', $newId, $data['libelle']);
        header('Location: list.php?project_id=' . $projectId);
        exit;
    }
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/_form_fields.php';
require __DIR__ . '/../../includes/footer.php';
