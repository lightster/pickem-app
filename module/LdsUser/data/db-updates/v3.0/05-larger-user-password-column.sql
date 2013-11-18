-- ~ Make the password field larger so it can store bcrypt passwords
-- ~ and legacy salted-md5 WITH the salts

ALTER TABLE user CHANGE COLUMN password password VARCHAR(128) NOT NULL DEFAULT '';