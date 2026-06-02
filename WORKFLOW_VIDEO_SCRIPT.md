# Script Vidéo : Workflow Cityplay 🎬

Ce document sert de guide pour la réalisation d'une vidéo de présentation du workflow complet de la plateforme **Cityplay**.

---

## 🔐 PARTIE 1 : WORKFLOW ADMINISTRATEUR
*Objectif : Montrer comment un administrateur prépare l'univers de jeu.*

### Étape 1 : Accès au Terminal
- **Action** : Se rendre sur la page de connexion.
- **Détail** : Saisir les identifiants administrateur.
- **Visuel** : Dashboard Admin avec les statistiques globales (Total énigmes, Taux de succès, Utilisateurs actifs).

### Étape 2 : Configuration des Cités
- **Action** : Cliquer sur "Villes" dans le menu latéral.
- **Détail** : Ajouter une nouvelle ville (ex: "Ouidah") avec son département.
- **Visuel** : Liste des villes s'actualisant avec la nouvelle entrée.

### Étape 3 : Déploiement des Lieux
- **Action** : Sélectionner une ville et ajouter un "Lieu" (ex: "Porte du Non-Retour").
- **Détail** : Placer le marqueur sur la carte interactive pour définir les coordonnées GPS.
- **Visuel** : Carte Leaflet avec le nouveau marqueur personnalisé.

### Étape 4 : Forge d'Énigmes
- **Action** : Cliquer sur "Énigmes" pour le lieu créé.
- **Détail** : 
    - Rédiger la question.
    - Définir la réponse correcte.
    - Choisir le niveau de difficulté (Facile, Intermédiaire, Difficile).
- **Visuel** : Formulaire de création d'énigme avec le nouveau style visuel

### Étape 5 : Système d'Assistance (Indices)
- **Action** : Dans le même formulaire, ajouter des indices.
- **Détail** :
    - Charger plusieurs images d'indices.
    - Ajouter des mots-clés textuels.
- **Visuel** : Section "Indices" avec prévisualisation des fichiers chargés.

### Étape 6 : Génération de Lien & Partage (Nouveau)
- **Action** : Dans le menu "Tous les lieux", cliquer sur "GÉNÉRER LIEN".
- **Détail** : 
    - Redirection vers le Setup de jeu.
    - L'admin choisit les paramètres (Niveau, Mode).
    - **Option de participation** : L'admin peut choisir de ne pas participer (mode "Maître du Jeu").
- **Visuel** : Interface de configuration simplifiée et Lobby avec le lien de partage prêt à être copié.

---

## 🎮 PARTIE 2 : WORKFLOW JOUEUR
*Objectif : Montrer l'expérience utilisateur et les différents modes de jeu.*

### Étape 1 : Inscription & Profil
- **Action** : Créer un compte joueur.
- **Détail** : Personnalisation du profil et accès au "Dashboard Joueur".
- **Visuel** : Interface de progression modernisée avec support complet du Mode Sombre.

### Étape 2 : Exploration & Choix de Mission
- **Action** : Cliquer sur "Jouer Maintenant" ou "Explorer".
- **Détail** : Sélectionner une ville ou utiliser un lien de partage généré par un admin.
- **Visuel** : Carte immersive avec les cités disponibles.

### Étape 3 : Configuration du Mode de Jeu
- **Action** : Choisir les paramètres de la session.
- **Modes de jeu à expliquer** :
    1. **Mode Solo** : Pour une aventure personnelle, lancement immédiat.
    2. **Mode Participants (Coop)** : Jouer ensemble pour résoudre la liste d'énigmes. Un joueur peut valider une énigme pour tout le groupe.
    3. **Mode Challengers (Compétition)** : Course au score. Qui résoudra le plus d'énigmes le plus rapidement ?
- **Visuel** : Interface de configuration (Setup) dynamique : si un lieu est déjà choisi via un lien admin, l'étape 1 est sautée automatiquement.

### Étape 4 : Le Lobby (Salle d'attente)
- **Action** : (Si Multi) Partager le lien d'invitation.
- **Détail** : Attendre que les amis rejoignent. L'hôte lance la partie.
- **Visuel** : Interface de Lobby avec les avatars des joueurs. L'admin peut y être présent sans prendre de place de joueur.
- **Visuel** : On peut rabattre la salle d'attente pour faire autre chose sur l'application (tout autre chose même entamer une autre session ou quitter l'application) et revenir dans la salle d'attente continuer la session en cours

### Étape 5 : Phase de Jeu Active
- **Action** : Résoudre une énigme sur le terrain.
- **Détail** :
    - Lecture de l'énigme liée au lieu.
    - **Mode Découverte** : Choix du moyen de transport (Pied, Moto, Voiture, Avion) pour adapter le chrono. Le chrono ne doit pas etre en dur, il doit dependre de la distance entre la localisation actuelle du l'utilisateur et la localisation du lieu à decouvrir selon le maps et le moyen de deplacement 
    - **Utilisation d'un indice** : Cliquer sur "Besoin d'aide ?" pour révéler une image ou un mot-clé.
    - Saisie de la réponse.
- **Visuel** : Interface `ActiveRiddle` corrigée et optimisée (compte à rebours fluide, boussole interactive).

### Étape 6 : Triomphe & Progression
- **Action** : Validation de la réponse.
- **Détail** : Gain d'XP et mise à jour du classement (Leaderboard). les gains XP peuvent être utilisés pour debloquer des niveaux, gagner ou ajouter du temps au chrono, acheter des choses sur la plateforme.
- **Visuel** : Écran de victoire avec les points récoltés et barre de progression de niveau.