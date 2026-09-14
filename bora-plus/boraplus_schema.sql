-- BoraPlus - Schema atualizado e compatível
CREATE DATABASE IF NOT EXISTS boraplus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boraplus;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS avaliacoes;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS fotos_estabelecimento;
DROP TABLE IF EXISTS estabelecimento_categorias;
DROP TABLE IF EXISTS estabelecimentos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120)  NOT NULL,
  email           VARCHAR(160)  NOT NULL UNIQUE,
  senha_hash      VARCHAR(255)  NOT NULL,
  telefone        VARCHAR(20)   NULL,
  data_nascimento DATE          NULL,
  foto_perfil_url VARCHAR(255)  NULL,
  criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE administradores (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome            VARCHAR(120)  NOT NULL,
  email           VARCHAR(160)  NOT NULL UNIQUE,
  senha_hash      VARCHAR(255)  NOT NULL,
  telefone        VARCHAR(20)   NULL,
  criado_em       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categorias (
  id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome  VARCHAR(60) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categorias (nome) VALUES
  ('Bar'), ('Balada'), ('Pub'), ('Choperia'), ('Casa noturna'), ('Rooftop'), ('Lounge'), ('Restaurante-bar');

CREATE TABLE estabelecimentos (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome                VARCHAR(150)        NOT NULL,
  categoria           VARCHAR(60)         NOT NULL,
  preco               TINYINT UNSIGNED    NOT NULL,       -- 1 = $, 2 = $$, 3 = $$$
  bairro              VARCHAR(100)        NOT NULL,
  endereco            VARCHAR(255)        NOT NULL,
  lat                 DECIMAL(10,7)       NOT NULL,
  lng                 DECIMAL(10,7)       NOT NULL,
  descricao           TEXT                NOT NULL,
  usuario_id          INT UNSIGNED        NULL,
  administrador_id    INT UNSIGNED        NULL,
  telefone            VARCHAR(20)         NULL,
  horario_abertura    TIME                NULL,
  horario_fechamento  TIME                NULL,
  foto_capa_url       VARCHAR(255)        NULL,
  criado_em           TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_estab_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_estab_admin FOREIGN KEY (administrador_id) REFERENCES administradores(id) ON DELETE SET NULL,
  INDEX idx_bairro (bairro),
  INDEX idx_categoria (categoria),
  INDEX idx_preco (preco),
  INDEX idx_localizacao (lat, lng)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE estabelecimento_categorias (
  estabelecimento_id INT UNSIGNED NOT NULL,
  categoria_id        INT UNSIGNED NOT NULL,
  PRIMARY KEY (estabelecimento_id, categoria_id),
  CONSTRAINT fk_ec_estabelecimento FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos(id) ON DELETE CASCADE,
  CONSTRAINT fk_ec_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fotos_estabelecimento (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estabelecimento_id  INT UNSIGNED NOT NULL,
  foto_url            VARCHAR(255) NOT NULL,
  ordem               SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_foto_estabelecimento FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE eventos (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  estabelecimento_id  INT UNSIGNED NOT NULL,
  nome                VARCHAR(150) NOT NULL,
  descricao           TEXT,
  data_inicio         DATETIME NOT NULL,
  data_fim            DATETIME,
  preco               DECIMAL(8, 2) DEFAULT 0.00,
  imagem_url          VARCHAR(255),
  criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_evento_estabelecimento FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos(id) ON DELETE CASCADE,
  INDEX idx_eventos_data_inicio (data_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaliacoes (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id          INT UNSIGNED NOT NULL,
  estabelecimento_id  INT UNSIGNED NOT NULL,
  nota                TINYINT UNSIGNED NOT NULL,
  comentario          TEXT,
  criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_avaliacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_avaliacao_estabelecimento FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos(id) ON DELETE CASCADE,
  CONSTRAINT chk_nota_valida CHECK (nota BETWEEN 1 AND 5),
  CONSTRAINT uq_avaliacao_unica UNIQUE (usuario_id, estabelecimento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favoritos (
  usuario_id          INT UNSIGNED NOT NULL,
  estabelecimento_id  INT UNSIGNED NOT NULL,
  criado_em           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, estabelecimento_id),
  CONSTRAINT fk_favorito_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_favorito_estabelecimento FOREIGN KEY (estabelecimento_id) REFERENCES estabelecimentos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuário demo (demo@boraplus.com / 123456)
INSERT INTO usuarios (nome, email, senha_hash) VALUES
  ('Usuário Demo', 'demo@boraplus.com', '$2y$10$drz9hoQmHKwqYF7rYK9cTO1u6s/QN/7Besa0m0R0IignfxYwvlCau');

-- Estabelecimentos demo
INSERT INTO estabelecimentos (nome, categoria, preco, bairro, endereco, lat, lng, descricao, usuario_id) VALUES
  ('Bar Brahma', 'Bar', 2, 'Centro', 'Av. São João, 677 - Centro Histórico, São Paulo - SP', -23.5414500, -46.6388200, 'Tradicional bar e casa de shows no coração de São Paulo, famoso pelo chopp cremoso e música ao vivo.', 1),
  ('Riviera Bar', 'Lounge', 3, 'Consolação', 'Av. Paulista, 2584 - Consolação, São Paulo - SP', -23.5555000, -46.6616000, 'Um clássico paulistano na esquina da Paulista com a Consolação. Coquetelaria refinada e ambiente vintage.', 1),
  ('Bar Astor', 'Bar', 3, 'Vila Madalena', 'R. Delfina, 163 - Vila Madalena, São Paulo - SP', -23.5547000, -46.6908000, 'Boemia com elegância na Vila Madalena. Drinques clássicos, petiscos sofisticados e chopp impecável.', 1),
  ('Mundo Pensante', 'Casa noturna', 2, 'Bela Vista', 'R. Treze de Maio, 830 - Bela Vista, São Paulo - SP', -23.5583000, -46.6473000, 'Espaço cultural e noturno no Bixiga com shows ao vivo, discotecagens, arte e energia vibrante.', 1),
  ('Cervejaria Tarantino', 'Choperia', 2, 'Limão', 'R. Miguel Nelson Bechara, 316 - Limão, São Paulo - SP', -23.5074000, -46.6844000, 'A primeira cervejaria artesanal independente de grande porte na cidade, com taproom ao ar livre.', 1),
  ('Tatu Bola Bar', 'Bar', 2, 'Itaim Bibi', 'R. Clodomiro Amazonas, 202 - Itaim Bibi, São Paulo - SP', -23.5855000, -46.6778000, 'Bar descontraído com teto de fitinhas de Bonfim, caipirinhas variadas e roda de samba ao vivo.', 1),
  ('Boteco do Caranguejo', 'Restaurante-bar', 1, 'Pinheiros', 'R. dos Pinheiros, 450 - Pinheiros, São Paulo - SP', -23.5654000, -46.6874000, 'Comida de boteco saborosa, cerveja bem gelada e ambiente informal para o happy hour dos amigos.', 1);

-- Setup para banco boramais também (garante compatibilidade máxima)
CREATE DATABASE IF NOT EXISTS boramais CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE boramais;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favoritos;
DROP TABLE IF EXISTS avaliacoes;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS fotos_estabelecimento;
DROP TABLE IF EXISTS estabelecimento_categorias;
DROP TABLE IF EXISTS estabelecimentos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS administradores;
DROP TABLE IF EXISTS usuarios;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE usuarios LIKE boraplus.usuarios;
INSERT INTO usuarios SELECT * FROM boraplus.usuarios;

CREATE TABLE administradores LIKE boraplus.administradores;
INSERT INTO administradores SELECT * FROM boraplus.administradores;

CREATE TABLE categorias LIKE boraplus.categorias;
INSERT INTO categorias SELECT * FROM boraplus.categorias;

CREATE TABLE estabelecimentos LIKE boraplus.estabelecimentos;
INSERT INTO estabelecimentos SELECT * FROM boraplus.estabelecimentos;

CREATE TABLE estabelecimento_categorias LIKE boraplus.estabelecimento_categorias;
INSERT INTO estabelecimento_categorias SELECT * FROM boraplus.estabelecimento_categorias;

CREATE TABLE fotos_estabelecimento LIKE boraplus.fotos_estabelecimento;
CREATE TABLE eventos LIKE boraplus.eventos;
CREATE TABLE avaliacoes LIKE boraplus.avaliacoes;
CREATE TABLE favoritos LIKE boraplus.favoritos;
