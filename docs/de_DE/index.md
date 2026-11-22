---
layout: default
lang: de_DE
---

Beschreibung
===

DevRepo aktualisiert Ihre **in Entwicklung befindlichen** Plugins direkt aus einem Branch ihres Git-Repositorys (**Gogs**, **Gitea** oder **GitHub**), ohne git oder SSH-Zugriff auf die Box. Das ist besonders praktisch, wenn Jeedom in einem Docker-Container läuft.

Für jedes verfolgte Plugin lädt ein Klick auf **Aktualisieren** das Archiv des Branches herunter, prüft, ob es sich um das richtige Plugin handelt, ersetzt seine Dateien und führt anschließend seine Update-Funktion aus (wie bei einem normalen Update aus dem Market).

> Nur für die Entwicklung: Dateien, die im Branch fehlen, werden nicht gelöscht, und vor dem Update wird keine Sicherung erstellt.

Konfiguration
===

![Liste der verfolgten Plugins](../images/devrepo_screenshot.png)

Die Liste der verfolgten Plugins befindet sich auf der Seite des Plugins: **Plugins → Programmierung → DevRepo**. Für jedes Plugin:

- **Plugin**: seine ID (Name seines Ordners in `plugins/`), z. B. `groupir`. Installierte Plugins werden vorgeschlagen. Ein noch nicht installiertes Plugin kann angegeben werden: DevRepo erstellt seinen Ordner, anschließend muss es nur noch in der Plugin-Verwaltung aktiviert werden;
- **Repository**: URL des Repositorys, z. B. `https://server/besitzer/repository.git` oder `https://github.com/besitzer/repository`;
- **Branch**: zu verfolgender Branch (standardmäßig `beta`);
- **Auto**: aktualisiert das Plugin automatisch bei jedem neuen Commit auf dem Branch (siehe unten);
- **Token**: erforderlich, wenn das Repository privat ist (Gogs / Gitea: *Einstellungen → Anwendungen*; GitHub: *Settings → Developer settings → Personal access tokens*). Es wird **verschlüsselt** gespeichert und nie wieder angezeigt: Lassen Sie das Feld leer, um es zu behalten, oder verwenden Sie den Radiergummi, um es zu löschen.

Klicken Sie auf **Speichern** (die Liste wird vor einem Update auch automatisch gespeichert).

Verwendung
===

- **Prüfen**: vergleicht das letzte Update jedes Plugins mit dem letzten Commit seines Branches (*aktuell* oder *neuer Commit*);
- **Aktualisieren**: lädt den Branch herunter und ersetzt die Dateien des Plugins. Die Spalte *Letztes Update* zeigt Datum und Commit der installierten Version (Details beim Überfahren mit der Maus).

Automatische Aktualisierung
===

Bei Plugins mit aktiviertem Kästchen **Auto** prüft DevRepo den Branch **alle 5 Minuten**: Sobald ein neuer Commit gepusht wird, wird das Plugin wie mit der Schaltfläche *Aktualisieren* aktualisiert. Ein Roboter-Symbol in *Letztes Update* kennzeichnet ein automatisch durchgeführtes Update.

- Die Aufgabe entspricht der Zeile **cron5** im Bereich *Funktionalitäten* der Plugin-Konfiguration: Wird sie deaktiviert, werden alle automatischen Updates ausgesetzt;
- schlägt das Update auf einen Commit fehl, wird es nicht endlos wiederholt: DevRepo wartet auf den nächsten Commit (oder einen Klick auf *Aktualisieren*). Der Fehler steht im Log `devrepo`;
- ein vorübergehend nicht erreichbares Repository wird beim nächsten Durchlauf einfach erneut geprüft.

Webhook: sofortige Aktualisierung
===

Statt auf die Prüfung zu warten, kann das Repository Jeedom bei jedem Push benachrichtigen. Die Schaltfläche <i class="fas fa-plug"></i> einer Zeile zeigt die Webhook-URL dieses Plugins (interner und externer Zugriff auf Jeedom):

- im Repository: *Einstellungen → Webhooks* (Gogs, Gitea oder GitHub), URL einfügen, Ereignis **push**, Inhaltstyp **JSON**;
- die Aktualisierung erfolgt nur, wenn **Auto** aktiviert ist und der gepushte Branch der verfolgte ist;
- das Repository muss Jeedom erreichen können: ein Gogs / Gitea im lokalen Netz verwendet den internen Zugriff; GitHub benötigt den externen Zugriff.

Die URL enthält den API-Schlüssel von DevRepo (*Einstellungen → System → Konfiguration → API*): Geben Sie ihn nicht weiter.

Echtzeitmodus
===

Kann das Repository Jeedom nicht erreichen (GitHub und eine nicht im Internet erreichbare Box), prüft die Schaltfläche **Echtzeit** die Branches der **Auto**-Plugins **1 Stunde lang alle 10 Sekunden** (Standard), für die Dauer einer Entwicklungssitzung. Die Schaltfläche zeigt die verbleibende Zeit an; ein Klick beendet den Modus. Währenddessen ist die Prüfung alle 5 Minuten ausgesetzt.

Die Dauer wird in der Liste neben der Schaltfläche gewählt: von 15 Minuten bis 8 Stunden. Sie gilt ab der nächsten Aktivierung.

Bei GitHub ohne Token (höchstens 60 Anfragen pro Stunde) wird die Prüfung auf 90 Sekunden verlängert: Geben Sie ein Token ein, um die 10 Sekunden zu nutzen.

Benachrichtigung
===

Jede automatische Aktualisierung (Webhook, Echtzeit oder Prüfung alle 5 Minuten) zeigt auf allen geöffneten Jeedom-Seiten eine Benachrichtigung mit dem installierten Commit oder dem Fehler an. Ist die DevRepo-Seite geöffnet, wird die Zeile des Plugins aktualisiert.

DevRepo kann sich auch selbst aktualisieren: Fügen Sie `devrepo` mit seinem Repository zur Liste hinzu.

Im Fehlerfall wird eine eindeutige Meldung angezeigt (Repository nicht gefunden, Token abgelehnt, Archiv enthält nicht das richtige Plugin, Schreibrechte...). Details stehen im Log `devrepo`.
