<?php

namespace Fumikito\WpDeploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


/**
 * Deploy command.
 *
 * @package wp-deploy
 */
class DeployCommand extends Command {
	
	protected $unwanted_files = [
		'.git',
		'bower_components',
		'node_modules',
		'.gitignore',
		'tests',
		'.editorconfig',
		'.eslintrc',
		'.travis.yml',
		'phpcs.ruleset.xml',
		'phpcs.xml.dist',
		'phpunit.xml.dist',
		'bin'
	];
	
	protected function configure() {
		$this
			->setName( 'wp-deploy' )
			->setDescription( 'Build WordPress plugin or theme from git repo and upload it to remote server.' )
			->addArgument( 'host', InputArgument::REQUIRED, '' )
			->setDefinition( new InputDefinition( [
				new InputOption( 'theme', '', InputOption::VALUE_NONE, 'Means this repo is theme.' ),
				new InputOption( 'plugin', '', InputOption::VALUE_NONE, 'Means this repo is plugin.' ),
				new InputOption( 'repo', 'r', InputOption::VALUE_REQUIRED, 'Repository URL of theme or plugin.' ),
				new inputoption( 'host', '', inputoption::VALUE_REQUIRED, 'Host to upload.' ),
				new inputoption( 'dir', 'd', inputoption::VALUE_REQUIRED, 'WordPress root path in remote server.' ),
				new InputOption( 'user', 'u', InputOption::VALUE_OPTIONAL, 'User name of server.', '' ),
				new InputOption( 'key', 'i', InputOption::VALUE_OPTIONAL, 'Private key path for ssh connection.', '' ),
				new InputOption( 'tag', 't', InputOption::VALUE_OPTIONAL, 'Repository tag version.', '' ),
				new InputOption( 'npm', '', InputOption::VALUE_OPTIONAL, 'NPM script to be executed.', 'start' ),
				new InputOption( 'relative-path', '', InputOption::VALUE_OPTIONAL, 'Relative path from WordPress root.', '' ),
				new InputOption( 'as-zip', 'z', InputOption::VALUE_OPTIONAL, 'If set, not deploy to server, but save file as specified path.', '' ),
			] ) );
	}
	
	
	protected function execute( InputInterface $input, OutputInterface $output ) {
		// Get relative path.
		$relative_path = '';
		if ( $input->getOption( 'theme' ) ) {
			$relative_path = 'wp-content/themes';
		} elseif ( $input->getOption( 'plugin' ) ) {
			$relative_path = 'wp-content/plugins';
		} else {
			$relative_path = $input->getOption( 'relative-path' );
		}
		if ( ! $relative_path ) {
			throw new \Exception( 'Relative path from WordPress root is not set.' );
		}
		$relative_path = ltrim( $relative_path, '/' );
		$out = $input->getOption( 'as-zip' );
		if ( $out && ! is_dir( $out ) ) {
			throw new \Exception( sprintf( 'Target Directory %s doesn\'t exist.', $out ) );
		}
		// Get repo and host.
		$host       = $input->getOption( 'host' );
		$repo       = $input->getOption( 'repo' );
		$target_dir = $input->getOption( 'dir' );
		$base_name = str_replace( '.git', '', basename( $repo ) );
		// Set working directory.
		$working_dir = tempnam( sys_get_temp_dir(), 'wp-deploy-' );
		unlink( $working_dir );
		mkdir( $working_dir, 0755, true );
		$current_dir = getcwd();
		// Move to working dir and build.
		$output->writeln( sprintf( 'Moving from <info>%s</info> to <info>%s</info>', $current_dir, $working_dir ) );
		chdir( $working_dir );
		// Clone git.
		$output->writeln( sprintf( 'Cloning <info>%s</info> into <info>%s</info>', $repo, getcwd() ) );
		$process = new Process( [ 'git', 'clone', $repo, $base_name ] );
		$process->run();
		if ( ! $process->isSuccessful() ) {
			throw new ProcessFailedException( $process );
		}
		echo $process->getOutput();
		chdir( $working_dir . '/' . $base_name );
		// If tag is set, change tag.
		if ( $tag = $input->getOption( 'tag' ) ) {
			$process = new Process( [ 'git', 'checkout', "refs/tags/{$tag}" ] );
			$process->run();
			if ( ! $process->isSuccessful() ) {
				throw new ProcessFailedException( $process );
			}
			$output->writeln( $process->getOutput() );
		} else {
			$tag = 'master';
		}
		// Composer.
		if ( file_exists( 'composer.json' ) ) {
			$output->write( 'Composer found. Installing...  ' );
			$process = new Process( [ 'composer', 'install', '--no-dev' ], null, null, null, 300 );
			$process->run();
			if ( ! $process->isSuccessful() ) {
				throw new ProcessFailedException( $process );
			}
			$output->writeln( [
				'<fg=blue>Composer installed!</>',
				'----------',
			] );
		}
		// NPM.
		if ( file_exists( 'package.json' ) ) {
			$output->write( 'Node package found. Installing...  ' );
			$commands = [ [ 'npm', 'install' ] ];
			$npm_script = $input->getOption( 'npm' );
			if ( 'start' === $npm_script ) {
				$commands[] = [ 'npm', 'start' ];
			} else {
				$commands[] = [ 'npm', 'run', $npm_script ];
			}
			foreach ( $commands as $line ) {
				$process = new Process( $line, null, null, null, 300 );
				$process->run();
				if ( ! $process->isSuccessful() ) {
					throw new ProcessFailedException( $process );
				}
			}
			$output->writeln( [
				'<fg=blue>NPM is installed!</>',
				'----------',
			] );
		}
		// Remove unwanted files.
		foreach ( $this->unwanted_files as $file ) {
			if ( is_dir( $file ) ) {
				$process = new Process( [ 'rm', '-rf', $file ] );
				$process->run();
				if ( ! $process->isSuccessful() ) {
					throw new ProcessFailedException( $process );
				}
				$output->writeln( sprintf( '<comment>Directory %s removed.</comment>', $file ) );
			} else if ( file_exists( $file ) ) {
				unlink( $file );
				$output->writeln( sprintf( '<comment>File %s removed.</comment>', $file ) );
			}
		}
		// Make readme.
		if ( file_exists( 'readme.md' ) || file_exists( 'README.md' ) ) {
			foreach ( [
				[ 'curl', '-O', 'https://raw.githubusercontent.com/fumikito/wp-readme/master/wp-readme.php' ],
				[ 'php', 'wp-readme.php' ],
				[ 'rm', 'wp-readme.php' ],
			] as $line ) {
				$process = new Process( $line );
				$process->run();
				if ( ! $process->isSuccessful() ) {
					throw new ProcessFailedException( $process );
				}
			}
			$output->writeln( 'readme.txt generated.' );
		}
		// Build done.
		$output->writeln( [
			'',
			'----------',
			'<fg=blue>Build Process Finished.</>',
			'----------',
			'',
		] );
		if ( $out ) {
			// Make zip archive and move it.
			chdir( $working_dir );
			$file_name = "{$base_name}-{$tag}.zip";
			foreach ( [
				[ 'zip', '-r', $file_name, $base_name ],
				[ 'mv', $file_name, $out ],
				[ 'rm', '-rf', $base_name ]
			] as $line ) {
				$process = new Process( $line );
				$process->run();
				if ( ! $process->isSuccessful() ) {
					throw new ProcessFailedException( $process );
				}
			}
			$output->writeln( [
				'',
				'=============================',
				sprintf( '<info>File is saved as %s</info>', $out . '/' . $file_name ),
				'=============================',
			] );
		} else {
			if ( $user = $input->getOption( 'user' ) ) {
				$host = "{$user}@{$host}";
			}
			$line = [
				'rsync',
				'-rvct',
			];
			if ( $key = $input->getOption( 'key' ) ) {
				$line[] = '-e';
				$line[] = "'ssh -i {$key}'";
			}
			$line[] = '.';
			$line[] = "{$host}:/{$target_dir}/{$relative_path}/{$base_name}";
			$process = new Process( $line );
			$process->run();
			if ( ! $process->isSuccessful() ) {
				throw new ProcessFailedException( $process );
			}
			$output->writeln( [
				$process->getOutput(),
				'',
				'=============================',
				'<info>FINISHED</info>',
				'=============================',
			] );
		}
	}
}
