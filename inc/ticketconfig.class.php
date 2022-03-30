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
            $voucher_id = $ticket_config->fields['credit_default_followup'] ?? null;
            break;

         case TicketTask::getType():
            $voucher_id = $ticket_config->fields['credit_default_task'] ?? null;
            break;

         case ITILSolution::getType():
            $voucher_id = $ticket_config->fields['credit_default_solution'] ?? null;
            break;
      }

      return $voucher_id ?: null;
   }


   /**
    * Show default credit option ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket, $isTicket = false) {

      if (!Session::haveRight("entity", UPDATE)) {
         return true;
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
            'on_change'   => 'propageSelected(this)',
         ]
      );
      $out .= "</td>";
      $out .= "<td>".__('Default for followups', 'credit')."</td>";
      $out .= "<td>";
      $out .= PluginCreditEntity::dropdown(
         [
            'name'        => 'credit_default_followup',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['credit_default_followup'] ?? 0,
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
            'name'        => 'credit_default_task',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['credit_default_task'] ?? 0,
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
            'name'        => 'credit_default_solution',
            'entity'      => $ticket->getEntityID(),
            'entity_sons' => true,
            'display'     => false,
            'value'       => $ticket_config->fields['credit_default_solution'] ?? 0,
            'condition'   => ['is_active' => 1],
            'comments'    => false,
            'rand'        => $rand,
         ]
      );
      $out .= "</td>";
      $out .= "</tr>";
      $out .= "<tr class='tab_bg_1'>";
      $out .= "</tbody></table>";
      echo $out;
   }

   static function updateConfig(Ticket $ticket) {
      if (!Session::haveRight("entity", UPDATE)) {
         return;
      }

      $input = [];

      $config_fields = [
         'credit_default_followup',
         'credit_default_task',
         'credit_default_solution',
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
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `tickets_id` int(11) NOT NULL DEFAULT '0',
                     `credit_default` tinyint(1) NOT NULL DEFAULT '0',
                     `credit_default_followup` tinyint(1) NOT NULL DEFAULT '0',
                     `credit_default_solution` tinyint(1) NOT NULL DEFAULT '0',
                     `credit_default_task` tinyint(1) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      }

      $migration->addKey($table, 'tickets_id');
   }

   static function uninstall(Migration $migration) {

      $table = self::getTable();
      $migration->displayMessage("Uninstalling $table");
      $migration->dropTable($table);
   }
}
