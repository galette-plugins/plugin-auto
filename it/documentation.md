---
title: Documentazione
description: Plugin to manage Automobile clubs
---

Questo componente aggiuntivo fornisce la gestione del parco auto per gli
automobile club. Puoi gestire:

* veicoli (proprietario, altre informazioni, foto, etc),
* storia delle modifiche del veicolo (proprietari, colore, ...),
* marche,
* modelli,
* tipo di trasmissione,
* tipo di carrozzeria,
* colori,
* finiture,
* condizioni.

Questo componente aggiuntivo è stato inizialmente sviluppato in collaborazione
con Anatole del [Club 404](https://www.leclub404.com/), e François del [club
Fiat 500](http://www.club500.fr/). Un grande grazie per il loro prezioso aiuto
durante lo sviluppo :)

## Installazione

Prima di tutto, scaricare il plugin:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Estrai l'archivio scaricato nella cartella `plugins` di Galette. Per esempio, su
Linux (sostituendo `{url}` e `{version}` con i rispettivi valori):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inizializzazione del database

Per poter funzionare, questo componente aggiuntivo richiede diverse nuove
tabelle nel database. Vedi [Interfaccia di gestione dei componenti aggiuntivi di
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Abbiamo finito; il componente aggiuntivo Auto è stato installato :)

Non ci sono particolari impostazioni richieste, puoi direttamente inserire i
dati nel database.

## Configura i campi necessari

Quando aggiungi un nuovo veicolo nel database, ci sono diversi campi necessari,
ma che potrebbero non interessarti. In questi casi, puoi definire i tuoi campi
personalizzati: crea un file `local_auto_required.inc.php` nella cartella
`config` di Galette e dichiara un vettore di campi di cui necessiti. Per
esempio, se hai bisogno solo il nome e il modello di un'auto, avrai bisogno di:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
