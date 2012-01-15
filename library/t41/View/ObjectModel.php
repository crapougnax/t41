<?php

namespace t41\View;

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
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 865 $
 */

use t41\ObjectModel;

/**
 * Deprecated class providing basic methods to every view-related object.
 *
 * @category   t41
 * @package    t41_View
 * @deprecated
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class ObjectModel extends ObjectModel\ObjectModelAbstract {
	
	
	protected $_title;
	
	
	protected $_content = array();
	
	
	protected $_decorator = array('name' => 'default');
	
	
	/**
	 * Class constructor. Accepts id value and array of parameters:
	 * <ul>
	 * <li>pos_x: X position of the object in the view. Unit depends on view type</li>
	 * <li>pos_y: Y position of the object in the view. Unit depends on view type</li></ul>
	 * 
	 * @param string $id
	 * @param array $params
	 */
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array('pos_x'	=> new \t41\Parameter(\t41\Parameter::INTEGER)
										, 'pos_y'	=> new \t41\Parameter(\t41\Parameter::INTEGER)
									));

		parent::__construct($id, $params);
	}
	
	
	public function setTitle($str)
	{
		$this->_title = $str;
		return $this;
	}
	
	
	public function getTitle()
	{
		return $this->_title;
	}
	
	
	/**
	 * 
	 * Add content to the view object
	 * @param mixed $str
	 * @return $this
	 */
	public function setContent($str)
	{
		$this->_content[] = $str;
		return $this;
	}
	
	
	public function getContent()
	{
		return $this->_content;
	}
	
	
	/**
	 * 
	 * Register the object in the view with the given placeholder and optional decorator parameters
	 * 
	 * @param string $placeHolder
	 * @param array $params
	 * @param boolean $clone
	 * @return boolean
	 */
	public function register($placeHolder = \t41\View::PH_DEFAULT, array $params = null, $clone = false)
	{
		$obj = $clone ? clone $this : $this;
		
		return \t41\View::addObject($obj, $placeHolder, $params);
	}
	
	
	/**
	 * Set which decorator to use with object
	 *
	 * @param string|array $decorator Decorator theme
	 * @param string $lib Alternative library where to find the decorator
	 * @param string $class use decorators of this class
	 * @param array $params decorator parameters
	 * @return t41_Object
	 */
	final public function setDecorator($decorator = 'Default', $lib = null, $class = null, array $params = array())
	{
		if (is_array($decorator)) {

			$this->_decorator = $decorator;
			
		} else {
			
			$this->_decorator['name']	= $decorator;
			$this->_decorator['lib'] 	= $lib ? $lib : null;
			$this->_decorator['class'] 	= $class ? $class : null;
			$this->_decorator['params'] = $params;
		}
		
		return $this;
	}
	
	
	public function getDecorator()
	{
		return $this->_decorator;
	}
}