-- This script adds the `updated_at` column to the `tools` table.
-- This column will automatically track when a tool's record is updated.

ALTER TABLE `tools`
ADD COLUMN `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'The timestamp when the tool was last updated.' AFTER `created_at`;
