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

function creditInitConsume(root) {
    const rand = root.dataset.creditConsumeRand;
    const quantityUrl = root.dataset.creditQuantityUrl;

    function showCreditDiv() {
        const creditVoucherBlock = document.getElementById('creditvoucherblock' + rand);
        if (creditVoucherBlock) {
            const isHidden = creditVoucherBlock.style.display !== 'none';
            creditVoucherBlock.style.display = isHidden ? 'none' : 'block';
            var consumeblock = document.querySelector('[name="credit_consume_' + rand + '"]');
            var quantity = consumeblock.querySelector('[id*="plugin_credit_quantity_' + rand + '"]');
            if (quantity) {
                quantity.disabled = isHidden;
            }
        }
    }

    function getQuantityRemaining(val) {
        var consumeblock = document.querySelector('[name="credit_consume_' + rand + '"]');
        var quantity = consumeblock.querySelector('[id*="plugin_credit_quantity_' + rand + '"]');
        var quantity_field = quantity.closest('.col-6');
        var quantity_label = consumeblock.querySelector('[for*="plugin_credit_quantity_' + rand + '"]');
        if (val == 0) {
            quantity_field.classList.add('d-none');
            quantity.disabled = true;
            quantity.max = 0;
            quantity.value = 0;
            quantity_label.querySelector('span.form-help').setAttribute('data-bs-title', __('Maximum consumable quantity', 'credit') + ' : 0 ');
            quantity_label.querySelector('span.form-help').setAttribute('data-bs-content', __('Maximum consumable quantity', 'credit') + ' : 0 ');
        } else {
            quantity_field.classList.remove('d-none');
            quantity.disabled = false;

            $.ajax({
                type: 'POST',
                url: quantityUrl,
                data: {
                    entity: val,
                }
            }).done(function (data) {
                quantity.max = data;
                quantity.value = 1;
                quantity_label.querySelector('span.form-help').setAttribute('data-bs-title', __('Maximum consumable quantity', 'credit') + ' : <b>' + data);
                quantity_label.querySelector('span.form-help').setAttribute('data-bs-content', __('Maximum consumable quantity', 'credit') + ' : <b>' + data);
            });
        }
    }

    document.getElementById('plugin_credit_consumed_voucher_' + rand).addEventListener('change', function () {
        showCreditDiv(this.value);
    });
    $('#dropdown_plugin_credit_entities_id' + rand).on('change', function () {
        getQuantityRemaining(this.value);
    });
}

function creditInitForm(root) {
    const rand = root.dataset.creditFormRand;
    const quantityUrl = root.dataset.creditQuantityUrl;

    function getQuantityRemainingForm(val, source) {
        var form = source ? source.closest('form') : document.getElementById('creditentity_form' + rand);
        if (!form) {
            return;
        }

        var quantity = form.querySelector('[id*="plugin_credit_quantity"]');
        var quantity_label = form.querySelector('[for*="plugin_credit_quantity"]');
        if (!quantity || !quantity_label) {
            return;
        }

        var form_help = quantity_label.querySelector('span.form-help');
        if (!form_help) {
            return;
        }

        if (val == 0) {
            quantity.value = 0;
            form_help.setAttribute('data-bs-title', __('Maximum consumable quantity', 'credit') + ' : 0 ');
            form_help.setAttribute('data-bs-content', __('Maximum consumable quantity', 'credit') + ' : 0 ');
        } else {
            quantity.closest('.form-field').classList.remove('d-none');
            $.ajax({
                type: 'POST',
                url: quantityUrl,
                data: {
                    entity: val,
                }
            }).done(function (data) {
                quantity.max = data;
                quantity.value = 1;
                form_help.setAttribute('data-bs-title', __('Maximum consumable quantity', 'credit') + ' : <b>' + data);
                form_help.setAttribute('data-bs-content', __('Maximum consumable quantity', 'credit') + ' : <b>' + data);
            });
        }
    }

    $('#dropdown_plugin_credit_entities_id' + rand).on('change', function () {
        getQuantityRemainingForm(this.value, this);
    });
}

document.querySelectorAll('[data-credit-consume-rand]:not([data-credit-consume-ready])').forEach(function (root) {
    root.dataset.creditConsumeReady = '';
    creditInitConsume(root);
});

document.querySelectorAll('[data-credit-form-rand]:not([data-credit-form-ready])').forEach(function (root) {
    root.dataset.creditFormReady = '';
    creditInitForm(root);
});
