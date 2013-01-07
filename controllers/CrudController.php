<?php
use t41\View\FormComponent;

use t41\ObjectModel\ObjectUri;

use t41\ObjectModel;

use t41\View;

use t41\View\ListComponent;

use t41\ObjectModel\Collection;

require_once 'DefaultController.php';

abstract class t41_CrudController extends t41_DefaultController {


	protected $_class;
	
	
	public function init()
	{
		parent::init();
		View::addRequiredLib('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js', 'js');
		View::addCoreLib('core.js');
	}
	
	
	public function indexAction()
	{
		
	}

	
	public function readAction()
	{
		$collection = new Collection($this->_class);
		
		if ($this->_getParam('id')) {
			$collection->having(ObjectUri::IDENTIFIER)->equals($this->_getParam('id'));
			$collection->setBoundaryBatch(1)->find();
			if ($collection->getTotalMembers() == 1) {
				$form = new FormComponent($collection->getMember(Collection::POS_FIRST));
				$form->register();
			}
		} else {
			
			$list = new ListComponent($collection);
			$list->addRowAction($_SERVER['REQUEST_URI'], 'Read', array('icon' => 'tool-blue'));
			$list->register();
		}
	}
}

