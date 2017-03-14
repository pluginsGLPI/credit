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

class PluginInterventionEntity extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb=0) {
      return _n('Intervention voucher type', 'Intervention vouchers types', $nb, 'intervention');
   }

   /**
    * @see CommonGLPI::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Entity' :
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName(), self::countForItem($item));
            }
            break;
         default :
            return self::getTypeName();
            break;
      }
      return '';
   }

   /**
    * @see CommonGLPI::displayTabContentForItem()
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'Entity' :
            self::showForEntity($item);
            break;

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
                                    "`entities_id` = '".$item->getID()."'");
   }

   /**
    * Get all intervention vouchers for entity.
    *
    * @param $ID           integer     entities ID
    * @param $start        integer     first line to retrieve (default 0)
    * @param $limit        integer     max number of line to retrive (0 for all) (default 0)
    * @param $sqlfilter    string      to add an SQL filter (default '')
    * @return array of vouchers
   **/
   static function getAllForEntity($ID, $start=0, $limit=0, $sqlfilter='') {
      global $DB;

      $query = "SELECT *
                FROM `" . getTableForItemType(__CLASS__) . "`
                WHERE `entities_id` = '$ID'";
      if ($sqlfilter) {
         $query .= "AND ($sqlfilter) ";
      }
      $query .= "ORDER BY `id` DESC";

      if ($limit) {
         $query .= " LIMIT ".intval($start)."," . intval($limit);
      }

      $vouchers = array();
      foreach ($DB->request($query) as $data) {
         $tmp = array();
         $tmp['id']         = $data['id'];
         $tmp['type']       = Dropdown::getDropdownName(getTableForItemType('PluginInterventionType'),
                                                         $data['plugin_intervention_types_id']);
         $tmp['begin_date']         = Html::convDate($data["begin_date"]);
         $tmp['end_date']           = Html::convDate($data["end_date"]);
         $tmp['quantity_sold']      = $data['quantity'];
         $tmp['quantity_consumed']  = 0;
         $tmp['quantity_remaining'] = 0;

         $vouchers[$tmp['id']] = $tmp;
      }

      return $vouchers;
   }

   /**
    * Show intervention vouchers of an entity
    *
    * @param $entity Entity object
   **/
   static function showForEntity(Entity $entity) {
      global $DB, $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      $canedit = $entity->canEdit($ID);
      $number  = self::countForItem($entity);

      if ($canedit) {
         $rand = mt_rand();
         echo "<div class='firstbloc'>";
         echo "<form name='interventionentity_form$rand' id='interventionentity_form$rand' method='post' action='";
         echo Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='8'>"
                  . __('Add an intervention voucher', 'intervention')."</tr>";
         echo "<tr class='tab_bg_1'><td class='tab_bg_2 center'>"
                  . __('Intervention voucher type', 'intervention')."&nbsp;";
         echo "<input type='hidden' name='entities_id' value='$ID'>";
         PluginInterventionType::dropdown(array('name'  => 'plugin_intervention_types_id'));
         echo "</td><td class='tab_bg_2 center'>".__('Quantity sold', 'intervention')."</td><td>";
         Dropdown::showNumber("quantity", array('value' => '',
                                                'min'   => 1,
                                                'max'   => 200,
                                                'step'  => 1,
                                                'toadd' => array(0 => __('Unlimited'))));
         echo "</td><td class='tab_bg_2 center'>".__('Start date')."</td><td>";
         Html::showDateField("begin_date", array('value' => ''));
         echo "</td><td class='tab_bg_2 center'>".__('End date')."</td><td>";
         Html::showDateField("end_date", array('value' => ''));
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('No intervention voucher', 'intervention')."</th></tr>";
         echo "</table>";
         return;
      }

      $rand = mt_rand();

      if ($canedit && $number) {
            Html::openMassiveActionsForm('mass'.get_called_class().$rand);
            $massiveactionparams
               = array('num_displayed'
                           => $number,
                       'specific_actions'
                           => array('update' => _x('button', 'Update'),
                                    'purge'  => _x('button', 'Delete permanently')));
            Html::showMassiveActions($massiveactionparams);
      }

      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit) {
         $header_begin  .= "<th width='10'>";
         $header_top    .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
         $header_bottom .= Html::getCheckAllAsCheckbox('mass'.get_called_class().$rand);
         $header_end    .= "</th>";
      }
      $header_end .= "<th>".__('Type')."</th>";
      $header_end .= "<th>".__('Start date')."</th>";
      $header_end .= "<th>".__('End date')."</th>";
      $header_end .= "<th>".__('Quantity sold', 'intervention')."</th>";
      $header_end .= "<th>".__('Quantity consumed', 'intervention')."</th>";
      $header_end .= "<th>".__('Quantity remaining', 'intervention')."</th>";
      $header_end .= "</tr>\n";
      echo $header_begin.$header_top.$header_end;

      Session::initNavigateListItems(__CLASS__, sprintf(__('%1$s'), self::getTypeName(1)));

      foreach (self::getAllForEntity($ID) as $data) {
         Session::addToNavigateListItems(__CLASS__, $data["id"]);
         echo "<tr class='tab_bg_2'>";

         if ($canedit) {
            echo "<td width='10'>";
            Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
            echo "</td>";
         }
         echo "<td width='40%'>".$data['type']."</td>".
              "<td class='tab_date'>".$data['begin_date']."</td>".
              "<td class='tab_date'>".$data['end_date']."</td>".
              "<td>".$data['quantity_sold']."</td>".
              "<td>".$data['quantity_consumed']."</td>";
         echo "<td>".$data['quantity_remaining']."</td></tr>";
      }

      echo $header_begin.$header_bottom.$header_end;
      echo "</table>\n";

      if ($canedit) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                = parent::getSearchOptions();

      $tab[2]['table']    = $this->getTable();
      $tab[2]['field']    = 'begin_date';
      $tab[2]['name']     = __('Start date');
      $tab[2]['datatype'] = 'date';

      $tab[3]['table']    = $this->getTable();
      $tab[3]['field']    = 'end_date';
      $tab[3]['name']     = __('End date');
      $tab[3]['datatype'] = 'date';

      $tab[4]['table']    = $this->getTable();
      $tab[4]['field']    = 'quantity';
      $tab[4]['name']     = __('Quantity sold', 'intervention');
      $tab[4]['datatype'] = 'decimal';

      $tab[5]['table']    = getTableForItemType('PluginInterventionType');
      $tab[5]['field']    = 'name';
      $tab[5]['name']     = __('Intervention voucher type', 'intervention');
      $tab[5]['datatype'] = 'dropdown';

      return $tab;
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
   **/
   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $msg = __('The end date has been changed automatically.', 'intervention');
         Session::addMessageAfterRedirect($msg, false, WARNING);

         $input['end_date'] = $input['begin_date'];
      }
      return $input;
   }


   /**
    * @see CommonDBTM::prepareInputForUpdate()
   **/
   function prepareInputForUpdate($input) {

      $input = parent::prepareInputForUpdate($input);

      if (empty($input['end_date'])
          || ($input['end_date'] == 'NULL')
          || ($input['end_date'] < $input['begin_date'])) {

         $msg = __('The end date has been changed automatically.', 'intervention');
         Session::addMessageAfterRedirect($msg, false, WARNING);

         $input['end_date'] = $input['begin_date'];
      }

      return $input;
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
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `plugin_intervention_types_id` tinyint(1) NOT NULL DEFAULT '0',
                     `begin_date` datetime DEFAULT NULL,
                     `end_date` datetime DEFAULT NULL,
                     `quantity` int(11) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `entities_id` (`entities_id`),
                     KEY `plugin_intervention_types_id` (`plugin_intervention_types_id`),
                     KEY `begin_date` (`begin_date`),
                     KEY `end_date` (`end_date`)
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
