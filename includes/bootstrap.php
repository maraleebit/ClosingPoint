<?php
/**
 * Fichier d'amorçage à inclure en tout premier sur chaque page protégée.
 * Démarre la session de façon sécurisée, charge la configuration, la connexion
 * BDD et gère l'expiration automatique de session (déconnexion après inactivité).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,   // inaccessible en JavaScript (anti-vol de cookie)
        'samesite' => 'Lax',  // limite les attaques CSRF cross-site
    ]);
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$pdo = getPDO();

// --- Expiration de session après inactivité ---
if (isset($_SESSION['user'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        $expiredUserId = $_SESSION['user']['id'] ?? null;
        logAudit($pdo, $expiredUserId, 'session_expiree', 'users', $expiredUserId, 'Déconnexion automatique après inactivité');
        $_SESSION = [];
        session_destroy();
        header('Location: ' . BASE_URL . '/public/login.php?expired=1');
        exit;
    }
    $_SESSION['last_activity'] = time();
}

// Jeton CSRF systématiquement disponible pour les formulaires
csrf_token();
