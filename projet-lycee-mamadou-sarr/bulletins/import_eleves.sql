-- Désactiver les contraintes de clés étrangères temporairement
SET FOREIGN_KEY_CHECKS = 0;

-- Charger les données du CSV dans la table eleves
LOAD DATA INFILE 'chemin/vers/TLL1.csv'
INTO TABLE eleves
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 LINES
(matricule, nom, prenom, @date_naiss, lieu_naiss, sexe, @adresse, @tel, @pere, @mere)
SET
date_naiss = NULLIF(@date_naiss, ''),
adresse = NULLIF(@adresse, ''),
tel = NULLIF(@tel, ''),
pere = NULLIF(@pere, ''),
mere = NULLIF(@mere, '');

-- Si vous voulez aussi inscrire les élèves dans une classe spécifique
INSERT INTO inscrire (idpromotion, matricule, code_filiere, idClasse)
SELECT 
    (SELECT idpromotion FROM promotion WHERE annee_scolaire = '2024-2025'), -- promotion active
    e.matricule,
    (SELECT code_filiere FROM classe WHERE idClasse = [ID_CLASSE]), -- remplacer [ID_CLASSE]
    [ID_CLASSE] -- remplacer [ID_CLASSE]
FROM eleves e
WHERE e.matricule IN (
    SELECT matricule FROM eleves WHERE matricule LIKE 'RC%'
);

-- Réactiver les contraintes de clés étrangères
SET FOREIGN_KEY_CHECKS = 1; 