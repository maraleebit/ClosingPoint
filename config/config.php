<?php
/**
 * Configuration générale de l'application
 * Projet 36 - Plateforme M&A avec Data Room virtuelle
 */

// Fuseau horaire (Sénégal)
date_default_timezone_set('Africa/Dakar');

// Nom de l'application
define('SITE_NAME', 'ClosingPoint');

// URL de base de l'application = dossier du projet dans htdocs (racine du projet, pas /public)
// Exemple d'accès : http://localhost/ClosingPoint/public/login.php
// Adapter cette constante si le dossier est renommé ou déplacé dans htdocs.
define('BASE_URL', '/ClosingPoint');

// Sécurité des sessions
define('SESSION_TIMEOUT', 1200); // 20 minutes d'inactivité -> déconnexion automatique

// Stockage des documents de la data room (hors du répertoire public pour la sécurité)
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 20 * 1024 * 1024); // 20 Mo
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'csv', 'txt']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Paramètres d'envoi d'email (à adapter en production - PHPMailer/SMTP)
define('MAIL_FROM', 'no-reply@ma-dataroom.sn');
define('MAIL_FROM_NAME', SITE_NAME);
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');

// Affichage des erreurs : à mettre à 0 en production
error_reporting(E_ALL);
ini_set('display_errors', '1');
