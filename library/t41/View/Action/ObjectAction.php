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
	t41\ObjectModel\Property,
	t41\Core;

/**
 * Class handling remote actions on objects
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectAction extends AbstractAction {

	
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
	public function execute(array $data = array())
	{
		$res = false;
		
		switch ($this->_action) {
			
			case 'create':
				$res = $this->_obj->save();
				break;
				
			case 'delete':
				$res = $this->_obj->delete();
				break;
				
			case 'update':
				
				// test object uri, if empty, object is new or faulty
				
				// walk through POST data
				foreach ($data as $key => $val) {
					
					if (($property = $this->_obj->getProperty($key)) !== false) {
						
						if ($property instanceof Property\ObjectProperty) {
							
							$val = Core\Registry::get($val);
						
							if (is_object($val)) {
							
								$property->setValue($val);
							}
							
						} else if ($property instanceof Property\CollectionProperty) {
						
							$class = $property->getParameter('instanceof');
							$keyprop = $property->getParameter('keyprop');

							// val for a collection should come as an array of new members
							foreach ($val as $memberKey => $memberArray) {
							
								$member = new $class();
								
								// set keyprop property value
								$member->getProperty($member->getProperty($keyprop)->setValue($this->_obj));

								// walk through 
								foreach ($memberArray as $memberPropKey => $memberPropVal) {
									
									$mprop = $member->getProperty($memberPropKey);
									
									if ($mprop instanceof Property\ObjectProperty) {
											
										$memberPropVal = Core\Registry::get($memberPropVal);
									
										if ($memberPropVal instanceof Property\AbstractProperty) {

											$mprop->setValue($memberPropVal->getValue());
											
										} else {
											
											$mprop->setValue($memberPropVal);
										}
									} else {
										
										$mprop->setValue($memberPropVal);
									}							
								}
								
								$property->getValue()->addMember($member);
							}
							
						} else {
							
							$property->setValue($val);
						}
					}
				}
				$res = $this->_obj->save();
				break;
		}
		
		return $res;
	}

}
