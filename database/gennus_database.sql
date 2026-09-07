-- Active: 1783825946346@@127.0.0.1@3306
CREATE DATABASE IF NOT EXISTS gennus_database
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gennus_database;

SET NAMES utf8mb4;

-- Remove tabelas antigas para permitir recriação limpa.
-- A ordem evita problemas caso relações sejam adicionadas no futuro.
DROP TABLE IF EXISTS vendas;
DROP TABLE IF EXISTS despesas;
DROP TABLE IF EXISTS funcionarios;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS cliente;

CREATE TABLE usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    sobrenome VARCHAR(100) NOT NULL,
    email_corporativo VARCHAR(190) NOT NULL,
    telefone VARCHAR(30) DEFAULT NULL,
    cpf_cnpj VARCHAR(30) DEFAULT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email_corporativo),
    UNIQUE KEY uk_usuarios_documento (cpf_cnpj)
) ENGINE=InnoDB;

-- 
CREATE TABLE clientes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(190) DEFAULT NULL,
    telefone VARCHAR(30) DEFAULT NULL,
    documento VARCHAR(30) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_clientes_nome (nome),
    KEY idx_clientes_email (email),
    KEY idx_clientes_documento (documento)
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    categoria VARCHAR(100) DEFAULT NULL,
    unidade VARCHAR(30) NOT NULL,
    custo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    preco DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estoque DECIMAL(12,3) NOT NULL DEFAULT 0.000,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_produtos_nome (nome),
    KEY idx_produtos_categoria (categoria)
) ENGINE=InnoDB;

CREATE TABLE funcionarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(150) NOT NULL,
    tipo_contrato VARCHAR(30) NOT NULL,
    documento VARCHAR(30) NOT NULL,
    salario_base DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'Ativo',
    vt VARCHAR(100) DEFAULT NULL,
    vr VARCHAR(100) DEFAULT NULL,
    extras JSON DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_funcionarios_nome (nome),
    KEY idx_funcionarios_documento (documento),
    KEY idx_funcionarios_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- DESPESAS MANUAIS
-- ============================================================
CREATE TABLE despesas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ============================================================
-- VENDAS
-- data_venda é necessária para vendas.js e charts.js.
--
-- produto_id foi mantido sem FK propositalmente:
-- o código atual permite excluir produtos, enquanto a venda mantém
-- produto_nome como fotografia histórica do item vendido.
-- ============================================================
CREATE TABLE vendas (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    produto_id INT UNSIGNED NOT NULL,
    produto_nome VARCHAR(150) NOT NULL,
    qtd DECIMAL(12,3) NOT NULL,
    unidade VARCHAR(30) NOT NULL,
    valor_total DECIMAL(12,2) NOT NULL,
    custo_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    data_venda DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    KEY idx_vendas_produto_id (produto_id),
    KEY idx_vendas_data_venda (data_venda)
) ENGINE=InnoDB;

-- ============================================================
-- TABELA LEGADA
-- cliente_login.php referencia "cliente" no singular.
-- O login principal atual usa a tabela "usuarios".
-- Mantida apenas para compatibilidade com esse endpoint antigo.
-- ============================================================
CREATE TABLE cliente (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario VARCHAR(190) NOT NULL,
    senha VARCHAR(255) NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uk_cliente_usuario (usuario)
) ENGINE=InnoDB;

-- ============================================================
-- DADOS DE DESENVOLVIMENTO
-- ============================================================
-- ATENÇÃO: o PHP atual ainda compara a senha diretamente.
-- Este usuário é SOMENTE para ambiente local de desenvolvimento.
-- Login: admin@gennus.local
-- Senha: admin123
INSERT INTO usuarios
    (nome, sobrenome, email_corporativo, telefone, cpf_cnpj, senha_hash)
VALUES
    ('Admin', 'Gennus', 'admin@gennus.local', '(41) 99999-0000',
     '00.000.000/0001-00', 'admin123');

INSERT INTO clientes (nome, email, telefone, documento) VALUES
    ('Cliente Demonstração', 'cliente@gennus.local', '(41) 98888-0001', '111.222.333-44'),
    ('Empresa Exemplo', 'contato@empresa.local', '(41) 98888-0002', '11.222.333/0001-44');

INSERT INTO produtos (nome, categoria, unidade, custo, preco, estoque) VALUES
    ('Notebook Corporativo', 'Informática', 'UN', 3200.00, 4599.90, 8.000),
    ('Monitor 24"', 'Informática', 'UN', 650.00, 899.90, 14.000),
    ('Licença ERP', 'Software', 'UN', 80.00, 199.90, 50.000);

INSERT INTO funcionarios
    (nome, tipo_contrato, documento, salario_base, status, vt, vr, extras)
VALUES
    ('Funcionário Demo', 'CLT', '222.333.444-55', 3500.00, 'Ativo',
     'Sim', 'Sim', JSON_ARRAY('Gympass: R$ 50,00'));

INSERT INTO despesas (descricao, valor) VALUES
    ('Internet', 199.90),
    ('Energia elétrica', 480.00);

-- Vendas de exemplo em dias diferentes para alimentar os gráficos.
INSERT INTO vendas
    (produto_id, produto_nome, qtd, unidade, valor_total, custo_total, data_venda)
VALUES
    (1, 'Notebook Corporativo', 1.000, 'UN', 4599.90, 3200.00, NOW()),
    (2, 'Monitor 24"', 2.000, 'UN', 1799.80, 1300.00, DATE_SUB(NOW(), INTERVAL 1 DAY)),
    (3, 'Licença ERP', 3.000, 'UN', 599.70, 240.00, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Ajusta o estoque para refletir as vendas de demonstração acima.
UPDATE produtos SET estoque = estoque - 1.000 WHERE id = 1;
UPDATE produtos SET estoque = estoque - 2.000 WHERE id = 2;
UPDATE produtos SET estoque = estoque - 3.000 WHERE id = 3;

SELECT * FROM usuarios;
