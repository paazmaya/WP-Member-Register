-- phpMyAdmin SQL Dump
-- version 3.3.9
-- http://www.phpmyadmin.net
--
-- Host: localhost:3306
-- Generation Time: Mar 25, 2011 at 11:35 PM
-- Server version: 5.1.47
-- PHP Version: 5.3.3

CREATE TABLE IF NOT EXISTS sy_club (
  id mediumint(6) unsigned NOT NULL DEFAULT '0',
  name varchar(100) COLLATE utf8_swedish_ci NOT NULL,
  address tinytext COLLATE utf8_swedish_ci NOT NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;


CREATE TABLE IF NOT EXISTS sy_grade (
  member mediumint(5) unsigned NOT NULL DEFAULT '0',
  grade enum('8K','7K','6K','5h','5K','4h','4K','3h','3K','2h','2K','1h','1K','1s','1D','2s','2D','3D','4D','5D','6D','7D','8D') COLLATE utf8_swedish_ci NOT NULL DEFAULT '8K',
  location varchar(100) COLLATE utf8_swedish_ci NOT NULL,
  nominator tinyint(4) NOT NULL DEFAULT '0',
  day int(10) unsigned NOT NULL DEFAULT '0',
  KEY member (member)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;


CREATE TABLE IF NOT EXISTS sy_member (
  member mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
  user_login varchar(50) COLLATE utf8_swedish_ci NOT NULL DEFAULT '' COMMENT 'wp_users reference',
  access tinyint(1) NOT NULL DEFAULT '0',
  firstname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
  lastname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
  birthdate int(10) NOT NULL,
  address varchar(160) COLLATE utf8_swedish_ci NOT NULL,
  zipcode varchar(6) COLLATE utf8_swedish_ci NOT NULL DEFAULT '20100',
  postitoimi varchar(80) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'Turku',
  phone varchar(20) COLLATE utf8_swedish_ci NOT NULL,
  email varchar(200) COLLATE utf8_swedish_ci NOT NULL,
  nationality varchar(2) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'FI',
  joindate int(10) unsigned NOT NULL DEFAULT '0',
  passinro mediumint(6) unsigned NOT NULL DEFAULT '0',
  notes tinytext COLLATE utf8_swedish_ci NOT NULL,
  lastlogin int(10) unsigned NOT NULL DEFAULT '0',
  active tinyint(1) NOT NULL DEFAULT '1',
  club mediumint(6) unsigned NOT NULL DEFAULT '189400',
  PRIMARY KEY (member)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;


CREATE TABLE IF NOT EXISTS sy_payment (
  id mediumint(5) unsigned NOT NULL AUTO_INCREMENT,
  member mediumint(5) unsigned NOT NULL DEFAULT '0',
  viitenro mediumint(6) unsigned NOT NULL DEFAULT '0',
  type varchar(24) COLLATE utf8_swedish_ci NOT NULL,
  summa float(8,2) NOT NULL DEFAULT '50.00',
  erapvm int(10) unsigned NOT NULL DEFAULT '0',
  maksupvm int(10) unsigned NOT NULL DEFAULT '0',
  viimeinenpvm int(10) unsigned NOT NULL DEFAULT '0',
  club mediumint(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (id),
  KEY member (member)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci;
