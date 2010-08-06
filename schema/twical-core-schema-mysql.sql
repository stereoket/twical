

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `event`
-- ----------------------------
DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `id` bigint(20) NOT NULL auto_increment,
  `twitter_username` varchar(70) collate utf8_unicode_ci NOT NULL,
  `summary` varchar(150) collate utf8_unicode_ci default NULL,
  `description` text collate utf8_unicode_ci,
  `location` varchar(200) collate utf8_unicode_ci default NULL,
  `latitude` float(18,6) default NULL,
  `longitude` float(18,6) default NULL,
  `url` varchar(255) collate utf8_unicode_ci default NULL,
  `start_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_date` date NOT NULL,
  `end_time` time NOT NULL,
  `person_id` bigint(20) NOT NULL default '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY  (`id`,`person_id`),
  KEY `event_person_id_person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
--  Table structure for `person`
-- ----------------------------
DROP TABLE IF EXISTS `person`;
CREATE TABLE `person` (
  `id` int(11) NOT NULL auto_increment,
  `is_muted` tinyint(1) default '0',
  `twitter_token` varchar(255) character set latin1 default NULL,
  `twitter_secret` varchar(255) character set latin1 default NULL,
  `twitter_userid` bigint(20) default NULL,
  `account_name` varchar(20) character set latin1 default NULL,
  `calendar_url` text character set latin1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

