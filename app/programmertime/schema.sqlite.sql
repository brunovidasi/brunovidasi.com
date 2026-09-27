-- ============================================================================
-- ProgrammerTime - SQLite schema
--
-- SQLite port of schema.sql (the MySQL version). Applied automatically by
-- application/config/instance.php the first time the app runs with
-- 'db' => array('driver' => 'sqlite'); there is no manual import step.
--
-- Differences from the MySQL schema: ENUM columns are TEXT, secondary keys are
-- separate CREATE INDEX statements, and usuario.login/email are COLLATE NOCASE
-- to keep MySQL's case-insensitive matching on login.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- usuario_nivel_acesso  (acesso_model.php / usuario_model.php / nivel_acesso.php)
-- Permission profiles ("cargo"), referenced by usuario.nivel_acesso.
-- Flags are stored as 'sim'/'nao' strings (see acesso_model->nivel_acesso()).
-- ----------------------------------------------------------------------------
CREATE TABLE usuario_nivel_acesso (
    id                  INTEGER PRIMARY KEY AUTOINCREMENT,
    cargo               VARCHAR(100) NOT NULL,
    cadastra_projeto    TEXT NOT NULL DEFAULT 'nao',
    edita_projeto       TEXT NOT NULL DEFAULT 'nao',
    cadastra_cliente    TEXT NOT NULL DEFAULT 'nao',
    edita_cliente       TEXT NOT NULL DEFAULT 'nao',
    cadastra_usuario    TEXT NOT NULL DEFAULT 'nao',
    edita_usuario       TEXT NOT NULL DEFAULT 'nao',
    envia_relatorio     TEXT NOT NULL DEFAULT 'nao',
    lanca_etapa         TEXT NOT NULL DEFAULT 'nao',
    lanca_pagamento     TEXT NOT NULL DEFAULT 'nao',
    UNIQUE (cargo));

