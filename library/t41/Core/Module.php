<?php

namespace t41\Core;

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
 * @package    t41_Backend
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 961 $
 */

use t41\Config;

/**
 * Class managing modules
 *
 * @category   t41
 * @package    t41_Modules
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Module {


	/**
	 * Path to modules base directory
	 * @var string
	 */
	static protected $_path;
	
	/**
	 * Array of detected modules
	 * @var array
	 */
	static protected $_modules = array();
	

	/**
	 * Detect all modules directories and try to get config for them
	 * @param string $path
	 */
	public static function init($path)
	{
		// @todo implement cache mechanism here
		
		self::$_path = $path . 'modules' . DIRECTORY_SEPARATOR;
		if (! is_dir(self::$_path)) return false;
		
		foreach (scandir(self::$_path) as $vendor) {
			
			if (is_dir(self::$_path . $vendor) && substr($vendor, 0, 1) != '.') {
		
				foreach (scandir(self::$_path . $vendor) as $entry) {
				
					$fpath = self::$_path . $vendor . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR;
					if (is_dir($fpath) && substr($entry, 0, 1) != '.') {
						
						if (is_dir($fpath . 'configs')) {
							
							Config::addPath($fpath . 'configs', Config::REALM_MODULES);
						}
					}
				}
			}
		}
		
		// load all detected modules configuration file
		self::$_modules = Config::loadConfig('module.xml', Config::REALM_MODULES);
		
//		Zend_Debug::dump(self::$_modules); die;
	}
}
