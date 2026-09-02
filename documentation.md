---
title: Documentation
description: Plugin to manage Automobile clubs
---

This plugin provides vehicles management for automobile clubs, you can manage:

* vehicles (owner, several information, photo, etc),
* vehicle history modification (owner, color, ...),
* brands,
* models,
* transmission types,
* body types,
* colors,
* finitions,
* states.

This plugin has been initially developed in collaboration with Anatole from [Club 404](https://www.leclub404.com/), and François from [club Fiat 500](http://www.club500.fr/). A big thanks to them for their precious help during plugin development :)

## Installation

First of all, download the plugin:

* [Get latest Auto plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extract the downloaded archive in Galette `plugins` directory. For example, under linux (replacing `{url}` and `{version}` with correct values):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Database initialisation

In order to work, this plugin requires several tables in the database. See [Galette plugins management interface](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

And this is finished; Auto plugin is installed :)

There is no particular setup required, you can just enter data in the database.

## Configure required fields

When adding a new vehicle in database, there are several fields that are required, but that may not fit your needs. In such case, you can define your own required fields: just create a `local_auto_required.inc.php` file in your Galette `config` directory and declare an array of the fields you want to require. As example, if you just want to require name and model for a car, you will need:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
