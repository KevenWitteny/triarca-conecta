CREATE DATABASE IF NOT EXISTS triarca_conecta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE triarca_conecta;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(150) NULL,
  email VARCHAR(180) NOT NULL UNIQUE,
  senha VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS documentos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  categoria_principal VARCHAR(100) NOT NULL,
  categoria VARCHAR(120) NOT NULL,
  nome_arquivo VARCHAR(255) NOT NULL,
  caminho_arquivo TEXT NOT NULL,
  data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_user (user_id),
  CONSTRAINT fk_documentos_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Crie a senha com PHP:
-- php -r "echo password_hash('123456', PASSWORD_DEFAULT);"
-- Depois substitua o hash abaixo.
INSERT INTO users (nome, email, senha, is_admin)
VALUES ('Administrador', 'admin@local.com', '$2y$10$EXEMPLO_SUBSTITUA_PELO_HASH_REAL', 1);
