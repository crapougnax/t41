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
class Acl {


	const DENIED 	= 'denied';
	
	const GRANTED	= 'granted';
	
	
	
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
		if (Core::getEnvData('cache_configs') !== false) {

			$ckey = 'configs_acl';
			if (($cached = Core::cacheGet($ckey)) !== false) {
				self::$_config = $cached;
				return;
			}
		}
		
		// load application acl configuration file
		$config = Config\Loader::loadConfig('acl.xml', Config::REALM_CONFIGS);
		
		$resources = array();
		
		// add all fragments coming from modules
		foreach (Core\Module::getConfig() as $vendorId => $vendorModules) {
			
			foreach ($vendorModules as $key => $module) {

				// module menus
				if (isset($module['controller']) && isset($module['controller']['items'])) {
				
					// walk recursively through all module's items (menu elements)
					$resources += self::_getAcl($module['controller']['base'], $module['controller']['items']);
				}
	
				// and optional menus extensions
				if (isset($module['controllers_extends'])) {
					
					foreach ($module['controllers_extends'] as $controller => $data) {
						//\Zend_Debug::dump($controller); die;
						$resources += self::_getAcl($key, $data['items']);
					}
				}
			}
		}

		if (! isset($config['acl']['resources'])) $config['acl']['resources'] = array();
		$config['acl']['resources'] += $resources;
		
		self::$_config = $config['acl'];
		if (isset($ckey)) {
			Core::cacheSet($config['acl'], $ckey);
		}
		
	}
	
	
	
	public static function getRole($id)
	{
		if (isset(self::$_config['roles'][$id])) {
			
			$data = self::$_config['roles'][$id];
				
			$role = new Acl\Role($id);
			$role->setLabel($data['label']);
			
			return $role;
			
		} else {
			
			return false;
		}
	}
	
	
	public static function getGrantedResources($role, $sep = null)
	{
		if (! isset(self::$_config['roles'][$role])) {
			
			throw new \t41\Exception(array("UNKNOWN_ROLE", $role));
		}
		
		$keys = array_merge((array) $role, self::getRoleGroups($role));
		
		$resources = array();
		foreach (self::$_config['resources'] as $key => $config) {

			foreach ($keys as $k) {
				if (isset($config[$k]) && $config[$k] != self::DENIED) {
					$resources[] = $key;
					break;
				}
			}
		}
		
		// replace '/' with given separator
		if ($sep) {
			
			foreach ($resources as $key => $val) {
				
				$resources[$key] = str_replace('/', $sep, $val);
			}
		}
		
		return $resources;
	}
	
	
	public static function getRoleGroups($role)
	{
		$groups = array('all');
		
		foreach (self::$_config['roles'] as $key => $data) {
			
			if (isset($data['type']) && $data['type'] != 'group') continue;
			
			if (@array_key_exists($role, $data['members'])) {
				
				$groups[] = $key;
			}
		}
		
		return $groups;
	}
	
	
	/**
	 * Extract ACL definitions from a module configuration array
	 * @param string $key
	 * @param array $array
	 * @return array
	 */
	protected static function _getAcl($key, $array) 
	{
		$acl = array();
		
		foreach ($array as $path => $data) {
				
			if (isset($data['items'])) {
				
				$acl += self::_getAcl($key, $data['items']);
			
			} else {
				
				if (! isset($data['acl'])) $data['acl'] = array('all' => self::GRANTED);
				$acl[$key . '/' . $path] = $data['acl'];
			}
		}
		return $acl;
	}
}
