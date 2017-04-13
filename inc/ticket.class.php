<?php
/*
 -------------------------------------------------------------------------
 credit plugin for GLPI
 Copyright (C) 2017 by the credit Development Team.

 https://github.com/pluginsGLPI/credit
 -------------------------------------------------------------------------

 LICENSE

 This file is part of credit.

 credit is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 credit is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with credit. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginCreditTicket extends CommonDBTM {

   public static $rightname = 'ticket';

   static function getTypeName($nb=0) {
      return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Ticket' :
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
            } else {
               return self::getTypeName(2);
            }
            break;
         default :
            return self::getTypeName(2);
            break;
      }
      return '';
   }

   /**
    * @see CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Ticket' :
            self::showForTicket($item);
            break;
      }
      return true;
   }

   /**
    * @param $item    CommonDBTM object
   **/
   public static function countForItem(CommonDBTM $item) {
      return countElementsInTable(getTableForItemType(__CLASS__),
                                    "`tickets_id` = '".$item->getID()."'");
   }

   /**
    * Get all credit vouchers for a ticket.
    *
    * @param $ID           integer     tickets ID
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrive (0 for all) (default 0)
    * @param $sqlfilter    string      to add an SQL filter (default '')
    * @return array of vouchers
   **/
   static function getAllForTicket($ID, $start=0, $limit=0, $sqlfilter='') {
      global $DB;

      $query = "SELECT *
                FROM `" . getTableForItemType(__CLASS__) . "`
                WHERE `tickets_id` = '$ID'";
      if ($sqlfilter) {
         $query .= "AND ($sqlfilter) ";
      }
      $query .= "ORDER BY `id` DESC";

      if ($limit) {
         $query .= " LIMIT ".intval($start)."," . intval($limit);
      }

      $vouchers = array();
      foreach ($DB->request($query) as $data) {
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
   }


   /**
    * Get all tickets for an credit vouchers.
    *
    * @param $ID           integer     plugin_credit_entities_id ID
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrive (0 for all) (default 0)
    * @param $sqlfilter    string      to add an SQL filter (default '')
    * @return array of vouchers
   **/
   static function getAllForCreditEntity($ID, $start=0, $limit=0, $sqlfilter='') {
      global $DB;

      $query = "SELECT *
                FROM `" . getTableForItemType(__CLASS__) . "`
                WHERE `plugin_credit_entities_id` = '$ID'";
      if ($sqlfilter) {
         $query .= "AND ($sqlfilter) ";
      }
      $query .= " ORDER BY `id` DESC";

      if ($limit) {
         $query .= " LIMIT ".intval($start)."," . intval($limit);
      }

      $tickets = array();
      foreach ($DB->request($query) as $data) {
         $tickets[$data['id']] = $data;
      }

      return $tickets;
   }

   /**
    * Get consumed tickets for credit entity entry
    *
    * @param $ID integer PluginCreditEntity id
   **/
   static function getConsumedForCreditEntity($ID) {
      global $DB;

      $tot   = 0;
      $table = getTableForItemType(__CLASS__);

      $query = "SELECT SUM(`consumed`)
                FROM $table
                WHERE `plugin_credit_entities_id` = '".$ID."'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result, 0, 0);
         if (!is_null($sum)) {
            $tot += $sum;
         }
      }

      return $tot;
   }

   /**
    * Show credit vouchers consumed for a ticket
    *
    * @param $ticket Ticket object
   **/
   static function showForTicket(Ticket $ticket) {
      global $DB, $CFG_GLPI;

      $ID = $ticket->getField('id');
      if (!$ticket->can($ID, READ)) {
         return false;
      }

      $canedit = $ticket->canEdit($ID);
      if (in_array($ticket->fields['status'], Ticket::getSolvedStatusArray())
          || in_array($ticket->fields['status'], Ticket::getClosedStatusArray())) {
         $canedit = false;
      }

      $out = "";
      $out .= "<div class='spaced'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'><th colspan='2'>";
      $out .= __('Consumed credits for this ticket', 'credit');
      $out .= "</th></tr></table></div>";

      $number = self::countForItem($ticket);
      $rand   = mt_rand();

      if ($number) {
         $out .= "<div class='spaced'>";

         if ($canedit) {
            echo $out; $out = "";
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = array('num_displayed'
                         => $number,
                       'container'
                         => 'mass'.__CLASS__.$rand,
                       'rand' => $rand,
                       'specific_actions'
                         => array('update' => _x('button', 'Update'),
                                  'purge'  => _x('button', 'Delete permanently')));
            Html::showMassiveActions($massiveactionparams);
         }

         $out .= "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         if ($canedit) {
            $header_begin  .= "<th width='10'>";
            $header_top    .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_bottom .= Html::getCheckAllAsCheckbox('mass'.__CLASS__.$rand);
            $header_end    .= "</th>";
         }
         $header_end .= "<th>".__('Voucher name', 'credit')."</th>";
         $header_end .= "<th>".__('Voucher type', 'credit')."</th>";
         $header_end .= "<th>".__('Date consumed', 'credit')."</th>";
         $header_end .= "<th>".__('User consumed', 'credit')."</th>";
         $header_end .= "<th>".__('Quantity consumed', 'credit')."</th>";
         $header_end .= "</tr>\n";
         $out.= $header_begin.$header_top.$header_end;

         foreach (self::getAllForTicket($ID) as $data) {

            $out .= "<tr class='tab_bg_2'>";
            if ($canedit) {
               $out .= "<td width='10'>";
               echo $out; $out = "";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               $out .= "</td>";
            }

            $PluginCreditEntity = new PluginCreditEntity();
            $PluginCreditEntity->getFromDB($data['plugin_credit_entities_id']);

            $out .= "<td width='40%' class='center'>";
            $out .= $PluginCreditEntity->getName();
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= Dropdown::getDropdownName(getTableForItemType('PluginCreditType'),
                                 $PluginCreditEntity->getField('plugin_credit_types_id'));
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= Html::convDate($data["date_creation"]);
            $out .= "</td>";

            $showuserlink = 0;
            if (Session::haveRight('user', READ)) {
               $showuserlink = 1;
            }

            $out .= "<td class='center'>";
            $out .= getUserName($data["users_id"], $showuserlink);
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= $data['consumed'];
            $out .= "</td></tr>";
         }

         $out .= $header_begin.$header_bottom.$header_end;
         $out .= "</table>\n";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            echo $out; $out = "";
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }

      } else {
         $out .= "<p class='center b'>".__('No credit was recorded', 'credit')."</p>";
      }
      $out .= "</div>\n";

      $out .= "<div class='spaced'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'><th colspan='2'>";
      $out .= __('Active credit vouchers for ticket entity', 'credit');
      $out .= "</th></tr></table>";
      $out .= "</div>";
      echo $out;

      $Entity = new Entity();
      $Entity->getFromDB($ticket->fields['entities_id']);
      PluginCreditEntity::showForItemtype($Entity, 'Ticket');
   }

   /**
    * Display contents at the end of solution form.
    *
    * @param array $params Array with "item" and "options" keys
    *
    * @return void
    */
   static public function postSolutionForm($params) {
      global $CFG_GLPI;

      $item    = $params['item'];
      $options = $params['options'];

      $showForm  = false;
      $callers   = debug_backtrace();
      foreach ($callers as $call) {
         if ($call['function']=='showSolutionForm') {
            $showForm = true;
            break;
         }
      }

      if ($showForm) {
         self::showMinimalForm($item);
      }
   }

   /**
    * Show the minimal form for declare credit.
    *
    * @param $ticket Ticket
   **/
   static function showMinimalForm(Ticket $ticket) {

      $out = "";

      $canedit = $ticket->canEdit($ticket->getID());
      if (in_array($ticket->fields['status'], Ticket::getSolvedStatusArray())
          || in_array($ticket->fields['status'], Ticket::getClosedStatusArray())) {
         $canedit = false;
      }

      if ($canedit) {
         $out .= "<tr><th colspan='2'>";
         $out .= self::getTypeName(2);
         $out .= "</th><th colspan='2'></th></tr>";
         $out .= "<tr><td>";
         $out .= "<label for='plugin_credit_consumed_voucher'>";
         $out .= __('Save and consumed a voucher ?', 'credit');
         $out .= "</label>";
         $out .= "</td><td>";
         echo $out; $out = "";
         Dropdown::showYesNo('plugin_credit_consumed_voucher');
         $out .= "</td><td colspan='2'></td>";
         $out .= "</tr><tr><td>";
         $out .= "<label for='voucher'>";
         $out .= __('Voucher name', 'credit');
         $out .= "</label>";
         $out .= "</td><td>";
         echo $out; $out = "";
         PluginCreditEntity::dropdown(['name'      => 'plugin_credit_entities_id',
                                       'entity'    => $ticket->getEntityID(),
                                       'condition' => "`is_active`='1'"]);
         $out .= "</td><td colspan='2'></td>";
         $out .= "</tr><tr><td>";
         $out .= "<label for='plugin_credit_quantity'>";
         $out .= __('Quantity consumed', 'credit');
         $out .= "</label>";
         $out .= "</td><td>";
         echo $out; $out = "";
         Dropdown::showNumber("plugin_credit_quantity", ['value' => '',
                                                               'min'   => 1,
                                                               'max'   => 200,
                                                               'step'  => 1]);
         $out .= "</td><td colspan='2'></td></tr>";
      }

      echo $out;
   }

   /**
    * Display the detailled list of tickets on which consumption is declared.
    *
    * @param $ID plugin_credit_entities_id
   **/
   static function displayConsumed($ID) {

      $out = "";
      $out .= "<div class='spaced'>";
      $out .= "<table class='tab_cadre_fixe'>";
      $out .= "<tr class='tab_bg_1'><th colspan='2'>";
      $out .= __('Detail of tickets on which consumption is declared', 'credit');
      $out .= "</th></tr></table>";
      $out .= "</div>";

      if (self::getConsumedForCreditEntity($ID) == 0) {
         $out .= "<p class='center b'>";
         $out .= __('No credit was recorded', 'credit');
         $out .= "</p>";
      } else {
         $out .= "<table class='tab_cadre_fixehov'>";
         $header_begin  = "<tr>";
         $header_top    = '';
         $header_bottom = '';
         $header_end    = '';
         $header_end .= "<th>".__('Title')."</th>";
         $header_end .= "<th>".__('Status')."</th>";
         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Ticket category')."</th>";
         $header_end .= "<th>".__('Date consumed', 'credit')."</th>";
         $header_end .= "<th>".__('User consumed', 'credit')."</th>";
         $header_end .= "<th>".__('Quantity consumed', 'credit')."</th>";
         $header_end .= "</tr>\n";
         $out .= $header_begin.$header_top.$header_end;

         foreach (self::getAllForCreditEntity($ID) as $data) {

            $Ticket = new Ticket();
            $Ticket->getFromDB($data['tickets_id']);

            $out .= "<tr class='tab_bg_2'>";
            $out .= "<td class='center'>";
            $out .= $Ticket->getLink(['linkoption' => 'target="_blank"']);
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= Ticket::getStatus($Ticket->fields['status']);
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= Ticket::getTicketTypeName($Ticket->fields['type']);
            $out .= "</td>";

            $itilcat = new ITILCategory();
            if ($itilcat->getFromDB($Ticket->fields['itilcategories_id'])) {
               $out .= "<td class='center'>";
               $out .= $itilcat->getName(['comments' => true]);
               $out .= "</td>";
            } else {
               $out .= "<td class='center'>";
               $out .= __('None');
               $out .= "</td>";
            }

            $out .= "<td class='center'>";
            $out .= Html::convDate($data["date_creation"]);
            $out .= "</td>";

            $showuserlink = 0;
            if (Session::haveRight('user', READ)) {
               $showuserlink = 1;
            }

            $out .= "<td class='center'>";
            $out .= getUserName($data["users_id"], $showuserlink);
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= $data['consumed'];
            $out .= "</td></tr>";
         }

         $out .= $header_begin.$header_bottom.$header_end;
         $out .= "</table>\n";
      }

      echo $out;
   }

   /**
    * Test if consumed voucher is selected and add them.
    *
    * @param  Ticket $ticket ticket object
    *
    * @return boolean
    */
   static function beforeUpdate(Ticket $ticket) {

      if (!is_array($ticket->input) || !count($ticket->input)) {
         return false;
      }

      if (!is_numeric(Session::getLoginUserID(false))
          || !Session::haveRightsOr('ticket', array(Ticket::STEAL, Ticket::OWN))) {
         return false;
      }

      if (isset($ticket->input['plugin_credit_consumed_voucher'])
          && ($ticket->input['plugin_credit_consumed_voucher'] == 1)) {

         if ($ticket->input['plugin_credit_entities_id']==0) {
            unset($ticket->input['status']);
            unset($ticket->input['solution']);
            unset($ticket->input['solutiontypes_id']);
            Session::addMessageAfterRedirect(__('You must provide an credit voucher',
                                    'credit'), true, ERROR);
         } else {
            $PluginCreditTicket = new self();
            $input = ['tickets_id'                      => $ticket->getID(),
                      'plugin_credit_entities_id' => $ticket->input['plugin_credit_entities_id'],
                      'consumed'                        => $ticket->input['plugin_credit_quantity'],
                      'users_id'                        => Session::getLoginUserID()];
            if ($PluginCreditTicket->add($input)) {
               Session::addMessageAfterRedirect(__('Credit voucher successfully added.',
                                       'credit'), true, INFO);
            }
         }
      }
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                = parent::getSearchOptions();

      $tab[881]['table']    = $this->getTable();
      $tab[881]['field']    = 'date_creation';
      $tab[881]['name']     = __('Date consumed', 'credit');
      $tab[881]['datatype'] = 'date';

      $tab[882]['table']    = $this->getTable();
      $tab[882]['field']    = 'consumed';
      $tab[882]['name']     = __('Quantity consumed', 'credit');
      $tab[882]['datatype'] = 'number';
      $tab[882]['min']      = 1;
      $tab[882]['max']      = 200;
      $tab[882]['step']     = 1;
      $tab[882]['toadd']    = array(0 => __('Unlimited'));

      $tab[883]['table']    = getTableForItemType('PluginCreditEntity');
      $tab[883]['field']    = 'name';
      $tab[883]['name']     = __('Credit vouchers', 'credit');
      $tab[883]['datatype'] = 'dropdown';

      return $tab;
   }

   /**
    * Install all necessary table for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) {
      global $DB;

      $table = getTableForItemType(__CLASS__);

      if (!TableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `tickets_id` int(11) NOT NULL DEFAULT '0',
                     `plugin_credit_entities_id` tinyint(1) NOT NULL DEFAULT '0',
                     `date_creation` datetime DEFAULT NULL,
                     `consumed` int(11) NOT NULL DEFAULT '0',
                     `users_id` tinyint(1) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `plugin_credit_entities_id` (`plugin_credit_entities_id`),
                     KEY `date_creation` (`date_creation`),
                     KEY `consumed` (`consumed`),
                     KEY `users_id` (`users_id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      } else {

         // Fix #1 in 1.0.1 : change tinyint(1) to int(11) for tickets_id
         $migration->changeField($table, 'tickets_id', 'tickets_id', 'integer');

         //execute the whole migration
         $migration->executeMigration();
      }
   }

   /**
    * Uninstall previously installed table of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall(Migration $migration) {

      $table = getTableForItemType(__CLASS__);

      $migration->displayMessage("Uninstalling $table");

      $migration->dropTable($table);
   }

}
