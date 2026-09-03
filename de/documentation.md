---
title: Dokumentation
description: Plugin to manage Automobile clubs
---

Dieses Plugin bietet Fahrzeugmanagement für Automobilclubs, hier können Sie
diese verwalten:

* Fahrzeuge (Eigentümer, Zusatzinformationen, Foto usw.),
* Fahrzeughistorie Änderungen (Besitzer, Farbe, ...),
* Marken,
* Modelle,
* Getriebe Typen,
* body types,
* Farben,
* finitions,
* Zustände.

Dieses Plugin wurde in Zusammenarbeit mit Anatole von [Club
404](https://www.leclub404.com/) und François von [club Fiat
500](http://www.club500.fr/) entwickelt. Ein großer Dank gilt ihnen für ihre
wertvolle Hilfe bei der Plugin-Entwicklung :)

## Installation

Als erstes, Lade das Plugin herunter:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extract the downloaded archive in Galette `plugins` directory. For example,
under linux (replacing `{url}` and `{version}` with correct values):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Datenbank Initialisierung

Damit es Funktioniert, benötigt dieses Plugin verschiedene Tabellen in der
Datenbank. Weiteres sehen sie hier [Galette plugins management
interface](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Und es ist abgeschlossen; Auto Plugin ist installiert :)

Es ist kein bestimmtes Setup erforderlich, Sie können einfach Daten in die
Datenbank eingeben.

## Benötigte Felder konfigurieren

Beim Hinzufügen eines neuen Fahrzeugs in der Datenbank gibt es mehrere Felder,
die benötigt werden, die jedoch nicht Ihren Bedürfnissen entsprechen. In diesem
Fall können Sie Ihre eigenen benötigten Felder definieren: Erstellen Sie einfach
ein `local_auto_required.inc.php` in Ihrem Galette `config`-Verzeichnis und
deklarieren Sie ein Array der Felder, die Sie benötigen. Wenn Sie beispielsweise
nur Name und Modell für ein Auto benötigen möchten, benötigen Sie:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
