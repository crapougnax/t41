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

use t41\View\SimpleComponent;

use t41\Core,
	t41\Config;

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
	static public $autoloaderPrefixes = array();
	
	
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
	 * Unique application identifier
	 * Used for the cache for example
	 * @var string
	 */
	static public $appId = 't41';
	
	
	/**
	 * Lazy(-loading) mode
	 * 
	 * @var boolean
	 */
	static public $lazy = false;
	
	
	/**
	 * Cache backend type (ZF adapters)
	 * @var string
	 */
	static public $cache = 'File';
	
	
	/**
	 * Cache Time To Live (in seconds)
	 * @var integer
	 */
	static public $cacheTTL = 7200;
	
	
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
	static protected $_env = array();

	
	/**
	 * App base path
	 *
	 * @var string
	 */
    static public $basePath;

    static public $t41Path;
    
    static public $env;
    
    
    static public $mode;
    
    
    /**
     * Configuration Data
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
	 * An array of memory usage info
	 * @var array
	 */
	static public $memusage = array();
	

    /**
     * Faut-il utiliser les gestionnaires d'exception t41 ?
     * (deconseille pendant le developpement)
     *
     * @var boolean
     */
    static protected $_fancyExceptions = true;
    
    
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
     * Set Application and t41 paths
     * @param string $path
     */
    public static function setPaths($path)
    {
    	if (file_exists($path)) {
	    	self::$basePath = $path;
    		$dirs = explode(DIRECTORY_SEPARATOR, __DIR__);
    		$dirs = array_slice($dirs, 0, count($dirs)-2);
    		self::$t41Path = implode(DIRECTORY_SEPARATOR, $dirs);
    		return true;
    	}
    	
    	return false;
    }
    
    
    /**
     * Sets the necessary paths and optionaly remove an already existing Zend Framework path
     * @param string $path
     * @param boolean $removeZfPath
     */
    public static function setIncludePaths($path, $removeZfPath = false)
    {
    	if (substr($path, -1) != DIRECTORY_SEPARATOR) $path .= DIRECTORY_SEPARATOR;
    	
		self::setPaths($path);

		$dpaths = explode(PATH_SEPARATOR, get_include_path());
		
		if ($removeZfPath === true) {
			foreach ($dpaths as $key => $dpath) {
				if (strpos($dpath, "/ZendFramework/") !== false) {
					unset($paths[$key]);
				}
			}
		}
		
    	set_include_path(
    			implode(PATH_SEPARATOR, $dpaths) . PATH_SEPARATOR
    			. $path . PATH_SEPARATOR
    			. $path . 'application/' . PATH_SEPARATOR
    			. $path . 'vendor/' . PATH_SEPARATOR
    			. $path . 'vendor/quatrain/t41/library' . PATH_SEPARATOR
    			. $path . 'vendor/zend/zf1/library'
    	);
    }

    
    /**
     * Add a prefix to be searched by the autoloader
     * @param string|array $prefix
     */
	public static function addAutoloaderPrefix($prefix, $path = null)
	{
		set_include_path(get_include_path() . PATH_SEPARATOR . $path);
		
		if (is_array($prefix)) {

			foreach ($prefix as $key => $val) {
			
				self::$autoloaderPrefixes[$key] = $val;	
				if (is_object(self::$_autoloaderInstance)) {

					if (substr($key, -1) == '_') {
						
						self::$_autoloaderInstance->registerPrefix($key, $val);
					} else {
						self::$_autoloaderInstance->registerNamespace($key, $val);
					}
				}
			}
			
		} else {
			
			self::$autoloaderPrefixes[$prefix] = $path;	
			if (is_object(self::$_autoloaderInstance)) {
				if (substr($prefix, -1) == '_') {
				
					self::$_autoloaderInstance->registerPrefix($prefix, $path);
				} else {
					self::$_autoloaderInstance->registerNamespace($prefix, $path);
				}
			}
		}
		
/*		if (self::$_autoloaderInstance) {
			\Zend_Debug::dump(self::$_autoloaderInstance->getRegisteredNamespaces());
		}
*/
	}
	
	
	public static function enableAutoloader($prefix = null)
	{
		if ($prefix) {
			self::addAutoloaderPrefix($prefix);
		}
		
		//self::$_autoloaderInstance = new \Zend\Loader\StandardAutoloader();
		require_once 'Zend/Loader/Autoloader.php';
		self::$_autoloaderInstance = \Zend_Loader_Autoloader::getInstance();
		
		self::$autoloaderPrefixes['t41'] = self::$basePath . 'vendor/quatrain/t41/library/t41';
		
		foreach (self::$autoloaderPrefixes as $prefix => $path) {
		
			if (substr($prefix, -1) == '_') {
				
				self::$_autoloaderInstance->registerNamespace($prefix, $path);
//				self::$_autoloaderInstance->registerPrefix($prefix, $path);
				
			} else {
				
				self::$_autoloaderInstance->registerNamespace($prefix, $path);
			}

//			self::$_autoloaderInstance->register();
		}
		
   		//\Zend\Debug::dump(self::$_autoloaderInstance);
    	self::$autoloaded = true;
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
    		set_exception_handler(array('\t41\Core', 'exceptionHandler'));
    		
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
        switch (View::getDisplayContext()) {
            
            case 'ajax':
              //  $ajax = new t41_Ajax();
              //  $ajax->setSendMessage($e->getMessage(), t41_Ajax::ERR);
                break;
                
            default:
            	View::resetObjects('default'); // to avoid infinite loop and fatal error, reset view content
				$error = new SimpleComponent();
				$error->setTitle('ERREUR FATALE : ' . html_entity_decode($e->getMessage()));
                if (self::$env == self::ENV_DEV) {
                	
                	// in dev mode, also print out execution trace
                    $error->setContent('<br/><pre>' . $e->getTraceAsString() . '</pre>');
                }
                $error->register();
                
	            exit(View::display());
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
        
        $headers = headers_list(); //  getallheaders();
        
        if (isset($headers['X-Requested-With']) && $headers['X-Requested-With'] == 'XMLHttpRequest') {
        	
        	//header($_SERVER['SERVER_PROTOCOL'] . ' ' . $errStr, true, 500);
			exit(sprintf('{status:"ERR", message:"%s", context:{line:%d,file:%s}', $errStr, $errLine, $errFile));

        } else {
        	
        	View::addError($errStr, $errFile, $errNo);
        }
        
        if ($fatale == true) {
        	
        	exit($errStr);
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
    	// enable garbage collection
    	gc_enable();
    	
    	// enable t41 error handler (notices are not catched until we get a proper logger)
    	set_error_handler(array('t41\Core', 'userErrorHandler'), (E_ALL | E_STRICT) ^ E_NOTICE);
    	
    	// define path but only if it's empty
    	if (! is_null($path) && empty(self::$basePath)) self::setPaths($path);

    	/* add application & t41 config files path (in first position if none was declared before) */
    	Config::addPath(self::$basePath . 'application/configs/', Config::REALM_CONFIGS);
    	Config::addPath(self::$basePath . 'vendor/quatrain/t41/configs/', Config::REALM_CONFIGS);
    	 
    	/* add templates folder path (in first position if none was declared before) */
    	Config::addPath(self::$basePath . 'application/views/', Config::REALM_TEMPLATES);

    	/* add t41 & application controllers paths (in first position if none was declared before) */
    	Config::addPath(self::$basePath . 'application/controllers/', Config::REALM_CONTROLLERS, null, 'default');
    	Config::addPath(self::$basePath . 'vendor/quatrain/t41/controllers/', Config::REALM_CONTROLLERS, null, 't41');
    	
    	/* register default REST controllers path */
    	/* @todo allow override in config file or even later */
    	Config::addPath(self::$basePath . 'vendor/quatrain/t41/controllers/rest/', Config::REALM_CONTROLLERS, null, 'rest');
    	 
    	// never cached, shall it be ?
    	$config = Config\Loader::loadConfig('application.xml');
    	self::$_config = $config['application'];
    	
    	/* CLI Mode */
    	if (php_sapi_name() == 'cli') {
    		
    		self::$mode = self::$_config['environments']['mode'] = 'cli';
			
			$opts = new \Zend_Console_Getopt(array('env=s'			=> 'Environment value'
												, 'controller=s'	=> "Controller"
												, 'module=s'		=> "Module"
												, 'action=s'		=> "Action"
												, 'params=s'		=> "Action parameters"
												, 'simulate'		=> "Simulate execution"
									)
							   );

			try {
				$opts->parse();
				
				//var_dump($opts->params); die;
				
			} catch (\Zend_Console_GetOpt_Exception $e) {
				
				die($e->getUsageMessage());
			}
			
			$match = trim($opts->env);
			
			/* temporary */
			define('CLI_CONTROLLER', trim($opts->controller));
			define('CLI_MODULE', trim($opts->module));
			define('CLI_ACTION', trim($opts->action));
			define('CLI_PARAMS', $opts->params);
			define('CLI_SIMULATE', (bool) $opts->simulate);
			
		} else {
    	
			View::setDisplay(View\Adapter\WebAdapter::ID);
			
	    	/* array of mode / $_SERVER data key value */
    		$envMapper = array('hostname' => 'SERVER_NAME');
    	
    		$match = isset($_SERVER[ $envMapper[self::$_config['environments']['mode']] ]) ?  $_SERVER[ $envMapper[self::$_config['environments']['mode']] ] : null;
		}
		
    	/* define which environment matches current mode value */
    	if (is_null($match)) {
    		throw new Config\Exception("environment value not detected");
    	}

    	self::$appId = str_replace('.', '_', $match);
    	
    	$envKey = null;
    	
    	switch (self::$_config['environments']['mode']) {
    		
    		case 'cli':
    	    	foreach (self::$_config['environments'] as $key => $value) {
    				
    				if (! is_array($value)) continue;
    				
    				if ($key == $match) {
    					
    					$envKey = self::$env = $key;
    					break;
    				}
    			}
    			break;
    			    		
    		case 'hostname':
    		default:	
    			foreach (self::$_config['environments'] as $key => $value) {
    				
    				if (! is_array($value)) continue;
    				
    				if (isset($value['hostname']) && in_array($match, (array) $value['hostname'])) {
    					$envKey = self::$env = $key;
    					break;
    				}
    			}
    			break;
    	}
    	
    	if (is_null($envKey)) {
    		throw new Config\Exception("No matching environment found");
    	}
    	
    	self::$_env += self::$_config['environments'][$envKey];
    	
    	if (self::getEnvData('cache_backend')) {
    	
    		self::$cache = self::getEnvData('cache_backend');
    	}
    	 
    	
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
		
		// load modules
		Core\Module::init($mpath ? $mpath : self::$basePath);
		
		// load ACL
		Core\Acl::init($mpath ? $mpath : self::$basePath);
		
		/* load configuration files if lazy mode is off */
		if (self::$lazy !== true) {

			// get backends configuration
    		Backend::loadConfig();

    		// get mappers configuration
    		Mapper::loadConfig();
    	
    		// get object model configuration
    		ObjectModel::loadConfig();
    		
		}
		
        // configure error reporting according to env
        if (in_array(self::$env, array(self::ENV_STAGE, self::ENV_PROD))) {
        	
        	error_reporting(E_ERROR); //(E_ALL | E_STRICT) ^ E_NOTICE);
        	ini_set('display_errors', 1);
        	
        } else {
            error_reporting(E_ALL & ~E_STRICT);
            ini_set('display_errors', 1);
        }

		// define some basic view data
		View::setEnvData('t41.version', self::VERSION);
//		if (class_exists('Zend_Version')) View::setEnvData('zf.version', \Zend_Version::VERSION);
		View::setEnvData('app.name', self::$_config['name']);
		View::setEnvData('app.version', self::getVersion());
		
	    // set a cache adapter
        if (! isset(self::$_adapters['registry'])) {
    		self::$_adapters['registry'] = new \Zend_Registry();
    	}
    	        
        // (re-)init session 
        if (! isset(self::$_adapters['session'])) {
    		self::$_adapters['session'] = '';//new t41_Session_Default();
    	}

        // to be done at the very end to avoid empty stack on exception
        if (self::$_fancyExceptions === true) {
	        //set_exception_handler(array('t41\Core', 'exceptionHandler'));
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
     * @deprecated
     */
    public static function htmlEncode($str)
    {
    	trigger_error("htmlEncode() is marked as deprecated and will be removed soon");
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
	 * ex: t41\Core::getAppKey('map', 'google') to get a GoogleMap API Key.
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
    
    /**
     * Returns a Zend Framework cache instance
     * @return \Zend_Cache_Core
     */
    public static function cacheGetAdapter()
    {
    	if (! isset(self::$_adapters['cache'])) {
    	
    		$cacheDir = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'C:\TEMP' : '/dev/shm';
    		if (! file_exists($cacheDir . DIRECTORY_SEPARATOR . self::$appId)) {
    			if (mkdir($cacheDir . DIRECTORY_SEPARATOR . self::$appId)) {
    				$cacheDir .= DIRECTORY_SEPARATOR . self::$appId;
    			}
    		} else {
    			$cacheDir .= DIRECTORY_SEPARATOR . self::$appId;
    		}
    		self::setAdapter('cache', \Zend_Cache::factory('Core'
    				, self::$cache
    				, array('automatic_serialization' 	=> true,
    						'cache_id_prefix' 			=> self::$appId . '__',
    						'lifetime'					=> self::$cacheTTL,
    				)
    				, array(
    						'hashed_directory_level'	=> 3,
    						'cache_dir' => $cacheDir,
    				)
    		)
    		);
    	}

    	return self::$_adapters['cache'];
    }
    
    
    public static function cacheSet($val, $key = null, $force = false, array $options = array())
    {
    	$cache = self::cacheGetAdapter();

    	if (is_null($key)) $key = md5(microtime() . $_SERVER['REMOTE_ADDR']);
    	 
        if (is_object($val)) {
        	$val = array('_class' => get_class($val), 'content' => serialize($val));
        }
        
        // don't re-cache already cached-content, except if force is set to true
        if ($cache->load($key) !== false && $force == false) {
        	return $key;
        }
        
    	return $cache->save($val, $key, isset($options['tags']) ? (array) $options['tags'] : array()) ? $key : false;
    }
    
    
    public static function cacheGet($key)
    {
    	$cache = self::cacheGetAdapter();
    	
    	$cached = $cache->load($key);
    	
        if (is_array($cached)) {
        	
        	if (isset($cached['_class'])) {
        		try {
                       \Zend_Loader::loadClass($cached['_class']);
                    } catch (Exception $e) {
                        return null;
                    }
                    
                return unserialize($cached['content']);
                
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
    		
    		if (($config = Config\Loader::loadConfig(self::$basePath . 'configs/messages/' . $store . '.' . $lang . '.xml')) === false) {

    			if (($config = Config\Loader::loadConfig(self::$basePath . 'configs/messages/' . $store . '.xml')) === false) {
    			
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
    
    
    static public function memUsage($group = 'default', $id = null)
	{
		list(, $caller) = debug_backtrace(false);
	//	\Zend_Debug::dump($caller); die;
		if (! isset(self::$memusage[$group])) {
			self::$memusage[$group] = array();
		}

		$index = substr(microtime(), 0,9);
		self::$memusage['default'][$index] = memory_get_usage();
		$func = $caller['class'] . '::' . $caller['function'];
		self::$memusage[$func][] = $index;
	}
	
	
	static public function processMemUsage()
	{
		
	}
}
