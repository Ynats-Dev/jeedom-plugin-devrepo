<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Webhook appelé par le dépôt (Gogs, Gitea, GitHub) à chaque push :
 * .../plugins/devrepo/core/php/jeeDevrepo.php?apikey=<clé API DevRepo>&plugin=<id du plugin>
 */
require_once __DIR__ . '/../../../../core/php/core.inc.php';

header('Content-Type: text/plain; charset=utf-8');
if (!jeedom::apiAccess(init('apikey'), 'devrepo')) {
  http_response_code(401);
  echo 'unauthorized';
  die();
}

// Seuls les push déclenchent une mise à jour (GitHub envoie aussi un « ping » à la création)
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? ($_SERVER['HTTP_X_GITEA_EVENT'] ?? ($_SERVER['HTTP_X_GOGS_EVENT'] ?? 'push'));
if ($event != 'push') {
  echo 'ignored: event ' . $event;
  die();
}

// Corps JSON, ou formulaire « payload=... » (GitHub en application/x-www-form-urlencoded)
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload) && isset($_POST['payload'])) {
  $payload = json_decode($_POST['payload'], true);
}

try {
  echo devrepo::webhook(init('plugin'), $payload);
} catch (Exception $e) {
  http_response_code(400);
  echo 'error: ' . $e->getMessage();
}
