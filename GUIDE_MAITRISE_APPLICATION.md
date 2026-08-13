# Guide de maîtrise — ClosingPoint

**Objectif de ce document :** te permettre d'expliquer CHAQUE écran, CHAQUE bouton et CHAQUE
calcul de l'application comme si tu l'avais codée toi-même — pas une liste de fonctionnalités,
mais le détail exact de ce qui se passe dans le code à chaque clic. Chaque module se termine par
des **« pièges de jury »** : les questions qu'un enseignant pose précisément pour vérifier que tu
comprends vraiment ton propre code, pas juste ce qu'il affiche à l'écran.

Lis ce document dans l'ordre — il suit le parcours naturel d'un utilisateur dans l'application.
Pour la soutenance/vidéo, tu n'as pas besoin de tout réciter : sache où chercher la réponse si le
jury creuse un point précis.

---

## 0. Ce qu'il faut savoir avant tout : l'architecture générale

- **Aucun framework** : PHP procédural pur, chaque fichier `.php` d'un module est à la fois
  contrôleur et vue.
- **`includes/bootstrap.php`** est inclus en première ligne de CHAQUE page protégée
  (`require_once __DIR__ . '/../includes/bootstrap.php'` ou équivalent selon la profondeur). Il
  fait, dans l'ordre :
  1. Démarre la session avec des cookies sécurisés (`httponly`, `samesite=Lax`, durée de vie = 0
     → cookie de session, supprimé à la fermeture du navigateur).
  2. Charge la config, la connexion PDO, et tous les fichiers `includes/*.php`.
  3. Vérifie l'**expiration de session** : si `time() - $_SESSION['last_activity'] > 1200`
     (20 minutes), déconnecte automatiquement, journalise `session_expiree`, redirige vers
     `login.php?expired=1`. Sinon rafraîchit `last_activity` — **c'est une fenêtre glissante**,
     pas un délai fixe : tant que tu cliques au moins une fois toutes les 20 minutes, tu ne sois
     jamais déconnectée automatiquement, même après plusieurs heures.
  4. Génère un jeton CSRF s'il n'existe pas encore en session.
- **Connexion base de données** (`config/database.php`) : PDO avec
  `EMULATE_PREPARES => false` — les requêtes préparées sont exécutées **nativement par MySQL**
  (pas simulées côté PHP), ce qui est la protection anti-injection SQL la plus robuste possible
  avec PDO.
- **3 rôles** stockés en ENUM dans `users.role` : `admin`, `conseiller`, `client`.

### Les fonctions de sécurité transversales (`includes/security.php`) — à connaître par cœur

| Fonction | Rôle exact |
|---|---|
| `requireLogin()` | Redirige vers `login.php` si personne n'est connecté. Retourne l'utilisateur sinon. |
| `requireRole(array $roles)` | Appelle `requireLogin()`, puis vérifie que le rôle de l'utilisateur est dans la liste passée (`in_array(..., true)` — comparaison stricte). Sinon : HTTP 403 + message « Accès refusé : votre profil (X) ne dispose pas des droits requis pour cette page. » |
| `userCanAccessProject($pdo,$user,$projectId)` | `true` immédiatement si rôle `admin`/`conseiller` (accès à tout le portefeuille). Si `client` : vérifie sa présence dans `project_team` pour ce projet précis (`SELECT COUNT(*) FROM project_team WHERE project_id=:p AND user_id=:u`). |
| `requireProjectAccess($pdo,$user,$projectId)` | Appelle la fonction précédente ; si `false` → 403 « vous ne faites pas partie de l'équipe de ce projet M&A. » |
| `csrf_token()` | Génère (si absent) `bin2hex(random_bytes(32))` — 256 bits d'aléa — et le stocke en session. **Un seul jeton par session**, pas un jeton par formulaire. |
| `csrf_field()` | Affiche `<input type="hidden" name="csrf_token" value="...">`. |
| `csrf_verify($token)` | Compare avec `hash_equals()` (résistant aux attaques par mesure de temps). Échec → HTTP 403 + « Jeton de sécurité invalide ou expiré (CSRF). » |
| `e($value)` | `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` — anti-XSS, appelée à CHAQUE affichage de donnée utilisateur. |
| `clientIp()` | `HTTP_X_FORWARDED_FOR` sinon `REMOTE_ADDR` sinon `"inconnue"`. **Piège** : cet en-tête est falsifiable côté client si l'appli n'est pas derrière un proxy de confiance — à assumer si le jury questionne la fiabilité de la preuve d'audit. |
| `logAudit($pdo,$userId,$action,$table,$rowId,$details)` | Insère dans `audit_log`, tronque `$details` à 500 caractères, capture l'IP. Toujours dans un `try/catch` : **une erreur d'audit ne casse jamais une action métier**. |

**Piège de jury n°1 — incohérence volontaire à assumer** : `modules/projects/view.php`
n'appelle que `requireLogin()`, **pas** `requireProjectAccess()`. Un client authentifié peut donc
consulter la fiche de n'importe quel projet en changeant l'`id` dans l'URL, même s'il n'est pas
dans l'équipe — contrairement à la data room qui vérifie systématiquement. Si le jury le
remarque : reconnais-le comme un axe d'amélioration identifié, ne le nie pas.

