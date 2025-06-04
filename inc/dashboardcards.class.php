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

use Glpi\Dashboard\Provider;

class PluginCreditDashboardCards extends CommonDBTM
{
    /**
     * Return cards
     * @return array
     */
    public static function getCards(): array
    {
        $cards = array();

        $cards["bn_count_credit"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Initial credit"),
            'provider' => PluginCreditDashboardCards::class . "::nbCredits",
            'filters' => [
                'credit'
            ]
        ];

        $cards["bn_credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Used credit"),
            'provider' => PluginCreditDashboardCards::class . "::nbCreditsUsed",
            'filters' => [
                'credit'
            ]
        ];

        $cards["bn_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Remaining credit"),
            'provider' => PluginCreditDashboardCards::class . "::nbCreditsRemaining",
            'filters' => [
                'credit'
            ]
        ];

        $cards["bn_percent_credit_remaining"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Proportion of credit remaining"),
            'provider' => PluginCreditDashboardCards::class . "::percentCreditsRemaining",
            'filters' => [
                'credit','entity'
            ]
        ];

        $cards["bn_percent_credit_used"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Proportion of credit used"),
            'provider' => PluginCreditDashboardCards::class . "::percentCreditsUsed",
            'filters' => [
                'credit'
            ]
        ];

        $cards["date_end_ticket"] = [
            'widgettype' => ["bigNumber"],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("End date of the contract"),
            'provider' => PluginCreditDashboardCards::class . "::getDateEndingCredit",
            'filters' => [
                'credit'
            ]
        ];

        $cards["credits_consumption_ticket"] = [
            'widgettype' => ['bars'],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Credit consumption by ticket"),
            'provider' => PluginCreditDashboardCards::class . "::getCreditsConsumption",
            'filters' => [
                'credit'
            ]
        ];

        $cards["credits_evolution_period"] = [
            'widgettype' => ['lines', 'bars', 'areas'],
            'itemtype' => "\\Credit",
            'group' => __('Credit'),
            'label' => __("Evolutions of credit consumption"),
            'provider' => PluginCreditDashboardCards::class . "::getCreditsEvolution",
            'filters' => [
                'credit'
            ]
        ];

        return $cards;
    }

    /**
     * @param array $params
     * @return array
     */
    public static function nbCredits(array $params = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $active_entity = Session::getActiveEntity();

        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'initial quantity of credit'
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'quantity'
                ],
                'FROM' => $t_table,
                'WHERE' => [
                    "$t_table.entities_id" => $active_entity
                ]

            ],
            Provider::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $nb_items = 0;
        foreach ($DB->request($criteria) as $id => $row) {
            $nb_items += $row['quantity'];
        }

        return [
            'number' => $nb_items,
            'url' => '',
            'label' => 'Initial quantity of credit',
            'icon' => $default_params['icon'],
            'alt' => $default_params['alt']
        ];
    }

    /**
     * Date format : MM/YY
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getDateEndingCredit(array $params = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $active_entity = Session::getActiveEntity();


        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'End date of the contract',
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();
        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    'end_date',
                ],
                'FROM' => $t_table,
                'WHERE' => [
                    "$t_table.entities_id" => $active_entity
                ]

            ],
            Provider::getFiltersCriteria($t_table, $params['apply_filters'])
        );


        $iterator = $DB->request($criteria);

        $result = $iterator->current();

        if (isset($result['end_date'])) {
            $date = date_create($result['end_date']);
            $date = date_format($date, 'm/y');
            return [
                'number' => $date,
                'url' => '',
                'label' => 'End date of the contract',
                'icon' => $default_params['icon'],
                'alt' => $default_params['alt']
            ];
        } else {
            return [
                'number' => 0,
                'url' => '',
                'label' => 'No end date',
                'icon' => $default_params['icon'],
                'alt' => $default_params['alt']
            ];
        }
    }

    /**
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsUsed(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'Quantity of credit used',
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);

        return [
            'number' => $tab['sum'],
            'url' => '',
            'label' => 'Quantity of credit used',
            'icon' => $default_params['icon'],
            'alt' => $default_params['alt']
        ];
    }

    /**
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function nbCreditsRemaining(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'Quantity of credit remaining',
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);

        $result = $tab['quantity'] - $tab['sum'];

        $result = $result < 0 ? 0 : $result;

        return [
            'number' => $result,
            'url' => '',
            'label' => 'Quantity of credit remaining',
            'icon' => $default_params['icon'],
            'alt' => $default_params['alt']
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public static function percentCreditsUsed(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'Proportion of credit used',
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);

        $result = $tab['quantity'] != 0 ? (($tab['sum']) / $tab['quantity']) * 100 : 0;

        return [
            'number' => round($result, 0, PHP_ROUND_HALF_DOWN) . '%',
            'url' => '',
            'label' => 'Proportion of credit used',
            'icon' => $default_params['icon'],
            'alt' => $default_params['alt']
        ];
    }

    /**
     * @param array $params
     * @return array
     */
    public static function percentCreditsRemaining(array $params = []): array
    {
        $default_params = [
            'label' => "",
            'icon' => self::getIconWallet(),
            'apply_filters' => [],
            'alt' => 'Proportion of credits remaining',
        ];
        $params = array_merge($default_params, $params);

        $tab = self::getCredits($params);

        $result = $tab['quantity'] != 0 ? (($tab['quantity'] - $tab['sum']) / $tab['quantity']) * 100 : 0;

        $result = $result < 0 ? 0 : $result;



        return [
            'number' => round($result, 0, PHP_ROUND_HALF_DOWN) . '%',
            'url' => '',
            'label' => 'Proportion of credits remaining',
            'icon' => $default_params['icon'],
            'alt' => $default_params['alt']
        ];
    }

    /**
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsConsumption(array $params = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $active_entity = Session::getActiveEntity();


        $default_params = [
            'label' => "",
            'icon' => "",
            'apply_filters' => [],
            'alt' => 'Quantity of credit consumed',
        ];
        $params = array_merge($default_params, $params);

        $t_table = PluginCreditEntity::getTable();
        $s_table = PluginCreditTicket::getTable();

        $criteria = array_merge_recursive(
            [
                'SELECT' => [
                    new QueryExpression(
                        "SUM(IFNULL({$s_table}.consumed, 0))
                    as consumed"
                    ),
                    "$s_table.tickets_id"
                ],
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    "$t_table.entities_id" => $active_entity
                ],
                'GROUP' => [
                    "$s_table.tickets_id"
                ]
            ],
            Provider::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $series = [];
        $tickets = [];
        $i = 0;
        $tab = array();

        foreach ($DB->request($criteria) as $id => $row) {
            $tab += [$row['tickets_id'] => $row['consumed']];
        }


        foreach ($tab as $ticket => $value) {
            $tickets[$i] = 'Ticket n°' . $ticket;
            $series['credit']['name'] = 'Crédit';
            $series['credit']['data'][$i] = [
                'value' => $value,
                'url' => Ticket::getFormURL() . "?id=" . $ticket
            ];
            $i++;
        }

        return [
            'data' => [
                'labels' => $tickets,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon' => $params['icon'],
            'alt' => $params['alt']
        ];
    }

    /**
     *
     * @param array $params default values for
     * - 'title' of the card
     * - 'icon' of the card
     * - 'apply_filters' values from dashboard filters
     *
     * @return array
     */
    public static function getCreditsEvolution(array $params = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $active_entity = Session::getActiveEntity();

        $default_params = [
            'label' => "",
            'icon' => "",
            'apply_filters' => [],
            'alt' => 'Evolution of credit consumption',
        ];

        $params = array_merge($default_params, $params);

        $e_table = PluginCreditEntity::getTable();
        $t_table = PluginCreditTicket::getTable();

        $criteria = array_merge_recursive([
            'SELECT' => [
                new QueryExpression(
                    "FROM_UNIXTIME(UNIX_TIMESTAMP(" . $DB->quoteName("{$t_table}.date_creation") . "),'%Y-%m') AS period"
                ),
                new QueryExpression(
                    "SUM(IFNULL({$t_table}.consumed, 0))
                    as " . $DB->quoteValue(_x('status', 'consumed'))
                ),
            ],
            'FROM'  => $t_table,
            'JOIN'  => [
                $e_table => [
                    'ON' => [
                        $e_table => 'id',
                        $t_table => 'plugin_credit_entities_id',
                    ],
                ],
            ],
            'WHERE' => [
                "$e_table.entities_id" => $active_entity
            ],
            'ORDER' => 'period ASC',
            'GROUP' => ['period'],
        ], Provider::getFiltersCriteria($e_table, $params['apply_filters']));

        $monthYears = [];
        $series = [];

        $i = 0;
        $iterator = $DB->request($criteria);

        foreach ($iterator as $result) {
            $monthYears[] = $result['period'];
            $tmp = $result;

            unset($tmp['period']);

            foreach ($tmp as $value) {
                $series['parmois']['name'] = "Consumption per month";
                $series['parmois']['data'][] = [
                    'value' => (int)$value,
                    'url' => '',
                ];

                $series['total']['name'] = "Total consumption";
                if ($i > 0) {
                    $series['total']['data'][] = [
                        'value' => (int)$value + $series['total']['data'][$i - 1]['value'],
                        'url' => '',
                    ];
                } else {
                    $series['total']['data'][] = [
                        'value' => (int)$value,
                        'url' => '',
                    ];
                }
            }
            $i++;
        }

        array_unshift($monthYears, '');
        if (isset($series['total']['data'])) {
            array_unshift($series['total']['data'], ['value' => 0, 'url' => '']);
            array_unshift($series['parmois']['data'], ['value' => 0, 'url' => '']);
        }

        return [
            'data'  => [
                'labels' => $monthYears,
                'series' => array_values($series),
            ],
            'label' => $params['label'],
            'icon'  => $params['icon'],
            'alt'   => $params['alt']
        ];
    }



    public static function getCredits(array $params = []): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $active_entity = Session::getActiveEntity();


        $t_table = PluginCreditEntity::getTable();
        $s_table = PluginCreditTicket::getTable();

        $criteria_quantity = array_merge_recursive(
            [
                'SELECT' => [
                    'quantity'
                ],
                'FROM' => $t_table,
                'WHERE' => [
                    "$t_table.entities_id" => $active_entity
                ]

            ],
            Provider::getFiltersCriteria($t_table, $params['apply_filters'])
        );


        $result_quantity = 0;

        foreach ($DB->request($criteria_quantity) as $id => $row) {
            $result_quantity += $row['quantity'];
        }

        $criteria_sum = array_merge_recursive(
            [
                'SELECT' => [
                    'SUM' => "$s_table.consumed AS sum",
                ],
                'FROM' => $t_table,
                'INNER JOIN' => [
                    $s_table => [
                        'ON' => [
                            $s_table => 'plugin_credit_entities_id',
                            $t_table => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    "$t_table.entities_id" => $active_entity
                ]
            ],
            Provider::getFiltersCriteria($t_table, $params['apply_filters'])
        );

        $result_sum = 0;
        foreach ($DB->request($criteria_sum) as $id => $row) {
            $result_sum += $row['sum'];
        }

        return [
            'quantity' => $result_quantity,
            'sum' => $result_sum
        ];
    }

    private static function getIconWallet(): string
    {
        return "fas fa-wallet";
    }
}
