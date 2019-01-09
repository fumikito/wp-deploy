# wp-deploy
Deploy WordPress Theme or Plugin via ssh.

## Installation



## Options

See

## How to Build

To make phar file, [Box](https://box-project.github.io/box2/) is required. 

`box build`

If you get an error message about `phar.readonly`, enable it temporary in 1 liner.

`php -d phar.readonly=0 box build`