**Piège de jury n°2** : le jeton CSRF est unique **par session**, pas par formulaire — pas de
rotation après usage. Un choix pédagogique assumé (simplicité), pas une négligence : un jeton
volé reste valable jusqu'à déconnexion/expiration, mais il faudrait d'abord voler la session pour
le récupérer.

---

## 1. Connexion / Déconnexion

### 1.1 `public/login.php`

- Page publique. Si déjà connecté → redirection immédiate vers le tableau de bord.
- **Anti brute-force** — stocké en session (pas en base, pas par IP) :
  - `$_SESSION['login_attempts']` compte les échecs consécutifs.
  - À partir de **5 échecs**, `$_SESSION['login_lock_until'] = time() + 60` → verrouillage de
    **60 secondes**, message « Trop de tentatives échouées. Réessayez dans 60 secondes. », bouton
    de connexion désactivé (`disabled`).
  - **Piège** : le compteur vit en session. Un attaquant qui supprime son cookie (ou ouvre un
    onglet privé) repart à zéro — ce n'est pas un verrou par compte ni par IP, juste un frein
    lié à la session/navigateur courant.
- **Validation à la soumission** (POST) :
  1. `csrf_verify()`.
  2. Champs vides → « Veuillez renseigner votre email et votre mot de passe. »
  3. Email mal formé (`filter_var(..., FILTER_VALIDATE_EMAIL)`) → « Adresse email invalide. »
  4. Sinon `attemptLogin()` (voir 1.2).
- Bandeau d'avertissement si arrivée via `?expired=1` (session expirée).
- Côté client : `email` (`type=email`, `required`, `autofocus`), `password`
  (`type=password`, `required`, `minlength="6"` — doublé en JS par `validation.js` avec un
  message personnalisé via `setCustomValidity`).

### 1.2 `includes/auth.php` — `attemptLogin($pdo, $email, $password)`

1. `SELECT * FROM users WHERE email = :email LIMIT 1`.
2. Si aucun utilisateur **ou** `!$user['is_active']` → log `echec_connexion`, retour `false`.
   **Un compte désactivé est traité exactement comme un mauvais mot de passe** — aucune fuite
   d'information sur l'existence ou l'état du compte (bonne pratique de sécurité).
3. `password_verify($password, $user['password_hash'])` — bcrypt. Échec → log
   `echec_connexion` (détail « Mot de passe invalide »), retour `false`.
4. Succès :
   - `session_regenerate_id(true)` — nouvel identifiant de session à chaque connexion, protection
     contre la fixation de session.
   - `unset($user['password_hash'])` **avant** de stocker en session — le hash ne doit jamais
     transiter en session.
   - `UPDATE users SET last_login = NOW()`.
   - log `connexion_reussie`.

### 1.3 `public/logout.php`

`logoutCurrentUser()` : log `deconnexion`, vide `$_SESSION`, supprime explicitement le cookie de
session (`setcookie` avec expiration passée), `session_destroy()`. Redirection `login.php`.

---

## 2. Tableau de bord — `public/dashboard.php`

Accès : `requireLogin()` — **tous les rôles voient exactement le même tableau de bord global**,
sans filtrage par équipe projet (contrairement à presque tout le reste de l'application).

### KPI affichés (calculs exacts)

- **Projets actifs / pipeline** : `COUNT(*)` et `SUM(valeur_estimee)` sur
  `ma_projects WHERE statut <> 'abandonne'`.
- **Due diligence** :
  - `total` = nombre total de points de contrôle.
  - `valides` = `SUM(statut='valide')`.
  - `red_flags_ouverts` = `SUM(red_flag=1 AND statut<>'valide')` — **un red flag déjà validé
    n'est plus compté comme « ouvert »**.
  - **Taux d'avancement DD = valides / total × 100**, arrondi à 1 décimale (0 si aucun item pour
    éviter une division par zéro).
- **Q&A ouvertes** : `COUNT(*) WHERE statut='ouverte'`.
- **Documents** : `COUNT(*)` sur `dataroom_documents`, tous projets confondus.

### Les 2 graphiques Chart.js

1. **Camembert** — répartition des projets par statut (`GROUP BY statut`).
2. **Barres empilées** — due diligence par domaine × statut, avec une matrice pré-remplie à 0
   pour les 6 domaines et 4 statuts (le graphique affiche toujours toutes les catégories, même
   sans données pour certaines).

### Moteur de commentaires automatiques — règles exactes, dans cet ordre

1. Si `red_flags_ouverts > 0` → alerte **danger** : « N red flag(s) de due diligence sont encore
   ouverts : une revue immédiate par le chef de projet est recommandée. »
2. Si taux DD `< 50%` → **warning** ; si `>= 80%` → **success**. **Entre 50% et 80% (exclus),
   aucun commentaire n'est généré sur ce critère** — une vraie « zone neutre » dans la logique
   des seuils, pas un oubli visible mais un vrai trou à savoir expliquer si le jury demande
   « et entre les deux ? ».
