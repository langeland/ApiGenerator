<?php


require __DIR__ . '/Bootstrap.php';

class ApiGenerator extends \ApiGenerator\Cli\Cli {

	private $baseTask = NULL;

	private $buildTargets = array(
		'master'
	);

	function __construct($appname = null, $author = null, $copyright = null) {
		//$this->settings = parse_ini_file('../Configuration/api-generator.ini', true);

		$this->settings = \Symfony\Component\Yaml\Yaml::parse(file_get_contents('../Configuration/api-generator.yaml'));

		//die(print_r($this->settings));
		$this->settings['locations']['base'] = realpath(__DIR__ . '/../');
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
			$this->task_prepareSource('git://git.typo3.org/Packages/TYPO3.CMS.git', 'TYPO3.CMS');
			#$this->task_prepareSource('git://git.typo3.org/Flow/Distributions/Base.git', 'TYPO3.FLOW');
			#$this->task_prepareSource('git://git.typo3.org/Neos/Distributions/Base.git', 'TYPO3.NEOS');

			$this->task_compile('TYPO3.CMS');
			#$this->task_compile('TYPO3.FLOW');
			#$this->task_compile('TYPO3.NEOS');

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
	public function option_targets($opt = null) {
		if ($opt == 'help') {
			return 'Sets the build targets. [ stable | latest | dev | master ]';
		}
		$this->buildTargets = explode(',', str_replace(' ', '', $opt));
		$this->outputLine('Setting build target(s) to: %s', array(implode(' ', $this->buildTargets)));
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
			$this->outputLine('Local repository does not exists... Clonong..', array());

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

		if (is_file($this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName . '/composer.json')) {
			$this->helper_systemCall(
				vsprintf(
					'composer install',
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
		if (in_array($version['name'], $this->settings['skip']['name'])) {
			$this->outputLine('Skipping %s (%s)',
				array(
					$version['name'],
					$version['version'],
				));
			return;
		}

		$destinationApi = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Api/' . $repositoryName . '/' . $version['target'] . '/' . $version['name'] . '/';

		if (!is_file($destinationApi . 'commit-' . $version['commit'])) {
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
				$this->outputLine('FAIELD compiling %s (%s)',
					array(
						$version['name'],
						$version['version'],
					));

				$this->helper_systemCall(
					vsprintf(
						'rm -rf %s',
						array(
							$destinationApi
						)
					),
					$this->settings['locations']['base'] . 'bin'
				);
				$this->outputLine('');
			}
		} else {
			$this->outputLine('API Build of %s (%s) exists, continueing',
				array(
					$version['name'],
					$version['version'],
				));
		}

		$destinationDocset = $this->settings['locations']['base'] . $this->settings['locations']['build'] . 'Docset/' . $repositoryName . '/' . $version['target'] . '/' . $version['name'] . '.docset/';
		if (!is_file($destinationDocset . 'Contents/Resources/Documents/commit-' . $version['commit'])) {
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
			/*
						try {
							# 1. Create the Docset Folder
							$this->outputLine('# 1. Create the Docset Folder');
							$this->helper_systemCall(
								vsprintf(
									'mkdir -p %s',
									array(
										$destinationDocset . 'Contents/Resources/Documents/'
									)
								)
							);

							$this->outputLine('# 2. Build the HTML Documentation');
							$this->helper_systemCall(
								vsprintf(
									'php apigen generate --debug -s %s -d %s --config %s --title "%s"',
									array(
										$this->settings['locations']['base'] . $this->settings['locations']['source'] . $repositoryName,
										$destinationDocset . 'Contents/Resources/Documents/',
										$this->settings['locations']['base'] . 'Configuration/apigen_Docset.neon',
										'TYPO3 CMS Version ' . $version['name'] . ' [' . $version['short'] . ']'
									)
								),
								$this->settings['locations']['base'] . 'bin',
								FALSE,
								'passthru'
							);

							$this->outputLine('# 3. Create the Info.plist File');
							$this->outputLine('# 4. Create the SQLite Index');
							$this->outputLine('# 5. Adding an Icon');
							$this->outputLine('# 6. Writing identification file');
							touch($destinationDocset . 'Contents/Resources/Documents/commit-' . $version['commit']);

							$this->outputLine('# 7. Compressing docset');
							$this->outputLine('# 8. Create the Feed.xml File');

						} catch (Exception $e) {
							$this->outputLine('FAIELD compiling %s (%s)',
								array(
									$version['name'],
									$version['version'],
								));

							$this->helper_systemCall(
								vsprintf(
									'rm -rf %s',
									array(
										$destinationDocset
									)
								),
								$this->settings['locations']['base'] . 'bin'
							);
							$this->outputLine('');
						}
						*/
		} else {
			$this->outputLine('Build of %s (%s) exists, continueing',
				array(
					$version['name'],
					$version['version'],
				));
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

		$this->outputLine('Extracting tags for target(s): %s on %s', array(implode(' ,', $this->buildTargets), $repositoryName));
		$versions = array();

		if (array_intersect($this->buildTargets, array('all', 'stable'))) {
			$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
						'branch' => $match[2] . '.' . $match[3],
						'name' => $match[2] . '.' . $match[3] . '.' . $match[4],
						'target' => 'stable'
					);
				} else {
					continue;
				}
			}
		}

		if (array_intersect($this->buildTargets, array('all', 'latest'))) {
			$pattern = '/(\S*)\srefs\/tags\/TYPO3_([0-9]*)-([0-9]*)-([0-9]*)$/';
			$temp = array();
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					if ($temp === array() || $temp[$match[2]][$match[3]]['latest'] < $match[4]) {
						$temp[$match[2]][$match[3]]['latest'] = $match[4];
						$temp[$match[2]][$match[3]][$match[4]] = array(
							'commit' => $match[1],
							'short' => substr($match[1], 0, 7),
							'version' => $match[2] . '.' . $match[3] . '.' . $match[4],
							'branch' => $match[2] . '.' . $match[3],
							'name' => $match[2] . '.' . $match[3] . 'latest',
							'target' => 'latest'
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

		if (array_intersect($this->buildTargets, array('all', 'dev'))) {
			$pattern = '/(\S*)\srefs\/remotes\/origin\/TYPO3_([0-9]*)-([0-9]*)/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => $match[2] . '.' . $match[3] . '.0-dev',
						'branch' => $match[2] . '.' . $match[3],
						'name' => $match[2] . '.' . $match[3] . 'dev',
						'target' => 'dev'
					);
				} else {
					continue;
				}
			}
		}

		if (array_intersect($this->buildTargets, array('all', 'master'))) {
			$pattern = '/(\S*)\srefs\/remotes\/origin\/master/';
			foreach ($gitShowRef AS $tag) {
				if (preg_match($pattern, $tag, $match)) {
					$versions[] = array(
						'commit' => $match[1],
						'short' => substr($match[1], 0, 7),
						'version' => 'master',
						'name' => 'master',
						'target' => 'master'
					);
				} else {
					continue;
				}
			}
		}

		$this->outputLine(' * Found %s versions for %s', array(count($versions), $repositoryName));
		print_r($versions);

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

