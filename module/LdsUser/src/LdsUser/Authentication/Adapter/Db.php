<?php

namespace LdsUser\Authentication\Adapter;

use DateTime;
use LdsUser\Authentication\SaltedMd5Password;
use Zend\Authentication\Result as AuthenticationResult;
use Zend\Crypt\Password\Bcrypt;
use ZfcUser\Authentication\Adapter\Db as ZfcUserDbAdapter;
use ZfcUser\Authentication\Adapter\AdapterChainEvent as AuthEvent;

class Db extends ZfcUserDbAdapter
{
	public function authenticate(AuthEvent $e)
	{
		// if an identity is already set, we don't need to attempt authentication
		if ($e->getIdentity()) {
			$this->setSatisfied(true);
			return;
		}

		// <straight-up copy from ZfcUser>
		$identity   = $e->getRequest()->getPost()->get('identity');
		$credential = $e->getRequest()->getPost()->get('credential');
		$credential = $this->preProcessCredential($credential);
		$userObject = NULL;

		// Cycle through the configured identity sources and test each
		$fields = $this->getOptions()->getAuthIdentityFields();
		while ( !is_object($userObject) && count($fields) > 0 ) {
			$mode = array_shift($fields);
			switch ($mode) {
				case 'username':
					$userObject = $this->getMapper()->findByUsername($identity);
					break;
				case 'email':
					$userObject = $this->getMapper()->findByEmail($identity);
					break;
			}
		}

		if (!$userObject) {
			$e->setCode(AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND)
			  ->setMessages(array('A record with the supplied identity could not be found.'));
			$this->setSatisfied(false);
			return false;
		}
		// </straight-up copy from ZfcUser>

		if ($this->getOptions()->getEnableUserState()) {
			// Don't allow user to login if state is not in allowed list
			if (!in_array($userObject->getState(), $this->getOptions()->getAllowedLoginStates())) {
				$e->setCode(AuthenticationResult::FAILURE_INACTIVE)
				  ->setMessages(array('A record with the supplied identity is not active.'));
				$this->setSatisfied(false);
				return false;
			}
		}
		
		$salted_md5	= new SaltedMd5Password();
		if (!$salted_md5->verify($credential, $userObject->getPassword())) {
			// Password does not match
			$e->setCode(AuthenticationResult::FAILURE_CREDENTIAL_INVALID)
			  ->setMessages(array('Supplied credential is invalid.'));
			$this->setSatisfied(false);
			return false;
		}

		// most of this is the same as ZfcUser, but one important thing to note
		// is that we're updating the user's password to use bcrypt instead of
		// leaving the legacy md5 hash
		$bcrypt = new Bcrypt();
		$bcrypt->setCost($this->getOptions()->getPasswordCost());
		// <more straight-up copy from ZfcUser>
		// Success!
		$e->setIdentity($userObject->getId());
		// Update user's password hash if the cost parameter has changed
		$this->updateUserPasswordHash($userObject, $credential, $bcrypt);
		$this->setSatisfied(true);
		$storage = $this->getStorage()->read();
		$storage['identity'] = $e->getIdentity();
		$this->getStorage()->write($storage);
		$e->setCode(AuthenticationResult::SUCCESS)
		  ->setMessages(array('Authentication successful.'));
		// </more straight-up copy from ZfcUser>
	}

	protected function updateUserPasswordHash($userObject, $password, $bcrypt)
	{
		$userObject->setPassword($bcrypt->create($password));
		$this->getMapper()->update($userObject);
		return $this;
	}
}
