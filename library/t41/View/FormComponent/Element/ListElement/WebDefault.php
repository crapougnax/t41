<?php

namespace t41\View\FormComponent\Element\ListElement;

use t41\ObjectModel,
	t41\View,
	t41\View\ViewUri,
	t41\View\FormComponent,
	t41\View\Action\AutocompleteAction,
	t41\View\Decorator\AbstractWebDecorator;

class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		$value = null;
		
		if ($this->_obj->getValue()) {
		
			$value = $this->_obj->getValue();
			if (($value instanceof ObjectModel\BaseObject || $value instanceof ObjectModel\DataObject) && $value->getUri()) {
					
				$value = $value->getUri()->getIdentifier();
				
			} else if ($value instanceof ObjectModel\ObjectUri) {
				
				$value = $value->getIdentifier();
			} else {
				
				$value = null;
			}
		}
		
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		
		if ($this->getParameter('mode') == View\FormComponent::SEARCH_MODE) {
			
			$name = ViewUri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
		}
		
		// display autocompleter field
		if ($this->_obj->getTotalValues() > $this->_obj->getParameter('select_max_values') 
				&& $this->getParameter('mode') != View\FormComponent::SEARCH_MODE) {

			View::addCoreLib('view:action:autocomplete.js');
			$acfield = new View\FormComponent\Element\FieldElement('_' . $name);
			//$acfield->setValue($this->_obj->getValue()->getDisplayValue());
			
			$action = new AutocompleteAction($this->_obj->getCollection());
			$action->setParameter('search', array('label'));
			$action->setParameter('display', array('type','label'));
			$action->setParameter('event', 'keyup');
			$action->setContextData('onclick', 't41.view.element.autocomplete.close');
			$action->setContextData('target', $this->_nametoDomId($name)); //$this->_obj->getId());
			$action->bind($acfield);
			
			$deco = View\Decorator::factory($acfield);
			$html = $deco->render();
			
			$deco = View\Decorator::factory($action);
			$deco->render();
			
			$html .= sprintf('<input type="hidden" name="%s" id="%s" value="%s"/>', $name, $this->_nametoDomId($name), $value);
			
			return $html . "\n";
			
		} else {
			
			// display menu list
			$zv = new \Zend_View();
			$options = array(null => '') + (array) $this->_obj->getEnumValues();

			return $zv->formSelect($name, $value, null, $options);
		}
	}
}
