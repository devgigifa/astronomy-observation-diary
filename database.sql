-- ============================================================
-- SCRIPT DE CRIAÇÃO DO BANCO DE DADOS
-- Projeto: Diário de Observações Astronômicas
-- ============================================================

SET NAMES utf8mb4;

-- Cria o banco de dados caso ele ainda não exista
CREATE DATABASE IF NOT EXISTS astronomy_diary
    CHARACTER SET utf8mb4       -- Suporte a caracteres especiais e emojis
    COLLATE utf8mb4_unicode_ci; -- Comparação de strings sem distinção de acento

-- Seleciona o banco para usar nas próximas instruções
USE astronomy_diary;

-- ============================================================
-- TABELA PRINCIPAL: observations
-- Cada linha representa uma observação registrada pelo usuário
-- ============================================================
CREATE TABLE IF NOT EXISTS observations (
    -- Chave primária: identificador único, incrementado automaticamente
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,

    -- Data em que a observação foi feita (tipo DATE = YYYY-MM-DD)
    obs_date    DATE            NOT NULL,

    -- Local da observação (ex: "Sítio São João, MG")
    location    VARCHAR(150)    NOT NULL,

    -- Objeto celeste observado (ex: "Saturno", "Nebulosa de Órion")
    object_name VARCHAR(100)    NOT NULL,

    -- Condições do céu: usamos ENUM para limitar os valores possíveis
    -- Isso evita dados inconsistentes como "claro", "Claro", "muito claro"
    sky_conditions ENUM('excellent','good','regular','poor') NOT NULL DEFAULT 'good',

    -- Notas livres do observador (tipo TEXT suporta textos longos)
    notes       TEXT,

    -- Caminho para o arquivo de foto salvo na pasta uploads/
    -- Pode ser NULL quando o usuário não envia foto
    photo_path  VARCHAR(255)    DEFAULT NULL,

    -- Timestamp automático: preenchido pelo MySQL no momento do INSERT
    created_at  TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DADOS DE EXEMPLO para facilitar os testes iniciais
-- ============================================================
INSERT INTO observations (obs_date, location, object_name, sky_conditions, notes) VALUES
('2026-10-15', 'Parque Estadual da Serra do Mar, SP', 'Saturn',    'excellent', 'Anéis visíveis com clareza no telescópio de 6 polegadas. Divisão de Cassini perceptível.'),
('2026-10-22', 'Sítio Rural, Minas Gerais',           'Orion Nebula','good',    'Núcleo brilhante. Quatro estrelas do Trapézio identificadas.'),
('2026-11-01', 'Quintal de casa, São Paulo',           'Moon',       'regular',  'Lua cheia. Crateras Mare Tranquillitatis e Mare Imbrium bem visíveis com binóculo 10x50.');
