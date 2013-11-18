<?php

namespace LdsUser\View\Helper;

use Zend\View\Helper\AbstractHelper;
use ZfcUser\Options\ModuleOptions;

class LdsUserRegistrationEnabled extends AbstractHelper
{
	/**
	 * Module options
	 * @var ModuleOptions
	 */
	protected $zfcuserModuleOpts;

	/**
	 * __invoke
	 *
	 * @access public
	 * @return bool
	 */
	public function __invoke()
	{
		return $this->zfcuserModuleOpts->getEnableRegistration();
	}

	/**
	 * Retrieve ModuleOptions object
	 * @return ModuleOptions
	 */
	public function getModuleOptions()
	{
		return $this->zfcuserModuleOpts;
	}

	/**
	 * Inject ZFC-User module options
	 * @param ModuleOptions $zfcuserModuleOpts
	 * @return LdsUserRegistrationEnabled
	 */
	public function setModuleOptions(ModuleOptions $zfcuserModuleOpts)
	{
		$this->zfcuserModuleOpts = $zfcuserModuleOpts;
		return $this;
	}
}
