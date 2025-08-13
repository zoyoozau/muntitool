-- This script creates the `tools` table for the Muntitool application.
-- You can run this directly in the SQL tab of phpMyAdmin for your database.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

--
-- Table structure for table `tools`
--

DROP TABLE IF EXISTS `tools`;
CREATE TABLE `tools` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'The display name of the tool.',
  `slug` varchar(255) NOT NULL COMMENT 'The URL-friendly slug.',
  `path` varchar(255) NOT NULL COMMENT 'The path to the tool''s HTML file.',
  `description` text NOT NULL COMMENT 'A short description of the tool.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'The timestamp when the tool was added.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- After creating the table, you can optionally insert the first tool if you wish,
-- or just use the upload form on the website to add it.
-- Example insert:
-- INSERT INTO `tools` (`name`, `slug`, `path`, `description`) VALUES
-- ('Dynamic Calendar Generator', 'dynamic-calendar-generator', 'tools/dynamic-calendar-generator.html', 'A versatile tool to create, customize, and share dynamic calendars for event planning and scheduling.');
