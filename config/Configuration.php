<?php


final class Config {


    public $EBORICA_TEST_MODE = true;
    public $EBORICA_TERMINAL_ID = 62161203;
    public $EBORICA_SERVICE_DESCRIPTION = 'Verification';
    public $EBORICA_LANGUAGE = 'BG';

    // TESTING
    public $EBORICA_CLIENT_SIGN_KEY_TEST = 'C:\local_projects\gs_website\config\test.gs_stroimarket.key';
    public $EBORICA_PROVIDER_SIGN_CERT_TEST = 'C:\local_projects\gs_website\config\Test_test.gs_stroimarket_f.cer';
    public $EBORICA_PROVIDER_VERIGY_CERT_TEST = 'C:\local_projects\gs_website\config\Test_test.gs_stroimarket_f.cer';

    public $EBORICA_CLIENT_ETLOG_KEY_TEST = 'C:\local_projects\gs_website\config\etlog.gs_stroimarket.key';
    public $EBORICA_CLIENT_ETLOG_CERT_TEST = 'C:\local_projects\gs_website\config\eTlog_etlog.gs_stroimarket_f.cer';
    public $EBORICA_CLIENT_SIGN_KEY_PASSWD_TEST = '123987';
    public $EBORICA_CLIENT_ETLOG_KEY_PASSWD_TEST = '';

    // PRODUCTION
    public $EBORICA_CLIENT_SIGN_KEY = 'C:\local_projects\gs_website\config\live.gs_stroimarket.key';
    public $EBORICA_PROVIDER_SIGN_CERT = 'C:\local_projects\gs_website\config\Production_live.gs_stroimarket_f.cer';
    public $EBORICA_CLIENT_ETLOG_KEY = 'C:\local_projects\gs_website\config\etlog.gs_stroimarket.key';
    public $EBORICA_CLIENT_ETLOG_CERT = 'C:\local_projects\gs_website\config\eTlog_etlog.gs_stroimarket_f.cer';
    public $EBORICA_CLIENT_SIGN_KEY_PASSWD = '';
    public $EBORICA_CLIENT_ETLOG_KEY_PASSWD = '';

    public $GOOGLE_RECAPTCHA = array(
        'private_key' => '6LfY7TYUAAAAAIuX6P55pspey52hmhqnhU0Ddbrs',
        'url' => 'https://www.google.com/recaptcha/api/siteverify',
        'public_key' => '6LfY7TYUAAAAAJHa5AyuPgpZc7kfOgrsN9MYlE30'
    );

	/******************************************************************
	 * APPLICATION SETTINGS
	 ******************************************************************/

	/**
	 * Sets the application systems available
	 *
	 * Syntax is as follows: url_for_access => dirname
	 * Basicly once defined only the access urls can be changed.
	 * url_for_access with value of `default` is opened on the main domain
	 *
 * @var array
	 * @access public
	 */
public $APPLICATIONS = array(
		'default' 	=> 'public',
		'admin' 	=> 'admin',
		'modules'	=> 'modules',
	);

	public $MODULES = array(
		'admin',
);

	/**
	 * The format of the supplied Cache driver:
	 * memcache://host:port:NS
	 * filesystem://
	 *
	 * @var string
	 * @access public
	 */
	//public $CACHE_DRIVER = 'memcache://127.0.0.1:11211:vivacom';
	public $CACHE_DRIVER = 'filesystem://127.0.0.1:11211:fw';

	/**
	 * Sets the enviroment mode
	 *
	 * @var boolean
	 * @access public
	 */
	public $DEVELOPMENT = true;


	/******************************************************************
	 * DATABASE SETTINGS
	 ******************************************************************/

	/**
	 * Data source name
	 *
	 * The format of the supplied DSN is in its fullest form:
	 *
	 *  driver://username:password@protocol+hostspec/database
	 *
	 * Most variations are allowed:
	 *
	 *  driver://username:password@protocol+hostspec:110//usr/db_file.db
	 *  driver://username:password@hostspec/database
	 *  driver://username:password@unix(/path/to/socket)/database
	 *  driver://username:password@hostspec
	 *  driver://username@hostspec
	 *  driver://hostspec/database
	 *  driver://hostspec
	 *  driver
	 *
	 * @var string
	 * @access public
	 */
//	public $DSN = 'mysqli://root:@desert/gs_stroimarket';
	public $DSN = 'mysqli://themags:betamag@35.205.164.64/gs_stroimarket';

	/**
	 * Are database records mirrored for each language or not
	 *
	 * @var boolean
	 * @access public
	 */
	public $DB_MIRROR = false;

	/**
	 * Table prefix
	 *
	 * @var string
	 * @access public
	 */
	public $DB_PREFIX = '';

	/******************************************************************
	 * SESSION SETTINGS
	 ******************************************************************/

	/**
	 * Type of session
	 *
	 * - db - session store in database
	 * - standard - use session standard functions.
	 * - counter - use stantard php session with db active counter
	 * - redis - redis data structire server
	 *
	 * @var string
	 * @access public
	 */
	public $SESSION_TYPE = 'standard';


	/******************************************************************
	 * DEBUG SETTINGS
	 ******************************************************************/

	/**
	 * Mode for all exceptions. Combination of following: screen, mail, sms
	 *
	 * @var string
	 * @access public
	 */
	public $DEBUG_MODE = 'screen';

	/******************************************************************
	 * SMTP SETTINGS
	 ******************************************************************/

	 /**
	 * The format of the supplied SMTP is in its fullest form:
	 *
	 *  smtp://username:password@host:port/helo
	 *
	 * @var string
	 * @access public
	 */
	public $SMTP = 'smtp://:@35.205.164.64:2525/35.205.164.64';

	/******************************************************************
	 * INTERNATIONALIZATION SETTINGS
	 ******************************************************************/

	/**
	 * All possible languages for the application
	 * - keys -> shortcuts to the website
	 * - values -> according to IANA registry
	 *
	 * @var array
	 * @access public
	 */
	public $LOCALE_SHORTCUTS = array('bg' => 'bg-BG', 'en' => 'en-US', 'de'=>'de-DE');

	/**
	 * Default locale
	 * For structure look above description
	 *
	 * @var string
	 * @access public
	 */
	public $DEFAULT_LOCALE = 'bg-BG';


	/******************************************************************
	 * PATH SETTINGS
	 ******************************************************************/

	/**
	 * *nix file command path. Leave empty to use default include path.
	 *
	 * @var string
	 * @access public
 */
	public $FILE_ROOT = "/usr/bin/";
	public $EMAILS_FROM = array('Stefan Karadjov', 'stefan.karadjov@themags.com');

	public $ITEMS_PER_PAGE = 20;
	
	public $FB_ID = '1749095718732524';

	public $GOOGLE_ID = '708382848504-7cjgdmb78olctjeihi148jdcqmj0nh7n.apps.googleusercontent.com';
}

?>