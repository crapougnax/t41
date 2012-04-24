<?php

namespace t41\ObjectModel\Rule;

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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\ObjectModel,
	t41\ObjectModel\Property;

/**
 * Abstract abstract class
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractRule extends ObjectModel\ObjectModelAbstract implements RuleInterface {


	/**
	 * Reference to BaseObject-derived instance
	 * @var t41\ObjectModel\BaseObject
	 */
	protected $_object;
	
	
	protected $_source;
	
	
	protected $_destination;

	
	public function __construct(array $params = null)
	{
		/* deal with class parameters first */
		$this->_setParameterObjects();
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
	}
	
	
	public function setObject(ObjectModel\BaseObject $object)
	{
		$this->_object = $object;
		return $this;
	}
	
	
	public function setSource($array)
	{
		$this->_source = $this->_setElement($array);
		return $this;
	}
	

	public function setDestination($array)
	{
		$this->_destination = $this->_setElement($array);
		return $this;
	}
	

	protected function _setElement(array $array)
	{
		$element = new RuleElement();
		
		if (isset($array['argument'])) {
			
			$element->setArgument($array['argument']);
		}
		
		if (isset($array['property'])) {
					
			$element->setType(RuleElement::TYPE_PROPERTY)->setValue($array['property']);
					
		} else if (isset($array['method'])) {
		
			$element->setType(RuleElement::TYPE_METHOD)->setValue($array['method']);
		}
		
		return $element;
	}
	
	
	public function execute(Property\AbstractProperty $property)
	{
		return true;
	}
}
