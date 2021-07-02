CREATE TABLE `glpi_plugin_credit_entities` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`entities_id` INT(11) NOT NULL DEFAULT '0',
	`is_active` TINYINT(1) NOT NULL DEFAULT '0',
	`plugin_credit_types_id` TINYINT(1) NOT NULL DEFAULT '0',
	`begin_date` TIMESTAMP NULL DEFAULT NULL,
	`end_date` TIMESTAMP NULL DEFAULT NULL,
	`quantity` INT(11) NOT NULL DEFAULT '0',
	`overconsumption_allowed` TINYINT(1) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `name` (`name`) USING BTREE,
	INDEX `entities_id` (`entities_id`) USING BTREE,
	INDEX `is_active` (`is_active`) USING BTREE,
	INDEX `plugin_credit_types_id` (`plugin_credit_types_id`) USING BTREE,
	INDEX `begin_date` (`begin_date`) USING BTREE,
	INDEX `end_date` (`end_date`) USING BTREE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE `glpi_plugin_credit_tickets` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`tickets_id` INT(11) NOT NULL DEFAULT '0',
	`plugin_credit_entities_id` INT(11) NOT NULL DEFAULT '0',
	`date_creation` TIMESTAMP NULL DEFAULT NULL,
	`consumed` INT(11) NOT NULL DEFAULT '0',
	`users_id` INT(11) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`) USING BTREE,
	INDEX `tickets_id` (`tickets_id`) USING BTREE,
	INDEX `plugin_credit_entities_id` (`plugin_credit_entities_id`) USING BTREE,
	INDEX `date_creation` (`date_creation`) USING BTREE,
	INDEX `consumed` (`consumed`) USING BTREE,
	INDEX `users_id` (`users_id`) USING BTREE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;

CREATE TABLE `glpi_plugin_credit_types` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`entities_id` INT(11) NOT NULL DEFAULT '0',
	`is_recursive` TINYINT(1) NOT NULL DEFAULT '0',
	`name` VARCHAR(255) NOT NULL DEFAULT '' COLLATE 'utf8_unicode_ci',
	`comment` TEXT NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`completename` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`plugin_credit_types_id` INT(11) NOT NULL DEFAULT '0',
	`level` INT(11) NOT NULL DEFAULT '1',
	`sons_cache` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`ancestors_cache` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8_unicode_ci',
	`date_mod` TIMESTAMP NULL DEFAULT NULL,
	`date_creation` TIMESTAMP NULL DEFAULT NULL,
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `unicity` (`entities_id`, `plugin_credit_types_id`, `name`) USING BTREE,
	INDEX `plugin_credit_types_id` (`plugin_credit_types_id`) USING BTREE,
	INDEX `name` (`name`) USING BTREE,
	INDEX `is_recursive` (`is_recursive`) USING BTREE,
	INDEX `date_mod` (`date_mod`) USING BTREE,
	INDEX `date_creation` (`date_creation`) USING BTREE
)
COLLATE='utf8_unicode_ci'
ENGINE=InnoDB;