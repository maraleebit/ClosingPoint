<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);
$pageTitle = 'Nouveau projet M&A';

$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify($_POST['csrf_token'] ?? null);

    $data = [
        'code_projet'         => trim($_POST['code_projet'] ?? ''),
        'nom_projet'          => trim($_POST['nom_projet'] ?? ''),
        'societe_cible'       => trim($_POST['societe_cible'] ?? ''),
        'societe_acquereur'   => trim($_POST['societe_acquereur'] ?? ''),
        'secteur'             => trim($_POST['secteur'] ?? ''),
        'statut'              => $_POST['statut'] ?? 'prospection',
        'devise'              => $_POST['devise'] ?? 'FCFA',
        'valeur_estimee'      => $_POST['valeur_estimee'] ?? '',
        'date_debut'          => $_POST['date_debut'] ?? '',
        'date_cible_closing'  => $_POST['date_cible_closing'] ?? '',
        'description'         => trim($_POST['description'] ?? ''),
    ];

    // --- Validation serveur (en complément de la validation JS côté client) ---
    if ($data['code_projet'] === '' || !preg_match('/^[A-Za-z0-9\-]{2,20}$/', $data['code_projet'])) {
        $errors[] = 'Le code projet est obligatoire (lettres, chiffres, tirets, 2 à 20 caractères).';
    }
    if ($data['nom_projet'] === '') {
        $errors[] = 'Le nom du projet est obligatoire.';
    }
    if ($data['societe_cible'] === '') {
        $errors[] = 'La société cible est obligatoire.';
    }
    if ($data['societe_acquereur'] === '') {
        $errors[] = 'La société acquéreur est obligatoire.';
    }
    if (!in_array($data['statut'], ['prospection','nda_signe','due_diligence','negociation','closing','abandonne'], true)) {
        $errors[] = 'Statut invalide.';
    }
    if ($data['valeur_estimee'] !== '' && !is_numeric($data['valeur_estimee'])) {
        $errors[] = 'La valeur estimée doit être un nombre.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO ma_projects (code_projet, nom_projet, societe_cible, societe_acquereur, secteur, statut, valeur_estimee, devise, date_debut, date_cible_closing, description, created_by)
                 VALUES (:code_projet, :nom_projet, :societe_cible, :societe_acquereur, :secteur, :statut, :valeur_estimee, :devise, :date_debut, :date_cible_closing, :description, :created_by)'
            );
            $stmt->execute([
                ':code_projet'        => $data['code_projet'],
                ':nom_projet'         => $data['nom_projet'],
                ':societe_cible'      => $data['societe_cible'],
                ':societe_acquereur'  => $data['societe_acquereur'],
                ':secteur'            => $data['secteur'] ?: null,
                ':statut'             => $data['statut'],
                ':valeur_estimee'     => $data['valeur_estimee'] !== '' ? $data['valeur_estimee'] : null,
                ':devise'             => $data['devise'],
                ':date_debut'         => $data['date_debut'] ?: null,
                ':date_cible_closing' => $data['date_cible_closing'] ?: null,
                ':description'        => $data['description'] ?: null,
                ':created_by'         => $user['id'],
            ]);
            $newId = (int)$pdo->lastInsertId();
            logAudit($pdo, (int)$user['id'], 'creation_projet', 'ma_projects', $newId, 'Création du projet ' . $data['code_projet']);

            // Ajoute automatiquement le créateur à l'équipe projet en tant que chef de projet
            $pdo->prepare('INSERT INTO project_team (project_id, user_id, role_projet) VALUES (:p, :u, :r)')
                ->execute([':p' => $newId, ':u' => $user['id'], ':r' => 'chef_projet']);

            header('Location: view.php?id=' . $newId . '&created=1');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $errors[] = 'Ce code projet existe déjà. Merci d\'en choisir un autre.';
            } else {
                error_log('Erreur création projet : ' . $e->getMessage());
                $errors[] = 'Une erreur technique est survenue. Veuillez réessayer.';
            }
        }
    }
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/_form_fields.php';
require __DIR__ . '/../../includes/footer.php';
