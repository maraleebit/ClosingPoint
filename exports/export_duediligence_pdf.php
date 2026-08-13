<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = requireLogin();

$projectId = (int)($_GET['project_id'] ?? 0);
requireProjectAccess($pdo, $user, $projectId);

$project = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$project->execute([':id' => $projectId]);
$project = $project->fetch();
if (!$project) { http_response_code(404); die('Projet introuvable.'); }

$items = $pdo->prepare(
    'SELECT dd.*, u.full_name AS responsable FROM due_diligence_items dd
     LEFT JOIN users u ON u.id = dd.responsable_id WHERE dd.project_id = :p ORDER BY dd.red_flag DESC, dd.domaine'
);
$items->execute([':p' => $projectId]);
$items = $items->fetchAll();

$totalItems = count($items);
$valides = count(array_filter($items, fn($i) => $i['statut'] === 'valide'));
$redFlags = array_filter($items, fn($i) => (bool)$i['red_flag']);
$impactTotal = array_sum(array_column($redFlags, 'impact_estime'));

logAudit($pdo, (int)$user['id'], 'export_pdf_due_diligence', 'due_diligence_items', $projectId);

// --- Construction du contenu HTML du rapport (utilisé par DOMPDF ou en impression navigateur) ---
ob_start();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Rapport de due diligence</title>
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #222; }
  h1 { font-size: 18px; color: #0d3b66; } h2 { font-size: 14px; margin-top: 20px; border-bottom: 1px solid #ccc; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th, td { border: 1px solid #ccc; padding: 5px 7px; font-size: 11px; text-align: left; }
  th { background: #0d3b66; color: #fff; }
  .redflag { background: #fdecea; }
  .kpi { display:inline-block; width: 23%; text-align:center; border:1px solid #ccc; padding:8px; margin-right:1%; }
</style></head><body>

<h1>Rapport de synthèse — Due Diligence</h1>
<p><strong>Projet :</strong> <?= e($project['code_projet']) ?> — <?= e($project['nom_projet']) ?><br>
<strong>Cible :</strong> <?= e($project['societe_cible']) ?> &nbsp; <strong>Acquéreur :</strong> <?= e($project['societe_acquereur']) ?><br>
<strong>Édité le :</strong> <?= date('d/m/Y H:i') ?> par <?= e($user['full_name']) ?></p>

<div class="kpi"><strong><?= $totalItems ?></strong><br>points de contrôle</div>
<div class="kpi"><strong><?= $totalItems ? round($valides / $totalItems * 100) : 0 ?>%</strong><br>validés</div>
<div class="kpi"><strong><?= count($redFlags) ?></strong><br>red flags</div>
<div class="kpi"><strong><?= formatMoney($impactTotal, $project['devise']) ?></strong><br>impact estimé cumulé</div>

<h2>Détail par point de contrôle</h2>
<table>
<thead><tr><th>Domaine</th><th>Libellé</th><th>Statut</th><th>Red flag</th><th>Impact estimé</th><th>Responsable</th><th>Échéance</th></tr></thead>
<tbody>
<?php foreach ($items as $it): ?>
  <tr class="<?= $it['red_flag'] ? 'redflag' : '' ?>">
    <td><?= e(domaineLabel($it['domaine'])) ?></td>
    <td><?= e($it['libelle']) ?></td>
    <td><?= e(ucfirst(str_replace('_',' ',$it['statut']))) ?></td>
    <td><?= $it['red_flag'] ? 'OUI' : 'Non' ?></td>
    <td><?= $it['impact_estime'] ? formatMoney($it['impact_estime'], $project['devise']) : '—' ?></td>
    <td><?= e($it['responsable'] ?: '—') ?></td>
    <td><?= formatDate($it['date_limite']) ?></td>
  </tr>
<?php endforeach; ?>
</tbody>
</table>

</body></html>
<?php
$html = ob_get_clean();

// --- Génération réelle du PDF si DOMPDF est installé via composer ---
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('due_diligence_' . $project['code_projet'] . '.pdf', ['Attachment' => true]);
        exit;
    }
}

// --- Repli : page HTML imprimable (Ctrl+P > Enregistrer en PDF) si DOMPDF n'est pas installé ---
echo $html;
echo '<p style="font-family:sans-serif;color:#900;"><em>Note : la librairie DOMPDF n\'est pas installée '
   . '(exécutez "composer install" — voir README.md) : ce rapport est affiché en HTML imprimable. '
   . 'Utilisez Ctrl+P puis "Enregistrer en PDF" pour obtenir le fichier PDF.</em></p>'
   . '<script>window.onload = () => window.print();</script>';
exit;
