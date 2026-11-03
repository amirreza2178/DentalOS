-- DentalOS local MariaDB bootstrap
-- Run this ONLY after you can connect as a MariaDB admin (usually root) in HeidiSQL.
-- Do not run against other existing databases. Do not drop anything.
-- Replace CHANGE_ME_APP_PASSWORD with a strong password of your choice.

CREATE DATABASE IF NOT EXISTS `dental_os`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'dental_os_app'@'127.0.0.1'
  IDENTIFIED BY 'CHANGE_ME_APP_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES, DROP, CREATE TEMPORARY TABLES, LOCK TABLES
  ON `dental_os`.*
  TO 'dental_os_app'@'127.0.0.1';

FLUSH PRIVILEGES;

SHOW DATABASES LIKE 'dental_os';
SELECT user, host FROM mysql.user WHERE user = 'dental_os_app';
