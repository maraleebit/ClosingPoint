<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée.');
}
csrf_verify($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT code_projet FROM ma_projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if ($project) {
    // Suppression en cascade (data room, due diligence, offres, NDA, Q&A, valorisations) via les FK ON DELETE CASCADE
    $pdo->prepare('DELETE FROM ma_projects WHERE id = :id')->execute([':id' => $id]);
    logAudit($pdo, (int)$user['id'], 'suppression_projet', 'ma_projects', $id, 'Suppression du projet ' . $project['code_projet']);
}

header('Location: list.php?deleted=1');
exit;
