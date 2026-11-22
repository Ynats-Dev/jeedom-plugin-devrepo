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

require_once __DIR__ . '/../../../../core/php/core.inc.php';

/**
 * DevRepo : met à jour des plugins en mode développement depuis une branche de leur dépôt
 * (Gogs, Gitea ou GitHub), sans git sur la box.
 *
 * config "repos" : [{plugin, repo, branch, token (chiffré), auto (0|1), last: {date, branch, commit, message, version, files, auto (false|cron|realtime|webhook)}}]
 * config "realtimeUntil" : fin du mode temps réel (timestamp)
 * config "realtimeDuration" : durée du mode temps réel, en minutes (choisie sur la page du plugin)
 */
class devrepo extends eqLogic {

  const DEFAULT_BRANCH = 'beta';

  /*     * ***********************Liste des dépôts*************************** */

  /** Liste enregistrée (jetons chiffrés). */
  private static function getRepos() {
    $repos = config::byKey('repos', __CLASS__, array());
    return is_array($repos) ? array_values($repos) : array();
  }

  private static function saveRepos($_repos) {
    config::save('repos', array_values($_repos), __CLASS__);
  }

  /** Liste pour la page de configuration : jamais de jeton, seulement s'il existe. */
  public static function listForUi() {
    $list = array();
    foreach (self::getRepos() as $repo) {
      $plugin = null;
      try {
        $plugin = plugin::byId($repo['plugin']);
      } catch (Exception $e) {
      }
      $list[] = array(
        'plugin' => $repo['plugin'],
        'repo' => $repo['repo'] ?? '',
        'branch' => $repo['branch'] ?? self::DEFAULT_BRANCH,
        'hasToken' => ($repo['token'] ?? '') != '',
        'auto' => !empty($repo['auto']),
        'last' => $repo['last'] ?? null,
        'installed' => is_object($plugin),
        'name' => is_object($plugin) ? $plugin->getName() : $repo['plugin'],
        'version' => is_object($plugin) ? self::installedVersion($repo['plugin']) : '',
      );
    }
    return $list;
  }

  /** Plugins installés (pour la liste de choix). */
  public static function installedPlugins() {
    $list = array();
    foreach (plugin::listPlugin() as $plugin) {
      $list[] = array('id' => $plugin->getId(), 'name' => $plugin->getName());
    }
    usort($list, function ($a, $b) {
      return strcasecmp($a['name'], $b['name']);
    });
    return $list;
  }

  /**
   * Enregistre la liste envoyée par la page.
   * Jeton : vide = inchangé, "__clear__" = supprimé, sinon nouveau jeton (chiffré).
   */
  public static function updateRepos($_list) {
    $previous = array();
    foreach (self::getRepos() as $repo) {
      $previous[$repo['plugin']] = $repo;
    }
    $repos = array();
    $seen = array();
    foreach ((array) $_list as $line => $item) {
      $where = __('Ligne', __FILE__) . ' ' . ($line + 1) . ' : ';
      $id = trim($item['plugin'] ?? '');
      if (!self::isValidPluginId($id)) {
        throw new Exception($where . __('identifiant de plugin invalide', __FILE__) . ' « ' . $id . ' »');
      }
      if (in_array($id, $seen)) {
        throw new Exception($where . __('ce plugin est déjà dans la liste', __FILE__) . ' : ' . $id);
      }
      $seen[] = $id;
      $url = trim($item['repo'] ?? '');
      self::parseRepoUrl($url, $where);
      $branch = trim($item['branch'] ?? '');
      if ($branch == '') {
        $branch = self::DEFAULT_BRANCH;
      }
      self::checkBranch($branch, $where);
      $token = $previous[$id]['token'] ?? '';
      $newToken = trim($item['token'] ?? '');
      if ($newToken == '__clear__') {
        $token = '';
      } elseif ($newToken != '') {
        $token = utils::encrypt($newToken);
      }
      $repos[] = array(
        'plugin' => $id,
        'repo' => $url,
        'branch' => $branch,
        'token' => $token,
        'auto' => empty($item['auto']) ? 0 : 1,
        'last' => $previous[$id]['last'] ?? null,
      );
    }
    self::saveRepos($repos);
    return self::listForUi();
  }

