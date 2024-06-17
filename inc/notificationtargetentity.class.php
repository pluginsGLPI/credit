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

class PluginCreditNotificationTargetEntity extends NotificationTarget
{
    public function getEvents()
    {
        return [
            'expired' => __('Expiration date', 'credit'),
            'lowcredits' => __('Low credits', 'credit')
        ];
    }

    public function addDataForTemplate($event, $options = [])
    {
        /** @var DBmysql $DB */
        global $DB;

        $this->data['##credit.name##'] = $this->obj->getField('name');
        $this->data['##credit.quantity_sold##'] = $this->obj->getField('quantity');
        $this->data['##credit.begindate##'] = $this->obj->getField('begin_date');
        $this->data['##credit.enddate##'] = $this->obj->getField('end_date');
        $this->data['##credit.overconsumption_allowed##'] = Dropdown::getYesNo($this->obj->getField('overconsumption_allowed'));
        $this->data['##credit.child_entities##'] = Dropdown::getYesNo($this->obj->getField('is_recursive'));

        $req = $DB->request(
            [
                'SELECT' => [
                    'SUM' => 'consumed AS consumed_total'
                ],
                'FROM'   => 'glpi_plugin_credit_tickets',
                'WHERE'  => [
                    'plugin_credit_entities_id' => $this->obj->getField('id'),
                ],
            ]
        );
        $data = $req->current();
        $this->data['##credit.quantity_remaining##'] = (int)$this->obj->getField('quantity') - (int)$data['consumed_total'];
        $this->data['##credit.quantity_consumed##'] = (int)$data['consumed_total'];

        $this->data['##credit.entity##'] = Dropdown::getDropdownName(
            'glpi_entities',
            $this->obj->getField('entities_id')
        );
        $this->data['##credit.type##'] = Dropdown::getDropdownName('glpi_plugin_credit_types', $this->obj->getField('plugin_credit_types_id'));
        $this->data['##lang.credit.begindate##'] = __('Begin date');
        $this->data['##lang.credit.enddate##'] = __('End date', 'credit');
        $this->data['##lang.credit.quantity_remaining##'] = __('Quantity remaining', 'credit');
        $this->data['##lang.credit.quantity_sold##'] = __('Quantity sold', 'credit');
        $this->data['##lang.credit.name##'] = PluginCreditEntity::getTypeName();
        $this->data['##lang.credit.overconsumption_allowed##'] = __('Allow overconsumption', 'credit');
        $this->data['##lang.credit.child_entities##'] = __('Child entities');
        $this->data['##lang.credit.quantity_consumed##'] = __('Quantity consumed', 'credit');
        $this->data['##lang.credit.entity##'] = Entity::getTypeName(1);
        $this->data['##lang.credit.type##'] = __('Type');

        $this->getTags();
        foreach ($this->tag_descriptions[NotificationTarget::TAG_LANGUAGE] as $tag => $values) {
            if (!isset($this->data[$tag])) {
                $this->data[$tag] = $values['label'];
            }
        }
    }

    public function getTags()
    {
        $tags = [
            'credit.name'                    => PluginCreditEntity::getTypeName(),
            'credit.quantity_sold'           => __('Quantity sold', 'credit'),
            'credit.begindate'               => __('Begin date'),
            'credit.enddate'                 => __('End date', 'credit'),
            'credit.quantity_remaining'      => __('Quantity remaining', 'credit'),
            'credit.quantity_consumed'       => __('Quantity consumed', 'credit'),
            'credit.child_entities'          => __('Child entities'),
            'credit.entity'                  => Entity::getTypeName(1),
            'credit.overconsumption_allowed' => __('Allow overconsumption', 'credit'),
            'credit.type'                    => __('Type'),
        ];

        foreach ($tags as $tag => $label) {
            $this->addTagToList(
                [
                    'tag'   => $tag,
                    'label' => $label,
                    'value' => true,
                ]
            );
        }

        $lang = [
            'credit.expired'             => __('Credit voucher expiration', 'credit'),
            'credit.expired.information' => __('This credit voucher will expire soon. Please, consider buying a new one.', 'credit'),
            'credit.lowcredits' => __('Amount of credit remaining close to or at 0', 'credit'),
            'credit.lowcredits.information' => __('The remaining quantity has reached 0 or almost.', 'credit'),
        ];

        foreach ($lang as $tag => $label) {
            $this->addTagToList(
                [
                    'tag'   => $tag,
                    'label' => $label,
                    'value' => false,
                    'lang'  => true,
                ]
            );
        }

        asort($this->tag_descriptions);

        return $this->tag_descriptions;
    }


