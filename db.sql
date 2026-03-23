-- Create database if not exists
CREATE DATABASE IF NOT EXISTS impact_point;
USE impact_point;

-- Players table
CREATE TABLE IF NOT EXISTS players (
    steamid64 VARCHAR(25) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tournaments table
CREATE TABLE IF NOT EXISTS tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    format VARCHAR(20) DEFAULT '1v1',
    max_players INT DEFAULT 32,
    status ENUM('open', 'closed', 'finished') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Registrations and Brackets table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    player_id VARCHAR(25) NOT NULL,
    slot INT NOT NULL, -- The position in the bracket (e.g., 0 to 31)
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (tournament_id, player_id),
    UNIQUE KEY (tournament_id, slot),
    FOREIGN KEY (player_id) REFERENCES players(steamid64)
);

-- Seed an initial tournament
INSERT INTO tournaments (name, format, max_players) VALUES ('WEEKLY BRAWL - SEMANA 1', '1v1', 32);
