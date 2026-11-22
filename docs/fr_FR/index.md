---
layout: default
lang: fr_FR
---

Description
===

DevRepo met à jour vos plugins **en cours de développement** directement depuis une branche de leur dépôt Git (**Gogs**, **Gitea** ou **GitHub**), sans git ni accès SSH à la box. C'est particulièrement pratique quand Jeedom tourne dans un conteneur Docker.

Pour chaque plugin suivi, un clic sur **Mettre à jour** télécharge l'archive de la branche, vérifie qu'il s'agit bien du bon plugin, remplace ses fichiers, puis lance sa fonction de mise à jour (comme une mise à jour classique depuis le Market).

> Réservé au développement : les fichiers absents de la branche ne sont pas supprimés, et aucune sauvegarde n'est faite avant la mise à jour.

Configuration
===

![Liste des plugins suivis](../images/devrepo_screenshot.png)

La liste des plugins suivis se trouve sur la page du plugin : **Plugins → Programmation → DevRepo**. Pour chaque plugin :

- **Plugin** : son identifiant (nom de son dossier dans `plugins/`), ex. `groupir`. Les plugins installés sont proposés. Un plugin pas encore installé peut être indiqué : DevRepo crée son dossier, il reste à l'activer dans la gestion des plugins ;
- **Dépôt** : URL du dépôt, ex. `https://serveur/proprietaire/depot.git` ou `https://github.com/proprietaire/depot` ;
- **Branche** : branche à suivre (par défaut `beta`) ;
- **Auto** : met à jour le plugin automatiquement à chaque nouveau commit sur la branche (voir ci-dessous) ;
- **Jeton d'accès** : nécessaire si le dépôt est privé (Gogs / Gitea : *Paramètres → Applications* ; GitHub : *Settings → Developer settings → Personal access tokens*). Il est stocké **chiffré** et n'est jamais réaffiché : laissez le champ vide pour le conserver, ou utilisez la gomme pour le supprimer.

Cliquez sur **Enregistrer** (la liste est aussi enregistrée automatiquement avant une mise à jour).

Utilisation
===

- **Vérifier** : compare la dernière mise à jour de chaque plugin avec le dernier commit de sa branche (*à jour* ou *nouveau commit*) ;
- **Mettre à jour** : récupère la branche et remplace les fichiers du plugin. La colonne *Dernière MAJ* indique la date et le commit installés (détail au survol).

Mise à jour automatique
===

Pour les plugins dont la case **Auto** est cochée, DevRepo vérifie la branche **toutes les 5 minutes** : dès qu'un nouveau commit est poussé, le plugin est mis à jour comme avec le bouton *Mettre à jour*. Une icône de robot signale dans *Dernière MAJ* une mise à jour faite automatiquement.

- La tâche correspond à la ligne **cron5** du panneau *Fonctionnalités* de la configuration du plugin : la désactiver suspend toutes les mises à jour automatiques ;
- si la mise à jour d'un commit échoue, elle n'est pas retentée en boucle : DevRepo attend le commit suivant (ou un clic sur *Mettre à jour*). L'erreur est dans le log `devrepo` ;
- un dépôt temporairement injoignable est simplement revérifié au passage suivant.

Webhook : mise à jour immédiate
===

Plutôt que d'attendre la vérification, le dépôt peut prévenir Jeedom à chaque push. Le bouton <i class="fas fa-plug"></i> d'une ligne affiche l'URL du webhook de ce plugin (accès interne et externe de Jeedom) :

- dans le dépôt : *Paramètres → Webhooks* (Gogs, Gitea ou GitHub), collez l'URL, événement **push**, type de contenu **JSON** ;
- la mise à jour ne se fait que si **Auto** est coché et que la branche poussée est celle suivie ;
- le dépôt doit pouvoir joindre Jeedom : un Gogs / Gitea du réseau local utilise l'accès interne ; GitHub a besoin de l'accès externe.

L'URL contient la clé API de DevRepo (*Réglages → Système → Configuration → API*) : ne la partagez pas.

Mode temps réel
===

Si le dépôt ne peut pas joindre Jeedom (GitHub et box non exposée sur internet), le bouton **Temps réel** vérifie les branches des plugins **Auto** **toutes les 10 secondes pendant 1 heure** (par défaut), le temps d'une session de développement. Le bouton affiche le temps restant ; un clic l'arrête. Pendant ce mode, la vérification toutes les 5 minutes est suspendue.

La durée se choisit dans la liste à côté du bouton : de 15 minutes à 8 heures. Elle est prise en compte à la prochaine activation.

Sur GitHub sans jeton (60 requêtes par heure au maximum), la vérification est espacée à 90 secondes : renseignez un jeton pour profiter des 10 secondes.

Notification
===

Chaque mise à jour automatique (webhook, temps réel ou vérification toutes les 5 minutes) affiche une notification sur toutes les pages de Jeedom ouvertes, avec le commit installé, ou l'erreur en cas d'échec. Si la page DevRepo est ouverte, la ligne du plugin est rafraîchie.

DevRepo peut aussi se mettre à jour lui-même : ajoutez `devrepo` à la liste avec son dépôt.

En cas d'erreur, un message explicite s'affiche (dépôt introuvable, jeton refusé, archive qui ne contient pas le bon plugin, droits d'écriture...). Le détail est dans le log `devrepo`.
