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
use Glpi\Event;
use Glpi\Exception\Http\BadRequestHttpException;

$PluginCreditEntity = new PluginCreditEntity();

if (isset($_POST["add"])) {
    $PluginCreditEntity->check(-1, CREATE, $_POST);
    if ($PluginCreditEntity->add($_POST)) {
        Event::log(
            $_POST["plugin_credit_types_id"],
            "entity",
            4,
            "setup",
            sprintf(__('%s adds a vouchers to an entity'), $_SESSION["glpiname"]),
        );
    }
    Html::back();
} elseif (isset($_POST["update"])) {
    $PluginCreditEntity->check($_POST['id'], UPDATE);
    $PluginCreditEntity->update($_POST);
    Html::back();
} elseif (isset($_POST["delete"])) {
    $PluginCreditEntity->check($_POST['id'], DELETE);
    $PluginCreditEntity->delete($_POST);
    $PluginCreditEntity->redirectToList();
} elseif (isset($_POST["restore"])) {
    $PluginCreditEntity->check($_POST['id'], DELETE);
    $PluginCreditEntity->restore($_POST);
    $PluginCreditEntity->redirectToList();
} elseif (isset($_POST["purge"])) {
    $PluginCreditEntity->check($_POST['id'], PURGE);
    $PluginCreditEntity->delete($_POST, true);
    $PluginCreditEntity->redirectToList();
} elseif (isset($_GET['id']) || !isset($_POST)) {
    $ID = isset($_GET['id']) ? intval($_GET['id']) : 0;

    Session::checkRight(PluginCreditEntity::$rightname, READ);

    if (isset($_GET['forcetab'])) {
        Session::setActiveTab(PluginCreditEntity::class, $_GET['forcetab']);
        unset($_GET['forcetab']);
    }

    Html::header(PluginCreditEntity::getTypeName(), $_SERVER['PHP_SELF'], "admin", PluginCreditEntity::class, "credit");
    $PluginCreditEntity->display(['id' => $ID]);
    Html::footer();
} else {
    throw new BadRequestHttpException();
}