3. Si des questions Q&A sont ouvertes → **info**.
4. Si aucune règle n'a été déclenchée → message **success** générique par défaut.

**Piège de jury** : ces règles sont **globales, tous projets confondus** — un seul red flag sur
n'importe quel projet du portefeuille déclenche l'alerte sur le dashboard général, il n'y a pas
de dashboard par projet séparé.

---

## 3. Projets M&A

### 3.1 Liste — `modules/projects/list.php`

- Recherche (`q`) : `LIKE '%...%'` combiné en `OR` sur `nom_projet`, `societe_cible`,
  `societe_acquereur`, `code_projet`.
- Filtres : `statut` (égalité stricte), `secteur` (liste peuplée dynamiquement par
  `SELECT DISTINCT secteur FROM ma_projects`).
- Pagination : 20 résultats/page (`paginate()`/`renderPagination()` dans `functions.php`), les
  liens de pagination conservent tous les filtres actifs.
- **Boutons par ligne** : Voir (tout rôle) → `view.php` ; Modifier (admin/conseiller seulement)
  → `edit.php` ; Supprimer (**admin seulement**) → formulaire POST vers `delete.php` avec
  confirmation JS (`confirm(...)`) et jeton CSRF.
- Bouton « Nouveau projet » (admin/conseiller) → `add.php`.

### 3.2 Création — `modules/projects/add.php`

**Accès** : `requireRole(['admin','conseiller'])`.

**Validations serveur exactes** :
- `code_projet` : obligatoire, doit matcher `/^[A-Za-z0-9\-]{2,20}$/` → « Le code projet est
  obligatoire (lettres, chiffres, tirets, 2 à 20 caractères). »
- `nom_projet`, `societe_cible`, `societe_acquereur` : non vides.
- `statut` : doit être dans `['prospection','nda_signe','due_diligence','negociation','closing','abandonne']`.
- `valeur_estimee` : si renseigné, doit être numérique.

**À l'insertion réussie** :
1. `INSERT INTO ma_projects (...)`.
2. `logAudit(..., 'creation_projet', ...)`.
3. **Le créateur est automatiquement ajouté à `project_team` avec `role_projet = 'chef_projet'`**
   — c'est ce qui lui garantit un accès si son rôle plateforme venait à être restreint plus tard.
4. Redirection vers la fiche du nouveau projet.

**Gestion d'erreur** : si `code_projet` existe déjà (contrainte SQL `UNIQUE`, code erreur
`23000`) → message dédié « Ce code projet existe déjà. »

### 3.3 Modification — `modules/projects/edit.php`

Mêmes règles qu'`add.php`, **sauf** : pas de re-vérification que `statut` fait partie des 6
valeurs autorisées (contrairement à `add.php`) — seule la colonne `ENUM` en base protège en
dernier recours contre une valeur invalide envoyée par une requête forgée.

### 3.4 Fiche projet — `modules/projects/view.php`

- Affiche toutes les infos du projet + bandeaux de confirmation (`?created=1`, `?updated=1`).
- **Ajout d'un membre à l'équipe** (admin/conseiller) : `INSERT INTO project_team`. En cas de
  doublon (contrainte `UNIQUE(project_id, user_id)`), l'erreur est **silencieusement ignorée** —
  aucun message n'est affiché, juste une redirection normale.
- **5 rôles projet possibles** : `chef_projet`, `analyste`, `conseiller_juridique`,
  `conseiller_financier`, `observateur_cible`.
- **Compteurs affichés dans la sidebar** (fonction `countForProject()`) : documents, due
  diligence, alertes DD (`red_flag=1`, **sans exclure les items déjà validés** — donc différent
  du KPI du dashboard qui, lui, exclut les items validés. Petite incohérence entre les deux
  compteurs, à savoir expliquer), Q&A, offres, NDA.

### 3.5 Suppression — `modules/projects/delete.php`

**Accès : admin seulement** (modification = admin+conseiller, suppression = admin seul).
`DELETE FROM ma_projects WHERE id=:id` — **toutes les données liées (équipe, data room, due
diligence, Q&A, NDA, valorisations, offres) sont supprimées en cascade par les contraintes
`ON DELETE CASCADE` du schéma SQL, pas par du code PHP explicite**.

**Piège de jury** : la suppression en base **ne supprime pas les fichiers physiques** du dossier
`uploads/projet_N/` — seules les métadonnées disparaissent, les fichiers réels restent orphelins
sur le disque.

---

## 4. Data room virtuelle

### 4.1 Navigation — `modules/dataroom/index.php`

- Sans `project_id` : sélecteur de projets (filtré par équipe pour un client).
- Avec `project_id` : `requireProjectAccess()` strict.
- **Arborescence** : chaque dossier a un `parent_id` (auto-référence) — le fil d'Ariane est
  reconstruit en remontant les `parent_id` un par un jusqu'à la racine.
- **Création de sous-dossier** (admin/conseiller) : simple `INSERT` avec le `parent_id` = dossier
  actuellement affiché.
