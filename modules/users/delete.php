<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$admin = requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); die('Méthode non autorisée.'); }
csrf_verify($_POST['csrf_token'] ?? null);

$id = (int)($_POST['id'] ?? 0);

// Désactivation (soft delete) plutôt que suppression physique : préserve l'intégrité
// référentielle avec les projets, documents et actions déjà créés par ce compte.
if ($id !== (int)$admin['id']) {
    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute([':id' => $id]);
    logAudit($pdo, (int)$admin['id'], 'desactivation_utilisateur', 'users', $id);
}

header('Location: list.php?deactivated=1');
exit;
