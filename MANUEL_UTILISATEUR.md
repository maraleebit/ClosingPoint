# Manuel utilisateur — ClosingPoint

**Plateforme de gestion de projets de fusion-acquisition (M&A) avec data room virtuelle**
Master CCA — École Supérieure Polytechnique de Dakar — Projet 36
Auteur : Mara Lee — Année universitaire 2025-2026

> **Comment finaliser ce manuel :** chaque section ci-dessous contient un repère
> `📷 [Capture : ...]` correspondant exactement à un point de la checklist `CAPTURES_ECRAN.md`.
> Prends la capture correspondante (voir cette checklist pour l'URL et le rôle à utiliser) et
> remplace le repère par l'image (`![légende](chemin/vers/image.png)`). Une fois toutes les
> images insérées, exporte ce fichier en PDF (Word, un convertisseur Markdown→PDF, ou impression
> navigateur) — avec les captures, le document atteint naturellement les 15-20 pages demandées.

---

## Table des matières

1. Présentation générale
2. Prise en main : installation et connexion
3. Tableau de bord
4. Gestion des projets M&A
5. Data room virtuelle
6. Due diligence
7. Questions / Réponses
8. NDA — accord de confidentialité
9. Valorisation
10. Offres et contre-offres
11. Administration (utilisateurs et audit)
12. Sécurité de la plateforme
13. Comptes de démonstration — récapitulatif

---

## 1. Présentation générale

ClosingPoint accompagne un cabinet de conseil ou une direction financière tout au long d'un
processus de fusion-acquisition : évaluation de la cible, due diligence multi-domaines, échange
documentaire sécurisé via une data room virtuelle, questions/réponses avec les investisseurs,
signature électronique des accords de confidentialité (NDA) et suivi des offres jusqu'au closing.

La plateforme distingue trois profils d'utilisateur :

| Rôle | Ce qu'il peut faire |
|---|---|
| **Administrateur** | Accès complet : tous les projets, gestion des utilisateurs, journal d'audit |
| **Conseiller M&A** | Gère les projets de son équipe : due diligence, data room, valorisation, offres |
| **Client / Investisseur** | Accès restreint aux seuls projets où il est membre de l'équipe ; consultation, questions, signature NDA |

## 2. Prise en main : installation et connexion

L'installation complète (XAMPP, import de la base, configuration) est détaillée dans le
`README.md` à la racine du dépôt. Une fois Apache et MySQL démarrés et la base importée, l'accès
se fait à l'adresse :

```
http://localhost/ClosingPoint/public/login.php
```

📷 **[Capture : login.php — formulaire de connexion vide]**

La connexion se fait par email et mot de passe. En cas d'erreur, un message s'affiche sans
préciser si c'est l'email ou le mot de passe qui est incorrect (bonne pratique de sécurité) :

📷 **[Capture : tentative avec mot de passe erroné]**

Après 5 échecs successifs, le compte est temporairement verrouillé :

📷 **[Capture : message de verrouillage après échecs répétés — optionnel]**

Une fois connecté, l'utilisateur est redirigé vers le tableau de bord.

## 3. Tableau de bord

Le tableau de bord est la page d'accueil après connexion. Il présente :

- des indicateurs clés (KPI) sur le portefeuille de projets ;
- deux graphiques Chart.js alimentés par les données réelles de la base ;
- un moteur de commentaires automatiques (alertes sur les red flags de due diligence,
  avancement des projets, etc.).

📷 **[Capture : dashboard.php — vue admin, tous projets]**

Un utilisateur du rôle client ne voit que les projets où il figure dans l'équipe :

📷 **[Capture : dashboard.php — vue client, restreinte]**

## 4. Gestion des projets M&A

Le module Projets est le cœur de l'application : société cible, société acquéreur, statut de
l'opération, valeur estimée, calendrier.

📷 **[Capture : projects/list.php — liste avec recherche/filtres/pagination]**

La création d'un projet se fait via un formulaire dédié (réservé admin/conseiller) :

📷 **[Capture : projects/add.php — formulaire de création]**

La fiche projet centralise toutes les informations et donne accès, via la barre latérale, à
chacun des modules liés (data room, due diligence, Q&A, NDA, valorisation, offres) avec un
compteur d'éléments pour chacun :

📷 **[Capture : projects/view.php?id=1 — fiche projet et équipe]**

La modification reprend le même formulaire que la création (partiel `_form_fields.php` partagé) :

📷 **[Capture : projects/edit.php?id=1 — formulaire de modification]**

## 5. Data room virtuelle

La data room organise les documents d'un projet en arborescence de dossiers, avec dépôt sécurisé
et traçabilité complète des accès.

📷 **[Capture : dataroom/index.php?project_id=1 — arborescence et documents]**

Le dépôt d'un document vérifie l'extension (liste blanche) et la taille du fichier :

📷 **[Capture : dataroom/upload.php?project_id=1 — formulaire d'upload]**

Chaque consultation ou téléchargement de document est enregistré dans un journal d'accès,
consultable par les conseillers et administrateurs — c'est la piste d'audit de la data room :

📷 **[Capture : dataroom/log.php?project_id=1 — journal de consultation]**

Les documents marqués confidentiels reçoivent, lors du téléchargement, un filigrane dynamique
(nom de l'utilisateur + horodatage) si les dépendances Composer sont installées.

## 6. Due diligence

La due diligence est organisée en checklist par domaine : juridique, fiscal, financier,
commercial, RH, IT.

📷 **[Capture : duediligence/list.php?project_id=1 — checklist filtrable]**

Chaque point peut être marqué comme *red flag*, avec un impact financier estimé qui remonte
automatiquement au tableau de bord :

📷 **[Capture : un item avec red flag et impact_estime visibles]**

L'ajout d'un nouveau point de contrôle :

📷 **[Capture : duediligence/add.php?project_id=1 — formulaire d'ajout]**

Une synthèse est exportable en PDF pour diffusion :

📷 **[Capture : export PDF de due diligence]**

## 7. Questions / Réponses

Ce module centralise les échanges entre l'investisseur et l'équipe conseil au sujet des
documents de la data room.

📷 **[Capture : qa/list.php?project_id=1 — liste des questions/réponses]**

Un client pose sa question directement depuis l'interface :

📷 **[Capture : qa/ask.php?project_id=1 — poser une question]**

Le conseiller ou l'administrateur y répond, ce qui déclenche une notification email automatique
à l'auteur de la question :

📷 **[Capture : réponse à une question — vue conseiller]**

## 8. NDA — accord de confidentialité

Avant tout accès complet à la data room, l'investisseur doit signer électroniquement un accord de
confidentialité (NDA).

📷 **[Capture : ndas/list.php?project_id=1 — registre des signataires]**

Le texte de l'accord et la signature électronique :

📷 **[Capture : ndas/sign.php?project_id=1 — avant signature]**

Une fois signé, une empreinte SHA-256 horodatée est générée, garantissant la non-répudiation :

📷 **[Capture : confirmation après signature avec empreinte SHA-256]**

## 9. Valorisation

Trois méthodes de valorisation sont disponibles, confrontées dans un graphique comparatif de
type « football field ».

📷 **[Capture : valuation/list.php?project_id=1 — liste et graphique comparatif]**

**DCF (Gordon-Shapiro)** — actualisation des flux de trésorerie futurs :

📷 **[Capture : valuation/dcf.php?project_id=1]**

**Multiples VE/EBITDA** :

📷 **[Capture : valuation/multiples.php?project_id=1]**

**ANCC (Actif Net Comptable Corrigé)** :

📷 **[Capture : valuation/ancc.php?project_id=1]**

## 10. Offres et contre-offres

Le suivi de la négociation se fait par un historique d'offres et contre-offres, chacune avec un
statut modifiable en un clic.

📷 **[Capture : offers/list.php?project_id=1 — historique avec statuts]**

Une nouvelle offre ou contre-offre :

📷 **[Capture : offers/add.php?project_id=1 — formulaire]**

## 11. Administration

Réservée au rôle administrateur : gestion des comptes utilisateurs et de leurs rôles.

📷 **[Capture : users/list.php — liste des utilisateurs]**

📷 **[Capture : users/add.php — création d'un utilisateur avec rôle]**

Le journal d'audit horodate toute action sensible : connexions, créations, modifications,
suppressions, signatures NDA, exports.

📷 **[Capture : audit/list.php — journal d'audit avec recherche]**

Deux exports CSV sont disponibles : le journal d'audit et le contenu de la data room d'un
projet.

📷 **[Capture : export CSV du journal d'audit]**

## 12. Sécurité de la plateforme

Points de sécurité vérifiables et démontrables :

- **Requêtes préparées PDO** partout — aucune concaténation de variable dans une requête SQL.
- **Échappement systématique** de l'affichage via `htmlspecialchars()` (fonction `e()`).
- **Jeton CSRF** vérifié sur toute action de modification (POST) : visible en inspectant le code
  source d'un formulaire (champ caché `csrf_token`).
- **Contrôle d'accès** par rôle (`requireRole`) et par appartenance à l'équipe projet
  (`requireProjectAccess`) : un client qui tente d'accéder à un projet hors de son équipe reçoit
  un refus.
- **`.htaccess`** interdisant l'accès HTTP direct aux dossiers `config/`, `includes/`, `sql/`,
  `uploads/`.
- **Sessions sécurisées** : cookie `HttpOnly` + `SameSite=Lax`, régénération de l'identifiant de
  session à la connexion, expiration après inactivité.

📷 **[Capture : accès direct à config/database.php → bloqué par .htaccess]**

📷 **[Capture : client tentant d'ouvrir un projet hors équipe → accès refusé]**

📷 **[Capture : code source d'un formulaire montrant le champ csrf_token]**

## 13. Comptes de démonstration — récapitulatif

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@closingpoint.sn` | `Admin@2026` |
| Conseiller M&A | `conseiller@closingpoint.sn` | `Advisor@2026` |
| Analyste (conseiller) | `analyste@closingpoint.sn` | `Advisor@2026` |
| Client / Investisseur | `client@closingpoint.sn` | `Client@2026` |

---

*Fin du manuel utilisateur. Une fois les captures insérées, exporter ce document en PDF pour
constituer le livrable n°4 du cahier des charges (15-20 pages avec captures d'écran).*
