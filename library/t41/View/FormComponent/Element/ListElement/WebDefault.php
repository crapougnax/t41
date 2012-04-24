<?php

namespace t41\View\FormComponent\Element\ListElement;

use t41\View,
	t41\View\Decorator\AbstractWebDecorator;

class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		
//		if ($this->getParameter('mode') == t41_Form::SEARCH) {
//			
//			$name = t41_View_Uri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
//		}
		
		// display autocompleter field
		if ($this->_obj->getTotalValues() > $this->_obj->getParameter('select_max_values')) {
			
			//t41_Externals::enablejQueryUI();
			//t41_View::addRequiredLib('base', 'js', 't41');
			//t41_View::addRequiredLib('autocompleter', 'js', 't41');
			
			$key = $this->_obj->sessionize();
			//View::addEvent("new t41_autocompleter('$key')", 'js');
			
			$html  = sprintf('<input type="text" size="30" id="%s_input" value="%s"/>'
							, $key
							, $this->_obj->formatValue($this->_obj->getValue())
							);
			$html .= sprintf('<input type="hidden" name="%s" id="%s_hidden" value="%s"/>', $name, $key, $this->_obj->getValue());
							
			$html .= sprintf('<a id="%s_placeholder" style="display: none;" title="Cliquez pour &eacute;diter" class="input_placeholder"> </a>', $key);
			$html .= sprintf('<div class="suggestionsBox" id="%s_suggestions" style="display: none;">', $key);
			$html .= sprintf('<div class="suggestionList" id="%s_autoSuggestionsList"></div></div>', $key);
			
			return $html . "\n";
			
		} else {
			
			// display menu list
			$zv = new \Zend_View();
			$options = array(null => '') + (array) $this->_obj->getEnumValues();

			return $zv->formSelect($name, $this->_obj->getValue(), null, $options);
		}
	}
}