CREATE TABLE IF NOT EXISTS bra_activation_codes (
  id int unsigned NOT NULL AUTO_INCREMENT,
  code char(32) NOT NULL,
  bound_uid mediumint unsigned NOT NULL DEFAULT 0,
  per_account_once tinyint unsigned NOT NULL DEFAULT 1,
  limited tinyint unsigned NOT NULL DEFAULT 0,
  enabled tinyint unsigned NOT NULL DEFAULT 1,
  title varchar(255) NOT NULL,
  content text NOT NULL,
  enclosure text NOT NULL,
  created_at int unsigned NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY code (code)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS bra_activation_uses (
  id int unsigned NOT NULL AUTO_INCREMENT,
  code_id int unsigned NOT NULL,
  uid mediumint unsigned NOT NULL,
  request_key char(32) NOT NULL,
  mid int unsigned NOT NULL,
  used_at int unsigned NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY request_key (uid, request_key),
  KEY code_account (code_id, uid)
) ENGINE=MyISAM;
