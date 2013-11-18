<?php

namespace LdsUser\Mapper;

use ZfcBase\Mapper\AbstractDbMapper;
use ZfcUser\Mapper\User as ZfcUserUserMapper;
use ZfcUser\Entity\UserInterface as UserEntityInterface;
use Zend\Stdlib\Hydrator\HydratorInterface;

class User extends ZfcUserUserMapper
{
	public function findById($id)
	{
		$select = $this->getSelect()
					   ->where(array('userId' => $id));

		$entity = $this->select($select)->current();
		$this->getEventManager()->trigger('find', $this, array('entity' => $entity));
		return $entity;
	}

	public function update($entity, $where = null, $tableName = null, HydratorInterface $hydrator = null)
	{
		if (!$where) {
			$where = 'userId = ' . $entity->getId();
		}

		return parent::update($entity, $where, $tableName, $hydrator);
	}
}
