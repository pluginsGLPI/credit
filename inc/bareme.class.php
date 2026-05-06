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

class PluginCreditBareme extends CommonDropdown
{
    public static $rightname = 'config';

    public static function getTypeName($nb = 0)
    {
        return _sn('Rate scale', 'Rate scales', $nb, 'credit');
    }

    public static function getIcon()
    {
        return 'ti ti-calculator';
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name'  => 'points_par_tranche',
                'label' => __('Points per 15-minute slot', 'credit'),
                'type'  => 'integer',
                'min'   => 1,
                'max'   => 9999,
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();
        $tab[] = [
            'id'       => 10,
            'table'    => self::getTable(),
            'field'    => 'points_par_tranche',
            'name'     => __('Points per 15-minute slot', 'credit'),
            'datatype' => 'number',
        ];
        return $tab;
    }

    /**
     * Get all baremes as id => [name, points_par_tranche] map.
     */
    public static function getAllBaremes(): array
    {
        /** @var DBmysql $DB */
        global $DB;

        $baremes = [];
        foreach ($DB->request(['FROM' => self::getTable(), 'ORDER' => 'name']) as $row) {
            $baremes[$row['id']] = [
                'name'              => $row['name'],
                'points_par_tranche' => (int) $row['points_par_tranche'],
            ];
        }
        return $baremes;
    }

    /**
     * Get points_par_tranche for a given bareme id.
     */
    public static function getPointsParTranche(int $bareme_id): int
    {
        /** @var DBmysql $DB */
        global $DB;

        $row = $DB->request([
            'SELECT' => ['points_par_tranche'],
            'FROM'   => self::getTable(),
            'WHERE'  => ['id' => $bareme_id],
        ])->current();

        return $row ? (int) $row['points_par_tranche'] : 0;
    }

    /**
     * Calculate points from duration (seconds) and bareme id.
     * Formula: ceil(duration_minutes / 15) * points_par_tranche
     * Each started 15-minute slot counts as a full slot.
     */
    public static function calculatePoints(int $duration_seconds, int $bareme_id): int
    {
        if ($duration_seconds <= 0 || $bareme_id <= 0) {
            return 0;
        }
        $duration_minutes  = $duration_seconds / 60;
        $tranches          = (int) ceil($duration_minutes / 15);
        $points_par_tranche = self::getPointsParTranche($bareme_id);
        return $tranches * $points_par_tranche;
    }

    /**
     * Install the bareme table and seed default values.
     */
    public static function install(Migration $migration): bool
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table             = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = <<<SQL
                CREATE TABLE IF NOT EXISTS `$table` (
                    `id` int {$default_key_sign} NOT NULL auto_increment,
                    `entities_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                    `is_recursive` tinyint NOT NULL DEFAULT '0',
                    `name` varchar(255) DEFAULT NULL,
                    `points_par_tranche` int NOT NULL DEFAULT '0',
                    `comment` text,
                    `date_mod` timestamp NULL DEFAULT NULL,
                    `date_creation` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `name` (`name`),
                    KEY `entities_id` (`entities_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;
SQL;
            $DB->doQuery($query);

            // Seed the four default rate scales
            $now      = date('Y-m-d H:i:s');
            $defaults = [
                ['name' => 'ING (Ingénieur)',    'points_par_tranche' => 33],
                ['name' => 'TECH (Technicien)',   'points_par_tranche' => 28],
                ['name' => 'SOIR-WEEKEND',        'points_par_tranche' => 44],
                ['name' => 'FERIE',               'points_par_tranche' => 58],
            ];
            foreach ($defaults as $bareme) {
                $DB->insert($table, array_merge($bareme, [
                    'entities_id'   => 0,
                    'is_recursive'  => 1,
                    'date_creation' => $now,
                    'date_mod'      => $now,
                ]));
            }
        } else {
            // Ensure column exists on existing installs
            if (!$DB->fieldExists($table, 'points_par_tranche')) {
                $migration->addField($table, 'points_par_tranche', 'int NOT NULL DEFAULT 0');
                $migration->executeMigration();
            }
        }

        return true;
    }

    /**
     * Uninstall the bareme table.
     */
    public static function uninstall(Migration $migration): bool
    {
        $migration->dropTable(self::getTable());
        return true;
    }
}
