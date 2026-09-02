---
title: Documentação
description: Plugin to manage Automobile clubs
---

Este plugin oferece gestão de veículos para clubes automóveis, permitindo-lhe
gerir :

* veículos (proprietário, várias informações, foto, etc.),
* Modificação do histórico do veículo (proprietário, cor, etc),
* marcas,
* modelos,
* tipos de transmissão,
* Tipos de carroceria
* cores,
* terminações,
* estados.

Este plugin foi inicialmente desenvolvido em colaboração com Anatole do [Club
404](https://www.leclub404.com/) e François do [Club Fiat
500](http://www.club500.fr/). Um grande agradecimento a eles pela valiosa ajuda
durante o desenvolvimento do plugin :)

## Instalação

Primeiramente, baixe o plugin:

* [Get latest Auto
  plugin!](https://github.com/galette-plugins/plugin-auto/releases/latest)
* [Get Auto plugin nightly
  build!](https://github.com/galette-plugins/plugin-auto/releases/tag/nightly)

Extraia o arquivo baixado no diretório `plugins` do Galette. Por exemplo, no
Linux (substituindo `{url}` e `{version}` pelos valores corretos):

```bash
$ cd /var/www/html/galette/plugins
$ wget {url}
$ tar xjvf galette-plugin-auto-{version}.tar.bz2
```

## Inicialização do banco de dados

Para funcionar, este plugin requer várias tabelas no banco de dados. Consulte
[Interface de gerenciamento de plugins do
Galette](https://doc.galette.eu/en/master/plugins/index.html#plugins-managment).

E está concluído; o plugin Auto está instalado :)

Não é necessária nenhuma configuração específica, basta inserir os dados no
banco de dados.

## Configure os campos obrigatórios

Ao adicionar um novo veículo ao banco de dados, existem vários campos
obrigatórios, mas que podem não atender às suas necessidades. Nesse caso, você
pode definir seus próprios campos obrigatórios: basta criar um arquivo
`local_auto_required.inc.php` no diretório `config` do seu Galette e declarar um
array com os campos que você deseja tornar obrigatórios. Por exemplo, se você
quiser exigir apenas o nome e o modelo de um carro, você precisará de:

```php
<?php
return array(
     'name'  => 1,
     'model' => 1
);
```
