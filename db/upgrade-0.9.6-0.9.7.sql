/* Upgrade file for DB schema update from version 0.9.6 to 0.9.7 */
/* Indeed Apply screener questions support */

ALTER TABLE `joborder` ADD COLUMN `screener_questions` TEXT DEFAULT NULL AFTER `questionnaire_id`;