- **Filtres** : recherche par nom, catégorie (`juridique, fiscal, financier, commercial, rh, it,
  autre`).
- **Actions par document** : téléchargement (tout rôle ayant accès) ; journal de consultation
  (admin/conseiller seulement).

### 4.2 Dépôt — `modules/dataroom/upload.php`

**Accès** : admin/conseiller seulement.

**Validations, dans l'ordre exact** :
1. Fichier absent → « Veuillez sélectionner un fichier à déposer. »
2. Erreur PHP d'upload → « Erreur lors du téléversement (code N). »
3. Taille `> 20 Mo` (`MAX_UPLOAD_SIZE`) → « Le fichier dépasse la taille maximale autorisée
   (20 Mo). »
4. Extension hors liste blanche → « Extension de fichier non autorisée. Extensions acceptées :
   pdf, doc, docx, xls, xlsx, png, jpg, jpeg, csv, txt. » (`isAllowedExtension()`).

**Mécanismes de sécurité au stockage** :
- Le fichier est stocké **hors du dossier public** (`uploads/` à la racine, pas dans
  `public/`) — impossible d'y accéder par une URL directe.
- **Nom de stockage aléatoire** : `bin2hex(random_bytes(16)) . '.' . extension` (32 caractères
  hexadécimaux) — totalement déconnecté du nom d'origine, qui est conservé séparément
  (`nom_original`) uniquement pour l'affichage.
- Détection du type MIME réel côté serveur (`mime_content_type()`, lecture des octets du fichier)
  plutôt que de faire confiance au type déclaré par le navigateur.
- Chaque dépôt est journalisé dans `document_access_log` (action `upload`) + `audit_log`.

**Piège de jury** : la validation de taille se fait **après** que PHP a déjà reçu le fichier
entier — `MAX_UPLOAD_SIZE` (20 Mo) est une seconde barrière applicative, la première étant
`upload_max_filesize`/`post_max_size` dans `php.ini`.

### 4.3 Téléchargement et filigrane — `modules/dataroom/download.php`

- `requireProjectAccess()` calculé à partir du `project_id` **du document en base**, pas d'un
  paramètre fourni par le client — donc non manipulable directement.
- **Traçabilité systématique**, avant même de streamer le fichier : ligne dans
  `document_access_log` (action `telechargement`) + `audit_log`.
