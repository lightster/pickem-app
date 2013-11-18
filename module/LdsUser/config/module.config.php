<?php
return array(
	'ldsuser_config' => array(
		/*
		'authentication_adapter_service_names' => array(
			// service name => priority
			'ldsuser_authentication_adapter_current'	=> 1,
			'ldsuser_authentication_adapter_legacy'		=> 2,
		),
		*/
	),
	'service_manager' => array(
		'aliases' => array(
			'ldsuser_authentication_adapter_current' => 'ZfcUser\Authentication\Adapter\Db',
		),
		'factories' => array(
			'ZfcUser\Authentication\Adapter\AdapterChain' => 'LdsUser\Authentication\Adapter\AdapterChainServiceFactory',
			'zfcuser_user_mapper' => function ($sm) {
				$options = $sm->get('zfcuser_module_options');
				$mapper = $sm->get('ldsuser_user_mapper');
				$mapper->setDbAdapter($sm->get('zfcuser_zend_db_adapter'));
				$entityClass = $options->getUserEntityClass();
				$mapper->setEntityPrototype(new $entityClass);
				$mapper->setHydrator($sm->get('ldsuser_user_hydrator'));
				return $mapper;
			},
		),
		'invokables' => array(
			'ldsuser_authentication_adapter_legacy' => 'LdsUser\Authentication\Adapter\Db',
			'ldsuser_user_mapper' => 'LdsUser\Mapper\User',
			'ldsuser_user_hydrator' => 'LdsUser\Mapper\UserHydrator',
		),
	),
);
