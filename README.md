# ClosingPoint — Projet 36 : Plateforme M&A avec Data Room virtuelle

**Master CCA — École Supérieure Polytechnique de Dakar**
Sujet : *Plateforme web de gestion de projets de fusion-acquisition (M&A) avec data room virtuelle*
Domaine : Finance — Fusions-acquisitions
Enseignant : M. Ousmane LY — Année universitaire 2025-2026

---

## 1. Présentation

**ClosingPoint** est une plateforme qui accompagne un cabinet de conseil ou une direction financière tout au long d'un
processus de fusion-acquisition : évaluation de la cible, due diligence multi-domaines, data room
virtuelle sécurisée pour l'échange documentaire, module Questions/Réponses, signature électronique
des accords de confidentialité (NDA) et suivi des offres/contre-offres jusqu'au closing.

## 2. Stack technique

| Composant  | Technologie |
|---|---|
| Serveur | Apache (XAMPP) |
| Langage serveur | PHP 8+ (procédural, PDO) |
| Base de données | MySQL / MariaDB (phpMyAdmin) |
| Front-end | HTML5, CSS3, JavaScript, Bootstrap 5 |
| Graphiques | Chart.js |
| Export PDF | DOMPDF (+ FPDI/TCPDF pour le filigrane dynamique) |
| Export Excel/CSV | CSV natif PHP (`fputcsv`) |
| Email | PHPMailer (repli automatique sur `mail()` natif) |

## 3. Prérequis

