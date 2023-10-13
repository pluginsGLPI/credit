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
use Glpi\Api\HL\Doc as Doc;
use Glpi\Api\HL\Route;
use Glpi\Http\Request;
use Glpi\Http\Response;

#[Route(path: '/Credit', priority: 1, tags: ['Credit'])]
final class PluginCreditApicontroller extends AbstractController
{
    protected static function getRawKnownSchemas(): array
    {
        $schemas = [];
        $schemas['Credit'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PluginCreditEntity::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => AbstractController::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'is_active' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'type' => AbstractController::getDropdownTypeSchema(class: PluginCreditType::class, full_schema: 'PluginCreditType'),
                'date_begin' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'begin_date'
                ],
                'date_end' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE,
                    'x-field' => 'end_date'
                ],
                'quantity' => ['type' => Doc\Schema::TYPE_INTEGER],
                'over_consumption_allowed' => [
                    'type' => Doc\Schema::TYPE_BOOLEAN,
                    'x-field' => 'overconsumption_allowed'
                ],
            ]
        ];
        $schemas['CreditType'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PluginCreditEntity::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'name' => ['type' => Doc\Schema::TYPE_STRING],
                'comment' => ['type' => Doc\Schema::TYPE_STRING],
                'entity' => AbstractController::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'is_recursive' => ['type' => Doc\Schema::TYPE_BOOLEAN],
                'parent' => AbstractController::getDropdownTypeSchema(class: PluginCreditType::class, full_schema: 'PluginCreditType'),
                'level' => ['type' => Doc\Schema::TYPE_INTEGER],
                'date_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-readonly' => true,
                ],
                'date_mod' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-readonly' => true,
                ],
            ]
        ];
        $schemas['CreditConsumption'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PluginCreditEntity::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'ticket' => AbstractController::getDropdownTypeSchema(class: Ticket::class, full_schema: 'Ticket'),
                'credit' => [
                    'type' => Doc\Schema::TYPE_OBJECT,
                    'x-itemtype' => PluginCreditTicket::class,
                    'x-full-schema' => 'Credit',
                    'properties' => [
                        'id' => [
                            'type' => Doc\Schema::TYPE_INTEGER,
                            'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                            'x-readonly' => true,
                        ],
                        'name' => ['type' => Doc\Schema::TYPE_STRING],
                    ]
                ],
                'date_creation' => [
                    'type' => Doc\Schema::TYPE_STRING,
                    'format' => Doc\Schema::FORMAT_STRING_DATE_TIME,
                    'x-readonly' => true,
                ],
                'consumed' => ['type' => Doc\Schema::TYPE_INTEGER],
                'user' => AbstractController::getDropdownTypeSchema(class: User::class, full_schema: 'User'),
            ]
        ];
        $schemas['CreditEntityConfig'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PluginCreditEntityConfig::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'entity' => AbstractController::getDropdownTypeSchema(class: Entity::class, full_schema: 'Entity'),
                'consume_for_followups' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_followups'],
                'consume_for_tasks' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_tasks'],
                'consume_for_solutions' => ['type' => Doc\Schema::TYPE_BOOLEAN, 'x-field' => 'consume_voucher_for_solutions'],
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
        $schemas['CreditTicketConfig'] = [
            'type' => Doc\Schema::TYPE_OBJECT,
            'x-itemtype' => PluginCreditTicketConfig::class,
            'properties' => [
                'id' => [
                    'type' => Doc\Schema::TYPE_INTEGER,
                    'format' => Doc\Schema::FORMAT_INTEGER_INT64,
                    'x-readonly' => true,
                ],
                'ticket' => AbstractController::getDropdownTypeSchema(class: Ticket::class, full_schema: 'Ticket'),
                'followups_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, full_schema: 'Credit'),
                'tasks_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, full_schema: 'Credit'),
                'solutions_credit' => AbstractController::getDropdownTypeSchema(class: PluginCreditEntity::class, full_schema: 'Credit'),
            ]
        ];
        return $schemas;
    }
}

