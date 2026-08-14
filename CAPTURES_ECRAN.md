# Checklist des captures d'écran — ClosingPoint

Guide pour réaliser les captures demandées dans le cahier des charges. Basé sur la liste des
« Écrans de l'application » de `DOCUMENTATION_TECHNIQUE.md` §5, complétée avec les 3 profils
(admin / conseiller / client) là où l'affichage diffère selon le rôle.

**Pré-requis** : Apache + MySQL démarrés (XAMPP), base `closingpoint` importée avec le jeu de
données de démonstration. URL de base : `http://localhost/ClosingPoint/`.

**Comptes de démo** (voir README §5) :

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@closingpoint.sn` | `Admin@2026` |
| Conseiller M&A | `conseiller@closingpoint.sn` | `Advisor@2026` |
| Client / Investisseur | `client@closingpoint.sn` | `Client@2026` |

> Astuce : utilise une fenêtre de navigation privée par rôle (ou 3 navigateurs différents) pour
> garder plusieurs sessions ouvertes en parallèle sans te déconnecter/reconnecter sans cesse.

Numérote tes fichiers `01_connexion.png`, `02_dashboard.png`, etc. pour garder l'ordre du plan.

---

## 1. Connexion / déconnexion

- [ ] `public/login.php` — formulaire de connexion vide
- [ ] Tentative avec mot de passe erroné → message d'erreur affiché
- [ ] (Optionnel, pour montrer la sécurité) 5 échecs successifs → message de verrouillage temporaire
- [ ] Connexion réussie → redirection vers le tableau de bord

## 2. Tableau de bord (KPI + graphiques + commentaires)

- [ ] `public/dashboard.php` connecté en **admin** — vue d'ensemble tous projets, KPI, les 2
      graphiques Chart.js, bloc de commentaires automatiques
- [ ] `public/dashboard.php` connecté en **client** — vue restreinte à ses projets

## 3. Projets M&A

- [ ] `modules/projects/list.php` — liste avec recherche/filtres (statut, secteur) + pagination
- [ ] `modules/projects/add.php` — formulaire de création (admin/conseiller)
- [ ] `modules/projects/view.php?id=1` — fiche projet détaillée (infos cible/acquéreur, équipe,
      compteurs data room/DD/QA/NDA/valorisation/offres dans la sidebar)
- [ ] `modules/projects/edit.php?id=1` — formulaire de modification

## 4. Data room virtuelle

- [ ] `modules/dataroom/index.php?project_id=1` — arborescence de dossiers + liste des documents
- [ ] Dépôt d'un document (`upload.php?project_id=1`) — formulaire d'upload
- [ ] `modules/dataroom/log.php?project_id=1` — journal de consultation/téléchargement
      (`document_access_log`)
- [ ] (Optionnel) tentative d'upload d'une extension non autorisée → message d'erreur (liste
      blanche)

## 5. Due diligence

- [ ] `modules/duediligence/list.php?project_id=1` — checklist filtrable (domaine, statut, red
      flag uniquement)
- [ ] Un item avec `red_flag` coché et son `impact_estime` visible
- [ ] `modules/duediligence/add.php?project_id=1` — formulaire d'ajout
- [ ] Export PDF de synthèse (`exports/export_duediligence_pdf.php?project_id=1`)

## 6. Questions / Réponses (Q&A)

- [ ] `modules/qa/list.php?project_id=1` — liste des questions/réponses, filtre par statut
- [ ] `modules/qa/ask.php?project_id=1` — poser une question (vue **client**)
- [ ] Réponse à une question (vue **conseiller/admin**, `answer.php?id=...`)

## 7. NDA (accord de confidentialité)

- [ ] `modules/ndas/list.php?project_id=1` — registre des signataires
- [ ] `modules/ndas/sign.php?project_id=1` — texte de l'accord + signature électronique (avant
      signature)
- [ ] Après signature → confirmation avec empreinte SHA-256 horodatée

## 8. Valorisation

- [ ] `modules/valuation/list.php?project_id=1` — liste des évaluations + graphique comparatif
      (« football field »)
- [ ] `modules/valuation/dcf.php?project_id=1` — méthode DCF (Gordon-Shapiro)
- [ ] `modules/valuation/multiples.php?project_id=1` — méthode des multiples VE/EBITDA
- [ ] `modules/valuation/ancc.php?project_id=1` — méthode ANCC

## 9. Offres et contre-offres

- [ ] `modules/offers/list.php?project_id=1` — historique des offres avec statuts (badges) +
      changement de statut inline
- [ ] `modules/offers/add.php?project_id=1` — formulaire de nouvelle offre/contre-offre

## 10. Administration

- [ ] `modules/users/list.php` — liste des utilisateurs (recherche)
- [ ] `modules/users/add.php` — création d'un utilisateur avec rôle
- [ ] `modules/audit/list.php` — journal d'audit (connexions, créations, suppressions,
      signatures, exports) avec recherche
- [ ] Export CSV du journal d'audit (`exports/export_audit_csv.php`)
- [ ] Export CSV de la data room (`exports/export_dataroom_csv.php?project_id=1`)

---

## Sécurité (si le cahier des charges demande une preuve explicite)

- [ ] Accès direct à un fichier protégé, ex. `http://localhost/ClosingPoint/config/database.php`
      → bloqué par `.htaccess` (403/erreur)
- [ ] Un client qui tente d'ouvrir un projet auquel il n'est pas rattaché
      (`modules/projects/view.php?id=<id_hors_équipe>`) → accès refusé
- [ ] Jeton CSRF : inspecter le code source d'un formulaire → champ `csrf_token` caché présent

---

**Total : ~32 captures** couvrant les 10 écrans + les preuves de sécurité. Ajuste la liste si le
cahier des charges impose un nombre ou des intitulés précis — dans ce cas, transmets-le et je
remets à jour cette checklist en conséquence.
