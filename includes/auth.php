<?php
/**
 * Authentification : connexion, déconnexion.
 * Mots de passe hachés avec password_hash()/password_verify() (bcrypt).
 */

/**
 * Tente une connexion. Retourne l'utilisateur (sans le hash) si succès, sinon false.
 */
function attemptLogin(PDO $pdo, string $email, string $password)
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !$user['is_active']) {
        logAudit($pdo, $user['id'] ?? null, 'echec_connexion', 'users', $user['id'] ?? null, "Tentative avec l'email : $email");
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        logAudit($pdo, $user['id'], 'echec_connexion', 'users', $user['id'], 'Mot de passe invalide');
        return false;
    }

    // Régénère l'identifiant de session pour prévenir la fixation de session
    session_regenerate_id(true);

    unset($user['password_hash']);
    $_SESSION['user'] = $user;
    $_SESSION['last_activity'] = time();

    $update = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
    $update->execute([':id' => $user['id']]);

    logAudit($pdo, (int)$user['id'], 'connexion_reussie', 'users', (int)$user['id']);

    return $user;
}

/** Déconnecte proprement l'utilisateur courant. */
function logoutCurrentUser(PDO $pdo): void
{
    $user = currentUser();
    if ($user) {
        logAudit($pdo, (int)$user['id'], 'deconnexion', 'users', (int)$user['id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
