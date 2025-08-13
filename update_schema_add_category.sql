-- This script updates the `tools` table to add a `category` column.
-- Run this script in the SQL tab of phpMyAdmin for your database ONE TIME.
-- This is for users who have ALREADY run the initial `schema.sql`.

ALTER TABLE `tools`
ADD COLUMN `category` VARCHAR(255) NOT NULL DEFAULT 'Utilities & Calendars' COMMENT 'The category of the tool.' AFTER `description`;

-- After running this, your `tools` table will be ready for the new category feature.
-- Any existing tools will be assigned the default category 'Utilities & Calendars'.
-- You can change them manually in phpMyAdmin if you wish.
