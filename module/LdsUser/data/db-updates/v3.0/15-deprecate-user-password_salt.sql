-- ~ Deprecate user.securityHash

ALTER TABLE user CHANGE COLUMN securityHash _passwordSalt varchar(4) NOT NULL DEFAULT '';