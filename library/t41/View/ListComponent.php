<?php

namespace t41\View;

/**
 * t41 Toolkit
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://t41.quatrain.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@quatrain.com so we can send you a copy immediately.
 *
 * @category   t41
 * @package    t41_Form
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 * @version    $Revision: 879 $
 */

use t41\View,
	t41\ObjectModel,
	t41\ObjectModel\Property,
	t41\View\ListComponent\Element,
	t41\View\FormComponent\Element\ButtonElement;

/**
 * Class providing data list objects
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 */
class ListComponent extends ViewObject {

	
	protected $_collection;
	

	/**
	 * @deprecated
	 * @var unknown_type
	 */
	protected $_fields;
	
	
	protected $_elements;
	

	protected $_events = array('global' => array(), 'row' => array());
	
	
	protected $_columns;
	
	
	/**
	 * Class constructor

	 * @param t41_Object_Collection $collection
	 * @param array $params
	 */
    public function __construct(ObjectModel\Collection $collection, array $params = null)
    {
    	$this->_collection = $collection;
    	
    	parent::__construct(null, $params);
    }
    

    /**
     *
     * @return t41_Object_Collection
     */
    public function getCollection()
    {
    	return $this->_collection;
    }
    
    
    /**
     * Define an array of printable columns based on list or setted parameter
     * @return array
     */
    public function getColumns()
    {
    	if (! is_array($this->_columns)) {
    		
    		$do = $this->_collection->getDataObject();
    		$columns = $this->getParameter('columns') ? $this->getParameter('columns') : array_keys($do->getProperties());
    		
    		$this->_columns = array();
    		
    		foreach ($columns as $column) {

    			// $column may contain recursive property reference
    			$parts = explode('.', $column);
    			 
    			// find matching property
    			$property = $do->getProperty($parts[0]);
    			 
    			if (! $property instanceof Property\AbstractProperty) {
    				
    				continue;
    			}
    			
    			$obj = new Element\ColumnElement($column);
    			
    			if (count($parts) > 1) {

    				$obj->setParameter('recursion', array_slice($parts, 1));
    				
    				foreach (array_slice($parts, 1) as $recursion) {
    					
    					// recursion to find the related property
    					if ($property instanceof Property\ObjectProperty) {
    						
    						$do2 = ObjectModel\DataObject::factory($property->getParameter('instanceof'));
    						$property = $do2->getProperty($recursion);
    						
    						if (! $property instanceof Property\AbstractProperty) {
    						
    							continue;
    						}
    					}
    				}
    			}
    			
    			$obj->setParameter('property', $parts[0]);
    			$obj->setTitle($property->getLabel());
    			 
    			$this->_columns[] = $obj;
    		}
    	}
//    	\Zend_Debug::dump($this->_columns); die;
    	return $this->_columns;
    }
    
    
    public function getRows()
    {
    	return $this->_collection->getMembers();
    }
    
    
    public function query()
    {
    	$this->_collection->setParameter('memberType', ObjectModel::DATA);
    	$this->_collection->find();
    	$this->setParameter('max', $this->_collection->getMax());
    }
    
    
    /**
     * 
     * @param unknown_type $type
     * @param Element\ButtonElement|string $button
     * @throws Exception
     */
    public function addRowAction($link, $button = null)
    {
    	
    	if (! $button instanceof ButtonElement) {
    		
    		$title = $button;
    		$button = new ButtonElement();
    		$button->setTitle($title);
    	}
    	
    	$button->setLink($link);
    	$button->setDecorator(null, array('size' => 'small'));
    	    	
    	return $this->_addEvent('row', $button);
    }
    
    
    protected function _addEvent($scope, $button = null)
    {
    	if (! is_array($this->_events[$scope])) {
    		
    		$this->_events[$scope] = array();
    	}
    	$this->_events[$scope][] = $button;
    	return $this;
    }
    
    
    public function getEvents($scope)
    {
    	return isset($this->_events[$scope]) ? $this->_events[$scope] : array();
    }
}
