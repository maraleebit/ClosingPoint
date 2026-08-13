# Document technique — ClosingPoint (Projet 36, M&A DataRoom)

## 1. Modèle Conceptuel de Données (MCD)

### Entités et attributs principaux

| Entité | Attributs clés | Relations |
|---|---|---|
| **USERS** | id, full_name, email, password_hash, role, is_active | 1,n avec PROJECT_TEAM, MA_PROJECTS (créateur), etc. |
| **MA_PROJECTS** | id, code_projet, nom_projet, societe_cible, societe_acquereur, statut, valeur_estimee | 1,n avec PROJECT_TEAM, DATAROOM_FOLDERS, DUE_DILIGENCE_ITEMS, QA_QUESTIONS, NDAS, VALUATIONS, OFFERS |
| **PROJECT_TEAM** | id, project_id, user_id, role_projet | n,n entre MA_PROJECTS et USERS |
| **DATAROOM_FOLDERS** | id, project_id, parent_id (auto-référence), nom | Arborescence hiérarchique |
| **DATAROOM_DOCUMENTS** | id, project_id, folder_id, nom_original, chemin_relatif, categorie, confidentiel | 1,n avec DOCUMENT_ACCESS_LOG |
| **DOCUMENT_ACCESS_LOG** | id, document_id, user_id, action, adresse_ip, date_action | Traçabilité (piste d'audit data room) |
| **NDAS** | id, project_id, user_id, hash_signature, signed_at | Signature électronique |
| **DUE_DILIGENCE_ITEMS** | id, project_id, domaine, statut, red_flag, impact_estime, responsable_id | Checklist par domaine |
| **QA_QUESTIONS** | id, project_id, document_id, question, reponse, statut | Module Q&A |
| **VALUATIONS** | id, project_id, methode, hypotheses (JSON), valeur_calculee | DCF / multiples / ANCC |
| **OFFERS** | id, project_id, type_offre, montant, statut | Offres et contre-offres |
| **AUDIT_LOG** | id, user_id, action, table_concernee, ligne_id, details | Journal d'audit horodaté global |

### Relations clés (cardinalités)

- Un **utilisateur** peut appartenir à l'équipe de **plusieurs projets** (n,n via `project_team`).
- Un **projet** possède **une arborescence de dossiers** (`dataroom_folders`, auto-référencée par
  `parent_id`) et **plusieurs documents** (`dataroom_documents`), chacun rattaché à 0 ou 1 dossier.
- Chaque **document** génère **plusieurs entrées** dans `document_access_log` (une par consultation
  ou téléchargement) : c'est la piste d'audit de la data room.
- Un **projet** porte **plusieurs points de due diligence**, chacun affecté à **un responsable**
  (utilisateur membre de l'équipe projet).
- Un **projet** peut recevoir **plusieurs évaluations** (une par méthode/hypothèses testées) et
  **plusieurs offres/contre-offres** successives, formant une chronologie de négociation.

Le schéma complet (12 tables, toutes liées par des clés étrangères avec règles `ON DELETE
CASCADE`/`RESTRICT`/`SET NULL` appropriées) se trouve dans `sql/schema.sql`.

## 2. Architecture applicative

```
Requête HTTP
    │
    ▼
public/*.php ou modules/<module>/*.php   (point d'entrée = contrôleur + vue, style PHP procédural MVC léger)
    │
    ├─ includes/bootstrap.php   → session sécurisée, config, connexion PDO, expiration de session, CSRF
    ├─ includes/security.php    → CSRF, échappement, requireLogin(), requireRole(), audit trail
    ├─ includes/auth.php        → attemptLogin(), logoutCurrentUser()
    ├─ includes/functions.php   → helpers métier (formatage, pagination, DCF, email, watermark PDF)
    └─ includes/header|sidebar|footer.php → gabarit d'affichage commun (Bootstrap 5)
    │
    ▼
config/database.php (PDO, requêtes préparées) ──▶ MySQL (base closingpoint)
```

Chaque module métier (`modules/<entite>/`) suit la même convention :

- `list.php` — liste avec recherche, filtres multi-critères, pagination (20 lignes/page)
- `add.php` / `edit.php` — formulaire de création/modification (validation client JS + serveur PHP)
- `delete.php` — suppression sécurisée (POST + jeton CSRF obligatoire)
- `view.php` (le cas échéant) — fiche détaillée

## 3. Conventions de code

- **Nommage** : `snake_case` pour les colonnes SQL et variables PHP, `camelCase` pour les fonctions
  utilitaires, français pour les libellés métier (cohérence avec le contexte SYSCOHADA/OHADA du cursus).
- **Sécurité systématique** :
  - Toute requête SQL passe par PDO avec paramètres liés (`:nom` ou `?`), jamais de concaténation.
  - Tout affichage de donnée utilisateur passe par `e()` (wrapper `htmlspecialchars`).
  - Toute action de modification (POST) vérifie `csrf_verify($_POST['csrf_token'])`.
  - Tout mot de passe est haché avec `password_hash()`/vérifié avec `password_verify()`.
  - Tout accès à un module vérifie le rôle (`requireRole`) puis, pour les données d'un projet précis,
    l'appartenance à l'équipe projet (`requireProjectAccess`) — un client ne voit que ses projets.
- **Traçabilité** : toute action sensible (connexion, création/modification/suppression, upload,
  téléchargement, signature NDA, export) est enregistrée dans `audit_log` via `logAudit()`.
- **Réutilisation** : les formulaires longs (projets, due diligence) sont factorisés dans des
  partiels `_form_fields.php` partagés entre `add.php` et `edit.php`.

## 4. Choix techniques justifiés

| Choix | Justification |
|---|---|
| PHP procédural (pas de framework) | Imposé/autorisé par le cahier des charges XAMPP ; permet une maîtrise complète du code, indispensable pour la soutenance. |
| PDO + requêtes préparées | Protection native contre les injections SQL (OWASP A03). |
| Filigrane PDF via FPDI/TCPDF (optionnel) | Répond à l'exigence « watermarking dynamique » tout en restant dégradable proprement si Composer n'est pas exécuté (pas de blocage du rendu). |
| Signature NDA par empreinte SHA-256 | Simule une signature électronique non-répudiable (identité + contenu + horodatage) sans dépendre d'un prestataire tiers payant, conforme à l'esprit pédagogique du projet. |
| Due diligence avec `red_flag` + `impact_estime` | Permet le calcul automatique de l'impact financier cumulé des risques identifiés, affiché dans le tableau de bord et le rapport PDF. |

## 5. Écrans de l'application (pour le manuel utilisateur)

1. Connexion / déconnexion
2. Tableau de bord (KPI + 2 graphiques Chart.js + commentaires automatiques)
3. Liste des projets M&A (recherche/filtres/pagination) + fiche projet + équipe
4. Data room : arborescence, dépôt de document, téléchargement, journal de consultation
5. Due diligence : checklist filtrable, red flags, export PDF de synthèse
6. Questions/Réponses (Q&A) avec notification email
7. NDA : texte de l'accord, signature électronique, registre des signataires
8. Valorisation : DCF, multiples, ANCC + graphique comparatif (« football field »)
9. Offres et contre-offres avec suivi de statut
10. Administration : utilisateurs (CRUD, rôles) et journal d'audit (export CSV)
