<?php

namespace ApiGenerator\Service;

class CommandLineService {

	/**
	 * @var string|null
	 */
	private $command = NULL;

	/**
	 * @var string|null
	 */
	private $prefix = NULL;

	/**
	 * @var array
	 */
	private $arguments = array();

	/**
	 * @var array
	 */
	private $options = array();

	function __construct($command) {
		$this->command = $command;
	}

	/**
	 * @return null|string
	 */
	public function getCommand() {
		return $this->command;
	}

	/**
	 * @param null|string $command
	 * @return $this
	 */
	public function setCommand($command) {
		$this->command = $command;
		return $this;
	}

	/**
	 * @return null|string
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @param null|string $prefix
	 * @return $this
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @param array $arguments
	 * @return $this
	 */
	public function setArguments($arguments) {
		$this->arguments = $arguments;
		return $this;
	}

	/**
	 * @param $argumentName
	 * @param null $argumentValue
	 * @param bool $quateArgumentValue
	 * @return $this
	 */
	public function addArgument($argumentName, $argumentValue = NULL, $quateArgumentValue = false) {
		$this->arguments[] = array($argumentName, $argumentValue);
		return $this;
	}

	/**
	 * @return array
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * @param array $options
	 * @return $this
	 */
	public function setOptions($options) {
		$this->options = $options;
		return $this;
	}

	/**
	 * @param $optionsName
	 * @param null $optionsValue
	 * @param bool $quatOptionValue
	 * @return $this
	 */
	public function addOption($optionsName,$optionsValue = NULL, $quatOptionValue = false) {
		$this->options[] = array($optionsName, $optionsValue);
		return $this;
	}

	public function build() {
		$commandLineParts = array();

		if (!is_null($this->prefix)) {
			$commandLineParts[] = $this->prefix;
		}

		$commandLineParts[] = $this->command;

		foreach($this->arguments as $argument){
			$commandLineParts[] = implode(' ', $argument);
		}

		return implode(' ', $commandLineParts);

	}

}