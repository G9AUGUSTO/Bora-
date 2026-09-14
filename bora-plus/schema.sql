-- schema.sql
-- Estrutura de banco de dados para o app Bora+

CREATE DATABASE IF NOT EXISTS boramais
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE boramais;

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(120)        NOT NULL,
  email         VARCHAR(190)        NOT NULL UNIQUE,
  senha_hash    VARCHAR(255)        NOT NULL,
  criado_em     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS estabelecimentos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome          VARCHAR(150)        NOT NULL,
  categoria     VARCHAR(60)         NOT NULL,
  preco         TINYINT UNSIGNED    NOT NULL,       -- 1 = $, 2 = $$, 3 = $$$
  bairro        VARCHAR(100)        NOT NULL,
  endereco      VARCHAR(255)        NOT NULL,
  lat           DECIMAL(10,7)       NOT NULL,
  lng           DECIMAL(10,7)       NOT NULL,
  descricao     TEXT                NOT NULL,
  usuario_id    INT UNSIGNED        NULL,           -- quem cadastrou
  criado_em     TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_estab_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON DELETE SET NULL,
  INDEX idx_bairro (bairro),
  INDEX idx_categoria (categoria),
  INDEX idx_preco (preco)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