    public static function install(Migration $migration)
    {
        /** @var DBmysql $DB */
        global $DB;

        $template     = new NotificationTemplate();
        $translation  = new NotificationTemplateTranslation();
        $notification = new Notification();
        $n_n_template = new Notification_NotificationTemplate();
        $target       = new NotificationTarget();

        $templates_id = false;
        $result = $DB->request(
            [
                'SELECT' => 'id',
                'FROM'   => 'glpi_notificationtemplates',
                'WHERE'  => [
                    'itemtype' => 'PluginCreditEntity',
                    'name'     => 'Credit expired',
                ]
            ]
        );

        if (count($result) > 0) {
            $data = $result->current();
            $templates_id = $data['id'];
        } else {
            $templates_id = $template->add(
                [
                    'name'     => 'Credit expired',
                    'itemtype' => 'PluginCreditEntity',
                    'date_mod' => $_SESSION['glpi_currenttime'],
                    'comment'  => '',
                    'css'      => '',
                ]
            );
        }

        if ($templates_id) {
            $tanslation_count = countElementsInTable(
                $translation->getTable(),
                ['notificationtemplates_id' => $templates_id]
            );
            if ($tanslation_count == 0) {
                $translation->add(
                    [
                        'notificationtemplates_id' => $templates_id,
                        'language'                 => '',
                        'subject'                  => '##lang.credit.expired## : ##credit.name##',
                        'content_text'             => '##lang.credit.expired.information##',
                        'content_html'             => '##lang.credit.expired.information##',
                    ]
                );
            }

            $notifications_count = countElementsInTable(
                $notification->getTable(),
                ['itemtype' => 'PluginCreditEntity', 'event' => 'expired']
            );

            if ($notifications_count == 0) {
                $notification_id = $notification->add(
                    [
                        'name'         => 'Credit expired',
                        'entities_id'  => 0,
                        'itemtype'     => 'PluginCreditEntity',
                        'event'        => 'expired',
                        'comment'      => '',
                        'is_recursive' => 1,
                        'is_active'    => 1,
                        'date_mod'     => $_SESSION['glpi_currenttime'],
                    ]
                );

                 $n_n_template->add(
                     [
                         'notifications_id'         => $notification_id,
                         'mode'                     => Notification_NotificationTemplate::MODE_MAIL,
                         'notificationtemplates_id' => $templates_id,
                     ]
                 );

                 $target->add(
                     [
                         'notifications_id' => $notification_id,
                         'type'             => Notification::USER_TYPE,
                         'items_id'         => Notification::ENTITY_ADMINISTRATOR,
                     ]
                 );
            }
        }

        $templates_id_quantity = false;
        $result_quantity = $DB->request(
            [
                'SELECT' => 'id',
                'FROM' => 'glpi_notificationtemplates',
                'WHERE' => [
                    'itemtype' => 'PluginCreditEntity',
                    'name' => 'Low credits',
                ]
            ]
        );

        if (count($result_quantity) > 0) {
            $data_quantity = $result_quantity->current();
            $templates_id_quantity = $data_quantity['id'];
        } else {
            $templates_id_quantity = $template->add(
                [
                    'name' => 'Low credits',
                    'itemtype' => 'PluginCreditEntity',
                    'date_mod' => $_SESSION['glpi_currenttime'],
                    'comment' => '',
                    'css' => '',
                ]
            );
        }

        if ($templates_id_quantity) {
            $translation_count_quantity = countElementsInTable(
                $translation->getTable(),
                ['notificationtemplates_id' => $templates_id_quantity]
            );
            if ($translation_count_quantity == 0) {
                $translation->add(
                    [
                        'notificationtemplates_id' => $templates_id_quantity,
                        'language' => '',
                        'subject' => '##lang.credit.lowcredits## : ##credit.name##',
                        'content_text' => '##lang.credit.lowcredits.information##',
                        'content_html' => '##lang.credit.lowcredits.information##',
                    ]
                );
            }

            $notifications_count_quantity = countElementsInTable(
                $notification->getTable(),
                ['itemtype' => 'PluginCreditEntity', 'event' => 'lowcredits']
            );

            if ($notifications_count_quantity == 0) {
                $notification_id_quantity = $notification->add(
                    [
                        'name' => 'Low credits',
                        'entities_id' => 0,
                        'itemtype' => 'PluginCreditEntity',
                        'event' => 'lowcredits',
                        'comment' => '',
                        'is_recursive' => 1,
                        'is_active' => 1,
                        'date_mod' => $_SESSION['glpi_currenttime'],
                    ]
                );

                $n_n_template->add(
                    [
                        'notifications_id' => $notification_id_quantity,
                        'mode' => Notification_NotificationTemplate::MODE_MAIL,
                        'notificationtemplates_id' => $templates_id_quantity,
                    ]
                );

                $target->add(
                    [
                        'notifications_id' => $notification_id_quantity,
                        'type' => Notification::USER_TYPE,
                        'items_id' => Notification::ENTITY_ADMINISTRATOR,
                    ]
                );
            }
        }
    }

