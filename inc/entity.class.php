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

use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;

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

class PluginCreditEntity extends CommonDBTM
{
    public static $rightname = 'entity';

    public static function getTypeName($nb = 0)
    {
        return _n('Credit voucher', 'Credit vouchers', $nb, 'credit');
    }

    public static function getIcon()
    {
        return 'ti ti-coins';
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public function canCreateItem(): bool
    {
        return true;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Entity) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = self::countForItem($item);
            }
            return self::createTabEntry(self::getTypeName($nb), $nb);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Entity) {
            self::showForItemtype($item);
        }
        return true;
    }

    /**
     * @param $item    CommonDBTM object
     */
    public static function countForItem(CommonDBTM $item)
    {
        return countElementsInTable(
            self::getTable(),
            getEntitiesRestrictCriteria('', '', $item->getID(), true)
        );
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input['name']) || $input['name'] == '') {
            Session::addMessageAfterRedirect(__('Credit voucher name is mandatory.', 'credit'));
            return false;
        }

        if (isset($input['end_date']) && $input['end_date'] != '') {
            $input['end_date'] .= ' 23:59:59';
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        if (isset($input['name']) && strlen($input['name']) === 0) {
            Session::addMessageAfterRedirect(__('Credit voucher name is mandatory.', 'credit'));
            return false;
        }

        if (isset($input['end_date']) && $input['end_date'] != '') {
            $input['end_date'] .= ' 23:59:59';
        }

        return $input;
    }

    public function post_purgeItem()
    {
        $pc_ticket = new PluginCreditTicket();
        $pc_ticket->deleteByCriteria([
            'plugin_credit_entities_id' => $this->getID()
        ]);
    }

    /**
     * Get all credit vouchers for entity.
     *
     * @param $ID           integer     entities ID
     * @param $sqlfilter    array       to add a SQL filter (default [])
     * @return array of vouchers
     */
    public static function getAllForEntity($ID, $sqlfilter = []): array
    {
        /** @var DBmysql $DB */
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
     * Show credit vouchers of an entity
     *
     * @param $entity object Entity
     * @param $itemtype string Entity or Ticket
     */
    public static function showForItemtype(Entity $entity, $itemtype = 'Entity')
    {
        $ID = $entity->getField('id');
        if (!$entity->can($ID, READ)) {
            return;
        }

        $canedit = $itemtype === 'Entity' && $entity->canEdit($ID);

        if ($itemtype === 'Entity') {
            PluginCreditEntityConfig::showEntityConfigForm($entity->getID());
        }

        $columns = [
            'name'                      => __('Name'),
            'plugin_credit_types_id'    => __('Type'),
            'is_active'                 => __('Active'),
            'begin_date'                => __('Start date'),
            'end_date'                  => __('End date'),
            'quantity'                  => __('Quantity sold', 'credit'),
            'quantity_consumed'         => __('Quantity consumed', 'credit'),
            'quantity_remaining'        => __('Quantity remaining', 'credit'),
            'overconsumption_allowed'   => __('Allow overconsumption', 'credit'),
            'low_credit_alert'          => __('Low credits alert', 'credit'),
            'entities_id'               => __('Entity'),
            'is_recursive'              => __('Child entities')
        ];

        $sqlfilter = [];
        if ($itemtype == 'Ticket') {
            $sqlfilter = [
                'is_active' => '1'
            ];
        }

        $entries = [];
        foreach (self::getAllForEntity($ID, $sqlfilter) as $data) {
            $quantity_sold = (int)$data['quantity'];
            if (0 === $quantity_sold) {
                $quantity_sold = __('Unlimited');
            }

            $item = new self();
            $item = $item->getById($data['id']);

            if (!empty($data['plugin_credit_types_id'])) {
                $type = new PluginCreditType();
                $type = $type->getById($data['plugin_credit_types_id']);
                if ($type) {
                    $data['plugin_credit_types_id'] = $type->getLink();
                }
            } else {
                $data['plugin_credit_types_id'] = '';
            }

            $entries[] = array_merge($data, [
                'name'                      => $item->getLink(),
                'quantity'                  => $quantity_sold,
                'itemtype'                  => PluginCreditEntity::class,
                'low_credit_alert'          => $data['low_credit_alert'] == -1 ? __('Disabled') : $data['low_credit_alert'] . '%',
                'quantity_consumed'         => PluginCreditEntity::getConsumedForCredit($data['id']),
                'quantity_remaining'        => $data['quantity'] > 0 ? $data['quantity'] - PluginCreditEntity::getConsumedForCredit($data['id']) : 'Unlimited',
                'entities_id'               => Entity::badgeCompletenameLink($entity),
            ]);
        }


        $rand  = mt_rand();
        $nb = count($entries);
        $massiveactionparams = [
            'num_displayed'    => min($nb, $_SESSION['glpilist_limit']),
            'container'        => 'mass' . __CLASS__ . $rand,
            'itemtype'         => PluginCreditEntity::class,
        ];

        if ($itemtype === 'Entity' && $canedit) {
            TemplateRenderer::getInstance()->display('@credit/creditentity.hmtl.twig', [
                'form_url'              => self::getFormUrl(),
                'credittypeclass'       => PluginCreditType::class,
                'columns'               => $columns,
                'entity_id'             => $ID,
                'entries'               => $entries,
                'canedit'               => $canedit,
                'massiveactionparams'   => $massiveactionparams,
            ]);
        }
    }

    public function rawSearchOptions()
    {
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
            'name'     => PluginCreditType::getTypeName(),
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
            'id'      => 997,
            'table'   => self::getTable(),
            'field'   => 'low_credit_alert',
            'name'    => __('Low credit alert', 'credit'),
            'datatype' => 'number',
            'min'     => 0,
            'max'     => 50,
            'step'    => 10,
            'toadd'   => [-1 => __('Disabled')],
            'unit'    => '%'
        ];

        return $tab;
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'creditexpired':
                return [
                    'description' => __('Expiration date', 'credit'),
                    'parameter'   => __('Notice (in days)', 'credit')
                ];
            case 'lowcredits':
                return [
                    'description' => __('Low credits', 'credit'),
                ];
        }
        return [];
    }

    public static function cronCreditExpired($task)
    {
        /**
         * @var array $CFG_GLPI
         * @var DBmysql $DB
         */
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

    public static function cronLowCredits($task)
    {
        /**
         * @var array $CFG_GLPI
         * @var DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        if (!$CFG_GLPI['use_notifications']) {
            return 0;
        }

        $alert = new Alert();
        $credits_iterator = $DB->request(
            [
                'SELECT' => [
                    'glpi_plugin_credit_entities.id',
                    'glpi_plugin_credit_entities.name',
                    'glpi_plugin_credit_entities.quantity',
                    'glpi_plugin_credit_entities.low_credit_alert',
                    new QueryExpression('SUM(glpi_plugin_credit_tickets.consumed) AS quantity_consumed')
                ],
                'FROM' => 'glpi_plugin_credit_entities',
                'LEFT JOIN' => [
                    'glpi_plugin_credit_tickets' => [
                        'ON' => [
                            'glpi_plugin_credit_tickets' => 'plugin_credit_entities_id',
                            'glpi_plugin_credit_entities' => 'id',
                        ]
                    ]
                ],
                'WHERE' => [
                    'glpi_plugin_credit_entities.is_active' => 1,
                ],
                'GROUPBY' => 'glpi_plugin_credit_entities.id',
                'HAVING' => new QueryExpression('glpi_plugin_credit_entities.quantity - quantity_consumed <= (glpi_plugin_credit_entities.quantity * glpi_plugin_credit_entities.low_credit_alert) / 100')
            ]
        );

        foreach ($credits_iterator as $credit_data) {
            $task->addVolume(1);
            $task->log(
                sprintf(
                    'Low credit for %s',
                    $credit_data['name'],
                )
            );

            $credit = new PluginCreditEntity();
            $credit->getFromDB($credit_data['id']);

            NotificationEvent::raiseEvent('lowcredits', $credit);

            $input = [
                'type' => Alert::END,
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
    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL auto_increment,
                    `name` varchar(255) DEFAULT NULL,
                    `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `is_recursive` tinyint NOT NULL DEFAULT '0',
                    `is_active` tinyint NOT NULL DEFAULT '0',
                    `plugin_credit_types_id` tinyint {$default_key_sign} NOT NULL DEFAULT '0',
                    `begin_date` timestamp NULL DEFAULT NULL,
                    `end_date` timestamp NULL DEFAULT NULL,
                    `quantity` int NOT NULL DEFAULT '0',
                    `overconsumption_allowed` tinyint NOT NULL DEFAULT '0',
                    `low_credit_alert` int DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `entities_id` (`entities_id`),
                    KEY `is_recursive` (`is_recursive`),
                    KEY `is_active` (`is_active`),
                    KEY `plugin_credit_types_id` (`plugin_credit_types_id`),
                    KEY `begin_date` (`begin_date`),
                    KEY `end_date` (`end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        } else {
            // 1.5.0
            $migration->addField($table, 'overconsumption_allowed', 'bool', ['update' => "1"]);

            // 1.9.0
            $migration->addField($table, 'is_recursive', 'bool');
            $migration->addKey($table, 'is_recursive');

            // 1.10.0
            $migration->dropField($table, 'is_default'); // Was created during dev phase of 1.10.0, then removed

            // 1.13.2
            $migration->addField($table, 'low_credit_alert', 'int', ['update' => "NULL"]);
        }

        return true;
    }

    /**
     * Uninstall previously installed table of the plugin
     *
     * @return boolean True if success
     */
    public static function uninstall(Migration $migration)
    {
        $table = self::getTable();
        $migration->dropTable($table);

        return true;
    }

    public static function getActiveFilter()
    {
        /** @var DBmysql $DB */
        global $DB;
        return [
            'glpi_plugin_credit_entities.is_active' => 1,
            'OR' => [
                'glpi_plugin_credit_entities.end_date' => null,
                new QueryExpression(
                    sprintf(
                        'NOW() < %s',
                        $DB->quoteName('glpi_plugin_credit_entities.end_date')
                    )
                ),
            ],
        ];
    }

    public static function getMaximumConsumptionForCredit(int $credit_id)
    {
        /** @var DBmysql $DB */
        global $DB;

        $entity_query = [
            'SELECT' => ['overconsumption_allowed', 'quantity'],
            'FROM'   => 'glpi_plugin_credit_entities',
            'WHERE'  => [
                'id' => $credit_id,
            ],
        ];
        $entity_result = $DB->request($entity_query)->current();
        $overconsumption_allowed = $entity_result['overconsumption_allowed'];
        $quantity_sold           = (int)$entity_result['quantity'];

        if (0 !== $quantity_sold && !$overconsumption_allowed) {
            $consumed = self::getConsumedForCredit($credit_id);
            $max      = max(0, $quantity_sold - $consumed);

            return $max;
        } else {
            return 100000;
        }
    }

    /**
     * Get the total consumption for a credit vouchers.
     *
     * @param int $credit_id ID of the credit vouchers
     *
     * @return int Total consumption
     */
    public static function getConsumedForCredit(int $credit_id)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ticket_query = [
            'SELECT' => [
                'SUM' => 'consumed AS consumed_total',
            ],
            'FROM'   => 'glpi_plugin_credit_tickets',
            'WHERE'  => [
                'plugin_credit_entities_id' => $credit_id,
            ],
        ];

        $ticket_result = $DB->request($ticket_query)->current();

        return (int)$ticket_result['consumed_total'];
    }
}
