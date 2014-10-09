<?php
/**
 * Part of Member Register
 * License: MIT (http://opensource.org/licenses/MIT)
 *
 * Installation hook
 */


function mr_install() {
    global $wpdb;
    global $mr_db_version;
    $mr_prefix = 'mr_';

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );


    $table_name = $wpdb->prefix . $mr_prefix . 'group';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) NOT NULL AUTO_INCREMENT,
		  title varchar(200) COLLATE utf8_swedish_ci NOT NULL,
		  creator mediumint(6) NOT NULL COMMENT 'Member ID who creted',
		  modified int(10) NOT NULL COMMENT 'Unix timestamp of last modification',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'group_member';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) NOT NULL AUTO_INCREMENT,
		  group_id mediumint(6) NOT NULL COMMENT 'ID of the group',
		  member_id mediumint(6) NOT NULL COMMENT 'ID of the member',
		  PRIMARY KEY (id)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'file';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  bytesize int(12) unsigned NOT NULL,
		  basename varchar(255) COLLATE utf8_swedish_ci NOT NULL,
		  directory varchar(255) COLLATE utf8_swedish_ci NOT NULL DEFAULT '',
		  uploader mediumint(6) unsigned NOT NULL COMMENT 'Member ID',
		  uploaded int(10) NOT NULL COMMENT 'Unix timestamp',
		  mingrade varchar(2) COLLATE utf8_swedish_ci NOT NULL DEFAULT '' COMMENT 'mr_grade_values if any minimum',
		  clubonly mediumint(6) unsigned NOT NULL DEFAULT '0' COMMENT 'Club ID if not 0',
		  artonly varchar(10) COLLATE utf8_swedish_ci NOT NULL DEFAULT '' COMMENT 'Only shown for those whose main martial',
		  grouponly mediumint(6) NOT NULL DEFAULT '0' COMMENT 'Group ID if not 0',
		  visible tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (id),
		  KEY visible (visible),
		  KEY directory (directory),
		  KEY mingrade (mingrade),
		  KEY clubonly (clubonly),
		  KEY artonly (artonly)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'forum_post';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  topic mediumint(6) unsigned NOT NULL,
		  content text COLLATE utf8_swedish_ci NOT NULL,
		  member mediumint(6) unsigned NOT NULL,
		  created int(10) NOT NULL COMMENT 'Unix timestamp',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id),
		  KEY visible (visible),
		  KEY topic (topic)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'forum_topic';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  title varchar(250) COLLATE utf8_swedish_ci NOT NULL,
		  member mediumint(6) unsigned NOT NULL COMMENT 'User ID in mr_member',
		  access tinyint(2) NOT NULL DEFAULT '0' COMMENT 'Minimum access level needed to see',
		  created int(10) unsigned NOT NULL COMMENT 'Unix timestamp',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  UNIQUE KEY id (id),
		  KEY visible (visible)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'grade';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  member mediumint(6) unsigned NOT NULL DEFAULT '0',
		  grade enum('6K','5h','5K','4h','4K','3h','3K','2h','2K','1h','1K','1s','1D','2s','2D','3D','4D','5D','6D','7D','8D','9D') COLLATE utf8_swedish_ci NOT NULL DEFAULT '5K',
		  type enum('Yuishinkai','Kobujutsu') COLLATE utf8_swedish_ci NOT NULL DEFAULT 'Yuishinkai',
		  location varchar(255) COLLATE utf8_swedish_ci NOT NULL,
		  nominator varchar(250) COLLATE utf8_swedish_ci NOT NULL DEFAULT '',
		  day date NOT NULL DEFAULT '0000-00-00',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id),
		  KEY member (member)
		  KEY visible (visible)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'member';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  user_login varchar(50) COLLATE utf8_swedish_ci NOT NULL DEFAULT '' COMMENT 'wp_users reference',
		  access mediumint(4) NOT NULL DEFAULT '0',
		  firstname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
		  lastname varchar(40) COLLATE utf8_swedish_ci NOT NULL,
		  birthdate date NOT NULL DEFAULT '0000-00-00',
		  address varchar(160) COLLATE utf8_swedish_ci NOT NULL,
		  zipcode varchar(6) COLLATE utf8_swedish_ci NOT NULL DEFAULT '20100',
		  postal varchar(80) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'Turku',
		  phone varchar(20) COLLATE utf8_swedish_ci NOT NULL,
		  email varchar(200) COLLATE utf8_swedish_ci NOT NULL,
		  nationality varchar(2) COLLATE utf8_swedish_ci NOT NULL DEFAULT 'FI',
		  joindate date NOT NULL DEFAULT '0000-00-00',
		  passnro mediumint(6) unsigned NOT NULL DEFAULT '0',
		  notes tinytext COLLATE utf8_swedish_ci NOT NULL,
		  lastlogin int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Unix timestamp',
		  active tinyint(1) NOT NULL DEFAULT '0',
		  club mediumint(6) unsigned NOT NULL DEFAULT '0',
		  martial enum('karate','kobujutsu','taiji','judo','mma') COLLATE utf8_swedish_ci NOT NULL DEFAULT 'karate',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id),
		  KEY user_login (user_login)
		  KEY visible (martial)
		  KEY visible (visible)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'payment';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  member mediumint(6) unsigned NOT NULL DEFAULT '0',
		  reference mediumint(8) unsigned NOT NULL DEFAULT '0',
		  type varchar(50) COLLATE utf8_swedish_ci NOT NULL,
		  amount float(8,2) NOT NULL DEFAULT '0.00',
		  deadline date NOT NULL DEFAULT '0000-00-00',
		  paidday date NOT NULL DEFAULT '0000-00-00',
		  validuntil date NOT NULL DEFAULT '0000-00-00',
		  club mediumint(6) unsigned NOT NULL DEFAULT '0',
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id),
		  KEY member (member),
		  KEY visible (visible)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'club';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  id mediumint(6) unsigned NOT NULL AUTO_INCREMENT,
		  title varchar(140) COLLATE utf8_swedish_ci NOT NULL,
		  address tinytext COLLATE utf8_swedish_ci NOT NULL,
		  visible tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (id)
		  KEY visible (visible)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci ;";

        dbDelta( $sql );
    }

    $table_name = $wpdb->prefix . $mr_prefix . 'country';
    if ( $wpdb->get_var( "show tables like '" . $table_name . "'" ) != $table_name ) {
        $sql = "CREATE TABLE " . $table_name . " (
		  code varchar(2) COLLATE utf8_swedish_ci NOT NULL,
		  name varchar(140) COLLATE utf8_swedish_ci NOT NULL,
		  PRIMARY KEY (code)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_swedish_ci COMMENT='County codes as per ISO 3166-1 alpha-2';";

        dbDelta( $sql );

        $sql = "INSERT INTO wp_mr_country (code, name) VALUES
		('AF', 'Afghanistan'),
		('AL', 'Albania'),
		('DZ', 'Algeria'),
		('AS', 'American Samoa'),
		('AD', 'Andorra'),
		('AO', 'Angola'),
		('AI', 'Anguilla'),
		('AQ', 'Antarctica'),
		('AG', 'Antigua and Barbuda'),
		('AR', 'Argentina'),
		('AM', 'Armenia'),
		('AW', 'Aruba'),
		('AU', 'Australia'),
		('AT', 'Austria'),
		('AZ', 'Azerbaijan'),
		('BS', 'Bahamas'),
		('BH', 'Bahrain'),
		('BD', 'Bangladesh'),
		('BB', 'Barbados'),
		('BY', 'Belarus'),
		('BE', 'Belgium'),
		('BZ', 'Belize'),
		('BJ', 'Benin'),
		('BM', 'Bermuda'),
		('BT', 'Bhutan'),
		('BO', 'Bolivia'),
		('BA', 'Bosnia and Herzegovina'),
		('BW', 'Botswana'),
		('BV', 'Bouvet Island'),
		('BR', 'Brazil'),
		('IO', 'British Indian Ocean Territory'),
		('BN', 'Brunei Darussalam'),
		('BG', 'Bulgaria'),
		('BF', 'Burkina Faso'),
		('BI', 'Burundi'),
		('CI', 'C´te d''Ivoire'),
		('KH', 'Cambodia'),
		('CM', 'Cameroon'),
		('CA', 'Canada'),
		('CV', 'Cape Verde'),
		('KY', 'Cayman Islands'),
		('CF', 'Central African Republic'),
		('TD', 'Chad (Tchad)'),
		('CL', 'Chile'),
		('CN', 'China, People''s Republic of'),
		('CX', 'Christmas Island'),
		('CC', 'Cocos Islands'),
		('CO', 'Colombia'),
		('KM', 'Comoros'),
		('CD', 'Congo, Democratic Republic of the'),
		('CG', 'Congo, Republic of the'),
		('CK', 'Cook Islands'),
		('CR', 'Costa Rica'),
		('HR', 'Croatia (Hrvatska)'),
		('CU', 'Cuba'),
		('CY', 'Cyprus'),
		('CZ', 'Czech Republic'),
		('DK', 'Denmark'),
		('DJ', 'Djibouti'),
		('DM', 'Dominica'),
		('DO', 'Dominican Republic'),
		('EC', 'Ecuador'),
		('EG', 'Egypt'),
		('SV', 'El Salvador'),
		('GQ', 'Equatorial Guinea'),
		('ER', 'Eritrea'),
		('EE', 'Estonia'),
		('ET', 'Ethiopia'),
		('FK', 'Falkland Islands'),
		('FO', 'Faroe Islands'),
		('FJ', 'Fiji'),
		('FI', 'Finland (Suomi)'),
		('FR', 'France'),
		('GF', 'French Guiana'),
		('PF', 'French Polynesia'),
		('TF', 'French Southern Territories'),
		('GA', 'Gabon'),
		('GM', 'Gambia'),
		('GE', 'Georgia'),
		('DE', 'Germany'),
		('GH', 'Ghana'),
		('GI', 'Gibraltar'),
		('GR', 'Greece'),
		('GL', 'Greenland'),
		('GD', 'Grenada'),
		('GP', 'Guadeloupe'),
		('GU', 'Guam'),
		('GT', 'Guatemala'),
		('GN', 'Guinea'),
		('GW', 'Guinea-Bissau'),
		('GY', 'Guyana'),
		('HT', 'Haiti'),
		('HM', 'Heard Island and McDonald Islands'),
		('HN', 'Honduras'),
		('HK', 'Hong Kong'),
		('HU', 'Hungary'),
		('IS', 'Iceland'),
		('IN', 'India'),
		('ID', 'Indonesia'),
		('IR', 'Iran, Islamic Republic of'),
		('IQ', 'Iraq'),
		('IE', 'Ireland, Republic of'),
		('IL', 'Israel'),
		('IT', 'Italy (Italia)'),
		('JM', 'Jamaica'),
		('JP', 'Japan (日本)'),
		('JO', 'Jordan'),
		('KZ', 'Kazakhstan'),
		('KE', 'Kenya'),
		('KI', 'Kiribati'),
		('KP', 'Korea, Democratic People''s Republic of (North Korea)'),
		('KR', 'Korea, Republic of (South Korea)'),
		('KW', 'Kuwait'),
		('KG', 'Kyrgyzstan'),
		('LA', 'Lao People''s Democratic Republic (Laos)'),
		('LV', 'Latvia'),
		('LB', 'Lebanon'),
		('LS', 'Lesotho'),
		('LR', 'Liberia'),
		('LY', 'Libyan Arab Jamahiriya (Libya)'),
		('LI', 'Liechtenstein'),
		('LT', 'Lithuania'),
		('LU', 'Luxembourg'),
		('MO', 'Macao (Macau)'),
		('MK', 'Macedonia, The Former Yugoslav Republic of'),
		('MG', 'Madagascar'),
		('MW', 'Malawi'),
		('MY', 'Malaysia'),
		('MV', 'Maldives'),
		('ML', 'Mali'),
		('MT', 'Malta'),
		('MH', 'Marshall Islands'),
		('MQ', 'Martinique'),
		('MR', 'Mauritania'),
		('MU', 'Mauritius'),
		('YT', 'Mayotte'),
		('MX', 'Mexico'),
		('FM', 'Micronesia, Federated States of'),
		('MD', 'Moldova, Republic of'),
		('MC', 'Monaco'),
		('MN', 'Mongolia'),
		('ME', 'Montenegro'),
		('MS', 'Montserrat'),
		('MA', 'Morocco'),
		('MZ', 'Mozambique'),
		('MM', 'Myanmar (Burma)'),
		('NA', 'Namibia'),
		('NR', 'Nauru'),
		('NP', 'Nepal'),
		('NL', 'Netherlands'),
		('AN', 'Netherlands Antilles'),
		('NC', 'New Caledonia'),
		('NZ', 'New Zealand'),
		('NI', 'Nicaragua'),
		('NE', 'Niger'),
		('NG', 'Nigeria'),
		('NU', 'Niue'),
		('NF', 'Norfolk Island'),
		('MP', 'Northern Mariana Islands'),
		('NO', 'Norway (Norge)'),
		('OM', 'Oman'),
		('PK', 'Pakistan'),
		('PW', 'Palau'),
		('PS', 'Palestinian Territory, Occupied'),
		('PA', 'Panama'),
		('PG', 'Papua New Guinea'),
		('PY', 'Paraguay'),
		('PE', 'Peru'),
		('PH', 'Philippines'),
		('PN', 'Pitcairn Islands'),
		('PL', 'Poland'),
		('PT', 'Portugal'),
		('PR', 'Puerto Rico'),
		('QA', 'Qatar'),
		('RE', 'Reunion'),
		('RO', 'Romania'),
		('RU', 'Russian Federation'),
		('RW', 'Rwanda'),
		('SH', 'Saint Helena'),
		('KN', 'Saint Kitts and Nevis'),
		('LC', 'Saint Lucia'),
		('PM', 'Saint Pierre and Miquelon'),
		('VC', 'Saint Vincent and the Grenadines'),
		('WS', 'Samoa'),
		('SM', 'San Marino'),
		('ST', 'Sao Tome and Principe'),
		('SA', 'Saudi Arabia'),
		('SN', 'Senegal'),
		('RS', 'Serbia'),
		('CS', 'Serbia and Montenegro'),
		('SC', 'Seychelles'),
		('SL', 'Sierra Leone'),
		('SG', 'Singapore'),
		('SK', 'Slovakia'),
		('SI', 'Slovenia (Slovenija)'),
		('SB', 'Solomon Islands'),
		('SO', 'Somalia'),
		('ZA', 'South Africa (Zuid Afrika)'),
		('GS', 'South Georgia and the South Sandwich Islands'),
		('ES', 'Spain (España)'),
		('LK', 'Sri Lanka'),
		('SD', 'Sudan'),
		('SR', 'Suriname'),
		('SJ', 'Svalbard and Jan Mayen Islands'),
		('SZ', 'Swaziland'),
		('SE', 'Sweden'),
		('CH', 'Switzerland'),
		('SY', 'Syrian Arab Republic (Syria)'),
		('TW', 'Taiwan, Province of China (Republic of China)'),
		('TJ', 'Tajikistan'),
		('TZ', 'Tanzania, United Republic of'),
		('TH', 'Thailand'),
		('TL', 'Timor-Leste (East Timor)'),
		('TG', 'Togo'),
		('TK', 'Tokelau'),
		('TO', 'Tonga'),
		('TT', 'Trinidad and Tobago'),
		('TN', 'Tunisia'),
		('TR', 'Turkey'),
		('TM', 'Turkmenistan'),
		('TC', 'Turks and Caicos Islands'),
		('TV', 'Tuvalu'),
		('UG', 'Uganda'),
		('UA', 'Ukraine'),
		('AE', 'United Arab Emirates'),
		('GB', 'United Kingdom'),
		('US', 'United States'),
		('UM', 'United States Minor Outlying Islands'),
		('UY', 'Uruguay'),
		('UZ', 'Uzbekistan'),
		('VU', 'Vanuatu'),
		('VA', 'Vatican City State'),
		('VE', 'Venezuela'),
		('VN', 'Viet Nam (Vietnam)'),
		('VG', 'Virgin Islands, British'),
		('VI', 'Virgin Islands, U.S.'),
		('WF', 'Wallis and Futuna'),
		('EH', 'Western Sahara'),
		('YE', 'Yemen'),
		('ZM', 'Zambia'),
		('ZW', 'Zimbabwe'),
		('AX', 'Åland Islands');";

        dbDelta( $sql );
    }

    add_option( 'mr_db_version', $mr_db_version );
}


