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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
function devrepo_install() {
  devrepo_defaultConfig();
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function devrepo_update() {
  devrepo_defaultConfig();
}

// Valeurs par défaut de la configuration du plugin
function devrepo_defaultConfig() {
  if (config::byKey('realtimeDuration', 'devrepo', '') == '') {
    config::save('realtimeDuration', 60, 'devrepo');
  }
}

// Fonction exécutée automatiquement après la suppression du plugin
function devrepo_remove() {
  foreach (array('realtime', 'hookUpdate') as $function) {
    foreach (cron::searchClassAndFunction('devrepo', $function) as $cron) {
      $cron->remove();
    }
  }
  // Les jetons d'accès (chiffrés) et la liste des dépôts sont supprimés avec la configuration du plugin
}
