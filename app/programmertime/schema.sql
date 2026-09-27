-- ============================================================================
-- ProgrammerTime (CodeIgniter 2.2.0) - reconstructed MySQL 8 schema
--
-- This schema was reverse-engineered from the CodeIgniter models/controllers
-- under application/models and application/controllers (Active Record calls,
-- raw $this->db->query() SQL, and array keys passed to insert()/update()).
-- No original .sql dump existed in the repository.
--
-- application/config/database.php confirms:
--   driver = mysql, charset = utf8
-- application/config/config.php confirms sess_use_database = FALSE, so the
-- CI session library table (user_session) is NOT required and was omitted.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS programmertime
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE programmertime;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- access_level  (auth_model.php / user_model.php / access_level.php)
-- Permission profiles ("roles"), referenced by user.access_level.
-- Flags are stored as 'yes'/'no' strings (see auth_model->access_level()).
-- ----------------------------------------------------------------------------
CREATE TABLE access_level (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role                    VARCHAR(100) NOT NULL,
    can_create_project      ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_edit_project        ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_create_client       ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_edit_client         ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_create_user         ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_edit_user           ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_send_report         ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_log_time            ENUM('yes','no') NOT NULL DEFAULT 'no',
    can_log_payment         ENUM('yes','no') NOT NULL DEFAULT 'no',
    PRIMARY KEY (id),
    UNIQUE KEY uq_access_level_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- user  (user_model.php / auth_model.php)
-- login/password verified in auth.php::login() via hash_password() (md5 helper).
-- ----------------------------------------------------------------------------
CREATE TABLE user (
    user_id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login                   VARCHAR(100) NOT NULL,
    name                    VARCHAR(150) NOT NULL,
    email                   VARCHAR(150) NOT NULL,
    employee_id             VARCHAR(50)  DEFAULT NULL,
    id_number               VARCHAR(20)  DEFAULT NULL,
    tax_id                  VARCHAR(20)  DEFAULT NULL,
    birth_date              DATE         DEFAULT NULL,
    salt                    VARCHAR(50)  DEFAULT NULL,
    password                VARCHAR(255) NOT NULL,
    email_token             VARCHAR(255) DEFAULT NULL COMMENT 'confirmation/reset token, see generate_confirmation_code()',
    access_level            INT UNSIGNED DEFAULT NULL,
    color                   VARCHAR(10)  DEFAULT '#3c8dbc' COMMENT 'hex color used in UI avatars',
    image                   VARCHAR(255) NOT NULL DEFAULT 'none.png',
    status                  ENUM('active','inactive') NOT NULL DEFAULT 'active',
    confirmed               ENUM('yes','no') NOT NULL DEFAULT 'no',
    reload                  ENUM('yes','no') DEFAULT 'no',
    login_count             INT UNSIGNED NOT NULL DEFAULT 0,
    last_access             DATETIME     DEFAULT NULL,
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_user_login (login),
    UNIQUE KEY uq_user_email (email),
    KEY idx_user_access_level (access_level),
    CONSTRAINT fk_user_access_level FOREIGN KEY (access_level)
        REFERENCES access_level (id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- client  (client_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE client (
    client_id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name                    VARCHAR(150) NOT NULL,
    website                 VARCHAR(150) DEFAULT NULL,
    email                   VARCHAR(150) DEFAULT NULL,
    phone                   VARCHAR(20)  DEFAULT NULL,
    mobile                  VARCHAR(20)  DEFAULT NULL,
    legal_name              VARCHAR(150) DEFAULT NULL,
    contact_name            VARCHAR(150) DEFAULT NULL,
    contact_email           VARCHAR(150) DEFAULT NULL,
    contact_phone           VARCHAR(20)  DEFAULT NULL,
    address                 VARCHAR(150) DEFAULT NULL,
    address_number          VARCHAR(20)  DEFAULT NULL,
    address_line2     VARCHAR(100) DEFAULT NULL,
    address_district        VARCHAR(100) DEFAULT NULL,
    address_state           VARCHAR(100)   DEFAULT NULL,
    address_city            VARCHAR(100) DEFAULT NULL,
    address_postcode        VARCHAR(20)  DEFAULT NULL,
    tax_id                  VARCHAR(20)  DEFAULT NULL,
    company_tax_id          VARCHAR(20)  DEFAULT NULL,
    status                  ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (client_id),
    UNIQUE KEY uq_client_email (email),
    KEY idx_client_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- company  (company_model.php) - singleton row (company_id = 1) with company info
-- representative_id references the responsible user.
-- ----------------------------------------------------------------------------
CREATE TABLE company (
    company_id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    representative_id       INT UNSIGNED DEFAULT NULL,
    name                    VARCHAR(150) DEFAULT NULL,
    legal_name              VARCHAR(150) DEFAULT NULL,
    company_tax_id          VARCHAR(20)  DEFAULT NULL,
    email                   VARCHAR(150) DEFAULT NULL,
    phone                   VARCHAR(20)  DEFAULT NULL,
    mobile                  VARCHAR(20)  DEFAULT NULL,
    website                 VARCHAR(150) DEFAULT NULL,
    logo_image              VARCHAR(255) DEFAULT NULL,
    founding_date           DATE DEFAULT NULL,
    plan                    VARCHAR(50)  DEFAULT NULL,
    activation              VARCHAR(20)  DEFAULT NULL,
    address                 VARCHAR(150) DEFAULT NULL,
    address_number          VARCHAR(20)  DEFAULT NULL,
    address_district        VARCHAR(100) DEFAULT NULL,
    address_line2     VARCHAR(100) DEFAULT NULL,
    address_city            VARCHAR(100) DEFAULT NULL,
    address_state           VARCHAR(100)   DEFAULT NULL,
    address_postcode        VARCHAR(20)  DEFAULT NULL,
    PRIMARY KEY (company_id),
    KEY idx_company_representative_id (representative_id),
    CONSTRAINT fk_company_representative FOREIGN KEY (representative_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- project_type  (project_model.php::get_types())
-- ----------------------------------------------------------------------------
CREATE TABLE project_type (
    type_id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type                    VARCHAR(100) NOT NULL,
    PRIMARY KEY (type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- project_phase  (time_entry_model.php::get_phases())
-- ----------------------------------------------------------------------------
CREATE TABLE project_phase (
    phase_id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    phase                   VARCHAR(100) NOT NULL,
    PRIMARY KEY (phase_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- project  (project_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE project (
    project_id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    client_id               INT UNSIGNED DEFAULT NULL,
    type_id                 INT UNSIGNED DEFAULT NULL,
    owner_id                INT UNSIGNED DEFAULT NULL,
    name                    VARCHAR(200) NOT NULL,
    image                   VARCHAR(255) DEFAULT NULL,
    status                  ENUM('not_started','in_progress','paused','cancelled','completed') NOT NULL DEFAULT 'not_started',
    priority                VARCHAR(20)  DEFAULT NULL,
    description             TEXT,
    notes                   TEXT,
    link                    VARCHAR(255) DEFAULT NULL,
    deadline                DATETIME DEFAULT NULL,
    start_date              DATETIME DEFAULT NULL,
    end_date                DATETIME DEFAULT NULL,
    PRIMARY KEY (project_id),
    KEY idx_project_client_id (client_id),
    KEY idx_project_type_id (type_id),
    KEY idx_project_owner_id (owner_id),
    CONSTRAINT fk_project_client FOREIGN KEY (client_id)
        REFERENCES client (client_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_project_type FOREIGN KEY (type_id)
        REFERENCES project_type (type_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_project_owner FOREIGN KEY (owner_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- task  (task_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE task (
    task_id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    phase_id                INT UNSIGNED DEFAULT NULL,
    name                    VARCHAR(200) NOT NULL,
    description             TEXT,
    hours                   INT UNSIGNED DEFAULT NULL COMMENT 'estimated hours',
    due_date                DATETIME DEFAULT NULL,
    owner_id                INT UNSIGNED DEFAULT NULL,
    status                  ENUM('not_started','in_progress','completed','cancelled') NOT NULL DEFAULT 'not_started',
    created_by              INT UNSIGNED DEFAULT NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (task_id),
    KEY idx_task_project_id (project_id),
    KEY idx_task_phase_id (phase_id),
    KEY idx_task_owner_id (owner_id),
    KEY idx_task_created_by (created_by),
    CONSTRAINT fk_task_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_task_phase FOREIGN KEY (phase_id)
        REFERENCES project_phase (phase_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_task_owner FOREIGN KEY (owner_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_task_created_by FOREIGN KEY (created_by)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- time_entry  (time_entry_model.php) - a logged work session on a project
-- end_time IS NULL means the timer is still running (see open_time_entry()).
-- ----------------------------------------------------------------------------
CREATE TABLE time_entry (
    time_entry_id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED NOT NULL,
    project_id              INT UNSIGNED NOT NULL,
    task_id                 INT UNSIGNED DEFAULT NULL,
    phase_id                INT UNSIGNED DEFAULT NULL,
    technical_description   TEXT,
    client_description      TEXT,
    date                    DATE DEFAULT NULL,
    start_time              VARCHAR(10) DEFAULT NULL COMMENT 'HH:MM',
    end_time                VARCHAR(10) DEFAULT NULL COMMENT 'HH:MM, NULL while the timer is running',
    backdated               VARCHAR(5)  DEFAULT NULL COMMENT '1/empty flag from the backdated() helper',
    created_at              DATETIME DEFAULT NULL,
    finished_at             DATETIME DEFAULT NULL,
    PRIMARY KEY (time_entry_id),
    KEY idx_time_entry_user_id (user_id),
    KEY idx_time_entry_project_id (project_id),
    KEY idx_time_entry_task_id (task_id),
    KEY idx_time_entry_phase_id (phase_id),
    CONSTRAINT fk_time_entry_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_task FOREIGN KEY (task_id)
        REFERENCES task (task_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_time_entry_phase FOREIGN KEY (phase_id)
        REFERENCES project_phase (phase_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- payment  (finance_model.php) - payments and costs of a project
-- ----------------------------------------------------------------------------
CREATE TABLE payment (
    payment_id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    description             VARCHAR(255) NOT NULL,
    notes                   TEXT,
    status                  ENUM('unpaid','invoiced','partially_paid','paid') NOT NULL DEFAULT 'unpaid',
    type                    ENUM('project_cost','external_cost','other_cost') NOT NULL DEFAULT 'project_cost',
    paid_by                 ENUM('company','client') NOT NULL DEFAULT 'client',
    link                    VARCHAR(255) DEFAULT NULL,
    amount                  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid             DECIMAL(10,2) DEFAULT NULL,
    paid_date               DATETIME DEFAULT NULL,
    invoiced_date           DATETIME DEFAULT NULL,
    PRIMARY KEY (payment_id),
    KEY idx_payment_project_id (project_id),
    CONSTRAINT fk_payment_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- project_image  (image_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE project_image (
    image_id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    user_id                 INT UNSIGNED DEFAULT NULL,
    title                   VARCHAR(150) DEFAULT NULL,
    caption                 VARCHAR(255) DEFAULT NULL,
    image                   VARCHAR(255) DEFAULT NULL,
    date                    DATETIME DEFAULT NULL,
    PRIMARY KEY (image_id),
    KEY idx_image_project_id (project_id),
    KEY idx_image_user_id (user_id),
    CONSTRAINT fk_image_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_image_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- image_comment  (image_model.php)
-- ----------------------------------------------------------------------------
CREATE TABLE image_comment (
    comment_id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_id                INT UNSIGNED NOT NULL,
    user_id                 INT UNSIGNED DEFAULT NULL,
    comment                 TEXT,
    date                    DATETIME DEFAULT NULL,
    PRIMARY KEY (comment_id),
    KEY idx_comment_image_id (image_id),
    KEY idx_comment_user_id (user_id),
    CONSTRAINT fk_comment_image FOREIGN KEY (image_id)
        REFERENCES project_image (image_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id)
        REFERENCES user (user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- project_email  (send_email.php::client_report() logs sent client reports)
-- ----------------------------------------------------------------------------
CREATE TABLE project_email (
    email_id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    subject                 VARCHAR(255) DEFAULT NULL,
    message                 TEXT,
    from_name               VARCHAR(150) DEFAULT NULL,
    from_email              VARCHAR(150) DEFAULT NULL,
    to_email                VARCHAR(150) DEFAULT NULL,
    cc                      VARCHAR(150) DEFAULT NULL,
    date                    DATETIME DEFAULT NULL,
    PRIMARY KEY (email_id),
    KEY idx_project_email_project_id (project_id),
    CONSTRAINT fk_project_email_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- message  (message_model.php) - internal user-to-user messaging/inbox
-- user_id = mailbox owner (the row is duplicated per recipient), see
-- send_message() which inserts once per side of the conversation.
-- ----------------------------------------------------------------------------
CREATE TABLE message (
    message_id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED NOT NULL COMMENT 'mailbox owner',
    from_user_id            INT UNSIGNED NOT NULL,
    to_user_id              INT UNSIGNED DEFAULT NULL,
    project_id              INT UNSIGNED DEFAULT NULL,
    subject                 VARCHAR(255) DEFAULT NULL,
    message                 TEXT,
    reply_to                INT UNSIGNED DEFAULT NULL,
    sent_at                 DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_draft                TINYINT(1) NOT NULL DEFAULT 0,
    is_favorite             TINYINT(1) NOT NULL DEFAULT 0,
    is_trash                TINYINT(1) NOT NULL DEFAULT 0,
    is_read                 TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (message_id),
    KEY idx_message_user_id (user_id),
    KEY idx_message_from (from_user_id),
    KEY idx_message_to (to_user_id),
    KEY idx_message_project_id (project_id),
    KEY idx_message_reply_to (reply_to),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- report  (report_model.php) - saved/generated HTML project reports
-- ----------------------------------------------------------------------------
CREATE TABLE report (
    report_id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id              INT UNSIGNED NOT NULL,
    report                  LONGTEXT COMMENT 'generated report HTML',
    date                    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (report_id),
    KEY idx_report_project_id (project_id),
    CONSTRAINT fk_report_project FOREIGN KEY (project_id)
        REFERENCES project (project_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- help  (help_model.php) - in-app help articles, no FKs
-- ----------------------------------------------------------------------------
CREATE TABLE help (
    help_id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title                   VARCHAR(200) NOT NULL,
    text                    TEXT,
    type                    VARCHAR(50)  DEFAULT NULL,
    status                  ENUM('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (help_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- info  (auth_model.php::get_info() / dashboard::about()) - system version info
-- ----------------------------------------------------------------------------
CREATE TABLE info (
    info_id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    version                 VARCHAR(20) NOT NULL DEFAULT '1.0.0.0',
    php                     VARCHAR(20) DEFAULT NULL COMMENT 'recommended PHP version, shown on dashboard/about',
    site                    VARCHAR(150) DEFAULT NULL,
    developer               VARCHAR(150) DEFAULT NULL,
    developer_email         VARCHAR(150) DEFAULT NULL,
    release_date            DATETIME DEFAULT NULL,
    PRIMARY KEY (info_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- Seed data
--
-- application/controllers/auth.php::index() checks
-- user_model->get_users()->num_rows() == 1 to decide whether to route
-- to /auth/first_access/ (first-access setup). That means exactly ONE
-- user row must already exist for the app to be in its expected
-- "fresh install" browsable state, and that row is the admin account -- see
-- the seed insert below for the dev password / how to regenerate the hash.
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
    (2, 'Manager',       'yes', 'yes', 'yes', 'yes', 'no', 'no', 'yes', 'yes', 'yes');

-- Admin/first-access user. Login is assumed to be 'admin' (no explicit
-- string was found in the source). password = md5(encryption_key . 'admin123')
-- using the placeholder encryption_key shipped in config.php -- this is a
-- LOCAL DEV PASSWORD ONLY ('admin123'). If you change encryption_key (you
-- should, before any real deployment -- see README), regenerate this hash:
--   php -r "echo md5('<your_encryption_key>' . '<your_password>');"
-- and UPDATE user SET password='<new_hash>' WHERE user_id=1;
INSERT INTO user
    (user_id, login, name, email, salt, password, email_token, access_level, color, image, status, confirmed, login_count, created_at)
VALUES
    (1, 'admin', 'Administrator', 'admin@programmertime.local', '', 'e9e127f7efd0681d0bc4d35a66a815e0', NULL, 1, '#3c8dbc', 'none.png', 'active', 'yes', 5, NOW());

ALTER TABLE user AUTO_INCREMENT = 2;

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
    (1, '1.0.0', '8.1', 'www.programmertime.com', 'Bruno Vieira', 'bruno@programmertime.com', NOW());
