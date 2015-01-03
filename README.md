# WP C5 Exporter

A WordPress plugin for moving your WordPress blog content to your concrete5 site. You can export a file of your blog's content in order to import it into your concrete5 site. The export file will be an XML file format called concrete5 CIF (content import format). You can also download files of your blog in order to import these into your concrete5 site.

## Requires

* PHP 5.3+
* concrete5 5.7.3+
* WordPress 4.0+ (not yet tested)

## Build

### 1. Clone this repository.

### 2. Install gulp.

```sh
$ npm install --global gulp
```

[Getting Started with gulp](https://github.com/gulpjs/gulp/blob/master/docs/getting-started.md)

### 3. Install the required gulp plugins.

```sh
$ npm install --save-dev
```

### 4. Install Composer.

```sh
$ curl -sS https://getcomposer.org/installer | php
```

[Getting Started with Composer](https://getcomposer.org/doc/00-intro.md)

### 4. Run gulp to build a zip package.

```sh
$ gulp
```

## Contribute

Currently this plugin is a beta release. All contributions are welcome.