-- ----------------------------------------------------------------------------
-- usuario  (usuario_model.php / acesso_model.php)
-- login/senha verified in acesso.php::logar() via cripto() (md5 helper).
-- ----------------------------------------------------------------------------
CREATE TABLE usuario (
    idusuario           INTEGER PRIMARY KEY AUTOINCREMENT,
    login               VARCHAR(100) COLLATE NOCASE NOT NULL,
    nome                VARCHAR(150) NOT NULL,
    email               VARCHAR(150) COLLATE NOCASE NOT NULL,
    matricula           VARCHAR(50)  DEFAULT NULL,
    rg                  VARCHAR(20)  DEFAULT NULL,
    cpf                 VARCHAR(20)  DEFAULT NULL,
    data_nascimento     DATE         DEFAULT NULL,
    salt                VARCHAR(50)  DEFAULT NULL,
    senha               VARCHAR(255) NOT NULL,
    email_senha         VARCHAR(255) DEFAULT NULL,
    nivel_acesso        INT DEFAULT NULL,
    cor                 VARCHAR(10)  DEFAULT '#3c8dbc',
    imagem              VARCHAR(255) NOT NULL DEFAULT 'none.png',
    status              TEXT NOT NULL DEFAULT 'ativo',
    usuario_confirmado  TEXT NOT NULL DEFAULT 'nao',
    recarregar          TEXT DEFAULT 'nao',
    numero_acesso       INT NOT NULL DEFAULT 0,
    ultimo_acesso       DATETIME     DEFAULT NULL,
    data_cadastro       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (login),
    UNIQUE (email),
    CONSTRAINT fk_usuario_nivel_acesso FOREIGN KEY (nivel_acesso)
        REFERENCES usuario_nivel_acesso (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- cliente  (cliente_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE cliente (
    idcliente               INTEGER PRIMARY KEY AUTOINCREMENT,
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
    status                   TEXT NOT NULL DEFAULT 'ativo',
    data_cadastro            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (email)
);

-- ----------------------------------------------------------------------------
-- empresa  (empresa_model.php) - singleton row (idempresa = 1) with company info
-- idrepresentante references the responsible usuario.
-- ----------------------------------------------------------------------------
CREATE TABLE empresa (
    idempresa               INTEGER PRIMARY KEY AUTOINCREMENT,
    idrepresentante         INT DEFAULT NULL,
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
    CONSTRAINT fk_empresa_representante FOREIGN KEY (idrepresentante)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_tipo  (projeto_model.php::get_tipos())
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tipo (
    idtipo   INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo     VARCHAR(100) NOT NULL
);

-- ----------------------------------------------------------------------------
-- projeto_fase  (etapa_model.php::get_fases())
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_fase (
    idfase   INTEGER PRIMARY KEY AUTOINCREMENT,
    fase     VARCHAR(100) NOT NULL
);

-- ----------------------------------------------------------------------------
-- projeto  (projeto_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto (
    idprojeto        INTEGER PRIMARY KEY AUTOINCREMENT,
    idcliente         INT DEFAULT NULL,
    idtipo            INT DEFAULT NULL,
    idresponsavel     INT DEFAULT NULL,
    nome              VARCHAR(200) NOT NULL,
    imagem            VARCHAR(255) DEFAULT NULL,
    status            TEXT NOT NULL DEFAULT 'nao_comecado',
    prioridade        VARCHAR(20)  DEFAULT NULL,
    descricao         TEXT,
    obs               TEXT,
    link              VARCHAR(255) DEFAULT NULL,
    prazo             DATETIME DEFAULT NULL,
    data_inicio       DATETIME DEFAULT NULL,
    data_fim          DATETIME DEFAULT NULL,
    CONSTRAINT fk_projeto_cliente FOREIGN KEY (idcliente)
        REFERENCES cliente (idcliente) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_projeto_tipo FOREIGN KEY (idtipo)
        REFERENCES projeto_tipo (idtipo) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_projeto_responsavel FOREIGN KEY (idresponsavel)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_tarefa  (tarefa_model.php) - "tarefa" = task
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tarefa (
    idtarefa                 INTEGER PRIMARY KEY AUTOINCREMENT,
    idprojeto                 INT NOT NULL,
    idfase                    INT DEFAULT NULL,
    nome                      VARCHAR(200) NOT NULL,
    descricao                 TEXT,
    horas                     INT DEFAULT NULL,
    data_prazo                DATETIME DEFAULT NULL,
    idusuario_responsavel     INT DEFAULT NULL,
    status                    TEXT NOT NULL DEFAULT 'nao_comecado',
    idusuario_cadastro        INT DEFAULT NULL,
    data_cadastro             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tarefa_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_fase FOREIGN KEY (idfase)
        REFERENCES projeto_fase (idfase) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_responsavel FOREIGN KEY (idusuario_responsavel)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_tarefa_cadastro FOREIGN KEY (idusuario_cadastro)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_tarefa_hora  (etapa_model.php) - "etapa" = a logged work session/stage
-- fim IS NULL means the stage is still open (see etapa_aberta()).
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_tarefa_hora (
    idetapa              INTEGER PRIMARY KEY AUTOINCREMENT,
    idusuario             INT NOT NULL,
    idprojeto             INT NOT NULL,
    idtarefa              INT DEFAULT NULL,
    idfase                INT DEFAULT NULL,
    descricao_tecnica     TEXT,
    descricao_cliente     TEXT,
    data                  DATE DEFAULT NULL,
    inicio                VARCHAR(10) DEFAULT NULL,
    fim                   VARCHAR(10) DEFAULT NULL,
    retroativa            VARCHAR(5)  DEFAULT NULL,
    data_cadastro         DATETIME DEFAULT NULL,
    data_retorno          DATETIME DEFAULT NULL,
    CONSTRAINT fk_etapahora_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_tarefa FOREIGN KEY (idtarefa)
        REFERENCES projeto_tarefa (idtarefa) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_etapahora_fase FOREIGN KEY (idfase)
        REFERENCES projeto_fase (idfase) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_financeiro  (financeiro_model.php) - "financeiro" = payments/charges
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_financeiro (
    idfinanceiro       INTEGER PRIMARY KEY AUTOINCREMENT,
    idprojeto           INT NOT NULL,
    descricao           VARCHAR(255) NOT NULL,
    obs                 TEXT,
    status              TEXT NOT NULL DEFAULT 'nao_pago',
    tipo                TEXT NOT NULL DEFAULT 'custo_projeto',
    pago_por            TEXT NOT NULL DEFAULT 'cliente',
    link                VARCHAR(255) DEFAULT NULL,
    valor               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    valor_pago          DECIMAL(10,2) DEFAULT NULL,
    data_pago           DATETIME DEFAULT NULL,
    data_cobrado        DATETIME DEFAULT NULL,
    CONSTRAINT fk_financeiro_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_imagem  (imagem_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_imagem (
    idimagem     INTEGER PRIMARY KEY AUTOINCREMENT,
    idprojeto     INT NOT NULL,
    idusuario     INT DEFAULT NULL,
    titulo        VARCHAR(150) DEFAULT NULL,
    legenda       VARCHAR(255) DEFAULT NULL,
    imagem        VARCHAR(255) DEFAULT NULL,
    data          DATETIME DEFAULT NULL,
    CONSTRAINT fk_imagem_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_imagem_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_imagem_comentario  (imagem_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_imagem_comentario (
    idcomentario   INTEGER PRIMARY KEY AUTOINCREMENT,
    idimagem        INT NOT NULL,
    idusuario       INT DEFAULT NULL,
    comentario      TEXT,
    data            DATETIME DEFAULT NULL,
    CONSTRAINT fk_comentario_imagem FOREIGN KEY (idimagem)
        REFERENCES projeto_imagem (idimagem) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comentario_usuario FOREIGN KEY (idusuario)
        REFERENCES usuario (idusuario) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- projeto_email  (enviar_email.php::relatorio_cliente() logs sent client reports)
-- ----------------------------------------------------------------------------
CREATE TABLE projeto_email (
    idemail       INTEGER PRIMARY KEY AUTOINCREMENT,
    idprojeto      INT NOT NULL,
    assunto        VARCHAR(255) DEFAULT NULL,
    mensagem       TEXT,
    de_nome        VARCHAR(150) DEFAULT NULL,
    de_email       VARCHAR(150) DEFAULT NULL,
    para_email     VARCHAR(150) DEFAULT NULL,
    copia          VARCHAR(150) DEFAULT NULL,
    data           DATETIME DEFAULT NULL,
    CONSTRAINT fk_projeto_email_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- mensagem  (mensagem_model.php) - internal user-to-user messaging/inbox
-- idusuario = mailbox owner (the row is duplicated per-recipient), see
-- enviar_mensagem() which inserts once per side of the conversation.
-- ----------------------------------------------------------------------------
CREATE TABLE mensagem (
    idmensagem          INTEGER PRIMARY KEY AUTOINCREMENT,
    idusuario            INT NOT NULL,
    id_usuario_from      INT NOT NULL,
    idusuario_to         INT DEFAULT NULL,
    idprojeto            INT DEFAULT NULL,
    assunto              VARCHAR(255) DEFAULT NULL,
    mensagem             TEXT,
    resposta_de          INT DEFAULT NULL,
    data_envio           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    rascunho             TINYINT(1) NOT NULL DEFAULT 0,
    favorito             TINYINT(1) NOT NULL DEFAULT 0,
    lixo                 TINYINT(1) NOT NULL DEFAULT 0,
    lida                 TINYINT(1) NOT NULL DEFAULT 0,
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
);

-- ----------------------------------------------------------------------------
-- relatorio  (relatorio_model.php) - saved/generated HTML project reports
-- ----------------------------------------------------------------------------
CREATE TABLE relatorio (
    idrelatorio    INTEGER PRIMARY KEY AUTOINCREMENT,
    idprojeto       INT NOT NULL,
    relatorio       TEXT,
    data            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_relatorio_projeto FOREIGN KEY (idprojeto)
        REFERENCES projeto (idprojeto) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- ajuda  (ajuda_model.php) - in-app help articles, no FKs
-- ----------------------------------------------------------------------------
CREATE TABLE ajuda (
    idajuda   INTEGER PRIMARY KEY AUTOINCREMENT,
    titulo     VARCHAR(200) NOT NULL,
    texto      TEXT,
    tipo       VARCHAR(50)  DEFAULT NULL,
    status     TEXT NOT NULL DEFAULT 'ativo'
);

-- ----------------------------------------------------------------------------
-- info  (acesso_model.php::get_info() / dashboard::sobre()) - system version info
-- ----------------------------------------------------------------------------
CREATE TABLE info (
    idinfo                INTEGER PRIMARY KEY AUTOINCREMENT,
    versao                 VARCHAR(20) NOT NULL DEFAULT '1.0.0.0',
    php                    VARCHAR(20) DEFAULT NULL,
    site                   VARCHAR(150) DEFAULT NULL,
    desenvolvedor          VARCHAR(150) DEFAULT NULL,
    desenvolvedor_email    VARCHAR(150) DEFAULT NULL,
    data_lancamento        DATETIME DEFAULT NULL
);


CREATE INDEX idx_usuario_nivel_acesso ON usuario (nivel_acesso);
CREATE INDEX idx_cliente_nome ON cliente (nome);
CREATE INDEX idx_empresa_idrepresentante ON empresa (idrepresentante);
CREATE INDEX idx_projeto_idcliente ON projeto (idcliente);
CREATE INDEX idx_projeto_idtipo ON projeto (idtipo);
CREATE INDEX idx_projeto_idresponsavel ON projeto (idresponsavel);
CREATE INDEX idx_tarefa_idprojeto ON projeto_tarefa (idprojeto);
CREATE INDEX idx_tarefa_idfase ON projeto_tarefa (idfase);
CREATE INDEX idx_tarefa_responsavel ON projeto_tarefa (idusuario_responsavel);
CREATE INDEX idx_tarefa_cadastro ON projeto_tarefa (idusuario_cadastro);
CREATE INDEX idx_etapahora_idusuario ON projeto_tarefa_hora (idusuario);
CREATE INDEX idx_etapahora_idprojeto ON projeto_tarefa_hora (idprojeto);
CREATE INDEX idx_etapahora_idtarefa ON projeto_tarefa_hora (idtarefa);
CREATE INDEX idx_etapahora_idfase ON projeto_tarefa_hora (idfase);
CREATE INDEX idx_financeiro_idprojeto ON projeto_financeiro (idprojeto);
CREATE INDEX idx_imagem_idprojeto ON projeto_imagem (idprojeto);
CREATE INDEX idx_imagem_idusuario ON projeto_imagem (idusuario);
CREATE INDEX idx_comentario_idimagem ON projeto_imagem_comentario (idimagem);
CREATE INDEX idx_comentario_idusuario ON projeto_imagem_comentario (idusuario);
CREATE INDEX idx_projeto_email_idprojeto ON projeto_email (idprojeto);
CREATE INDEX idx_mensagem_idusuario ON mensagem (idusuario);
CREATE INDEX idx_mensagem_from ON mensagem (id_usuario_from);
CREATE INDEX idx_mensagem_to ON mensagem (idusuario_to);
CREATE INDEX idx_mensagem_idprojeto ON mensagem (idprojeto);
CREATE INDEX idx_mensagem_resposta_de ON mensagem (resposta_de);
CREATE INDEX idx_relatorio_idprojeto ON relatorio (idprojeto);

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

-- Admin account. Its password is set when the database is created, from the
-- config's admin_password (see application/config/instance.php): stored
-- passwords are md5(encryption_key . password), so no hash can be shipped here.
INSERT INTO usuario
    (idusuario, login, nome, email, salt, senha, email_senha, nivel_acesso, cor, imagem, status, usuario_confirmado, numero_acesso, data_cadastro)
VALUES
    (1, 'admin', 'Administrador', 'admin@programmertime.local', '', '', NULL, 1, '#3c8dbc', 'none.png', 'ativo', 'sim', 5, CURRENT_TIMESTAMP);

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
    (1, '1.0.0', '8.1', 'www.programmertime.com', 'Bruno Vieira', 'bruno@programmertime.com', CURRENT_TIMESTAMP);
