# Script — Vidéo de présentation ClosingPoint

Durée cible **10-12 minutes** (fourchette imposée par le cahier des charges : 8-15 min),
structure en 6 parties imposées. Langage volontairement simple et métier — aucun terme
informatique non expliqué — cohérent avec le guide « Comprendre ClosingPoint ».

**Avant l'enregistrement** : Apache/MySQL démarrés, 2-3 navigateurs/onglets prêts avec les
sessions admin/conseiller/client, projets de démonstration déjà en place (Baobab, Fleuve,
Teranga, Sahel Digital, Lumière, Kalao, Mirage — 6 statuts différents visibles au tableau de
bord). Ferme les onglets et notifications parasites, zoome le navigateur pour la lisibilité.

---

## 1. Présentation de l'étudiant et introduction du sujet (1-2 min)

**Dire :**
> « Bonjour, je m'appelle [ton prénom nom], je suis étudiante en Master CCA — Comptabilité,
> Contrôle, Audit — à l'École Supérieure Polytechnique de Dakar. Dans le cadre du projet "70
> Projets Web / XAMPP" encadré par M. Ousmane LY, j'ai choisi le sujet n°36 : une plateforme de
> gestion de projets de fusion-acquisition avec data room virtuelle, que j'ai appelée
> ClosingPoint. »

**Montrer :** la page de connexion.

## 2. Problématique et objectifs de la plateforme (1 min)

**Dire :**
> « Un rachat d'entreprise mobilise beaucoup de monde — le cabinet de conseil, l'acheteur, la
> cible, parfois des investisseurs — autour de documents extrêmement confidentiels. Sans outil
> dédié, tout cela se fait par emails et clés USB, sans aucune trace de qui a vu quoi, ni garantie
> de confidentialité. L'objectif de ClosingPoint est de remplacer ça par une plateforme unique et
> sécurisée où chaque étape du processus — évaluation de la cible, vérifications, échange de
> documents, négociation — est organisée, tracée, et réservée aux bonnes personnes. »

**Montrer :** rester sur la page de connexion ou basculer sur le tableau de bord.

## 3. Architecture technique et choix des technologies (1-2 min)

**Dire :**
> « Conformément au cahier des charges, la plateforme repose sur XAMPP : Apache, MySQL et PHP 8
> en procédural, avec des requêtes préparées PDO pour se protéger des injections SQL. Le
> front-end utilise Bootstrap 5 pour le rendu responsive et Chart.js pour les graphiques
> dynamiques du tableau de bord. Le code est organisé en un dossier par grande fonctionnalité —
> projets, data room, due diligence, questions/réponses, NDA, valorisation, offres, utilisateurs,
> audit — avec une base de données de 12 tables liées entre elles. Pour les exports, j'utilise
> DOMPDF pour le PDF, avec un mécanisme de repli automatique en HTML imprimable si les
> dépendances ne sont pas installées, afin que l'application reste utilisable sur n'importe quel
> poste XAMPP standard sans configuration complexe. »

**Montrer :** rapidement l'arborescence du projet, ou une page du document technique.

## 4. Démonstration en direct des fonctionnalités (5-8 min)

### 4.1 Connexion et sécurité (~0:40)
**Dire :** « Trois profils existent : administrateur, conseiller M&A et client investisseur. Les
mots de passe sont chiffrés, jamais stockés en clair, et après 5 échecs de connexion l'accès est
bloqué temporairement. »
**Faire :** se connecter en **conseiller** (`conseiller@closingpoint.sn`).

### 4.2 Tableau de bord (~0:45)
**Dire :** « Le tableau de bord donne une vue d'ensemble chiffrée de tout le portefeuille :
projets actifs, valeur du pipeline, red flags ouverts, avancement des vérifications — avec des
messages d'alerte automatiques quand un point nécessite une attention immédiate. »
**Montrer :** le camembert de répartition des projets (les 6 couleurs/statuts) et les alertes.

### 4.3 Projets M&A (~0:45)
**Dire :** « Chaque dossier de rachat a sa fiche : société cible, acquéreur, statut, valeur
estimée, équipe assignée avec des rôles différenciés. »
**Faire :** liste des projets → ouvrir une fiche (ex: Projet Kalao, statut Closing).

### 4.4 Data room (~1:00)
**Dire :** « La data room est le cœur du projet : un espace sécurisé pour déposer les documents
confidentiels, organisés en dossiers. Chaque consultation ou téléchargement est enregistré — on
sait toujours qui a vu quoi et quand. Les PDF confidentiels reçoivent en plus un filigrane
dynamique avec le nom du téléchargeur et la date, pour dissuader toute fuite. »
**Faire :** ouvrir la data room d'un projet, montrer l'arborescence.

