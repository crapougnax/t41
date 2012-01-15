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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 885 $
 */

/**
 * Class providing basic functions needed to handle data mapping
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Mapper {
	
	
	/**
	 * Character used as a separator between values in a same string
	 * 
	 * @var string
	 */
	const VALUES_SEPARATOR = ',';
	
	
	/**
	 * Character used as a separator between arguments in a value
	 * 
	 * @var string
	 */
	const ARGS_SEPARATOR = ':';
	
	
	/**
	 * Array of mappers definitions
	 * 
	 * @var array
	 */
	static protected $_config = array();
	

	/**
	 * Mappers objects instances store
	 * 
	 * @var array
	 */
	static protected $_instances = array('backend' => array());
	
	
	/**
	 * Load a configuration file and add or replace content
	 * 
	 * @param string $file name of file to parse, file should be in application/configs folder
	 * @param boolean $add wether to add to (true) or replace (false) existing configuration data
	 * @return boolean true in case of success, false otherwise
	 */
	static public function loadConfig($file = 'mappers.xml', $add = true)
	{
		$config = Config\Loader::loadConfig($file);
		
		if ($config === false) {
			
			return false;
		}
		
		if ($add === false) {
        
			self::$_config = $config['mappers'];
		
		} else {
			
	        self::$_config = array_merge(self::$_config, $config['mappers']);
		}

	//	Zend_Debug::dump(self::$_config); die;
		
		return true;
	}
	
	
	static public function factory($id)
	{
		if (! array_key_exists($id, self::$_config)) {
			
			throw new ObjectModel\Exception(array('MAPPER_NO_DECLARATION', $id));
		}
		
		$class = 't41_' . ucfirst(strtolower(self::$_config[$id]['type'])) . '_Mapper';
		
		try {
			
			$obj = new $class(self::$_config[$id]);
			
		} catch (ObjectModel\Exception $e) {
			
		} catch (ObjectModel\DataObject\Exception $e) {
			
		}
		
		return $obj;
	}
	

	static public function getInstance($key, $type = 'backend')
	{
		if (isset(self::$_instances['backend'][$key])) {
			
			return self::$_instances['backend'][$key];
		
		} else {
			
			$mapper = self::factory($key);
			self::$_instances['backend'][$key] = $mapper;
			
			return $mapper;
		}
	}
}
