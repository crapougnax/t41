<?php

namespace t41\View\FormComponent\Element\FieldElement;

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

use	t41\View;
use	t41\View\ViewUri;
use t41\View\Decorator\AbstractWebDecorator;

/**
 * Web decorator for the FieldElement class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebStreetNumber extends AbstractWebDecorator {

	
	public function render()
	{
		/* bind optional action to field */
		if ($this->_obj->getAction()) {
			$this->_bindAction($this->_obj->getAction());
			View::addEvent(sprintf("jQuery('#%s').focus()", $this->_id), 'js');
		}
		
		$name =  $this->getId();
		$value = explode('.', $this->_obj->getValue());
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $this->_nametoDomId($name) . ']';
		}

		$html  = sprintf('<input type="hidden" name="%s" id="%s" value="%s"/>'
							, $name
							, $this->_obj->getId()
							, $this->_obj->getValue()
						);
		$html .= sprintf('<input type="text" id="%s_number" size="3" maxlength="4"/>', $this->_obj->getId());
		
		$options = array('2' => 'Bis', '3' => 'Ter', '4' => 'Quater', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D');
		$options = array(null => $this->getParameter('defaultlabel')) + $options;
		$zv = new \Zend_View();
		$html .= $zv->formSelect(null, null, array('id' => $this->_obj->getId() . '_ext'), $options);

		View::addCoreLib('view:form.js');
		View::addEvent(sprintf("new t41.view.form.streetNumber('%s')", $this->_obj->getId()), 'js');
		return $html;
	}
}
