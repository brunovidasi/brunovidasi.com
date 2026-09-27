-- ============================================================================
-- ProgrammerTime - SQLite schema
--
-- SQLite port of schema.sql (the MySQL version). Applied automatically by
-- application/config/instance.php the first time the app runs with
-- 'db' => array('driver' => 'sqlite'); there is no manual import step.
--
-- Differences from the MySQL schema: ENUM columns are TEXT, secondary keys are
-- separate CREATE INDEX statements, and user.login/email are COLLATE NOCASE
-- to keep MySQL's case-insensitive matching on login.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- access_level  (auth_model.php / user_model.php / access_level.php)
-- Permission profiles ("roles"), referenced by user.access_level.
-- Flags are stored as 'yes'/'no' strings (see auth_model->access_level()).
-- ----------------------------------------------------------------------------
CREATE TABLE access_level (
    id                    INTEGER PRIMARY KEY AUTOINCREMENT,
    role                  VARCHAR(100) NOT NULL,
    can_create_project    TEXT NOT NULL DEFAULT 'no',
    can_edit_project      TEXT NOT NULL DEFAULT 'no',
    can_create_client     TEXT NOT NULL DEFAULT 'no',
    can_edit_client       TEXT NOT NULL DEFAULT 'no',
    can_create_user       TEXT NOT NULL DEFAULT 'no',
    can_edit_user         TEXT NOT NULL DEFAULT 'no',
    can_send_report       TEXT NOT NULL DEFAULT 'no',
    can_log_time          TEXT NOT NULL DEFAULT 'no',
    can_log_payment       TEXT NOT NULL DEFAULT 'no',
    UNIQUE (role));

