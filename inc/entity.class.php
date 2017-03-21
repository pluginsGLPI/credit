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

class PluginCreditEntity extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb=0) {
      return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
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
    * Actions done after the PURGE of the item in the database
    *
    * @return nothing
   **/
   public function post_purgeItem() {
      global $DB;

      $table = getTableForItemType('PluginCreditTicket');
      $query = "DELETE FROM `$table` WHERE `plugin_credit_entities_id` = {$this->getID()};";
      $DB->query($query);
   }

   /**
    * Get all credit vouchers for entity.
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
    * Show credit vouchers of an entity
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
         $out .= "<form name='creditentity_form$rand' id='creditentity_form$rand' method='post' action='";
         $out .= Toolbox::getItemTypeFormURL(__CLASS__)."'>";
         $out .= "<table class='tab_cadre_fixe'>";
         $out .= "<tr class='tab_bg_1'><th colspan='7'>";
         $out .= __('Add an credit voucher', 'credit')."</th></tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<input type='hidden' name='entities_id' value='$ID'>";
         $out .= "<td>". __('Name')."</td>";
         $out .= "<td>" . Html::input("name", array('size' => 50)) . "</td>";
         $out .= "<td class='tab_bg_2 right'>". __('Type')."</td><td>";
         echo $out; $out = "";
         PluginCreditType::dropdown(array('name'  => 'plugin_credit_types_id'));
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>".__('Start date')."</td><td>";
         echo $out; $out = "";
         Html::showDateField("begin_date", array('value' => ''));
         $out .= "</td></tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<td>".__('Active')."</td>";
         $out .= "<td>";
         echo $out; $out = "";
         Dropdown::showYesNo("is_active", '');
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>";
         $out .= __('Quantity sold', 'credit')."</td><td>";
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
         $header_end .= "<th>".__('Name')."</th>";
         $header_end .= "<th>".__('Type')."</th>";
         $header_end .= "<th>".__('Active')."</th>";
         $header_end .= "<th>".__('Start date')."</th>";
         $header_end .= "<th>".__('End date')."</th>";
         $header_end .= "<th>".__('Quantity sold', 'credit')."</th>";
         $header_end .= "<th>".__('Quantity consumed', 'credit')."</th>";
         $header_end .= "<th>".__('Quantity remaining', 'credit')."</th>";
         $header_end .= "</tr>\n";
         $out .= $header_begin.$header_top.$header_end;

         $sqlfilter = "";
         if ($itemtype == 'Ticket') {
            $sqlfilter = "`is_active`='1'";
         }

         foreach (self::getAllForEntity($ID, 0, 0, $sqlfilter) as $data) {

            $out .= "<tr class='tab_bg_2'>";
            if ($canedit) {
               $out .= "<td width='10'>";
               echo $out; $out = "";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               $out .= "</td>";
            }

            $out .= "<td width='30%'>";
            $out .= "<a href=\"".$CFG_GLPI['root_doc']."/front/entity.form.php?id=".$ID;
            $out .= "&forcetab=PluginCreditEntity$1\">";
            $out .= $data['name'];
            $out .= "</a>";
            $out .= "</td>";
            $out .= "<td width='15%'>";
            $out .= Dropdown::getDropdownName(getTableForItemType('PluginCreditType'),
                                                $data['plugin_credit_types_id']);
            $out .= "</td>";
            $out .= "<td>";
            $out .= ($data["is_active"]) ? __('Yes') : __('No');
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

            Ajax::createIframeModalWindow('displaycreditconsumed_' . $data["id"],
                                          $CFG_GLPI["root_doc"]
                                             . "/plugins/credit/front/ticket.php?plugcreditentity="
                                             . $data["id"],
                                          ['title'         => __('Consumed details', 'credit'),
                                           'reloadonclose' => false]);

            $quantity_consumed = PluginCreditTicket::getConsumedForCreditEntity($data['id']);
            $out .= "<td class='center'>";
            $out .= "<a href='#' onClick=\"" . Html::jsGetElementbyID('displaycreditconsumed_'
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
         $out .= "<p class='center b'>".__('No credit voucher', 'credit')."</p>";
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

      $tab[991]['table']    = $this->getTable();
      $tab[991]['field']    = 'is_active';
      $tab[991]['name']     = __('Active');
      $tab[991]['datatype'] = 'bool';

      $tab[992]['table']    = $this->getTable();
      $tab[992]['field']    = 'begin_date';
      $tab[992]['name']     = __('Start date');
      $tab[992]['datatype'] = 'date';

      $tab[993]['table']    = $this->getTable();
      $tab[993]['field']    = 'end_date';
      $tab[993]['name']     = __('End date');
      $tab[993]['datatype'] = 'date';

      $tab[994]['table']    = $this->getTable();
      $tab[994]['field']    = 'quantity';
      $tab[994]['name']     = __('Quantity sold', 'credit');
      $tab[994]['datatype'] = 'number';
      $tab[994]['min']      = 1;
      $tab[994]['max']      = 200;
      $tab[994]['step']     = 1;
      $tab[994]['toadd']    = array(0 => __('Unlimited'));

      $tab[995]['table']    = getTableForItemType('PluginCreditType');
      $tab[995]['field']    = 'name';
      $tab[995]['name']     = __('Credit voucher type', 'credit');
      $tab[995]['datatype'] = 'dropdown';

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
                     `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_active` tinyint(1) NOT NULL DEFAULT '0',
                     `plugin_credit_types_id` tinyint(1) NOT NULL DEFAULT '0',
                     `begin_date` datetime DEFAULT NULL,
                     `end_date` datetime DEFAULT NULL,
                     `quantity` int(11) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`),
                     KEY `entities_id` (`entities_id`),
                     KEY `is_active` (`is_active`),
                     KEY `plugin_credit_types_id` (`plugin_credit_types_id`),
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
