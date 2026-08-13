# Comprendre et présenter ClosingPoint — guide simple

Ce document explique ce que fait chaque écran de l'application, en langage courant, sans terme
informatique. L'objectif : que tu puisses présenter l'application avec assurance, comme si tu
l'avais conçue toi-même, en expliquant à quoi sert chaque partie et pourquoi elle existe.

---

## Vue d'ensemble : à quoi sert ClosingPoint ?

Imagine un cabinet de conseil qui accompagne le rachat d'une entreprise par une autre (une
fusion-acquisition). Ce processus dure plusieurs mois et implique beaucoup de monde : les
conseillers du cabinet, les dirigeants de l'entreprise qui achète, ceux de l'entreprise ciblée, et
parfois des investisseurs. Tout ce monde doit s'échanger des documents extrêmement confidentiels
(comptes, contrats, informations stratégiques), négocier un prix, vérifier qu'il n'y a pas de
mauvaise surprise cachée dans les comptes de la cible, et finalement se mettre d'accord.

Avant une application comme ClosingPoint, tout cela se faisait par emails et clés USB, sans
aucune trace de qui a vu quoi, ni de garantie de confidentialité. **ClosingPoint remplace tout
cela par un site web unique et sécurisé** où chaque étape du processus est organisée, tracée, et
accessible seulement aux bonnes personnes.

L'application distingue trois types d'utilisateurs :

- **L'administrateur** : la personne qui gère toute la plateforme (crée les comptes, surveille
  tout).
- **Le conseiller M&A** : la personne du cabinet qui pilote un dossier de rachat en particulier.
- **Le client / investisseur** : la personne qui veut racheter l'entreprise, ou qui a un intérêt
  dans l'opération. Elle ne voit que les dossiers qui la concernent.

---

## 1. La connexion

Pour utiliser l'application, on entre son email et son mot de passe sur la page d'accueil.

- Les mots de passe ne sont jamais stockés « en clair » : ils sont chiffrés, comme dans n'importe
  quelle banque en ligne. Même quelqu'un qui accéderait à la base de données ne pourrait pas lire
  les mots de passe des utilisateurs.
- Si quelqu'un se trompe 5 fois de suite en tapant son mot de passe, l'application bloque
  temporairement la connexion pendant une minute — une protection simple contre quelqu'un qui
  essaierait de deviner un mot de passe au hasard.
- Si on reste inactif pendant 20 minutes, l'application déconnecte automatiquement, comme un
  distributeur de billets qui se ferme si on ne fait rien.

**Ce qu'il faut retenir pour la présentation** : la sécurité des accès est pensée comme dans une
application professionnelle réelle, pas juste comme une démo.

---

## 2. Le tableau de bord (l'écran d'accueil après connexion)

C'est la première chose qu'on voit après s'être connecté. Il donne une vue d'ensemble de toute
l'activité, comme le tableau de bord d'une voiture donne une vue d'ensemble de son état.

On y trouve :
- **Des chiffres clés** : combien de dossiers de rachat sont en cours, quelle est leur valeur
  totale estimée, combien de points de vérification (due diligence, voir plus bas) sont déjà
  validés, combien de questions des investisseurs attendent encore une réponse.
- **Deux graphiques** : un qui montre la répartition des dossiers selon leur avancement
  (prospection, négociation, closing...), un autre qui montre l'état des vérifications par
  thème (juridique, fiscal, financier...).
- **Des messages d'alerte automatiques** : par exemple, si un problème grave (« red flag », voir
  plus bas) a été détecté sur un dossier et n'a pas encore été traité, un message d'alerte rouge
  apparaît directement pour attirer l'attention.

**Ce qu'il faut retenir** : ce tableau de bord donne, en un coup d'œil, une photo de tout le
portefeuille de dossiers de rachat en cours — utile pour un directeur ou un associé du cabinet qui
supervise plusieurs opérations à la fois.

---

## 3. Les projets de fusion-acquisition (le cœur de l'application)

Chaque « projet » représente un dossier de rachat : quelle entreprise veut racheter quelle autre,
à quel prix estimé, à quel stade en est-on.

### La liste des projets

