/* Upgrade file for DB schema update from version 0.9.5 to 0.9.6 */
/* Multi-location job posting support */

/* New table for job order locations */
CREATE TABLE `joborder_location` (
  `joborder_location_id` int(11) NOT NULL AUTO_INCREMENT,
  `joborder_id` int(11) NOT NULL,
  `site_id` int(11) NOT NULL,
  `city` varchar(64) NOT NULL DEFAULT '',
  `state` varchar(64) NOT NULL DEFAULT '',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`joborder_location_id`),
  KEY `IDX_joborder_id` (`joborder_id`),
  KEY `IDX_site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* Migrate existing job locations to the new table */
INSERT INTO `joborder_location` (`joborder_id`, `site_id`, `city`, `state`, `is_primary`, `date_created`)
SELECT `joborder_id`, `site_id`, `city`, `state`, 1, NOW()
FROM `joborder`
WHERE `city` != '' OR `state` != '';
