<?php

require_once 't41/Form/Element/Abstract.php';

class t41_Form_Element_Uri extends t41_Form_Element_Abstract {

	
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array('uritype' => new t41_Parameter( t41_Parameter::STRING // type
																		, null // default value
																		, true // protected
																		, array('email', 'url') // possible values
																		)
										 )
								   );
		
		parent::__construct($id, $params);
	}
}