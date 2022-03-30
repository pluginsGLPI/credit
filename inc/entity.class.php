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

class PluginCreditEntity extends CommonDBTM {

   public static $rightname = 'entity';

   static function getTypeName($nb = 0) {
      return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      $nb = self::countForItem($item);
      switch ($item->getType()) {
         case 'Entity' :
            if ($_SESSION['glpishow_count_on_tabs']) {
               return self::createTabEntry(self::getTypeName($nb), $nb);
            }
         default :
            return self::getTypeName($nb);
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
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
      return countElementsInTable(
         self::getTable(),
         getEntitiesRestrictCriteria('', '', $item->getID(), true)
      );
   }

   public function prepareInputForAdd($input) {
      if (!$this->validateInput($input)) {
         return false;
      }

      return $input;
   }

   public function prepareInputForUpdate($input) {
      if (!$this->validateInput($input)) {
         return false;
      }

      return $input;
   }

   public function post_purgeItem() {
      $pc_ticket = new PluginCreditTicket;
      $pc_ticket->deleteByCriteria([
         'plugin_credit_entities_id' => $this->getID()
      ]);
   }

   public function validateInput($input): bool {
      $message = "";

      if (isset($input['name']) && strlen($input['name']) == '') {
         $message .= __('Credit voucher name is mandatory.', 'credit')."</br>";
      }

      $credit = new PluginCreditEntity();

      if ($input['is_default_followup']) {
         if (count($credit->find([
            "entities_id" => $input["entities_id"],
            "is_default_followup"  => $input['is_default_followup']
         ])) > 0) {
            $message .= __("Default credit for followups already exist", "credit")."</br>";
         }
      }

      if ($input['is_default_task']) {
         if (count($credit->find([
            "entities_id" => $input["entities_id"],
            "is_default_task"  => $input['is_default_task']
         ])) > 0) {
            $message .= __("Default credit for tasks already exist", "credit")."</br>";
         }
      }

      if ($input['is_default_solution']) {
         if (count($credit->find([
            "entities_id" => $input["entities_id"],
            "is_default_solution"  => $input['is_default_solution']
         ])) > 0) {
            $message .= __("Default credit for solutions already exist", "credit")."</br>";
         }
      }

      if ($message != "") {
         Session::addMessageAfterRedirect($message, false, ERROR);
         return false;
      }

      return true;
   }

   /**
    * Get all credit vouchers for entity.
    *
    * @param $ID           integer     entities ID
    * @param $sqlfilter    array       to add a SQL filter (default [])
    * @return array of vouchers
    */
   static function getAllForEntity($ID, $sqlfilter = []): array {
      global $DB;

      $request = [
         'SELECT' => '*',
         'FROM'   => self::getTable(),
         'WHERE'  => getEntitiesRestrictCriteria('', '', $ID, true) + $sqlfilter,
         'ORDER'  => ['id DESC'],
      ];

      $vouchers = [];
      foreach ($DB->request($request) as $data) {
         $vouchers[$data['id']] = $data;
      }

      return $vouchers;
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
   static function getDefaultForEntityAndType($ID, $itemtype) {

      $credit = new PluginCreditEntity();
      $options = ["entities_id" => $ID,
                  "is_active" => 1];

      //second step check if default credit for itemtype exist
      switch ($itemtype) {
         case ITILFollowup::getType():
            $options['is_default_followup'] = 1;
            break;

         case TicketTask::getType():
            $options['is_default_task'] = 1;
            break;

         case ITILSolution::getType():
            $options['is_default_solution'] = 1;
            break;
      }

      $credit->getFromDBByCrit($options);
      if (!$credit->getFromDBByCrit($options)) {
         return 0;
      } else {
         return $credit->fields['id'];
      }

   }

   /**
    * Show credit vouchers of an entity
    *
    * @param $entity object Entity
    * @param $itemtype string Entity or Ticket
   **/
   static function showForItemtype(Entity $entity, $itemtype = 'Entity') {
      $ID = $entity->getField('id');
      if (!$entity->can($ID, READ)) {
         return false;
      }

      $out     = "";
      $canedit = $itemtype == 'Ticket'
                  ? false
                  : $entity->canEdit($ID);

      if ($canedit) {
         $rand = mt_rand();
         $out .= "<div class='firstbloc'>";
         $out .= "<form name='creditentity_form$rand' id='creditentity_form$rand' method='post' action='";
         $out .= self::getFormUrl()."'>";
         $out .= "<input type='hidden' name='entities_id' value='$ID'>";
         $out .= "<table class='tab_cadre_fixe'>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<th colspan='10'>" . __('Add a credit voucher', 'credit') . "</th>";
         $out .= "</tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<td>". __('Name')."<span class='red'>*</span></strong></td>";
         $out .= "<td colspan='5'>".Html::input("name", ['size' => 50])."</td>";
         $out .= "<td class='tab_bg_2 right'>".__('Type')."</td>";
         $out .= "<td>";
         $out .= PluginCreditType::dropdown(['name'    => 'plugin_credit_types_id',
                                             'display' => false]);
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>".__('Start date')."</td>";
         $out .= "<td>";
         $out .= Html::showDateField("begin_date", ['value'   => '', 'display' => false]);
         $out .= "</td>";
         $out .= "</tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<td>".__('Active')."</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo("is_active", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>".__('Child entities')."</td><td>";
         $out .= Dropdown::showYesNo("is_recursive", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>";
         $out .= __('Quantity sold', 'credit')."</td><td>";
         $out .= Dropdown::showNumber("quantity", ['value'   => '',
                                                   'min'     => 1,
                                                   'max'     => 1000000,
                                                   'step'    => 1,
                                                   'toadd'   => [0 => __('Unlimited')],
                                                   'display' => false]);
         $out .= "</td>";
         $out .= "<td>".__('Allow overconsumption', 'credit')."</td>";
         $out .= "<td>";
         $out .= Dropdown::showYesNo("overconsumption_allowed", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "<td class='tab_bg_2 right'>".__('End date')."</td>";
         $out .= "<td>";
         $out .= Html::showDateField("end_date", ['value' => '', 'display' => false]);
         $out .= "</td>";
         $out .= "</tr>";
         $out .= "</table>";
         $out .= "<table class='tab_cadre_fixe'>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<th colspan='8'>" . __('Default options', 'credit') . "</th>";
         $out .= "</tr>";
         $out .= "<tr>";
         $title = __("No")." => ".__("Not considered as default.", "credit")."\n";
         $title .= __("Yes")." => ".__("Considered as default for followups, tasks and solution", "credit")."\n";
         $title .= __("Advanced configuration", "credit")." => ".__("Configure defaults for followups, tasks and solutions separately.", "credit");
         $out .= "<td>".__('Default for tickets', 'credit')."<i class='fa fa-info-circle' title='$title'></i></td>";
         $out .= "<td>";
         $out .= Dropdown::showFromArray("is_default", self::getDefaultOptions(), ['display' => false,
                                                                                    'on_change' => 'changeDefaultVisibilityOptions(this)']);
         $out .= "</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>".__('Default for followups', 'credit')."</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>";
         $out .= Dropdown::showYesNo("is_default_followup", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>".__('Default for tasks', 'credit')."</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>";
         $out .= Dropdown::showYesNo("is_default_task", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>".__('Default for solutions', 'credit')."</td>";
         $out .= "<td class='default_option_visibility' style='visibility:hidden'>";
         $out .= Dropdown::showYesNo("is_default_solution", 0, -1, ['display' => false]);
         $out .= "</td>";
         $out .= "</tr>";
         $out .= "<tr class='tab_bg_1'>";
         $out .= "<td class='tab_bg_2 center' colspan='8'>";
         $out .= "<input type='submit' name='add' value='"._sx('button', 'Add')."' class='submit'>";
         $out .= "</td>";
         $out .= "</tr>";
         $out .= "</table>";
         $out .= Html::closeForm(false);
         $out .= "</div>";
      }

      PluginCreditEntityConfig::showEntityConfig($entity, $entity::getType());

      $out    .= "<div class='spaced'>";
      $number  = self::countForItem($entity);
      if ($number) {

         if ($canedit) {
            $rand = mt_rand();
            $out .= Html::getOpenMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = [
               'num_displayed'    => $number,
               'container'        => 'mass'.__CLASS__.$rand,
               'rand'             => $rand,
               'display'          => false,
               'specific_actions' => [
                  'update' => _x('button', 'Update'),
                  'purge'  => _x('button', 'Delete permanently')
               ]
            ];
            $out .= Html::showMassiveActions($massiveactionparams);
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
         $header_end .= "<th>".__('Allow overconsumption', 'credit')."</th>";
         $header_end .= "<th>".__('Entity')."</th>";
         $header_end .= "<th>".__('Child entities')."</th>";
         $header_end .= "<th>".__('Default for followups', 'credit')."</th>";
         $header_end .= "<th>".__('Default for tasks', 'credit')."</th>";
         $header_end .= "<th>".__('Default for solutions', 'credit')."</th>";
         $header_end .= "</tr>";
         $out .= $header_begin.$header_top.$header_end;

         $sqlfilter = [];
         if ($itemtype == 'Ticket') {
            $sqlfilter = [
               'is_active' => '1'
            ];
         }

         foreach (self::getAllForEntity($ID, $sqlfilter) as $data) {

            $out .= "<tr class='tab_bg_2'>";
            if ($canedit) {
               $out .= "<td width='10'>";
               $out .= Html::getMassiveActionCheckBox(__CLASS__, $data["id"]);
               $out .= "</td>";
            }

            $out .= "<td width='30%'>";
            $out .= "<a href='".Entity::getFormURLWithID($ID, true);
            $out .= "&forcetab=PluginCreditEntity$1'>";
            $out .= $data['name'] == '' ? '(' . $data['id'] . ')' : $data['name'];
            $out .= "</a>";
            $out .= "</td>";
            $out .= "<td width='15%'>";
            $out .= Dropdown::getDropdownName(PluginCreditType::getTable(),
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

            $quantity_sold = (int)$data['quantity'];
            $out .= "<td class='center'>";
            $out .= 0 === $quantity_sold ? __('Unlimited') : $quantity_sold;
            $out .= "</td>";

            $quantity_consumed = PluginCreditTicket::getConsumedForCreditEntity($data['id']);
            $out .= "<td class='center'>";
            $out .= Ajax::createIframeModalWindow('displaycreditconsumed_'.$data["id"],
                                                  Plugin::getWebDir('credit').
                                                  "/front/ticket.php?plugcreditentity=".
                                                  $data["id"],
                                          ['title'         => __('Consumed details', 'credit'),
                                           'reloadonclose' => false,
                                           'display'       => false]);

            $out .= "<a href='#' onClick=\"".Html::jsGetElementbyID('displaycreditconsumed_'.
                                                                   $data["id"]).
                                          ".dialog('open');\" ";
            $out .= "title='".__('Consumed details', 'credit')."' ";
            $out .= "alt='".__('Consumed details', 'credit')."'>";
            $out .= $quantity_consumed;
            $out .= "</a></td>";

            $out .= "<td class='center'>";
            $out .= 0 === $quantity_sold
                    ? __('Unlimited')
                    : max(0, $quantity_sold - $quantity_consumed);

            $out .= "</td><td>";
            $out .= ($data["overconsumption_allowed"]) ? __('Yes') : __('No');
            $out .= "</td>";

            $out .= "<td>";
            $out .= Dropdown::getDropdownName(Entity::getTable(), $data['entities_id']);
            $out .= "</td>";
            $out .= "<td>";
            $out .= ($data["is_recursive"]) ? __('Yes') : __('No');
            $out .= "</td>";

            $out .= "<td>";
            $out .= ($data["is_default_followup"]) ? __('Yes') : __('No');
            $out .= "</td>";

            $out .= "<td>";
            $out .= ($data["is_default_task"]) ? __('Yes') : __('No');
            $out .= "</td>";

            $out .= "<td>";
            $out .= ($data["is_default_solution"]) ? __('Yes') : __('No');
            $out .= "</td>";

            $out .= "</tr>";
         }

         $out .= $header_begin.$header_bottom.$header_end;
         $out .= "</table>";

         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            $out .= Html::showMassiveActions($massiveactionparams);
            $out .= Html::closeForm(false);
         }
      } else {
         $out .= "<p class='center b'>".__('No credit voucher', 'credit')."</p>";
      }
      $out .= "</div>";
      echo $out;
   }

   function rawSearchOptions() {

      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => 991,
         'table'    => self::getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id'       => 992,
         'table'    => self::getTable(),
         'field'    => 'begin_date',
         'name'     => __('Start date'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 993,
         'table'    => self::getTable(),
         'field'    => 'end_date',
         'name'     => __('End date'),
         'datatype' => 'date',
      ];

      $tab[] = [
         'id'       => 994,
         'table'    => self::getTable(),
         'field'    => 'quantity',
         'name'     => __('Quantity sold', 'credit'),
         'datatype' => 'number',
         'min'      => 1,
         'max'      => 1000000,
         'step'     => 1,
         'toadd'    => [0 => __('Unlimited')],
      ];

      $tab[] = [
         'id'       => 995,
         'table'    => PluginCreditType::getTable(),
         'field'    => 'name',
         'name'     => __('Credit voucher type', 'credit'),
         'datatype' => 'dropdown',
      ];

      $tab[] = [
         'id'       => 996,
         'table'    => self::getTable(),
         'field'    => 'overconsumption_allowed',
         'name'     => __('Allow overconsumption', 'credit'),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id'       => 998,
         'table'    => self::getTable(),
         'field'    => 'is_default_followup',
         'name'     => __('Default for followups', 'credit'),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id'       => 999,
         'table'    => self::getTable(),
         'field'    => 'is_default_task',
         'name'     => __('Default for tasks', 'credit'),
         'datatype' => 'bool',
      ];

      $tab[] = [
         'id'       => 1000,
         'table'    => self::getTable(),
         'field'    => 'is_default_solution',
         'name'     => __('Default for solutions', 'credit'),
         'datatype' => 'bool',
      ];

      return $tab;
   }

   static function getDefaultOptions() {
      return [
         0 => __('No'),
         1 => __('Yes'),
         2 => __('Advanced configuration', 'credit'),
      ];
   }

   static function cronInfo($name) {
      switch ($name) {
         case 'creditexpired':
            return [
               'description' => __('Expiration date', 'credit'),
               'parameter'   => __('Notice (in days)', 'credit')
            ];
      }
      return [];
   }

   static function cronCreditExpired($task) {
      global $CFG_GLPI, $DB;

      if (!$CFG_GLPI['use_notifications']) {
         return 0;
      }

      $notice_time = (int)$task->fields['param'];

      $alert = new Alert();
      $credits_iterator = $DB->request(
         [
            'SELECT'    => [
               'glpi_plugin_credit_entities.*',
            ],
            'FROM'      => 'glpi_plugin_credit_entities',
            'LEFT JOIN' => [
               'glpi_alerts' => [
                  'ON' => [
                     'glpi_alerts'                 => 'items_id',
                     'glpi_plugin_credit_entities' => 'id',
                     [
                        'AND' => [
                           'glpi_alerts.itemtype' => self::getType(),
                           'glpi_alerts.type'     => Alert::END,
                        ],
                     ],
                  ]
               ]
            ],
            'WHERE'     => [
               'glpi_alerts.date'                      => null,
               'glpi_plugin_credit_entities.is_active' => 1,
               ['NOT' => ['glpi_plugin_credit_entities.end_date' => null]],
               new QueryExpression(
                  sprintf(
                     'ADDDATE(NOW(), INTERVAL %s DAY) >= %s',
                     $notice_time,
                     $DB->quoteName('glpi_plugin_credit_entities.end_date')
                  )
               ),
            ],
         ]
      );

      foreach ($credits_iterator as $credit_data) {
         $task->addVolume(1);
         $task->log(
            sprintf(
               'Credit %s expires on %s',
               $credit_data['name'],
               date('Y-m-d', strtotime($credit_data['end_date']))
            )
         );

         $credit = new PluginCreditEntity();
         $credit->getFromDB($credit_data['id']);

         NotificationEvent::raiseEvent('expired', $credit);

         $input = [
            'type'     => Alert::END,
            'itemtype' => self::getType(),
            'items_id' => $credit_data['id'],
         ];
         $alert->add($input);
         unset($alert->fields['id']);
      }

      return 1;
   }

   /**
    * Install all necessary tables for the plugin
    *
    * @return boolean True if success
    */
   static function install(Migration $migration) {
      global $DB;

      $table = self::getTable();

      if (!$DB->tableExists($table)) {
         $migration->displayMessage("Installing $table");

         $query = "CREATE TABLE IF NOT EXISTS `$table` (
                     `id` int(11) NOT NULL auto_increment,
                     `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                     `entities_id` int(11) NOT NULL DEFAULT '0',
                     `is_recursive` tinyint NOT NULL DEFAULT '0',
                     `is_active` tinyint(1) NOT NULL DEFAULT '0',
                     `plugin_credit_types_id` tinyint(1) NOT NULL DEFAULT '0',
                     `begin_date` timestamp DEFAULT NULL,
                     `end_date` timestamp DEFAULT NULL,
                     `quantity` int(11) NOT NULL DEFAULT '0',
                     `overconsumption_allowed` tinyint(1) NOT NULL DEFAULT '0',
                     `is_default_followup` tinyint(1) NOT NULL DEFAULT '0',
                     `is_default_solution` tinyint(1) NOT NULL DEFAULT '0',
                     `is_default_task` tinyint(1) NOT NULL DEFAULT '0',
                     PRIMARY KEY (`id`),
                     KEY `name` (`name`),
                     KEY `entities_id` (`entities_id`),
                     KEY `is_recursive` (`is_recursive`),
                     KEY `is_active` (`is_active`),
                     KEY `plugin_credit_types_id` (`plugin_credit_types_id`),
                     KEY `begin_date` (`begin_date`),
                     KEY `end_date` (`end_date`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->query($query) or die($DB->error());
      } else {
         $migration->displayMessage("Upgrading $table");

         if (!$DB->fieldExists($table, 'overconsumption_allowed')) {
            //1.5.0
            $migration->addField(
               $table,
               "overconsumption_allowed",
               "TINYINT(1) NOT NULL DEFAULT '0'",
               [
                  'update' => "1",
               ]
            );
         }

         if (!$DB->fieldExists($table, 'is_recursive')) {
            //1.9.0
            $migration->displayMessage("Add field 'is_recursive'");
            $migration->addField(
               $table,
               "is_recursive",
               "TINYINT NOT NULL DEFAULT '0'",
               [
                  'update' => "0",
               ]
            );
            $migration->addKey($table, 'is_recursive');
         }

         $migration->dropField($table, 'is_default'); // Was added during dev phase of 1.10.0

         if (!$DB->fieldExists($table, 'is_default_followup')) {
            //1.10.0
            $migration->addField(
               $table,
               "is_default_followup",
               "TINYINT(1) NOT NULL DEFAULT '0'",
               [
                  'value' => "0",
               ]
            );
         }

         if (!$DB->fieldExists($table, 'is_default_task')) {
            //1.10.0
            $migration->addField(
               $table,
               "is_default_task",
               "TINYINT(1) NOT NULL DEFAULT '0'",
               [
                  'value' => "0",
               ]
            );
         }

         if (!$DB->fieldExists($table, 'is_default_solution')) {
            //1.10.0
            $migration->addField(
               $table,
               "is_default_solution",
               "TINYINT(1) NOT NULL DEFAULT '0'",
               [
                  'value' => "0",
               ]
            );
         }
      }
   }

   /**
    * Uninstall previously installed table of the plugin
    *
    * @return boolean True if success
    */
   static function uninstall(Migration $migration) {
      $table = self::getTable();
      $migration->displayMessage("Uninstalling $table");
      $migration->dropTable($table);
   }

}
