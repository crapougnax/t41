<?php

namespace t41\View\FormComponent\Element\ButtonElement;

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
 */

use	t41\View,
	t41\View\ViewUri,
	t41\ObjectModel\ObjectUri,
	t41\View\Decorator\AbstractWebDecorator;

/**
 * Default web decorator for a button element
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		// add CSS libraries
		View::addCoreLib('sprites.css');
		View::addCoreLib('buttons.css');
		
		$extraHtml = array();
		$class = array('element');
		if ($this->getParameter('nolabel') != true) $class[] = 'button';
		if ($this->getParameter('size')) $class[] = $this->getParameter('size');
		
		/* bind optional action to button */
		if ($this->_obj->getAction()) {
		
			$this->_bindAction($this->_obj->getAction());
			
		} else if ($this->_obj->getLink()) {
			
			$link = $this->_obj->getLink();

			$uri = $this->_obj->getParameter('uri');
			if ($uri instanceof ObjectUri && substr($link,0,1) == '/') $link .= '/id/' . rawurlencode($uri->getIdentifier());
			
			$extraHtml[] = sprintf("onclick=\"t41.view.link('%s', jQuery('#%s'))\"", $link, $this->getId());
			
		} else {
			
			$data = $this->getParameter('data');
			$adapter = ViewUri::getUriAdapter();
			$args = array();
			
			foreach ((array) $this->_obj->getParameter('identifiers') as $key => $identifier) {
				$identifierKey = is_numeric($key) ? $identifier : $key;
				$args[$identifierKey] = $data[$identifier];
			}
			$onclick  = "document.location='" . "/"; //$this->_obj->getUri();
			$onclick .= (count($args) > 0) ? $adapter->makeUri($args, true) . "'" : "'";
		}
		
		if ($this->_obj->getParameter('disabled')) {
			$class[] = 'disabled';
		}
		
		if ($this->getParameter('pairs')) {
			foreach ($this->getParameter('pairs') as $key => $val) {
				$extraHtml[] = sprintf('%s="%s"', $key, $val);
			}
		}
		
		if ($this->getParameter('icon')) {
			$class[] = 'icon';
		}			
		
		$value = $this->getParameter('nolabel') ? '' : $this->_escape($this->_obj->getTitle());
		
		foreach ((array) $this->getParameter('data') as $key => $val) {
			$extraHtml[] = sprintf('data-%s="%s"', $key, $val);
		}
					
		$html = sprintf('<a class="%s" id="%s" data-help="%s" %s><span class="%s"></span>%s</a>'
						, implode(' ', $class)
						, $this->_id
						, ''//$this->_escape($this->_obj->getHelp())
						, implode(' ', $extraHtml)
						, $this->getParameter('icon') ? $this->getParameter('icon') : null
						, $value
						);
		
		return $html;
	}
}
