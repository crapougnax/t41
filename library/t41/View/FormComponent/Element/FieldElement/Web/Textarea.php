<?php

require_once 't41/View/Web/Decorator.php';

class t41_View_Form_Element_Generic_Web_Textarea extends t41_View_Form_Element_Generic_Web_Default {

	public function render()
	{
		$name =  $this->_obj->getAltId();
		if ($this->getParameter('mode') == t41_Form::SEARCH) {

			$name = t41_View_Uri::getUriAdapter()->getIdentifier('search') . '[' . $name . ']';
		}
		
		$extraArgs = '';
		if ($this->getParameter('args')) {

			foreach ($this->getParameter('args') as $argKey => $argVal) {
				$extraArgs .= sprintf(' %s="%s"', $argKey, $argVal);
			}
		}
		$size = ($this->getParameter('mode') == t41_Form::SEARCH) ? round(t41_View_Web_Decorator::FIELD_SIZE/2) : t41_View_Web_Decorator::FIELD_SIZE;
		$max = $this->_obj->getValueConstraint('maxval');
		$html  = sprintf('<textarea name="%s" id="%s" rows="5" cols="%s"%s>%s</textarea>'
							, $name
							, $name
							, ($max > $size || $max == 0) ? $size : $max
							, $extraArgs
							, $this->_obj->getValue()
						);
		
		return $html;
	}
}