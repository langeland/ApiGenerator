<?php


require __DIR__ . '/Bootstrap.php';

class ApiGenerator extends \ApiGenerator\Cli\Cli {

	private $baseTask = NULL;

	private $buildSources = array(
		'all'
	);

	private $buildSubsets = array(
		'master'
	);

	private $buildTargets = array(
		'api', 'docset'
	);

	function __construct($appname = null, $author = null, $copyright = null) {
		$this->settings = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('../Configuration/api-generator.yaml'));
		$this->settings['locations']['base'] = realpath(__DIR__ . '/../') . '/';
		parent::__construct('Kiskstart new TYPO3 FLOW / NEOS project', 'Jon Klixbüll Langeland', '(c) 2014 MOC A/S.');
	}

	/**
	 * The main() function gets called if at least one argument is present.
	 * If no arguments are present, the automatically generated help is displayed.
	 *
	 * The main functions job to do the main work of the script.
	 *
	 *
	 * ./snapdev.php create --name=jaboo --source=typo3/flow-base-distribution
	 * ./snapdev.php create --name=razzel --source=git://git@git.moc.net/Distributions/Aris.git
	 *
	 *
	 */
	public function main() {
		if ($this->baseTask != NULL) {
			call_user_func(array($this, 'execute_' . $this->baseTask));
		} else {
			die('Missing argument');
		}
	}

	private function execute_build() {
		$this->outputLine('Building source API', array());
		try {
			$this->task_createDirectories();
			foreach ($this->settings['source'] AS $sourceName => $source) {
				$this->task_prepareSource($source['git'], $sourceName);
				$this->task_compile($sourceName);
			}

		} catch (Exception $e) {
			$this->outputLine('Error: %s', array($e->getMessage()));
		}
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 * Argument is like flag, but just a string.
	 * ./example.php create --name=jaboo --source="git@github.com:mocdk/vagrant-neosdev.git"
	 */
	public function argument_build($opt = null) {
		if ($opt == 'help') {
			return 'Build source API';
		}

		$this->baseTask = 'build';
	}

	/**
	 * Argument is like flag, but just a string.
	 * ./example.php config
	 */
	public function argument_config($opt = null) {
		if ($opt == 'help') {
			return 'Displays the current configuration';
		}

		print_r($this->settings);
		exit();
	}

	/**
	 * Argument is like flag, but just a string.
	 * ./example.php config
	 */
	public function option_sources($opt = null) {
		if ($opt == 'help') {
			return 'Sets the build sources. [ ' . implode(' | ', array_keys($this->settings['source'])) . ' | all ]';
		}
		$this->buildSubsets = explode(',', str_replace(' ', '', $opt));
		$this->outputLine('Setting build source(s) to: %s', array(implode(' ', $this->buildSubsets)));
	}

	/**
	 * Argument is like flag, but just a string.
	 * ./example.php config
	 */
	public function option_targets($opt = null) {
		if ($opt == 'help') {
			return 'Sets the build targets. [ api | docset | all ]';
		}
		$this->buildSubsets = explode(',', str_replace(' ', '', $opt));
		$this->outputLine('Setting build target(s) to: %s', array(implode(' ', $this->buildSubsets)));
	}

	/**
	 * Argument is like flag, but just a string.
	 * ./example.php config
	 */
	public function option_subsets($opt = null) {
		if ($opt == 'help') {
			return 'Sets the build subsets. [ stable | latest | dev | master | all ]';
		}
		$this->buildSubsets = explode(',', str_replace(' ', '', $opt));
		$this->outputLine('Setting build subset(s) to: %s', array(implode(' ', $this->buildSubsets)));
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	private function task_createDirectories() {
		$this->outputLine('/*******************************************************************************************************************');
		$this->outputLine(' * Creating directory structure at: %s', array($this->settings['locations']['base']));
		$this->outputLine(' ******************************************************************************************************************/');

		$storageDir = $this->settings['locations']['base'] . $this->settings['locations']['storage'];
		$this->outputLine('  Creating directory: %s', array($storageDir));
		if (!is_dir($storageDir) && !mkdir($storageDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$buildDir = $this->settings['locations']['base'] . $this->settings['locations']['build'];
		$this->outputLine('  Creating directory: %s', array($buildDir));
		if (!is_dir($buildDir) && !mkdir($buildDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$sourceDir = $this->settings['locations']['base'] . $this->settings['locations']['source'];
		$this->outputLine('  Creating directory: %s', array($sourceDir));
		if (!is_dir($sourceDir) && !mkdir($sourceDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$temporaryDir = $this->settings['locations']['base'] . $this->settings['locations']['temporary'];
		$this->outputLine('  Creating directory: %s', array($temporaryDir));
		if (!is_dir($temporaryDir) && !mkdir($temporaryDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$logDir = $this->settings['locations']['base'] . $this->settings['locations']['log'];
		$this->outputLine('  Creating directory: %s', array($logDir));
		if (!is_dir($logDir) && !mkdir($logDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$archiveDir = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Archive/';
		$this->outputLine('  Creating directory: %s', array($archiveDir));
		if (!is_dir($archiveDir) && !mkdir($archiveDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$apiDir = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'API/';
		$this->outputLine('  Creating directory: %s', array($apiDir));
		if (!is_dir($apiDir) && !mkdir($apiDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

		$docsetDir = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Docset/';
		$this->outputLine('  Creating directory: %s', array($docsetDir));
		if (!is_dir($docsetDir) && !mkdir($docsetDir, 0777)) {
			$this->outputLine('Failed to create folders...');
			$this->quit(1);
		}

	}

	private function task_prepareSource($repository, $alias = NULL) {

		$basename = substr(basename($repository), 0, -4);
		$repositoryName = (is_null($alias)) ? $basename : $alias;
		$this->outputLine('/*******************************************************************************************************************');
		$this->outputLine(' * Prepearin source %s', array($repositoryName));
		$this->outputLine(' ******************************************************************************************************************/');

		if (!is_dir($this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName)) {
			$this->outputLine('Local repository does not exists... Cloning..', array());

			$this->helper_systemCall(
				vsprintf(
					'git clone %s %s',
					array(
						$repository,
						$repositoryName
					)
				),
				$this->settings['locations']['base'] . $this->settings['locations']['source']
			);

		} else {

			$this->outputLine('Local repository exists... Resetting..', array());
			$this->helper_systemCall(
				vsprintf(
					'git checkout master',
					array()
				),
				$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName
			);
			$this->helper_systemCall(
				vsprintf(
					'git pull',
					array()
				),
				$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName
			);
		}

	}

	private function task_compile($repositoryName) {
		$versions = $this->helper_loadVersions($repositoryName);

		foreach ($versions AS $version) {

			$this->task_compileVersion($repositoryName, $version);

		}

	}

	private function task_compileVersion($repositoryName, array $version) {
		$this->outputLine('/*******************************************************************************************************************');
		$this->outputLine(' * Compiling HTML version of %s (%s)', array($version['name'], $version['version']));
		$this->outputLine(' ******************************************************************************************************************/');

		$buildOptions = array();
		if (array_key_exists('buildOptions', $this->settings['source'][$repositoryName]) && is_string($this->settings['source'][$repositoryName]['buildOptions'])) {
			$buildOptions = explode(',', str_replace(' ', '', $this->settings['source'][$repositoryName]['buildOptions']));
		}

		$destinationApi = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Api/' . $repositoryName . '/' . $version['subset'] . '/' . $version['name'] . '/';
		if (is_file($destinationApi . 'commit-' . $version['commit'])) {
			$this->outputLine('API Build of %s (%s) exists, continueing',
				array(
					$version['name'],
					$version['version'],
				));
		} else {
			$this->helper_systemCall(
				vsprintf(
					'git checkout --quiet %s',
					array(
						$version['commit']
					)
				),
				$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName
			);

			if (in_array('composer', $buildOptions)) {
				$this->helper_systemCall(
					vsprintf(
						'composer --no-interaction install',
						array()
					),
					$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName,
					FALSE,
					'passthru'
				);
			}

			try {
				$this->helper_systemCall(
					vsprintf(
						'php apigen generate --debug -s %s -d %s --config %s --title "%s"',
						array(
							$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName,
							$destinationApi,
							$this->settings['locations']['base'] . 'Configuration/apigen_Api.neon',
							'TYPO3 CMS Version ' . $version['name'] . ' [' . $version['short'] . ']'
						)
					),
					$this->settings['locations']['base'] . 'bin',
					FALSE,
					'passthru'
				);
				touch($destinationApi . 'commit-' . $version['commit']);
			} catch (Exception $e) {
				$this->outputLine('FAIELD compiling %s (%s)', array($version['name'], $version['version'],));
				$this->helper_systemCall(vsprintf('rm -rf %s', array($destinationApi)), $this->settings['locations']['base'] . 'bin');
				$this->outputLine('');
			}
		}

		$destinationDocset = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Docset/' . $repositoryName . '/' . $version['subset'] . '/' . $version['name'] . '.docset/';
		if (!is_file($destinationDocset . 'Contents/Resources/Documents/commit-' . $version['commit'])) {
			$this->outputLine('Build of %s (%s) exists, continueing',
				array(
					$version['name'],
					$version['version'],
				));
		} else {
			$this->outputLine('/*******************************************************************************************************************');
			$this->outputLine(' * Compiling DOCSET version of %s (%s)', array($version['name'], $version['version']));
			$this->outputLine(' ******************************************************************************************************************/');

			$this->helper_systemCall(
				vsprintf(
					'git checkout --quiet %s',
					array(
						$version['commit']
					)
				),
				$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName
			);
			try {
				$this->outputLine('# 1. Create the Docset Folder');
				$this->outputLine('# 2. Build the HTML Documentation');
				$this->outputLine('# 3. Create the Info.plist File');
				$this->outputLine('# 4. Create the SQLite Index');
				$this->outputLine('# 5. Adding an Icon');
				$this->outputLine('# 6. Writing identification file');
				$this->outputLine('# 7. Compressing docset');
				$this->outputLine('# 8. Create the Feed.xml File');
			} catch (Exception $e) {
				$this->outputLine('FAIELD compiling %s (%s)', array($version['name'], $version['version']));
				$this->helper_systemCall(vsprintf('rm -rf %s', array($destinationDocset)), $this->settings['locations']['base'] . 'bin');
				$this->outputLine('');
			}
		}

		$this->outputLine();
		$this->outputLine();

		return;
	}

	/*******************************************************************************************************************
	 *
	 ******************************************************************************************************************/

	/**
	 * @param string $repositoryName
	 * @return array
	 * @throws Exception
	 */
	private function helper_loadVersions($repositoryName) {
		$this->outputLine('/*******************************************************************************************************************');
		$this->outputLine(' * Extracting versions for %s', array($repositoryName));
		$this->outputLine(' ******************************************************************************************************************/');

		$gitShowRef = $this->helper_systemCall(
			vsprintf(
				'git show-ref',
				array()
			),
			$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName
		);

		$this->outputLine('Extracting tags for subset(s): %s on %s', array(implode(' ,', $this->buildSubsets), $repositoryName));
		$versions = array();

		if (array_intersect($this->buildSubsets, array('all', 'stable'))) {
			$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
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
			$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
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

		$this->outputLine('Found %s versions for %s', array(count($versions), $repositoryName));
		return $versions;
	}

	private function helper_systemCall($command, $changeWorkingDirectory = NULL, $dryRun = FALSE, $use = 'exec') {
		$output = '';
		if ($changeWorkingDirectory != NULL) {
			$currentWorkingDirectory = getcwd();
			chdir($changeWorkingDirectory);
		}
		$this->outputLine('Running: > %s', array($command));
		if (!$dryRun) {

			switch ($use) {
				case 'exec':
					exec($command, $output, $return_var);
					if ($return_var != 0) {
						throw new \Exception('Command faild..');
					}
					break;
				case 'passthru':
					passthru($command, $return_var);
					if ($return_var != 0) {
						throw new \Exception('Command faild..');
					}
					break;
				case 'system':
					die('Not implemented');
					break;
				default:
					die('Unknowen function');
			}

		}
		if ($changeWorkingDirectory != NULL) {
			chdir($currentWorkingDirectory);
		}

		return $output;
	}

}

/**
 *IMPORTANT, instantiate your class! i.e. new Classname();
 */
new ApiGenerator();