  private static function findRepo($_pluginId) {
    foreach (self::getRepos() as $repo) {
      if ($repo['plugin'] == $_pluginId) {
        return $repo;
      }
    }
    throw new Exception(__('Plugin absent de la liste', __FILE__) . ' : ' . $_pluginId);
  }

  private static function isValidPluginId($_id) {
    return (bool) preg_match('/^[A-Za-z0-9_]+$/', $_id);
  }

  private static function checkBranch($_branch, $_where = '') {
    if (!preg_match('#^[A-Za-z0-9._/-]+$#', $_branch) || strpos($_branch, '..') !== false) {
      throw new Exception($_where . __('nom de branche invalide', __FILE__) . ' : ' . $_branch);
    }
  }

  /**
   * Découpe l'URL du dépôt : https://serveur/proprietaire/depot(.git)
   * @return array [type (github|gogs), base, owner, name]
   */
  private static function parseRepoUrl($_url, $_where = '') {
    if (!preg_match('#^(https?://[^/\s]+)/([^/\s]+)/([^/\s]+?)(?:\.git)?/?$#', $_url, $m)) {
      throw new Exception($_where . __('URL de dépôt invalide (attendu : https://serveur/propriétaire/dépôt.git)', __FILE__) . ' : ' . $_url);
    }
    $isGithub = (bool) preg_match('#^https?://(www\.)?github\.com$#i', $m[1]);
    return array($isGithub ? 'github' : 'gogs', $m[1], $m[2], $m[3]);
  }

  private static function installedVersion($_pluginId) {
    $file = self::pluginDir($_pluginId) . '/plugin_info/info.json';
    if (!is_file($file)) {
      return '';
    }
    $info = json_decode(file_get_contents($file), true);
    return $info['pluginVersion'] ?? '';
  }

  private static function pluginDir($_pluginId) {
    return realpath(__DIR__ . '/../../..') . '/' . $_pluginId;
  }

  /*     * ***********************Accès au dépôt*************************** */

  /** Encode un nom de branche pour une URL en conservant les « / » (ex. feature/x). */
  private static function encodeRef($_branch) {
    return str_replace('%2F', '/', rawurlencode($_branch));
  }

  /** URLs de l'API selon le type de dépôt, et en-têtes d'authentification. */
  private static function remote($_repo) {
    list($type, $base, $owner, $name) = self::parseRepoUrl($_repo['repo']);
    $branch = $_repo['branch'] ?? self::DEFAULT_BRANCH;
    self::checkBranch($branch);
    $token = ($_repo['token'] ?? '') == '' ? '' : utils::decrypt($_repo['token']);
    if ($type == 'github') {
      $api = 'https://api.github.com/repos/' . rawurlencode($owner) . '/' . rawurlencode($name);
      $headers = array('Accept: application/vnd.github+json');
      if ($token != '') {
        $headers[] = 'Authorization: Bearer ' . $token;
      }
      return array(
        'type' => $type,
        'repoUrl' => $api,
        'branchUrl' => $api . '/branches/' . self::encodeRef($branch),
        'archiveUrl' => $api . '/zipball/' . self::encodeRef($branch),
        'headers' => $headers,
        'branch' => $branch,
        'hasToken' => $token != '',
      );
    }
    // Gogs / Gitea
    $api = $base . '/api/v1/repos/' . rawurlencode($owner) . '/' . rawurlencode($name);
    return array(
      'type' => $type,
      'repoUrl' => $api,
      'branchUrl' => $api . '/branches/' . self::encodeRef($branch),
      'archiveUrl' => $api . '/archive/' . self::encodeRef($branch) . '.zip',
      'headers' => ($token == '') ? array() : array('Authorization: token ' . $token),
      'branch' => $branch,
      'hasToken' => $token != '',
    );
  }

