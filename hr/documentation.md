---
title: Dokumentacija
description: Dodatak za upravljanje automobilskim klubovima
---

Dodatak omogućuje automobilskim klubovima upravljanje:

* vozilima (vlasnik, razni podaci, fotografija i slično),
* poviješću izmjena vozila (vlasnik, boja i slično),
* markama,
* modelima,
* vrstama mjenjača,
* vrstama karoserije,
* bojama,
* paketima opreme,
* stanjima vozila.

Dodatak je izvorno razvijen u suradnji s Anatoleom iz [Club
404](https://www.leclub404.com/) i Françoisom iz [club Fiat
500](http://www.club500.fr/). Hvala im na dragocjenoj pomoći pri razvoju :)

## Instalacija

Najprije preuzmite dodatak:

* [Preuzmite najnoviji dodatak
  Auto!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Preuzmite noćnu razvojnu verziju dodatka
  Auto!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Raspakirajte preuzetu arhivu u Galetteovu mapu `plugins`. Primjerice, na Linuxu
(zamijenite `{url}` i `{version}` odgovarajućim vrijednostima):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inicijalizacija baze podataka

Dodatak zahtijeva nekoliko tablica u bazi. Pogledajte [sučelje za upravljanje
dodacima
Galettea](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Gotovo; dodatak Auto je instaliran :)

Nisu potrebne posebne postavke; možete odmah unositi podatke u bazu.

## Podešavanje obveznih polja

Pri dodavanju vozila neka su polja obvezna, što možda ne odgovara vašim
potrebama. Možete zadati vlastita obvezna polja: u Galetteovoj mapi `config`
izradite datoteku `local_auto_required.inc.php` i definirajte niz željenih
polja. Ako želite da obvezni budu samo naziv i model, unesite:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
