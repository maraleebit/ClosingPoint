# Script — Vidéo de présentation ClosingPoint

Durée cible **10-12 minutes** (fourchette imposée : 8-15 min), structure conforme au cahier des
charges (Master CCA — ESP Dakar, Projet 36). Chaque bloc indique le minutage, ce que tu **dis**
et ce que tu **montres**. Les minutages sont indicatifs — respecte surtout la fourchette globale
et l'ordre des 6 parties imposées.

**Avant l'enregistrement** : Apache/MySQL démarrés, base `closingpoint` importée, 2-3
navigateurs/onglets prêts avec les sessions admin/conseiller/client déjà ouvertes ou faciles à
ouvrir. Ferme les onglets et notifications parasites. Zoome le navigateur pour la lisibilité.

---

## 1. Présentation de l'étudiant et introduction du sujet (1-2 min)

**Dire :**
> « Bonjour, je m'appelle [ton prénom nom], je suis étudiant(e) en Master CCA — Comptabilité,
> Contrôle, Audit — à l'École Supérieure Polytechnique de Dakar. Dans le cadre du projet "70
> Projets Web / XAMPP" encadré par M. Ousmane LY, j'ai choisi le sujet n°36 : une plateforme web
> de gestion de projets de fusion-acquisition (M&A) avec data room virtuelle, que j'ai nommée
> ClosingPoint. »

**Montrer :** ton visage/webcam si le format le permet, ou un slide titre avec ton nom et le
sujet, puis la page de connexion de l'application.

## 2. Problématique et objectifs de la plateforme (1 min)

**Dire :**
> « Un processus de fusion-acquisition mobilise de nombreux intervenants — cabinet conseil,
> acquéreur, cible, investisseurs — autour de documents ultra-confidentiels échangés par email ou
> clé USB, sans traçabilité ni sécurité. La problématique était donc : comment structurer
> numériquement tout le cycle M&A — évaluation de la cible, due diligence multi-domaines, échange
> documentaire sécurisé, négociation — dans une plateforme unique, sécurisée et traçable ?
> L'objectif est d'offrir un outil professionnel utilisable par un cabinet de conseil réel. »

