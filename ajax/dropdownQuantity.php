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

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

/** @var DBmysql $DB */
global $DB;

if (isset($_POST["entity"])) {
    $entity_query = [
        'SELECT' => ['overconsumption_allowed', 'quantity'],
        'FROM'   => 'glpi_plugin_credit_entities',
        'WHERE'  => [
            'id' => $_POST['entity'],
        ],
    ];
    $entity_result = $DB->request($entity_query)->current();

    $overconsumption_allowed = $entity_result['overconsumption_allowed'];
    $quantity_sold           = (int)$entity_result['quantity'];

    if (0 !== $quantity_sold && !$overconsumption_allowed) {
        $ticket_query = [
            'SELECT' => [
                'SUM' => 'consumed AS consumed_total',
            ],
            'FROM'   => 'glpi_plugin_credit_tickets',
            'WHERE'  => [
                'plugin_credit_entities_id' => $_POST['entity'],
            ],
        ];
        $ticket_result = $DB->request($ticket_query)->current();

        $consumed = (int)$ticket_result['consumed_total'];
        $max      = max(0, $quantity_sold - $consumed);

        Dropdown::showNumber("plugin_credit_quantity", ['value'   => '',
            'min'     => 0,
            'max'     => $max,
            'step'    => 1,
        ]);
    } else {
        Dropdown::showNumber("plugin_credit_quantity", ['value'   => '',
            'min'     => 0,
            'max'     => 1000000,
            'step'    => 1,
        ]);
    }
}
