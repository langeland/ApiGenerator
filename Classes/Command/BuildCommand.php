<?php

namespace ApiGenerator\Command;

class BuildCommand extends \ApiGenerator\Command\AbstractCommand {

	private $buildSources = array();

	private $buildSubsets = array();

	private $buildTargets = array();

	public function __construct() {
		parent::__construct();
	}

	protected function configure() {
		$this->loadConfugiration();
		$this
			->setName('build')
			->setDescription('Build source API')
			->addArgument('sources', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Sets the build sources. [ ' . implode(' | ', array_keys($this->configuration['source'])) . ' | all  ]', 'all')
			->addArgument('targets', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Sets the build targets. [ api | docset | all ].', 'all')
			->addArgument('subsets', \Symfony\Component\Console\Input\InputArgument::OPTIONAL, 'Sets the build subsets. [ stable | latest | dev | master | all ].', 'master');
	}

	protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output) {
		$this->input = $input;
		$this->output = $output;

		$output->writeln('Validating input arguments.');
		$this->validateCommandArgument_sources($input->getArgument('sources'));
		$this->validateCommandArgument_targets($input->getArgument('targets'));
		$this->validateCommandArgument_subsets($input->getArgument('subsets'));

		try {
			$output->writeln('Building source API');
			$this->task_createDirectories();
			foreach ($this->configuration['source'] AS $sourceName => $source) {
				$this->task_prepareSource($source['git'], $sourceName);

				$versions = $this->helper_loadVersions($sourceName);
				$table = new \Symfony\Component\Console\Helper\Table($this->output);
				$table->setHeaders(array_keys($versions[0]));
				foreach ($versions AS $version) {
					$table->addRow(array_values($version));
				}
				$table->render();

				foreach ($versions AS $version) {
					$this->task_compileVersion($sourceName, $version);
				}

			}

		} catch (Exception $e) {
			$this->output->writeln('Error: ' . $e->getMessage());
		}

	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	protected function validateCommandArgument_sources($sources) {
		$this->buildSources = explode(',', str_replace(' ', '', $sources));
	}

	protected function validateCommandArgument_targets($targets) {
		$this->buildTargets = explode(',', str_replace(' ', '', $targets));
	}

	protected function validateCommandArgument_subsets($subsets) {
		$this->buildSubsets = explode(',', str_replace(' ', '', $subsets));
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_createDirectories() {
		$this->output->writeln('/*******************************************************************************************************************');
		$this->output->writeln(' * Creating directory structure at: ' . $this->configuration['locations']['base']);
		$this->output->writeln(' ******************************************************************************************************************/');

		$directories = array(
			'storageDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['storage'],
			'buildDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['build'],
			'sourceDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['source'],
			'temporaryDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['temporary'],
			'logDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['log'],
			'archiveDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['build'] . 'Archive/',
			'apiDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['build'] . 'API/',
			'docsetDir' => $this->configuration['locations']['base'] . $this->configuration['locations']['build'] . 'Docset/'
		);

		foreach ($directories AS $name => $path) {
			$this->output->writeln('    Creating directory: ' . $path);
			if (!is_dir($path) && !mkdir($path, 0777)) {
				$this->output->writeln('Failed to create folders... ' . $path);
				$this->quit(1);
			}
		}

	}

	private function task_prepareSource($repository, $alias = NULL) {

		$basename = substr(basename($repository), 0, -4);
		$repositoryName = (is_null($alias)) ? $basename : $alias;
		$this->output->writeln('/*******************************************************************************************************************');
		$this->output->writeln(' * Prepearin source ' . $repositoryName);
		$this->output->writeln(' ******************************************************************************************************************/');

		if (!is_dir($this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName)) {
			$this->output->writeln('Local repository does not exists... Cloning..');

			$process = new \Symfony\Component\Process\Process(
				vsprintf(
					'git clone %s %s',
					array(
						$repository,
						$repositoryName
					)
				),
				$this->configuration['locations']['base'] . $this->configuration['locations']['source']
			);
			$process->setTimeout(NULL)->run();

			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getErrorOutput());
			}

		} else {

			$commands = array(
				'checkout master' => 'git checkout master',
				'git reset --hard origin/master' => 'git reset --hard origin/master',
				'git pull' => 'git pull'
			);

			$this->output->writeln('Local repository exists... Resetting..');
			foreach ($commands as $description => $command) {
				$this->output->writeln('    ' . $description);
				$process = new \Symfony\Component\Process\Process(
					$command,
					$this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName
				);
				$process->setTimeout(NULL)->run();
				if (!$process->isSuccessful()) {
					throw new \RuntimeException($process->getErrorOutput());
				}
			}
		}

	}

	private function task_compileVersion($repositoryName, array $version) {
		$this->output->writeln('/*******************************************************************************************************************');
		$this->output->writeln(' * Compiling HTML version of ' . $version['name'] . ' (' . $version['version'] . ')');
		$this->output->writeln(' ******************************************************************************************************************/');

		$buildOptions = array();
		if (array_key_exists('buildOptions', $this->configuration['source'][$repositoryName]) && is_string($this->configuration['source'][$repositoryName]['buildOptions'])) {
			$buildOptions = explode(',', str_replace(' ', '', $this->configuration['source'][$repositoryName]['buildOptions']));
		}

		$destinationApi = $this->configuration['locations']['base'] . $this->configuration['locations']['build'] . 'Api/' . $repositoryName . '/' . $version['subset'] . '/' . $version['name'] . '/';
		if (is_file($destinationApi . 'commit-' . $version['commit'])) {
			$this->output->writeln('API build of ' . $version['name'] . ' (' . $version['version'] . ') exists, continueing');
		} else {

			$this->output->writeln('    git checkout --quiet ' . $version['commit']);
			$process = new \Symfony\Component\Process\Process(
				'git checkout --quiet ' . $version['commit'],
				$this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName
			);
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getErrorOutput());
			}

			if (in_array('composer', $buildOptions) && is_file($this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName . '/composer.json')) {
				$this->output->writeln('    composer --no-interaction update');
				$process = new \Symfony\Component\Process\Process(
					'composer --no-interaction update',
					$this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName
				);
				$process->setTimeout(NULL);

				$process->run(function ($type, $buffer) {
					if (\Symfony\Component\Process\Process::ERR === $type) {
						$this->output->write($buffer);
					} else {
						$this->output->write($buffer);
					}
				});

				if (!$process->isSuccessful()) {
					$message = 'Process error on "' . $process->getCommandLine() . '". Exit code: ' . $process->getExitCode();
					throw new \RuntimeException($message);
				}

			}

			try {

				$commandLine = new \ApiGenerator\Service\CommandLineService('apigen generate');
				$commandLine->setPrefix('php');
				$commandLine
					->addArgument('--debug')
					->addArgument('--source', $this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName)
					->addArgument('--destination', $destinationApi)
					->addArgument('--title', '"TYPO3 CMS Version ' . $version['name'] . ' [' . $version['short'] . ']"')
					->addArgument('--template-config', $this->configuration['locations']['base'] . 'Libraries/apigen/theme-bootstrap/src/config.neon')
					->addArgument('--access-levels', 'public,protected,private')
					->addArgument('--tree');

				$exclude = array(
					'misc/*',
					't3lib/class.t3lib_superadmin.php',
					'typo3/sysext/core/Migrations/Code/LegacyClassesForIde.php',
					'typo3/sysext/cms/tslib/media/scripts/',
					'typo3/sysext/statictemplates/media/scripts/',
					'tests/*',
					'Tests/*',
					'Packages/Libraries/phpunit'
				);

				$commandLine->addArgument('--exclude', '"' . implode(',', $exclude). '"');

				$process = new \Symfony\Component\Process\Process(
					$commandLine->build(),
					$this->configuration['locations']['base'] . 'bin'
				);

				$process->setTimeout(NULL);

				$process->run(function ($type, $buffer) {
					if (\Symfony\Component\Process\Process::ERR === $type) {
						$this->output->write($buffer);
					} else {
						$this->output->write($buffer);
					}
				});

				if (!$process->isSuccessful()) {
					$message = 'Process error on "' . $process->getCommandLine() . '". Exit code: ' . $process->getExitCode();
					throw new \RuntimeException($message);
				}
				touch($destinationApi . 'commit-' . $version['commit']);
			} catch (Exception $e) {
				$this->output->writeln('FAIELD compiling %s (%s)', array($version['name'], $version['version'],));
				$this->helper_systemCall(vsprintf('rm -rf %s', array($destinationApi)), $this->configuration['locations']['base'] . 'bin');
				$this->output->writeln('');
			}
		}

