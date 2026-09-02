---
title: Documentation
description: Plugin to manage Automobile clubs
---

Ce plugin fournit une gestion de véhicules pour des clubs automobiles, vous
pouvez gérer :

* des véhicules (propriétaires, informations diverses, photo, etc),
* l'historique des modifications des véhicules (propriétaire, couleur, ...)
* marques,
* modèles,
* types de transmission,
* types de carrosseries,
* couleurs,
* finitions,
* états.

Ce plugin a été initialement développé en collaboration avec Anatole du [Club
404](https://www.leclub404.com/), et François du [club Fiat
500](http://www.club500.fr/). Un grand merci à eux pour leur aide précieuse
durant le développement du plugin :)

## Installation

Tout d'abord, téléchargez le plugin :

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extrayez l'archive téléchargée dans le dossier `plugins` de Galette. Par
exemple, sous linux (en remplaçant `{url}` et `{version}` par les valeurs
adéquates) :

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Initialisation de la base de données

Pour fonctionner, ce plugin requiert des tables dans la base de données.
Référez-vous [à l'interface de gestion des plugins de
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Et c'est terminé, le plugin Auto est installé :)

Il n'y a aucune configuration requise, vous pouvez juste entrer vos données en
base.

## Configuration des champs requis

Lors de l'ajout d'un nouveau véhicule en base, plusieurs champs sont requis,
mais cela peut ne pas correspondre à vos besoins. Si tel est le cas, vous pouvez
définir vos propres champs requis : créez simplement un fichier
`local_auto_required.inc.php` dans le dossier `config` de votre Galette et
déclarez-y un tableau des champs que vous voulez requérir. Par exemple, si vous
souhaitez juste que les nom et modèle soient obligatoires, vous aurez :

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
