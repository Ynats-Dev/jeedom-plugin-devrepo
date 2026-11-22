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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    // Liste des dépôts et plugins installés
    if (init('action') == 'getRepos') {
        ajax::success(array(
            'repos' => devrepo::listForUi(),
            'plugins' => devrepo::installedPlugins(),
            'realtime' => devrepo::realtimeRemaining(),
            'realtimeDuration' => devrepo::realtimeDuration(),
            'webhook' => devrepo::webhookUrls(),
        ));
    }

    // Enregistre la liste
    if (init('action') == 'saveRepos') {
        $list = json_decode(init('repos', '[]'), true);
        ajax::success(devrepo::updateRepos(is_array($list) ? $list : array()));
    }

    // Dernier commit disponible pour chaque plugin
    if (init('action') == 'checkAll') {
        ajax::success(devrepo::checkAll());
    }

    // Met à jour un plugin depuis sa branche
    if (init('action') == 'updatePlugin') {
        ajax::success(devrepo::updatePlugin(init('plugin')));
    }

    // Active / arrête le mode temps réel (renvoie les secondes restantes)
    if (init('action') == 'setRealtime') {
        ajax::success(devrepo::setRealtime(init('enable') == 1));
    }

    // Durée du mode temps réel, en minutes (renvoie la durée enregistrée)
    if (init('action') == 'setRealtimeDuration') {
        ajax::success(devrepo::setRealtimeDuration(init('minutes')));
    }

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}
