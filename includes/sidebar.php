<?php
/** Menu latéral, adapté selon le rôle de l'utilisateur connecté. */
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';
$role = $user['role'] ?? 'client';

function navLink(string $href, string $icon, string $label, string $currentPath, string $matchSubstring): void
{
    $active = (strpos($currentPath, $matchSubstring) !== false) ? ' active' : '';
    echo '<li class="nav-item"><a class="nav-link' . $active . '" href="' . e($href) . '">'
        . '<i class="bi ' . $icon . '"></i> ' . e($label) . '</a></li>';
}
?>
<nav class="col-md-2 d-md-block bg-light sidebar border-end py-3">
  <ul class="nav flex-column">
    <?php navLink(BASE_URL . '/public/dashboard.php', 'bi-speedometer2', 'Tableau de bord', $currentPath, '/public/dashboard.php'); ?>
    <?php navLink(BASE_URL . '/modules/projects/list.php', 'bi-kanban', 'Projets M&A', $currentPath, '/modules/projects/'); ?>
    <?php navLink(BASE_URL . '/modules/dataroom/index.php', 'bi-folder2-open', 'Data Room', $currentPath, '/modules/dataroom/'); ?>
    <?php navLink(BASE_URL . '/modules/duediligence/list.php', 'bi-clipboard-check', 'Due Diligence', $currentPath, '/modules/duediligence/'); ?>
    <?php navLink(BASE_URL . '/modules/qa/list.php', 'bi-chat-dots', 'Questions / Réponses', $currentPath, '/modules/qa/'); ?>
    <?php navLink(BASE_URL . '/modules/ndas/list.php', 'bi-file-earmark-lock', 'NDA', $currentPath, '/modules/ndas/'); ?>
    <?php if (in_array($role, ['admin', 'conseiller'], true)): ?>
      <?php navLink(BASE_URL . '/modules/valuation/list.php', 'bi-calculator', 'Valorisation', $currentPath, '/modules/valuation/'); ?>
    <?php endif; ?>
    <?php navLink(BASE_URL . '/modules/offers/list.php', 'bi-cash-coin', 'Offres', $currentPath, '/modules/offers/'); ?>
    <?php if ($role === 'admin'): ?>
      <li><hr></li>
      <li class="sidebar-section-label">Administration</li>
      <?php navLink(BASE_URL . '/modules/users/list.php', 'bi-people', 'Utilisateurs', $currentPath, '/modules/users/'); ?>
      <?php navLink(BASE_URL . '/modules/audit/list.php', 'bi-shield-check', "Journal d'audit", $currentPath, '/modules/audit/'); ?>
    <?php endif; ?>
  </ul>
</nav>
