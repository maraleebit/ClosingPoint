<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireLogin();

$docId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM dataroom_documents WHERE id = :id');
$stmt->execute([':id' => $docId]);
$doc = $stmt->fetch();
if (!$doc) { http_response_code(404); die('Document introuvable.'); }

// Contrôle d'accès : le document doit appartenir à un projet auquel l'utilisateur a accès
requireProjectAccess($pdo, $user, (int)$doc['project_id']);

$filePath = UPLOAD_DIR . $doc['chemin_relatif'];
if (!is_file($filePath)) {
    http_response_code(404);
    die('Le fichier physique est introuvable sur le serveur (démonstration : déposez un fichier réel via le module Upload).');
}

// --- Traçabilité complète des consultations (data room) ---
$pdo->prepare('INSERT INTO document_access_log (document_id, user_id, action, adresse_ip) VALUES (:d, :u, "telechargement", :ip)')
    ->execute([':d' => $docId, ':u' => $user['id'], ':ip' => clientIp()]);
logAudit($pdo, (int)$user['id'], 'telechargement_document', 'dataroom_documents', $docId, $doc['nom_original']);

// --- Filigrane dynamique pour les PDF confidentiels (si librairie FPDI/TCPDF installée) ---
$streamPath = $filePath;
$isTempWatermark = false;
if ($doc['confidentiel'] && strtolower(pathinfo($doc['nom_original'], PATHINFO_EXTENSION)) === 'pdf') {
    $watermarkText = $user['full_name'] . ' — ' . date('d/m/Y H:i');
    $watermarked = generatePdfWatermarkCopy($filePath, $watermarkText);
    if ($watermarked) {
        $streamPath = $watermarked;
        $isTempWatermark = true;
    }
}

header('Content-Description: File Transfer');
header('Content-Type: ' . ($doc['type_mime'] ?: 'application/octet-stream'));
header('Content-Disposition: attachment; filename="' . basename($doc['nom_original']) . '"');
header('Content-Length: ' . filesize($streamPath));
header('X-Content-Type-Options: nosniff');
readfile($streamPath);

if ($isTempWatermark) {
    @unlink($streamPath);
}
exit;
