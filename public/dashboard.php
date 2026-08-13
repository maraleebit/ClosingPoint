<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$user = requireLogin();
$pageTitle = 'Tableau de bord';

// --- KPI 1 : projets actifs et valeur du pipeline ---
$kpiProjets = $pdo->query(
    "SELECT COUNT(*) AS nb, COALESCE(SUM(valeur_estimee),0) AS valeur_totale
     FROM ma_projects WHERE statut <> 'abandonne'"
)->fetch();

// --- KPI 2 : due diligence ---
$kpiDD = $pdo->query(
    "SELECT
        COUNT(*) AS total,
        SUM(statut = 'valide') AS valides,
        SUM(red_flag = 1 AND statut <> 'valide') AS red_flags_ouverts
     FROM due_diligence_items"
)->fetch();
$tauxAvancementDD = $kpiDD['total'] > 0 ? round(($kpiDD['valides'] / $kpiDD['total']) * 100, 1) : 0;

// --- KPI 3 : Q&A ouvertes ---
$kpiQA = $pdo->query("SELECT COUNT(*) AS nb FROM qa_questions WHERE statut = 'ouverte'")->fetch();

// --- KPI 4 : documents data room ---
$kpiDocs = $pdo->query("SELECT COUNT(*) AS nb FROM dataroom_documents")->fetch();

// --- Graphique 1 : répartition des projets par statut ---
$statutRows = $pdo->query(
    "SELECT statut, COUNT(*) AS nb FROM ma_projects GROUP BY statut"
)->fetchAll();
$statutLabels = [];
$statutData = [];
foreach ($statutRows as $row) {
    $statutLabels[] = domaineLabel($row['statut']) !== $row['statut'] ? domaineLabel($row['statut']) : ucfirst(str_replace('_', ' ', $row['statut']));
    $statutData[] = (int)$row['nb'];
}

// --- Graphique 2 : due diligence par domaine et statut (empilé) ---
$ddRows = $pdo->query(
    "SELECT domaine, statut, COUNT(*) AS nb FROM due_diligence_items GROUP BY domaine, statut"
)->fetchAll();
$domaines = ['juridique', 'fiscal', 'financier', 'commercial', 'rh', 'it'];
$statuts = ['a_verifier', 'en_cours', 'valide', 'alerte'];
$ddMatrix = array_fill_keys($statuts, array_fill_keys($domaines, 0));
foreach ($ddRows as $row) {
    if (isset($ddMatrix[$row['statut']][$row['domaine']])) {
        $ddMatrix[$row['statut']][$row['domaine']] = (int)$row['nb'];
    }
}

// --- Moteur de commentaires automatiques (règles métier simples) ---
$commentaires = [];
if ((int)$kpiDD['red_flags_ouverts'] > 0) {
    $commentaires[] = ['type' => 'danger', 'texte' => (int)$kpiDD['red_flags_ouverts'] . ' red flag(s) de due diligence sont encore ouverts : une revue immédiate par le chef de projet est recommandée.'];
}
if ($tauxAvancementDD < 50) {
    $commentaires[] = ['type' => 'warning', 'texte' => 'La due diligence n\'est avancée qu\'à ' . $tauxAvancementDD . '% : le calendrier de closing pourrait être à risque.'];
} elseif ($tauxAvancementDD >= 80) {
    $commentaires[] = ['type' => 'success', 'texte' => 'La due diligence est avancée à ' . $tauxAvancementDD . '% : le dossier progresse conformément au calendrier.'];
}
if ((int)$kpiQA['nb'] > 0) {
    $commentaires[] = ['type' => 'info', 'texte' => (int)$kpiQA['nb'] . ' question(s) du module Q&A attendent une réponse.'];
}
if (empty($commentaires)) {
    $commentaires[] = ['type' => 'success', 'texte' => 'Aucune alerte détectée : l\'ensemble des portefeuilles de projets M&A est sous contrôle.'];
}

require __DIR__ . '/../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-md-4 col-lg-2">
    <div class="card kpi-card border-primary">
      <div class="card-body">
        <div class="text-muted small">Projets actifs</div>
        <div class="fs-3 fw-bold"><?= (int)$kpiProjets['nb'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-lg-3">
    <div class="card kpi-card border-success">
      <div class="card-body">
        <div class="text-muted small">Valeur du pipeline</div>
        <div class="fs-5 fw-bold"><?= formatMoney($kpiProjets['valeur_totale']) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-lg-2">
    <div class="card kpi-card border-danger">
      <div class="card-body">
        <div class="text-muted small">Red flags ouverts</div>
        <div class="fs-3 fw-bold text-danger"><?= (int)$kpiDD['red_flags_ouverts'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-lg-2">
    <div class="card kpi-card border-warning">
      <div class="card-body">
        <div class="text-muted small">Avancement Due Diligence</div>
        <div class="fs-3 fw-bold"><?= $tauxAvancementDD ?>%</div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-lg-2">
    <div class="card kpi-card border-info">
      <div class="card-body">
        <div class="text-muted small">Questions ouvertes (Q&amp;A)</div>
        <div class="fs-3 fw-bold"><?= (int)$kpiQA['nb'] ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4 col-lg-1">
    <div class="card kpi-card border-secondary">
      <div class="card-body">
        <div class="text-muted small">Documents</div>
        <div class="fs-3 fw-bold"><?= (int)$kpiDocs['nb'] ?></div>
      </div>
    </div>
  </div>
</div>

<div class="mb-4">
  <?php foreach ($commentaires as $c): ?>
    <div class="alert alert-<?= e($c['type']) ?> py-2"><i class="bi bi-info-circle"></i> <?= e($c['texte']) ?></div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Répartition des projets par statut</div>
      <div class="card-body"><canvas id="chartStatutProjets" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Due diligence — statut par domaine</div>
      <div class="card-body"><canvas id="chartDueDiligence" height="220"></canvas></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const statutLabels = <?= json_encode($statutLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const statutData = <?= json_encode($statutData) ?>;

new Chart(document.getElementById('chartStatutProjets'), {
  type: 'pie',
  data: {
    labels: statutLabels,
    datasets: [{ data: statutData, backgroundColor: ['#6c757d','#0dcaf0','#ffc107','#0d6efd','#198754','#dc3545'] }]
  },
  options: { responsive: true }
});

const domaineLabels = <?= json_encode(array_map('domaineLabel', $domaines), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const ddMatrix = <?= json_encode($ddMatrix) ?>;
const ddDatasets = [
  { label: 'À vérifier', backgroundColor: '#6c757d', data: Object.values(ddMatrix['a_verifier']) },
  { label: 'En cours', backgroundColor: '#ffc107', data: Object.values(ddMatrix['en_cours']) },
  { label: 'Validé', backgroundColor: '#198754', data: Object.values(ddMatrix['valide']) },
  { label: 'Alerte', backgroundColor: '#dc3545', data: Object.values(ddMatrix['alerte']) }
];

new Chart(document.getElementById('chartDueDiligence'), {
  type: 'bar',
  data: { labels: domaineLabels, datasets: ddDatasets },
  options: {
    responsive: true,
    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
