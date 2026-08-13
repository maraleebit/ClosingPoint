<?php
/**
 * Fonctions de sécurité transverses :
 * - Protection CSRF
 * - Échappement anti-XSS
 * - Contrôle d'accès (authentification + rôles)
 * - Journal d'audit horodaté
 */

/** Génère (ou retourne) le jeton CSRF de la session en cours. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Affiche le champ caché CSRF à insérer dans tous les formulaires POST. */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Vérifie le jeton CSRF envoyé par un formulaire. Arrête l'exécution si invalide. */
function csrf_verify(?string $token): void
{
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die('Jeton de sécurité invalide ou expiré (CSRF). Veuillez rafraîchir la page et réessayer.');
    }
}

/** Échappement systématique pour tout affichage de donnée utilisateur (anti-XSS). */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Retourne l'utilisateur connecté (tableau associatif) ou null. */
function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Bloque l'accès à la page si l'utilisateur n'est pas authentifié. */
function requireLogin(): array
{
    $user = currentUser();
    if (!$user) {
        header('Location: ' . BASE_URL . '/public/login.php');
        exit;
    }
    return $user;
}

/**
 * Bloque l'accès à la page si l'utilisateur n'a pas l'un des rôles autorisés.
 * @param string[] $roles Rôles autorisés, ex: ['admin','conseiller']
 */
function requireRole(array $roles): array
{
    $user = requireLogin();
    if (!in_array($user['role'], $roles, true)) {
        http_response_code(403);
        die('Accès refusé : votre profil (' . e($user['role']) . ') ne dispose pas des droits requis pour cette page.');
    }
    return $user;
}

/**
 * Contrôle d'accès différencié par projet : un client (investisseur/cible) ne peut
 * consulter que les projets où il figure dans l'équipe projet. Admin et conseiller
 * ont accès à l'ensemble du portefeuille de projets M&A.
 */
function userCanAccessProject(PDO $pdo, array $user, int $projectId): bool
{
    if (in_array($user['role'], ['admin', 'conseiller'], true)) {
        return true;
    }
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM project_team WHERE project_id = :p AND user_id = :u');
    $stmt->execute([':p' => $projectId, ':u' => $user['id']]);
    return (int)$stmt->fetchColumn() > 0;
}

/** Bloque l'accès si l'utilisateur courant n'a pas le droit de voir ce projet. */
function requireProjectAccess(PDO $pdo, array $user, int $projectId): void
{
    if (!userCanAccessProject($pdo, $user, $projectId)) {
        http_response_code(403);
        die("Accès refusé : vous ne faites pas partie de l'équipe de ce projet M&A.");
    }
}

/** Adresse IP du client (best effort, tient compte des proxys courants). */
function clientIp(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'inconnue';
}

/**
 * Enregistre une action sensible dans le journal d'audit (audit trail horodaté).
 */
function logAudit(PDO $pdo, ?int $userId, string $action, ?string $table = null, ?int $rowId = null, ?string $details = null): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_log (user_id, action, table_concernee, ligne_id, details, adresse_ip)
             VALUES (:user_id, :action, :table_concernee, :ligne_id, :details, :ip)'
        );
        $stmt->execute([
            ':user_id'          => $userId,
            ':action'           => $action,
            ':table_concernee'  => $table,
            ':ligne_id'         => $rowId,
            ':details'          => $details ? mb_substr($details, 0, 500) : null,
            ':ip'               => clientIp(),
        ]);
    } catch (Throwable $e) {
        error_log('Erreur audit_log : ' . $e->getMessage());
    }
}