		$destinationDocset = $this->configuration['locations']['base'] . $this->configuration['locations']['build'] . 'Docset/' . $repositoryName . '/' . $version['subset'] . '/' . $version['name'] . '.docset/';
		if (is_file($destinationDocset . 'Contents/Resources/Documents/commit-' . $version['commit'])) {
			$this->output->writeln('DocSet build of ' . $version['name'] . ' (' . $version['version'] . ') exists, continueing');
		} else {
			$this->output->writeln('/*******************************************************************************************************************');
			$this->output->writeln(' * Compiling DOCSET version of ' . $version['name'] . ' (' . $version['version'] . ')');
			$this->output->writeln(' ******************************************************************************************************************/');

			$process = new \Symfony\Component\Process\Process(
				'git checkout --quiet ' . $version['commit'],
				$this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName
			);
			$process->run();
			if (!$process->isSuccessful()) {
				throw new \RuntimeException($process->getErrorOutput());
			}
			try {
				$this->output->writeln('# 1. Create the Docset Folder');

				$directories = array(
					'Documents' => $destinationDocset. 'Contents/Resources/Documents'
				);

				foreach ($directories AS $name => $path) {
					$this->output->writeln('    Creating directory: ' . $path);
					if (!is_dir($path) && !mkdir($path, 0777, true)) {
						$this->output->writeln('Failed to create folders... ' . $path);
						$this->quit(1);
					}
				}

				$this->output->writeln('# 2. Build the HTML Documentation');
				$this->output->writeln('# 3. Create the Info.plist File');
				$this->output->writeln('# 4. Create the SQLite Index');
				$this->output->writeln('# 5. Adding an Icon');
				$this->output->writeln('# 6. Writing identification file');
				$this->output->writeln('# 7. Compressing docset');
				$this->output->writeln('# 8. Create the Feed.xml File');
				touch($destinationDocset . 'Contents/Resources/Documents/commit-' . $version['commit']);

			} catch (Exception $e) {
				$this->output->writeln('FAIELD compiling ' . $version['name'] . ' (' . $version['version'] . ')');

				$process = new \Symfony\Component\Process\Process(
					'rm -rf ' . $destinationDocset,
					$this->configuration['locations']['base'] . 'bin'
				);
				$process->run();
				if (!$process->isSuccessful()) {
					throw new \RuntimeException($process->getErrorOutput());
				}

				$this->output->writeln('');
			}
		}