- **Filigrane dynamique** : appliqué **uniquement si** le document est `confidentiel=1` **et**
  son extension d'origine est `.pdf`. Le texte du filigrane est
  `<nom complet de l'utilisateur qui télécharge> — <date/heure exacte>` — but dissuasif anti-fuite
  (si le PDF circule ensuite, on sait qui l'a téléchargé et quand).
  - Technique : `FPDI` importe chaque page du PDF source en template, `TCPDF` écrit le texte en
    rouge, semi-transparent (35% d'opacité), **rotationné à 45°**, centré.
  - Si les librairies Composer (FPDI/TCPDF) ne sont pas installées → repli automatique et
    silencieux sur le fichier original, **la traçabilité en base reste enregistrée quoi qu'il
    arrive**.
- En-tête `X-Content-Type-Options: nosniff` envoyé — empêche le navigateur de deviner un autre
  type MIME que celui déclaré (anti MIME-sniffing).

### 4.4 Journal — `modules/dataroom/log.php`

**Accès : admin/conseiller seulement**, même pour un document de son propre projet — un client
ne peut jamais consulter ce journal.

**Piège de jury** : le schéma SQL prévoit 3 valeurs d'action possibles pour
`document_access_log` (`upload`, `consultation`, `telechargement`), mais **seules `upload` et
`telechargement` sont réellement produites par le code** — il n'existe aucun écran de simple
prévisualisation qui journaliserait une `consultation` sans téléchargement.

---

## 5. Due diligence

### 5.1 Liste — `modules/duediligence/list.php`

- Filtres : `domaine` (6 valeurs), `statut` (4 valeurs), `red_flag=1` (case à cocher
  auto-soumise).
- **Tri** : `ORDER BY red_flag DESC, date_limite ASC` — les red flags remontent toujours en
  premier, puis tri par échéance.
- La ligne entière prend un fond rouge (`table-danger`) si `red_flag=1`.
- Boutons : Export PDF (tout rôle ayant accès), Nouveau point de contrôle / Modifier / Supprimer
  (admin/conseiller seulement, avec confirmation JS pour la suppression).

### 5.2 Les 6 domaines et 4 statuts (codés en dur, fichier `_form_fields.php`)

- **Domaines** : juridique, fiscal, financier, commercial, RH, IT (systèmes d'information).
- **Statuts** : à vérifier, en cours, validé, alerte.

### 5.3 Création — `modules/duediligence/add.php`

**Accès** : admin/conseiller.

**Validations** : `domaine` doit être l'une des 6 valeurs ; `libelle` non vide ; `impact_estime`
(si renseigné) doit être numérique.

**Piège de jury** : le formulaire porte l'attribut `novalidate` sur la balise `<form>`, ce qui
**désactive la validation HTML5 native du navigateur** malgré les attributs `required` présents
sur les champs — la seule validation réelle est donc côté serveur PHP. (À l'inverse, le
formulaire Q&A n'a pas `novalidate` et bénéficie d'une double validation client + serveur.)

### 5.4 Modification — `modules/duediligence/edit.php`

**Piège de jury** : contrairement à `add.php`, l'édition **ne revérifie pas** que le `domaine`
envoyé fait partie des 6 valeurs autorisées — seule la contrainte `ENUM` de la colonne SQL
protège en dernier recours.

### 5.5 Suppression — `modules/duediligence/delete.php`

Le `SELECT` préalable exige que `id` **ET** `project_id` correspondent simultanément
(`WHERE id=:id AND project_id=:p`) — empêche de supprimer un item d'un autre projet même en
forgeant l'`id` posté. Le libellé de l'item est conservé dans le log d'audit **avant**
suppression (traçabilité de ce qui a été supprimé).

### 5.6 Comment l'impact financier remonte réellement

**Piège de jury important** : le tableau de bord global (`dashboard.php`) **n'affiche aucun
montant financier cumulé** — seulement des comptages et des pourcentages. Le calcul de
« l'impact estimé cumulé » n'existe que dans l'**export PDF par projet** :

```
Impact cumulé = somme des impact_estime UNIQUEMENT des items marqués red_flag = 1
              (pas la somme de tous les items de la checklist)
```

Si le jury demande « où voit-on l'impact financier cumulé ? » → réponse : nulle part sur le
dashboard, seulement dans le PDF de due diligence de chaque projet, et seulement sur les red
flags, pas sur l'ensemble des points de contrôle.

### 5.7 Export PDF — `exports/export_duediligence_pdf.php`

**Mécanisme de génération avec repli** :
1. Vérifie si `vendor/autoload.php` existe et si la classe DOMPDF est disponible.
2. Si oui : génère un vrai PDF téléchargeable (`Dompdf::render()` + `stream(...)`).
3. **Si non** : affiche le rapport en HTML brut dans le navigateur, avec un bandeau
   d'avertissement, **et déclenche automatiquement `window.print()` au chargement de la page**
   (pas juste un message d'erreur passif — la boîte de dialogue d'impression s'ouvre
   automatiquement, l'utilisateur choisit alors « Enregistrer en PDF »).

---

## 6. Questions / Réponses (Q&A)

### 6.1 Poser une question — `modules/qa/ask.php`

Accessible à **tout rôle** ayant accès au projet (y compris client).

**Validation exacte** : question vide → « Veuillez saisir votre question. » ; moins de 10
caractères (`mb_strlen`) → « Merci de préciser votre question (10 caractères minimum). » — ce
formulaire n'a pas `novalidate`, donc la contrainte HTML5 `minlength="10"` s'applique aussi côté
navigateur (double validation, contrairement à la due diligence).

### 6.2 Répondre — `modules/qa/answer.php`

**Accès** : admin/conseiller seulement. Validation : réponse vide → « La réponse ne peut pas être
vide. »

**Séquence exacte à la soumission** :
1. `UPDATE qa_questions SET reponse=..., statut='repondue', answered_at=NOW() ...`.
2. `logAudit(...)`.
3. **Notification email automatique** au demandeur via `sendAppMail()`.

**Piège de jury** : l'échec d'envoi de l'email (SMTP non configuré, PHPMailer absent) **ne
bloque jamais l'enregistrement de la réponse** — l'`UPDATE` SQL est déjà validé en base avant
même l'appel à `sendAppMail()`, et cette fonction avale silencieusement toute erreur (elle
retourne juste `false`, loggé en `error_log()`, sans jamais interrompre le flux).

### 6.3 `sendAppMail()` — le mécanisme d'email avec repli (`includes/functions.php`)

- Si PHPMailer (via Composer) est disponible : envoi SMTP réel selon les constantes `SMTP_*` de
  `config.php`.
- Sinon : repli sur la fonction native PHP `mail()` — qui, sous XAMPP par défaut sans serveur mail
  local configuré, n'envoie rien mais ne bloque pas non plus l'application (échec silencieux).
- **Dans tous les cas**, la fonction ne garantit jamais la délivrabilité réelle, seulement
  l'absence d'exception immédiate.

---

## 7. NDA — Accord de confidentialité

### 7.1 Liste — `modules/ndas/list.php`

Détection « déjà signé » = simple boucle PHP cherchant l'utilisateur courant dans la liste des
signatures (pas une requête SQL dédiée). Affiche pour chaque signature l'empreinte SHA-256 mais
**tronquée aux 24 premiers caractères** (`substr(..., 0, 24) . '…'`) — le hash complet (64
caractères) est en base mais jamais affiché intégralement à l'écran.

### 7.2 Signature — `modules/ndas/sign.php`

- Le **texte de l'accord est généré dynamiquement** avec les vraies valeurs du projet (nom de la
  cible, nom de l'acquéreur) — donc unique par projet, pas un texte générique statique.
- Validations : nom vide → « Veuillez saisir votre nom complet pour signer. » ; case
  d'acceptation non cochée → « Vous devez cocher la case d'acceptation pour signer le NDA. »

**Formule exacte de l'empreinte de non-répudiation** :
```
hash = SHA-256( nom_saisi | email_du_compte_connecté | texte_complet_de_l_accord | horodatage_serveur )
```
Les 4 éléments sont concaténés avec le séparateur `|`. **L'email vient de la session (pas d'un
champ de formulaire)** — donc non falsifiable côté client. Le texte de l'accord inclut cible et
acquéreur, donc le hash est indirectement lié au projet.

**Pièges de jury** :
1. Ce n'est **pas** une signature cryptographique au sens strict (pas de clé privée/certificat) —
   c'est une **empreinte d'intégrité** : si on falsifiait rétroactivement le texte de l'accord ou
   l'email en base, le hash ne correspondrait plus. Le code le documente lui-même comme
   « signature électronique simplifiée ».
2. **Aucune contrainte SQL n'empêche un même utilisateur de signer plusieurs fois** le NDA d'un
   même projet — le badge « déjà signé » de `list.php` est purement informatif, pas un verrou
   technique d'accès à `sign.php`.
3. **Deux horodatages distincts** existent : celui calculé côté PHP juste avant l'insertion
   (inclus DANS le hash) et celui stocké dans la colonne `signed_at` (rempli par
   `DEFAULT CURRENT_TIMESTAMP` de MySQL) — proches mais pas rigoureusement identiques.

---

## 8. Valorisation

Accès aux 4 pages (`list`, `dcf`, `multiples`, `ancc`) : **admin/conseiller seulement** — un
client n'a jamais accès à la valorisation.

### 8.1 Liste et graphique comparatif — `modules/valuation/list.php`

Le graphique « football field » n'apparaît que si **au moins 2 évaluations** existent pour le
projet. **Piège de jury** : ce n'est pas un vrai football field financier (qui montrerait une
fourchette min-max par méthode) — c'est une barre horizontale **par évaluation enregistrée**,
dans l'ordre chronologique. Si un conseiller relance 3 DCF avec des hypothèses différentes, on
obtient 3 barres « DCF » distinctes, pas une agrégation.

### 8.2 DCF / Gordon-Shapiro — `modules/valuation/dcf.php`

**Champs** : FCF année 1, taux de croissance (horizon explicite), WACC, taux de croissance
perpétuelle (g terminal), horizon (1 à 15 ans, défaut 5).

**Validations serveur, dans l'ordre** :
- FCF année 1 `<= 0` → erreur.
- WACC hors de `]0 ; 1[` (soit 0 à 100%) → erreur.
- **`g_terminal >= WACC` → erreur explicite « Le taux de croissance à l'infini doit être
  strictement inférieur au WACC. »** — condition mathématique impérative de Gordon-Shapiro : si
  g ≥ WACC, le dénominateur (WACC − g) devient nul ou négatif, ce qui est économiquement
  incohérent (une entreprise ne peut pas croître indéfiniment plus vite que son coût du capital).
- Horizon hors de `[1 ; 15]` → erreur.

**Formule exacte, étape par étape** :
```
FCF₁ = FCF année 1 saisi
FCFₙ = FCFₙ₋₁ × (1 + croissance)          pour n = 2 à horizon (capitalisation en cascade)

Facteur d'actualisation année n = 1 / (1 + WACC)ⁿ
FCF actualisé année n = FCFₙ × facteur d'actualisation

Valeur actualisée des flux = Σ (FCF actualisés, années 1 à horizon)

Valeur terminale (Gordon-Shapiro) = FCF_horizon × (1 + g_terminal) / (WACC − g_terminal)
Valeur terminale actualisée = Valeur terminale / (1 + WACC)^horizon

Valeur d'entreprise (VE) = Valeur actualisée des flux + Valeur terminale actualisée
```

**Pièges de jury** :
1. Le résultat est une **Valeur d'Entreprise (VE)**, pas une valeur des fonds propres — aucune
   déduction de dette nette n'est faite dans ce module (contrairement à la méthode des
   multiples).
