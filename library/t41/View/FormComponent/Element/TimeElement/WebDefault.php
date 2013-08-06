<?php

namespace t41\View\FormComponent\Element\TimeElement;

use t41\ObjectModel\Property;

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

use t41\View\Decorator\AbstractWebDecorator,
	t41\View,
	t41\View\ViewUri;
use t41\ObjectModel\Property\TimeProperty;

/**
 * Web decorator for the FieldElement class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		/* bind optional action to field */
		if ($this->_obj->getAction()) {

			$this->_bindAction($this->_obj->getAction());
			View::addEvent(sprintf("jQuery('#%s').focus()", $this->_id), 'js');
		}
		
		$name = $this->getId();
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $this->_nametoDomId($name) . ']';
		}


		$html  = sprintf('<input type="hidden" name="%s" id="%s" value="%s"/>'
				, $name
				, $this->_obj->getId()
				, $this->_obj->getValue()
		);
		
		$zv = new \Zend_View();
		$options = array(null => $this->getParameter('defaultlabel')) + $this->_obj->getEnumValues(TimeProperty::HOUR_PART);
		$html .= $zv->formSelect($name . '_hour', $this->_obj->getValue(TimeProperty::HOUR_PART), null, $options);
		$options = array(null => $this->getParameter('defaultlabel')) + $this->_obj->getEnumValues(TimeProperty::MIN_PART);
		$html .= ' : ' . $zv->formSelect($name . '_minute', $this->_obj->getValue(TimeProperty::MIN_PART), null, $options);		

		View::addCoreLib('view:form.js');
		View::addEvent(sprintf("new t41.view.form.timeElement('%s')", $this->_obj->getId()), 'js');
		
		return $html;
	}
}
