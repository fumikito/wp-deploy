# wp-deploy
Deploy WordPress Theme or Plugin via ssh.

## Installation

W.I.P

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