<?php

/**
 * -------------------------------------------------------------------------
 * Credit plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Credit.
 *
 * Credit is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Credit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Credit. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @author    François Legastelois
 * @copyright Copyright (C) 2017-2023 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginCreditType extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static function getTypeName($nb = 0) {
      return _n('Credit voucher type', 'Credit vouchers types', $nb, 'credit');
   }

   /**
    * Install all necessary tables for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) {
      global $DB;

      $default_charset = DBConnection::getDefaultCharset();
      $default_collation = DBConnection::getDefaultCollation();
      $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int {$default_key_sign} NOT NULL auto_increment,
                     `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                     `is_recursive` tinyint NOT NULL DEFAULT '0',
                     `name` varchar(255) NOT NULL DEFAULT '',
                     `comment` text,
                     `completename` VARCHAR(255) NULL DEFAULT NULL,
                     `plugin_credit_types_id` INT {$default_key_sign} NOT NULL DEFAULT '0',
                     `level` INT NOT NULL DEFAULT '1',
                     `sons_cache` LONGTEXT NULL,
                     `ancestors_cache` LONGTEXT NULL,
                     `date_mod` timestamp NULL DEFAULT NULL,
                     `date_creation` timestamp NULL DEFAULT NULL,
                     PRIMARY KEY (`id`),
                     UNIQUE KEY `unicity` (`entities_id`,`plugin_credit_types_id`,`name`),
                     KEY `plugin_credit_types_id` (`plugin_credit_types_id`),
                     KEY `name` (`name`),
                     KEY `is_recursive` (`is_recursive`),
                     KEY `date_mod` (`date_mod`),
                     KEY `date_creation` (`date_creation`)
                  ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
         $DB->query($query) or die($DB->error());
      }
   }

   /**
    * Uninstall previously installed table of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall(Migration $migration) {

      $table = self::getTable();
      $migration->dropTable($table);
   }

}
