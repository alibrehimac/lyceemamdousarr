-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : lun. 10 fév. 2025 à 14:34
-- Version du serveur : 5.7.24
-- Version de PHP : 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `alamal1lms`
--

-- --------------------------------------------------------

--
-- Structure de la table `associer`
--

CREATE TABLE `associer` (
  `code_filiere` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `coefficient` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `associer`
--

INSERT INTO `associer` (`code_filiere`, `id_matiere`, `coefficient`) VALUES
(10, 2, 1),
(11, 1, 4),
(11, 2, 1),
(11, 4, 1),
(11, 6, 2),
(11, 8, 3),
(11, 9, 2),
(11, 10, 1),
(11, 11, 1),
(11, 18, 1),
(11, 19, 4),
(11, 21, 1),
(11, 26, 3);

-- --------------------------------------------------------

--
-- Structure de la table `bulletin`
--

CREATE TABLE `bulletin` (
  `id_bulletin` int(11) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `idpromotion` int(11) NOT NULL,
  `idperiode` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `classe`
--

CREATE TABLE `classe` (
  `idClasse` int(11) NOT NULL,
  `nom_classe` varchar(50) NOT NULL,
  `code_filiere` int(11) NOT NULL,
  `idpromotion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `classe`
--

INSERT INTO `classe` (`idClasse`, `nom_classe`, `code_filiere`, `idpromotion`) VALUES
(1, '12', 11, 2),
(2, '12', 10, 2),
(3, '10', 13, 2);

-- --------------------------------------------------------

--
-- Structure de la table `eleves`
--

CREATE TABLE `eleves` (
  `matricule` varchar(20) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `date_naiss` date DEFAULT NULL,
  `lieu_naiss` varchar(50) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL,
  `adresse` text,
  `tel` varchar(15) DEFAULT NULL,
  `pere` varchar(50) DEFAULT NULL,
  `mere` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `eleves`
--

INSERT INTO `eleves` (`matricule`, `nom`, `prenom`, `date_naiss`, `lieu_naiss`, `sexe`, `adresse`, `tel`, `pere`, `mere`) VALUES
('RC14CG20J028', 'N\'DIAYE', 'Diahara', '2006-05-08', 'Bamako', 'F', NULL, '66104044', 'Abdoulaye', 'Fatoumata DIARRA'),
('RC14CG20J133', 'FOFANA', 'Mafarima', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC14CG20J399', 'COULIBALY', 'Hawa Tamba', '2006-07-02', 'Bamako', 'F', NULL, '68179559', 'Tamba', 'Rokia DOUMBIA'),
('RC14CG20R322', 'SIDIBE', 'Souleymane', '2006-12-10', 'Bamako', 'M', NULL, '79284826', 'Moussa', 'Sadio SISSOKO'),
('RC14CG20R391', 'COULIBALY', 'Daniel Abdramane', '2006-08-16', 'Bamako', 'M', NULL, '71898488', 'Seydou', 'Niame DIALLO'),
('RC14CG21A834', 'SANGARE', 'fanta', '2006-09-16', 'Bamako', 'F', NULL, '66791183', 'Mamadou', 'Flatenin TRAORE'),
('RC14CG21J113', 'DIOP', 'Soukeina', '2007-11-07', 'Bamako', 'F', NULL, '76130239', 'Maki', 'Fanta SANOGO'),
('RC14CG21J422', 'SISSOKO', 'Bamoye', '2005-07-21', 'Bamako', 'M', NULL, NULL, 'Boubacar', 'KAMISSOKO'),
('RC14CG21J482', 'SISSOKO', 'Goundey', '2006-01-01', 'Bamako', 'M', NULL, NULL, 'Sega', 'Dialy KOUMA'),
('RC15CG20J128', 'DRAME', 'Hawa', '2005-02-08', 'Bamako', 'F', NULL, '68846494', 'Abdoulaye', 'Mariam TANAPO'),
('RC15CG20J180', 'KONE', 'Aminata', NULL, 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC15CG20J344', 'COULIBALY', 'Bintou A', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC15CG20J369', 'BAGAYOKO', '', NULL, '', NULL, '', '', '', ''),
('RC15CG20J386', 'COULIBALY', 'fatoumata Fomon', '2005-12-22', 'Bamako', 'F', NULL, '79101259', 'Famon', 'Oumou FOFNA'),
('RC15CG20J394', 'COULIBALY', 'Habsatou', '2004-12-25', 'Bamako', 'F', NULL, '70916176', 'Zoumana', 'Fatoumata BOURE'),
('RC15CG20J402', 'COULIBALY', 'Idrissa', NULL, 'Bamako', 'M', NULL, NULL, NULL, NULL),
('RC15CG20J728', 'TRAORE', 'Asstan Cheickna', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC15CG20J759', 'BALLO', 'Kadidiatou', '2005-11-13', 'Bamako', 'F', NULL, NULL, 'Abdoul', 'Fatoumata BALLO'),
('RC15CG20J800', 'TRAORE', 'Fatoumata', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC15CG20N181', 'COULIBALY', 'Soumaila', '2005-09-15', 'Bamako', 'M', NULL, NULL, 'Bakary', 'Mamou COULIBALY'),
('RC15CG20U995', 'SIDIBE', 'Rokiatou', '2005-10-10', 'Bamako', 'F', NULL, '76231787', 'Solomana', 'Maria SIDIBE'),
('RC15CG21J112', 'BERE', 'Bougougna', '2006-12-12', 'Bamako', 'F', NULL, NULL, 'Bougougna', 'Fanta SANOU'),
('RC15CG21J148', 'KANOUTE', 'Mamadou Koumbouna', '2006-04-08', 'Bamako', 'M', NULL, '76406439', 'Moussa', 'Maimouna BAMBA'),
('RC15CG21J166', 'TOUNKARA', 'Diaminatou', '2006-04-26', 'Bamako', 'F', NULL, '75471108', 'Gaoussou', 'Oumou DIARRA'),
('RC15CG21J286', 'TRAORE', 'Idrissa Yacouba', '2006-09-16', 'Bamako', 'M', NULL, '75073730', 'Yacouba', 'Djeneba BAMBA'),
('RC15CG21J287', 'TRAORE', 'Kadiatou', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC15CG21J288', 'TRAORE', 'Mariam', '2005-12-25', 'Bamako', 'F', NULL, '76543210', 'Mamadou', 'Aminata TRAORE'),
('RC15CG21J289', 'TRAORE', 'Oumar', '2006-03-15', 'Bamako', 'M', NULL, '79876543', 'Seydou', 'Fatoumata DIALLO'),
('RC15CG21J294', 'TRAORE', 'Moriba', '2006-05-08', 'Bamako', 'M', NULL, NULL, 'Lassine', 'Aminata TRAORE'),
('RC15CG21N720', 'MOUNKORO', 'Massa Yaya', '2006-03-22', 'Bamako', 'M', NULL, '75422077', 'Passani', 'Vian KAMATE'),
('RC15CG22J124', 'KANOUTE', 'Fatoumata', '2007-03-24', 'Bamako', 'F', NULL, '79149001', 'Hamma', 'Mariam DIALLO'),
('RC15CG22J128', 'DOUMBIA', 'Ramata Karim', '2006-03-09', 'Bamako', 'F', NULL, '75051951', 'Karim', 'Naira KEITA'),
('RC15CG22J154', 'KANTE', 'Mariam', '2008-02-07', 'Bamako', 'F', NULL, '74051002', 'Daouda', 'Maimouna KANTE'),
('RC15CG22J329', 'FANE', 'Cheick Oumar', '2007-10-13', 'Bamako', 'M', NULL, '94205847', 'Mahamoudou', 'Banadia FANE'),
('RC15CG22J997', 'FANE', 'Fatoumata Yaya', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG19J493', 'DEMBELE', 'Aminata', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG20D971', 'KOMOTA', 'Fatoumata', '2004-09-19', 'Bamako', 'F', NULL, '75084889', 'Moussa', 'Oumou DIA'),
('RC16CG20J175', 'KONATE', 'Ibrahim S', NULL, 'Bamako', 'M', NULL, NULL, NULL, NULL),
('RC16CG20J182', 'KONE', 'Hamidou', '2004-07-12', 'Bamako', 'M', NULL, '70870336', 'Mamadou', 'Djeneba KONE'),
('RC16CG20J248', 'SOUKA', 'Ballakissa', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG20J284', 'CISSE', 'Sita', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG20J410', 'COULIBALY', 'Mamadou', NULL, 'Bamako', 'M', NULL, NULL, NULL, NULL),
('RC16CG20J617', 'TOGOLA', 'Kalla', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG20J702', 'BALLO', 'djenebou', '2004-11-01', 'Bamako', 'F', NULL, '78319715', 'Lassina', 'Fanta DOUMBIA'),
('RC16CG20J730', 'DIAKITE', 'Djoumin', '2004-06-17', 'Bamako', 'F', NULL, '93305956', 'Zoumna', 'Maminata'),
('RC16CG20J823', 'TRAORE', 'Bakary', '2006-01-01', 'Bamako', 'M', NULL, NULL, NULL, NULL),
('RC16CG20R221', 'KEITA', 'Mousoukoura', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG21F176', 'HAMMAR', 'Fadimata', '2005-11-30', 'Bamako', 'F', NULL, '70103568', 'Moussa Aldjo', 'Mariam MASSAYA'),
('RC16CG21J120', 'DOUMBIA', 'Awa', NULL, 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG21J165', 'KOFFI', 'Mechac Dorcival', '2005-06-25', 'Bamako', 'M', NULL, '71008572', 'Marc Aangba', 'Ahou Rosine'),
('RC16CG21J193', 'SAMKE', 'Mariam Souleymane', '2007-12-28', 'Bamako', 'F', NULL, '79024659', 'Souleymane', 'Ramata KONE'),
('RC16CG21J368', 'SIDIBE', 'Nantenin', '2006-01-01', 'Bamako', 'F', NULL, NULL, NULL, NULL),
('RC16CG21Q278', 'TRAORE', 'Alakana', '2004-03-03', 'Bamako', 'M', NULL, '79637963', 'Yeli', 'Aminata TANDIA'),
('RC18CF8820J365', 'Modibo', 'Cisse', '2010-02-15', 'Bamako', 'M', 'Golf', '7894561', 'Sambourou', 'cisse'),
('test001', 'test', 'qwert', '2017-12-31', 'sevare', 'M', 'golf', '96939396', 'papa', 'maman');

-- --------------------------------------------------------

--
-- Structure de la table `eleves_sans_matricule`
--

CREATE TABLE `eleves_sans_matricule` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `date_naiss` date DEFAULT NULL,
  `lieu_naiss` varchar(50) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL,
  `adresse` text,
  `tel` varchar(15) DEFAULT NULL,
  `pere` varchar(50) DEFAULT NULL,
  `mere` varchar(50) DEFAULT NULL,
  `date_import` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `filiere`
--

CREATE TABLE `filiere` (
  `code_filiere` int(11) NOT NULL,
  `nom_filiere` varchar(50) NOT NULL,
  `idserie` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `filiere`
--

INSERT INTO `filiere` (`code_filiere`, `nom_filiere`, `idserie`) VALUES
(7, 'Terminale Sciences expérimentales (TSEXP)', 4),
(8, 'Terminale Sciences exactes (TSE)', 4),
(9, 'Terminale Économie et gestion (TSECO)', 6),
(10, 'Terminale Lettres et langues (TLL)', 5),
(11, 'Terminale Arts et lettres (TAL)', 5),
(12, 'Terminale Sciences sociales (TSS)', 6),
(13, '10ème année : Tronc commun', 7),
(14, '11ème année : Série scientifique', 4),
(15, '11ème année : Série littéraire', 5),
(16, '11ème année : Série économique', 6);

-- --------------------------------------------------------

--
-- Structure de la table `inscrire`
--

CREATE TABLE `inscrire` (
  `idpromotion` int(11) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `code_filiere` int(11) NOT NULL,
  `idClasse` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `inscrire`
--

INSERT INTO `inscrire` (`idpromotion`, `matricule`, `code_filiere`, `idClasse`) VALUES
(2, 'RC14CG20J028', 11, 1),
(2, 'RC14CG20J133', 11, 1),
(2, 'RC14CG20J399', 11, 1),
(2, 'RC14CG20R322', 11, 1),
(2, 'RC14CG20R391', 11, 1),
(2, 'RC14CG21A834', 11, 1),
(2, 'RC14CG21J113', 11, 1),
(2, 'RC14CG21J422', 11, 1),
(2, 'RC14CG21J482', 11, 1),
(2, 'RC15CG20J128', 11, 1),
(2, 'RC15CG20J180', 11, 1),
(2, 'RC15CG20J344', 11, 1),
(2, 'RC15CG20J369', 11, 1),
(2, 'RC15CG20J386', 11, 1),
(2, 'RC15CG20J394', 11, 1),
(2, 'RC15CG20J402', 11, 1),
(2, 'RC15CG20J728', 11, 1),
(2, 'RC15CG20J759', 11, 1),
(2, 'RC15CG20J800', 11, 1),
(2, 'RC15CG20N181', 11, 1),
(2, 'RC15CG20U995', 11, 1),
(2, 'RC15CG21J112', 11, 1),
(2, 'RC15CG21J148', 11, 1),
(2, 'RC15CG21J166', 11, 1),
(2, 'RC15CG21J286', 11, 1),
(2, 'RC15CG21J287', 11, 1),
(2, 'RC15CG21J288', 11, 1),
(2, 'RC15CG21J289', 11, 1),
(2, 'RC15CG21J294', 11, 1),
(2, 'RC15CG21N720', 11, 1),
(2, 'RC15CG22J124', 11, 1),
(2, 'RC15CG22J128', 11, 1),
(2, 'RC15CG22J154', 11, 1),
(2, 'RC15CG22J329', 11, 1),
(2, 'RC15CG22J997', 11, 1),
(2, 'RC16CG19J493', 11, 1),
(2, 'RC16CG20D971', 11, 1),
(2, 'RC16CG20J175', 11, 1),
(2, 'RC16CG20J182', 11, 1),
(2, 'RC16CG20J248', 11, 1),
(2, 'RC16CG20J284', 11, 1),
(2, 'RC16CG20J410', 11, 1),
(2, 'RC16CG20J617', 11, 1),
(2, 'RC16CG20J702', 11, 1),
(2, 'RC16CG20J730', 11, 1),
(2, 'RC16CG20J823', 11, 1),
(2, 'RC16CG20R221', 11, 1),
(2, 'RC16CG21F176', 11, 1),
(2, 'RC16CG21J120', 11, 1),
(2, 'RC16CG21J165', 11, 1),
(2, 'RC16CG21J193', 11, 1),
(2, 'RC16CG21J368', 11, 1),
(2, 'RC16CG21Q278', 11, 1),
(2, 'RC18CF8820J365', 13, 3);

-- --------------------------------------------------------

--
-- Structure de la table `login`
--

CREATE TABLE `login` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `login`
--

INSERT INTO `login` (`id_user`, `username`, `password`, `nom`, `prenom`, `role`) VALUES
(1, 'admin', '$2y$10$5CodNTEX8VToJ6ru5SPvk.CoVBBzQVFW3CsXKtYlkATiPNr0VAVES', 'admin', 'admin', 'admin'),
(2, 'iya', '$2y$10$klE9Rk4ZBT2nHiolksKjyueFESXG7lrR6mNN7fwdBD5Q9lxRIWcW2', 'Madame Maiga', 'Iya', 'user');

-- --------------------------------------------------------

--
-- Structure de la table `matieres`
--

CREATE TABLE `matieres` (
  `id_matiere` int(11) NOT NULL,
  `nom_matiere` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `matieres`
--

INSERT INTO `matieres` (`id_matiere`, `nom_matiere`) VALUES
(1, 'ARTS'),
(2, 'CONDUITE'),
(3, 'ECONOMIE'),
(4, 'EDUCATION PHYSIQUE ET SPORTIVE'),
(5, 'FRANÇAIS'),
(6, 'HISTOIRE-GEOGRAPHIE'),
(7, 'INFORMATIQUE'),
(8, 'LANGUE VIVANTE 1'),
(9, 'LANGUE VIVANTE 2'),
(10, 'LANGUES NATIONALES'),
(11, 'MATHEMATIQUES'),
(12, 'PHYSIQUE-CHIMIE'),
(13, 'SVT'),
(14, 'ARTS'),
(15, 'CONDUITE'),
(16, 'ECONOMIE'),
(17, 'EDUCATION PHYSIQUE ET SPORTIVE'),
(18, 'EDUCATION CIVIQUE ET MORALE'),
(19, 'FRANÇAIS'),
(20, 'HISTOIRE-GEOGRAPHIE'),
(21, 'INFORMATIQUE'),
(22, 'LANGUE VIVANTE 1'),
(23, 'LANGUE VIVANTE 2'),
(24, 'LANGUES NATIONALES'),
(25, 'MATHEMATIQUES'),
(26, 'PHILOSOPHIE');

-- --------------------------------------------------------

--
-- Structure de la table `matiere_classe`
--

CREATE TABLE `matiere_classe` (
  `id` int(11) NOT NULL,
  `idClasse` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `coefficient` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `matiere_classe`
--

INSERT INTO `matiere_classe` (`id`, `idClasse`, `id_matiere`, `coefficient`) VALUES
(1, 1, 1, 4),
(2, 1, 2, 1);

-- --------------------------------------------------------

--
-- Structure de la table `matiere_serie`
--

CREATE TABLE `matiere_serie` (
  `id` int(11) NOT NULL,
  `idserie` int(11) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `coefficient` int(11) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `id_note` int(11) NOT NULL,
  `matricule` varchar(20) NOT NULL,
  `id_matiere` int(11) NOT NULL,
  `idpromotion` int(11) NOT NULL,
  `idperiode` int(11) NOT NULL,
  `note_classe` decimal(5,2) DEFAULT NULL,
  `note_examen` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO `notes` (`id_note`, `matricule`, `id_matiere`, `idpromotion`, `idperiode`, `note_classe`, `note_examen`) VALUES
(3, 'RC15CG20J369', 1, 1, 2, '10.00', '6.00'),
(4, 'RC16CG20J702', 1, 1, 2, '8.50', '10.00'),
(5, 'RC15CG21J112', 1, 1, 2, '15.00', '16.00'),
(6, 'RC14CG20R391', 1, 1, 2, '11.00', '13.00'),
(7, 'RC15CG20J386', 1, 1, 2, '15.50', '18.00'),
(8, 'RC14CG20J399', 1, 1, 2, '16.00', '10.00'),
(9, 'RC15CG20J402', 1, 1, 2, '18.00', '18.00'),
(10, 'RC16CG20J410', 1, 1, 2, '14.50', '16.00'),
(11, 'RC16CG20J730', 1, 1, 2, NULL, NULL),
(12, 'RC14CG21J113', 1, 1, 2, '20.00', '20.00'),
(13, 'RC16CG21J120', 1, 1, 2, NULL, NULL),
(14, 'RC15CG22J128', 1, 1, 2, '17.00', '17.00'),
(15, 'RC16CG21F176', 1, 1, 2, NULL, NULL),
(16, 'RC16CG20D971', 1, 1, 2, '15.00', '17.00'),
(17, 'RC16CG20J175', 1, 1, 2, NULL, NULL),
(18, 'RC15CG20J180', 1, 1, 2, NULL, NULL),
(19, 'RC15CG21N720', 1, 1, 2, '13.00', '8.00'),
(20, 'RC14CG21A834', 1, 1, 2, '15.50', '16.00'),
(21, 'RC15CG20U995', 1, 1, 2, '16.50', '15.00'),
(22, 'RC15CG21J294', 1, 1, 2, '12.50', '8.00'),
(23, 'RC15CG20J369', 18, 2, 2, '10.00', '9.00'),
(24, 'RC16CG20J702', 18, 2, 2, '10.00', '10.00'),
(25, 'RC15CG21J112', 18, 2, 2, NULL, NULL),
(26, 'RC14CG20R391', 18, 2, 2, NULL, NULL),
(27, 'RC15CG20J386', 18, 2, 2, NULL, NULL),
(28, 'RC14CG20J399', 18, 2, 2, NULL, NULL),
(29, 'RC15CG20J402', 18, 2, 2, NULL, NULL),
(30, 'RC16CG20J410', 18, 2, 2, NULL, NULL),
(31, 'RC16CG20J730', 18, 2, 2, NULL, NULL),
(32, 'RC14CG21J113', 18, 2, 2, NULL, NULL),
(33, 'RC16CG21J120', 18, 2, 2, NULL, NULL),
(34, 'RC15CG22J128', 18, 2, 2, NULL, NULL),
(35, 'RC16CG21F176', 18, 2, 2, NULL, NULL),
(36, 'RC16CG20D971', 18, 2, 2, NULL, NULL),
(37, 'RC16CG20J175', 18, 2, 2, NULL, NULL),
(38, 'RC15CG20J180', 18, 2, 2, NULL, NULL),
(39, 'RC15CG21N720', 18, 2, 2, NULL, NULL),
(40, 'RC14CG21A834', 18, 2, 2, NULL, NULL),
(41, 'RC15CG20U995', 18, 2, 2, NULL, NULL),
(42, 'RC15CG21J294', 18, 2, 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `promotion`
--

CREATE TABLE `promotion` (
  `idpromotion` int(11) NOT NULL,
  `annee_scolaire` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `promotion`
--

INSERT INTO `promotion` (`idpromotion`, `annee_scolaire`) VALUES
(1, '2020-2021'),
(2, '2024-2025');

-- --------------------------------------------------------

--
-- Structure de la table `series`
--

CREATE TABLE `series` (
  `idserie` int(11) NOT NULL,
  `series` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `series`
--

INSERT INTO `series` (`idserie`, `series`) VALUES
(4, 'Scientifique'),
(5, 'Littéraire'),
(6, 'Économique'),
(7, 'Tronc commun');

-- --------------------------------------------------------

--
-- Structure de la table `trimestres`
--

CREATE TABLE `trimestres` (
  `idperiode` int(11) NOT NULL,
  `trimestre` varchar(50) NOT NULL,
  `idpromotion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Déchargement des données de la table `trimestres`
--

INSERT INTO `trimestres` (`idperiode`, `trimestre`, `idpromotion`) VALUES
(1, '1ier Trimestre', 1),
(2, '1 er Trimestre', 2),
(3, '2ieme Trimestre', 2);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `associer`
--
ALTER TABLE `associer`
  ADD PRIMARY KEY (`code_filiere`,`id_matiere`),
  ADD KEY `fk_associer_matiere` (`id_matiere`);

--
-- Index pour la table `bulletin`
--
ALTER TABLE `bulletin`
  ADD PRIMARY KEY (`id_bulletin`),
  ADD KEY `matricule` (`matricule`),
  ADD KEY `idpromotion` (`idpromotion`),
  ADD KEY `idperiode` (`idperiode`);

--
-- Index pour la table `classe`
--
ALTER TABLE `classe`
  ADD PRIMARY KEY (`idClasse`),
  ADD KEY `fk_classe_filiere` (`code_filiere`),
  ADD KEY `fk_classe_promotion` (`idpromotion`);

--
-- Index pour la table `eleves`
--
ALTER TABLE `eleves`
  ADD PRIMARY KEY (`matricule`);

--
-- Index pour la table `eleves_sans_matricule`
--
ALTER TABLE `eleves_sans_matricule`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD PRIMARY KEY (`code_filiere`),
  ADD KEY `fk_filiere_serie` (`idserie`);

--
-- Index pour la table `inscrire`
--
ALTER TABLE `inscrire`
  ADD PRIMARY KEY (`idpromotion`,`matricule`),
  ADD KEY `fk_inscrire_eleve` (`matricule`),
  ADD KEY `fk_inscrire_filiere` (`code_filiere`),
  ADD KEY `fk_inscrire_classe` (`idClasse`);

--
-- Index pour la table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `matieres`
--
ALTER TABLE `matieres`
  ADD PRIMARY KEY (`id_matiere`);

--
-- Index pour la table `matiere_classe`
--
ALTER TABLE `matiere_classe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_matiere_classe` (`idClasse`,`id_matiere`),
  ADD KEY `id_matiere` (`id_matiere`);

--
-- Index pour la table `matiere_serie`
--
ALTER TABLE `matiere_serie`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_matiere_serie` (`idserie`,`id_matiere`),
  ADD KEY `id_matiere` (`id_matiere`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id_note`),
  ADD KEY `matricule` (`matricule`),
  ADD KEY `id_matiere` (`id_matiere`),
  ADD KEY `idpromotion` (`idpromotion`),
  ADD KEY `idperiode` (`idperiode`);

--
-- Index pour la table `promotion`
--
ALTER TABLE `promotion`
  ADD PRIMARY KEY (`idpromotion`);

--
-- Index pour la table `series`
--
ALTER TABLE `series`
  ADD PRIMARY KEY (`idserie`);

--
-- Index pour la table `trimestres`
--
ALTER TABLE `trimestres`
  ADD PRIMARY KEY (`idperiode`),
  ADD KEY `idpromotion` (`idpromotion`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `bulletin`
--
ALTER TABLE `bulletin`
  MODIFY `id_bulletin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `classe`
--
ALTER TABLE `classe`
  MODIFY `idClasse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `eleves_sans_matricule`
--
ALTER TABLE `eleves_sans_matricule`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `filiere`
--
ALTER TABLE `filiere`
  MODIFY `code_filiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `login`
--
ALTER TABLE `login`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `matieres`
--
ALTER TABLE `matieres`
  MODIFY `id_matiere` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `matiere_classe`
--
ALTER TABLE `matiere_classe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `matiere_serie`
--
ALTER TABLE `matiere_serie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `id_note` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT pour la table `promotion`
--
ALTER TABLE `promotion`
  MODIFY `idpromotion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `series`
--
ALTER TABLE `series`
  MODIFY `idserie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `trimestres`
--
ALTER TABLE `trimestres`
  MODIFY `idperiode` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `associer`
--
ALTER TABLE `associer`
  ADD CONSTRAINT `fk_associer_filiere` FOREIGN KEY (`code_filiere`) REFERENCES `filiere` (`code_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_associer_matiere` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id_matiere`) ON DELETE CASCADE;

--
-- Contraintes pour la table `bulletin`
--
ALTER TABLE `bulletin`
  ADD CONSTRAINT `bulletin_ibfk_1` FOREIGN KEY (`matricule`) REFERENCES `eleves` (`matricule`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_ibfk_2` FOREIGN KEY (`idpromotion`) REFERENCES `promotion` (`idpromotion`) ON DELETE CASCADE,
  ADD CONSTRAINT `bulletin_ibfk_3` FOREIGN KEY (`idperiode`) REFERENCES `trimestres` (`idperiode`) ON DELETE CASCADE;

--
-- Contraintes pour la table `classe`
--
ALTER TABLE `classe`
  ADD CONSTRAINT `fk_classe_filiere` FOREIGN KEY (`code_filiere`) REFERENCES `filiere` (`code_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_classe_promotion` FOREIGN KEY (`idpromotion`) REFERENCES `promotion` (`idpromotion`) ON DELETE CASCADE;

--
-- Contraintes pour la table `filiere`
--
ALTER TABLE `filiere`
  ADD CONSTRAINT `fk_filiere_serie` FOREIGN KEY (`idserie`) REFERENCES `series` (`idserie`) ON DELETE CASCADE;

--
-- Contraintes pour la table `inscrire`
--
ALTER TABLE `inscrire`
  ADD CONSTRAINT `fk_inscrire_classe` FOREIGN KEY (`idClasse`) REFERENCES `classe` (`idClasse`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inscrire_eleve` FOREIGN KEY (`matricule`) REFERENCES `eleves` (`matricule`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inscrire_filiere` FOREIGN KEY (`code_filiere`) REFERENCES `filiere` (`code_filiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inscrire_promotion` FOREIGN KEY (`idpromotion`) REFERENCES `promotion` (`idpromotion`) ON DELETE CASCADE;

--
-- Contraintes pour la table `matiere_classe`
--
ALTER TABLE `matiere_classe`
  ADD CONSTRAINT `matiere_classe_ibfk_1` FOREIGN KEY (`idClasse`) REFERENCES `classe` (`idClasse`) ON DELETE CASCADE,
  ADD CONSTRAINT `matiere_classe_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id_matiere`) ON DELETE CASCADE;

--
-- Contraintes pour la table `matiere_serie`
--
ALTER TABLE `matiere_serie`
  ADD CONSTRAINT `matiere_serie_ibfk_1` FOREIGN KEY (`idserie`) REFERENCES `series` (`idserie`) ON DELETE CASCADE,
  ADD CONSTRAINT `matiere_serie_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id_matiere`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`matricule`) REFERENCES `eleves` (`matricule`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`id_matiere`) REFERENCES `matieres` (`id_matiere`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_3` FOREIGN KEY (`idpromotion`) REFERENCES `promotion` (`idpromotion`) ON DELETE CASCADE,
  ADD CONSTRAINT `notes_ibfk_4` FOREIGN KEY (`idperiode`) REFERENCES `trimestres` (`idperiode`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trimestres`
--
ALTER TABLE `trimestres`
  ADD CONSTRAINT `trimestres_ibfk_1` FOREIGN KEY (`idpromotion`) REFERENCES `promotion` (`idpromotion`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
