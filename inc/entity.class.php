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
    * Show intervention vouchers of an entity
    *
    * @param $entity Entity object
   **/
   function showForEntity(Entity $entity) {
      global $DB, $CFG_GLPI;

      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      $canedit       = $entity->canEdit($ID);
      $nb_per_line   = 3;
      $rand          = mt_rand();

      if ($canedit) {
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
         echo "</td><td class='tab_bg_2 center'>".__('Quantity')."</td><td>";
         Html::autocompletionTextField($this, "quantity", array('value' => '',
                                                            'size'  => 10));
         echo "</td><td class='tab_bg_2 center'>".__('Date start')."</td><td>";
         Html::showDateTimeField("date_start", array('value'      => '',
                                               'timestep'   => 0,
                                               'maybeempty' => false));
         echo "</td><td class='tab_bg_2 center'>".__('Date end')."</td><td>";
         Html::showDateTimeField("date_end", array('value'      => '',
                                               'timestep'   => 0,
                                               'maybeempty' => false));
         echo "</td><td class='tab_bg_2 center'>";
         echo "<input type='submit' name='add' value=\""._sx('button', 'Add')."\" class='submit'>";
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      /* WIP
      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      echo "<div class='spaced'>";
      if ($cancreate && $nb) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
         $massiveactionparams
            = array('container'
                        => 'mass'.__CLASS__.$rand,
                    'specific_actions'
                        => array('purge' => _x('button', 'Delete permanently')));
         Html::showMassiveActions($massiveactionparams);
      }*/
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
                     `date_start` datetime DEFAULT NULL,
                     `date_end` datetime DEFAULT NULL,
                     `quantity` int(11) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `entities_id` (`entities_id`),
                     KEY `plugin_intervention_types_id` (`plugin_intervention_types_id`),
                     KEY `date_start` (`date_start`),
                     KEY `date_end` (`date_end`)
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
