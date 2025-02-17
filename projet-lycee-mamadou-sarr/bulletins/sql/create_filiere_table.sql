-- Vérifier si la table existe et la créer si nécessaire
CREATE TABLE IF NOT EXISTS filiere (
    code_filiere VARCHAR(10) PRIMARY KEY,
    nom_filiere VARCHAR(100) NOT NULL,
    idserie INT NOT NULL,
    FOREIGN KEY (idserie) REFERENCES series(idserie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