On voit tous les dossiers en cours, avec une barre de recherche et des filtres (par exemple,
n'afficher que les dossiers « en négociation »). On peut chercher un dossier par le nom de
l'entreprise cible ou de l'acheteur.

### Créer un nouveau projet

Un formulaire permet de renseigner : un code interne pour identifier le dossier, le nom du
projet, le nom de l'entreprise cible, le nom de l'entreprise acheteuse, le secteur d'activité, le
stade d'avancement (prospection, due diligence, négociation, closing...), la valeur estimée de
l'opération, et les dates prévues.

Dès qu'un dossier est créé, la personne qui l'a créé devient automatiquement responsable de ce
dossier — elle y a donc toujours accès par la suite.

### La fiche d'un projet

C'est la page centrale d'un dossier. On y voit toutes ses informations, l'équipe de personnes qui
y travaillent, et des raccourcis vers chacune des étapes du dossier (la data room, les
vérifications, les questions, l'accord de confidentialité, l'évaluation du prix, les offres). À
côté de chaque raccourci, un petit chiffre indique combien d'éléments il contient — par exemple
« 12 documents » ou « 3 questions en attente ».

On peut aussi ajouter des personnes à l'équipe du dossier, en précisant leur rôle (chef de projet,
analyste, conseiller juridique, conseiller financier, ou simple observateur côté entreprise
cible).

**Ce qu'il faut retenir** : c'est la fiche projet qui sert de porte d'entrée vers tout le reste —
en présentation, c'est probablement l'écran par lequel tu commenceras ta démonstration après la
connexion.

---

## 4. La data room (l'échange de documents sécurisé)

C'est l'équivalent d'une salle des archives virtuelle et sécurisée, où l'on dépose tous les
documents liés à un dossier de rachat : comptes financiers, contrats, statuts juridiques, etc.

- Les documents sont organisés en **dossiers et sous-dossiers**, comme sur un ordinateur classique.
- Quand on dépose un fichier, on choisit sa catégorie (juridique, fiscal, financier, commercial,
  ressources humaines, informatique, ou autre) et on indique s'il est **confidentiel**.
- L'application refuse certains types de fichiers dangereux ou trop lourds, pour éviter les
  problèmes techniques ou de sécurité — seuls les formats habituels (PDF, Word, Excel, images,
  etc.) sont acceptés.
- **Chaque consultation ou téléchargement de document est enregistrée** : on sait précisément qui
  a téléchargé quel document, et à quel moment. C'est ce qu'on appelle le « journal de
  consultation », visible par les conseillers et l'administrateur.
- Pour les documents confidentiels au format PDF, l'application ajoute automatiquement un
  **filigrane** (un texte semi-transparent en diagonale) avec le nom de la personne qui télécharge
  et la date — un peu comme un tampon personnalisé. Si le document venait à fuiter, on saurait
  immédiatement qui l'a téléchargé.

**Ce qu'il faut retenir** : la data room est LA fonctionnalité qui donne tout son sens au
projet — c'est elle qui remplace les échanges non sécurisés par email, avec une vraie traçabilité
professionnelle.

---

## 5. La due diligence (les vérifications avant l'achat)

La due diligence, c'est l'ensemble des vérifications qu'on fait sur l'entreprise cible avant de
l'acheter, pour s'assurer qu'il n'y a pas de mauvaise surprise (dettes cachées, litiges en cours,
problèmes de conformité, etc.).

L'application organise ces vérifications en **six grands thèmes** : juridique, fiscal, financier,
commercial, ressources humaines, et informatique.

Pour chaque point de vérification, on précise :
- Son statut : à vérifier, en cours, validé, ou en alerte.
- S'il s'agit d'un **« red flag »** (un problème sérieux détecté) — dans ce cas, on peut chiffrer
  l'impact financier estimé de ce problème (par exemple, un litige qui pourrait coûter 50 millions
  de FCFA).
- Un responsable, choisi parmi les membres de l'équipe du dossier.

La liste peut être filtrée pour n'afficher que les red flags, ou seulement les points d'un thème
précis. Un point marqué red flag remonte toujours en haut de la liste, avec une ligne surlignée en
rouge pour bien le repérer.

Un rapport complet peut être exporté en PDF, avec un résumé chiffré : combien de points ont été
vérifiés, combien sont validés, et surtout — **la somme totale de l'impact financier estimé de
tous les red flags identifiés**. C'est ce chiffre qui permet à l'équipe de négocier une baisse de
prix si des problèmes sérieux sont découverts.

**Ce qu'il faut retenir** : ce module transforme un processus habituellement fait sur des
tableaux Excel dispersés en une checklist centralisée, avec un chiffrage financier direct des
risques — un vrai outil d'aide à la négociation.

---

## 6. Les questions / réponses

Pendant la due diligence, l'investisseur qui étudie le dossier a souvent des questions sur un
document ou un point précis (« Pourquoi cette charge exceptionnelle en 2024 ? »). Ce module lui
permet de poser sa question directement dans l'application plutôt que par email dispersé.

- N'importe qui ayant accès au dossier peut poser une question.
- Seuls les conseillers ou l'administrateur peuvent y répondre.
- Dès qu'une réponse est donnée, **la personne qui a posé la question reçoit automatiquement un
  email** l'informant qu'une réponse est disponible.