		return;
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function helper_loadVersions($repositoryName) {
		$this->output->writeln('/*******************************************************************************************************************');
		$this->output->writeln(' * Extracting versions for ' . $repositoryName);
		$this->output->writeln(' ******************************************************************************************************************/');

		$process = new \Symfony\Component\Process\Process(
			'git show-ref',
			$this->configuration['locations']['base'] . $this->configuration['locations']['source'] . $repositoryName
		);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new \RuntimeException($process->getErrorOutput());
		}

		$gitShowRef = explode("\n", $process->getOutput());

		$this->output->writeln('Extracting tags for subset(s): ' . implode(' ,', $this->buildSubsets) . ' on ' . $repositoryName);
		$versions = array();

		if (array_intersect($this->buildSubsets, array('all', 'stable'))) {
			$pattern = '/(\S*)\srefs\/tags\/(?:TYPO3_)?([0-9]*)[-.]([0-9]*)[-.]([0-9]*)$/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
						'branch' => $match[2] . '.' . $match[3],
						'name' => $match[2] . '.' . $match[3] . '.' . $match[4],
						'subset' => 'stable'
					);
				} else {
					continue;
				}
			}
		}

		if (array_intersect($this->buildSubsets, array('all', 'latest'))) {
			$pattern = '/(\S*)\srefs\/tags\/(?:TYPO3_)?([0-9]*)[-.]([0-9]*)[-.]([0-9]*)$/';
			$temp = array();
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					if ($temp[$match[2]][$match[3]]['latest'] < $match[4]) {
						$temp[$match[2]][$match[3]]['latest'] = $match[4];
						$temp[$match[2]][$match[3]][$match[4]] = array(
							'commit' => $match[1],
							'short' => substr($match[1], 0, 7),
							'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
							'branch' => $match[2] . '.' . $match[3],
							'name' => $match[2] . '.' . $match[3] . 'latest',
							'subset' => 'latest'
						);
					}
				} else {
					continue;
				}
			}

			$versions = array();
			foreach ($temp AS $major) {
				foreach ($major AS $minor) {
					$versions[] = $minor[$minor['latest']];
				}
			}
		}

		if (array_intersect($this->buildSubsets, array('all', 'dev'))) {
			$pattern = '/(\S*)\srefs\/remotes\/origin\/TYPO3_([0-9]*)-([0-9]*)/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => $match[2] . '.' . $match[3] . '.0-dev',
						'branch' => $match[2] . '.' . $match[3],
						'name' => $match[2] . '.' . $match[3] . 'dev',
						'subset' => 'dev'
					);
				} else {
					continue;
				}
			}
		}

		if (array_intersect($this->buildSubsets, array('all', 'master'))) {
			$pattern = '/(\S*)\srefs\/remotes\/origin\/master/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => 'master',
						'name' => 'master',
						'subset' => 'master'
					);
				} else {
					continue;
				}
			}
		}

		$this->output->writeln('Found ' . count($versions) . ' versions for ' . $repositoryName);
		return $versions;
	}

}