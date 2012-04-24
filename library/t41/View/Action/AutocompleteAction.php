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

	
	protected $_id = 'action/autocomplete';
	
	
	protected $_callback = 'showProps';
	
	
	protected $_context = array('minChars' => 3);
	
	
	/**
	 * Object class
	 *
	 * @var string
	 */
	protected $_objClass = 't41\ObjectModel\Collection';
	
	
	protected $_parsedDisplay;
	
	
	/**
	 * Execute the action and returns a result
	 *
	 * @return array
	 */
	public function execute(array $params = array())
	{
		if (! isset($params['query']) || empty($params['query'])) {
			
			return false;
		}
		
		if (! $this->_parsedDisplay) {

			// @todo get propertys from objects and collection properties 
			$this->_parsedDisplay = array();
			foreach ($this->getParameter('display') as $propId) {
				
				$do = $this->_obj->getDataObject();
				$property = $do->getRecursiveProperty($propId);
		//		var_dump($property);
				if (! $property instanceof Property\AbstractProperty) {
					continue;
				}
				
				if (strstr($propId, '.')) {
					
					$propId = '_' . $propId;
				}
				
				$this->_parsedDisplay[$propId] = $property->reduce();
			}
		}
		
		$data = array();

		foreach ($this->getParameter('search') as $property) {
			
			//$this->_obj->resetConditions($property);
			$this->_obj->having($property)->contains($params['query']);
		}

		if (isset($params['batch']) && $params['batch'] > 0) {
			
			$this->_obj->setBoundaryBatch($params['batch']);
		}
			
		if ($this->_obj->find(ObjectModel::MODEL) === false) {
		
			return false;
		}
				
		foreach ($this->_obj->getMembers() as $member) {
		
			$data[] = $member->reduce(array('params' => array(), 
											'extprops' => array('produit' => array('reference','label','statut'))));
		}
		
		return array('collection' => $data, 'display' => $this->_parsedDisplay);
	}	
}