**Ce qu'il faut retenir** : ça centralise tous les échanges de questions dans un seul endroit,
consultable par toute l'équipe, plutôt que dispersés dans des boîtes mail individuelles.

---

## 7. L'accord de confidentialité (NDA)

Avant de pouvoir consulter les documents les plus sensibles, un investisseur doit d'abord
accepter et signer un accord de confidentialité — un engagement à ne pas divulguer les
informations qu'il va consulter.

- Le texte de l'accord mentionne précisément le nom de l'entreprise cible et de l'acheteur — il
  est donc unique à chaque dossier, pas un texte générique.
- Pour signer, la personne saisit son nom et coche une case d'acceptation.
- Une fois signé, l'application génère une **empreinte numérique unique** qui prouve, de façon
  incontestable, que telle personne a bien signé tel accord à telle date précise (à la seconde).
  C'est l'équivalent numérique d'une signature avec cachet et horodatage chez un notaire — sans
  avoir besoin d'un prestataire de signature électronique payant.
- Un registre liste tous les signataires d'un dossier, avec la date exacte de leur signature.

**Ce qu'il faut retenir** : c'est une signature électronique simplifiée mais réelle — elle prouve
qui a signé, quoi exactement, et quand, de façon vérifiable.

---

## 8. L'évaluation de l'entreprise (valorisation) — le cœur financier de l'outil

C'est ici que se joue la question centrale d'un rachat : **combien vaut vraiment l'entreprise
cible ?** L'application propose trois méthodes classiques d'évaluation financière, que tu connais
déjà en tant que comptable/financière — l'application les rend juste interactives.

### Méthode 1 — L'actualisation des flux de trésorerie futurs (DCF)

**Le principe** : une entreprise vaut la somme de tout l'argent qu'elle va générer dans le futur,
en tenant compte du fait qu'un euro/franc gagné demain vaut moins qu'un euro/franc gagné
aujourd'hui (c'est l'actualisation).

**Ce qu'on renseigne dans l'application** :
- Le flux de trésorerie disponible attendu pour la première année.
- Le taux de croissance de ce flux pour les années suivantes (par exemple 5% par an).
- Le taux d'actualisation (le « coût du capital », c'est-à-dire le rendement minimum exigé par les
  investisseurs — souvent appelé WACC).
- Un taux de croissance « à l'infini », plus faible, pour estimer la valeur de l'entreprise
  au-delà de la période détaillée (généralement 5 à 10 ans).
- Le nombre d'années sur lesquelles on projette précisément les flux (l'horizon).

**Ce que l'application calcule** : elle projette les flux de trésorerie année par année en
appliquant le taux de croissance, actualise chaque flux avec le taux d'actualisation, additionne
le tout, puis ajoute une « valeur terminale » qui représente tout ce que l'entreprise vaudra
au-delà de la période projetée. Le résultat final est la **valeur de l'entreprise dans son
ensemble** (avant de retirer les dettes).

**Une règle mathématique importante que l'application impose** : le taux de croissance à l'infini
doit toujours être strictement inférieur au taux d'actualisation. Sinon, le calcul deviendrait
absurde (une entreprise ne peut pas croître pour toujours plus vite que le rendement exigé par
ses investisseurs) — l'application refuse le calcul si cette règle n'est pas respectée, avec un
message d'erreur clair.

### Méthode 2 — Les multiples de marché (VE/EBITDA)

**Le principe** : on regarde à combien se sont vendues des entreprises comparables sur le marché,
exprimé sous forme de multiple de leur résultat d'exploitation (EBITDA), et on applique ce même
multiple à l'entreprise cible.

**Ce qu'on renseigne** : l'EBITDA de l'entreprise cible, le multiple de marché observé pour ce
secteur (saisi manuellement, en se basant sur son expérience ou des données de marché), et la
dette financière nette de l'entreprise.

**Ce que l'application calcule** : Valeur de l'entreprise = EBITDA × multiple. Puis, pour obtenir
la valeur qui reviendrait réellement aux actionnaires, on retire la dette nette : **Valeur des
actionnaires = Valeur de l'entreprise − Dette nette**.

**Point important à connaître** : c'est bien cette deuxième valeur (celle des actionnaires, après
déduction de la dette) qui est conservée et affichée dans les comparatifs — pas la valeur brute
de l'entreprise.

### Méthode 3 — L'actif net comptable corrigé (ANCC)

**Le principe** : on part de ce que possède réellement l'entreprise (son patrimoine), tel qu'il
apparaît dans ses comptes, puis on corrige ce chiffre pour tenir compte de la valeur réelle des
actifs (qui peut différer de leur valeur comptable historique) et des dettes qui ne seraient pas
encore inscrites au bilan.

