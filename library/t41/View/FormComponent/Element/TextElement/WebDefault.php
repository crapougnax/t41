<?php

namespace t41\View\FormComponent\Element\TextElement;

use t41\ObjectModel\Property;

use t41\ObjectModel,
t41\View,
t41\View\ViewUri,
t41\View\FormComponent,
t41\View\Decorator\AbstractWebDecorator;


class WebDefault extends AbstractWebDecorator {


	public function render()
	{
		$name =  $this->_obj->getAltId();
		
		$extraArgs = '';
		if ($this->getParameter('args')) {

			foreach ($this->getParameter('args') as $argKey => $argVal) {
				$extraArgs .= sprintf(' %s="%s"', $argKey, $argVal);
			}
		}
		$max = $this->_obj->getValueConstraint('maxval');
		$html  = sprintf('<textarea name="%s" cols="50" id="%s" rows="%s">%s</textarea>'
							, $name
							, $name
							, ($max > 10 || $max == 0) ? 10 : $max
							, $this->_obj->getValue()
						);
		
		$this->addConstraintObserver(array(Property::CONSTRAINT_UPPERCASE,Property::CONSTRAINT_LOWERCASE));
		
		return $html;
	}
}
