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

use t41\Core,
	t41\Config;

/**
 * Class managing menus
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Layout {


	static protected $_menus = array();
	
	static public $module;
	
	static public $controller;
	
	static public $action;
	
	
	public static function addMenu($id, Layout\Menu $menu)
	{
		self::$_menus[$id] = $menu;
	}
	
	
	public static function getMenu($id = null)
	{
//		\Zend_Debug::dump(self::$_menus);
		return is_null($id) ? self::$_menus : self::$_menus[$id];
	}
	
	
	public static function getCurrentMenu()
	{
		return self::getMenu(self::$module);
	}
}
