<?php

namespace t41\View\Action;

use t41\ObjectModel\ObjectUri;

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

use t41\Core;

use t41\Core\Registry;

use t41\Backend\Condition;

use t41\Parameter;

use t41\ObjectModel\Property;

use t41\ObjectModel;

/**
 * Class providing an AJAX autocompletion controller.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class AutocompleteAction extends AbstractAction {

	
	const SEARCHMODE_CONTAINS = 'contains';
	
	const SEARCHMODE_BEGINS   = 'begins';
	
	
	protected $_id = 'action/autocomplete';
	
	
	protected $_callbacks = array();
	
	
	protected $_context = array('minChars' => 3, 'displayMode' => 'list');
	
	
	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41\ObjectModel\Collection';
	
	
	protected $_parsedDisplay;
	
	
	public $queryfield = '_query';
	
	
	public $queryidfield	= '_id';
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute($params = null)
	{
		if ((! isset($params[$this->queryfield]) || empty($params[$this->queryfield]))
		&& (! isset($params[$this->queryidfield]) || empty($params[$this->queryidfield]))) {
			return false;
		}
		
		if (isset($params['_offset'])) {
			$this->setParameter('offset', (int) $params['_offset']);
		}
		
		if (Core::getEnvData('cache_datasets') === true) {

			$md5 = md5(isset($params[$this->queryfield]) ? $params[$this->queryfield] : $params[$this->queryidfield]);
			//@todo check unicity, especially with hard-coded conditions having()
			$ckey = 'ds_ac_' . $this->_cachePrefix . '_' . $md5
				  . '_' . $this->getParameter('offset') . '_' . $this->getParameter('batch');
		
			if (($data = Core::cacheGet($ckey)) === false) {
				if (isset($params[$this->queryfield])) {
					$data = $this->_getSuggestions(trim($params[$this->queryfield]));
				} else {
					$data = $this->_getFromIdentifier(trim($params[$this->queryidfield]));
				}
				$data['cache-key'] = $ckey;
				Core::cacheSet($data,$ckey);
			}
			
		} else {
			
			if (isset($params[$this->queryfield])) {
				$data = $this->_getSuggestions(trim($params[$this->queryfield]));
			} else {
				$data = $this->_getFromIdentifier(trim($params[$this->queryidfield]));
			}
		}
		
		return $data;
	}
	
	
	/**
	 * Execute a find() call on the collection with the current query
	 * @param string $query
	 * @return array
	 */
	protected function _getSuggestions($query)
	{
		$data = array();
		
		$combo = $this->_obj->having(Condition::MODE_AND);
		
		foreach ($this->getParameter('searchprops') as $property) {
			
			switch ($this->getParameter('searchmode')) {
				
				case self::SEARCHMODE_BEGINS:
					$combo->having($property)->orMode()->beginsWith($query);
					break;
					
				case self::SEARCHMODE_CONTAINS:
				default:
					$combo->having($property)->orMode()->contains($query);
					break;
			} 
		}
		
		$this->_obj->setBoundaryOffset($this->getParameter('offset'));
		$this->_obj->setBoundaryBatch($this->getParameter('batch'));

//		$this->_obj->find(ObjectModel::MODEL);
//		var_dump(\t41\Backend::getLastQuery()); die;
		
		if ($this->_obj->find(ObjectModel::MODEL) === false) {
		//	return false;
		}
		
		foreach ($this->_obj->getMembers() as $member) {
			$data[$member->getUri()->getIdentifier()] = $member->reduce((array) $this->getParameter('member_reduce_params'));
		}

		return array('collection' => $data, 'max' => $this->_obj->getMax(), 'total' => $this->_obj->getTotalMembers());	
	}
	
	
	/**
	 * Execute a find() call on the collection with the current identifier
	 * @param string $query
	 * @return array
	 */
	protected function _getFromIdentifier($id)
	{
		$data = array();
	
		$this->_obj->having(ObjectUri::IDENTIFIER)->equals($id);	
		$this->_obj->setBoundaryOffset(0);
		$this->_obj->setBoundaryBatch(1);
			
		if ($this->_obj->find(ObjectModel::MODEL) === false) {
			return false;
		}
	
		//\Zend_Debug::dump(\t41\Backend::getLastQuery()); die;
		foreach ($this->_obj->getMembers() as $member) {
			$data[$member->getUri()->getIdentifier()] = $member->reduce((array) $this->getParameter('member_reduce_params'));
		}
	
		return array('collection' => $data, 'max' => $this->_obj->getMax(), 'total' => $this->_obj->getTotalMembers());
	}
	
	public function getDisplay()
	{
		if (! $this->_parsedDisplay) {
		
			// @todo get propertys from objects and collection properties
			$this->_parsedDisplay = array();
			
			/* @var $do t41\ObjectModel\DataObject */
			$do = $this->_obj->getDataObject();
			
			foreach ($this->getParameter('display') as $propId) {
		
				$property = $do->getRecursiveProperty($propId);
				if (! $property instanceof Property\AbstractProperty) {
					//\Zend_Debug::dump($propId);
					//\Zend_Debug::dump($property); die;
					continue;
				}
		
				if (strstr($propId, '.')) {
						
					$propId = '_' . $propId;
				}
		
				$this->_parsedDisplay[$propId] = $property->reduce();
			}
		}
		
		return $this->_parsedDisplay;
	}
	
	
	public function reduce(array $params = array())
	{
		$array = parent::reduce($params);
		$array['data']['display'] = $this->getDisplay();
		
		return $array;
	}
}
