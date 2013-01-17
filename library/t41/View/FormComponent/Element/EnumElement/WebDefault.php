<?php

namespace t41\View\FormComponent\Element\EnumElement;

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

use t41\View,
	t41\View\ViewUri,
	t41\View\Decorator\AbstractWebDecorator;

/**
 * t41 default web decorator for enum elements
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
			$this->setParameter('radiomax',0);
		}

		if (count($this->_obj->getEnumValues()) > $this->getParameter('radiomax')) {
			// display menu list
			$options = array(null => $this->getParameter('defaultlabel')) + (array) $this->_obj->getEnumValues();
			$zv = new \Zend_View();
			return $zv->formSelect($name, $this->_obj->getValue(), null, $options);
		
		} else {
			$html = '';
			foreach ($this->_obj->getEnumValues() as $key => $val) {
				$html .= sprintf('<input type="radio" name="%s" id="%s" value="%s"%s/>&nbsp;%s '
								, $name
								, $name
								, $key
								, ($key == $this->_obj->getValue()) ? ' checked="checked"': null
								, $this->_escape($val)
								);
			}
			return $html;
		}
	}
}