  /** Dernier commit de la branche : [id court, message]. */
  private static function lastCommit($_remote) {
    try {
      $info = json_decode(self::httpGet($_remote['branchUrl'], $_remote['headers']), true);
    } catch (Exception $e) {
      throw self::explain($e, $_remote);
    }
    $commit = $info['commit'] ?? array();
    // Gogs / Gitea : commit.id + commit.message ; GitHub : commit.sha + commit.commit.message
    $id = $commit['id'] ?? ($commit['sha'] ?? '');
    $message = $commit['message'] ?? ($commit['commit']['message'] ?? '');
    return array(substr($id, 0, 7), trim(strtok($message, "\n")));
  }

  /**
   * Précise une erreur 404 : dépôt introuvable (URL, ou dépôt privé sans jeton valide)
   * ou branche absente du dépôt (avec la branche par défaut du dépôt).
   */
  private static function explain(Exception $_e, $_remote) {
    if ($_e->getCode() != 404) {
      return $_e;
    }
    try {
      $repo = json_decode(self::httpGet($_remote['repoUrl'], $_remote['headers']), true);
    } catch (Exception $e) {
      if ($e->getCode() != 404) {
        return $e;
      }
      $hint = $_remote['hasToken']
        ? __('vérifiez l\'URL, et que le jeton donne accès à ce dépôt', __FILE__)
        : __('vérifiez l\'URL ; si le dépôt est privé, renseignez un jeton d\'accès', __FILE__);
      return new Exception(__('Dépôt introuvable (HTTP 404)', __FILE__) . ' : ' . $hint, 404);
    }
    $message = __('Branche introuvable dans le dépôt', __FILE__) . ' : « ' . $_remote['branch'] . ' »';
    if (($repo['default_branch'] ?? '') != '') {
      $message .= ' (' . __('branche par défaut du dépôt', __FILE__) . ' : « ' . $repo['default_branch'] . ' »)';
    }
    return new Exception($message, 404);
  }

