-- 1. Suppression avec CASCADE pour forcer la destruction des liens
DROP TABLE IF EXISTS candidats CASCADE;
DROP TABLE IF EXISTS groupes CASCADE;
DROP TABLE IF EXISTS utilisateurs CASCADE;

-- 2. Création de la table PARENTE (doit être créée en premier)
CREATE TABLE groupes (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255),
    couleur VARCHAR(50),
    criteres TEXT,
    note_groupe FLOAT DEFAULT NULL
);

-- 3. Création de la table ENFANT (qui référence le parent)
CREATE TABLE candidats (
    id SERIAL PRIMARY KEY,
    numero_dossier VARCHAR(255),
    genre VARCHAR(50),
    departement VARCHAR(50),
    ville_lycee VARCHAR(255),
    specialite VARCHAR(255) NOT NULL,
    type_bac VARCHAR(255),
    profil_candidat VARCHAR(255) DEFAULT 'Général',
    lat DOUBLE PRECISION,
    lng DOUBLE PRECISION,
    note_lycee FLOAT DEFAULT NULL,
    note_avenir FLOAT DEFAULT NULL,
    note_dossier FLOAT DEFAULT NULL,
    date_inscription VARCHAR(255) DEFAULT NULL,
    groupe_id INT DEFAULT NULL REFERENCES groupes(id) ON DELETE SET NULL
);

-- 4. Création de la table utilisateurs
CREATE TABLE utilisateurs (
    id SERIAL PRIMARY KEY,
    identifiant VARCHAR(50) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('administrateur', 'utilisateur'))
);

-- 5. Création de l'Index pour accélérer la géolocalisation
CREATE INDEX IF NOT EXISTS idx_ville_lycee ON candidats(ville_lycee);