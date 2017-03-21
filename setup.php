<?php
/*
 -------------------------------------------------------------------------
 credit plugin for GLPI
 Copyright (C) 2017 by the credit Development Team.

 https://github.com/pluginsGLPI/credit
 -------------------------------------------------------------------------

 LICENSE

 This file is part of credit.

 credit is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 credit is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with credit. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

define('PLUGIN_CREDIT_VERSION', '1.0.0');

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_credit() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $plugin = new Plugin();

   // Hack for vertical display
   if (isset($CFG_GLPI['layout_excluded_pages'])) {
      array_push($CFG_GLPI['layout_excluded_pages'], "type.form.php");
   }

   $PLUGIN_HOOKS['csrf_compliant']['credit'] = true;

   Plugin::registerClass('PluginCreditType');

   if (Session::getLoginUserID() && $plugin->isActivated('credit')) {

      if (Session::haveRight('entity', UPDATE)) {
         Plugin::registerClass('PluginCreditEntity', ['addtabon' => 'Entity']);
      }

      if (Session::haveRightsOr('ticket', array(Ticket::STEAL, Ticket::OWN))) {

         Plugin::registerClass('PluginCreditTicket', ['addtabon' => 'Ticket']);

         $PLUGIN_HOOKS['post_item_form']['credit'] =
            ['PluginCreditTicket', 'postSolutionForm'];

         $PLUGIN_HOOKS['pre_item_update']['credit'] =
            ['Ticket' => ['PluginCreditTicket', 'beforeUpdate']];
      }
   }
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_credit() {
   return [
      'name'           => __('Credit vouchers', 'credit'),
      'version'        => PLUGIN_CREDIT_VERSION,
      'author'         => '<a href="http://www.teclib.com">Teclib\'</a>',
      'license'        => 'GPLv3',
      'homepage'       => 'https://github.com/pluginsGLPI/credit',
      'minGlpiVersion' => '9.1.2'
   ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_credit_check_prerequisites() {
   // Strict version check (could be less strict, or could allow various version)
   if (version_compare(GLPI_VERSION, '9.1.2', 'lt')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.1.2');
      } else {
         echo "This plugin requires GLPI >= 9.1.2";
      }
      return false;
   }
   return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_credit_check_config($verbose = false) {
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      _e('Installed / not configured', 'credit');
   }
   return false;
}