**Ce qu'on renseigne** : l'actif net comptable de départ, les plus-values latentes (des biens qui
valent aujourd'hui plus que ce qui est inscrit dans les comptes), les moins-values latentes
(l'inverse), et le passif non comptabilisé (des dettes ou engagements pas encore inscrits, par
exemple un litige en cours).

**Ce que l'application calcule** :
**ANCC = Actif net comptable + Plus-values latentes − Moins-values latentes − Passif non comptabilisé.**

### Le graphique comparatif

Une fois plusieurs évaluations réalisées pour un même dossier, un graphique les affiche côte à
côte sous forme de barres, pour visualiser d'un coup d'œil la fourchette de valeurs obtenues selon
les différentes méthodes.

**Point à assumer si on te le demande** : les 3 méthodes ne mesurent pas exactement la même chose
au départ (le DCF donne la valeur de l'entreprise entière avant dette, les multiples donnent la
valeur revenant aux actionnaires après dette, l'ANCC est une approche patrimoniale) — en pratique
professionnelle, on les compare quand même car elles donnent toutes, in fine, un ordre de grandeur
de ce que vaut l'entreprise, mais il faut avoir cette nuance en tête. Chaque nouveau calcul est
conservé dans l'historique, rien n'est jamais écrasé — on garde ainsi la trace de toutes les
hypothèses testées au fil de la négociation.

---

## 9. Les offres et contre-offres (la négociation)

Une fois l'évaluation faite et la due diligence avancée, vient la négociation du prix final. Ce
module permet d'enregistrer chaque étape de la négociation : l'offre initiale de l'acheteur,
les éventuelles contre-offres du vendeur, et l'offre finale.

Pour chaque offre, on précise : son type (offre initiale, contre-offre, ou offre finale), le
montant proposé, la devise, la date, et les conditions particulières éventuelles (par exemple un
paiement différé).

Chaque offre a un statut qu'on peut changer en un clic : proposée, en négociation, acceptée, ou
refusée. Toutes les offres d'un dossier sont listées dans l'ordre chronologique, ce qui permet de
reconstituer facilement l'historique complet d'une négociation.

**Ce qu'il faut retenir** : c'est le module qui capture toute l'histoire de la négociation
financière, du premier montant proposé jusqu'à l'accord final.

---

## 10. L'administration (réservée à l'administrateur)

### Gestion des utilisateurs

L'administrateur peut créer de nouveaux comptes (en choisissant leur rôle : administrateur,
conseiller, ou client), modifier les informations d'un compte existant, ou désactiver un compte
qui ne doit plus avoir accès. Désactiver un compte ne le supprime pas définitivement — ses
informations et son historique restent conservés, mais il ne peut plus se connecter.

### Le journal d'audit

C'est un registre qui enregistre automatiquement toute action importante réalisée dans
l'application : chaque connexion, chaque création ou suppression de dossier, chaque signature de
NDA, chaque export de document, etc. — avec la date, l'heure, la personne responsable et l'adresse
réseau depuis laquelle l'action a été faite. C'est un outil de contrôle et de conformité,
consultable et exportable, seulement par l'administrateur.

**Ce qu'il faut retenir** : ce journal, c'est la « boîte noire » de l'application — il permet de
répondre à la question « qui a fait quoi, et quand ? » sur n'importe quelle action sensible.

---

## Récapitulatif pour ta présentation

Si tu devais résumer l'application en une phrase par module :

1. **Connexion** — accès sécurisé, à mot de passe chiffré, avec blocage anti-devinette.
2. **Tableau de bord** — vue d'ensemble chiffrée et alertes automatiques sur tout le portefeuille.
3. **Projets** — la fiche centrale de chaque dossier de rachat, avec son équipe.
4. **Data room** — l'échange sécurisé et tracé de tous les documents confidentiels.
5. **Due diligence** — la checklist des vérifications, avec chiffrage des risques détectés.
6. **Questions/réponses** — le canal centralisé de communication entre investisseur et conseil.
7. **NDA** — la signature électronique de l'engagement de confidentialité.
8. **Valorisation** — les trois méthodes classiques pour estimer le prix de l'entreprise.
9. **Offres** — l'historique complet de la négociation du prix.
10. **Administration/audit** — la gestion des accès et la traçabilité de tout ce qui se passe.

Relis ce document une ou deux fois, puis ouvre l'application en parallèle et refais le parcours
écran par écran en te demandant : « comment j'expliquerais ça à quelqu'un qui ne connaît pas
l'appli ? » — c'est exactement le niveau de clarté attendu à l'oral.