2. Un seul taux de croissance unique est appliqué uniformément sur tout l'horizon — pas de flux
   détaillés année par année saisissables individuellement (simplification pédagogique assumée).
3. Chaque clic sur « Calculer et enregistrer » crée une **nouvelle ligne** en base (pas de mise à
   jour) — l'historique complet de toutes les évaluations passées est conservé.

### 8.3 Multiples VE/EBITDA — `modules/valuation/multiples.php`

**Champs** : EBITDA, multiple sectoriel (saisi manuellement, aucune base de comparables
automatique), dette nette (optionnelle, peut être négative = trésorerie nette positive).

**Formule exacte** :
```
Valeur d'entreprise (VE) = EBITDA × Multiple
Valeur des fonds propres = VE − Dette nette
```

**Piège de jury majeur** : les deux valeurs sont **affichées**, mais seule la **valeur des fonds
propres** (VE − dette) est **enregistrée** dans `valuations.valeur_calculee`. Conséquence : le
graphique comparatif de `list.php` compare en réalité pour cette méthode une valeur de fonds
propres, alors que le DCF enregistre une VE brute, et l'ANCC (ci-dessous) une approche
patrimoniale — **une incohérence méthodologique réelle si on compare les 3 barres brutes sans
retraitement**, à assumer honnêtement devant le jury plutôt qu'à cacher.

