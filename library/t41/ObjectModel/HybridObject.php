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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2013 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */


/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_ObjectModel
 * @copyright  Copyright (c) 2006-2013 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class HybridObject extends BaseObject {

	
	public function __construct($val = null, array $params = null)
	{
		$this->_setParameterObjects();
	
		if (is_array($params)) {
			$this->_setParameters($params);
		}
		
		$this->_dataObject = new DataObject('t41\ObjectModel\HybridObject');
	}
	
	
	public function mergeObject(BaseObject $source, array $properties = array())
	{
		foreach ($source->getDataObject()->getProperties() as $key => $property) {
			if (count($properties) > 0 && ! in_array($key, $properties)) {
				continue;
			} 
			$this->_dataObject->addProperty($property);
		}
	}
}
