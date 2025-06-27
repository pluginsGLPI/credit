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

use Glpi\Dashboard\Filters\AbstractFilter;

class PluginCreditDashboardFilters extends AbstractFilter
{
    const FIELD = "plugin_credit_types_id";

    public static function getName(): string
    {
        return __("Crédit");
    }

    public static function getId(): string
    {
        return "credit";
    }

    public static function canBeApplied(string $table): bool
    {
        /** @var \DBmysql $DB */
        global $DB;
        return $DB->fieldExists($table, self::FIELD);
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            self::getId(),
            PluginCreditEntity::class
        );
    }

    public static function getCriteria(string $table, $value): array
    {
        if ((int) $value > 0) {
            $field = self::FIELD;
            return [
                "WHERE" => [
                    "$table.$field" => (int) $value
                ]
            ];
        }

        return [];
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        if ((int) $value > 0) {
            return [
                [
                    'link'       => 'AND',
                    'searchtype' => 'equals',
                    'value'      => (int) $value,
                    'field'      => self::getSearchOptionID(
                        $table,
                        self::FIELD,
                        PluginCreditEntity::getTable()
                    ),
                ]
            ];
        }

        return [];
    }
}