    public static function uninstall()
    {
        /** @var DBmysql $DB */
        global $DB;

        $notification = new Notification();
        $notifications_iterator = $DB->request(
            [
                'SELECT' => 'id',
                'FROM'   => $notification->getTable(),
                'WHERE'  => [
                    'itemtype' => 'PluginCreditEntity',
                    'event'    => 'expired',
                ],
            ]
        );
        foreach ($notifications_iterator as $notification_data) {
            $notification->delete($notification_data);
        }

        $template    = new NotificationTemplate();
        $templates_iterator = $DB->request(
            [
                'SELECT' => 'id',
                'FROM'   => $template->getTable(),
                'WHERE'  => [
                    'itemtype' => 'PluginCreditEntity',
                ],
            ]
        );
        foreach ($templates_iterator as $template_data) {
            $translation = new NotificationTemplateTranslation();
            $translations_iterator = $DB->request(
                [
                    'SELECT' => 'id',
                    'FROM'   => $translation->getTable(),
                    'WHERE'  => [
                        'notificationtemplates_id' => $template_data['id'],
                    ],
                ]
            );
            foreach ($translations_iterator as $translation_data) {
                 $translation->delete($translation_data);
            }

            $template->delete($template_data);
        }

        $notifications_iterator_quantity = $DB->request(
            [
                'SELECT' => 'id',
                'FROM' => $notification->getTable(),
                'WHERE' => [
                    'itemtype' => 'PluginCreditEntity',
                    'event' => 'lowcredits',
                ],
            ]
        );
        foreach ($notifications_iterator_quantity as $notification_data_quantity) {
            $notification->delete($notification_data_quantity);
        }

        $templates_iterator_quantity = $DB->request(
            [
                'SELECT' => 'id',
                'FROM' => $template->getTable(),
                'WHERE' => [
                    'itemtype' => 'PluginCreditEntity',
                    'name' => 'lowcredits',
                ],
            ]
        );
        foreach ($templates_iterator_quantity as $template_data_quantity) {
            $translation = new NotificationTemplateTranslation();
            $translations_iterator_quantity = $DB->request(
                [
                    'SELECT' => 'id',
                    'FROM' => $translation->getTable(),
                    'WHERE' => [
                        'notificationtemplates_id' => $template_data_quantity['id'],
                    ],
                ]
            );
            foreach ($translations_iterator_quantity as $translation_data_quantity) {
                $translation = new NotificationTemplateTranslation();
                $translation->delete($translation_data_quantity);
            }

            $template->delete($template_data_quantity);
        }
    }
}
