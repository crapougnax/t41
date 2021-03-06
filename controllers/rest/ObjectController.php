<?php

use t41\Core;
use t41\Core\Registry;
use t41\ObjectModel;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\Property;
use t41\ObjectModel\Collection;
use t41\Backend;
use t41\View\Action;
use t41\Core\Status;


require_once 'DefaultController.php';

class Rest_ObjectController extends Rest_DefaultController {

	
	public function updateAction()
	{
		try {
			// populate status message if provided
			if (isset($this->_post['_status'])) {
				$this->_obj->status = new Status($this->_post['_status']);
			}
			
			// test object uri, if empty, object is new or faulty
			// @todo mix this with ObjectModel::populate()
				
				// walk through POST data
				foreach ($this->_post as $key => $val) {

					if (($property = $this->_obj->getProperty($key)) !== false) {
						if ($property instanceof Property\ObjectProperty) {
								
							if ($val) {
								$class = $property->getParameter('instanceof');
								if (substr($val,0,4) == 'obj_') {
									// get object from cache
									$property->setValue(Core::cacheGet($val));
								} else {
									$property->setValue(new $class($val));
								}
							} else {
								$property->resetValue();
							}
							
						} else if ($property instanceof Property\CollectionProperty) {
							$class = $property->getParameter('instanceof');
							$keyprop = $property->getParameter('keyprop');
				
							// val for a collection should come as an array of new/existing members
							foreach ($val as $memberKey => $memberArray) {
								if (! is_numeric($memberKey)) {
									$this->_status = "NOK";
									$this->_context['message'] = 'member-id is not a number';
									return false;									
								}
								
								// action exists to update or remove member
								if (isset($memberArray['action'])) {
								    
								    // ensure thaa the received reference matches a fresh version of the collection
								    $property->getValue()->find();
								    
									// get target member
									$object = $property->getValue()->getMember($memberKey, ObjectModel::MODEL);
										
									switch ($memberArray['action']) {
										
										case 'delete':
											if ($property->setValue($object, Collection::MEMBER_REMOVE) !== true) {
												$this->_status = "NOK";
												$this->_context['message'] = 'error removing member from collection';
												return false;
											}
											break;
											
										case 'update':
											foreach ($memberArray['props'] as $mApropN => $mApropV) {
												if (($mAprop = $object->getProperty($mApropN)) !== false) {
													$mAprop->setValue($mApropV);
												}
											}
											// direct update of the member
											$object->save();
											break;
									}
								} else {
									
									// no action, default is new member to add
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
				
									// check if member was added successfully, break otherwise
									if ($property->setValue($member) === false) {
										$this->_status = "NOK";
										$this->_context['message'] = 'error adding member to collection';
										break;
									}
								}
							}	
						} else {
							$property->setValue($val);
						}
					}
				}

				// if record has no uri yet and an identifier value is present, inject it so backend will use it as primary key 
				if (! $this->_obj->getUri() && isset($this->_post[ObjectUri::IDENTIFIER])) {
					$this->_obj->setUri($this->_post[ObjectUri::IDENTIFIER]);
				}
				
				$result = $this->_obj->save();
	
				if ($result === false) {
					$this->_context['message'] = Backend::getLastQuery();
					$this->_status = 'NOK';
				} else {
					$collections = isset($this->_post['_collections']) ? $this->_post['_collections'] : 1;
					$extprops = isset($this->_post['_extprops']) ? $this->_post['_extprops'] : [];
					$this->_data['object'] = $this->_obj->reduce(array('params' => array(), 'extprops' => $extprops, 'collections' => $collections));
					$this->_executeActions('ok');
					$this->_refreshCache = true;
				}
			} catch (\Exception $e) {
				$this->_context['err'] = $e->getMessage();
				if (Core::$env == Core::ENV_DEV) $this->_context['trace'] = $e->getTraceAsString();
				$this->_status = 'ERR';
			}
	}
	
	
	/**
	 * Send an up-to-date version of the action-bound object
	 */
	public function refreshAction()
	{
		// @deprecated but kept for compatibility reason
		$collections = isset($this->_post['collections']) ? $this->_post['collections'] : 1;
		$collections = isset($this->_post['_collections']) ? $this->_post['_collections'] : $collections;
		$extprops = isset($this->_post['_extprops']) ? $this->_post['_extprops'] : true;
		
		$this->_data['object'] = $this->_obj->reduce(array('params' => array(), 'extprops' => $extprops, 'collections' => $collections));
		$this->_executeActions('ok');
		$this->_refreshCache = true;		
	}
	
	
	public function createAction()
	{
		try {
			$result = $this->_obj->execute();
		
			if ($result === false) {
				$this->_status = 'NOK';
			} else {
				$this->_data['object'] = $this->_obj->getObject()->reduce(array('params' => array()));
			}
		} catch (\Exception $e) {
			$this->_context['message'] = $e->getMessage();
			$this->_status = 'ERR';
		}
	}

	
	public function readAction()
	{
		try {
			$res = $this->_obj->read();
				
		} catch (\Exception $e) {
			$this->_context['message'] = $e->getMessage();
			$this->_status = 'ERR';
		}
	
		if ($this->_obj->status instanceof Core\Status) {
			$this->_context['message'] = $this->_obj->status->getMessage();
			$this->_context['context'] = $this->_obj->status->getContext();
		}
		$this->_status = $res ? 'OK' : 'NOK';
	}

	
	public function deleteAction()
	{
		try {
			$res = $this->_obj->delete();
			
		} catch (\Exception $e) {
			
			$this->_context['err'] = $e->getMessage();
			$this->_status = 'ERR';
		}
		
		if ($this->_obj->status instanceof Core\Status) {
			
			$this->_context['message'] = $this->_obj->status->getMessage();
			$this->_context['context'] = $this->_obj->status->getContext();
		}
		
		$this->_status = $res ? 'OK' : 'NOK';
	}
	
	
	/**
	 * returns a collection of objects matching the dependency parameter
	 */
	public function dependAction()
	{
		if (($property = $this->_obj->getProperty($this->_post['destProperty']['id'])) !== false) {			
			$collection = new Collection($property->getParameter('instanceof'));
			foreach ($this->_post['srcProperty'] as $key => $val) {
				$collection->having($key)->equals($val);
			}
			$collection->setBoundaryBatch(50000);
			$collection->find(ObjectModel::MODEL);
			
			$data = array();
			foreach ($collection->getMembers() as $member) {
				$reduced = $member->reduce();
				$data[$member->getUri()->getIdentifier()] = $reduced;
				if ($property->getValue() && $property->getValue()->getIdentifier() == $member->getIdentifier()) {
					$this->_data['value'] = $reduced['uuid'];
				}
			}
			
			$this->_data['total'] = $collection->getTotalMembers();
			$this->_data['collection'] = $data;
		} else {
			$this->_status = 'NOK';
		}
	}
	
	
	/**
	 * Returns the value of the given property
	 * 
	 */
	public function getAction()
	{
		try {
			$res = $this->_obj->getProperty($this->_post['property']);
			$res->getValue();
			
			// if the save flag is defined, force object saving
			if (isset($this->_post['save'])) {
				
				Backend::save($res->getParent());
				$this->_refreshCache = true;
			}
			
			$this->_data = $res->reduce(array('params' => array()));
			
		} catch (\Exception $e) {
	
			$this->_context['message'] = $e->getMessage();
			$this->_status = 'ERR';
		}
	
		if ($this->_obj->status instanceof Core\Status) {
			$this->_context['message'] = $this->_obj->status->getMessage();
			$this->_context['context'] = $this->_obj->status->getContext();
		}
		$this->_status = $res ? 'OK' : 'NOK';
	}
	
	
	public function execAction()
	{
		try {
			$res = $this->_obj->{$this->_post['method']}($this->_post);
			$this->_data = $res->reduce(array('params' => array()));
			
		} catch (\Exception $e) {
		
			$this->_context['message'] = $e->getMessage();
			$this->_status = 'ERR';
		}
		
		if ($this->_obj->status instanceof Core\Status) {
			$this->_context['message'] = $this->_obj->status->getMessage();
			$this->_context['context'] = $this->_obj->status->getContext();
		}
		$this->_status = $res ? 'OK' : 'NOK';		
	}
	
	
	protected function _executeActions($which)
	{
		$res = true;
		try {
			foreach ($this->_actions[$which] as $action) {
			
				$res = $res && call_user_func($action, $this->_obj, $this->_post);
			}
		} catch (Exception $e) {
			
			$this->_status = 'ERR';
			$this->_context['message'] = $e->getMessage();
			return false;
		}
		
		return $res;
	}
}
