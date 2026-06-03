CREATE DATABASE IF NOT EXISTS abroad_info
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;

USE abroad_info;

CREATE TABLE IF NOT EXISTS users (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	account VARCHAR(50) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	role VARCHAR(20) NOT NULL DEFAULT 'student',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uk_users_account (account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS universities (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL,
	country VARCHAR(100) DEFAULT NULL,
	city VARCHAR(100) DEFAULT NULL,
	qs_rank INT DEFAULT NULL,
	usnews_rank INT DEFAULT NULL,
	website VARCHAR(500) DEFAULT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_universities_country (country),
	KEY idx_universities_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projects (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	university_id BIGINT UNSIGNED NOT NULL,
	name VARCHAR(255) NOT NULL,
	description TEXT,
	language_requirement VARCHAR(255) DEFAULT NULL,
	gpa_requirement DECIMAL(5,2) DEFAULT NULL,
	degree_level VARCHAR(50) DEFAULT NULL,
	deadline_date DATE DEFAULT NULL,
	page_url VARCHAR(500) DEFAULT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_projects_university_id (university_id),
	KEY idx_projects_name (name),
	KEY idx_projects_deadline (deadline_date),
	CONSTRAINT fk_projects_university
		FOREIGN KEY (university_id) REFERENCES universities (id)
		ON DELETE CASCADE
		ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admission_pages (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	university_id BIGINT UNSIGNED DEFAULT NULL,
	university_home VARCHAR(500) DEFAULT NULL,
	page_title VARCHAR(500) DEFAULT NULL,
	page_url VARCHAR(500) NOT NULL,
	requirement_snippet TEXT,
	country VARCHAR(100) DEFAULT NULL,
	deadline_date DATE DEFAULT NULL,
	source VARCHAR(30) NOT NULL DEFAULT 'spider',
	crawled_at TIMESTAMP NULL DEFAULT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY uk_admission_pages_page_url (page_url),
	KEY idx_admission_pages_country (country),
	KEY idx_admission_pages_deadline (deadline_date),
	KEY idx_admission_pages_university_id (university_id),
	CONSTRAINT fk_admission_pages_university
		FOREIGN KEY (university_id) REFERENCES universities (id)
		ON DELETE SET NULL
		ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

