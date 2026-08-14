-- =====================================================================
-- ClosingPoint — Projet 36 : Plateforme de gestion de projets de
-- fusion-acquisition (M&A) avec data room virtuelle
-- Master CCA - ESP Dakar - M. Ousmane LY
-- Script de création de la base de données + données de démonstration
-- Moteur : MySQL 5.7+/MariaDB (XAMPP) - Charset utf8mb4
-- =====================================================================

DROP DATABASE IF EXISTS closingpoint;
CREATE DATABASE closingpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE closingpoint;

-- ---------------------------------------------------------------------
-- 1. UTILISATEURS (3 rôles obligatoires)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','conseiller','client') NOT NULL DEFAULT 'client',
    phone VARCHAR(30) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. PROJETS M&A
-- ---------------------------------------------------------------------
CREATE TABLE ma_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_projet VARCHAR(20) NOT NULL UNIQUE,
    nom_projet VARCHAR(180) NOT NULL,
    societe_cible VARCHAR(180) NOT NULL,
    societe_acquereur VARCHAR(180) NOT NULL,
    secteur VARCHAR(100) DEFAULT NULL,
    statut ENUM('prospection','nda_signe','due_diligence','negociation','closing','abandonne') NOT NULL DEFAULT 'prospection',
    valeur_estimee DECIMAL(18,2) DEFAULT NULL,
    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    date_debut DATE DEFAULT NULL,
    date_cible_closing DATE DEFAULT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_proj_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. ÉQUIPE PROJET (droits d'accès différenciés)
-- ---------------------------------------------------------------------
CREATE TABLE project_team (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role_projet ENUM('chef_projet','analyste','conseiller_juridique','conseiller_financier','observateur_cible') NOT NULL,
    date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_project_user (project_id, user_id),
    CONSTRAINT fk_team_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_team_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. DATA ROOM - ARBORESCENCE DE DOSSIERS
-- ---------------------------------------------------------------------
CREATE TABLE dataroom_folders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    nom VARCHAR(150) NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_folder_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_folder_parent FOREIGN KEY (parent_id) REFERENCES dataroom_folders(id) ON DELETE CASCADE,
    CONSTRAINT fk_folder_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. DATA ROOM - DOCUMENTS
-- ---------------------------------------------------------------------
CREATE TABLE dataroom_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    folder_id INT DEFAULT NULL,
    nom_original VARCHAR(255) NOT NULL,
    nom_fichier_stocke VARCHAR(255) NOT NULL,
    chemin_relatif VARCHAR(255) NOT NULL,
    taille_octets BIGINT NOT NULL DEFAULT 0,
    type_mime VARCHAR(100) DEFAULT NULL,
    categorie ENUM('juridique','fiscal','financier','commercial','rh','it','autre') NOT NULL DEFAULT 'autre',
    confidentiel TINYINT(1) NOT NULL DEFAULT 1,
    uploaded_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_doc_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_doc_folder FOREIGN KEY (folder_id) REFERENCES dataroom_folders(id) ON DELETE SET NULL,
    CONSTRAINT fk_doc_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. JOURNAL DE CONSULTATION (traçabilité data room)
-- ---------------------------------------------------------------------
CREATE TABLE document_access_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('upload','consultation','telechargement') NOT NULL,
    adresse_ip VARCHAR(45) DEFAULT NULL,
    date_action DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_document FOREIGN KEY (document_id) REFERENCES dataroom_documents(id) ON DELETE CASCADE,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. NDA (ACCORDS DE CONFIDENTIALITÉ SIGNÉS EN LIGNE)
-- ---------------------------------------------------------------------
CREATE TABLE ndas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    nom_signataire VARCHAR(150) NOT NULL,
    email_signataire VARCHAR(150) NOT NULL,
    hash_signature VARCHAR(128) NOT NULL,
    adresse_ip VARCHAR(45) DEFAULT NULL,
    signed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_nda_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_nda_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. DUE DILIGENCE - CHECKLIST PAR DOMAINE
-- ---------------------------------------------------------------------
CREATE TABLE due_diligence_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    domaine ENUM('juridique','fiscal','financier','commercial','rh','it') NOT NULL,
    libelle VARCHAR(200) NOT NULL,
    description TEXT,
    statut ENUM('a_verifier','en_cours','valide','alerte') NOT NULL DEFAULT 'a_verifier',
    red_flag TINYINT(1) NOT NULL DEFAULT 0,
    impact_estime DECIMAL(18,2) DEFAULT NULL,
    responsable_id INT DEFAULT NULL,
    date_limite DATE DEFAULT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dd_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_dd_responsable FOREIGN KEY (responsable_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_dd_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. MODULE Q&A
-- ---------------------------------------------------------------------
CREATE TABLE qa_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    document_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    reponse TEXT DEFAULT NULL,
    posee_par INT NOT NULL,
    repondu_par INT DEFAULT NULL,
    statut ENUM('ouverte','repondue','fermee') NOT NULL DEFAULT 'ouverte',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    answered_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_qa_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_qa_document FOREIGN KEY (document_id) REFERENCES dataroom_documents(id) ON DELETE SET NULL,
    CONSTRAINT fk_qa_asker FOREIGN KEY (posee_par) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_qa_answerer FOREIGN KEY (repondu_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 10. ÉVALUATIONS (DCF, MULTIPLES, ANCC)
-- ---------------------------------------------------------------------
CREATE TABLE valuations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    methode ENUM('dcf','multiples','ancc') NOT NULL,
    hypotheses TEXT COMMENT 'JSON des hypothèses saisies',
    valeur_calculee DECIMAL(18,2) NOT NULL,
    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_val_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_val_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 11. OFFRES ET CONTRE-OFFRES
-- ---------------------------------------------------------------------
CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    type_offre ENUM('offre_initiale','contre_offre','offre_finale') NOT NULL,
    montant DECIMAL(18,2) NOT NULL,
    devise VARCHAR(10) NOT NULL DEFAULT 'FCFA',
    conditions TEXT,
    statut ENUM('proposee','en_negociation','acceptee','refusee') NOT NULL DEFAULT 'proposee',
    emise_par INT NOT NULL,
    date_offre DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_offer_project FOREIGN KEY (project_id) REFERENCES ma_projects(id) ON DELETE CASCADE,
    CONSTRAINT fk_offer_emitter FOREIGN KEY (emise_par) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 12. JOURNAL D'AUDIT (audit trail horodaté - actions sensibles)
-- ---------------------------------------------------------------------
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    table_concernee VARCHAR(60) DEFAULT NULL,
    ligne_id INT DEFAULT NULL,
    details VARCHAR(500) DEFAULT NULL,
    adresse_ip VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- DONNÉES DE DÉMONSTRATION
-- =====================================================================

-- Comptes de test (mots de passe hachés avec password_hash() / bcrypt réel)
-- admin@closingpoint.sn      / Admin@2026    -> rôle admin
-- conseiller@closingpoint.sn / Advisor@2026  -> rôle conseiller
-- client@closingpoint.sn     / Client@2026   -> rôle client (investisseur / cible)
INSERT INTO users (full_name, email, password_hash, role, phone) VALUES
('Fatou Ndiaye (Administrateur)', 'admin@closingpoint.sn', '$2y$10$Lzyb57AgmqkLDVe9YUGsjuPLAcTaBWSpdCgYquZ20FInw2Lk9fgWW', 'admin', '+221771112233'),
('Moussa Diop (Conseiller M&A)', 'conseiller@closingpoint.sn', '$2y$10$4bkqkT2irETxVBCuCcpxR./iSkVp8PtYJNCH8Oemofq8ZBb96ax7K', 'conseiller', '+221772223344'),
('Awa Diallo (Investisseur)', 'client@closingpoint.sn', '$2y$10$CY4ipTPJzuxgi9ahMgl2AedEQEOPuNhEd3TLLGk8gp1wok1XjuwH6', 'client', '+221773334455'),
('Ibrahima Fall (Analyste)', 'analyste@closingpoint.sn', '$2y$10$4bkqkT2irETxVBCuCcpxR./iSkVp8PtYJNCH8Oemofq8ZBb96ax7K', 'conseiller', '+221774445566');

INSERT INTO ma_projects (code_projet, nom_projet, societe_cible, societe_acquereur, secteur, statut, valeur_estimee, devise, date_debut, date_cible_closing, description, created_by) VALUES
('MA-2026-001', 'Projet Baobab', 'Distributeurs Sahel SA', 'Groupe Teranga Holding', 'Distribution & Logistique', 'due_diligence', 4500000000.00, 'FCFA', '2026-03-01', '2026-10-31', 'Acquisition majoritaire (70%) de Distributeurs Sahel SA par le Groupe Teranga Holding afin de renforcer son réseau logistique en Afrique de l\'Ouest.', 2),
('MA-2026-002', 'Projet Fleuve', 'AgroFinance Sénégal', 'Banque Atlantique Ouest', 'Services financiers', 'negociation', 2100000000.00, 'FCFA', '2026-05-15', '2026-12-15', 'Rapprochement stratégique entre AgroFinance Sénégal et Banque Atlantique Ouest visant une synergie sur le segment agricole.', 2),
('MA-2026-003', 'Projet Teranga', 'PharmaSénégal SA', 'MediGroup Afrique', 'Santé & Pharmacie', 'prospection', 1800000000.00, 'FCFA', '2026-06-01', '2027-02-28', 'Prise de contact initiale en vue du rachat de PharmaSénégal SA par MediGroup Afrique pour renforcer sa distribution pharmaceutique régionale.', 2),
('MA-2026-004', 'Projet Sahel Digital', 'TeleSenNet', 'Orange Digital Ventures', 'Télécommunications', 'nda_signe', 3200000000.00, 'FCFA', '2026-04-10', '2026-11-30', 'Rachat de l\'opérateur de réseau TeleSenNet par Orange Digital Ventures ; NDA signé entre les parties, data room en cours d\'ouverture.', 2),
('MA-2026-005', 'Projet Lumière', 'Solaire Ouest Africa', 'Groupe Teranga Holding', 'Énergie renouvelable', 'closing', 5600000000.00, 'FCFA', '2025-11-01', '2026-07-15', 'Acquisition finalisée de Solaire Ouest Africa par le Groupe Teranga Holding, opération menée à son terme avec succès.', 2),
('MA-2026-006', 'Projet Kalao', 'AgroExport CI', 'Groupe Sahel Industries', 'Agroalimentaire', 'closing', 2750000000.00, 'FCFA', '2025-09-01', '2026-05-20', 'Cession réussie d\'AgroExport CI au Groupe Sahel Industries, signature des actes de closing effectuée.', 2),
('MA-2026-007', 'Projet Mirage', 'BTP Rapide SA', 'Fonds Atlantique Capital', 'BTP & Construction', 'abandonne', 1200000000.00, 'FCFA', '2026-01-15', '2026-06-30', 'Opération abandonnée suite à la découverte de red flags majeurs (litiges non provisionnés) lors de la due diligence.', 2);

INSERT INTO project_team (project_id, user_id, role_projet) VALUES
(1, 2, 'chef_projet'),
(1, 4, 'analyste'),
(1, 3, 'observateur_cible'),
(2, 2, 'chef_projet'),
(2, 4, 'conseiller_financier'),
(3, 2, 'chef_projet'),
(3, 4, 'analyste'),
(4, 2, 'chef_projet'),
(4, 4, 'analyste'),
(5, 2, 'chef_projet'),
(5, 3, 'observateur_cible'),
(6, 2, 'chef_projet'),
(6, 3, 'observateur_cible'),
(7, 2, 'chef_projet');

INSERT INTO dataroom_folders (project_id, parent_id, nom, created_by) VALUES
(1, NULL, '01 - Juridique', 2),
(1, NULL, '02 - Financier', 2),
(1, NULL, '03 - Commercial', 2),
(1, 1, 'Statuts et PV d\'AG', 2),
(2, NULL, '01 - Juridique', 2),
(2, NULL, '02 - Financier', 2);

-- Les documents ci-dessous sont des métadonnées de démonstration ;
-- déposez de vrais fichiers via le module Upload pour qu'ils soient téléchargeables.
INSERT INTO dataroom_documents (project_id, folder_id, nom_original, nom_fichier_stocke, chemin_relatif, taille_octets, type_mime, categorie, confidentiel, uploaded_by) VALUES
(1, 4, 'Statuts_DistributeursSahel_2024.pdf', 'demo_statuts.pdf', 'projet_1/demo_statuts.pdf', 512340, 'application/pdf', 'juridique', 1, 2),
(1, 2, 'Etats_financiers_2023-2025.xlsx', 'demo_etats_financiers.xlsx', 'projet_1/demo_etats_financiers.xlsx', 348210, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'financier', 1, 2),
(2, 6, 'Bilan_AgroFinance_2025.pdf', 'demo_bilan.pdf', 'projet_2/demo_bilan.pdf', 289540, 'application/pdf', 'financier', 1, 2);

INSERT INTO document_access_log (document_id, user_id, action, adresse_ip) VALUES
(1, 2, 'upload', '127.0.0.1'),
(1, 3, 'consultation', '127.0.0.1'),
(2, 2, 'upload', '127.0.0.1'),
(3, 2, 'upload', '127.0.0.1');

INSERT INTO ndas (project_id, user_id, nom_signataire, email_signataire, hash_signature, adresse_ip) VALUES
(1, 3, 'Awa Diallo', 'client@closingpoint.sn', SHA2('Awa Diallo|client@closingpoint.sn|2026-03-05 09:00:00', 256), '127.0.0.1');

INSERT INTO due_diligence_items (project_id, domaine, libelle, description, statut, red_flag, impact_estime, responsable_id, date_limite, created_by) VALUES
(1, 'juridique', 'Vérification des statuts et RCCM', 'Contrôle de la conformité OHADA des statuts et de l\'immatriculation RCCM.', 'valide', 0, NULL, 4, '2026-04-15', 2),
(1, 'financier', 'Analyse de la dette bancaire', 'Vérification des covenants et échéanciers des emprunts en cours.', 'alerte', 1, 180000000.00, 4, '2026-05-01', 2),
(1, 'fiscal', 'Contrôle des redressements fiscaux passés', 'Recherche de litiges ou notifications de redressement des 3 derniers exercices.', 'en_cours', 0, NULL, 4, '2026-05-10', 2),
(1, 'commercial', 'Concentration du portefeuille clients', 'Évaluation de la dépendance vis-à-vis des 5 principaux clients.', 'a_verifier', 0, NULL, 2, '2026-05-20', 2),
(2, 'rh', 'Passifs sociaux et engagements de retraite', 'Chiffrage des indemnités de départ et engagements non provisionnés.', 'alerte', 1, 65000000.00, 4, '2026-06-01', 2);

INSERT INTO qa_questions (project_id, document_id, question, reponse, posee_par, repondu_par, statut, answered_at) VALUES
(1, 2, 'Pouvez-vous détailler l\'origine de la baisse de marge en 2025 ?', 'La baisse provient principalement de la hausse du coût du carburant sur le poste logistique (+12% sur l\'exercice).', 3, 2, 'repondue', '2026-04-02 14:30:00'),
(1, NULL, 'Existe-t-il des contrats fournisseurs avec clause de changement de contrôle ?', NULL, 3, NULL, 'ouverte', NULL);

INSERT INTO valuations (project_id, methode, hypotheses, valeur_calculee, devise, created_by) VALUES
(1, 'dcf', '{"fcf_an1":420000000,"croissance":0.05,"wacc":0.12,"g_terminal":0.03,"horizon":5}', 4380000000.00, 'FCFA', 2),
(1, 'multiples', '{"ebitda":650000000,"multiple_ve_ebitda":6.8}', 4420000000.00, 'FCFA', 2),
(1, 'ancc', '{"actifComptable":2900000000,"plusValues":700000000,"moinsValues":100000000,"passifExigible":70000000}', 3430000000.00, 'FCFA', 2),
(2, 'dcf', '{"fcfAn1":250000000,"croissance":6,"wacc":13,"gTerminal":3,"horizon":5}', 2741900000.00, 'FCFA', 2),
(2, 'multiples', '{"ebitda":380000000,"multiple":6.5,"dette":350000000}', 2120000000.00, 'FCFA', 2),
(2, 'ancc', '{"actifComptable":1800000000,"plusValues":450000000,"moinsValues":80000000,"passifExigible":120000000}', 2050000000.00, 'FCFA', 2),
(4, 'dcf', '{"fcfAn1":400000000,"croissance":7,"wacc":12,"gTerminal":3,"horizon":5}', 5037000000.00, 'FCFA', 2),
(4, 'multiples', '{"ebitda":620000000,"multiple":7.2,"dette":900000000}', 3564000000.00, 'FCFA', 2),
(4, 'ancc', '{"actifComptable":2600000000,"plusValues":700000000,"moinsValues":150000000,"passifExigible":200000000}', 2950000000.00, 'FCFA', 2);

INSERT INTO offers (project_id, type_offre, montant, devise, conditions, statut, emise_par, date_offre) VALUES
(1, 'offre_initiale', 4200000000.00, 'FCFA', 'Offre indicative sous réserve des conclusions de la due diligence.', 'en_negociation', 2, '2026-04-10'),
(1, 'contre_offre', 4550000000.00, 'FCFA', 'Contre-proposition de la cible intégrant la valorisation du fonds de commerce.', 'proposee', 2, '2026-04-20');

INSERT INTO audit_log (user_id, action, table_concernee, ligne_id, details, adresse_ip) VALUES
(2, 'creation_projet', 'ma_projects', 1, 'Création du projet MA-2026-001', '127.0.0.1'),
(2, 'creation_projet', 'ma_projects', 2, 'Création du projet MA-2026-002', '127.0.0.1'),
(3, 'signature_nda', 'ndas', 1, 'NDA signé par Awa Diallo pour le projet MA-2026-001', '127.0.0.1');
