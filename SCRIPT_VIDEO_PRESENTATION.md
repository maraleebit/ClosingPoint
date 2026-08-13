# Script — Vidéo de présentation ClosingPoint

Format : démo commandée (voix + écran), durée cible **~7 minutes**. Chaque bloc indique le
minutage, ce que tu **dis**, et ce que tu **montres/fais** à l'écran. Adapte le débit à ton rythme
naturel — les minutages sont indicatifs, pas un métronome.

**Avant l'enregistrement** : Apache/MySQL démarrés, 2-3 fenêtres/navigateurs prêts avec les
sessions admin/conseiller/client, projet de démo déjà peuplé (jeu de données fourni par
`sql/schema.sql`). Ferme les onglets et notifications parasites.

---

### 0:00 – 0:35 · Introduction

**Dire :**
> « Bonjour, je vous présente ClosingPoint, une plateforme web de gestion de projets de
> fusion-acquisition avec data room virtuelle, réalisée dans le cadre du Master CCA de l'École
> Supérieure Polytechnique de Dakar. L'objectif : accompagner un cabinet de conseil ou une
> direction financière tout au long d'un processus de M&A — évaluation de la cible, due
> diligence, échange documentaire sécurisé, et suivi jusqu'au closing. »

**Montrer :** page de connexion (`login.php`) déjà ouverte, ou un slide titre si tu en as un.

---

### 0:35 – 1:10 · Connexion & sécurité

**Dire :**
> « L'authentification repose sur trois profils : administrateur, conseiller M&A et client
> investisseur, avec des droits d'accès différenciés. Les mots de passe sont hachés en bcrypt, la
> session expire après 20 minutes d'inactivité, et chaque formulaire est protégé par un jeton
> CSRF. »

**Faire :** se connecter en tant que **conseiller** (`conseiller@ma-dataroom.sn`).

---

### 1:10 – 1:50 · Tableau de bord

**Dire :**
> « Une fois connecté, le tableau de bord affiche les KPI clés du portefeuille de projets, deux
> graphiques Chart.js alimentés par les données réelles de la base, et un moteur de commentaires
> automatiques qui signale par exemple les red flags de due diligence ou l'avancement d'un
> projet. »

**Montrer :** `dashboard.php` — pointer les KPI, les graphiques, un commentaire automatique.

---

### 1:50 – 2:40 · Projets M&A

**Dire :**
> « Le cœur de l'application est la gestion de projets M&A : société cible, acquéreur, statut,
> valeur estimée, calendrier. La liste propose recherche, filtres et pagination. »

**Faire :** ouvrir `modules/projects/list.php`, filtrer, ouvrir un projet (`view.php`) — montrer
la fiche projet et l'équipe associée, puis la sidebar avec les compteurs (data room, due
diligence, Q&A, NDA, valorisation, offres).

---

### 2:40 – 3:30 · Data room virtuelle

**Dire :**
> « Chaque projet dispose d'une data room virtuelle : arborescence de dossiers, dépôt sécurisé de
> documents avec liste blanche d'extensions, et surtout une traçabilité complète — chaque
> consultation ou téléchargement est enregistré dans un journal d'accès. Les PDF confidentiels
> reçoivent un filigrane dynamique au nom de l'utilisateur et horodaté. »

**Faire :** ouvrir `dataroom/index.php`, naviguer dans l'arborescence, ouvrir le journal
(`log.php`) pour montrer la traçabilité.

---

### 3:30 – 4:15 · Due diligence

**Dire :**
> « Le module de due diligence couvre six domaines — juridique, fiscal, financier, commercial, RH,
> IT — avec un statut par point de contrôle et un signalement des red flags accompagné d'un impact
> financier chiffré, qui remonte automatiquement dans le tableau de bord. Une synthèse est
> exportable en PDF. »

**Faire :** `duediligence/list.php`, filtrer sur un red flag, lancer l'export PDF.

---

### 4:15 – 4:45 · Questions / Réponses & NDA

**Dire :**
> « Le module Questions/Réponses permet à l'investisseur de poser des questions sur un document, avec
> notification email automatique dès la réponse du conseiller. Et avant tout accès à la data room,
> l'investisseur signe électroniquement un NDA — signature horodatée avec empreinte SHA-256
> non-répudiable. »

**Faire :** basculer sur la session **client**, poser une question (`qa/ask.php`), montrer
`ndas/sign.php` et le registre des signataires.

---

### 4:45 – 5:30 · Valorisation

**Dire :**
> « Trois méthodes de valorisation sont disponibles : le DCF selon Gordon-Shapiro, les multiples
> VE/EBITDA, et l'ANCC. Les résultats sont comparés visuellement dans un graphique de type
> football field. »

**Faire :** `valuation/dcf.php`, puis `list.php` pour le graphique comparatif.

---

### 5:30 – 6:00 · Offres et contre-offres

**Dire :**
> « Le suivi des négociations se fait via le module Offres : offre initiale, contre-offres
> successives, offre finale, avec changement de statut en un clic — proposée, en négociation,
> acceptée ou refusée. »

**Faire :** `offers/list.php`, changer le statut d'une offre en direct.

---

### 6:00 – 6:35 · Administration & audit

**Dire :**
> « Côté administration, la gestion des utilisateurs et de leurs rôles, et surtout un journal
> d'audit qui horodate toute action sensible — connexions, créations, suppressions, signatures,
> exports — exportable en CSV pour la conformité. »

**Faire :** session **admin** → `users/list.php`, puis `audit/list.php`, export CSV.

---

### 6:35 – 7:00 · Conclusion

**Dire :**
> « ClosingPoint couvre ainsi l'ensemble du cycle de vie d'une opération de fusion-acquisition,
> avec une sécurité applicative soignée — requêtes préparées PDO, échappement systématique, CSRF,
> contrôle d'accès par rôle et par équipe projet — pour un cas d'usage réaliste en finance
> d'entreprise. Merci de votre attention. »

**Montrer :** retour au tableau de bord ou slide de conclusion.

---

## Notes de tournage

- Grossis la fenêtre du navigateur / le zoom pour que le texte reste lisible en vidéo.
- Coupe les temps morts de chargement au montage plutôt qu'à l'oral.
- Si tu dois raccourcir à 5 min : fusionne les blocs 4 (Q&A+NDA) et 6 (offres) en une phrase de
  transition chacun, et retire le détail des 3 méthodes de valorisation (n'en montre qu'une).
