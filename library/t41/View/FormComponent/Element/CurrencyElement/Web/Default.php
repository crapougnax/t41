<?php

require_once 't41/View/Web/Decorator.php';

class t41_Form_Element_Currency_Web_Default extends t41_View_Web_Decorator {

	
	public function __construct($obj, array $params = null)
	{
//		$this->_setParameterObjects(array('mode' => new t41_Parameter(), 'data' => new t41_Parameter(), 'pairs' => new t41_Parameter()));
		parent::__construct($obj, $params);
	}
	
	
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
		$max = $this->_obj->getValueConstraint('maxval');
		$html  = sprintf('<input type="text" name="%s" id="%s" size="%s" value="%s"%s/>'
							, $name
							, $name
							, ($max > 30 || $max == 0) ? 30 : $max
							, $this->_obj->getValue()
							, $extraArgs
						);
		
		return $html;
	}
}