**Montrer :** le tableau de bord (vue d'ensemble) ou rester sur un slide.

## 3. Architecture technique et choix des technologies (1-2 min)

**Dire :**
> « Conformément au cahier des charges, la plateforme repose sur XAMPP : Apache, MySQL et PHP 8
> en procédural avec des requêtes préparées PDO pour se protéger des injections SQL. Le front-end
> utilise Bootstrap 5 pour le responsive, et Chart.js pour les graphiques dynamiques du tableau de
> bord. J'ai structuré le code en mini-MVC : un dossier `includes/` pour la sécurité,
> l'authentification et les fonctions métier partagées, et un dossier `modules/` avec un
> sous-dossier par entité — projets, data room, due diligence, Q&A, NDA, valorisation, offres,
> utilisateurs, audit — chacun avec ses pages list/add/edit/delete selon les besoins. La base de
> données compte 12 tables liées par clés étrangères. Pour l'export PDF j'utilise DOMPDF, avec un
> filigrane dynamique via FPDI/TCPDF sur les documents confidentiels, et un repli en HTML
> imprimable si Composer n'est pas installé, pour que l'application reste fonctionnelle sur tout
> poste XAMPP standard. »

**Montrer :** rapidement l'arborescence du projet dans l'explorateur de fichiers ou un éditeur de
code, ou le schéma du document technique (`DOCUMENTATION_TECHNIQUE.md`).

## 4. Démonstration en direct des fonctionnalités (5-8 min)

### 4.1 Authentification et sécurité (~0:45)
**Dire :** « L'authentification distingue trois profils : administrateur, conseiller M&A et
client investisseur. Les mots de passe sont hachés en bcrypt, la session expire après 20 minutes
d'inactivité, et chaque formulaire de modification est protégé par un jeton CSRF. »
**Faire :** se connecter en **conseiller** (`conseiller@ma-dataroom.sn`).

### 4.2 Tableau de bord (~0:45)
**Dire :** « Le tableau de bord affiche les KPI du portefeuille, deux graphiques Chart.js
alimentés par les données réelles, et un moteur de commentaires automatiques — par exemple une
alerte sur un red flag de due diligence. »
**Montrer :** `dashboard.php`.

### 4.3 Projets M&A — CRUD (~0:45)
**Dire :** « Le module projets couvre le CRUD complet : cible, acquéreur, statut, valeur estimée,
avec recherche, filtres multi-critères et pagination sur la liste. »
**Faire :** `projects/list.php` → filtrer → ouvrir une fiche projet (`view.php`) avec son équipe.

### 4.4 Data room virtuelle (~1:00)
**Dire :** « Chaque projet a sa data room : arborescence de dossiers, upload sécurisé avec liste
blanche d'extensions, et traçabilité complète — chaque consultation est journalisée. Les PDF
confidentiels reçoivent un filigrane dynamique au nom de l'utilisateur et horodaté. »
**Faire :** `dataroom/index.php`, naviguer, ouvrir le journal de consultation (`log.php`).

### 4.5 Due diligence (~0:45)
**Dire :** « La due diligence couvre six domaines — juridique, fiscal, financier, commercial, RH,
IT — avec signalement des red flags et impact financier chiffré, remonté automatiquement au
tableau de bord. Export PDF de synthèse disponible. »
**Faire :** `duediligence/list.php`, filtrer un red flag, lancer l'export PDF.

### 4.6 Q&A et NDA (~0:45)
**Dire :** « Les investisseurs posent leurs questions via le module Q&A avec notification email
automatique à la réponse, et signent électroniquement le NDA avant tout accès — signature
horodatée avec empreinte SHA-256 non-répudiable. »
**Faire :** basculer en session **client**, poser une question, montrer `ndas/sign.php`.

### 4.7 Valorisation (~0:45)
**Dire :** « Trois méthodes de valorisation : DCF selon Gordon-Shapiro, multiples VE/EBITDA, et
ANCC, comparées visuellement dans un graphique football field. »
**Faire :** `valuation/dcf.php` puis `list.php`.

### 4.8 Offres et administration (~0:45)
**Dire :** « Le suivi des négociations se fait via les offres et contre-offres avec changement de
statut en un clic. Côté administration, la gestion des utilisateurs et un journal d'audit qui
horodate toute action sensible, exportable en CSV. »
**Faire :** `offers/list.php` (changer un statut), puis session **admin** →
`users/list.php` → `audit/list.php` → export CSV.

## 5. Difficultés rencontrées et solutions apportées (1 min)

**Dire (à adapter à ton vécu réel — sois concret) :**
> « La principale difficulté a été [exemple : gérer le filigrane dynamique des PDF confidentiels
> sans dépendance obligatoire, pour que l'application reste utilisable même sans `composer
> install`]. J'ai résolu cela en [détecter la présence des librairies et basculer automatiquement
> sur un rendu HTML imprimable en repli]. Une autre difficulté a été [ex: la gestion des droits
> d'accès différenciés par rôle et par équipe projet pour le client, afin qu'un investisseur ne
> voie jamais les projets auxquels il n'est pas rattaché] — résolue via la fonction
> `requireProjectAccess()` vérifiée systématiquement en entrée de chaque module. »

> ⚠️ Personnalise ce bloc avec tes propres difficultés réelles — le jury évalue ta capacité à
> expliquer et justifier ton propre travail, pas seulement le résultat.

## 6. Apports personnels et perspectives d'amélioration (1 min)

**Dire (à adapter) :**
> « Au-delà du socle demandé, j'ai ajouté [ex: le moteur de commentaires automatiques du tableau
> de bord, le calcul d'impact financier cumulé des red flags, ou la bascule automatique des
> exports PDF]. Comme perspectives d'amélioration, je pense à [ex: une notification en temps réel
> lors du dépôt d'un nouveau document dans la data room, une signature électronique NDA via un
> prestataire tiers certifié, ou un module de reporting consolidé multi-projets pour un cabinet
> gérant plusieurs deals en parallèle]. »

**Dire (conclusion) :**
> « Merci de votre attention. »

**Montrer :** retour au tableau de bord ou slide de conclusion.

---

## Notes de tournage

- Respecte l'ordre des 6 parties du cahier des charges — c'est ce que le jury et tes camarades
  (notation collective à 20 %) attendent de retrouver.
- Coupe les temps morts de chargement au montage plutôt qu'à l'oral.
- Vise 10-12 min : en dessous de 8 min ou au-dessus de 15 min, tu sors de la fourchette imposée.
- Dépose la vidéo sur Google Drive, YouTube (non répertorié) ou WeTransfer, puis transmets le
  lien à l'enseignant — c'est lui qui le partage avec la promotion pour la notation collective.
