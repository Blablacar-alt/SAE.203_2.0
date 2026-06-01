-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : lun. 01 juin 2026 à 07:35
-- Version du serveur : 8.0.40
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `sae203`
--

-- --------------------------------------------------------

--
-- Structure de la table `creneau`
--

CREATE TABLE `creneau` (
  `id_creneau` int NOT NULL,
  `id_salle` int NOT NULL,
  `id_date` int NOT NULL,
  `heure_debut` time DEFAULT NULL,
  `heure_fin` time DEFAULT NULL,
  `places_restante` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `date_expo`
--

CREATE TABLE `date_expo` (
  `id_date` int NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `element_programme`
--

CREATE TABLE `element_programme` (
  `id_element` int NOT NULL,
  `id_salle` int NOT NULL,
  `libelle` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `exposition`
--

CREATE TABLE `exposition` (
  `id_exposition` int NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `lieu` varchar(100) DEFAULT NULL,
  `description` tinytext,
  `annee` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `inscription`
--

CREATE TABLE `inscription` (
  `id_inscription` int NOT NULL,
  `id_creneau` int NOT NULL,
  `id_visiteur` int NOT NULL,
  `nb_personnes` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT NULL,
  `statut` varchar(100) DEFAULT NULL,
  `token_modification` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `salle`
--

CREATE TABLE `salle` (
  `id_salle` int NOT NULL,
  `numero` varchar(100) DEFAULT NULL,
  `nom_thematique` varchar(100) DEFAULT NULL,
  `sous_titre` tinytext,
  `description` tinytext,
  `capacite_max` int DEFAULT NULL,
  `exposition_id_exposition` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `visiteur`
--

CREATE TABLE `visiteur` (
  `id_visiteur` int NOT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profil` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD PRIMARY KEY (`id_creneau`),
  ADD KEY `fk_creneau_salle_idx` (`id_salle`),
  ADD KEY `fk_date_rdv_idx` (`id_date`);

--
-- Index pour la table `date_expo`
--
ALTER TABLE `date_expo`
  ADD PRIMARY KEY (`id_date`);

--
-- Index pour la table `element_programme`
--
ALTER TABLE `element_programme`
  ADD PRIMARY KEY (`id_element`),
  ADD KEY `fk_element_salle_idx` (`id_salle`);

--
-- Index pour la table `exposition`
--
ALTER TABLE `exposition`
  ADD PRIMARY KEY (`id_exposition`);

--
-- Index pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD PRIMARY KEY (`id_inscription`),
  ADD KEY `fk_creneau_nbr_idx` (`id_creneau`),
  ADD KEY `fk_nbr_visiteur_idx` (`id_visiteur`);

--
-- Index pour la table `salle`
--
ALTER TABLE `salle`
  ADD PRIMARY KEY (`id_salle`),
  ADD KEY `fk_salle_exposition1_idx` (`exposition_id_exposition`);

--
-- Index pour la table `visiteur`
--
ALTER TABLE `visiteur`
  ADD PRIMARY KEY (`id_visiteur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `creneau`
--
ALTER TABLE `creneau`
  MODIFY `id_creneau` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `date_expo`
--
ALTER TABLE `date_expo`
  MODIFY `id_date` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `element_programme`
--
ALTER TABLE `element_programme`
  MODIFY `id_element` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `exposition`
--
ALTER TABLE `exposition`
  MODIFY `id_exposition` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `inscription`
--
ALTER TABLE `inscription`
  MODIFY `id_inscription` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `salle`
--
ALTER TABLE `salle`
  MODIFY `id_salle` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `visiteur`
--
ALTER TABLE `visiteur`
  MODIFY `id_visiteur` int NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `creneau`
--
ALTER TABLE `creneau`
  ADD CONSTRAINT `fk_creneau_salle` FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_date_rdv` FOREIGN KEY (`id_date`) REFERENCES `date_expo` (`id_date`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `element_programme`
--
ALTER TABLE `element_programme`
  ADD CONSTRAINT `fk_element_salle` FOREIGN KEY (`id_salle`) REFERENCES `salle` (`id_salle`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `inscription`
--
ALTER TABLE `inscription`
  ADD CONSTRAINT `fk_creneau_nbr` FOREIGN KEY (`id_creneau`) REFERENCES `creneau` (`id_creneau`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `fk_nbr_visiteur` FOREIGN KEY (`id_visiteur`) REFERENCES `visiteur` (`id_visiteur`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Contraintes pour la table `salle`
--
ALTER TABLE `salle`
  ADD CONSTRAINT `fk_salle_exposition1` FOREIGN KEY (`exposition_id_exposition`) REFERENCES `exposition` (`id_exposition`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
