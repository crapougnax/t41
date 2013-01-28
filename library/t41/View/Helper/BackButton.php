<?php

namespace t41\View\Helper;

use t41\View\ViewObject;
use t41\View\FormComponent\Element\ButtonElement;

class BackButton extends ViewObject {
	
	
	protected $_obj;
	
	
	public function __construct($id = 'back')
	{
		$this->_obj = new ButtonElement($id);
		$this->_obj->setTitle("Retour")
					->setHelp("Cliquez ici pour retourner à l'écran précédent")
					  ->setLink($_SERVER['HTTP_REFERER'])
					    ->setDecoratorParams(array('icon' => 'left-arrow-green'));
	}
	
	
	public function get()
	{
		return $this->_obj;
	}
}