### 8.4 ANCC — `modules/valuation/ancc.php`

**Formule exacte** :
```
ANCC = Actif net comptable + Plus-values latentes − Moins-values latentes − Passif exigible non comptabilisé
```

Seule validation serveur : actif comptable `<= 0` → erreur. **Piège** : le champ
« passif exigible » n'a aucune borne (`min`) côté HTML ni côté serveur — rien n'empêche
d'y saisir une valeur négative, ce qui n'a pas de sens économique.

---

## 9. Offres et contre-offres

### 9.1 Liste — `modules/offers/list.php`

Accessible à **tout rôle** ayant accès au projet (y compris client, en lecture).

**Changement de statut inline** (admin/conseiller seulement) : un simple `<select>` avec
`onchange="this.form.submit()"` — dès qu'on choisit une autre valeur, le formulaire (CSRF +
`offer_id` cachés) se soumet automatiquement, sans bouton « Valider » séparé.

`UPDATE offers SET statut=:s WHERE id=:id AND project_id=:p` — le `AND project_id=:p` empêche de
modifier une offre d'un autre projet même en trafiquant l'`offer_id`.

### 9.2 Création — `modules/offers/add.php`

**Champs** : type d'offre (offre initiale / contre-offre / offre finale), montant, devise
(pré-remplie avec la devise du projet), date, conditions (texte libre).

Le **statut est toujours forcé à `"proposee"`** à la création — il n'évolue que via l'action
inline de `list.php`, jamais via un champ du formulaire d'ajout.

**Pièges de jury** :
1. **Aucun lien logique** n'existe entre une offre et sa contre-offre en base (pas de colonne
   `parent_offer_id`) — seul l'ordre chronologique (`ORDER BY date_offre ASC`) permet de
   reconstituer la négociation à l'œil.
2. N'importe quel admin/conseiller peut faire passer **plusieurs offres du même projet** au
   statut « Acceptée » simultanément — aucune règle métier ne l'empêche.

---

## 10. Administration — Utilisateurs

Toutes les pages (`list`, `add`, `edit`, `delete`) : **admin seulement**.

### 10.1 Création — `modules/users/add.php`

**Validations** : nom non vide, email valide (`FILTER_VALIDATE_EMAIL`), rôle dans
`['admin','conseiller','client']`, mot de passe ≥ 8 caractères.

**Hachage** : `password_hash($password, PASSWORD_DEFAULT)` = **bcrypt** (le commentaire du code
le précise explicitement) — jamais de mot de passe en clair stocké.

Gestion du doublon d'email (contrainte `UNIQUE`, code SQL `23000`) → message dédié « Cette
adresse email est déjà utilisée. »

### 10.2 Modification — `modules/users/edit.php`

