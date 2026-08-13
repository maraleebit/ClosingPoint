<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
$user = requireRole(['admin', 'conseiller']);
$pageTitle = 'Modifier le projet';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ma_projects WHERE id = :id');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();
if (!$project) {
    http_response_code(404);
    die('Projet introuvable.');
}

$errors = [];
$data = $project;

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
    if ($data['valeur_estimee'] !== '' && !is_numeric($data['valeur_estimee'])) {
        $errors[] = 'La valeur estimée doit être un nombre.';
    }

    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE ma_projects SET code_projet=:code_projet, nom_projet=:nom_projet, societe_cible=:societe_cible,
                 societe_acquereur=:societe_acquereur, secteur=:secteur, statut=:statut, valeur_estimee=:valeur_estimee,
                 devise=:devise, date_debut=:date_debut, date_cible_closing=:date_cible_closing, description=:description
                 WHERE id=:id'
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
                ':id'                 => $id,
            ]);
            logAudit($pdo, (int)$user['id'], 'modification_projet', 'ma_projects', $id, 'Mise à jour du projet ' . $data['code_projet']);
            header('Location: view.php?id=' . $id . '&updated=1');
            exit;
        } catch (PDOException $e) {
            $errors[] = ($e->getCode() === '23000')
                ? 'Ce code projet existe déjà.'
                : 'Une erreur technique est survenue.';
        }
    }
}

require __DIR__ . '/../../includes/header.php';
require __DIR__ . '/_form_fields.php';
require __DIR__ . '/../../includes/footer.php';
