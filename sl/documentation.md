---
title: Dokumentacija
description: Plugin to manage Automobile clubs
---

Ta vtičnik omogoča upravljanje vozil za avtomobilske klube, upravljate lahko:

* vozila (lastnik, več informacij, fotografija itd.),
* sprememba zgodovine vozila (lastnik, barva, ...),
* blagovne znamke,
* modeli,
* vrste prenosov,
* tipi telesa,
* barve,
* sklepi,
* države.

Ta vtičnik je bil prvotno razvit v sodelovanju z Anatolejem iz kluba [Club
404](https://www.leclub404.com/) in Françoisom iz kluba [club Fiat
500](http://www.club500.fr/). Velika zahvala za dragoceno pomoč med razvojem
vtičnika :)

## Namestitev

Najprej prenesite vtičnik:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Razširite prenesen arhiv v imenik Galette `plugins`. Na primer v Linuxu
(zamenjajte `{url}` in `{version}` s pravilnimi vrednostmi):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inicializacija baze podatkov

Za delovanje ta vtičnik potrebuje več tabel v bazi podatkov. Glejte [Vmesnik za
upravljanje vtičnikov
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

In to je končano; vtičnik Auto je nameščen :)

Ni potrebna posebna nastavitev, podatke lahko le vnesete v bazo podatkov.

## Konfigurirajte obvezna polja

Pri dodajanju novega vozila v podatkovno bazo je potrebnih več polj, ki pa morda
ne ustrezajo vašim potrebam. V takem primeru lahko definirate svoja lastna
obvezna polja: preprosto ustvarite datoteko `local_auto_required.inc.php` v
imeniku `config` v Galette in deklarirajte polje s polji, ki jih želite
zahtevati. Na primer, če želite zahtevati le ime in model avtomobila, boste
potrebovali:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
