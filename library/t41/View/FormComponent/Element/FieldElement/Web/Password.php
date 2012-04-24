<?php

require_once 't41/View/Form/Element/Generic/Web/Default.php';

class t41_View_Form_Element_Generic_Web_Password extends t41_View_Form_Element_Generic_Web_Default {

	
	public function render()
	{
		$max = 0;
		$name =  $this->_obj->getAltId();
		$extraArgs = '';
		if ($this->getParameter('args')) {

			foreach ($this->getParameter('args') as $argKey => $argVal) {
				$extraArgs .= sprintf(' %s="%s"', $argKey, $argVal);
			}
		}
		$size = $this->_obj->getConstraint(t41_Property::CONSTRAINT_MINLENGTH) ? $this->_obj->getConstraint(t41_Property::CONSTRAINT_MINLENGTH) : 10;
		if ($this->_obj->getConstraint(t41_Property::CONSTRAINT_MAXLENGTH)) {
			
			$max = $this->_obj->getConstraint(t41_Property::CONSTRAINT_MAXLENGTH);
			$extraArgs .= sprintf(' maxlength="%d"', $max);
		}
		$html = sprintf('<input type="password" name="%s" id="%s" size="%s"%s/>'
						, $name
						, $name
						, ($max > $size || $max == 0) ? $size : $max
						, $extraArgs
					   );
		
		return $html;
	}
}