- [XAMPP](https://www.apachefriends.org/) avec Apache 8+ et MySQL/MariaDB démarrés
- PHP 8.0 ou supérieur (fourni avec XAMPP)
- [Composer](https://getcomposer.org/) (optionnel mais recommandé pour activer les exports PDF natifs,
  le filigrane dynamique et l'envoi d'email via PHPMailer — sans lui, l'application reste pleinement
  fonctionnelle grâce aux mécanismes de repli documentés ci-dessous)

## 4. Installation

### 4.1 Copier le projet dans XAMPP

Copiez (ou clonez) l'intégralité du dossier `ClosingPoint` dans le répertoire
`htdocs` de votre installation XAMPP, par exemple :

```
C:\xampp\htdocs\ClosingPoint\
```

### 4.2 Créer la base de données

1. Démarrez **Apache** et **MySQL** depuis le panneau de contrôle XAMPP.
2. Ouvrez [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
3. Onglet **Importer** → sélectionnez le fichier `sql/schema.sql` de ce projet → cliquez sur **Exécuter**.
   (Le script crée la base `closingpoint`, ses 12 tables et un jeu de données de démonstration réaliste.)

### 4.3 Configurer la connexion à la base

Le fichier `config/database.php` est préconfiguré pour l'installation par défaut de XAMPP
(hôte `127.0.0.1`, utilisateur `root`, mot de passe vide). Si votre configuration diffère, modifiez :

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'closingpoint');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Vérifiez également `config/config.php` → la constante `BASE_URL` doit correspondre au nom du dossier
placé dans `htdocs` (par défaut `/ClosingPoint`).

### 4.4 (Optionnel) Installer les dépendances Composer

Pour activer les exports PDF natifs (DOMPDF), le filigrane dynamique des PDF confidentiels (FPDI/TCPDF)
et l'envoi d'email via PHPMailer, ouvrez un terminal dans le dossier du projet et exécutez :

```bash
composer install
```

**Sans Composer**, l'application reste utilisable : les exports PDF basculent automatiquement sur une
page HTML imprimable (Ctrl+P → Enregistrer en PDF) et les emails utilisent la fonction `mail()` native
de PHP.

### 4.5 Créer le dossier de stockage des documents

Le dossier `uploads/` doit être accessible en écriture par Apache (c'est le cas par défaut sous XAMPP
Windows). Les sous-dossiers par projet (`uploads/projet_1/`, etc.) sont créés automatiquement lors du
premier dépôt de document.

### 4.6 Accéder à l'application

Ouvrez votre navigateur à l'adresse :

```
http://localhost/ClosingPoint/public/login.php
```

## 5. Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@closingpoint.sn` | `Admin@2026` |
| Conseiller M&A | `conseiller@closingpoint.sn` | `Advisor@2026` |
| Analyste (conseiller) | `analyste@closingpoint.sn` | `Advisor@2026` |
| Client / Investisseur | `client@closingpoint.sn` | `Client@2026` |

> Les mots de passe sont hachés avec `password_hash()` (bcrypt) — aucun mot de passe n'est jamais
> stocké en clair dans la base de données.

## 6. Fonctionnalités principales

- **Authentification sécurisée** : hachage bcrypt, verrouillage temporaire après 5 échecs, expiration
  de session après 20 minutes d'inactivité, jeton CSRF sur tous les formulaires.
- **3 profils différenciés** : administrateur, conseiller M&A, client/investisseur — avec droits
  d'accès aux projets restreints à l'équipe projet pour le rôle client.
- **Gestion de projets M&A** (CRUD complet) : cible, acquéreur, statut, valeur estimée, calendrier.
- **Data room virtuelle** : arborescence de dossiers, upload sécurisé (liste blanche d'extensions,
  taille limitée), téléchargement tracé (`document_access_log`), filigrane dynamique des PDF
  confidentiels (nom + horodatage) si `composer install` a été exécuté.
- **Due diligence multi-domaines** : checklist par domaine (juridique, fiscal, financier, commercial,
  RH, IT), statuts, signalement des *red flags* avec impact chiffré.
- **Module Questions / Réponses** avec notification email automatique à la réponse.
- **NDA en ligne** : signature électronique horodatée avec empreinte SHA-256 non-répudiable.
- **Valorisation multi-méthodes** : DCF (Gordon-Shapiro), multiples VE/EBITDA, ANCC.
- **Offres et contre-offres** avec suivi du statut de négociation.
- **Tableau de bord** avec KPI et graphiques Chart.js alimentés par les données réelles de la base,
  et moteur de commentaires automatiques (alertes red flags, avancement due diligence).
- **Exports** PDF (synthèse due diligence) et CSV (data room, journal d'audit).
- **Journal d'audit horodaté** de toutes les actions sensibles (connexions, créations, suppressions,
  signatures, exports).

## 7. Structure du projet

```
config/          Configuration (BDD, constantes applicatives) - accès HTTP direct bloqué
includes/        Fonctions partagées (sécurité, auth, layout) - accès HTTP direct bloqué
sql/             Script de création de la base + données de démonstration
modules/         Un sous-dossier par entité métier (projects, dataroom, duediligence, qa, ndas,
                 valuation, offers, users, audit) — chacun avec list/add/edit/delete
public/          Points d'entrée web (login, dashboard) + assets CSS/JS
exports/         Générateurs d'exports PDF/CSV
uploads/         Documents de la data room (accès direct HTTP bloqué, tout passe par download.php)
```

## 8. Sécurité mise en œuvre

- Requêtes préparées PDO partout (aucune concaténation de variable dans une requête SQL).
- Échappement systématique à l'affichage via `htmlspecialchars()` (fonction `e()`).
- Jeton CSRF vérifié sur toute action de modification (POST).
- Contrôle d'accès par rôle (`requireRole`) et par appartenance à l'équipe projet
  (`requireProjectAccess`) pour le rôle client.
- `.htaccess` interdisant l'accès HTTP direct aux dossiers `config/`, `includes/`, `sql/`, `uploads/`.
- Sessions avec cookie `HttpOnly` + `SameSite=Lax`, régénération de l'identifiant de session à la
  connexion, expiration automatique après inactivité.

## 9. Limites connues (démonstration pédagogique)

- Les exports PDF et le filigrane dynamique nécessitent `composer install` pour fonctionner
  nativement ; en son absence, des mécanismes de repli fonctionnels sont fournis (voir §4.4).
- L'envoi d'email réel nécessite un serveur SMTP configuré (`config/config.php`) ou un serveur de
  mail local ; par défaut sous XAMPP, `mail()` n'envoie rien mais ne bloque pas l'application.
