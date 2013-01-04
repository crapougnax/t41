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
	static protected $_modules;
	
	
	static protected $_config;
	
	
	/**
	 * Detect all modules directories and try to get config for them
	 * @param string $path
	 */
	public static function init($path)
	{
		self::$_path = $path . 'application/modules' . DIRECTORY_SEPARATOR;
		if (! is_dir(self::$_path)) return false;
		
		// get config from cache
		if (Core::getEnvData('cache_configs') == "bogus") {
		
			$ckey = 'configs_modules';
			if (($cached = Core::cacheGet($ckey)) !== false) {
				self::$_config = $cached;
			}
		}
		
		if (true) { //! self::$_config) {
		
			self::$_config = array();
			self::$_modules = array('enabled' => array(), 'disabled' => array());
		
			foreach (scandir(self::$_path) as $vendor) {
			
				if (is_dir(self::$_path . $vendor) && substr($vendor, 0, 1) != '.') {
		
					foreach (scandir(self::$_path . $vendor) as $entry) {
				
						$fpath = self::$_path . $vendor . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR;
						if (is_dir($fpath) && substr($entry, 0, 1) != '.') {
						
							if (is_dir($fpath . 'configs')) {
							
								// register path with $vendor as prefix
								Config::addPath($fpath . 'configs', Config::REALM_MODULES, null, $vendor);
							}
						}
					}
				}
			}

			// load all detected modules configuration file
			$config = Config\Loader::loadConfig('module.xml', Config::REALM_MODULES);
		
			// remove useless "modules" key
			foreach ($config as $key => $val) {
				self::$_config[$key] = $val['modules'];
			}
			
			// cache config if cache is enabled
			if (isset($ckey)) {
				Core::cacheSet(self::$_config, $ckey);
			}
		}
		
		unset($ckey);
		
		
		// build menu
		if (Core::getEnvData('cache_configs') == "bogus") {
			
			$ckey = 'configs_menu_main';
			if (($cached = Core::cacheGet($ckey)) !== false) {
				$menu = $cached;
			}	
		}
		
		if (true) { //! isset($menu)) {

			$menu = new Layout\Menu('main');
			$menu->setLabel('Main Menu');
		
			foreach (self::$_config as $prefix => $modules) {
			
				foreach ($modules as $key => $module) {
					
					if ($module['enabled'] != 'true') continue;
				
					$path = Core::$basePath . 'application/modules' . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $key;
					self::bind($module, $path);
				
					// if module has controllers, declare them to front controller
					// then add them to main menu
					if (isset($module['controller']) || isset($module['controllers_extends'])) {
						Config::addPath($path . '/controllers/', Config::REALM_CONTROLLERS, null, $module['controller']['base']);
					}
				
					if (isset($module['controller'])) {				
						$menu->addItem($module['controller']['base'], $module['controller']);
					}
				
					// if module extends existing controllers, declare them for inclusion at the end of the process
					if (isset($module['controllers_extends']) && ! empty($module['controllers_extends'])) {
						$menu->registerExtends($module['controller']['base'], $module['controllers_extends']);
					}
				}
			}
		
			// process extends declaration when menu is complete
			$menu->proceedExtends();
			
			if (isset($ckey)) {
				Core::cacheSet($menu, $ckey);
			}
		}
		
		Layout::addMenu('main', $menu);
	}
	
	
	public static function bind(array $config, $path, Layout\Menu $menu = null)
	{
		$store = ($config['enabled'] == true) ? 'enabled' : 'disabled';
		
		if ($store == 'disabled') {
			
			return;
		}
		
		Config::addPath($path . '/configs/', Config::REALM_CONFIGS);
		
		// if modules has model, declare path to the autoloader
		if (is_dir($path . '/models') && isset($config['namespace'])) {
			Core::addAutoloaderPrefix($config['namespace'], $path . '/models/');
			Config::addPath($path . '/models', Config::REALM_OBJECTS, null, $config['namespace']);
		}
	}
	
	
	public static function getConfig()
	{
		return self::$_config;
	}
}
