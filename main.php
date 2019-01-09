<?php
require __DIR__ . '/vendor/autoload.php';



$application = new \Symfony\Component\Console\Application( 'wp-deploy', '0.2.0' );
$command     = new \Fumikito\WpDeploy\DeployCommand();
$application->add( $command );
$application->setDefaultCommand( $command->getName(), true );
$application->run();
