<?php

namespace t41\Config;

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
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 913 $
 */

use t41\Core\Registry;

use t41\Core,
	t41\Config,
	t41\Config\Adapter;

/**
 * Class providing basic functions needed to manage Configuration files
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class Loader {
	
	
	/**
	 * Array of config adapters instances
	 * 
	 * @var array
	 */
	static protected $_adapters = array();
	
	
	/**
	 * Load a file into a Configuration Array
	 * 
	 * @param string $file
	 * @param integer $realm
	 * @param array $params
	 * 
	 * @return array|false Configuration Array or false if the file don't exists
	 */
	public static function loadConfig($file, $realm = Config::REALM_CONFIGS, array $params = null)
	{
		// try to get cached version if configs caching is enabled
		if (Core::getEnvData('cache_configs') === true) {
			$ckey = self::getCacheKey($file, $realm);
			if (($cached = Core::cacheGet($ckey)) !== false) {
				Core::log(sprintf('[Config] Retrieved %s config file from cache', $file));
				return $cached;
			}
		}
		
		if (($filePath = self::findFile($file, $realm)) == null) {
			/* no matching file name in paths */
			Core::log(sprintf('[Config] Failed loading %s config file', $file), \Zend_Log::ERR);
			return false;
		}

		$type = substr( $file, strrpos($file, '.') + 1 );
		
		/* use existing adapter instance or create it */
		if (! isset(self::$_adapters[$type])) {
			$className = sprintf('\t41\Config\Adapter\%sAdapter', ucfirst(strtolower($type)));
		
			try {
				self::$_adapters[$type] = new $className($filePath, $params);
			
				if (! self::$_adapters[$type] instanceof Adapter\AbstractAdapter) {
					throw new Exception("$className is not implementing AbstractAdapter.");
				}			
			} catch (\Exception $e) {
				throw new Exception($e->getMessage());
			}
		} else {
			self::$_adapters[$type]->setPath($filePath);
		}

		$config = self::$_adapters[$type]->load();
		Core::log(sprintf('[Config] Successfully loaded %s config file', $file));
		
		/* if ckey is set, cache is activated but empty */
		if (isset($ckey)) {
			Core::cacheSet($config, $ckey, true, array('tags' => array('config')));
		}
		return $config;
	}
	
	
	/**
	 * Looks for the given file name in all declared paths in ordered list for the given realm
	 * Returns the full path of the matching files or null.
	 * @param string $file
	 * @param string|array $realm
	 * @return string
	 */		
	static public function findFile($file, $realm = Config::REALM_CONFIGS, $returnFirst = false)
	{
		$prefix = Config::DEFAULT_PREFIX;
		$files = array($prefix => array());

		$paths = is_array($realm) ? $realm : Config::getPaths($realm);
		foreach ($paths as $path) {
			if (strstr($path, Config::PREFIX_SEPARATOR) !== false) {
				list($path, $prefix) = explode(Config::PREFIX_SEPARATOR, $path);
				if (! isset($files[$prefix])) $files[$prefix] = array();
				
			} else {
				$prefix = '_';
			}
			
			$filePath = (substr($file, 0, 1) == DIRECTORY_SEPARATOR) ? $file : $path . $file;
		
			if (file_exists($filePath)) {
				if ($returnFirst) return $filePath;
				$files[$prefix][] = $filePath;
			}
		}
		return count($files) > 0 ? $files : false;
	}
	
	
	/**
	 * Build and return a unique cache key for the given file and realm
	 * @param string $file
	 * @param integer $realm
	 * @return string
	 */
	static public function getCacheKey($file, $realm)
	{
		return 'configs_' . str_replace(array('.','/'),'_', $file) . '_' . $realm;
	}
}
