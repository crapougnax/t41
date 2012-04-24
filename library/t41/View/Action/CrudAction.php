<?php

namespace t41\View\Action;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.t41.org/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@t41.org so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

use t41\View\Action\AbstractAction,
	t41\ObjectModel,
	t41\ObjectModel\Property;

/**
 * Class providing an AJAX form controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class CrudAction extends AbstractAction {

	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41\ObjectModel\BaseObject';

	
	protected $_obj;
	
	
	protected $_id = 'action';
	
	
	public function setClass($class)
	{
		$this->_class = $class;
		return $this;
	}
	
	
	public function getClass()
	{
		return $this->_class;
	}

	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute()
	{
		$res = false;
		
		switch ($this->_callback) {
			
			case 'create':
				
				$this->_obj = ObjectModel::factory($this->getClass());
				foreach ($this->getContext() as $key => $value) {
					
					if ($this->_obj->setProperty($key, $value) === false) {
						
						var_dump($key); die;
						continue;
					}
				}
				
				$res = $this->_obj->save();
				
				break;
		}
		
		return $res;
	}
	
	
	public function getObject()
	{
		return $this->_obj;
	}
}
