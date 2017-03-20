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
      return _n('Intervention voucher', 'Intervention vouchers', $nb, 'intervention');
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
            self::showForItemtype($item);
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
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
   }

   /**
    * Show intervention vouchers of an entity
    *
    * @param $entity object Entity
    * @param $itemtype string Entity or Ticket
   **/
   static function showForItemtype(Entity $entity, $itemtype='Entity') {
      global $DB, $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      $out     = "";
      $canedit = ($itemtype=='Ticket') ? false : $entity->canEdit($ID);

      if ($canedit) {
         $rand = mt_rand();
         $out .= "<div class='firstbloc'>";
         $out .= "<form name='interventionentity_form$rand' id='interventionentity_form$rand' method='post' action='";
         $out .= Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         $out .= "<table class='tab_cadre_fixe'>";
         $out .= "<tr class='tab_bg_1'><th colspan='7'>";
         $out .= __('Add an intervention voucher', 'intervention')."</th></tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<input type='hidden' name='entities_id' value='$ID'>";
         $out .= "<td>". __('Name')."</td>";
         $out .= "<td>" . Html::input("name", array('size' => 50)) . "</td>";
         $out .= "<td class='tab_bg_2 right'>". __('Type')."</td><td>";
         echo $out; $out = "";
         PluginInterventionType::dropdown(array('name'  => 'plugin_intervention_types_id'));
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>".__('Start date')."</td><td>";
         echo $out; $out = "";
         Html::showDateField("begin_date", array('value' => ''));
         $out .= "</td></tr>";
         $out .= "<tr class='tab_bg_1'><td colspan='2'></td><td class='tab_bg_2 right'>";
         $out .= __('Quantity sold', 'intervention')."</td><td>";
         echo $out; $out = "";
         Dropdown::showNumber("quantity", ['value' => '',
                                           'min'   => 1,
                                           'max'   => 200,
                                           'step'  => 1,
                                           'toadd' => [0 => __('Unlimited')]]);
         $out .= "</td><td class='tab_bg_2 right'>".__('End date')."</td><td>";
         echo $out; $out = "";
         Html::showDateField("end_date", array('value' => ''));
         $out .= "</td><td class='tab_bg_2 right'>";
         $out .= "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         $out .= "</td></tr>";
         $out .= "</table>";
         echo $out; $out = "";
         Html::closeForm();
         $out .= "</div>";
      }

      $out    .= "<div class='spaced'>";
      $number  = self::countForItem($entity);
      if ($number) {

         if ($canedit) {
            $rand = mt_rand();
            echo $out; $out = "";
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams
               = array('num_displayed'
                         => $number,
                       'container'
                         => 'mass'.__CLASS__.$rand,
                       'rand' => $rand,
                       'specific_actions'
                         => array('purge' => _x('button', 'Delete permanently')));
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
         $header_end .= "<th>".__('Name')."</th>";
         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Start date')."</th>";
         $header_end .= "<th>".__('End date')."</th>";
         $header_end .= "<th>".__('Quantity sold', 'intervention')."</th>";
         $header_end .= "<th>".__('Quantity consumed', 'intervention')."</th>";
         $header_end .= "<th>".__('Quantity remaining', 'intervention')."</th>";
         $header_end .= "</tr>\n";
         $out .= $header_begin.$header_top.$header_end;

         foreach (self::getAllForEntity($ID) as $data) {

            $out .= "<tr class='tab_bg_2'>";
            if ($canedit) {
               $out .= "<td width='10'>";
               echo $out; $out = "";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               $out .= "</td>";
            }

            $out .= "<td width='40%'>";
            $out .= $data['name'];
            $out .= "</td>";
            $out .= "<td>";
            $out .= Dropdown::getDropdownName(getTableForItemType('PluginInterventionType'),
                                                $data['plugin_intervention_types_id']);
            $out .= "</td>";
            $out .= "<td class='tab_date'>";
            $out .= Html::convDate($data["begin_date"]);
            $out .= "</td>";
            $out .= "<td class='tab_date'>";
            $out .= Html::convDate($data["end_date"]);
            $out .= "</td>";
            $out .= "<td class='center'>";
            $out .= ($data['quantity']==0) ? __('Unlimited') : $data['quantity'];;
            $out .= "</td>";

            Ajax::createIframeModalWindow('displayinterventionconsumed_' . $data["id"],
                                          $CFG_GLPI["root_doc"]
                                             . "/plugins/intervention/front/ticket.php?pluginterventionentity="
                                             . $data["id"],
                                          ['title'         => __('Consumed details', 'intervention'),
                                           'reloadonclose' => false]);

            $quantity_consumed = PluginInterventionTicket::getConsumedForInterventionEntity($data['id']);
            $out .= "<td class='center'>";
            $out .= "<a href='#' onClick=\"" . Html::jsGetElementbyID('displayinterventionconsumed_'
                                                                      . $data["id"])
                                             . ".dialog('open');\">";
            $out .= $quantity_consumed;
            $out .= "</a></td>";

            $out .= "<td class='center'>";
            $out .= ($data['quantity']==0) ? __('Unlimited') : $data['quantity'] - $quantity_consumed;
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
         $out .= "<p class='center b'>".__('No intervention voucher', 'intervention')."</p>";
      }
      $out .= "</div>\n";
      echo $out;
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
      $tab[4]['datatype'] = 'number';
      $tab[4]['min']      = 1;
      $tab[4]['max']      = 200;
      $tab[4]['step']     = 1;
      $tab[4]['toadd']    = array(0 => __('Unlimited'));

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
                     `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `plugin_intervention_types_id` tinyint(1) NOT NULL DEFAULT '0',
                     `begin_date` datetime DEFAULT NULL,
                     `end_date` datetime DEFAULT NULL,
                     `quantity` int(11) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`),
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
