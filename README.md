# Traitement Automatique des Données XML

Ce script PHP automatise le traitement d'un dossier compressé (ZIP) contenant plusieurs fichiers XML. Il permet d'extraire les fichiers XML, d'exploiter les données qu'ils contiennent et de les insérer dans une base de données MySQL gérée via phpMyAdmin. Chaque fichier XML correspond à une table spécifique de la base de données.

## Fonctionnalités

1. **Extraction du ZIP**  
   Le script utilise la classe `ZipArchive` pour extraire les fichiers XML d'un dossier compressé.

2. **Lecture et Traitement des Fichiers XML**  
   Chaque fichier XML est lu à l'aide de `SimpleXML` pour parser et exploiter les données.

3. **Insertion dans la Base de Données**  
   Les données extraites sont insérées dans une base de données MySQL. Chaque fichier XML correspond à une table spécifique.

## Prérequis

- **PHP** (version 7.4 ou supérieure recommandée)
- Extensions PHP nécessaires :  
  - `zip`  
  - `SimpleXML`  
  - `mysqli` ou `PDO` (pour la connexion à MySQL)
- Un serveur MySQL (gestion via phpMyAdmin)
- Accès en lecture/écriture dans le répertoire de travail

## Installation

1. **Téléchargement du Code**  
   Clonez ou téléchargez le code source dans votre environnement de développement.

2. **Configuration**  
   - Mettez à jour les informations de connexion à la base de données dans le script (hôte, nom d'utilisateur, mot de passe, nom de la base de données).
   - Assurez-vous que toutes les extensions PHP requises sont activées.

## Utilisation

1. **Préparation du Fichier ZIP**  
   Déposez le fichier ZIP contenant les fichiers XML dans le répertoire prévu à cet effet.

2. **Exécution du Script**  
   Vous pouvez exécuter le script en ligne de commande :

   ```bash
   php script.php