  private static function httpGet($_url, $_headers) {
    $ch = curl_init($_url);
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_TIMEOUT => 120,
      CURLOPT_HTTPHEADER => $_headers,
      CURLOPT_USERAGENT => 'Jeedom DevRepo',
    ));
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    if (PHP_VERSION_ID < 80000) {
      curl_close($ch);
    }
    if ($body === false) {
      throw new Exception(__('Erreur de connexion au dépôt', __FILE__) . ' : ' . $error);
    }
    if ($code == 401 || $code == 403) {
      throw new Exception(__('Accès refusé (HTTP', __FILE__) . ' ' . $code . ') : ' . __('vérifiez le jeton d\'accès', __FILE__));
    }
    if ($code == 404) {
      throw new Exception(__('Introuvable (HTTP 404) : vérifiez l\'URL du dépôt, le nom de la branche et le jeton (dépôt privé)', __FILE__), 404);
    }
    if ($code != 200) {
      throw new Exception(__('Réponse inattendue du dépôt (HTTP', __FILE__) . ' ' . $code . ')');
    }
    return $body;
  }

  /*     * ***********************Vérifier / mettre à jour*************************** */

  /** Dernier commit disponible pour chaque plugin de la liste : [plugin => {commit, message} | {error}]. */
  public static function checkAll() {
    $result = array();
    foreach (self::getRepos() as $repo) {
      try {
        list($id, $message) = self::lastCommit(self::remote($repo));
        $result[$repo['plugin']] = array('commit' => $id, 'message' => $message);
      } catch (Exception $e) {
        $result[$repo['plugin']] = array('error' => $e->getMessage());
      }
    }
    return $result;
  }

  /**
   * Mise à jour automatique d'un plugin dont l'option « Auto » est cochée :
   * si la branche a un nouveau commit, le plugin est mis à jour.
   * Un commit dont la mise à jour a échoué n'est pas retenté (en attendant le commit suivant).
   * @return bool true si le plugin a été mis à jour
   */
  private static function autoUpdate($_repo, $_source) {
    $id = $_repo['plugin'];
    // Une seule mise à jour à la fois par plugin (webhook, temps réel et cron peuvent se croiser)
    $lock = fopen(jeedom::getTmpFolder(__CLASS__) . '/lock_' . $id, 'c');
    if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
      return false;
    }
    try {
      list($commit, $message) = self::lastCommit(self::remote($_repo));
      // Relu sous verrou : une autre mise à jour vient peut-être de se terminer
      $current = self::findRepo($id);
      if ($commit == '' || $commit == ($current['last']['commit'] ?? '')) {
        return false;
      }
      $failedKey = __CLASS__ . '::failed::' . $id;
      if (cache::byKey($failedKey)->getValue('') == $commit) {
        return false;
      }
      log::add(__CLASS__, 'info', $id . ' : ' . __('nouveau commit', __FILE__) . ' ' . $commit . ' (' . $message . '), ' . __('mise à jour automatique', __FILE__) . ' (' . $_source . ')');
      try {
        $last = self::updatePlugin($id, $_source);
        cache::delete($failedKey);
        self::notify(__('Plugin mis à jour automatiquement', __FILE__), $id . ' : ' . $last['branch'] . ' @ ' . $commit . ($message != '' ? ' · ' . $message : '') . ($last['version'] != '' ? ' · v' . $last['version'] : ''), 'success');
        event::add('devrepo::updated', array('plugin' => $id, 'last' => $last));
        return true;
      } catch (Exception $e) {
        cache::set($failedKey, $commit);
        log::add(__CLASS__, 'error', $id . ' : ' . __('échec de la mise à jour automatique', __FILE__) . ' (' . $commit . ') : ' . $e->getMessage());
        self::notify(__('Échec de la mise à jour automatique', __FILE__), $id . ' @ ' . $commit . ' : ' . $e->getMessage(), 'error');
      }
    } catch (Exception $e) {
      // Dépôt injoignable : on réessaiera au prochain passage
      log::add(__CLASS__, 'debug', $id . ' : ' . $e->getMessage());
    } finally {
      flock($lock, LOCK_UN);
      fclose($lock);
    }
    return false;
  }

  /** Notification système (toast) sur toutes les pages Jeedom ouvertes. */
  private static function notify($_title, $_message, $_theme) {
    try {
      event::add('notify', array('title' => 'DevRepo · ' . $_title, 'message' => $_message, 'theme' => $_theme));
    } catch (Exception $e) {
    }
  }

  /** Vérification toutes les 5 minutes (inutile pendant le mode temps réel, qui vérifie bien plus souvent). */
  public static function cron5() {
    if (self::realtimeRemaining() > 0) {
      return;
    }
    foreach (self::getRepos() as $repo) {
      if (!empty($repo['auto'])) {
        self::autoUpdate($repo, 'cron');
      }
    }
  }

  /*     * ***********************Mode temps réel*************************** */

  const REALTIME_DURATIONS = array(15, 30, 60, 120, 240, 480); // minutes proposées
  const REALTIME_DEFAULT_DURATION = 60; // minutes
  const REALTIME_INTERVAL = 10;    // secondes entre deux vérifications
  const REALTIME_GITHUB_ANONYMOUS = 90; // GitHub sans jeton : 60 requêtes / heure au maximum

  /** Durée du mode temps réel choisie sur la page du plugin, en minutes. */
  public static function realtimeDuration() {
    $minutes = (int) config::byKey('realtimeDuration', __CLASS__, self::REALTIME_DEFAULT_DURATION);
    return in_array($minutes, self::REALTIME_DURATIONS) ? $minutes : self::REALTIME_DEFAULT_DURATION;
  }

  /** Enregistre la durée du mode temps réel (prise en compte à la prochaine activation). */
  public static function setRealtimeDuration($_minutes) {
    $minutes = (int) $_minutes;
    if (!in_array($minutes, self::REALTIME_DURATIONS)) {
      throw new Exception(__('Durée invalide', __FILE__) . ' : ' . $_minutes);
    }
    config::save('realtimeDuration', $minutes, __CLASS__);
    return $minutes;
  }

  /** Secondes restantes du mode temps réel (0 = inactif). */
  public static function realtimeRemaining() {
    return max(0, (int) config::byKey('realtimeUntil', __CLASS__, 0) - time());
  }

  /**
   * Active (pour realtimeDuration()) ou arrête le mode temps réel :
   * une tâche démon du core vérifie les branches toutes les REALTIME_INTERVAL secondes.
   */
  public static function setRealtime($_enable) {
    $cron = cron::byClassAndFunction(__CLASS__, 'realtime');
    if (!$_enable) {
      config::save('realtimeUntil', 0, __CLASS__);
      if (is_object($cron)) {
        $cron->remove();
      }
      log::add(__CLASS__, 'info', __('Mode temps réel arrêté', __FILE__));
      return self::realtimeRemaining();
    }
    $minutes = self::realtimeDuration();
    config::save('realtimeUntil', time() + $minutes * 60, __CLASS__);
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass(__CLASS__);
      $cron->setFunction('realtime');
    }
    $cron->setEnable(1);
    $cron->setDeamon(1);
    $cron->setDeamonSleepTime(self::REALTIME_INTERVAL);
    $cron->setSchedule('* * * * *');
    $cron->setTimeout($minutes + 5);
    $cron->save();
    $cron->run(true);
    log::add(__CLASS__, 'info', __('Mode temps réel activé', __FILE__) . ' (' . $minutes . ' min)');
    return self::realtimeRemaining();
  }

  /** Un passage du démon temps réel. */
  public static function realtime() {
    if (self::realtimeRemaining() <= 0) {
      // Durée écoulée : la tâche se supprime et le processus s'arrête
      $cron = cron::byClassAndFunction(__CLASS__, 'realtime');
      if (is_object($cron)) {
        $cron->remove(false);
      }
      log::add(__CLASS__, 'info', __('Mode temps réel terminé', __FILE__));
      exit();
    }
    static $nextCheck = array();
    foreach (self::getRepos() as $repo) {
      if (empty($repo['auto'])) {
        continue;
      }
      $id = $repo['plugin'];
      if (time() < ($nextCheck[$id] ?? 0)) {
        continue;
      }
      $anonymousGithub = ($repo['token'] ?? '') == '' && preg_match('#^https?://(www\.)?github\.com/#i', $repo['repo'] ?? '');
      if ($anonymousGithub) {
        $nextCheck[$id] = time() + self::REALTIME_GITHUB_ANONYMOUS;
      }
      if (self::autoUpdate($repo, 'realtime') && $id == __CLASS__) {
        // DevRepo s'est mis à jour lui-même : on redémarre pour charger le nouveau code (relancé par le core à la minute suivante)
        exit();
      }
    }
  }

  /*     * ***********************Webhook*************************** */

  /** URLs du webhook (adresses interne et externe de Jeedom), sans le paramètre plugin. */
  public static function webhookUrls() {
    $path = '/plugins/' . __CLASS__ . '/core/php/jeeDevrepo.php?apikey=' . jeedom::getApiKey(__CLASS__);
    $urls = array();
    foreach (array('internal', 'external') as $mode) {
      try {
        $base = network::getNetworkAccess($mode);
      } catch (Exception $e) {
        $base = '';
      }
      if ($base != '' && !in_array($base . $path, $urls)) {
        $urls[$mode] = $base . $path;
      }
    }
    return $urls;
  }

  /**
   * Appel du dépôt (push) : si la branche poussée est celle suivie et que « Auto » est coché,
   * la mise à jour est lancée en arrière-plan (le dépôt attend une réponse rapide).
   * @return string réponse texte pour le dépôt
   */
  public static function webhook($_pluginId, $_payload) {
    if (!self::isValidPluginId($_pluginId)) {
      throw new Exception(__('Identifiant de plugin invalide', __FILE__) . ' : ' . $_pluginId);
    }
    $repo = self::findRepo($_pluginId);
    if (empty($repo['auto'])) {
      return 'ignored: auto update disabled for ' . $_pluginId;
    }
    $ref = is_array($_payload) ? ($_payload['ref'] ?? '') : '';
    $branch = $repo['branch'] ?? self::DEFAULT_BRANCH;
    if ($ref != '' && $ref != 'refs/heads/' . $branch) {
      return 'ignored: ' . $ref . ' is not refs/heads/' . $branch;
    }
    $option = array('plugin' => $_pluginId);
    $cron = cron::byClassAndFunction(__CLASS__, 'hookUpdate', $option);
    if (!is_object($cron)) {
      $cron = new cron();
      $cron->setClass(__CLASS__);
      $cron->setFunction('hookUpdate');
      $cron->setOption($option);
      $cron->setOnce(1);
      $cron->setTimeout(10);
    }
    // Lancée tout de suite ; l'horaire sert de filet si le lancement immédiat échoue
    $cron->setSchedule(cron::convertDateToCron(strtotime('+1 minute')));
    $cron->save();
    $cron->run(true);
    log::add(__CLASS__, 'debug', $_pluginId . ' : webhook ' . ($ref != '' ? $ref : '(sans ref)'));
    return 'ok: update of ' . $_pluginId . ' scheduled';
  }

  /** Tâche ponctuelle lancée par le webhook. */
  public static function hookUpdate($_options = array()) {
    try {
      self::autoUpdate(self::findRepo($_options['plugin'] ?? ''), 'webhook');
    } catch (Exception $e) {
      log::add(__CLASS__, 'error', $e->getMessage());
    }
  }

  /**
   * Remplace les fichiers d'un plugin par ceux de la branche de son dépôt.
   * Les fichiers absents de la branche ne sont pas supprimés. Lance ensuite la fonction de mise à jour du plugin.
   * @param string|false $_auto false (bouton), ou la source de la mise à jour automatique : cron, realtime, webhook
   */
  public static function updatePlugin($_pluginId, $_auto = false) {
    if (!self::isValidPluginId($_pluginId)) {
      throw new Exception(__('Identifiant de plugin invalide', __FILE__) . ' : ' . $_pluginId);
    }
    $repo = self::findRepo($_pluginId);
    $remote = self::remote($repo);

    $commit = array('', '');
    try {
      $commit = self::lastCommit($remote);
    } catch (Exception $e) {
      log::add(__CLASS__, 'debug', $_pluginId . ' : ' . $e->getMessage());
    }

    try {
      $data = self::httpGet($remote['archiveUrl'], $remote['headers']);
    } catch (Exception $e) {
      throw self::explain($e, $remote);
    }
    if (substr($data, 0, 2) != 'PK') {
      throw new Exception(__('Le serveur n\'a pas renvoyé une archive zip', __FILE__));
    }
    if (!class_exists('ZipArchive')) {
      throw new Exception(__('L\'extension PHP zip est requise', __FILE__));
    }

    $tmp = jeedom::getTmpFolder(__CLASS__) . '/update_' . $_pluginId;
    self::removeDir($tmp);
    mkdir($tmp . '/src', 0775, true);
    try {
      file_put_contents($tmp . '/archive.zip', $data);
      $zip = new ZipArchive();
      if ($zip->open($tmp . '/archive.zip') !== true || !$zip->extractTo($tmp . '/src')) {
        throw new Exception(__('Impossible d\'extraire l\'archive', __FILE__));
      }
      $zip->close();

      // L'archive contient en général un dossier racine "<dépôt>-<branche>/"
      $infoFiles = array_merge(glob($tmp . '/src/plugin_info/info.json') ?: array(), glob($tmp . '/src/*/plugin_info/info.json') ?: array());
      if (count($infoFiles) == 0) {
        throw new Exception(__('Archive invalide : plugin_info/info.json introuvable', __FILE__));
      }
      $source = dirname(dirname($infoFiles[0]));
      $info = json_decode(file_get_contents($infoFiles[0]), true);
      if (($info['id'] ?? '') != $_pluginId) {
        throw new Exception(__('Le dépôt contient le plugin', __FILE__) . ' « ' . ($info['id'] ?? '?') . ' » ' . __('et non', __FILE__) . ' « ' . $_pluginId . ' »');
      }
      $count = 0;
      self::copyDir($source, self::pluginDir($_pluginId), $count);
    } finally {
      self::removeDir($tmp);
    }

    // Comme une mise à jour classique : fonction "<id>_update" du plugin (s'il est installé et actif)
    $updateRun = false;
    $plugin = null;
    try {
      $plugin = plugin::byId($_pluginId);
    } catch (Throwable $e) {
      // Plugin pas encore installé : il reste à l'activer dans la gestion des plugins
    }
    try {
      if (is_object($plugin) && $plugin->isActive() && method_exists($plugin, 'callInstallFunction')) {
        $plugin->callInstallFunction('update', true);
        $updateRun = true;
      }
    } catch (Throwable $e) {
      log::add(__CLASS__, 'warning', $_pluginId . ' : ' . __('erreur dans la fonction de mise à jour du plugin', __FILE__) . ' : ' . $e->getMessage());
    }

    $last = array(
      'date' => date('Y-m-d H:i:s'),
      'branch' => $remote['branch'],
      'commit' => $commit[0],
      'message' => $commit[1],
      'version' => $info['pluginVersion'] ?? '',
      'files' => $count,
      'updateRun' => $updateRun,
      'auto' => $_auto,
    );
    $repos = self::getRepos();
    foreach ($repos as &$item) {
      if ($item['plugin'] == $_pluginId) {
        $item['last'] = $last;
      }
    }
    unset($item);
    self::saveRepos($repos);
    log::add(__CLASS__, 'info', $_pluginId . ' : ' . __('mis à jour', __FILE__) . ' ' . json_encode($last));
    return $last;
  }

  private static function copyDir($_source, $_destination, &$_count) {
    if (!is_dir($_destination) && !mkdir($_destination, 0775, true)) {
      throw new Exception(__('Impossible de créer le dossier', __FILE__) . ' ' . $_destination);
    }
    foreach (scandir($_source) as $item) {
      if (in_array($item, array('.', '..', '.git'))) {
        continue;
      }
      $from = $_source . '/' . $item;
      $to = $_destination . '/' . $item;
      if (is_dir($from)) {
        self::copyDir($from, $to, $_count);
      } else {
        if (!copy($from, $to)) {
          throw new Exception(__('Impossible d\'écrire', __FILE__) . ' ' . $to . ' ' . __('(droits du dossier du plugin ?)', __FILE__));
        }
        $_count++;
      }
    }
  }

  private static function removeDir($_dir) {
    if (!is_dir($_dir)) {
      return;
    }
    foreach (scandir($_dir) as $item) {
      if ($item == '.' || $item == '..') {
        continue;
      }
      $path = $_dir . '/' . $item;
      is_dir($path) ? self::removeDir($path) : unlink($path);
    }
    rmdir($_dir);
  }
}

class devrepoCmd extends cmd {
  public function execute($_options = array()) {
  }
}
