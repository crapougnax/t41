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

class Controller {
	
	
	static protected $_instance;
	
	static protected $_routes = array();

	
	static public function init()
	{
		self::$_instance = \Zend\Controller\Front::getInstance();
		
		foreach (Config::getPaths(Config::REALM_CONTROLLERS) as $controller) {
			
			list($path, $prefix) = explode(Config::PREFIX_SEPARATOR, $controller);
			self::$_routes[$prefix] = $path;
		}
		
		self::$_instance->setControllerDirectory(self::$_routes);
	}
	
	static public function dispatch()
	{
		self::$_instance->dispatch();	
	}
}
