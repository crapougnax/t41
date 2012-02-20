<?php

namespace t41\Config\Adapter;

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
 * @version    $Revision: 832 $
 */

/**
 * Abstract class for Config Adapters
 * 
 * @abstract
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AdapterAbstract implements AdapterInterface {


	/**
	 *  Configuration file path
	 *
	 * @var array
	 */
	protected $_filePath;
	
	
	/**
	 * Array of configuration keys to consider
	 * 
	 * @var $_identifiers array
	 */
	protected $_identifiers = array('id', 'lang', 'alias', 'env', 'type', 'datastore', 'dataclass', 'pkey', 'default', 'backend', 'extends', 'mode', 'vendor');

	/**
	 * Constructor
	 * 
	 * @param array $params
	 */
	public function __construct(array $params = null)
	{   

		/**
		 * @todo implement $params parsing
		 */
	}
	
	
	/**
	 * @see t41\Config\Adapter.AdapterInterface::validate()
	 */
	public function validate()
	{
		return true;
	}
	
	
	/**
	 * Save an array into a configuration file
	 * 
	 * @param array $config	Configuration Array
	 * @param bool $add Add data on true, overwrite on false
	 */
	public function save(array $config, $add = true) {
		
	}
	
	
	/**
	 * Returns a type-casted version of a given value
	 * 
	 * @param mixed $value
	 * @return mixed
	 */
	protected function _castValue($value)
	{
		if (is_numeric($value) || $value == '0') {
					
			$value = (strpos($value, '.') !== false) ? (float) $value : (int) $value;

		} else if (in_array(strtolower($value), array('true', 'false'))) {
					
			$value = (strtolower($value) == 'true') ? true : false;
				
		} else {
					
			$value = (string) $value;
		}
		
		return $value;
	}
}
