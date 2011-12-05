<?php

namespace t41\ObjectModel;

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
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

/**
 * Class providing basic functions for Data Objects
 *
 * @category   t41
 * @package    t41_Data
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Data {
	

	/**
	 * Factory pattern use to instanciate a new data object for given class name
	 * 
	 * @param string $class
	 * @return t41_Data_Object
	 * @throws t41_Data_Exception
	 */
	static public function factory($class)
	{		
		require_once 't41/Data/Object.php';
		
		try {
			
			$do = new DataObject($class);
		
		} catch (Property\Exception $e) {
			
			throw new DataObject\Exception(array("PROPERTY_ERROR", $e->getMessage()));
		}
			
		return $do;
	}
}