# WP-Deploy

Deploy WordPress Theme or Plugin via ssh.

[![Travis CI](https://travis-ci.org/fumikito/wp-deploy.svg?branch=master)](https://travis-ci.org/fumikito/wp-deploy)

## Installation

Download a phar file in [releases](https://github.com/fumikito/wp-deploy/releases).
Allow execution with `chmod +x ./wp-deploy.phar` and move it under your path environment.
Here're the command lines. 

```shell
wget https://github.com/fumikito/wp-deploy/releases/download/0.2.2/wp-deploy.phar
chomd +x ./wp-deploy.phar
mv wp-depoy.phar ~/bin/wp-deploy
```

Run `wp-deploy --help` and check results.

## Example

You can run this command like this.

```
wp-deploy --host=ec2-user@ssh.example.com --dir=/var/www/wordpress --theme --repo=git@github.com:fumikito/wp-deploy.git
```

## Options

Execute `wp-deploy --help`

## How to Build

Clone this repository and run composer.

```
git clone https://github.com/fumikito/wp-deploy.git
cd wp-deploy
composer install
```

To make phar file, [Box](https://box-project.github.io/box2/) is required. 

`box build`

If you get an error message about `phar.readonly`, enable it temporary in 1 liner.

`php -d phar.readonly=0 box build`

Now you get `wp-deploy.phar` file. Moving it to your command directory (e.g. `~/bin`) or anything you like. 