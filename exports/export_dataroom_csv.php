<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = requireLogin();

$projectId = (int)($_GET['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);

$project = $pdo->prepare('SELECT code_projet FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }

$stmt = $pdo->prepare(
    'SELECT d.nom_original, d.categorie, d.confidentiel, d.taille_octets, u.full_name AS uploader, d.created_at
     FROM dataroom_documents d JOIN users u ON u.id = d.uploaded_by
     WHERE d.project_id = :p ORDER BY d.created_at DESC'
);
$stmt->execute([':p' => $projectId]);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="dataroom_' . $project['code_projet'] . '_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF");
fputcsv($out, ['Document', 'Catégorie', 'Confidentiel', 'Taille (octets)', 'Déposé par', 'Date de dépôt'], ';');
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['nom_original'], $row['categorie'], $row['confidentiel'] ? 'Oui' : 'Non',
        $row['taille_octets'], $row['uploader'], $row['created_at'],
    ], ';');
}
fclose($out);

logAudit($pdo, (int)$user['id'], 'export_csv_dataroom', 'dataroom_documents', $projectId);
exit;
