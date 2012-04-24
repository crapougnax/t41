<?php

namespace t41\View\FormComponent\Element\EnumElement;


use t41\View\Decorator\AbstractWebDecorator;


class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		
/*		if ($this->getParameter('mode') == t41_Form::SEARCH) {
			
			$name = t41_View_Uri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
		}
*/
		
		$zv = new \Zend_View();
		
		if (count($this->_obj->getEnumValues()) > 3) {
			
			// display menu list
			$options = array(null => '') + (array) $this->_obj->getEnumValues();

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