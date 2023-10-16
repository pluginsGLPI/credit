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
 * @copyright Copyright (C) 2017-2023 by Credit plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/credit
 * -------------------------------------------------------------------------
 */

use Glpi\Api\HL\Controller\AbstractController;
use Glpi\Api\HL\Doc\Schema;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_credit_install() {

   $migration = new Migration(PLUGIN_CREDIT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginCredit' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }

   $migration->addRight(
      PluginCreditTicketConfig::$rightname,
      PluginCreditTicketConfig::TICKET_TAB | PluginCreditTicketConfig::TICKET_FORM,
      [Entity::$rightname => UPDATE]
   );

   $migration->executeMigration();

   CronTask::register(
      'PluginCreditEntity',
      'creditexpired',
      DAY_TIMESTAMP,
      [
         'comment' => '',
         'mode' => CronTask::MODE_EXTERNAL,
      ]
   );

   return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_credit_uninstall() {

   $migration = new Migration(PLUGIN_CREDIT_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginCredit' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }

   $migration->executeMigration();

   return true;
}

/**
 * Define Dropdown tables to be manage in GLPI :
 */
function plugin_credit_getDropdown() {
   return ['PluginCreditType' => PluginCreditType::getTypeName(Session::getPluralNumber())];
}

function plugin_credit_get_datas(NotificationTargetTicket $target) {

   global $DB;

   $target->data['##lang.credit.voucher##'] = PluginCreditEntity::getTypeName();
   $target->data['##lang.credit.used##']    = __('Quantity consumed', 'credit');
   $target->data['##lang.credit.left##']    = __('Quantity remaining', 'credit');

   $id = $target->data['##ticket.id##'];
   $ticket=new Ticket();
   $ticket->getFromDB($id);
   $entity_id=$ticket->fields['entities_id'];

   $query = "SELECT
         `glpi_plugin_credit_entities`.`name`,
         `glpi_plugin_credit_entities`.`quantity`,
         (SELECT SUM(`glpi_plugin_credit_tickets`.`consumed`) FROM `glpi_plugin_credit_tickets` WHERE `glpi_plugin_credit_tickets`.`plugin_credit_entities_id` = `glpi_plugin_credit_entities`.`id` AND `glpi_plugin_credit_tickets`.`tickets_id` = {$id}) AS `consumed_on_ticket`,
         (SELECT SUM(`glpi_plugin_credit_tickets`.`consumed`) FROM `glpi_plugin_credit_tickets` WHERE `glpi_plugin_credit_tickets`.`plugin_credit_entities_id` = `glpi_plugin_credit_entities`.`id`) AS  `consumed_total`
      FROM `glpi_plugin_credit_entities`
      WHERE `is_active`=1 and `entities_id`={$entity_id}";

   foreach ($DB->request($query) as $credit) {
      $target->data["credit.ticket"][] = [
         '##credit.voucher##' => $credit['name'],
         '##credit.used##'    => (int)$credit['consumed_on_ticket'],
         '##credit.left##'    => (int)$credit['quantity'] - (int)$credit['consumed_total'],
      ];
   }
}

function plugin_credit_redefine_api_schemas(array $data): array {
    // Handle modifications to existing schemas
    foreach ($data['schemas'] as &$schema) {
        if (!isset($schema['x-itemtype'])) {
            continue;
        }
        if ($schema['x-itemtype'] === Entity::class) {
            $schema['properties']['credits'] = [
                'type' => Schema::TYPE_ARRAY,
                'items' => [
                    'type' => Schema::TYPE_OBJECT,
                    'x-itemtype' => PluginCreditEntity::class,
                    'x-full-schema' => 'Credit',
                    'x-join' => [
                        'table' => PluginCreditEntity::getTable(),
                        'fkey' => 'id',
                        'field' => 'entities_id',
                        'primary-property' => 'id'
                    ],
                    'properties' => [
                        'id' => [
                            'type' => Schema::TYPE_INTEGER,
                            'format' => Schema::FORMAT_INTEGER_INT64,
                            'x-readonly' => true,
                        ],
                        'name' => ['type' => Schema::TYPE_STRING],
                        'is_recursive' => ['type' => Schema::TYPE_BOOLEAN],
                        'is_active' => ['type' => Schema::TYPE_BOOLEAN],
                        'type' => AbstractController::getDropdownTypeSchema(class: PluginCreditType::class, full_schema: 'PluginCreditType'),
                        'date_begin' => [
                            'type' => Schema::TYPE_STRING,
                            'format' => Schema::FORMAT_STRING_DATE,
                            'x-field' => 'begin_date'
                        ],
                        'date_end' => [
                            'type' => Schema::TYPE_STRING,
                            'format' => Schema::FORMAT_STRING_DATE,
                            'x-field' => 'end_date'
                        ],
                        'quantity' => ['type' => Schema::TYPE_INTEGER],
                        'over_consumption_allowed' => [
                            'type' => Schema::TYPE_BOOLEAN,
                            'x-field' => 'overconsumption_allowed'
                        ],
                    ]
                ]
            ];
            $schema['properties']['credits_config'] = [
                'type' => Schema::TYPE_OBJECT,
                'x-itemtype' => PluginCreditEntityConfig::class,
                'x-full-schema' => 'CreditEntityConfig',
                'x-join' => [
                    'table' => PluginCreditEntityConfig::getTable(),
                    'fkey' => 'id',
                    'field' => 'entities_id',
                    'primary-property' => 'id'
                ],
                'properties' => [
                    'id' => [
                        'type' => Schema::TYPE_INTEGER,
                        'format' => Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'consume_for_followups' => ['type' => Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_followups'],
                    'consume_for_tasks' => ['type' => Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_tasks'],
                    'consume_for_solutions' => ['type' => Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_solutions'],
                    'followups_credit' => AbstractController::getDropdownTypeSchema(
                        class: PluginCreditEntity::class,
                        field: 'plugin_credit_entities_id_followups',
                        full_schema: 'Credit'
                    ),
                    'tasks_credit' => AbstractController::getDropdownTypeSchema(
                        class: PluginCreditEntity::class,
                        field: 'plugin_credit_entities_id_tasks',
                        full_schema: 'Credit'
                    ),
                    'solutions_credit' => AbstractController::getDropdownTypeSchema(
                        class: PluginCreditEntity::class,
                        field: 'plugin_credit_entities_id_solutions',
                        full_schema: 'Credit'
                    ),
                ]
            ];
        } else if ($schema['x-itemtype'] === Ticket::class) {
            $schema['properties']['credits_consumed'] = [
                'type' => Schema::TYPE_ARRAY,
                'items' => [
                    'type' => Schema::TYPE_OBJECT,
                    'x-itemtype' => PluginCreditTicket::class,
                    'x-full-schema' => 'CreditConsumption',
                    'x-join' => [
                        'table' => PluginCreditTicket::getTable(),
                        'fkey' => 'id',
                        'field' => 'tickets_id',
                        'primary-property' => 'id'
                    ],
                    'properties' => [
                        'id' => [
                            'type' => Schema::TYPE_INTEGER,
                            'format' => Schema::FORMAT_INTEGER_INT64,
                            'x-readonly' => true,
                        ],
                        'credit' => [
                            'type' => Schema::TYPE_OBJECT,
                            'x-itemtype' => PluginCreditTicket::class,
                            'x-full-schema' => 'Credit',
                            'properties' => [
                                'id' => [
                                    'type' => Schema::TYPE_INTEGER,
                                    'format' => Schema::FORMAT_INTEGER_INT64,
                                    'x-readonly' => true,
                                ],
                                'name' => ['type' => Schema::TYPE_STRING],
                            ]
                        ],
                        'date_creation' => [
                            'type' => Schema::TYPE_STRING,
                            'format' => Schema::FORMAT_STRING_DATE_TIME,
                            'x-readonly' => true,
                        ],
                        'consumed' => ['type' => Schema::TYPE_INTEGER],
                        'user' => AbstractController::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
                    ]
                ]
            ];
            $schema['properties']['credits_config'] = [
                'type' => Schema::TYPE_OBJECT,
                'x-itemtype' => PluginCreditTicketConfig::class,
                'x-full-schema' => 'CreditTicketConfig',
                'x-join' => [
                    'table' => PluginCreditTicketConfig::getTable(),
                    'fkey' => 'id',
                    'field' => 'tickets_id',
                    'primary-property' => 'id'
                ],
                'properties' => [
                    'id' => [
                        'type' => Schema::TYPE_INTEGER,
                        'format' => Schema::FORMAT_INTEGER_INT64,
                        'x-readonly' => true,
                    ],
                    'followups_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, field: 'plugin_credit_entities_id_followups', full_schema: 'Credit'),
                    'tasks_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, field: 'plugin_credit_entities_id_tasks', full_schema: 'Credit'),
                    'solutions_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, field: 'plugin_credit_entities_id_solutions', full_schema: 'Credit'),
                ]
            ];
        }
    }

    return $data;
}