Le mot de passe devient **optionnel** : si le champ est laissé vide, le hash existant est
**préservé intact** (deux requêtes `UPDATE` différentes selon le cas — jamais de risque
d'écraser le hash par une valeur vide).

**Piège de jury** : rien n'empêche un admin de modifier son propre rôle en `client` via ce
formulaire (contrairement à la désactivation, bloquée pour soi-même) — un vrai risque de
se bloquer l'accès par erreur.

### 10.3 « Suppression » — `modules/users/delete.php`

**Ce n'est pas une suppression physique mais une désactivation logique (soft delete)** :
```sql
UPDATE users SET is_active = 0 WHERE id = :id
```
Le commentaire du code justifie ce choix explicitement : préserver l'intégrité référentielle
avec les projets/documents déjà créés par ce compte (contraintes `ON DELETE RESTRICT` en base).
**Un admin ne peut pas se désactiver lui-même** (contrôle serveur, pas juste caché en interface).

**Piège de jury** : un utilisateur désactivé qui tente de se connecter échoue exactement comme
avec un mauvais mot de passe (voir `attemptLogin()`, §1.2) — pas de message spécifique « compte
désactivé » affiché, volontairement, pour ne pas confirmer l'existence du compte à un tiers.

---

## 11. Journal d'audit et exports CSV

### 11.1 `modules/audit/list.php`

**Accès : admin seulement**. Recherche sur 3 colonnes combinées (action, détails, nom
utilisateur). `LEFT JOIN` avec `users` (pas `JOIN` simple) car `user_id` peut être `NULL`
(actions système, ex : `session_expiree` déclenchée par le serveur).

**Liste complète des actions effectivement journalisées dans le code** :
`echec_connexion`, `connexion_reussie`, `deconnexion`, `session_expiree`, `creation_projet`,
`modification_projet`, `suppression_projet`, `ajout_membre_equipe`, `upload_document`,
`telechargement_document`, `creation_due_diligence`, `modification_due_diligence`,
`suppression_due_diligence`, `export_pdf_due_diligence`, `creation_question_qa`,
`reponse_question_qa`, `signature_nda`, `creation_valorisation_dcf`,
`creation_valorisation_multiples`, `creation_valorisation_ancc`, `creation_offre`,
`maj_statut_offre`, `creation_utilisateur`, `modification_utilisateur`,
`desactivation_utilisateur`, `export_csv_audit`, `export_csv_dataroom`.

**Piège de jury** : le champ `montant`/hypothèses détaillées d'une valorisation n'est **jamais**
dupliqué dans `audit_log` — seul l'`id` de la ligne créée y est loggé. Le détail financier complet
(JSON des hypothèses) ne vit que dans la table `valuations`.

### 11.2 Exports CSV (`exports/export_audit_csv.php`, `exports/export_dataroom_csv.php`)

- Écriture directe sur `php://output` via `fputcsv()` — pas de fichier temporaire sur disque.
- **BOM UTF-8** (`\xEF\xBB\xBF`) écrit en première ligne — nécessaire pour que Excel affiche
  correctement les accents français.
- **Séparateur `;` (point-virgule)**, pas la virgule — adapté aux réglages régionaux français
  d'Excel.
- L'export de l'audit est réservé à l'admin ; l'export data room est ouvert à tout rôle ayant
  accès au projet.
- Chaque export est lui-même journalisé dans `audit_log` (méta-traçabilité : on sait qui a
  exporté quoi et quand).

---

## 12. Fonctions utilitaires clés (`includes/functions.php`)

| Fonction | Ce qu'elle fait |
|---|---|
| `formatMoney($montant, $devise)` | `number_format` à 0 décimale, espace comme séparateur de milliers, suivi de la devise. `—` si vide. |
| `formatDate($date, $format)` | Format par défaut `d/m/Y`. `—` si vide ou invalide. |
| `paginate($total, $page, $perPage)` | Calcule page/offset/nombre de pages, borne la page demandée entre 1 et le max. |
| `renderPagination($pageInfo, $baseFile)` | Génère les liens de pagination Bootstrap en conservant tous les filtres GET actifs. |
| `projectStatusBadge()` / `ddStatusBadge()` / `redFlagBadge()` | Génèrent les badges colorés Bootstrap correspondants. |
| `roleLabel()` / `domaineLabel()` | Traduisent les valeurs ENUM techniques en libellés français lisibles. |
| `sendAppMail()` | Envoi email avec repli PHPMailer → `mail()` native, voir §6.3. |
| `isAllowedExtension()` | Vérifie l'extension d'un fichier contre la liste blanche `ALLOWED_EXTENSIONS`. |
| `ensureUploadDirForProject()` | Crée le dossier `uploads/projet_N/` s'il n'existe pas. |
| `generatePdfWatermarkCopy()` | Filigrane dynamique FPDI/TCPDF, voir §4.3. |
| `computeDCF()` | Moteur de calcul DCF/Gordon-Shapiro, voir §8.2 — réutilisable indépendamment de l'interface. |

---

## 13. Top 10 des pièges de jury à absolument maîtriser

Si tu ne dois retenir qu'une liste courte avant la soutenance, c'est celle-ci :

1. **`projects/view.php` n'a pas de `requireProjectAccess()`** — un client peut voir la fiche de
   n'importe quel projet en changeant l'`id` dans l'URL.
2. **L'impact financier cumulé de due diligence n'existe QUE dans le PDF export**, jamais sur le
   dashboard, et **seulement sur les red flags**, pas sur tous les items.
3. **Le champ `novalidate` sur le formulaire due diligence** désactive la validation HTML5
   malgré les `required` visibles — seule la validation serveur PHP compte réellement.
4. **La méthode des multiples enregistre la valeur des fonds propres (VE − dette)**, alors que le
   DCF enregistre une VE brute — les 3 méthodes de valorisation ne sont pas directement
   comparables sans retraitement dans le graphique « football field ».
5. **`g_terminal` doit être strictement inférieur au WACC** (Gordon-Shapiro) — sinon division par
   zéro ou incohérence économique, bloqué par validation serveur explicite.
6. **Le NDA n'a aucune contrainte d'unicité** — un utilisateur peut techniquement signer
   plusieurs fois le NDA d'un même projet.
7. **La suppression d'utilisateur est un soft delete** (`is_active=0`), jamais une suppression
   physique — pour préserver l'intégrité référentielle.
8. **L'échec d'envoi d'email (réponse Q&A) ne bloque jamais l'enregistrement** — l'email est
   une action secondaire, best-effort, après le commit en base.
9. **Le filigrane PDF ne s'applique que si `confidentiel=1` ET extension `.pdf`** — et seulement
   si Composer/FPDI/TCPDF sont installés, avec repli silencieux sinon (mais la traçabilité en
   base reste toujours enregistrée, filigrane ou pas).
10. **Le compteur anti-brute-force du login vit en session**, pas en base ni par IP — contournable
    en supprimant les cookies, mais suffisant pour l'usage pédagogique visé.

---

*Prépare-toi en relisant ce document une fois en entier, puis en re-manipulant l'application
écran par écran en te posant la question « et si je clique ici, qu'est-ce qui se passe
exactement dans le code ? » — c'est ce niveau de réponse que le jury testera.*
