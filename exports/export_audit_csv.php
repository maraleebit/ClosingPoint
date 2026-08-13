<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = requireRole(['admin']);

$search = trim($_GET['q'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    // Un nom de paramètre distinct par occurrence (PDO natif rejette la réutilisation d'un même :q).
    $where[] = '(a.action LIKE :q1 OR a.details LIKE :q2 OR u.full_name LIKE :q3)';
    $like = '%' . $search . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$stmt = $pdo->prepare(
    "SELECT a.created_at, u.full_name, a.action, a.table_concernee, a.ligne_id, a.details, a.adresse_ip
     FROM audit_log a LEFT JOIN users u ON u.id = a.user_id $whereSql ORDER BY a.created_at DESC"
);
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="journal_audit_' . date('Y-m-d_His') . '.csv"');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour compatibilité Excel
fputcsv($out, ['Date/heure', 'Utilisateur', 'Action', 'Table', 'ID ligne', 'Détails', 'Adresse IP'], ';');
while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['created_at'], $row['full_name'] ?: 'Système', $row['action'],
        $row['table_concernee'], $row['ligne_id'], $row['details'], $row['adresse_ip'],
    ], ';');
}
fclose($out);

logAudit($pdo, (int)$user['id'], 'export_csv_audit', 'audit_log');
exit;
