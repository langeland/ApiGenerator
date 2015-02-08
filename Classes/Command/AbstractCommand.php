<?php

namespace ApiGenerator\Command;

class AbstractCommand extends \Symfony\Component\Console\Command\Command {

	protected $configuration = array();

	/**
	 * @var \Symfony\Component\Console\Input\InputInterface
	 */
	protected $input;

	/**
	 * @var \Symfony\Component\Console\Output\OutputInterface
	 */
	protected $output;

	public function __construct() {
		parent::__construct();
		$this->loadConfugiration();
	}

	public function loadConfugiration() {
		if ($this->configuration === array()) {
			$this->configuration = \Symfony\Component\Yaml\Yaml::parse(file_get_contents(__DIR__ . '/../../Configuration/api-generator.yaml'));
			$this->configuration['locations']['base'] = realpath(__DIR__ . '/../../') . '/';
			if (is_file($_SERVER['HOME'] . '/.api-generator/config.yaml')) {
				$configurationHome = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($_SERVER['HOME'] . '/.rosemary/config.yaml'));
				$this->configuration = $this->array_merge_recursive_distinct($this->configuration, $configurationHome);
			}
		}
	}

	private function array_merge_recursive_distinct(array &$array1, &$array2 = null) {
		$merged = $array1;

		if (is_array($array2)) {
			foreach ($array2 as $key => $val) {
				if (is_array($array2[$key])) {
					$merged[$key] = is_array($merged[$key]) ? $this->array_merge_recursive_distinct($merged[$key], $array2[$key]) : $array2[$key];
				} else {
					$merged[$key] = $val;
				}
			}
		}

		return $merged;
	}

	public function validatecommandArguments() {

	}

	public function validatecommandOptions() {

	}

}