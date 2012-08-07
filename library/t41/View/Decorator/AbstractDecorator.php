<?php

namespace t41\View\Decorator;

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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 876 $
 */

use t41\View,
	t41\View\Decorator,
	t41\ObjectModel\ObjectModelAbstract;

/**
 * Abstract class providing basic methods and parameters
 * for decorators in a Abstract context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractDecorator extends ObjectModelAbstract {


	/**
	 * View object
	 *
	 * @var t41_View_Object
	 */
	protected $_obj;

	
	protected $_instanceof = 't41\View\ViewObject';
	
	
	public function __construct($obj, array $params = null)
	{
		if (! $obj instanceof $this->_instanceof) {
			throw new \Exception("Decorator " . get_class($this) . " only accepts instances of " . $this->_instanceof);
		}
		
		$this->_obj = $obj;
		$this->_setParameterObjects();
		$this->setId($this->_obj->getId() ? $this->_obj->getId() : 't41_' . substr(md5(microtime()), 0, 10));
		
		if (is_array($params)) {
			
			$this->_setParameters($params);
		}
	}
	
	
	public function render()
	{
		return '';
	}
	
	
	protected function _contentRendering()
	{
		$content = '';
	
		foreach ($this->_obj->getContent() as $elem) {
	
			if ($elem instanceof View\ViewObject) {
	
				try {
						
					$deco = Decorator::factory($elem);
						
				} catch (View\Exception $e) {
						
					$decoratorClassBase = get_class($elem) . '\\' . View::getContext();
						
					try {
	
						// get decorator data from object
						$objDecorator = $this->_obj->getDecorator();
	
						// Current class object decorator
						$decoratorClass = $decoratorClassBase .  ucfirst($objDecorator['name']);
						$deco = new $decoratorClass($elem);
							
					} catch (View\Exception $e) {
							
						// Default decorator
						$decoratorClass = $decoratorClassBase . 'Default';
						$deco = new $decoratorClass($elem);
					}
				}
	
				if ($deco instanceof self) {
						
					$content .= $deco->render();
				}
	
			} else {
	
				$content .= $elem;
			}
		}
	
		return $content;
	}
	
	
    /**
     * Returns an id to use within HTML markup
     * 
     * @return string $id
     */
    public function getAltId($str = null)
    {
    	if (!is_null($this->_cssStyle)) {
    		return $this->_cssStyle . '_' . $this->_obj->getId();
    	} else {
    		return __CLASS__ . '_' . $this->_obj->getId();
    	}
    }
    
    /**
     * Returns current theme from decorator parameter or
     * the one defined by the view, and requires that file
     * to be called in the rendered view
     * 
     * @param string $view
     * @return string
     */
    protected function _getTheme($view = 'all')
    {
    	if ($this->getParameter('theme')) {
			View::addRequiredLib($this->getParameter('theme'), 'css', 't41');
			return $this->getParameter('theme');
		} else {
			View::addRequiredLib(View::getTheme($view), 'css', 't41');
			return View::getTheme($view);
		}
    }
	
    /**
     * Returns current color from decorator parameter or
     * the one defined by the view, and requires that file
     * to be called in the rendered view
     * 
     * @param string $view
     * @return string
     */
    protected function _getColor($view = 'all')
    {
    	if ($this->_obj->getParameter('color')) {
    		
			View::addRequiredLib($this->_obj->getParameter('color'), 'css', 't41');
			return $this->_obj->getParameter('color');
		
    	} else {
    		
			return View::getColor($view);
		}
    }
	
    
	protected function _formatValue($field, $value)
	{
		if ($value instanceof View\ObjectModel) {
			
			$deco = View\Decorator::factory($value);
			return $deco->render();
		
		} else {
			
			return nl2br(htmlentities($field->formatValue($value)));
		}
	}
}
