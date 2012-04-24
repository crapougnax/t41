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
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 913 $
 */

use t41\Config;

/**
 * Class providing basic functions needed to manage Configuration files
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class Config {
	
	const PREFIX_SEPARATOR	= '|';
	
	
	const DEFAULT_PREFIX	= '_';
	
	
	const STORE_KEYS 		= 'keys';
	
	
	const POSITION_TOP		= 'top';
	
	
	const POSITION_BOTTOM	= 'bottom';
	
	
	const REALM_CONFIGS		= 1;
	
	
	const REALM_OBJECTS 	= 2;
	
	
	const REALM_TEMPLATES	= 4;
	
	
	const REALM_MODULES		= 8;
		
	
	const REALM_CONTROLLERS	= 16;
	
	
	/**
	 * Realms paths store
	 * @var array
	 */
	static protected $_paths = array(Config::REALM_CONFIGS		=> array()
								  ,  Config::REALM_OBJECTS		=> array()
								  ,  Config::REALM_TEMPLATES	=> array()
								  ,  Config::REALM_MODULES		=> array()
								  ,  Config::REALM_CONTROLLERS	=> array()
									);
	
		
	
	/**
	 * 
	 * Add a path where to look for *.xml config files, classes and templates
	 * @param string $path
	 * @param integer $realms
	 * @param string $position
	 */
	static public function addPath($path, $realms = null, $position = self::POSITION_BOTTOM, $prefix = null)
	{
		if (is_null($realms)) {
			
			throw new Config\Exception("Realms must be indicated");
		}
		
		if (substr($path, -1) != DIRECTORY_SEPARATOR) $path .= DIRECTORY_SEPARATOR;

		if (! is_null($prefix) && is_string($prefix)) $path .= self::PREFIX_SEPARATOR . $prefix;
		
		foreach (array_keys(self::$_paths) as $constant) {
			
			if (($constant & $realms) != 0) {
				
				switch ($position) {
			
					case self::POSITION_TOP:
						array_unshift(self::$_paths[$constant], $path);
						break;
				
					case self::POSITION_BOTTOM:
					default:
				
						self::$_paths[$constant][] = $path;
						break;
				}
			}
		}
	}


	/**
	 * Returns an array containing all declared search paths for the given realm of config files
	 * @param string $realm
	 * @return array
	 */
	static public function getPaths($realm = self::REALM_CONFIGS)
	{
		if (! is_int($realm)) {
			
			throw new Exception("INTEGER_EXPECTED");
		}
		
		return isset(self::$_paths[$realm]) ? self::$_paths[$realm] : false;
	}
}
