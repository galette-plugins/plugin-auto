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

Lo primero de todo, descarga el complemento:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extrae el archivo descargado en la carpeta `plugin` de Galette . Por ejemplo, en
linux (sustituyendo `{url}` y `{version}` con los valores correctos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inicialización de base de datos

Para que funcione, este complemento necesita varias tablas en la base de datos.
Consulta [la interfaz de gestión de complementos de
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
