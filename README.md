# OpenEuropa Multilingual

[![Build Status](https://drone.fpfis.eu/api/badges/openeuropa/oe_multilingual/status.svg?branch=master)](https://drone.fpfis.eu/openeuropa/oe_multilingual)

The OpenEuropa Multilingual module offers default multilingual features for the OpenEuropa project, like:

- Enable all 24 official EU languages
- Provide an optional language switcher block on the [OpenEuropa Theme][1] site header region
- Make sure that the administrative interface is always set to English
- Allow English to be translated so that default English copy can be fixed/improved

**Table of contents:**

- [Installation](#installation)
- [Development](#development)
  - [Project setup](#project-setup)
  - [Using Docker Compose](#using-docker-compose)
  - [Disable Drupal 8 caching](#disable-drupal-8-caching)
- [Demo module](#demo-module)

## Installation

The recommended way of installing the OpenEuropa Multilingual module is via a [Composer-based workflow][2].

In your Drupal project's main `composer.json` add the following dependency:

```json
{
    "require": {
        "openeuropa/oe_multilingual": "dev-master"
    }
}
```

And run:

```
$ composer update
```

### Enable the module

In order to enable the module in your project run:

```
$ ./vendor/bin/drush en oe_multilingual
```

## Development

The OpenEuropa Multilingual project contains all the necessary code and tools for an effective development process,
such as:

- All PHP development dependencies (Drupal core included) are required by [composer.json](composer.json)
- Project setup and installation can be easily handled thanks to the integration with the [Task Runner][3] project.
- All system requirements are containerized using [Docker Composer][4]

### Project setup

Download all required PHP code by running:

```
$ composer install
```

This will build a fully functional Drupal test site in the `./build` directory that can be used to develop and showcase
the module's functionality.

During development the module requires a fully functional OpenEuropa Theme to be present and enabled on the test site.

In order to achieve that we need to manually install and build all frontend-related theme dependencies as described in
the ["Project setup"][5] section of the OpenEuropa Theme documentation.

In short:

```
$ cd build/themes/contrib/oe_theme
$ npm install
$ npm run build
```

In order to fetch the required code you'll need to have [Node.js (>= 8)][6] installed locally.

Before setting up and installing the site make sure to customize default configuration values by copying [runner.yml.dist](runner.yml.dist)
to `./runner.yml` and overriding relevant properties.

To set up the project run:

```
$ ./vendor/bin/run drupal:site-setup
```

This will:

- Symlink the theme in  `./build/modules/custom/oe_multilingual` so that it's available for the test site
- Setup Drush and Drupal's settings using values from `./runner.yml.dist`
- Setup PHPUnit and Behat configuration files using values from `./runner.yml.dist`

After a successful setup install the site by running:

```
$ ./vendor/bin/run drupal:site-install
```

This will:

- Install the test site
- Enable the OpenEuropa Multilingual module
- Enable the OpenEuropa Multilingual Demo module and [Configuration development][7] modules
- Enable and set the OpenEuropa Theme as default

### Using Docker Compose

The setup procedure described above can be sensitively simplified by using Docker Compose.

Requirements:

- [Docker][8]
- [Docker-compose][9]

Run:

```
$ docker-compose up -d
```

Then:

```
$ docker-compose exec -u web web composer install
$ docker-compose exec -u node node npm install
$ docker-compose exec -u node node npm run build
$ docker-compose exec -u web web ./vendor/bin/run drupal:site-setup
$ docker-compose exec -u web web ./vendor/bin/run drupal:site-install
```

Your test site will be available at [http://localhost:8080/build](http://localhost:8080/build).

Run tests as follows:

```
$ docker-compose exec -u web web ./vendor/bin/phpunit
$ docker-compose exec -u web web ./vendor/bin/behat
```

### Disable Drupal 8 caching

Manually disabling Drupal 8 caching is a laborious process that is well described [here][10].

Alternatively you can use the following Drupal Console commands to disable/enable Drupal 8 caching:

```
$ ./vendor/bin/drupal site:mode dev  # Disable all caches.
$ ./vendor/bin/drupal site:mode prod # Enable all caches.
```

Note: to fully disable Twig caching the following additional manual steps are required:

1. Open `./build/sites/default/services.yml`
2. Set `cache: false` in `twig.config:` property. E.g.:
```
parameters:
 twig.config:
   cache: false
```
3. Rebuild Drupal cache: `./vendor/bin/drush cr`

This is due to the following [Drupal Console issue][11].

## Demo module

The OpenEuropa Multilingual module ships with a demo module which provides all the necessary configuration and code needed
to showcase the modules's most important features.

The demo module includes a translatable content type with automatic URL path generation.

In order to install the OpenEuropa Multilingual demo module follow the instructions [here][12] or enable it via [Drush][13]
by running:

```
$ ./vendor/bin/drush en oe_multilingual_demo -y
```

[1]: https://github.com/openeuropa/oe_theme
[2]: https://www.drupal.org/docs/develop/using-composer/using-composer-to-manage-drupal-site-dependencies#managing-contributed
[3]: https://github.com/openeuropa/task-runner
[4]: https://docs.docker.com/compose
[5]: https://github.com/openeuropa/oe_theme#project-setup
[6]: https://nodejs.org/en
[7]: https://www.drupal.org/project/config_devel
[8]: https://www.docker.com/get-docker
[9]: https://docs.docker.com/compose
[10]: https://www.drupal.org/node/2598914
[11]: https://github.com/hechoendrupal/drupal-console/issues/3854
[12]: https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
[13]: https://www.drush.org/
