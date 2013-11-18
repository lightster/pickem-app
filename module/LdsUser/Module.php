<?php
namespace LdsUser;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;

class Module
implements
	AutoloaderProviderInterface,
	ConfigProviderInterface,
	ViewHelperProviderInterface
{
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
				),
			),
		);
	}

	public function getConfig()
	{
		return include __DIR__ . '/config/module.config.php';
	}

	public function getServiceConfig()
	{
		return array(
			'factories' => array(
				'ldsuser_module_options' => function ($sm) {
					$config = $sm->get('Config');
					return new Options\ModuleOptions(isset($config['ldsuser']) ? $config['ldsuser'] : array());
				},
			),
		);
	}

	public function getViewHelperConfig()
	{
		return array(
			'factories' => array(
				'ldsUserRegistrationEnabled' => function ($sm) {
					$locator = $sm->getServiceLocator();
					$viewHelper = new View\Helper\LdsUserRegistrationEnabled;
					$viewHelper->setModuleOptions($locator->get('zfcuser_module_options'));
					return $viewHelper;
				},
			),
		);

	}
}
