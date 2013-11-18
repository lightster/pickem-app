<?php

namespace LdsUser\Authentication;

use LdsUser\Exception\CannotGenerateLegacyPassword;
use Zend\Crypt\Password\PasswordInterface;

class SaltedMd5Password
implements
	PasswordInterface
{
	/**
	 * Create a password hash for a given plain text password
	 *
	 * @param  string $password The password to hash
	 * @return string The formatted password hash
	 */
	public function create($password)
	{
		throw new CannotGenerateLegacyPassword('Salted-md5 password generation is not supported.');
	}



	/**
	 * Verify a password hash against a given plain text password
	 *
	 * @param  string $password The password to hash
	 * @param  string $hash     The supplied hash to validate
	 * @return boolean Does the password validate against the hash
	 */
	public function verify($password, $hash)
	{
		// the password in the entity is stored as {salt}:{salted_password_hash}
		list($salt, $stored_hash) = explode(':', $hash);
		return (md5(md5($password) . $salt) == $stored_hash);
	}
}
