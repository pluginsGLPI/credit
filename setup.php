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

define('PLUGIN_CREDIT_VERSION', '1.14.0');

// Minimal GLPI version, inclusive
define("PLUGIN_CREDIT_MIN_GLPI", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_CREDIT_MAX_GLPI", "10.0.99");

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_credit()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $plugin = new Plugin();

    $PLUGIN_HOOKS['csrf_compliant']['credit'] = true;

    if (Session::getLoginUserID() && $plugin->isActivated('credit')) {
        Plugin::registerClass(
            'PluginCreditEntity',
            [
                'notificationtemplates_types' => true,
                'addtabon'                    => 'Entity'
            ]
        );

        if (Session::haveRightsOr('ticket', [Ticket::STEAL, Ticket::OWN])) {
            Plugin::registerClass('PluginCreditTicket', ['addtabon' => 'Ticket']);

            $PLUGIN_HOOKS['post_item_form']['credit'] = [
                'PluginCreditTicket',
                'displayVoucherInTicketProcessingForm'
            ];

            $PLUGIN_HOOKS['item_add']['credit'] = [
                'ITILFollowup'   => ['PluginCreditTicket', 'consumeVoucher'],
                'ITILSolution'   => ['PluginCreditTicket', 'consumeVoucher'],
                'TicketTask'     => ['PluginCreditTicket', 'consumeVoucher'],
                'Ticket'         => ['PluginCreditTicketConfig', 'updateConfig'],
            ];
            // Update config on 'pre_item_update' as only changing these fields in ticket form will not trigger 'item_update'.
            $PLUGIN_HOOKS['pre_item_update']['credit'] = [
                'Ticket' => ['PluginCreditTicketConfig', 'updateConfig'],
            ];
        }
        $PLUGIN_HOOKS['add_javascript']['credit'] = [
            'js/credit.js'
        ];
        $PLUGIN_HOOKS['item_get_datas']['credit'] = ['NotificationTargetTicket' => 'plugin_credit_get_datas'];
        $PLUGIN_HOOKS['dashboard_cards']['credit'] = 'plugin_credit_dashboardcards';
        $PLUGIN_HOOKS['dashboard_filters']['credit'] = [PluginCreditDashboardFilters::class];
    }

    Plugin::registerClass(PluginCreditProfile::class, ['addtabon' => Profile::class]);
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_credit()
{
    return [
        'name'           => _n('Credit voucher', 'Credit vouchers', 2, 'credit'),
        'version'        => PLUGIN_CREDIT_VERSION,
        'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
        'license'        => 'GPLv3',
        'homepage'       => 'https://github.com/pluginsGLPI/credit',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_CREDIT_MIN_GLPI,
                'max' => PLUGIN_CREDIT_MAX_GLPI,
            ]
        ]
    ];
}
