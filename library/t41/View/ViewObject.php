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

use t41\Parameter,
	t41\View,
	t41\ObjectModel\ObjectModelAbstract;

/**
 * class providing basic methods to every view-related object.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class ViewObject extends ObjectModelAbstract {
	
	
	protected $_title;
	
	
	protected $_parent;
	
	
	protected $_help;
	
	
	protected $_content = array();
	
	
	protected $_decorator = array('name' => 'default');
	
	
	/**
	 * Set object title value
	 * @param string $str
	 * @return \t41\View\ViewObject
	 */
	public function setTitle($str)
	{
		$this->_title = $str;
		return $this;
	}
	
	
	/**
	 * Return object's title current value
	 * @return string
	 */
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
	
	
	/**
	 * Get registered content
	 * @return multitype:
	 */
	public function getContent()
	{
		return $this->_content;
	}
	

	/**
	 * Register the c
	 * @param ViewObject $parent
	 * @return \t41\View\Decorator\AbstractDecorator
	 */
	public function setParent(ViewObject $parent)
	{
		$this->_parent = $parent;
		return $this;
	}
	
	
	public function getParent()
	{
		return $this->_parent;
	}
	
	
	/**
	 * Sets a help text message
	 * @param string $str
	 */
	public function setHelp($str)
	{
		$this->_help = $str;
		return $this;
	}
	
	
	/**
	 * Get help text
	 * @return string
	 */
	public function getHelp()
	{
		return $this->_help;
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
	public function register($placeHolder = View::PH_DEFAULT, array $params = null, $clone = false)
	{
		$obj = $clone ? clone $this : $this;
		
		return View::addObject($obj, $placeHolder, $params);
	}
	
	
	/**
	 * Set which decorator to use with object
	 *
	 * @param string $decorator Decorator name. 
	 * 							If relative, will be resolved as {objectClass}\{ViewType}{DecoratorName}
	 * 							If value begins with a namespace separator, matching class will be called directly
	 * @param array $params decorator parameters
	 * @return t41\View\ViewObject
	 */
	final public function setDecorator($decorator = 'Default', array $params = array())
	{
		if (is_array($decorator)) {

			$this->_decorator = $decorator;
			
		} else {
			
			$this->_decorator['name']	= $decorator;
	//		$this->_decorator['lib'] 	= $lib ? $lib : null;
	//		$this->_decorator['class'] 	= $class ? $class : null;
			$this->_decorator['params'] = $params;
		}
		
		return $this;
	}
	
	
	/**
	 * Shortcut to define decorator parameters
	 * @param array $array
	 */
	public function setDecoratorParams(array $array)
	{
		$this->_decorator['params'] = $array;
		return $this;
	}
	
	
	/**
	 * Retrieve parameters that the selected decorator will receive upon instanciation
	 * @return array
	 */
	public function getDecoratorParams()
	{
		return isset($this->_decorator['params']) ? $this->_decorator['params'] : array();
	}
	
	
	public function getDecorator()
	{
		return $this->_decorator;
	}
}
