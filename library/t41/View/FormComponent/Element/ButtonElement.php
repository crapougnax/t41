<?php

namespace t41\View\FormComponent\Element;

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
 * @version    $Revision: 876 $
 */

use t41\Parameter,
	t41\Backend,
	t41\View,
	t41\View\Action;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ButtonElement extends AbstractElement {

	
	/**
	 * 
	 * t41_View_Action object associated with button
	 * @var \t41\View\Action\AbstractAction
	 */
	protected $_action;
	

	/**
	 * Temp property while we decide wether a simple link on a button should be in an action class
	 * @var string
	 */
	protected $_link;
	
	
	/**
	 * Embedded image in button
	 * @var t41_View_Image
	 */
	protected $_image;

	
	public function setLink($str)
	{
		$this->_link = $str;
		return $this;
	}
	
	
	public function getLink()
	{
		return $this->_link;
	}
	
	
	public function setSubmit()
	{
		return $this->setAction(new Action\FormComponent\SubmitAction());
	}
	
	
	public function setReset()
	{
		return $this->setAction(new Action\FormComponent\ResetAction());
	}
	
	
	public static function factory($label, $link, array $params = array('icon' => 'tool-blue'))
	{
		$button = new self();
		$button->setTitle($label);
		$button->setLink($link);
		$button->setDecoratorParams($params);
		return $button;
	}
}
