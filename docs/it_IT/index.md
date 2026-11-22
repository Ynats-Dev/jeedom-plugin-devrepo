---
layout: default
lang: it_IT
---

Descrizione
===

DevRepo aggiorna i tuoi plugin **in sviluppo** direttamente da un branch del loro repository Git (**Gogs**, **Gitea** o **GitHub**), senza git né accesso SSH al box. È particolarmente comodo quando Jeedom gira in un container Docker.

Per ogni plugin seguito, un clic su **Aggiorna** scarica l'archivio del branch, verifica che si tratti del plugin giusto, ne sostituisce i file e poi esegue la sua funzione di aggiornamento (come un normale aggiornamento dal Market).

> Riservato allo sviluppo: i file assenti dal branch non vengono eliminati e non viene effettuato alcun backup prima dell'aggiornamento.

Configurazione
===

![Elenco dei plugin seguiti](../images/devrepo_screenshot.png)

L'elenco dei plugin seguiti si trova nella pagina del plugin: **Plugin → Programmazione → DevRepo**. Per ogni plugin:

- **Plugin**: il suo identificativo (nome della sua cartella in `plugins/`), es. `groupir`. Vengono proposti i plugin installati. È possibile indicare un plugin non ancora installato: DevRepo crea la sua cartella, resta solo da attivarlo nella gestione dei plugin;
- **Repository**: URL del repository, es. `https://server/proprietario/repository.git` o `https://github.com/proprietario/repository`;
- **Branch**: branch da seguire (`beta` per impostazione predefinita);
- **Auto**: aggiorna automaticamente il plugin a ogni nuovo commit sul branch (vedi sotto);
- **Token**: necessario se il repository è privato (Gogs / Gitea: *Impostazioni → Applicazioni*; GitHub: *Settings → Developer settings → Personal access tokens*). Viene memorizzato **cifrato** e non viene mai più visualizzato: lascia il campo vuoto per conservarlo, oppure usa la gomma per eliminarlo.

Clicca su **Salva** (l'elenco viene salvato automaticamente anche prima di un aggiornamento).

Utilizzo
===

- **Verifica**: confronta l'ultimo aggiornamento di ogni plugin con l'ultimo commit del suo branch (*già aggiornato* o *nuovo commit*);
- **Aggiorna**: scarica il branch e sostituisce i file del plugin. La colonna *Ultimo aggiornamento* indica la data e il commit installati (dettaglio al passaggio del mouse).

Aggiornamento automatico
===

Per i plugin con la casella **Auto** selezionata, DevRepo controlla il branch **ogni 5 minuti**: non appena viene inviato un nuovo commit, il plugin viene aggiornato come con il pulsante *Aggiorna*. Un'icona a forma di robot in *Ultimo aggiornamento* segnala un aggiornamento effettuato automaticamente.

- L'attività corrisponde alla riga **cron5** del pannello *Funzionalità* della configurazione del plugin: disattivarla sospende tutti gli aggiornamenti automatici;
- se l'aggiornamento a un commit fallisce, non viene ritentato in loop: DevRepo attende il commit successivo (o un clic su *Aggiorna*). L'errore è nel log `devrepo`;
- un repository temporaneamente irraggiungibile viene semplicemente ricontrollato al passaggio successivo.

Webhook: aggiornamento immediato
===

Invece di attendere la verifica, il repository può avvisare Jeedom a ogni push. Il pulsante <i class="fas fa-plug"></i> di una riga mostra l'URL del webhook di quel plugin (accesso interno ed esterno di Jeedom):

- nel repository: *Impostazioni → Webhooks* (Gogs, Gitea o GitHub), incolla l'URL, evento **push**, tipo di contenuto **JSON**;
- l'aggiornamento avviene solo se **Auto** è selezionato e il branch inviato è quello seguito;
- il repository deve poter raggiungere Jeedom: un Gogs / Gitea della rete locale usa l'accesso interno; GitHub richiede l'accesso esterno.

L'URL contiene la chiave API di DevRepo (*Impostazioni → Sistema → Configurazione → API*): non condividerla.

Modalità tempo reale
===

Se il repository non può raggiungere Jeedom (GitHub e box non esposto su internet), il pulsante **Tempo reale** verifica i branch dei plugin **Auto** **ogni 10 secondi per 1 ora** (predefinito), il tempo di una sessione di sviluppo. Il pulsante mostra il tempo rimanente; un clic la arresta. Durante questa modalità, la verifica ogni 5 minuti è sospesa.

La durata si sceglie nell'elenco accanto al pulsante: da 15 minuti a 8 ore. Viene applicata alla prossima attivazione.

Su GitHub senza token (al massimo 60 richieste all'ora), la verifica è distanziata a 90 secondi: inserisci un token per sfruttare i 10 secondi.

Notifica
===

Ogni aggiornamento automatico (webhook, tempo reale o verifica ogni 5 minuti) mostra una notifica su tutte le pagine di Jeedom aperte, con il commit installato, o l'errore in caso di fallimento. Se la pagina DevRepo è aperta, la riga del plugin viene aggiornata.

DevRepo può anche aggiornare se stesso: aggiungi `devrepo` all'elenco con il suo repository.

In caso di errore viene visualizzato un messaggio esplicito (repository non trovato, token rifiutato, archivio che non contiene il plugin giusto, permessi di scrittura...). Il dettaglio è nel log `devrepo`.
