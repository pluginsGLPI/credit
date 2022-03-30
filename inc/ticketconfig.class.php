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

   static function createTicketOption(Ticket $ticket) {
      $ticketConfig = new PluginCreditTicketConfig();
      $data = [
         "tickets_id"               => $ticket->getID(),
         "credit_default_followup"  => 0,
         "credit_default_task"      => 0,
         "credit_default_solution"  => 0,
      ];
      $ticketConfig->add($data);
      return $ticketConfig;
   }

   public function prepareInputForAdd($input) {
      $input['credit_default'] = $this->computeDefault($input);

      return $input;
   }

   public function prepareInputForUpdate($input) {
      $input['credit_default'] = $this->computeDefault($input);

      return $input;
   }

   public function computeDefault(array $input) {
      $default_for_followups = $input['credit_default_followup'] ?? $this->fields['credit_default_followup'] ?? 0;
      $default_for_tasks     = $input['credit_default_task'] ?? $this->fields['credit_default_task'] ?? 0;
      $default_for_solutions = $input['credit_default'] ?? $this->fields['credit_default'] ?? 0;

      $default = null;
      if ($default_for_followups === $default_for_tasks && $default_for_tasks === $default_for_solutions) {
         $default = $default_for_followups;
      } else {
         $default = 0;
      }
      return $default;
   }

   /**
    * Get default credit for entity and itemtype
    *
    * @param $ID           integer     entities ID
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrieve (0 for all) (default 0)
    * @param $sqlfilter    string      to add a SQL filter (default '')
    * @return int
   **/
   static function getDefaultForTicket($ID, $itemtype) {
      $ticketConfig = new PluginCreditTicketConfig();
      if ($ticketConfig->getFromDBByCrit(["tickets_id" => $ID])) {
         switch ($itemtype) {
            case ITILFollowup::getType():
               return $ticketConfig->fields['credit_default_followup'];
               break;

            case TicketTask::getType():
               return $ticketConfig->fields['credit_default_task'];
               break;

            case ITILSolution::getType():
               return $ticketConfig->fields['credit_default_solution'];
               break;
         }
      }
      return 0;
   }


   /**
    * Show default credit option ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicketTab(Ticket $ticket, $isTicket = false) {

      if (!Session::haveRight("entity", UPDATE)) {
         return true;
      }

      $canedit = $ticket->canEdit($ticket->getID());
      if (in_array($ticket->fields['status'], Ticket::getSolvedStatusArray())
          || in_array($ticket->fields['status'], Ticket::getClosedStatusArray())) {
         $canedit = false;
      }

      //load ticket configuration
      $ticketconfig = new PluginCreditTicketConfig();
      if (!$ticketconfig->getFromDBByCrit(["tickets_id" => $ticket->getID()])) {
         if (!$isTicket) {
            $ticketconfig = PluginCreditTicketConfig::createTicketOption($ticket);
         } else {
            $ticketconfig->getEmpty();
         }
      }

      $credit = new PluginCreditEntity();
      $data = $credit->find([
         "entities_id" => $ticket->getEntityID(),
         "is_active"   => true
      ]);

      $values = [];
      foreach ($data as $key => $value) {
         $values[$key] = $value['name'];
      }

      if (!$isTicket) {
         $ticketconfig->showFormHeader(["colspan" => 4]);
      }
      $rand = mt_rand();
      $out = "";
      if ($isTicket) {
         $out .= "<table id='creditmainform' class='tab_cadre_fixe'><tbody>";
         $out .= "<tr>";
         $out .= "<th style='width:13%'>".__('Credit', 'credit')."</th>";
      } else {
         $out .= "<tr>";
      }

      $out .= "<td>".__('Default for ticket', 'credit')."</td>";
      $out .= "<td>";

      $out .= PluginCreditEntity::dropdown(['name'      => 'credit_default',
                                             'entity'    => $ticket->getEntityID(),
                                             'display'   => false,
                                             'value'     => $ticketconfig->fields['credit_default'],
                                             'condition' => ['is_active' => 1],
                                             'rand'      => $rand,
                                             'on_change' => 'propageSelected(this)']);
      $out .= "</td>";
      $out .= "<td >".__('Default for followups', 'credit')."</td>";

      $out .= "<td>";
      $out .= Dropdown::showFromArray("credit_default_followup", $values, ["display" => false,
                                                                           'display_emptychoice' => true,
                                                                           'rand'      => $rand,
                                                                           'value'     => $ticketconfig->fields['credit_default_followup'],
                                                                           ]);
      $out .= "</td>";

      $out .= "<td >".__('Default for tasks', 'credit')."</td>";
      $out .= "<td>";
      $out .= Dropdown::showFromArray("credit_default_task", $values, ["display" => false,
                                                                     'display_emptychoice' => true,
                                                                     'rand'      => $rand,
                                                                     'value'     => $ticketconfig->fields['credit_default_task'],
                                                                     ]);
      $out .= "</td>";

      $out .= "<td >".__('Default for solutions', 'credit')."</td>";
      $out .= "<td>";
      $out .= Dropdown::showFromArray("credit_default_solution", $values, ["display" => false,
                                                                        'display_emptychoice' => true,
                                                                        'rand'      => $rand,
                                                                        'value'     => $ticketconfig->fields['credit_default_solution'],
                                                                        ]);

      $out .= "</td>";
      $out .= "</tr>";
      $out .= "<tr class='tab_bg_1'>";

      if (!$isTicket) {
         echo $out;
         $ticketconfig->showFormButtons(['candel'=>false,
                                          'canedit' => $canedit,
                                          'colspan' => 4]);
      } else {
         if (!$ticketconfig->isNewItem()) {
            $out .= Html::hidden("plugin_ticket_config_id", ['value' => $ticketconfig->getID()] );
         }
         $out .= "</tbody></table>";
         echo $out;
      }

   }

   static function showForTicket(Ticket $ticket) {
      self::showForTicketTab($ticket, true);
   }

   static function manageTicket(CommonDBTM $item) {
      $ticketConfig = new PluginCreditTicketConfig();
      if (isset($item->input['plugin_ticket_config_id'])) {
         $data = [
            "tickets_id" => $item->fields['id'],
            "id" => $item->input['plugin_ticket_config_id'],
         ];

         if (isset($item->input['credit_default'])) {
            $data['credit_default'] = $item->input['credit_default'];
         }

         if (isset($item->input['credit_default_followup'])) {
            $data['credit_default_followup'] = $item->input['credit_default_followup'];
         }

         if (isset($item->input['credit_default_task'])) {
            $data['credit_default_task'] = $item->input['credit_default_task'];
         }

         if (isset($item->input['credit_default_solution'])) {
            $data['credit_default_solution'] = $item->input['credit_default_solution'];
         }
         $ticketConfig->update($data);
      } else {

         $data = [
            "tickets_id" => $item->fields['id'],
         ];

         if (isset($item->input['credit_default'])) {
            $data['credit_default'] = $item->input['credit_default'];
         } else {
            $data['credit_default'] = 0;
         }

         if (isset($item->input['credit_default_followup'])) {
            $data['credit_default_followup'] = $item->input['credit_default_followup'];
         } else {
            $data['credit_default_followup'] = 0;
         }

         if (isset($item->input['credit_default_task'])) {
            $data['credit_default_task'] = $item->input['credit_default_task'];
         } else {
            $data['credit_default_task'] = 0;
         }

         if (isset($item->input['credit_default_solution'])) {
            $data['credit_default_solution'] = $item->input['credit_default_solution'];
         } else {
            $data['credit_default_solution'] = 0;
         }

         if ($ticketConfig->getFromDBByCrit([
            "tickets_id" => $item->fields['id'],
         ])) {
            $data['id'] = $ticketConfig->getID();
            $ticketConfig->update($data);
         } else {
            $ticketConfig->add($data);
         }
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