-- ----------------------------------------------------------------------------
-- user  (user_model.php / auth_model.php)
-- login/password verified in auth.php::login() via hash_password() (md5 helper).
-- ----------------------------------------------------------------------------
CREATE TABLE user (
    user_id             INTEGER PRIMARY KEY AUTOINCREMENT,
    login               VARCHAR(100) COLLATE NOCASE NOT NULL,
    name                VARCHAR(150) NOT NULL,
    email               VARCHAR(150) COLLATE NOCASE NOT NULL,
    employee_id         VARCHAR(50)  DEFAULT NULL,
    id_number           VARCHAR(50)  DEFAULT NULL,
    tax_id              VARCHAR(50)  DEFAULT NULL,
    birth_date          DATE         DEFAULT NULL,
    salt                VARCHAR(50)  DEFAULT NULL,
    password            VARCHAR(255) NOT NULL,
    email_token         VARCHAR(255) DEFAULT NULL,
    access_level        INT DEFAULT NULL,
    color               VARCHAR(10)  DEFAULT '#3c8dbc',
    image               VARCHAR(255) NOT NULL DEFAULT 'none.png',
    status              TEXT NOT NULL DEFAULT 'active',
    confirmed           TEXT NOT NULL DEFAULT 'no',
    reload              TEXT DEFAULT 'no',
    login_count         INT NOT NULL DEFAULT 0,
    last_access         DATETIME     DEFAULT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (login),
    UNIQUE (email),
    CONSTRAINT fk_user_access_level FOREIGN KEY (access_level)
        REFERENCES access_level (id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- client  (client_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE client (
    client_id               INTEGER PRIMARY KEY AUTOINCREMENT,
    name                    VARCHAR(150) NOT NULL,
    website                 VARCHAR(150) DEFAULT NULL,
    email                   VARCHAR(150) DEFAULT NULL,
    phone                   VARCHAR(30)  DEFAULT NULL,
    mobile                  VARCHAR(30)  DEFAULT NULL,
    legal_name              VARCHAR(150) DEFAULT NULL,
    contact_name            VARCHAR(150) DEFAULT NULL,
    contact_email           VARCHAR(150) DEFAULT NULL,
    contact_phone           VARCHAR(30)  DEFAULT NULL,
    address                 VARCHAR(150) DEFAULT NULL,
    address_number          VARCHAR(20)  DEFAULT NULL,
    address_line2           VARCHAR(100) DEFAULT NULL,
    address_district        VARCHAR(100) DEFAULT NULL,
    address_state           VARCHAR(100) DEFAULT NULL,
    address_city            VARCHAR(100) DEFAULT NULL,
    address_postcode        VARCHAR(20)  DEFAULT NULL,
    tax_id                  VARCHAR(50)  DEFAULT NULL,
    company_tax_id          VARCHAR(50)  DEFAULT NULL,
    status                  TEXT NOT NULL DEFAULT 'active',
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (email)
);

-- ----------------------------------------------------------------------------
-- company  (company_model.php) - singleton row (company_id = 1) with company info
-- representative_id references the responsible user.
-- ----------------------------------------------------------------------------
CREATE TABLE company (
    company_id              INTEGER PRIMARY KEY AUTOINCREMENT,
    representative_id       INT DEFAULT NULL,
    name                    VARCHAR(150) DEFAULT NULL,
    legal_name              VARCHAR(150) DEFAULT NULL,
    company_tax_id          VARCHAR(50)  DEFAULT NULL,
    email                   VARCHAR(150) DEFAULT NULL,
    phone                   VARCHAR(30)  DEFAULT NULL,
    mobile                  VARCHAR(30)  DEFAULT NULL,
    website                 VARCHAR(150) DEFAULT NULL,
    logo_image              VARCHAR(255) DEFAULT NULL,
    founding_date           DATE DEFAULT NULL,
    plan                    VARCHAR(50)  DEFAULT NULL,
    activation              VARCHAR(20)  DEFAULT NULL,
    address                 VARCHAR(150) DEFAULT NULL,
    address_number          VARCHAR(20)  DEFAULT NULL,
    address_district        VARCHAR(100) DEFAULT NULL,
    address_line2           VARCHAR(100) DEFAULT NULL,
    address_city            VARCHAR(100) DEFAULT NULL,
    address_state           VARCHAR(100) DEFAULT NULL,
    address_postcode        VARCHAR(20)  DEFAULT NULL,
    CONSTRAINT fk_company_representative FOREIGN KEY (representative_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- project_type  (project_model.php::get_types())
-- ----------------------------------------------------------------------------
CREATE TABLE project_type (
    type_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    type      VARCHAR(100) NOT NULL
);

-- ----------------------------------------------------------------------------
-- project_phase  (time_entry_model.php::get_phases())
-- ----------------------------------------------------------------------------
CREATE TABLE project_phase (
    phase_id  INTEGER PRIMARY KEY AUTOINCREMENT,
    phase     VARCHAR(100) NOT NULL
);

-- ----------------------------------------------------------------------------
-- project  (project_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE project (
    project_id        INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id         INT DEFAULT NULL,
    type_id           INT DEFAULT NULL,
    owner_id          INT DEFAULT NULL,
    name              VARCHAR(200) NOT NULL,
    image             VARCHAR(255) DEFAULT NULL,
    status            TEXT NOT NULL DEFAULT 'not_started',
    priority          VARCHAR(20)  DEFAULT NULL,
    description       TEXT,
    notes             TEXT,
    link              VARCHAR(255) DEFAULT NULL,
    deadline          DATETIME DEFAULT NULL,
    start_date        DATETIME DEFAULT NULL,
    end_date          DATETIME DEFAULT NULL,
    CONSTRAINT fk_project_client FOREIGN KEY (client_id)
        REFERENCES client (client_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_project_type FOREIGN KEY (type_id)
        REFERENCES project_type (type_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_project_owner FOREIGN KEY (owner_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- task  (task_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE task (
    task_id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id                INT NOT NULL,
    phase_id                  INT DEFAULT NULL,
    name                      VARCHAR(200) NOT NULL,
    description               TEXT,
    hours                     INT DEFAULT NULL,
    due_date                  DATETIME DEFAULT NULL,
    owner_id                  INT DEFAULT NULL,
    status                    TEXT NOT NULL DEFAULT 'not_started',
    created_by                INT DEFAULT NULL,
    created_at                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_task_phase FOREIGN KEY (phase_id)
        REFERENCES project_phase (phase_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_task_owner FOREIGN KEY (owner_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_task_created_by FOREIGN KEY (created_by)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- time_entry  (time_entry_model.php) - a logged work session on a project
-- end_time IS NULL means the timer is still running (see open_time_entry()).
-- ----------------------------------------------------------------------------
CREATE TABLE time_entry (
    time_entry_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id               INT NOT NULL,
    project_id            INT NOT NULL,
    task_id               INT DEFAULT NULL,
    phase_id              INT DEFAULT NULL,
    technical_description TEXT,
    client_description    TEXT,
    date                  DATE DEFAULT NULL,
    start_time            VARCHAR(10) DEFAULT NULL,
    end_time              VARCHAR(10) DEFAULT NULL,
    backdated             VARCHAR(5)  DEFAULT NULL,
    created_at            DATETIME DEFAULT NULL,
    finished_at           DATETIME DEFAULT NULL,
    CONSTRAINT fk_time_entry_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_task FOREIGN KEY (task_id)
        REFERENCES task (task_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_phase FOREIGN KEY (phase_id)
        REFERENCES project_phase (phase_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- payment  (finance_model.php) - payments and costs of a project
-- ----------------------------------------------------------------------------
CREATE TABLE payment (
    payment_id          INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id          INT NOT NULL,
    description         VARCHAR(255) NOT NULL,
    notes               TEXT,
    status              TEXT NOT NULL DEFAULT 'unpaid',
    type                TEXT NOT NULL DEFAULT 'project_cost',
    paid_by             TEXT NOT NULL DEFAULT 'client',
    link                VARCHAR(255) DEFAULT NULL,
    amount              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid         DECIMAL(10,2) DEFAULT NULL,
    paid_date           DATETIME DEFAULT NULL,
    invoiced_date       DATETIME DEFAULT NULL,
    CONSTRAINT fk_payment_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- project_image  (image_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE project_image (
    image_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id    INT NOT NULL,
    user_id       INT DEFAULT NULL,
    title         VARCHAR(150) DEFAULT NULL,
    caption       VARCHAR(255) DEFAULT NULL,
    image         VARCHAR(255) DEFAULT NULL,
    date          DATETIME DEFAULT NULL,
    CONSTRAINT fk_image_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_image_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- image_comment  (image_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE image_comment (
    comment_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    image_id        INT NOT NULL,
    user_id         INT DEFAULT NULL,
    comment         TEXT,
    date            DATETIME DEFAULT NULL,
    CONSTRAINT fk_comment_image FOREIGN KEY (image_id)
        REFERENCES project_image (image_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- project_email  (send_email.php::client_report() logs sent client reports)
-- ----------------------------------------------------------------------------
CREATE TABLE project_email (
    email_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id     INT NOT NULL,
    subject        VARCHAR(255) DEFAULT NULL,
    message        TEXT,
    from_name      VARCHAR(150) DEFAULT NULL,
    from_email     VARCHAR(150) DEFAULT NULL,
    to_email       VARCHAR(150) DEFAULT NULL,
    cc             VARCHAR(150) DEFAULT NULL,
    date           DATETIME DEFAULT NULL,
    CONSTRAINT fk_project_email_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- message  (message_model.php) - internal user-to-user messaging/inbox
-- user_id = mailbox owner (the row is duplicated per recipient), see
-- send_message() which inserts once per side of the conversation.
-- ----------------------------------------------------------------------------
CREATE TABLE message (
    message_id          INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id             INT NOT NULL,
    from_user_id        INT NOT NULL,
    to_user_id          INT DEFAULT NULL,
    project_id          INT DEFAULT NULL,
    subject             VARCHAR(255) DEFAULT NULL,
    message             TEXT,
    reply_to            INT DEFAULT NULL,
    sent_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_draft            TINYINT(1) NOT NULL DEFAULT 0,
    is_favorite         TINYINT(1) NOT NULL DEFAULT 0,
    is_trash            TINYINT(1) NOT NULL DEFAULT 0,
    is_read             TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_message_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_from FOREIGN KEY (from_user_id)
        REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_to FOREIGN KEY (to_user_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_message_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_message_reply FOREIGN KEY (reply_to)
        REFERENCES message (message_id) ON DELETE SET NULL ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- report  (report_model.php) - saved/generated HTML project reports
-- ----------------------------------------------------------------------------
CREATE TABLE report (
    report_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id     INT NOT NULL,
    report         TEXT,
    date           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_report_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------------------------------------------------------
-- help  (help_model.php) - in-app help articles, no FKs
-- ----------------------------------------------------------------------------
CREATE TABLE help (
    help_id    INTEGER PRIMARY KEY AUTOINCREMENT,
    title      VARCHAR(200) NOT NULL,
    text       TEXT,
    type       VARCHAR(50)  DEFAULT NULL,
    status     TEXT NOT NULL DEFAULT 'active'
);

-- ----------------------------------------------------------------------------
-- info  (auth_model.php::get_info() / dashboard::about()) - system version info
-- ----------------------------------------------------------------------------
CREATE TABLE info (
    info_id                INTEGER PRIMARY KEY AUTOINCREMENT,
    version                VARCHAR(20) NOT NULL DEFAULT '1.0.0.0',
    php                    VARCHAR(20) DEFAULT NULL,
    site                   VARCHAR(150) DEFAULT NULL,
    developer              VARCHAR(150) DEFAULT NULL,
    developer_email        VARCHAR(150) DEFAULT NULL,
    release_date           DATETIME DEFAULT NULL
);


CREATE INDEX idx_user_access_level ON user (access_level);
CREATE INDEX idx_client_name ON client (name);
CREATE INDEX idx_company_representative_id ON company (representative_id);
CREATE INDEX idx_project_client_id ON project (client_id);
CREATE INDEX idx_project_type_id ON project (type_id);
CREATE INDEX idx_project_owner_id ON project (owner_id);
CREATE INDEX idx_task_project_id ON task (project_id);
CREATE INDEX idx_task_phase_id ON task (phase_id);
CREATE INDEX idx_task_owner_id ON task (owner_id);
CREATE INDEX idx_task_created_by ON task (created_by);
CREATE INDEX idx_time_entry_user_id ON time_entry (user_id);
CREATE INDEX idx_time_entry_project_id ON time_entry (project_id);
CREATE INDEX idx_time_entry_task_id ON time_entry (task_id);
CREATE INDEX idx_time_entry_phase_id ON time_entry (phase_id);
CREATE INDEX idx_payment_project_id ON payment (project_id);
CREATE INDEX idx_image_project_id ON project_image (project_id);
CREATE INDEX idx_image_user_id ON project_image (user_id);
CREATE INDEX idx_comment_image_id ON image_comment (image_id);
CREATE INDEX idx_comment_user_id ON image_comment (user_id);
CREATE INDEX idx_project_email_project_id ON project_email (project_id);
CREATE INDEX idx_message_user_id ON message (user_id);
CREATE INDEX idx_message_from ON message (from_user_id);
CREATE INDEX idx_message_to ON message (to_user_id);
CREATE INDEX idx_message_project_id ON message (project_id);
CREATE INDEX idx_message_reply_to ON message (reply_to);
CREATE INDEX idx_report_project_id ON report (project_id);

-- ============================================================================
-- Seed data
--
-- application/controllers/auth.php::index() checks
-- user_model->get_users()->num_rows() == 1 to decide whether to route
-- to /auth/first_access/ (first-access setup). That means exactly ONE
-- user row must already exist for the app to be in its expected
-- "fresh install" browsable state, and that row is the admin account.
--
-- user.access_level is a FK to access_level.id, so a matching
-- access-level row is required for the seed admin insert to succeed. A
-- second row (id=2, "Manager") is also seeded because
-- user_model->post_first_access() hard-codes access_level = '2' for the
-- account created through the first-access flow.
-- ============================================================================

INSERT INTO access_level
    (id, role, can_create_project, can_edit_project, can_create_client, can_edit_client, can_create_user, can_edit_user, can_send_report, can_log_time, can_log_payment)
VALUES
    (1, 'Administrator', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes', 'yes'),
    (2, 'Manager',       'yes', 'yes', 'yes', 'yes', 'no',  'no',  'yes', 'yes', 'yes');

-- Admin account. Its password is set when the database is created, from the
-- config's admin_password (see application/config/instance.php): stored
-- passwords are md5(encryption_key . password), so no hash can be shipped here.
INSERT INTO user
    (user_id, login, name, email, salt, password, email_token, access_level, color, image, status, confirmed, login_count, created_at)
VALUES
    (1, 'admin', 'Administrator', 'admin@programmertime.local', '', '', NULL, 1, '#3c8dbc', 'none.png', 'active', 'yes', 5, CURRENT_TIMESTAMP);

-- Minimal lookup data so the "New Project" form has options to pick
-- from on a fresh install (project_type/project_phase have no other source).
INSERT INTO project_type (type) VALUES
    ('Website'), ('Web System'), ('Mobile App'), ('Consulting');

INSERT INTO project_phase (phase) VALUES
    ('Planning'), ('Development'), ('Testing'), ('Acceptance'), ('Delivery');

-- company is a singleton row (company_id=1, hardcoded in company_model.php's
-- get_company()); company/edit fatals without it since CI2's ->row()
-- returns an empty array (not an object) when the query has no rows.
INSERT INTO company (company_id, representative_id, name, legal_name) VALUES
    (1, 1, 'ProgrammerTime', 'ProgrammerTime Ltd');

-- info is likewise a singleton row (info_id=1, hardcoded in
-- auth_model.php's get_info()), read by dashboard/about.
INSERT INTO info (info_id, version, php, site, developer, developer_email, release_date) VALUES
    (1, '1.0.0', '8.1', 'www.programmertime.com', 'Bruno Vieira', 'bruno@programmertime.com', CURRENT_TIMESTAMP);
