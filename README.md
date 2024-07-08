Ludo Facture est une application web de gestion de factures développée avec Symfony. Cette application permet aux utilisateurs de créer, mettre à jour, rechercher et envoyer des factures par email. Elle comprend également des fonctionnalités de gestion des utilisateurs telles que l'inscription, la connexion et la mise à jour du profil.

Fonctionnalités Principales
Gestion des Utilisateurs
Inscription : Les nouveaux utilisateurs peuvent s'enregistrer via un formulaire d'inscription.
Connexion et Déconnexion : Les utilisateurs peuvent se connecter et se déconnecter de l'application.
Mise à jour du Profil : Les utilisateurs peuvent mettre à jour leur adresse email et changer leur mot de passe.
Gestion des Factures
Création de Factures : Les utilisateurs peuvent créer des factures en remplissant un formulaire détaillé.
Mise à Jour des Factures : Les utilisateurs peuvent modifier les factures existantes.
Recherche de Factures : Les utilisateurs peuvent rechercher des factures par client via une barre de recherche.
Génération de PDF : Les factures peuvent être générées et sauvegardées en format PDF.
Envoi de Factures par Email
Envoi d'Email : Les utilisateurs peuvent envoyer des factures en pièce jointe par email à des clients en utilisant un bouton dédié.
Structure du Projet
Templates Twig
base.html.twig : Template de base étendu par les autres templates pour les différentes pages (connexion, inscription, création de facture, etc.).
Formulaires : Utilisation des formulaires Symfony pour gérer l'inscription, la mise à jour des factures, et l'envoi des factures.
Contrôleurs
SecurityController : Gère la connexion et la déconnexion des utilisateurs.
MAJFactureController : Gère la création, la mise à jour, l'affichage et l'envoi des factures.
Entités et Repositories
User : Entité représentant les utilisateurs de l'application.
UserRepository : Repository pour gérer les opérations de base de données liées aux utilisateurs.
Sécurité
Configuration de la Sécurité : Gestion de l'authentification et de l'autorisation des utilisateurs via security.yaml.
Mailing
Mailtrap : Utilisation de Mailtrap pour l'envoi d'emails de test.
Envoi de Factures : Fonctionnalités pour envoyer des factures en pièce jointe par email.

