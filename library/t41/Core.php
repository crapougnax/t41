<?php

namespace t41;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 914 $
 */

use t41\Core;
use t41\Config;

/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Core {
	
	/**
	 * Current version
	 *
	 */
	const VERSION = '1.0.0 alpha';
	
	
	const ENV_PROD	= 'prod';
	
	
	const ENV_DEV	= 'dev';

	
	const ENV_STAGE	= 'stage';
	
	
	/**
	 * If set to TRUE, an autoloader has been activated
	 * 
	 * @var boolean
	 */
	static public $autoloaded = false;
	
	
	/**
	 * Array of prefixes for the autoloader
	 * Redefine in your child class or use t41_Core::addAutoloaderPrefix() 
	 * to add other prefixes BEFORE calling init()
	 * 
	 * @var array
	 */
	static public $autoloaderPrefixes = array('t41_');
	
	
	static protected $_autoloaderInstance;
	
	
	/**
	 * Current lang
	 * 
	 * @var string
	 */
	static public $lang = 'en';
	
	
	/**
	 * Application name
	 * 
	 * @var string
	 */
	static public $name;

	/**
	 * Lazy(-loading) mode
	 * 
	 * @var boolean
	 */
	static public $lazy = false;
	
	
	/**
	 * Debug mode
	 * 
	 * @var integer
	 */
	static public $debug = 0;
	
	
	/**
	 * Execution environment data
	 *
	 * @var array
	 */
	static protected $_env;

	
	/**
	 * App base path
	 *
	 * @var string
	 */
    static public $basePath;

    
    /**
     * Configuration Data
     *
     * @var Zend_Config
     */
    static protected $_config;

    
    /**
     * Messages array (exceptions, etc.)
     * @var array
     */
	static protected $_messages = array();
	
	/**
	 * An array of loaded items
	 * @var array
	 */
	static protected $_loaded = array();


    /**
     * Faut-il utiliser les gestionnaires d'exception t41 ?
     * (deconseille pendant le developpement)
     *
     * @var boolean
     */
    static protected $_fancyExceptions = true;
    
    
    /**
     * Array of different environment related urls of the application 
     *
     * @var array
     */
    static protected $_urls = array();
    
    
    /**
     * Array of various adapters to be defined and called throughout the application
     * Most of them have direct basic accessors available in this class
     *
     * @var array
     */
    static protected $_adapters = array();

    
    /**
     * Array of possible keys for stored adapters
     *
     * @var array
     */
    static protected $_adaptersList = array('session', 'registry', 'cache', 'backend');
    
    
    /**
     * Set given adapter for given key
     * @param string $key
     * @param string $adapter
     * @throws Exception
     */
    public static function setAdapter($key, $adapter)
    {
    	if (! in_array($key, self::$_adaptersList)) {
    		
    		throw new Exception("$key is not a valid adapter type");
    	}
    	
    	self::$_adapters[$key] = $adapter;
    }
    

    /**
     * Add a prefix to be searched by the autoloader
     * @param string|array $prefix
     */
	public static function addAutoloaderPrefix($prefix)
	{
		if (is_array($prefix)) {

			foreach ($prefix as $var) {
			
				self::$autoloaderPrefixes[] = $var;	
				if (is_object(self::$_autoloaderInstance)) { self::$_autoloaderInstance->registerNamespace($var); }
			}
		} else {
			
			self::$autoloaderPrefixes[] = $prefix;	
			if (is_object(self::$_autoloaderInstance)) {self::$_autoloaderInstance->registerNamespace($prefix); }
		}
	}
	
	
	
	public static function enableAutoloader($prefix = null)
	{
		if ($prefix) {
			
			self::addAutoloaderPrefix($prefix);
		}
		
		require_once 'Zend/Version.php';
		
		if (\Zend_Version::compareVersion('1.8.0') == true) {
	
			require_once 'Zend/Loader/Autoloader.php';
			self::$_autoloaderInstance = \Zend_Loader_Autoloader::getInstance();
   			self::$_autoloaderInstance->registerNamespace(self::$autoloaderPrefixes);
    		
		} else {
	
			require_once 'Zend/Loader.php';
			\Zend_Loader::registerAutoload();
		}
    		
    	self::$autoloaded = true;
	}
	
	
    /**
     * Allow declaration of possible url matched with one of the possible environment
     *
     * @param string $url
     * @param string $env
     */
    public static function setUrl($url, $env = self::ENV_PROD)
    {
    	if (! is_null($env) && ! in_array($env, array(self::ENV_DEV, self::ENV_STAGE, self::ENV_PROD))) {
    		
    		throw new Exception("'$env' is not a recognized environment");
    	}
    	
    	self::$_urls[$env] = $url;
    }
    
    
    public static function getUrl($env = self::ENV_PROD)
    {
        if (! is_null($env) && ! in_array($env, array(self::ENV_DEV, self::ENV_STAGE, self::ENV_PROD))) {
    		
    		throw new Exception("'$env' is not a recognized environment");
    	}
    	
    	return isset(self::$_urls[$env]) ? self::$_urls[$env] : null;
    }

    
    /**
     * Enable or disable the t41 custom exceptions handler
     *
     * @param boolean $bool
     */
    public static function setFancyExceptions($bool)
    {
    	self::$_fancyExceptions = $bool;
    	if ($bool === true) {
    		set_exception_handler(array('t41_Core', 'exceptionHandler'));
    		
    	} else {
    		
    		restore_exception_handler();
    	}
    	
    	// le traitement n'est actuellement fait que pendant l'init
    }
    
    
    /**
     * Gestionnaire d'exceptions t41
     *
     * @param Exception $e
     */
    public static function exceptionHandler(Exception $e)
    {
        switch (\t41\View::getDisplayContext()) {
            
            case 'ajax':
                require_once 't41/Ajax.php';
                $ajax = new t41_Ajax();
                $ajax->setSendMessage($e->getMessage(), t41_Ajax::ERR);
                break;
                
            default:
            	\t41\View::resetObjects('default'); // to avoid infinite loop and fatal error, reset view content
            	require_once 't41/View/Error.php';
				$error = new t41_View_Error();
				$error->setTitle('ERREUR FATALE : ' . html_entity_decode($e->getMessage()));
                if (self::getEnvData('webEnv') == self::ENV_DEV) {
                	
                	// in dev mode, also print out execution trace
                    $error->setContent('<pre>' . $e->getTraceAsString() . '</pre>');
                }
                $error->register();
                
	            exit(\t41\View::display());
                break;
        }        
    }
    
    
    public static function userErrorHandler($errNo, $errStr, $errFile, $errLine, $errContext)
    {
        $fatale = false;
        
        switch($errNo) {
        	
            case E_USER_ERROR:
                $fatale = true;
                break;
        }
        
        if (\t41\View::getDisplayContext() == 'ajax') {
            
            require_once 't41/Ajax.php';
            $ajax = new t41_Ajax();
            $ajax->addData('file', $errFile);
            $ajax->addData('line', $errLine);
            
            $ajax->setSendMessage($errStr, t41_Ajax::ERR);

        } else {
        	
        	\t41\View::addError($errStr, $errFile, $errNo);
        }
        
        if ($fatale == true) {
        	
        	die ($errStr);
        }
    }
    
    
    /**
     * environment builder
     *
     * @var string $path base path
     * @var string $mpath modules path
     */
    public static function init($path = null, $mpath = null)
    {
    	// enable t41 error handler (notices are not catched until we get a proper logger)
    	set_error_handler(array('t41_Core', 'userErrorHandler'), (E_ALL | E_STRICT) ^ E_NOTICE);
    	
    	// define path but only if it's empty
    	if (empty(self::$basePath)) self::$basePath = $path;

    	/* add application config files path (in first position if none was declared before) */
    	Config::addPath($path . 'application/configs/', Config::REALM_CONFIGS);
    	
    	/* add templates folder path (in first position if none was declared before) */
    	Config::addPath($path . 'application/views/', Config::REALM_TEMPLATES);
    	
    	$config = Config\Loader::loadConfig('application.xml');
    	self::$_config = $config['application'];
    	
    	// load modules
    	Core\Module::init($mpath ? $mpath : $path);
    	
    	/* CLI Mode */
    	if (isset(self::$_config['cli']) && PHP_SAPI == 'cli') {
    		
    		self::$_config['environments']['mode'] = 'cli';
			
			$opts = new \Zend_Console_Getopt(array('env=s'			=> 'Environment value'
												, 'controller=s'	=> "Controller"
												, 'action=s'		=> "Action"
												, 'simulate'		=> "Simulate execution"
									)
							   );

			try {
				$opts->parse();
				
			} catch (\Zend_Console_Getopt_Exception $e) {
				
				die($e->getUsageMessage());
			}
			
			$match = trim($opts->env);
			
			/* temporary */
			define('CLI_CONTROLLER', trim($opts->controller));
			define('CLI_ACTION', trim($opts->action));
			define('CLI_SIMULATE', (bool) $opts->simulate);
			
		} else {
    	
			\t41\View::setDisplay('Web');
			
	    	/* array of mode / $_SERVER data key value */
    		$envMapper = array('hostname' => 'SERVER_NAME');
    	
    		$match = isset($_SERVER[ $envMapper[self::$_config['environments']['mode']] ]) ?  $_SERVER[ $envMapper[self::$_config['environments']['mode']] ] : null;
		}
		
    	/* define which environment matches current mode value */
    	if (is_null($match)) {
    		
    		throw new \t41\Config\Exception("environment value not detected");
    	}
    	
    	$envKey = null;
    	
    	switch (self::$_config['environments']['mode']) {
    		
    		case 'cli':
    	    	foreach (self::$_config['environments'] as $key => $value) {
    				
    				if (! is_array($value)) continue;
    				
    				if ($key == $match) {
    					
    					$envKey = $key;
    					break;
    				}
    			}
    			break;
    			    		
    		case 'hostname':
    		default:	
    			foreach (self::$_config['environments'] as $key => $value) {
    				
    				if (! is_array($value)) continue;
    				
    				if (isset($value['hostname']) && $value['hostname'] == $match) {
    					
    					$envKey = $key;
    					break;
    				}
    			}
    			break;
    	}
    	
    	if (is_null($envKey)) {
    		
    		throw new Config\Exception("No matching environment found");
    	}
    	
    	self::$_env += self::$_config['environments'][$envKey];
    	
    	self::$_env['version'] = self::$_config['versions'][self::$_config['versions']['default']];
    	
    	/* define app name */
    	self::$name = isset(self::$_config['name']) ? self::$_config['name'] : 'Untitled t41-based application';
    	    	
    	/* set PHP env */
    	setlocale(E_ALL, self::$_env['version']['locale']);
    	setlocale(LC_MONETARY, self::$_env['version']['currency']);
		date_default_timezone_set(isset(self::$_env['version']['timezone']) ? self::$_env['version']['timezone'] : 'Europe/Paris');

		if (isset(self::$_env['php'])) {
			
			foreach (self::$_env['php'] as $directive => $value) {
				
				ini_set($directive, $value);
			}
		}
		
		/* define lang - can be overwritten anywhere */
		self::$lang = self::$_config['versions']['default'];
		
		/* load configuration files if lazy mode is off */
		if (self::$lazy !== true) {

			// get backends configuration
    		require_once 't41/Backend.php';
    		\t41\Backend::loadConfig();

    		// get mappers configuration
    		require 't41/Mapper.php';
    		\t41\Mapper::loadConfig();
    	
    		// get object model configuration
    		require 't41/Object.php';
    		\t41\ObjectModel::loadConfig();
		}
		
        if (get_magic_quotes_runtime()) {
            ini_set('magic_quotes_runtime', 0);
        }
        
        // protection contre les inclusions distantes
        ini_set('allow_url_fopen', 0);
        
        if (! isset(self::$_env['webEnv'])) {
	        self::getEnv();
        }
        
        // configure error reporting according to env
        if (in_array(self::$_env['webEnv'], array(self::ENV_STAGE, self::ENV_PROD))) {
        	
        	error_reporting((E_ALL | E_STRICT) ^ E_NOTICE);
        	ini_set('display_errors', 0);
        	
        } else {

            error_reporting(-1) ;//E_ALL | E_STRICT);
            ini_set('display_errors', 1);
        }

		// define some basic view data
		\t41\View::setEnvData('t41.version', self::VERSION);
		\t41\View::setEnvData('zf.version', \Zend_Version::VERSION);
		\t41\View::setEnvData('app.name', self::$_config['name']);
		\t41\View::setEnvData('app.version', self::getVersion());
        
	    // set a cache adapter
        if (! isset(self::$_adapters['registry'])) {
        	
    		self::$_adapters['registry'] = new \Zend_Registry();
    	}
    	        
        // (re-)init session 
        if (! isset(self::$_adapters['session'])) {
    		
    		self::$_adapters['session'] = new t41_Session_Default();
    	}

        // to be done at the very end to avoid empty stack on exception
        if (self::$_fancyExceptions === true) {
	        set_exception_handler(array('t41_Core', 'exceptionHandler'));
        } 
    }
    
    
    /**
     * Returns the base path of the application folder
     *
     * @return string
     */
    public static function getBasePath()
    {
        return isset(self::$_env['appPath']) ? self::$_env['appPath'] : null;
    }
    

    /**
     * Returns the base path of the application folder
     *
     * @return string
     */
    public static function gett41Path()
    {
        return isset(self::$_env['t41Path']) ? self::$_env['t41Path'] : null;
    }
    
    
    /**
     * Loads a XML config file and returns a SimpleXmlElement object or false in case of failure
     *
     * @param string $fileName
     * @return SimpleXMLElement
     * @deprecated will be removed soon
     * 
     */
    public static function loadXmlConfig($fileName)
    {
    	$filePath = self::getBasePath() . 'application/configs/' . $fileName;
    	
    	if (file_exists($filePath)) {
    		
	        return simplexml_load_file($filePath);

    	} else {
    		
    		return false;
    	}
    }
    
    
    /**
     * Try to detect environment based on URL structure
     *
     */
    protected static function getEnv()
    {
	    self::$_env['webProto'] = ($_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    	self::$_env['hostname'] = $_SERVER['SERVER_NAME'];
	    
    	if (in_array($_SERVER['SERVER_NAME'], self::$_urls)) {
    		
    		self::$_env['webEnv'] = array_search($_SERVER['SERVER_NAME'], self::$_urls);

    	} else {
    		
    		// default value
    		self::$_env['webEnv'] = self::ENV_DEV;
    	}
    }

    
    /**
     * 
     * Return the value of the environment data matching the current key and optional store
     * @param string $key
     * @param string $store
     * @return mixed
     */
    public static function getEnvData($key, $store = null)
    {
    	if (is_null($store)) {
	        return isset(self::$_env[$key]) ? self::$_env[$key] : null;
    	} else {
    		
    		if (isset(self::$_env[$store]) && isset(self::$_env[$store][$key])) {
    			
    			return self::$_env[$store][$key];
    		} else {
    			
    			return null;
    		}
    	}
    }
    
    
    /**
     * Returns the application version as stored in application.xml 
     * @return string
     */
    public static function getVersion()
    {
    	return implode('.', self::$_config['version']);
    }
    
    
    /**
     * Encode HTML entities and change CR into <br/>
     *
     * @param string $str
     * @return string
     */
    public static function htmlEncode($str)
    {
    	// quite shaky right now
    	if (mb_detect_encoding($str) == 'ISO-8859-1') {
    		
    		$str2 = htmlentities($str);
    		if (empty($str2)) $str2 = $str; // ugly temp fix when value is not returned at all
    	}
    	
        return nl2br(isset($str2) ? $str2 : $str);
    }


    /**
     * Add a value to the current session with 
     * provided $key as key
     *
     * @param string $key
     * @param mixed $val
     */
    public static function sessionAdd($key, $val)
    {
		if (! self::$_adapters['session'] instanceof t41_Session_Abstract) {
			
			throw new Exception("No session adapter declared");
		}
		/*
		
		if (is_object($val)) {
		
            $val = array('_class' => get_class($val), 'content' => gzcompress(serialize($val)));
        }
        */
		return self::$_adapters['session']->set($key, $val);
    }

    /**
     * Returns the relevant session value
     * for a given key or all session data
     *
     * @param string $key
     * @return mixed
     */
    public static function sessionGet($key = null)
    {
		if (! self::$_adapters['session'] instanceof t41_Session_Abstract) {
			
			throw new Exception("No session adapter declared");
		}
		
		return self::$_adapters['session']->get($key);
    }
    
    
	/**
	 * Returns the key to be used for the given application and vendor names
	 * ex: t41_Core::getAppKey('map', 'google') to get a GoogleMap API Key.
	 * 
	 * @param string $application
	 * @param string $vendor
	 * @return string
	 */
    public static function getAppKey($application, $vendor)
    {
    	return isset(self::$_config->key->$vendor->$application) ? self::$_config->key->$vendor->$application : null;
    }
    
    
    /**
     * CACHE GLOBAL ACCESS METHODS
     */
    
    public static function cacheSet($key, $val = null)
    {
    	if (! isset(self::$_adapters['cache'])) {
    		
    		self::setAdapter('cache', \Zend_Cache::factory('Core'
    													, 'File'
    													, array('automatic_serialization' => true))
    													 );
    	}
    	
        if (is_object($val)) {
            $val = array('_class' => get_class($val), 'content' => gzcompress(serialize($val)));
        }
        
    	return self::$_adapters['cache']->save($val, $key);
    }
    
    
    public static function cacheGet($key)
    {
        if (! isset(self::$_adapters['cache'])) {
    		
    		self::setAdapter('cache', \Zend_Cache::factory('Core'
    													, 'File'
    													, array('automatic_serialization' => true))
    													 );
    	}
    	
    	$cached = self::$_adapters['cache']->load($key);
    	
        if (is_array($cached)) {
        	
        	if (isset($cached['_class'])) {
        		try {
                       \Zend_Loader::loadClass($cached['_class']);
                    } catch (Exception $e) {
                        return null;
                    }
                    
                return unserialize(gzuncompress($cached['content']));
                
            } else {
            	
                return $cached;
            }
            
        } else {
        	
            return $cached;
        }
    }

    
    /**
     * REGISTRY GLOBAL ACCESS METHODS
     */
    
    
    public static function registrySet($key, $val = null)
    {
    	if (! isset(self::$_adapters['registry'])) {
    		
    		self::setAdapter('registry', \Zend_Registry::getInstance());
    	}
    	
    	return self::$_adapters['registry']->set($key, $val);
    }
    
    
    public static function registryGet($key)
    {
        if (! isset(self::$_adapters['registry'])) {
    		
    		self::setAdapter('registry', \Zend_Registry::getInstance());
    	}
    	
    	return self::$_adapters['registry']->get($key);
    }
    
    
    public static function registryHasObject($key)
    {
        if (! isset(self::$_adapters['registry'])) {
    		
    		self::setAdapter('registry', \Zend_Registry::getInstance());
    	}
    	    
    	return self::$_adapters['registry']->isRegistered($key);
    }
    
    
    /**
     * MESSAGE HANDLING METHOD
     * 
     * this is prototypal and should be moved to a proper class soon
     */
    
    
    /**
     * Get text message matchin given key in given store for given language
     * 
     * @param string $key	message key
     * @param string $store message store
     * @param string $lang  language iso code 
     * @return string		message if exists, given key otherwise
     */
    public static function getText($key, $store = 'base', $lang = 'en')
    {
    	// @todo cache $_messages & $_loaded
    	
    	// first have a look at the file loaded status
    	if (! isset(self::$_messages[$store]) || ! isset(self::$_loaded[$store . '_' . $lang])) {
    		
    		if (($config = Config\Loader::loadConfig(self::gett41Path() . 'configs/messages/' . $store . '.' . $lang . '.xml')) === false) {

    			if (($config = Config\Loader::loadConfig(self::gett41Path() . 'configs/messages/' . $store . '.xml')) === false) {
    			
    				return $key;
    			}
    		} else {
    			
    			// set file as loaded
    			self::$_loaded[$store . '_' .  $lang] = true; 
    		}
    		
    		self::$_messages = array_merge_recursive(self::$_messages, $config);
    	}

    	// only keep first part of the store value
    	$store = substr($store, 0, strpos($store, DIRECTORY_SEPARATOR));

    	return isset(self::$_messages[$store][$key][$lang]) ? self::$_messages[$store][$key][$lang] : $key;
    }
    
    
    public static function sendNoCacheHeaders()
    {
		header('Expires: Sat, 27 Feb 1971 11:00:00 GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
		header('Pragma: no-cache');
    }
    
    
    /**
	 *	Returns the given Query String as an associative array
	 *
	 *	@param string  $string
	 *	@param boolean $utf8
	 *	@return array
     */
    public static function queryString2Array($string, $utf8 = true)
    {
    	$string = urldecode($string);
    	
    	if ($utf8 == false && mb_detect_encoding($string, 'UTF-8') !== false) {
    		
    		$string = utf8_decode($string);
    	}
    	
		$pairs = explode('&', $string);
		$data = array();
			
		foreach ($pairs as $pair) {

			$elem = explode('=', $pair);

			/* try to convert meta string to array on the fly */
			if (substr($elem[1],0,7) == 'ARRAY:[' && substr($elem[1],-1) == ']') {
				
				$tmp = substr($elem[1], 7, strlen($elem[1]) -8);
				$tmp = explode(',', $tmp);
				if (is_array($tmp)) $elem[1] = $tmp;
			}
							
			if (substr($elem[0], -2) == '[]') {
				
				$elem[0] = str_replace('[]', '', $elem[0]);
				if (! isset($data[$elem[0]])) $data[$elem[0]] = array();
				$data[$elem[0]][] = $elem[1];
				
			} else {
			
				$data[$elem[0]] = $elem[1];
			}
		}
		
		return $data;
    }
}