### 4.5 Due diligence (~0:45)
**Dire :** « La due diligence couvre six domaines de vérification. Un point signalé comme "red
flag" est chiffré financièrement — c'est ce montant cumulé qui permet de négocier une baisse de
prix si des problèmes sérieux sont découverts. »
**Faire :** liste due diligence, montrer un red flag et son impact estimé.

### 4.6 Q&A et NDA (~0:45)
**Dire :** « Les investisseurs posent leurs questions directement dans l'application, avec
notification automatique par email dès la réponse. Et avant tout accès complet à la data room,
ils signent électroniquement un accord de confidentialité — la signature génère une empreinte
numérique unique qui prouve qui a signé, quoi, et quand. »
**Faire :** montrer l'écran NDA (texte + signature).

### 4.7 Valorisation (~0:45)
**Dire :** « Trois méthodes classiques d'évaluation : l'actualisation des flux de trésorerie
futurs, les multiples de marché, et l'actif net comptable corrigé — comparées visuellement dans
un graphique dès que plusieurs évaluations existent pour un même projet. »
**Faire :** ouvrir le formulaire DCF, montrer un résultat calculé.

### 4.8 Offres et administration (~0:45)
**Dire :** « Le suivi de la négociation se fait via les offres et contre-offres, avec changement
de statut en un clic. Côté administration, la gestion des comptes et un journal d'audit qui
horodate toute action sensible, exportable en CSV. »
**Faire :** offres → changer un statut ; session **admin** → utilisateurs → journal d'audit.

## 5. Difficultés rencontrées et solutions apportées (1 min)

**Dire :**
> « J'ai rencontré trois difficultés principales. La première concernait le filigrane dynamique
> des PDF confidentiels : il repose sur des librairies externes qui ne sont pas toujours
> installées. J'ai résolu cela en détectant leur présence au moment du téléchargement, avec un
> repli silencieux sur le fichier original si elles manquent — le téléchargement ne bloque
> jamais, et la traçabilité en base reste garantie dans tous les cas.
>
> La deuxième portait sur le calcul de valorisation par actualisation des flux de trésorerie : la
> formule de Gordon-Shapiro devient mathématiquement absurde si le taux de croissance à l'infini
> dépasse le taux d'actualisation — j'ai donc ajouté une validation qui bloque le calcul avec un
> message explicite plutôt que de laisser afficher un résultat incohérent.
>
> La troisième était la gestion des droits d'accès à trois niveaux — le rôle de la personne, et
> son appartenance ou non à l'équipe d'un projet précis. Plutôt que de répéter cette vérification
> dans chaque écran, je l'ai centralisée dans deux fonctions réutilisables, appelées
> systématiquement en début de chaque page, pour garantir la cohérence de la sécurité partout
> dans l'application. »

## 6. Apports personnels et perspectives d'amélioration (1 min)

**Dire :**
> « Au-delà du socle demandé, j'ai ajouté un moteur de commentaires automatiques sur le tableau
> de bord qui traduit les chiffres en alertes lisibles pour un directeur — par exemple sur les
> red flags non traités — ainsi que le calcul automatique de l'impact financier cumulé des
> risques détectés dans l'export PDF de due diligence.
>
> Comme perspectives d'amélioration, je vois surtout deux axes : d'abord, relier explicitement
> une offre à la contre-offre qui lui répond, pour reconstituer automatiquement le fil complet
> d'une négociation plutôt que de se fier seulement à l'ordre chronologique ; ensuite, harmoniser
> les trois méthodes de valorisation pour qu'elles mesurent rigoureusement la même chose — la
> valeur des fonds propres — avant de les comparer sur le même graphique, ce qui rendrait la
> comparaison entre méthodes encore plus rigoureuse d'un point de vue financier.
>
> Merci de votre attention. »

**Montrer :** retour au tableau de bord.

---

## Notes de tournage

- Respecte l'ordre des 6 parties — c'est ce que le jury et tes camarades (notation collective à
  20 %) attendent de retrouver.
- Coupe les temps morts de chargement au montage plutôt qu'à l'oral.
- Vise 10-12 min : en dessous de 8 min ou au-dessus de 15 min, tu sors de la fourchette imposée.
- Les parties 5 et 6 sont rédigées pour toi mais restent **ta** matière : reformule-les avec tes
  propres mots à l'oral pour que ça sonne naturel, pas récité.
- Dépose la vidéo sur Google Drive, YouTube (non répertorié) ou WeTransfer, puis transmets le
  lien à l'enseignant.
