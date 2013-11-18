<?php

namespace LdsUser\Authentication\Adapter;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZfcUser\Authentication\Adapter\AdapterChain;

class AdapterChainServiceFactory
implements
	FactoryInterface
{
	public function createService(ServiceLocatorInterface $serviceLocator)
	{
		$chain = new AdapterChain;

		$module_options			= $serviceLocator->get('ldsuser_module_options');
		$adapter_service_names	= $module_options->getAuthenticationAdapterServiceNames();
		asort($adapter_service_names);
		foreach($adapter_service_names as $service_name => $priority) {
			$adapter	= $serviceLocator->get($service_name);
			$chain->getEventManager()->attach('authenticate', array($adapter, 'authenticate'));
		}

		return $chain;
	}
}
