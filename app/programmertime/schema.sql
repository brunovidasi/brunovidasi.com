-- ============================================================================
-- ProgrammerTime (CodeIgniter 2.2.0) - reconstructed MySQL 8 schema
--
-- This schema was reverse-engineered from the CodeIgniter models/controllers
-- under application/models and application/controllers (Active Record calls,
-- raw $this->db->query() SQL, and array keys passed to insert()/update()).
-- No original .sql dump existed in the repository.
--
-- application/config/database.php confirms:
--   database = brunovid_ptime_sce, driver = mysql, charset = utf8
-- application/config/config.php confirms sess_use_database = FALSE, so the
-- CI session library table (usuario_sessao) is NOT required and was omitted.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS brunovid_ptime_sce
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE brunovid_ptime_sce;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- usuario_nivel_acesso  (acesso_model.php / usuario_model.php / nivel_acesso.php)
-- Permission profiles ("cargo"), referenced by usuario.nivel_acesso.
-- Flags are stored as 'sim'/'nao' strings (see acesso_model->nivel_acesso()).
-- ----------------------------------------------------------------------------
CREATE TABLE usuario_nivel_acesso (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cargo               VARCHAR(100) NOT NULL,
    cadastra_projeto    ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    edita_projeto       ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    cadastra_cliente    ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    edita_cliente       ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    cadastra_usuario    ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    edita_usuario       ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    envia_relatorio     ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    lanca_etapa         ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    lanca_pagamento     ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_nivel_acesso_cargo (cargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- usuario  (usuario_model.php / acesso_model.php)
-- login/senha verified in acesso.php::logar() via cripto() (md5 helper).
-- ----------------------------------------------------------------------------
CREATE TABLE usuario (
    idusuario           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login               VARCHAR(100) NOT NULL,
    nome                VARCHAR(150) NOT NULL,
    email               VARCHAR(150) NOT NULL,
    matricula           VARCHAR(50)  DEFAULT NULL,
    rg                  VARCHAR(20)  DEFAULT NULL,
    cpf                 VARCHAR(20)  DEFAULT NULL,
    data_nascimento     DATE         DEFAULT NULL,
    salt                VARCHAR(50)  DEFAULT NULL,
    senha               VARCHAR(255) NOT NULL,
    email_senha         VARCHAR(255) DEFAULT NULL COMMENT 'confirmation/reset token, see gera_confirmacao()',
    nivel_acesso        INT UNSIGNED DEFAULT NULL,
    cor                 VARCHAR(10)  DEFAULT '#3c8dbc' COMMENT 'hex color used in UI avatars',
    imagem              VARCHAR(255) NOT NULL DEFAULT 'none.png',
    status              ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    usuario_confirmado  ENUM('sim','nao') NOT NULL DEFAULT 'nao',
    recarregar          ENUM('sim','nao') DEFAULT 'nao',
    numero_acesso       INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_acesso       DATETIME     DEFAULT NULL,
    data_cadastro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idusuario),
    UNIQUE KEY uq_usuario_login (login),
    UNIQUE KEY uq_usuario_email (email),
    KEY idx_usuario_nivel_acesso (nivel_acesso),
    CONSTRAINT fk_usuario_nivel_acesso FOREIGN KEY (nivel_acesso)
        REFERENCES usuario_nivel_acesso (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- cliente  (cliente_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE cliente (
    idcliente               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome                     VARCHAR(150) NOT NULL,
    website                  VARCHAR(150) DEFAULT NULL,
    email                    VARCHAR(150) DEFAULT NULL,
    telefone                 VARCHAR(20)  DEFAULT NULL,
    celular                  VARCHAR(20)  DEFAULT NULL,
    razao_social             VARCHAR(150) DEFAULT NULL,
    nome_contato             VARCHAR(150) DEFAULT NULL,
    email_contato            VARCHAR(150) DEFAULT NULL,
    telefone_contato         VARCHAR(20)  DEFAULT NULL,
    endereco                 VARCHAR(150) DEFAULT NULL,
    endereco_numero          VARCHAR(20)  DEFAULT NULL,
    endereco_complemento     VARCHAR(100) DEFAULT NULL,
    endereco_bairro          VARCHAR(100) DEFAULT NULL,
    endereco_estado          VARCHAR(2)   DEFAULT NULL,
    endereco_cidade          VARCHAR(100) DEFAULT NULL,
    endereco_cep             VARCHAR(20)  DEFAULT NULL,
    cpf                      VARCHAR(20)  DEFAULT NULL,
    cnpj                     VARCHAR(20)  DEFAULT NULL,
    status                   ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    data_cadastro            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idcliente),
    UNIQUE KEY uq_cliente_email (email),
    KEY idx_cliente_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- empresa  (empresa_model.php) - singleton row (idempresa = 1) with company info
-- idrepresentante references the responsible usuario.
-- ----------------------------------------------------------------------------
CREATE TABLE empresa (
    idempresa               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idrepresentante         INT UNSIGNED DEFAULT NULL,
    nome                     VARCHAR(150) DEFAULT NULL,
    razao_social             VARCHAR(150) DEFAULT NULL,
    cnpj                     VARCHAR(20)  DEFAULT NULL,
    email                    VARCHAR(150) DEFAULT NULL,
    telefone                 VARCHAR(20)  DEFAULT NULL,
    celular                  VARCHAR(20)  DEFAULT NULL,
    website                  VARCHAR(150) DEFAULT NULL,
    imagem_logo              VARCHAR(255) DEFAULT NULL,
    data_fundacao            DATE DEFAULT NULL,
    plano                    VARCHAR(50)  DEFAULT NULL,
    ativacao                 VARCHAR(20)  DEFAULT NULL,
    endereco                 VARCHAR(150) DEFAULT NULL,
    endereco_numero          VARCHAR(20)  DEFAULT NULL,
    endereco_bairro          VARCHAR(100) DEFAULT NULL,
    endereco_complemento     VARCHAR(100) DEFAULT NULL,
    endereco_municipio       VARCHAR(100) DEFAULT NULL,
    endereco_estado          VARCHAR(2)   DEFAULT NULL,
    endereco_cep             VARCHAR(20)  DEFAULT NULL,
    PRIMARY KEY (idempresa),
    KEY idx_empresa_idrepresentante (idrepresentante),
    CONSTRAINT fk_empresa_representante FOREIGN KEY (idrepresentante)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_tipo  (projeto_model.php::get_tipos())
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tipo (
    idtipo   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo     VARCHAR(100) NOT NULL,
    PRIMARY KEY (idtipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_fase  (etapa_model.php::get_fases())
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_fase (
    idfase   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    fase     VARCHAR(100) NOT NULL,
    PRIMARY KEY (idfase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto  (projeto_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto (
    idprojeto        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idcliente         INT UNSIGNED DEFAULT NULL,
    idtipo            INT UNSIGNED DEFAULT NULL,
    idresponsavel     INT UNSIGNED DEFAULT NULL,
    nome              VARCHAR(200) NOT NULL,
    imagem            VARCHAR(255) DEFAULT NULL,
    status            ENUM('nao_comecado','desenvolvimento','pausado','cancelado','concluido') NOT NULL DEFAULT 'nao_comecado',
    prioridade        VARCHAR(20)  DEFAULT NULL,
    descricao         TEXT,
    obs               TEXT,
    link              VARCHAR(255) DEFAULT NULL,
    prazo             DATETIME DEFAULT NULL,
    data_inicio       DATETIME DEFAULT NULL,
    data_fim          DATETIME DEFAULT NULL,
    PRIMARY KEY (idprojeto),
    KEY idx_projeto_idcliente (idcliente),
    KEY idx_projeto_idtipo (idtipo),
    KEY idx_projeto_idresponsavel (idresponsavel),
    CONSTRAINT fk_projeto_cliente FOREIGN KEY (idcliente)
        REFERENCES cliente (idcliente) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_projeto_tipo FOREIGN KEY (idtipo)
        REFERENCES projeto_tipo (idtipo) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_projeto_responsavel FOREIGN KEY (idresponsavel)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_tarefa  (tarefa_model.php) - "tarefa" = task
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tarefa (
    idtarefa                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idprojeto                 INT UNSIGNED NOT NULL,
    idfase                    INT UNSIGNED DEFAULT NULL,
    nome                      VARCHAR(200) NOT NULL,
    descricao                 TEXT,
    horas                     INT UNSIGNED DEFAULT NULL COMMENT 'estimated hours',
    data_prazo                DATETIME DEFAULT NULL,
    idusuario_responsavel     INT UNSIGNED DEFAULT NULL,
    status                    ENUM('nao_comecado','desenvolvimento','concluido') NOT NULL DEFAULT 'nao_comecado',
    idusuario_cadastro        INT UNSIGNED DEFAULT NULL,
    data_cadastro             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idtarefa),
    KEY idx_tarefa_idprojeto (idprojeto),
    KEY idx_tarefa_idfase (idfase),
    KEY idx_tarefa_responsavel (idusuario_responsavel),
    KEY idx_tarefa_cadastro (idusuario_cadastro),
    CONSTRAINT fk_tarefa_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_fase FOREIGN KEY (idfase)
        REFERENCES projeto_fase (idfase) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_responsavel FOREIGN KEY (idusuario_responsavel)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_cadastro FOREIGN KEY (idusuario_cadastro)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_tarefa_hora  (etapa_model.php) - "etapa" = a logged work session/stage
-- fim IS NULL means the stage is still open (see etapa_aberta()).
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tarefa_hora (
    idetapa              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idusuario             INT UNSIGNED NOT NULL,
    idprojeto             INT UNSIGNED NOT NULL,
    idtarefa              INT UNSIGNED DEFAULT NULL,
    idfase                INT UNSIGNED DEFAULT NULL,
    descricao_tecnica     TEXT,
    descricao_cliente     TEXT,
    data                  DATE DEFAULT NULL,
    inicio                VARCHAR(10) DEFAULT NULL COMMENT 'HH:MM',
    fim                   VARCHAR(10) DEFAULT NULL COMMENT 'HH:MM, NULL while stage is open',
    retroativa            VARCHAR(5)  DEFAULT NULL COMMENT 'sim/nao flag from retroativa() helper',
    data_cadastro         DATETIME DEFAULT NULL,
    data_retorno          DATETIME DEFAULT NULL,
    PRIMARY KEY (idetapa),
    KEY idx_etapahora_idusuario (idusuario),
    KEY idx_etapahora_idprojeto (idprojeto),
    KEY idx_etapahora_idtarefa (idtarefa),
    KEY idx_etapahora_idfase (idfase),
    CONSTRAINT fk_etapahora_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_tarefa FOREIGN KEY (idtarefa)
        REFERENCES projeto_tarefa (idtarefa) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_fase FOREIGN KEY (idfase)
        REFERENCES projeto_fase (idfase) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_financeiro  (financeiro_model.php) - "financeiro" = payments/charges
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_financeiro (
    idfinanceiro       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idprojeto           INT UNSIGNED NOT NULL,
    descricao           VARCHAR(255) NOT NULL,
    obs                 TEXT,
    status              ENUM('nao_pago','cobrado','parcialmente_pago','pago') NOT NULL DEFAULT 'nao_pago',
    tipo                ENUM('custo_projeto','custo_externo','custo_outro') NOT NULL DEFAULT 'custo_projeto',
    pago_por            ENUM('empresa','cliente') NOT NULL DEFAULT 'cliente',
    link                VARCHAR(255) DEFAULT NULL,
    valor               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_pago          DECIMAL(10,2) DEFAULT NULL,
    data_pago           DATETIME DEFAULT NULL,
    data_cobrado        DATETIME DEFAULT NULL,
    PRIMARY KEY (idfinanceiro),
    KEY idx_financeiro_idprojeto (idprojeto),
    CONSTRAINT fk_financeiro_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_imagem  (imagem_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_imagem (
    idimagem     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idprojeto     INT UNSIGNED NOT NULL,
    idusuario     INT UNSIGNED DEFAULT NULL,
    titulo        VARCHAR(150) DEFAULT NULL,
    legenda       VARCHAR(255) DEFAULT NULL,
    imagem        VARCHAR(255) DEFAULT NULL,
    data          DATETIME DEFAULT NULL,
    PRIMARY KEY (idimagem),
    KEY idx_imagem_idprojeto (idprojeto),
    KEY idx_imagem_idusuario (idusuario),
    CONSTRAINT fk_imagem_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_imagem_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_imagem_comentario  (imagem_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_imagem_comentario (
    idcomentario   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idimagem        INT UNSIGNED NOT NULL,
    idusuario       INT UNSIGNED DEFAULT NULL,
    comentario      TEXT,
    data            DATETIME DEFAULT NULL,
    PRIMARY KEY (idcomentario),
    KEY idx_comentario_idimagem (idimagem),
    KEY idx_comentario_idusuario (idusuario),
    CONSTRAINT fk_comentario_imagem FOREIGN KEY (idimagem)
        REFERENCES projeto_imagem (idimagem) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comentario_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- projeto_email  (enviar_email.php::relatorio_cliente() logs sent client reports)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_email (
    idemail       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idprojeto      INT UNSIGNED NOT NULL,
    assunto        VARCHAR(255) DEFAULT NULL,
    mensagem       TEXT,
    de_nome        VARCHAR(150) DEFAULT NULL,
    de_email       VARCHAR(150) DEFAULT NULL,
    para_email     VARCHAR(150) DEFAULT NULL,
    copia          VARCHAR(150) DEFAULT NULL,
    data           DATETIME DEFAULT NULL,
    PRIMARY KEY (idemail),
    KEY idx_projeto_email_idprojeto (idprojeto),
    CONSTRAINT fk_projeto_email_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- mensagem  (mensagem_model.php) - internal user-to-user messaging/inbox
-- idusuario = mailbox owner (the row is duplicated per-recipient), see
-- enviar_mensagem() which inserts once per side of the conversation.
-- ----------------------------------------------------------------------------
CREATE TABLE mensagem (
    idmensagem          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idusuario            INT UNSIGNED NOT NULL COMMENT 'mailbox owner',
    id_usuario_from      INT UNSIGNED NOT NULL,
    idusuario_to         INT UNSIGNED DEFAULT NULL,
    idprojeto            INT UNSIGNED DEFAULT NULL,
    assunto              VARCHAR(255) DEFAULT NULL,
    mensagem             TEXT,
    resposta_de          INT UNSIGNED DEFAULT NULL,
    data_envio           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rascunho             TINYINT(1) NOT NULL DEFAULT 0,
    favorito             TINYINT(1) NOT NULL DEFAULT 0,
    lixo                 TINYINT(1) NOT NULL DEFAULT 0,
    lida                 TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (idmensagem),
    KEY idx_mensagem_idusuario (idusuario),
    KEY idx_mensagem_from (id_usuario_from),
    KEY idx_mensagem_to (idusuario_to),
    KEY idx_mensagem_idprojeto (idprojeto),
    KEY idx_mensagem_resposta_de (resposta_de),
    CONSTRAINT fk_mensagem_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mensagem_from FOREIGN KEY (id_usuario_from)
        REFERENCES usuario (idusuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_mensagem_to FOREIGN KEY (idusuario_to)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mensagem_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_mensagem_resposta FOREIGN KEY (resposta_de)
        REFERENCES mensagem (idmensagem) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- relatorio  (relatorio_model.php) - saved/generated HTML project reports
-- ----------------------------------------------------------------------------
CREATE TABLE relatorio (
    idrelatorio    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    idprojeto       INT UNSIGNED NOT NULL,
    relatorio       LONGTEXT COMMENT 'generated report HTML',
    data            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (idrelatorio),
    KEY idx_relatorio_idprojeto (idprojeto),
    CONSTRAINT fk_relatorio_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- ajuda  (ajuda_model.php) - in-app help articles, no FKs
-- ----------------------------------------------------------------------------
CREATE TABLE ajuda (
    idajuda   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titulo     VARCHAR(200) NOT NULL,
    texto      TEXT,
    tipo       VARCHAR(50)  DEFAULT NULL,
    status     ENUM('ativo','inativo') NOT NULL DEFAULT 'ativo',
    PRIMARY KEY (idajuda)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- info  (acesso_model.php::get_info() / dashboard::sobre()) - system version info
-- ----------------------------------------------------------------------------
CREATE TABLE info (
    idinfo                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    versao                 VARCHAR(20) NOT NULL DEFAULT '1.0.0.0',
    php                    VARCHAR(20) DEFAULT NULL COMMENT 'recommended PHP version, shown on dashboard/sobre',
    site                   VARCHAR(150) DEFAULT NULL,
    desenvolvedor          VARCHAR(150) DEFAULT NULL,
    desenvolvedor_email    VARCHAR(150) DEFAULT NULL,
    data_lancamento        DATETIME DEFAULT NULL,
    PRIMARY KEY (idinfo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Seed data
--
-- application/controllers/acesso.php::index() checks
-- usuario_model->get_usuarios()->num_rows() == 1 to decide whether to route
-- to /acesso/primeira_vez/ (first-access setup). That means exactly ONE
-- usuario row must already exist for the app to be in its expected
-- "fresh install" browsable state, and that row is the admin account -- see
-- the seed insert below for the dev password / how to regenerate the hash.
--
-- usuario.nivel_acesso is a FK to usuario_nivel_acesso.id, so a matching
-- access-level row is required for the seed admin insert to succeed. A
-- second row (id=2, "Gerente") is also seeded because
-- usuario_model->post_primeira_vez() hard-codes nivel_acesso = '2' for the
-- account created through the first-access flow.
-- ============================================================================

INSERT INTO usuario_nivel_acesso
    (id, cargo, cadastra_projeto, edita_projeto, cadastra_cliente, edita_cliente, cadastra_usuario, edita_usuario, envia_relatorio, lanca_etapa, lanca_pagamento)
VALUES
    (1, 'Administrador', 'sim', 'sim', 'sim', 'sim', 'sim', 'sim', 'sim', 'sim', 'sim'),
    (2, 'Gerente',        'sim', 'sim', 'sim', 'sim', 'nao', 'nao', 'sim', 'sim', 'sim');

-- Admin/first-access user. Login is assumed to be 'admin' (no explicit
-- string was found in the source). senha = md5(encryption_key . 'admin123')
-- using the placeholder encryption_key shipped in config.php -- this is a
-- LOCAL DEV PASSWORD ONLY ('admin123'). If you change encryption_key (you
-- should, before any real deployment -- see README), regenerate this hash:
--   php -r "echo md5('<your_encryption_key>' . '<your_password>');"
-- and UPDATE usuario SET senha='<new_hash>' WHERE idusuario=1;
INSERT INTO usuario
    (idusuario, login, nome, email, salt, senha, email_senha, nivel_acesso, cor, imagem, status, usuario_confirmado, numero_acesso, data_cadastro)
VALUES
    (1, 'admin', 'Administrador', 'admin@programmertime.local', '', 'e9e127f7efd0681d0bc4d35a66a815e0', NULL, 1, '#3c8dbc', 'none.png', 'ativo', 'sim', 5, NOW());

ALTER TABLE usuario AUTO_INCREMENT = 2;

-- Minimal lookup data so the "Cadastrar Projeto" form has options to pick
-- from on a fresh install (projeto_tipo/projeto_fase have no other source).
INSERT INTO projeto_tipo (tipo) VALUES
    ('Website'), ('Sistema Web'), ('Aplicativo Mobile'), ('Consultoria');

INSERT INTO projeto_fase (fase) VALUES
    ('Planejamento'), ('Desenvolvimento'), ('Testes'), ('Homologação'), ('Entrega');

-- empresa is a singleton row (idempresa=1, hardcoded in empresa_model.php's
-- get_empresa()); empresa/editar fatals without it since CI2's ->row()
-- returns an empty array (not an object) when the query has no rows.
INSERT INTO empresa (idempresa, idrepresentante, nome, razao_social) VALUES
    (1, 1, 'ProgrammerTime', 'ProgrammerTime Ltda');

-- info is likewise a singleton row (idinfo=1, hardcoded in
-- acesso_model.php's get_info()), read by dashboard/sobre.
INSERT INTO info (idinfo, versao, php, site, desenvolvedor, desenvolvedor_email, data_lancamento) VALUES
    (1, '1.0.0', '8.1', 'www.programmertime.com', 'Bruno Vieira', 'bruno@programmertime.com', NOW());
