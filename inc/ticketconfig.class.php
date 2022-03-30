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

class PluginCreditTicketConfig extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb = 0) {
      return _n('Default voucher option', 'Default voucher options', $nb, 'credit');
   }

   /**
    * Get default credit for ticket and itemtype
    *
    * @param int     $ticket_id
    * @param string  $itemtype
    *
    * @return null|int
    */
   static function getDefaultForTicket($ticket_id, $itemtype) {
      $ticket_config = new self();
      $ticket_config->getFromDBByCrit(['tickets_id' => $ticket_id]);

      $voucher_id = null;
      switch ($itemtype) {
         case ITILFollowup::getType():
            $voucher_id = $ticket_config->fields['plugin_credit_entities_id_followups'] ?? null;
            break;

         case TicketTask::getType():
            $voucher_id = $ticket_config->fields['plugin_credit_entities_id_tasks'] ?? null;
            break;

         case ITILSolution::getType():
            $voucher_id = $ticket_config->fields['plugin_credit_entities_id_solutions'] ?? null;
            break;
      }

      return $voucher_id ?: null;
   }


   /**
    * Show default credit option ticket
    *
    * @param Ticket $ticket
   **/
   static function showForTicket(Ticket $ticket) {

      if (!Session::haveRight("entity", UPDATE)) {
         return;
      }

      //load ticket configuration
      $ticket_config = new PluginCreditTicketConfig();
      if (!$ticket->isNewItem()) {
         $ticket_config->getFromDBByCrit(["tickets_id" => $ticket->getID()]);
      }

      $rand = mt_rand();
      $out = "";
      $out .= "<table id='creditmainform' class='tab_cadre_fixe'><tbody>";
      $out .= "<tr>";
      $out .= "<th style='width:13%'>".__('Credit', 'credit')."</th>";

      $out .= "<td>".__('Default for ticket', 'credit')."</td>";
      $out .= "<td>";
      $out .= PluginCreditEntity::dropdown(
         [
            'name'        => 'credit_default',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['credit_default'] ?? 0,
            'condition'   => ['is_active' => 1],
            'comments'    => false,
            'rand'        => $rand,
            'on_change'   => 'PluginCredit.propagateDefaultVoucherValue(this)',
         ]
      );
      $out .= "</td>";
      $out .= "<td>".__('Default for followups', 'credit')."</td>";
      $out .= "<td>";
      $out .= PluginCreditEntity::dropdown(
         [
            'name'        => 'plugin_credit_entities_id_followups',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['plugin_credit_entities_id_followups'] ?? 0,
            'condition'   => ['is_active' => 1],
            'comments'    => false,
            'rand'        => $rand,
         ]
      );
      $out .= "</td>";
      $out .= "<td>".__('Default for tasks', 'credit')."</td>";
      $out .= "<td>";
      $out .= PluginCreditEntity::dropdown(
         [
            'name'        => 'plugin_credit_entities_id_tasks',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['plugin_credit_entities_id_tasks'] ?? 0,
            'condition'   => ['is_active' => 1],
            'comments'    => false,
            'rand'        => $rand,
         ]
      );
      $out .= "</td>";
      $out .= "<td>".__('Default for solutions', 'credit')."</td>";
      $out .= "<td>";
      $out .= PluginCreditEntity::dropdown(
         [
            'name'        => 'plugin_credit_entities_id_solutions',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['plugin_credit_entities_id_solutions'] ?? 0,
            'condition'   => ['is_active' => 1],
            'comments'    => false,
            'rand'        => $rand,
         ]
      );
      $out .= "</td>";
      $out .= "</tr>";
      $out .= "<tr class='tab_bg_1'>";
      $out .= "</tbody></table>";

      return $out;
   }

   static function updateConfig(Ticket $ticket) {
      if (!Session::haveRight("entity", UPDATE)) {
         return;
      }

      $input = [];

      $config_fields = [
         'plugin_credit_entities_id_followups',
         'plugin_credit_entities_id_tasks',
         'plugin_credit_entities_id_solutions',
      ];
      foreach ($config_fields as $field) {
         if (array_key_exists($field, $ticket->input)) {
            $input[$field] = $ticket->input[$field];
         }
      }

      if (empty($input)) {
         return;
      }
      $input['tickets_id'] = $ticket->getID();

      $ticket_config = new self();
      if ($ticket_config->getFromDBByCrit(['tickets_id' => $ticket->getID()])) {
         $ticket_config->update(['id' => $ticket_config->getID()] + $input);
      } else {
         $ticket_config->add($input);
      }
   }

   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int NOT NULL auto_increment,
                     `tickets_id` int NOT NULL DEFAULT '0',
                     `credit_default` tinyint NOT NULL DEFAULT '0',
                     `plugin_credit_entities_id_followups` tinyint NOT NULL DEFAULT '0',
                     `plugin_credit_entities_id_tasks` tinyint NOT NULL DEFAULT '0',
                     `plugin_credit_entities_id_solutions` tinyint NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `plugin_credit_entities_id_followups` (`plugin_credit_entities_id_followups`),
                     KEY `plugin_credit_entities_id_tasks` (`plugin_credit_entities_id_tasks`),
                     KEY `plugin_credit_entities_id_solutions` (`plugin_credit_entities_id_solutions`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      // During 1.10.0 dev phase, fields were named differently and had no keys
      $migration->changeField($table, 'credit_default_followup', 'plugin_credit_entities_id_followups', 'bool');
      $migration->changeField($table, 'credit_default_task', 'plugin_credit_entities_id_tasks', 'bool');
      $migration->changeField($table, 'credit_default_solution', 'plugin_credit_entities_id_solutions', 'bool');
      $migration->migrationOneTable($table);
      $migration->addKey($table, 'plugin_credit_entities_id_followups');
      $migration->addKey($table, 'plugin_credit_entities_id_tasks');
      $migration->addKey($table, 'plugin_credit_entities_id_solutions');

      $migration->addKey($table, 'tickets_id');
   }

   static function uninstall(Migration $migration) {

      $table = self::getTable();
      $migration->dropTable($table);
   }
}
