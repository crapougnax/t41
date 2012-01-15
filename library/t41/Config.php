<?php
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

namespace t41;

use t41\Config;


/** Required files */
require_once 't41/Config/Adapter/AdapterInterface.php';

/**
 * Class providing basic functions needed to manage Configuration files
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class Config {
	
	
	const STORE_KEYS 		= 'keys';
	
	
	const POSITION_TOP		= 'top';
	
	
	const POSITION_BOTTOM	= 'bottom';
	
	
	const REALM_CONFIGS		= 1;
	
	
	const REALM_OBJECTS 	= 2;
	
	
	const REALM_TEMPLATES	= 4;
		
	
	static protected $_paths = array();
	
	
	/**
	 * 
	 * Add a path where to look for *.xml config files, classes and templates
	 * @param string $path
	 * @param integer $realms
	 * @param string $position
	 */
	static public function addPath($path, $realms = null, $position = self::POSITION_BOTTOM)
	{
		if (is_null($realms)) {
			
			//require_once 't41/Config/Exception.php';
			throw new Config\Exception("Realms must be indicated");
		}
		
		$constants = array(self::REALM_CONFIGS, self::REALM_OBJECTS, self::REALM_TEMPLATES);

		foreach ($constants as $constant) {
			
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
		return self::$_paths[$realm];
	}
}
