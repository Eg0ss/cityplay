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
- **Visuel** : Formulaire de création d'énigme.

### Étape 5 : Système d'Assistance (Indices)
- **Action** : Dans le même formulaire, ajouter des indices.
- **Détail** :
    - Charger plusieurs images d'indices.
    - Ajouter des mots-clés textuels.
- **Visuel** : Section "Indices" avec prévisualisation des fichiers chargés.

---

## 🎮 PARTIE 2 : WORKFLOW JOUEUR
*Objectif : Montrer l'expérience utilisateur et les différents modes de jeu.*

### Étape 1 : Inscription & Profil
- **Action** : Créer un compte joueur.
- **Détail** : Personnalisation du profil et accès au "Dashboard Joueur".
- **Visuel** : Interface de progression avec les badges et le niveau d'XP (Aspirant, Explorateur, etc.).

### Étape 2 : Exploration & Choix de Mission
- **Action** : Cliquer sur "Jouer Maintenant" ou "Explorer".
- **Détail** : Sélectionner une ville sur la carte du Bénin.
- **Visuel** : Carte immersive avec les cités disponibles.

### Étape 3 : Configuration du Mode de Jeu
- **Action** : Choisir les paramètres de la session.
- **Modes de jeu à expliquer** :
    1. **Mode Solo** : Pour une aventure personnelle, lancement immédiat.
    2. **Mode Participants (Coop)** : Jouer ensemble pour résoudre la liste d'énigmes. Un joueur peut valider une énigme pour tout le groupe.
    3. **Mode Challengers (Compétition)** : Course au score. Qui résoudra le plus d'énigmes le plus rapidement ?
- **Visuel** : Interface de configuration (Setup) avec sélection du niveau et du nombre d'énigmes.

### Étape 4 : Le Lobby (Salle d'attente)
- **Action** : (Si Multi) Partager le lien d'invitation.
- **Détail** : Attendre que les amis rejoignent. L'hôte lance la partie.
- **Visuel** : Interface de Lobby avec les avatars des joueurs qui apparaissent en temps réel.

### Étape 5 : Phase de Jeu Active
- **Action** : Résoudre une énigme sur le terrain.
- **Détail** :
    - Lecture de l'énigme liée au lieu.
    - **Utilisation d'un indice** : Cliquer sur "Besoin d'aide ?" pour révéler une image ou un mot-clé (attention : réduit les points gagnés).
    - Saisie de la réponse.
- **Visuel** : Interface `ActiveRiddle` avec le compte à rebours et la carte.

### Étape 6 : Triomphe & Progression
- **Action** : Validation de la réponse.
- **Détail** : Gain d'XP et mise à jour du classement (Leaderboard).
- **Visuel** : Écran de victoire avec les points récoltés et barre de progression de niveau.
