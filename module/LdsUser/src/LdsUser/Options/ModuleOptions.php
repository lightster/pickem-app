<?php

namespace LdsUser\Options;

use Zend\Stdlib\AbstractOptions;

class ModuleOptions
extends AbstractOptions
{
	/**
	 * @var bool
	 */
	protected $authenticationAdapterServiceNames = array(
		// service name => priority

		// in the long-run, most cases will be satisified by the current
		// authentication adapter, so let's allow it to go first
		'ldsuser_authentication_adapter_current'	=> 1,
		// if the current authentication adapter didn't work, perhaps the user's
		// password is using the legacy password system?
		'ldsuser_authentication_adapter_legacy'		=> 2,
	);



	/**
	 * Setter for authentication adapter service names.
	 *
	 * @param array $authenticationAdapterServiceNames
	 * @return ModuleOptions
	 */
	public function setAuthenticationAdapterServiceNames($authenticationAdapterServiceNames)
	{
		$this->authenticationAdapterServiceNames = $authenticationAdapterServiceNames;
		return $this;
	}

	/**
	 * Get for authentication adapter service names.
	 *
	 * @return array
	 */
	public function getAuthenticationAdapterServiceNames()
	{
		return $this->authenticationAdapterServiceNames;
	}
}
