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

use Com\Tecnick\Color\Model\Template;
use Glpi\Application\View\TemplateRenderer;

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

class PluginCreditTicket extends CommonDBTM
{
    public static $rightname = 'ticket';

    public static function getTypeName($nb = 0)
    {
        return _sn('Credit voucher', 'Credit vouchers', $nb, 'credit');
    }

    public static function getIcon()
    {
        return 'ti ti-coins';
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM) {
            $nb = self::countForItem($item);
        } else {
            $nb = 0;
        }
        if ($item instanceof Ticket) {
            if ($_SESSION['glpishow_count_on_tabs']) {
                return self::createTabEntry(self::getTypeName($nb), $nb);
            } else {
                return self::getTypeName($nb);
            }
        } else {
            return self::getTypeName($nb);
        }
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Ticket) {
            self::showForTicket($item);
        }
        return true;
    }

    /**
     * @param $item    CommonDBTM object
     */
    public static function countForItem(CommonDBTM $item)
    {
        return countElementsInTable(self::getTable(), ['tickets_id' => $item->getID()]);
    }

    /**
     * Get all credit vouchers for a ticket.
     *
     * @param $ID           integer     tickets ID
     * @return array of vouchers
     */
    public static function getAllForTicket($ID): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $request = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'tickets_id' => $ID,
            ],
            'ORDER'  => ['id DESC'],
        ];

        $vouchers = [];
        foreach ($DB->request($request) as $data) {
            $vouchers[$data['id']] = $data;
        }

        return $vouchers;
    }


    /**
     * Get all tickets for a credit vouchers.
     *
     * @param $ID           integer     plugin_credit_entities_id ID
     * @return array of vouchers
     */
    public static function getAllForCreditEntity($ID): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $request = [
            'SELECT' => '*',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'plugin_credit_entities_id' => $ID
            ],
            'ORDER'  => ['id DESC'],
        ];

        $tickets = [];
        foreach ($DB->request($request) as $data) {
            $tickets[$data['id']] = $data;
        }

        return $tickets;
    }

    /**
     * Get consumed tickets for credit entity entry
     *
     * @param $ID integer PluginCreditEntity id
     */
    public static function getConsumedForCreditEntity($ID)
    {
        /** @var DBmysql $DB */
        global $DB;

        $tot   = 0;

        $request = [
            'SELECT' => ['SUM' => 'consumed as sum'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'plugin_credit_entities_id' => $ID
            ],
        ];

        $result = $DB->request($request);
        if ($row = $result->current()) {
            $tot = $row['sum'];
        }

        return $tot;
    }

    /**
     * Show credit vouchers consumed for a ticket
     *
     * @param $ticket Ticket object
     */
    public static function showForTicket(Ticket $ticket)
    {
        /** @var DBmysql $DB */
        global $DB;

        $ID = $ticket->getField('id');
        if (!$ticket->can($ID, READ)) {
            return false;
        }

        $canedit = false;
        if (Session::haveRight(Entity::$rightname, UPDATE)) {
            $canedit = true; // Entity admin has always right to update credits
        } elseif (
            $ticket->canEdit($ID)
            && !in_array($ticket->fields['status'], array_merge(Ticket::getSolvedStatusArray(), Ticket::getClosedStatusArray()))
        ) {
            $canedit = true;
        }

        $number = self::countForItem($ticket);
        $rand   = mt_rand();

        $entries = [];

        if ($number) {
            if ($canedit) {
                $massiveactionparams = [
                    'num_displayed'    => min($number, $_SESSION['glpilist_limit']),
                    'container'        => 'mass' . __CLASS__ . $rand,
                    'itemtype'         => PluginCreditTicket::class,
                    'specific_actions' => [
                        'update'    => _x('button', 'Update'),
                        'purge'     => _x('button', 'Delete permanently')
                    ]
                ];

                foreach (self::getAllForTicket($ID) as $data) {
                    $credit_entity = new PluginCreditEntity();
                    $credit_entity->getFromDB($data['plugin_credit_entities_id']);

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
                        'id'                        => $data['id'],
                        'name'                      => $credit_entity->getName(),
                        'plugin_credit_types_id'    => $data['plugin_credit_types_id'],
                        'date_creation'             => $data['date_creation'],
                        'users_id'                  => Session::haveRight('user', READ) == true ? getUserLink($data["users_id"]) : getUserName($data["users_id"]),
                        'consumed'                  => $data['consumed'],
                        'itemtype'                  => PluginCreditTicket::class,
                    ]);
                }
            }
        }
        PluginCreditTicketConfig::showForTicket($ticket);

        TemplateRenderer::getInstance()->display('@credit/tickets/form.html.twig', [
            'rand'                  => $rand,
            'entity_id'             => $ticket->getEntityID(),
            'type_name'             => self::getTypeName(2),
            'creditentityclass'     => PluginCreditEntity::class,
            'form_url'              => self::getFormUrl(),
            'conditions'            => PluginCreditEntity::getActiveFilter(),
            'canedit'               => $canedit,
            'ID'                    => $ID,
            'entries'               => $entries,
            'massiveactionparams'   => $massiveactionparams ?? []
        ]);

        $Entity = new Entity();
        $Entity->getFromDB($ticket->fields['entities_id']);
        PluginCreditEntity::showForItemtype($Entity, 'Ticket');
    }

    /**
     * Display voucher consumption fields at the end of a ticket processing form.
     *
     * @param array $params Array with "item" and "options" keys
     *
     * @return void
     */
    public static function displayVoucherInTicketProcessingForm($params)
    {
        $item = $params['item'];

        if ($item instanceof Ticket) {
            echo PluginCreditTicketConfig::showForTicket($item, true);
            return;
        }

        if (
            !($item instanceof ITILSolution)
            && !($item instanceof TicketTask)
            && !($item instanceof ITILFollowup)
        ) {
            return;
        }

        if (!$item->isNewItem()) {
           // Do not display fields in item update form.
            return;
        }

        $ticket = null;
        if (
            array_key_exists('parent', $params['options'])
            && $params['options']['parent'] instanceof Ticket
        ) {
            // Ticket can be found in `parent` option for TicketTask.
            $ticket = $params['options']['parent'];
        } elseif (
            array_key_exists('item', $params['options'])
            && $params['options']['item'] instanceof Ticket
        ) {
            // Ticket can be found in `'item'` option for ITILFollowup and ITILSolution.
            $ticket = $params['options']['item'];
        }

        // No parent of type Ticket found, parent might we might be an another
        // type of CommonITILObject so we should exit here
        if ($ticket === null) {
            return;
        }

        $canedit = $ticket->canEdit($ticket->getID());
        if (
            in_array($ticket->fields['status'], Ticket::getSolvedStatusArray())
            || in_array($ticket->fields['status'], Ticket::getClosedStatusArray())
        ) {
            $canedit = false;
        }

        $entity_config = new PluginCreditEntityConfig();
        $entity_config->getFromDBByCrit(['entities_id' => $ticket->getEntityID()]);
        $consume = false;
        if ($item instanceof ITILSolution) {
            $consume = $entity_config->fields['consume_voucher_for_solutions'] ?? 0;
        } elseif ($item instanceof TicketTask) {
            $consume = $entity_config->fields['consume_voucher_for_tasks'] ?? 0;
        } else {
            $consume = $entity_config->fields['consume_voucher_for_followups'] ?? 0;
        }

        $rand = mt_rand();

        if ($canedit) {
            //get default value for ticket
            $default_credit = PluginCreditTicketConfig::getDefaultForTicket($ticket->getID(), $item->getType());
            if ($default_credit == 0) {
                //get default value for entity
                $default_credit = PluginCreditEntityConfig::getDefaultForEntityAndType($ticket->getEntityID(), $item->getType());
            }

            if ($default_credit != 0) {
                $max = PluginCreditEntity::getMaximumConsumptionForCredit($default_credit);
            }
        }

        TemplateRenderer::getInstance()->display('@credit/tickets/consume.html.twig', [
            'rand'                  => $rand,
            'consume'               => $consume,
            'default_credit'        => $default_credit ?? 0,
            'default_credit_max'    => $max ?? 0,
            'entity_id'             => $ticket->getEntityID(),
            'type_name'             => self::getTypeName(2),
            'condition'             => PluginCreditEntity::getActiveFilter(),
            'creditentityclass'     => PluginCreditEntity::class,
            'plugin_credit_geturl'  => plugin_credit_geturl(),
        ]);
    }

    /**
     * Display the detailled list of tickets on which consumption is declared.
     *
     * @param int $ID plugin_credit_entities_id
     */
    public static function displayConsumed($ID)
    {
        $consumed_credits = self::getConsumedForCreditEntity($ID);
        $tickets_data = [];

        if ($consumed_credits > 0) {
            foreach (self::getAllForCreditEntity($ID) as $data) {
                $Ticket = new Ticket();
                $Ticket->getFromDB($data['tickets_id']);

                $itilcat = new ITILCategory();
                $category = __s('None');
                if ($itilcat->getFromDB($Ticket->fields['itilcategories_id'])) {
                    $category = $itilcat->getName(['comments' => true]);
                }

                $showuserlink = Session::haveRight('user', READ) ? 1 : 0;

                $tickets_data[] = [
                    'ticket_link' => $Ticket->getLink(['linkoption' => 'target="_blank"']),
                    'status' => Ticket::getStatus($Ticket->fields['status']),
                    'type' => Ticket::getTicketTypeName($Ticket->fields['type']),
                    'category' => $category,
                    'date_creation' => $data["date_creation"],
                    'username' => getUserName($data["users_id"], $showuserlink),
                    'consumed' => $data['consumed'],
                ];
            }
        }

        TemplateRenderer::getInstance()->display('@credit/tickets/consumed_details.html.twig', [
            'consumed_credits' => $consumed_credits,
            'tickets_data' => $tickets_data,
        ]);
    }

    /**
     * Test if consumed voucher is selected and add them.
     *
     * @param CommonDBTM $item Created item
     *
     * @return void
     */
    public static function consumeVoucher(CommonDBTM $item)
    {
        if (!count($item->input)) {
            return;
        }

        $ticketId = null;
        if (array_key_exists('tickets_id', $item->fields)) {
            // Ticket ID can be found in `tickets_id` field for TicketTask.
            $ticketId = $item->fields['tickets_id'];
        } else if (
            array_key_exists('itemtype', $item->fields)
             && array_key_exists('items_id', $item->fields)
             && 'Ticket' == $item->fields['itemtype']
        ) {
            // Ticket ID can be found in `items_id` field for ITILFollowup and ITILSolution.
            $ticketId = $item->fields['items_id'];
        }

        $ticket = new Ticket();
        if (null === $ticketId || !$ticket->getFromDB($ticketId)) {
            return;
        }

        if (
            !is_numeric(Session::getLoginUserID(false))
            || !Session::haveRightsOr('ticket', [Ticket::STEAL, Ticket::OWN])
        ) {
            return;
        }

        if (
            !isset($item->input['plugin_credit_consumed_voucher'])
            || $item->input['plugin_credit_consumed_voucher'] != 1
        ) {
            return;
        }

        if (
            !isset($item->input['plugin_credit_entities_id'])
            || $item->input['plugin_credit_entities_id'] == 0
        ) {
            Session::addMessageAfterRedirect(
                __s('You must provide a credit voucher', 'credit'),
                true,
                ERROR
            );
            return;
        }

        $credit_ticket = new self();

        $credit_entity = new PluginCreditEntity();
        $credit_entity->getFromDB($item->input['plugin_credit_entities_id']);

        $quantity_sold      = (int)$credit_entity->fields['quantity'];
        $quantity_consumed  = $credit_ticket->getConsumedForCreditEntity($item->input['plugin_credit_entities_id']);
        $quantity_remaining = max(0, $quantity_sold - $quantity_consumed);

        if (0 !== $quantity_sold && $quantity_remaining < $item->input['plugin_credit_quantity']) {
            if ($credit_entity->getField('overconsumption_allowed')) {
                Session::addMessageAfterRedirect(
                    sprintf(
                        __s('Quantity consumed exceeds remaining credits: %d', 'credit'),
                        $quantity_remaining
                    ),
                    true,
                    WARNING
                );
            } else {
                Session::addMessageAfterRedirect(
                    sprintf(
                        __s('Quantity consumed exceeds remaining credits: %d', 'credit'),
                        $quantity_remaining
                    ),
                    true,
                    ERROR
                );
                return;
            }
        }

        $input = [
            'tickets_id'                => $ticket->getID(),
            'plugin_credit_entities_id' => $item->input['plugin_credit_entities_id'],
            'consumed'                  => $item->input['plugin_credit_quantity'],
            'users_id'                  => Session::getLoginUserID(),
        ];
        if ($credit_ticket->add($input)) {
            Session::addMessageAfterRedirect(
                __s('Credit voucher successfully added.', 'credit'),
                true,
                INFO
            );
        }
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => 881,
            'table'    => self::getTable(),
            'field'    => 'date_creation',
            'name'     => __s('Date consumed', 'credit'),
            'datatype' => 'date',
        ];

        $tab[] = [
            'id'       => 882,
            'table'    => self::getTable(),
            'field'    => 'consumed',
            'name'     => __s('Quantity consumed', 'credit'),
            'datatype' => 'number',
            'min'      => 1,
            'max'      => 1000000,
            'step'     => 1,
            'toadd'    => [0 => __s('Unlimited')],
        ];

        $tab[] = [
            'id'       => 883,
            'table'    => PluginCreditEntity::getTable(),
            'field'    => 'name',
            'name'     => PluginCreditEntity::getTypeName(Session::getPluralNumber()),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    /**
     * Install all necessary table for the plugin
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
                    `tickets_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `plugin_credit_entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `date_creation` timestamp NULL DEFAULT NULL,
                    `consumed` int NOT NULL DEFAULT '0',
                    `users_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`),
                    KEY `tickets_id` (`tickets_id`),
                    KEY `plugin_credit_entities_id` (`plugin_credit_entities_id`),
                    KEY `date_creation` (`date_creation`),
                    KEY `consumed` (`consumed`),
                    KEY `users_id` (`users_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);
        } else {
            // Fix #1 in 1.0.1 : change tinyint to int for tickets_id
            $migration->changeField($table, 'tickets_id', 'tickets_id', "int {$default_key_sign} NOT NULL DEFAULT 0");

            // Change tinyint to int
            $migration->changeField(
                $table,
                'plugin_credit_entities_id',
                'plugin_credit_entities_id',
                "int {$default_key_sign} NOT NULL DEFAULT 0"
            );
            $migration->changeField($table, 'users_id', 'users_id', "int {$default_key_sign} NOT NULL DEFAULT 0");

            //execute the whole migration
            $migration->executeMigration();
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
}
