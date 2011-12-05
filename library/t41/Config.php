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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 913 $
 */

/** Required files */
require_once 't41/Config/Adapter/AdapterInterface.php';

/**
 * Class providing basic functions needed to manage Configuration files
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class Config {
	
	
	const STORE_KEYS = 'keys';
	
	
	const POSITION_TOP = 'top';
	
	
	const POSITION_BOTTOM = 'bottom';
	
	
	const REALM_CONFIGS	= 1;
	
	
	const REALM_OBJECTS = 2;
	
	
	const REALM_TEMPLATES = 4;
	
	
	static $_paths = array(self::REALM_CONFIGS => array()
						,  self::REALM_OBJECTS => array()
						, self::REALM_TEMPLATES => array()
						  );
	
	
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
		if (($filePath = self::_findFile($file)) === false) {
		
			/* no matching file name in paths */
			return false;
		}
		
		$type = substr( $file, strrpos($file, '.') + 1 );
		
		$className = 'Config\Adapter\\' . ucfirst(strtolower($type));
		
		try {

			require_once 't41/Config/Adapter/' . ucfirst(strtolower($type)) . '.php';
			
			$config = new $className($filePath, $params);
			
			if (! $config instanceof Config\Adapter\AdapterAbstract) {
				
				require_once 't41/Config/Exception.php';
				throw new Config\Exception("$className is not implementing AdapterAbstract.");
			}
			
			return $config->load();

		} catch (Exception $e) {
			
			require_once 't41/Config/Exception.php';
			throw new Config\Exception($e->getMessage());
			
		}
	}
	
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
			
			require_once 't41/Config/Exception.php';
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

	
	static public function getPaths($realm = self::REALM_CONFIGS)
	{
		return self::$_paths[$realm];
	}
	
	
	static protected function _findFile($file)
	{
		foreach (self::$_paths[self::REALM_CONFIGS] as $path) {
			
			$filePath = (substr($file, 0, 1) == DIRECTORY_SEPARATOR) ? $file : $path . $file;
			
			if (file_exists($filePath)) {
				
				return $filePath;
			}
		}
		
		return false;
	}
}