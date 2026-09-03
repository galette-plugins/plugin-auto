---
title: Documentación
description: Plugin to manage Automobile clubs
---

Este plugin proporciona la gestión de vehículos para los clubes de automóviles,
puede gestionar:

* vehículos (propietario, informaciones varias, foto, etc),
* modificación del historial del vehículo (propietario, color, ...),
* marcas,
* modelos
* tipos de transmisión,
* tipos de cuerpo,
* colores,
* acabados,
* condiciones.

Este plugin ha sido desarrollado inicialmente en colaboración con Anatole de
[Club 404](https://www.leclub404.com/), y François de [club Fiat
500](http://www.club500.fr/). Muchas gracias por su valiosa ayuda durante el
desarrollo del plugin :)

## Instalación

Antes que todo, descargue el complemento:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extraer el archivo descargado en el directorio de Galette `plugins`. Por
ejemplo, bajo linux (reemplazar `{url}` y `{version}` con valores correctos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inicio de la base de datos

Para funcionar, este plugin requiere varias tablas en la base de datos. Ver
[Interfaz de gestión de plugins
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

Y esto está terminado; el plugin Auto está instalado :)

No se requiere ninguna configuración particular, basta con introducir los datos
en la base de datos.

## Configurar los campos obligatorios

Al añadir un nuevo vehículo en la base de datos, hay varios campos que son
obligatorios, pero que pueden no ajustarse a sus necesidades. En este caso,
puede definir sus propios campos requeridos: sólo tiene que crear un archivo
`local_auto_required.inc` en su directorio `config` de Galette y declarar una
matriz de los campos que desea requerir. Por ejemplo, si sólo quieres requerir
el nombre y el modelo de un coche, necesitarás:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
