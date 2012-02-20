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

use t41\Config;
use t41\Config\Adapter;

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
	 * Realms paths store
	 * @var array
	 */
	static $_paths = array(Config::REALM_CONFIGS	=> array()
						,  Config::REALM_OBJECTS	=> array()
						,  Config::REALM_TEMPLATES	=> array()
						,  Config::REALM_MODULES	=> array()
						  );
	
	
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
	 * @param array $params
	 * 
	 * @return array|false Configuration Array or false if the file don't exists
	 */
	public static function loadConfig($file, array $params = null)
	{
		if (($filePath = self::findFile($file)) == null) {
		
			/* no matching file name in paths */
			return false;
		}
		
		$type = substr( $file, strrpos($file, '.') + 1 );
		
		/* use existing adapter instance or create it */
		if (! isset(self::$_adapters[$type])) {
		
			$className = sprintf('\t41\Config\Adapter\%sAdapter', ucfirst(strtolower($type)));
		
			try {

				self::$_adapters[$type] = new $className($params);
			
				if (! self::$_adapters[$type] instanceof Adapter\AdapterAbstract) {
				
					throw new Exception("$className is not implementing AdapterAbstract.");
				}			
			} catch (Exception $e) {
			
				throw new Exception($e->getMessage());
			}
		}

		return self::$_adapters[$type]->load($filePath);
	}
	
	
	/**
	 * Looks for the given file name in all declared paths in ordered list for the given realm
	 * Returns the full path of the first matching file or null.
	 * @param string $file
	 * @param string $realm
	 * @return string
	 */
	static public function findFile($file, $realm = \t41\Config::REALM_CONFIGS)
	{
		if (! in_array($realm, array_keys(self::$_paths))) {
			
			throw new Exception("Unrecognized realm value");
		}
		
		/* @todo implement persistent file path caching here */
		
		foreach (\t41\Config::getPaths($realm) as $path) {
			
			$filePath = (substr($file, 0, 1) == DIRECTORY_SEPARATOR) ? $file : $path . $file;
			if (file_exists($filePath)) {
				
				return $filePath;
			}
		}
		
		return null;
	}
}