<?php
/**
 * Connexion à la base de données MySQL via PDO
 * Utilise systématiquement des requêtes préparées (protection anti-injection SQL)
 */

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'closingpoint');
define('DB_USER', 'root');
define('DB_PASS', ''); // Mot de passe root XAMPP par défaut : vide
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO unique (singleton) connectée à la base.
 */
function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // requêtes préparées natives
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Erreur de connexion BDD : ' . $e->getMessage());
            http_response_code(500);
            die('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré dans XAMPP '
                . 'et que la base "closingpoint" a bien été importée (voir README.md).');
        }
    }

    return $pdo;
}
