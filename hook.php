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

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_credit_install()
{
    $migration = new Migration(PLUGIN_CREDIT_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
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
function plugin_credit_uninstall()
{
    $migration = new Migration(PLUGIN_CREDIT_VERSION);

    // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
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
function plugin_credit_getDropdown()
{
    return ['PluginCreditType' => PluginCreditType::getTypeName(Session::getPluralNumber())];
}

function plugin_credit_get_datas(NotificationTargetTicket $target)
{
    /** @var DBmysql $DB */
    global $DB;

    $target->data['##lang.credit.voucher##'] = PluginCreditEntity::getTypeName();
    $target->data['##lang.credit.used##']    = __('Quantity consumed', 'credit');
    $target->data['##lang.credit.left##']    = __('Quantity remaining', 'credit');

    $id = $target->data['##ticket.id##'];
    $ticket = new Ticket();
    $ticket->getFromDB($id);
    $entity_id = $ticket->fields['entities_id'];

    $it = new \DBmysqlIterator(null);
    $query = [
        'SELECT' => [
            'name',
            'quantity',
            'consumed_on_ticket' => new QueryExpression(
                sprintf(
                    '(%s)',
                    $it->buildQuery([
                        'SELECT' => [
                            'SUM' => [
                                'consumed',
                            ],
                        ],
                        'FROM' => [
                            'glpi_plugin_credit_tickets',
                        ],
                        'WHERE' => [
                            'plugin_credit_entities_id' => 'glpi_plugin_credit_entities.id',
                            'tickets_id' => $id,
                        ],
                    ])
                )
            ),
            'consumed_total' => new QueryExpression(
                sprintf(
                    '(%s)',
                    $it->buildQuery([
                        'SELECT' => [
                            'SUM' => [
                                'consumed',
                            ],
                        ],
                        'FROM' => [
                            'glpi_plugin_credit_tickets',
                        ],
                        'WHERE' => [
                            'plugin_credit_entities_id' => 'glpi_plugin_credit_entities.id',
                        ],
                    ])
                )
            ),
        ],
        'FROM' => [
            'glpi_plugin_credit_entities',
        ],
        'WHERE' => [
            'is_active' => 1,
            'entities_id' => $entity_id,
        ],
    ];

    foreach ($DB->request($query) as $credit) {
        $target->data["credit.ticket"][] = [
            '##credit.voucher##' => $credit['name'],
            '##credit.used##'    => (int)$credit['consumed_on_ticket'],
            '##credit.left##'    => (int)$credit['quantity'] - (int)$credit['consumed_total'],
        ];
    }
}
