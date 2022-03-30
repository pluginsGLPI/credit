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
 * @copyright Copyright (C) 2017-2022 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginCreditEntityConfig extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb = 0) {
      return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
   }

   static function showEntityConfigForm(Entity $entity, $itemtype = 'Entity') {

      $ID = $entity->getField('id');

      //get configuration values
      $config = new self();
      $config->getFromDBByCrit(['entities_id' => $ID]);

      $out = "";
      $rand = mt_rand();
      $out .= "<div class='firstbloc'>";
      $out .= "<form name='creditentityconfig_form$rand' id='creditentityconfig_form$rand' method='post' action='";
      $out .= self::getFormUrl()."'>";
      $out .= "<input type='hidden' name='entities_id' value='$ID'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'>";
      $out .= "<th colspan='8'>" . __('Default options for entity', 'credit') . "</th>";
      $out .= "</tr>";

      $out .= "<td>".__('By default consume a voucher for followups', 'credit')."</td>";
      $out .= "<td>";
      $out .= Dropdown::showYesNo("consume_voucher_for_followups", $config->fields['consume_voucher_for_followups'] ?? 0, -1, ['display' => false]);
      $out .= "</td>";

      $out .= "<td>".__('By default consume a voucher for tasks', 'credit')."</td>";
      $out .= "<td>";
      $out .= Dropdown::showYesNo("consume_voucher_for_tasks", $config->fields['consume_voucher_for_tasks'] ?? 0, -1, ['display' => false]);
      $out .= "</td>";

      $out .= "<td>".__('By default consume a voucher for solutions', 'credit')."</td>";
      $out .= "<td>";
      $out .= Dropdown::showYesNo("consume_voucher_for_solutions", $config->fields['consume_voucher_for_solutions'] ?? 0, -1, ['display' => false]);
      $out .= "</td>";

      $out .= "</table>";
      if ($config->isNewItem()) {
         $out .= "<input type='submit' name='add' value='"._sx('button', 'Update')."' class='submit'>";
      } else {
         $out .= "<input type='hidden' name='id' value='{$config->getID()}'>";
         $out .= "<input type='submit' name='update' value='"._sx('button', 'Update')."' class='submit'>";
      }
      $out .= Html::closeForm(false);
      $out .= "</div>";
      echo $out;
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `consume_voucher_for_followups` tinyint(1) NOT NULL DEFAULT '0',
                     `consume_voucher_for_tasks` tinyint(1) NOT NULL DEFAULT '0',
                     `consume_voucher_for_solutions` tinyint(1) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `entities_id` (`entities_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      // During 1.10.0 dev phase, entity config were stored in GLPI config table
      $configs = Config::getConfigurationValues('plugin:credit');
      foreach ($configs as $key => $config) {
         $entity_match = [];
         if (preg_match('/^Entity-(?<id>\d+)/', $key, $entity_match) !== 1) {
            continue;
         }
         $entity_id = $entity_match['id'];
         $values = json_decode($config, true);
         $entity_config = new self();
         $entity_config->add(
            [
               'entities_id'                  => $entity_id,
               'consume_voucher_for_followups' => $values['consume_voucher_followups'],
               'consume_voucher_for_tasks'     => $values['consume_voucher_tasks'],
               'consume_voucher_for_solutions' => $values['consume_voucher_solution'],
            ]
         );
         Config::deleteConfigurationValues('plugin:credit', [$key]);
      }
   }

   static function uninstall(Migration $migration) {

      $table = self::getTable();
      $migration->displayMessage("Uninstalling $table");
      $migration->dropTable($table);
   }
}
