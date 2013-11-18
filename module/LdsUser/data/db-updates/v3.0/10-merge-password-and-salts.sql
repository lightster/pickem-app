-- ~ Prepend the salt to the password

UPDATE user SET password = CONCAT(securityHash, ':', password);