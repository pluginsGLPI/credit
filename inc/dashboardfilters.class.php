<?php

use Glpi\Dashboard\Filters\AbstractFilter;
use PluginCreditEntity;


class PluginCreditDashboardFilters extends AbstractFilter
{

    const FIELD = "id";

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