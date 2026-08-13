<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Méthode non autorisée.'); }
csrf_verify($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
$projectId = (int)($_POST['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);

$stmt = $pdo->prepare('SELECT libelle FROM due_diligence_items WHERE id = :id AND project_id = :p');
$stmt->execute([':id' => $id, ':p' => $projectId]);
$item = $stmt->fetch();

if ($item) {
    $pdo->prepare('DELETE FROM due_diligence_items WHERE id = :id')->execute([':id' => $id]);
    logAudit($pdo, (int)$user['id'], 'suppression_due_diligence', 'due_diligence_items', $id, $item['libelle']);
}

header('Location: list.php?project_id=' . $projectId);
exit;
