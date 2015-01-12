wp fix-ngg-slugs
================

This is [WP-CLI](http://wp-cli.org/) package which populates missing `image_slug` entries in [NextGEN Gallery's](https://wordpress.org/plugins/nextgen-gallery/) `wp_ngg_pictures` table. It arose from [this support discussion](https://wordpress.org/support/topic/imagebrowser-next-and-prev-buttons-do-nothing-after-update?replies=13).

### Installation
This package is **not** available via Composer. To install, follow the steps for [installing manually](https://github.com/wp-cli/wp-cli/wiki/Community-Packages#installing-a-package-without-composer).

### Usage
Currently two options are supported, `--dry-run` and `--network`.

### About
This project is independent of **NextGEN Gallery** and not supported nor approved by them in any way. I created it to solve a specific problem. Its structure borrows from WP-CLI's `search-replace` command.
