/* Upgrade file for DB schema update from version 0.9.7 to 0.9.8 */
/* 10-Stage Candidate Lifecycle Management */

/* ========== New table: candidate_lifecycle_stage ========== */
CREATE TABLE `candidate_lifecycle_stage` (
  `stage_id` int(11) NOT NULL,
  `stage_name` varchar(64) NOT NULL,
  `color_hex` varchar(7) NOT NULL,
  `maps_to_pipeline_status` int(11) NOT NULL DEFAULT 100,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`stage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `candidate_lifecycle_stage` (`stage_id`, `stage_name`, `color_hex`, `maps_to_pipeline_status`, `sort_order`) VALUES
(1,  'Capture',             '#6366F1', 100, 1),
(2,  'Application',         '#8B5CF6', 200, 2),
(3,  'Assessment',          '#EC4899', 300, 3),
(4,  'First Interview',     '#F97316', 500, 4),
(5,  'Offer',               '#EAB308', 600, 5),
(6,  'Onboarding',          '#22C55E', 800, 6),
(7,  'Reject',              '#EF4444', 650, 7),
(8,  'Hire',                '#10B981', 800, 8),
(9,  'Orientation',         '#0EA5E9', 800, 9),
(10, 'Orientation Survey',  '#14B8A6', 800, 10);

/* ========== New table: candidate_lifecycle_substatus ========== */
CREATE TABLE `candidate_lifecycle_substatus` (
  `substatus_id` int(11) NOT NULL AUTO_INCREMENT,
  `stage_id` int(11) NOT NULL,
  `substatus_name` varchar(128) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_terminal` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`substatus_id`),
  KEY `IDX_stage_id` (`stage_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* Stage 1: Capture */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(1, 'New Lead',               1, 0, 1),
(1, 'Sourced',                0, 0, 2),
(1, 'Referral Received',      0, 0, 3),
(1, 'Job Board Import',       0, 0, 4),
(1, 'Career Fair Lead',       0, 0, 5),
(1, 'Social Media Lead',      0, 0, 6),
(1, 'Website Visitor',        0, 0, 7);

/* Stage 2: Application */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(2, 'Application Received',   1, 0, 1),
(2, 'Application Reviewed',   0, 0, 2),
(2, 'Resume Screened',        0, 0, 3),
(2, 'Phone Screen Scheduled', 0, 0, 4),
(2, 'Phone Screen Completed', 0, 0, 5),
(2, 'Application Incomplete', 0, 0, 6),
(2, 'Awaiting References',    0, 0, 7);

/* Stage 3: Assessment */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(3, 'Assessment Sent',        1, 0, 1),
(3, 'Assessment In Progress', 0, 0, 2),
(3, 'Assessment Completed',   0, 0, 3),
(3, 'Skills Test Passed',     0, 0, 4),
(3, 'Skills Test Failed',     0, 1, 5),
(3, 'Background Check Sent',  0, 0, 6),
(3, 'Background Check Cleared', 0, 0, 7);

/* Stage 4: First Interview */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(4, 'Interview Scheduled',    1, 0, 1),
(4, 'Interview Confirmed',    0, 0, 2),
(4, 'Interview Completed',    0, 0, 3),
(4, 'Second Interview',       0, 0, 4),
(4, 'Panel Interview',        0, 0, 5),
(4, 'Technical Interview',    0, 0, 6),
(4, 'Interview Rescheduled',  0, 0, 7),
(4, 'Interview No Show',      0, 1, 8);

/* Stage 5: Offer */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(5, 'Offer Drafted',          1, 0, 1),
(5, 'Offer Extended',         0, 0, 2),
(5, 'Offer Negotiating',      0, 0, 3),
(5, 'Offer Accepted',         0, 0, 4),
(5, 'Offer Declined',         0, 1, 5),
(5, 'Offer Expired',          0, 1, 6),
(5, 'Counter Offer Made',     0, 0, 7);

/* Stage 6: Onboarding */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(6, 'Onboarding Started',     1, 0, 1),
(6, 'Paperwork Sent',         0, 0, 2),
(6, 'Paperwork Received',     0, 0, 3),
(6, 'I-9 Verification',       0, 0, 4),
(6, 'Drug Test Scheduled',    0, 0, 5),
(6, 'Drug Test Cleared',      0, 0, 6),
(6, 'Equipment Ordered',      0, 0, 7),
(6, 'Onboarding Complete',    0, 0, 8);

/* Stage 7: Reject */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(7, 'Not Qualified',          1, 1, 1),
(7, 'Position Filled',        0, 1, 2),
(7, 'Candidate Withdrew',     0, 1, 3),
(7, 'Failed Assessment',      0, 1, 4),
(7, 'Interview No Show',      0, 1, 5),
(7, 'Failed Background Check', 0, 1, 6),
(7, 'Salary Mismatch',        0, 1, 7),
(7, 'Culture Fit',            0, 1, 8),
(7, 'Ghosted',                0, 1, 9),
(7, 'Client Declined',        0, 1, 10);

/* Stage 8: Hire */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(8, 'Start Date Confirmed',   1, 0, 1),
(8, 'First Day Completed',    0, 0, 2),
(8, 'Probation Period',       0, 0, 3),
(8, 'Hire Confirmed',         0, 0, 4);

/* Stage 9: Orientation */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(9, 'Orientation Scheduled',   1, 0, 1),
(9, 'Orientation In Progress', 0, 0, 2),
(9, 'Orientation Completed',   0, 0, 3),
(9, 'Training Assigned',       0, 0, 4),
(9, 'Training Completed',      0, 0, 5);

/* Stage 10: Orientation Survey */
INSERT INTO `candidate_lifecycle_substatus` (`stage_id`, `substatus_name`, `is_default`, `is_terminal`, `sort_order`) VALUES
(10, 'Survey Sent',            1, 0, 1),
(10, 'Survey Completed',       0, 0, 2),
(10, 'Follow-up Required',     0, 0, 3),
(10, 'Survey Closed',          0, 1, 4);

/* ========== New table: candidate_lifecycle_history ========== */
CREATE TABLE `candidate_lifecycle_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_id` int(11) NOT NULL DEFAULT 1,
  `candidate_id` int(11) NOT NULL,
  `joborder_id` int(11) NOT NULL,
  `stage_from` int(11) DEFAULT NULL,
  `stage_to` int(11) NOT NULL,
  `substatus_from` int(11) DEFAULT NULL,
  `substatus_to` int(11) NOT NULL,
  `changed_by` int(11) NOT NULL DEFAULT 0,
  `change_source` varchar(32) NOT NULL DEFAULT 'manual',
  `reject_reason` varchar(128) DEFAULT NULL,
  `date_created` datetime NOT NULL,
  PRIMARY KEY (`history_id`),
  KEY `IDX_candidate_joborder` (`candidate_id`, `joborder_id`),
  KEY `IDX_site_date` (`site_id`, `date_created`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/* ========== Alter candidate_joborder table ========== */
ALTER TABLE `candidate_joborder`
  ADD COLUMN `lifecycle_stage_id` int(11) DEFAULT NULL AFTER `status`,
  ADD COLUMN `lifecycle_substatus_id` int(11) DEFAULT NULL AFTER `lifecycle_stage_id`,
  ADD COLUMN `reject_reason` varchar(128) DEFAULT NULL AFTER `lifecycle_substatus_id`,
  ADD KEY `IDX_lifecycle_stage` (`site_id`, `lifecycle_stage_id`);

/* ========== Back-fill existing rows ========== */
/* Map status=100 (No Contact) -> stage 1 (Capture) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 1
WHERE `status` = 100 AND `lifecycle_stage_id` IS NULL;

/* Map status=200 (Contacted) -> stage 2 (Application) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 2
WHERE `status` = 200 AND `lifecycle_stage_id` IS NULL;

/* Map status=250 (Candidate Replied) -> stage 2 (Application) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 2
WHERE `status` = 250 AND `lifecycle_stage_id` IS NULL;

/* Map status=300 (Qualifying) -> stage 3 (Assessment) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 3
WHERE `status` = 300 AND `lifecycle_stage_id` IS NULL;

/* Map status=400 (Submitted) -> stage 3 (Assessment) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 3
WHERE `status` = 400 AND `lifecycle_stage_id` IS NULL;

/* Map status=500 (Interviewing) -> stage 4 (First Interview) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 4
WHERE `status` = 500 AND `lifecycle_stage_id` IS NULL;

/* Map status=600 (Offered) -> stage 5 (Offer) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 5
WHERE `status` = 600 AND `lifecycle_stage_id` IS NULL;

/* Map status=650 (Not in Consideration) -> stage 7 (Reject) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 7
WHERE `status` = 650 AND `lifecycle_stage_id` IS NULL;

/* Map status=700 (Client Declined) -> stage 7 (Reject) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 7
WHERE `status` = 700 AND `lifecycle_stage_id` IS NULL;

/* Map status=800 (Placed) -> stage 8 (Hire) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 8
WHERE `status` = 800 AND `lifecycle_stage_id` IS NULL;

/* Map status=0 (No Status) -> stage 1 (Capture) */
UPDATE `candidate_joborder` SET `lifecycle_stage_id` = 1
WHERE `status` = 0 AND `lifecycle_stage_id` IS NULL;

/* Set default substatus for back-filled rows (first/default substatus for each stage) */
UPDATE `candidate_joborder` cj
INNER JOIN `candidate_lifecycle_substatus` cls
  ON cls.stage_id = cj.lifecycle_stage_id AND cls.is_default = 1
SET cj.lifecycle_substatus_id = cls.substatus_id
WHERE cj.lifecycle_substatus_id IS NULL AND cj.lifecycle_stage_id IS NOT NULL;
