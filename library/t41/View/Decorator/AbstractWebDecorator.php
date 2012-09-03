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

use t41\Core,
	t41\View,
	t41\View\Decorator,
	t41\View\Decorator\AbstractDecorator;

/**
 * Abstract class providing basic methods and parameters
 * for decorators in a Abstract context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractWebDecorator extends AbstractDecorator {

	
	/**
	 * Constructors, calls parent and add CSS librairies
	 * @param t41\View\ViewObject $obj
	 * @param array $params
	 */
	public function __construct($obj, array $params = null)
	{
		parent::__construct($obj, $params);
		View::addCoreLib('style.css');
	}
	
	
	public function render()
	{
		return $this->_headerRendering() . $this->_contentRendering() . $this->_footerRendering();
	}

	
	protected function _headerRendering()
	{
		return '';
	}
	
	
	protected function _footerRendering()
	{
		return '';
	}

	
	/**
	 * return an html-escaped version of the given string
	 * 
	 * @todo handle charset as an option
	 * @param string $str
	 * @param string charset
	 * @return string
	 */
	protected function _escape($str, $charset = 'utf-8')
	{
		return htmlentities($str, ENT_QUOTES, $charset);
	}
	
	
	protected function _nametoDomId($str)
	{
		return str_replace(array('[',']'), '_', $str);
	}
	
	/**
	 * Bind action to element in view
	 * 
	 * @param View\Action\AbstractAction $action
	 * @param string $callback
	 */
	protected function _bindAction(View\Action\AbstractAction $action, $callback = null)
	{
		// prepare extra data (provided callback would replace default callback)
		$data = array('element' => $this->getId());
		if ($callback) $data['callback'] = $callback;
		
		// reduce action
		$reduced = $action->reduce(array('extra' => $data));
		
		View::addEvent(sprintf('t41.view.bind(%s)', \Zend_Json::encode($reduced)), 'js');
	}
}
