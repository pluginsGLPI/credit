<?php
/*
 -------------------------------------------------------------------------
 intervention plugin for GLPI
 Copyright (C) 2017 by the intervention Development Team.

 https://github.com/pluginsGLPI/intervention
 -------------------------------------------------------------------------

 LICENSE

 This file is part of intervention.

 intervention is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 intervention is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with intervention. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginInterventionTicket extends CommonDBTM {

   public static $rightname = 'ticket';

   static function getTypeName($nb=0) {
      return _n('Intervention voucher', 'Intervention vouchers', $nb, 'intervention');
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
    * Get all intervention vouchers for a ticket.
    *
    * @param $ID           integer     tickets ID
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrive (0 for all) (default 0)
    * @param $sqlfilter    string      to add an SQL filter (default '')
    * @return array of vouchers
   **/
   static function getAllForTicket($ID, $start=0, $limit=0, $sqlfilter='') {
      global $DB;

      $showuserlink = 0;
      if (Session::haveRight('user', READ)) {
         $showuserlink = 1;
      }

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
         $PluginInterventionEntity = new PluginInterventionEntity();
         $PluginInterventionEntity->getFromDB($data['plugin_intervention_entities_id']);

         $tmp = array();
         $tmp['id']        = $data['id'];
         $tmp['voucher']   = $PluginInterventionEntity->getName();
         $tmp['type']      =  Dropdown::getDropdownName(getTableForItemType('PluginInterventionType'),
                                 $PluginInterventionEntity->getField('plugin_intervention_types_id'));
         $tmp['date']      = Html::convDate($data["date_creation"]);
         $tmp['consumed']  = $data['consumed'];
         $tmp['users']     = getUserName($data["users_id"], $showuserlink);

         $vouchers[$tmp['id']] = $tmp;
      }

      return $vouchers;
   }

   /**
    * Show intervention vouchers consumed for a ticket
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
      $number  = self::countForItem($ticket);

      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }

      $rand = mt_rand();

      echo "<div class='spaced'>";

      Session::initNavigateListItems('PluginInterventionEntity',
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                     sprintf(__('%1$s = %2$s'),
                                             Ticket::getTypeName(1), $ticket->getName()));

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>"
            . __('Consumed interventions for this ticket', 'intervention');
      echo "</th></tr></table></div>";

      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager('', $start, $number);

         if ($canedit) {
            $rand = mt_rand();
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = array('num_displayed'
                         => $number,
                       'container'
                         => 'mass'.__CLASS__.$rand,
                       'specific_actions'
                         => array('purge' => _x('button', 'Delete permanently')));

            Html::showMassiveActions($massiveactionparams);
         }

         echo "<table class='tab_cadre_fixehov'>";
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
         $header_end .= "<th>".__('Name')."</th>";
         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Date')."</th>";
         $header_end .= "<th>".__('User')."</th>";
         $header_end .= "<th>".__('Quantity consumed', 'intervention')."</th>";
         $header_end .= "</tr>\n";
         echo $header_begin.$header_top.$header_end;

         $i = 0;
         foreach (self::getAllForTicket($ID) as $data) {

            if (($i >= $start) && ($i < ($start + $_SESSION['glpilist_limit']))) {
               echo "<tr class='tab_bg_2'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
                  echo "</td>";
               }
               echo "<td width='40%' class='center'>".$data['voucher']."</td>".
                    "<td class='center'>".$data['type']."</td>".
                    "<td class='center'>".$data['date']."</td>".
                    "<td class='center'>".$data['users']."</td>".
                    "<td class='center'>".$data['consumed']."</td></tr>";
            }

            Session::addToNavigateListItems(__CLASS__, $data["id"]);
            $i++;
         }

         echo $header_begin.$header_bottom.$header_end;
         echo "</table>\n";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }

      } else {
         echo "<p class='center b'>".__('No intervention was recorded', 'intervention')."</p>";
      }
      echo "</div>\n";

      $Entity = new Entity();
      $Entity->getFromDB($ticket->fields['entities_id']);
      PluginInterventionEntity::showForTicket($Entity);
   }

   /**
    * Get consumed tickets for intervention entity entry
    *
    * @param $ID integer PluginInterventionEntity id
   **/
   static function getConsumedForInterventionEntity($ID) {
      global $DB;

      $tot   = 0;
      $table = getTableForItemType(__CLASS__);

      $query = "SELECT SUM(`consumed`)
                FROM $table
                WHERE `plugin_intervention_entities_id` = '".$ID."'";

      if ($result = $DB->query($query)) {
         $sum = $DB->result($result, 0, 0);
         if (!is_null($sum)) {
            $tot += $sum;
         }
      }

      return $tot;
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

      $solutionForm  = false;
      $callers       = debug_backtrace();
      foreach ($callers as $call) {
         if ($call['function']=='showFormButtons') {
            $solutionForm = false;
            break;
         }
         if ($call['function']=='showSolutionForm') {
            $solutionForm = true;
            break;
         }
      }

      $canedit = $item->canEdit($item->getID());
      if (in_array($item->fields['status'], Ticket::getSolvedStatusArray())
          || in_array($item->fields['status'], Ticket::getClosedStatusArray())) {
         $canedit = false;
      }

      if ($solutionForm && $canedit) {
         echo '<tr><th colspan="2">' . self::getTypeName(2) . '</th><th colspan="2"></th></tr>';
         echo '<tr><td>';
         echo '<label for="plugin_intervention_consumed_voucher">'
                  . __('Save and consumed a voucher ?', 'intervention') . '</label>';
         echo '</td><td>';
         Dropdown::showYesNo('plugin_intervention_consumed_voucher');
         echo '</td><td colspan="2"></td>';
         echo '</tr><tr><td>';
         echo '<label for="voucher">'
                     . __('Intervention vouchers', 'intervention') . '</label>';
         echo '</td><td>';
         PluginInterventionEntity::dropdown(array('name'   => 'plugin_intervention_entities_id',
                                                  'entity' => $item->getEntityID()));
         echo '</td><td colspan="2"></td>';
         echo '</tr><tr><td>';
         echo '<label for="plugin_intervention_quantity">'
                  . __('Quantity consumed', 'intervention') . '</label>';
         echo '</td><td>';
         Dropdown::showNumber("plugin_intervention_quantity", array('value' => '',
                                                'min'   => 1,
                                                'max'   => 200,
                                                'step'  => 1));
         echo '</td><td colspan="2"></td></tr>';
      }
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

      if (isset($ticket->input['plugin_intervention_consumed_voucher'])
          && ($ticket->input['plugin_intervention_consumed_voucher'] == 1)) {

         if ($ticket->input['plugin_intervention_entities_id']==0) {
            unset($ticket->input['status']);
            unset($ticket->input['solution']);
            unset($ticket->input['solutiontypes_id']);
            Session::addMessageAfterRedirect(__('You must provide an intervention voucher',
                                    'intervention'), true, ERROR);
         } else {
            $PluginInterventionTicket = new self();
            $input = ['tickets_id'                      => $ticket->getID(),
                      'plugin_intervention_entities_id' => $ticket->input['plugin_intervention_entities_id'],
                      'consumed'                        => $ticket->input['plugin_intervention_quantity'],
                      'users_id'                        => Session::getLoginUserID()];
            if ($PluginInterventionTicket->add($input)) {
               Session::addMessageAfterRedirect(__('Intervention voucher successfully added.',
                                       'intervention'), true, INFO);
            }
         }
      }
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
                     `tickets_id` tinyint(1) NOT NULL DEFAULT '0',
                     `plugin_intervention_entities_id` tinyint(1) NOT NULL DEFAULT '0',
                     `date_creation` datetime DEFAULT NULL,
                     `consumed` int(11) NOT NULL DEFAULT '0',
                     `users_id` tinyint(1) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `tickets_id` (`tickets_id`),
                     KEY `plugin_intervention_entities_id` (`plugin_intervention_entities_id`),
                     KEY `date_creation` (`date_creation`),
                     KEY `consumed` (`consumed`),
                     KEY `users_id` (`users_id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
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
