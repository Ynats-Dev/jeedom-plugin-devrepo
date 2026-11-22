<?php
/* Liste des plugins à mettre à jour (affichée sur la page du plugin). */
if (!isConnect('admin')) {
  throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<div class="dr-root">
  <div class="dr-toolbar">
    <div class="dr-section"><i class="fas fa-code-branch"></i><span>{{Plugins suivis}}</span> <span class="dr-count dr-total">0</span></div>
    <div class="dr-spacer"></div>
    <select class="form-control input-sm dr-realtime-duration" title="{{Durée du mode temps réel}}">
      <option value="15">15 min</option>
      <option value="30">30 min</option>
      <option value="60">1 h</option>
      <option value="120">2 h</option>
      <option value="240">4 h</option>
      <option value="480">8 h</option>
    </select>
    <a class="btn btn-default btn-sm dr-realtime" title="{{Vérifie les branches des plugins « Auto » toutes les 10 secondes pendant la durée choisie (pour le développement)}}"><i class="fas fa-bolt"></i> <span>{{Temps réel}}</span></a>
    <a class="btn btn-default btn-sm dr-check" title="{{Comparer avec le dernier commit de chaque branche}}"><i class="fas fa-sync-alt"></i> {{Vérifier}}</a>
    <a class="btn btn-default btn-sm dr-add"><i class="fas fa-plus"></i> {{Ajouter}}</a>
    <a class="btn btn-success btn-sm dr-save dr-clean" title="{{Enregistrer la liste}}"><i class="fas fa-check"></i> {{Enregistrer}}</a>
  </div>
  <p class="dr-hint">{{« Mettre à jour » télécharge la branche du dépôt (Gogs, Gitea ou GitHub) et remplace les fichiers du plugin, sans git sur la box. Les fichiers absents de la branche sont conservés. « Auto » met à jour le plugin à chaque nouveau commit : immédiatement avec un webhook, sinon vérification toutes les 5 minutes, ou toutes les 10 secondes en mode « Temps réel ».}}</p>

  <div class="dr-head">
    <span>{{Plugin}}</span><span>{{Dépôt}}</span><span>{{Branche}}</span><span>{{Jeton}}</span><span title="{{Mise à jour automatique à chaque nouveau commit (vérification toutes les 5 minutes)}}">{{Auto}}</span><span>{{Dernière MAJ}}</span><span></span>
  </div>
  <div class="dr-list"></div>
  <div class="dr-empty"><i class="fas fa-code-branch"></i>{{Aucun plugin suivi.}}<br><span class="dr-meta">{{Cliquez sur « Ajouter ».}}</span></div>
  <datalist class="dr-plugins"></datalist>
</div>
