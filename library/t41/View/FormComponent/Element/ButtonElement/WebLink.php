<?php

require_once 't41/View/Web/Decorator.php';

class t41_Form_Element_Button_Web_Link extends t41_View_Web_Decorator {

	
	public function __construct($obj, array $params = null)
	{
		$this->_setParameterObjects(array('mode'	=> new t41_Parameter()
										, 'data'	=> new t41_Parameter()
										, 'pairs'	=> new t41_Parameter()
										 )
									);
		parent::__construct($obj, $params);
	}
	
	
	public function render()
	{
		if ($this->_obj->getEvent()) {
			
			$onclick = $this->_obj->getEvent();
			
		} else {
			
			$data = $this->getParameter('data');
			$adapter = t41_View_Uri::getUriAdapter();
			
			$args = array();
			
			foreach ((array) $this->_obj->getParameter('identifiers') as $key => $identifier) {
				
				$identifierKey = is_numeric($key) ? $identifier : $key;
				$args[$identifierKey] = $data[$identifier];
			}
			
			if ($this->_obj->getUri()) {

				$onclick  = $this->_obj->getUri();
				$onclick .= (count($args) > 0) ? $adapter->makeUri($args, true) : "";
			}
		}
		
		$extraHtml = '';
		
		if ($onclick) $onclick = sprintf('href="%s"', $onclick);
		
		if ($this->getParameter('pairs')) {
			
			foreach ($this->getParameter('pairs') as $key => $val) {
				
				$extraHtml .= sprintf('%s="%s" ', $key, $val);
			}
		}
		
		$html = sprintf('<a%s%s>%s</a>'
						, isset($onclick) ? " " . $onclick : null
						, " " . $extraHtml
						, htmlentities($this->_obj->getLabel())
						); 
		
		return $html . "\n";
	}
}