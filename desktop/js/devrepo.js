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

/* DevRepo : liste des plugins suivis et mise à jour depuis leur branche */
(function () {
  var AJAX_URL = 'plugins/devrepo/core/ajax/devrepo.ajax.php'

  function alertMsg(_message, _level) {
    if (typeof jeedomUtils !== 'undefined' && typeof jeedomUtils.showAlert === 'function') {
      jeedomUtils.showAlert({ message: _message, level: _level })
    } else {
      $('#div_alert').showAlert({ message: _message, level: _level })
    }
  }

  function confirmBox(_question, _callback) {
    if (typeof jeeDialog !== 'undefined' && typeof jeeDialog.confirm === 'function') {
      jeeDialog.confirm(_question, function (ok) { if (ok) _callback() })
    } else {
      bootbox.confirm(_question, function (ok) { if (ok) _callback() })
    }
  }

  /** Texte brut d'un message d'erreur Jeedom (le core l'entoure de <span id="span_errorMessage">). DOMParser n'exécute rien. */
  function plainText(_message) {
    if (typeof _message !== 'string' || _message.indexOf('<') === -1) {
      return _message
    }
    var doc = new DOMParser().parseFromString(_message, 'text/html')
    return (doc.body.textContent || '').trim()
  }

  function call(_action, _data) {
    var deferred = $.Deferred()
    $.ajax({
      type: 'POST',
      url: AJAX_URL,
      data: $.extend({ action: _action }, _data || {}),
      dataType: 'json',
      global: false,
      error: function (request, status, error) {
        alertMsg('{{Erreur de communication avec Jeedom}} : ' + error, 'danger')
        deferred.reject('{{Erreur de communication avec Jeedom}}')
      },
      success: function (data) {
        if (data.state != 'ok') {
          var message = plainText(data.result)
          alertMsg(message, 'danger')
          deferred.reject(message)
          return
        }
        deferred.resolve(data.result)
      }
    })
    return deferred.promise()
  }

  function lastText(_last) {
    if (!_last || !_last.date) {
      return '{{jamais}}'
    }
    return _last.date + ' · ' + _last.branch + (_last.commit ? ' @ ' + _last.commit : '') + (_last.version ? ' · v' + _last.version : '') + (_last.auto ? ' · {{auto}}' : '')
  }

  /** Date courte : « 01:39 » aujourd'hui, sinon « 19/11 01:39 » (ou « 19/11/2025 » une autre année). */
  function shortDate(_date) {
    var m = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2})/.exec(_date || '')
    if (!m) return _date || ''
    var now = new Date()
    var pad = function (n) { return (n < 10 ? '0' : '') + n }
    var today = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
    var time = m[4] + ':' + m[5]
    if (today == m[1] + '-' + m[2] + '-' + m[3]) return '{{aujourd\'hui}} ' + time
    if (now.getFullYear() == +m[1]) return m[3] + '/' + m[2] + ' ' + time
    return m[3] + '/' + m[2] + '/' + m[1]
  }

  function mount(_root) {
    var root = $(_root)
    if (root.hasClass('dr-mounted')) {
      return
    }
    root.addClass('dr-mounted')
    var listId = 'dr_plugins_' + Math.random().toString(36).slice(2)
    root.find('.dr-plugins').attr('id', listId)
    var dirty = false

    function refreshEmpty() {
      var count = root.find('.dr-row').length
      root.find('.dr-empty').toggle(count == 0)
      root.find('.dr-head').toggle(count > 0)
      root.find('.dr-total').text(count)
    }

    function setDirty(_dirty) {
      dirty = _dirty
      root.find('.dr-save').toggleClass('dr-clean', !dirty)
    }

    /** Confirmation visible sur la ligne du plugin après une mise à jour réussie. */
    function showDone(_row, _summary) {
      _row.addClass('dr-done').find('.dr-check-result').empty()
        .append($('<span class="dr-badge is-ok">').html('<i class="fas fa-check"></i> ').append(document.createTextNode('{{mis à jour}} · ' + _summary)))
    }

    function addRow(_repo) {
      _repo = _repo || {}
      var row = $('<div class="dr-row">').attr('data-plugin', _repo.plugin || '')
      var plugin = $('<div class="dr-cell dr-plugin">')
      plugin.append($('<input class="form-control input-sm dr-field" data-key="plugin" placeholder="{{id du plugin, ex. groupir}}">').attr('list', listId).val(_repo.plugin || ''))
      var meta = $('<div class="dr-meta dr-plugin-meta">')
      if (_repo.plugin) {
        if (_repo.installed) {
          meta.text(_repo.name + (_repo.version ? ' · v' + _repo.version : '')).attr('title', meta.text())
        } else {
          meta.append('<span class="dr-badge is-warn">{{non installé}}</span>')
        }
      }
      plugin.append(meta)
      row.append(plugin)
      row.append($('<div class="dr-cell">').append($('<input class="form-control input-sm dr-field" data-key="repo" placeholder="{{https://serveur/propriétaire/dépôt.git}}">').val(_repo.repo || '')))
      row.append($('<div class="dr-cell">').append($('<input class="form-control input-sm dr-field" data-key="branch" placeholder="beta">').val(_repo.branch || '')))
      var token = $('<div class="dr-cell dr-token">')
      token.append($('<input type="password" autocomplete="new-password" class="form-control input-sm dr-field" data-key="token">').attr('placeholder', _repo.hasToken ? '•••••• {{enregistré}}' : '{{aucun (public)}}'))
      if (_repo.hasToken) {
        token.append('<a class="btn btn-default btn-sm dr-token-clear" title="{{Supprimer le jeton enregistré}}"><i class="fas fa-eraser"></i></a>')
      }
      row.append(token)
      row.append($('<div class="dr-cell dr-auto">').append($('<input type="checkbox" class="dr-field" data-key="auto" title="{{Mise à jour automatique à chaque nouveau commit}}">').prop('checked', !!_repo.auto)))
      var last = $('<div class="dr-cell dr-last">')
      var line = $('<div class="dr-last-line">').appendTo(last)
      if (_repo.last && _repo.last.date) {
        line.attr('title', lastText(_repo.last) + (_repo.last.message ? '\n' + _repo.last.message : ''))
        line.append($('<span>').text(shortDate(_repo.last.date)))
        if (_repo.last.commit) {
          line.append($('<code class="dr-commit">').text(_repo.last.commit))
        }
        if (_repo.last.auto) {
          line.append('<i class="fas fa-robot dr-auto-mark" title="{{Mise à jour automatique}}"></i>')
        }
      } else {
        line.append('<span class="dr-never">{{jamais}}</span>')
      }
      line.append('<span class="dr-check-result"></span>')
      row.append(last)
      row.append('<div class="dr-cell dr-actions"><a class="btn btn-default btn-sm dr-hook" title="{{Webhook : mise à jour immédiate à chaque push}}"><i class="fas fa-plug"></i></a><a class="btn btn-warning btn-sm dr-update" title="{{Télécharger la branche et remplacer les fichiers}}"><i class="fas fa-download"></i> {{Mettre à jour}}</a><a class="btn btn-default btn-sm dr-remove" title="{{Retirer de la liste}}"><i class="fas fa-times"></i></a></div>')
      row.data('lastCommit', _repo.last ? _repo.last.commit : '')
      root.find('.dr-list').append(row)
      refreshEmpty()
      return row
    }

    var webhook = {}
    var realtimeEnd = 0
    var realtimeTimer = null
    var realtimeDuration = 60

    /** Durée lisible : 15 min, 1 h, 1 h 30... */
    function formatDuration(_minutes) {
      if (_minutes < 60) return _minutes + ' min'
      return Math.floor(_minutes / 60) + ' h' + (_minutes % 60 ? ' ' + (_minutes % 60) : '')
    }

    /** Panneau sous la ligne : URL(s) du webhook à déclarer dans le dépôt. */
    function toggleHook(_row) {
      var panel = _row.find('.dr-hook-panel')
      if (panel.length) {
        panel.remove()
        return
      }
      var id = $.trim(_row.find('.dr-field[data-key=plugin]').val())
      panel = $('<div class="dr-hook-panel">').appendTo(_row)
      if (id == '' || _row.attr('data-plugin') != id) {
        panel.append($('<div class="dr-meta">').text('{{Enregistrez d\'abord la liste.}}'))
        return
      }
      var labels = { internal: '{{Accès interne}}', external: '{{Accès externe}}' }
      $.each(webhook, function (mode, url) {
        var full = url + '&plugin=' + encodeURIComponent(id)
        var line = $('<div class="dr-hook-line">').appendTo(panel)
        line.append($('<span class="dr-hook-label">').text(labels[mode] || mode))
        line.append($('<input class="form-control input-sm" readonly>').val(full))
        line.append('<a class="btn btn-default btn-sm dr-hook-copy" title="{{Copier}}"><i class="far fa-copy"></i></a>')
      })
      panel.append($('<div class="dr-meta">').text('{{Dans le dépôt : Paramètres → Webhooks, événement « push », type de contenu JSON. La mise à jour ne se fait que si « Auto » est coché et que la branche poussée est celle suivie. Le dépôt doit pouvoir joindre Jeedom (accès externe pour GitHub).}}'))
    }

    /** Bouton « Temps réel » : actif pendant une durée limitée, avec le temps restant. */
    function setRealtime(_seconds) {
      realtimeEnd = _seconds > 0 ? Date.now() + _seconds * 1000 : 0
      clearInterval(realtimeTimer)
      refreshRealtime()
      if (realtimeEnd) {
        realtimeTimer = setInterval(refreshRealtime, 15000)
      }
    }

    function refreshRealtime() {
      var button = root.find('.dr-realtime')
      var remaining = Math.max(0, Math.ceil((realtimeEnd - Date.now()) / 60000))
      if (remaining <= 0) {
        clearInterval(realtimeTimer)
        realtimeEnd = 0
      }
      button.toggleClass('btn-danger dr-live', remaining > 0).toggleClass('btn-default', remaining <= 0)
      button.find('span').text(remaining > 0 ? '{{Temps réel}} · ' + remaining + ' min' : '{{Temps réel}}')
      button.attr('title', remaining > 0 ? '{{Arrêter le mode temps réel}}' : '{{Vérifie les branches des plugins « Auto » toutes les 10 secondes pendant la durée choisie (pour le développement)}}')
    }

    function render(_result) {
      webhook = _result.webhook || {}
      realtimeDuration = parseInt(_result.realtimeDuration, 10) || 60
      root.find('.dr-realtime-duration').val(String(realtimeDuration))
      setRealtime(_result.realtime || 0)
      root.find('.dr-list').empty()
      var datalist = root.find('.dr-plugins').empty()
      $.each(_result.plugins || [], function (i, p) {
        $('<option>').val(p.id).text(p.name).appendTo(datalist)
      })
      $.each(_result.repos || [], function (i, repo) {
        addRow(repo)
      })
      setDirty(false)
      refreshEmpty()
    }

    function collect() {
      return root.find('.dr-row').map(function () {
        var item = {}
        $(this).find('.dr-field').each(function () {
          item[$(this).attr('data-key')] = $(this).is(':checkbox') ? ($(this).is(':checked') ? 1 : 0) : $.trim($(this).val())
        })
        if ($(this).data('clearToken') && item.token == '') {
          item.token = '__clear__'
        }
        return item
      }).get()
    }

    function load() {
      return call('getRepos').then(function (result) {
        render(result)
        return result
      })
    }

    function save() {
      return call('saveRepos', { repos: JSON.stringify(collect()) }).then(function () {
        return load()
      })
    }

    root.on('input change', '.dr-field', function () {
      setDirty(true)
    })
    root.on('click', '.dr-add', function () {
      addRow({}).find('.dr-field[data-key=plugin]').focus()
      setDirty(true)
    })
    root.on('click', '.dr-remove', function () {
      $(this).closest('.dr-row').remove()
      refreshEmpty()
      setDirty(true)
    })
    root.on('click', '.dr-token-clear', function () {
      var row = $(this).closest('.dr-row')
      row.data('clearToken', true)
      row.find('.dr-field[data-key=token]').val('').attr('placeholder', '{{sera supprimé à l\'enregistrement}}')
      $(this).remove()
      setDirty(true)
    })
    root.on('click', '.dr-save', function () {
      save().then(function () {
        alertMsg('{{Liste enregistrée}}', 'success')
      })
    })
    root.on('click', '.dr-check', function () {
      var button = $(this).addClass('disabled')
      button.find('i').addClass('fa-spin')
      var start = dirty ? save() : $.Deferred().resolve().promise()
      start.then(function () {
        return call('checkAll')
      }).then(function (result) {
        root.find('.dr-row').each(function () {
          var info = result[$(this).attr('data-plugin')]
          var box = $(this).find('.dr-check-result').empty()
          if (!info) return
          if (info.error) {
            box.append($('<span class="dr-badge is-error">').text(info.error))
          } else if (info.commit && info.commit == $(this).data('lastCommit')) {
            box.append('<span class="dr-badge is-ok"><i class="fas fa-check"></i> {{à jour}}</span>')
          } else {
            box.append($('<span class="dr-badge is-new">').html('<i class="fas fa-arrow-up"></i> ').append(document.createTextNode(info.commit)).attr('title', '{{Nouveau commit}} : ' + info.message))
          }
        })
      }).always(function () {
        button.removeClass('disabled').find('i').removeClass('fa-spin')
      })
    })
    root.on('click', '.dr-hook', function () {
      toggleHook($(this).closest('.dr-row'))
    })
    root.on('click', '.dr-hook-copy', function () {
      var input = $(this).siblings('input')
      input.trigger('select')
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(input.val())
      } else {
        document.execCommand('copy')
      }
      alertMsg('{{URL copiée}}', 'success')
    })
    root.on('change', '.dr-realtime-duration', function () {
      var select = $(this)
      call('setRealtimeDuration', { minutes: select.val() }).then(function (minutes) {
        realtimeDuration = minutes
        select.val(String(minutes))
        alertMsg('{{Durée du mode temps réel}} : ' + formatDuration(minutes) + (realtimeEnd ? ' ({{prise en compte à la prochaine activation}})' : ''), 'success')
      }, function () {
        select.val(String(realtimeDuration))
      })
    })
    root.on('click', '.dr-realtime', function () {
      var enable = !realtimeEnd
      if (enable && root.find('.dr-field[data-key=auto]:checked').length == 0) {
        alertMsg('{{Cochez « Auto » pour au moins un plugin}}', 'warning')
        return
      }
      var button = $(this).addClass('disabled')
      var start = dirty ? save() : $.Deferred().resolve().promise()
      start.then(function () {
        return call('setRealtime', { enable: enable ? 1 : 0 })
      }).then(function (seconds) {
        setRealtime(seconds)
        alertMsg(enable ? '{{Mode temps réel activé}} : ' + formatDuration(Math.round(seconds / 60)) : '{{Mode temps réel arrêté}}', 'success')
      }).always(function () {
        button.removeClass('disabled')
      })
    })

    // Mise à jour automatique faite par Jeedom (webhook, temps réel, cron) : la ligne est rafraîchie
    $('body').on('devrepo::updated', function (_event, _option) {
      if (!_option || !_option.plugin || !document.body.contains(root[0])) return
      var summary = _option.last ? (_option.last.files + ' {{fichiers}}' + (_option.last.version ? ' · v' + _option.last.version : '')) : ''
      var mark = function () {
        showDone(root.find('.dr-row').filter(function () { return $(this).attr('data-plugin') == _option.plugin }), summary)
      }
      if (dirty) {
        mark() // ne pas écraser les modifications en cours
      } else {
        load().then(mark)
      }
    })

    root.on('click', '.dr-update', function () {
      var row = $(this).closest('.dr-row')
      var button = $(this)
      var id = $.trim(row.find('.dr-field[data-key=plugin]').val())
      var branch = $.trim(row.find('.dr-field[data-key=branch]').val()) || 'beta'
      if (id == '') {
        alertMsg('{{Indiquez d\'abord l\'id du plugin}}', 'warning')
        return
      }
      confirmBox('{{Remplacer les fichiers du plugin}} « ' + $('<div>').text(id).html() + ' » {{par ceux de la branche}} « ' + $('<div>').text(branch).html() + ' » ?', function () {
        button.addClass('disabled').find('i').attr('class', 'fas fa-spinner fa-spin')
        row.removeClass('dr-done dr-failed').find('.dr-check-result').empty()
          .append('<span class="dr-badge"><i class="fas fa-spinner fa-spin"></i> {{mise à jour…}}</span>')
        var start = dirty ? save() : $.Deferred().resolve().promise()
        start.then(function () {
          return call('updatePlugin', { plugin: id })
        }).then(function (last) {
          var summary = last.files + ' {{fichiers}}' + (last.version ? ' · v' + last.version : '') + (last.updateRun ? ' · {{fonction de mise à jour lancée}}' : '')
          alertMsg(id + ' {{mis à jour}} : ' + lastText(last) + ' · ' + summary, 'success')
          // DevRepo vient de se mettre à jour lui-même : on recharge la page
          if (id == 'devrepo') {
            showDone(row, summary + ' · {{rechargement de la page…}}')
            setTimeout(function () { window.location.reload() }, 3000)
            return
          }
          return load().then(function () {
            showDone(root.find('.dr-row').filter(function () { return $(this).attr('data-plugin') == id }), summary)
          })
        }, function (error) {
          row.addClass('dr-failed').find('.dr-check-result').empty()
            .append($('<span class="dr-badge is-error">').text('{{échec}}' + (error ? ' : ' + error : '')))
        }).always(function () {
          button.removeClass('disabled').find('i').attr('class', 'fas fa-download')
        })
      })
    })

    load()
  }

  $('.dr-root').each(function () {
    mount(this)
  })
})()
