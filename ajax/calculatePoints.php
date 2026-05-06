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

/**
 * AJAX endpoint: calculate credit points from task duration and selected bareme.
 *
 * POST parameters:
 *   duration_seconds  int   Task duration in seconds
 *   bareme_id         int   PluginCreditBareme id
 *
 * Returns the integer number of points (JSON).
 */

include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isActivated('credit')) {
    Http::notFound();
}

Session::checkLoginUser();

$duration_seconds = (int) ($_POST['duration_seconds'] ?? 0);
$bareme_id        = (int) ($_POST['bareme_id'] ?? 0);

header('Content-Type: application/json');
echo json_encode(PluginCreditBareme::calculatePoints($duration_seconds, $bareme_id));
