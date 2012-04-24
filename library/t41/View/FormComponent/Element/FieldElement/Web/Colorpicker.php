<?php

require_once 't41/View/Web/Decorator.php';

class t41_View_Form_Element_Generic_Web_Colorpicker extends t41_View_Form_Element_Generic_Web_Default {

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
		
		t41_View::addRequiredLib('jquery-excolor/jquery.modcoder.excolor', 'js', 'externals');
		t41_View::addEvent(sprintf('jQuery("#%s").modcoder_excolor({hue_bar:1})', $name), 'js');
		
		$html  = sprintf('<input type="text" name="%s" id="%s" style="width:60px" value="%s"%s/>'
							, $name
							, $name
							, $this->_obj->getValue()
							, $extraArgs
						);
		
		return $html;
	}
}