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
 */

use t41\ObjectModel\ObjectUri;

use t41\ObjectModel;
use t41\ObjectModel\Property;
use t41\View\ListComponent\Element;
use t41\View\FormComponent\Element\ButtonElement;
use t41\View\ListComponent\Element\MetaElement;
use t41\Core\Registry;
use t41\ObjectModel\Property\CurrencyProperty;
use t41\View\ViewUri\Adapter\AbstractAdapter;


/**
 * Class providing data list objects
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2009 Quatrain Technologies SAS (http://technologies.quatrain.com)
 * @license    http://t41.quatrain.com/license/new-bsd     New BSD License
 */
class ListComponent extends ViewObject {

	
	const METACOL = '*';
	
	
	protected $_collection;
	

	/**
	 * @deprecated
	 * @var unknown_type
	 */
	protected $_fields;
	
	
	protected $_elements;
	

	protected $_events = array('global' => array(), 'row' => array());
	
	
	protected $_columns;
	
	
	protected $_groups = array();
	
	
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
     * Returns the collection handled by the object
     * @return t41\ObjectModel\Collection
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
    		
    		$alt = $this->getParameter('altlabels');
    		
    		$do = $this->_collection->getDataObject();
    		$columns = $this->getParameter('columns') ? $this->getParameter('columns') : array_keys($do->getProperties());
    		
    		$this->_columns = array();
    		
    		foreach ($columns as $column) {
    			
    			// meta columns are useful to display calculated (not stored) values
    			if (substr($column, 0, 1) == self::METACOL) {
    				$parts = explode(':', substr($column, 1));
    				$obj = new Element\MetaElement($parts[0]);
    				$obj->setParameter('property', $parts[0]);
    				if (isset($parts[1])) $obj->setParameter('action', $parts[1]);
    				$this->_columns[] = $obj;
    				continue;
    				
    			} else if ($column instanceof MetaElement) {
    				$this->_columns[] = $column;
    				continue;
    			}

    			if ($column == ObjectUri::IDENTIFIER) {
    				$obj = new Element\IdentifierElement();
    				$obj->setTitle(isset($alt[$column]) ? $alt[$column] : 'ID');
    				$this->_columns[] = $obj;
    			}
    			
    			// $column may contain recursive property reference
    			$parts = explode('.', $column);
    			 
    			// find matching property
    			$property = $do->getRecursiveProperty($column);
    			 
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
    						if (($nproperty = $do2->getProperty($recursion)) !== false) {
    							$property = $nproperty;
    						}
    					}
    				}
    			}
    			
    			if (! is_object($property)) {
    				$alt[$column] = 'Erreur';
    			}
    			
    			$obj->setParameter('property', $parts[0]);
    			$obj->setTitle(isset($alt[$column]) ? $alt[$column] : $property->getLabel());
    			$obj->setParameter('align', $property instanceof CurrencyProperty ? 'R' : 'L');
    			 
    			$this->_columns[] = $obj;
    		}
    	}
    	
    	return $this->_columns;
    }
    
    
    public function getRows()
    {
    	return $this->_collection->getMembers();
    }
    
    
    public function addGroup($name, $from, $to)
    {
    	$this->_groups[] = array('name'		=> $name
    						   , 'from'		=> $from
    						   , 'to' 		=> $to
    	);
    	return $this;
    }
    
    
    public function getGroups()
    {
    	return $this->_groups;
    }
    
    
    /**
     * Execute a query on the collection after injection of the optional parameters
     * extracted from the View Uri adapter environment
     * @param AbstractAdapter $uriAdapter
     */
    public function query(AbstractAdapter $uriAdapter)
    {
    	$offsetIdentifier	= $uriAdapter->getIdentifier('offset');
    	$sortIdentifier		= $uriAdapter->getIdentifier('sort');
    	$searchIdentifier	= $uriAdapter->getIdentifier('search');
    	 
        // try and restore cached search terms for the current uri
    	if ($this->getParameter('uricache') != false) {
	    	$uriAdapter->restoreSearchTerms();
    	}	
    	
    	$env = $uriAdapter->getEnv();
    	 
    	// set query parameters from context
    	if (isset($env[$searchIdentifier]) && is_array($env[$searchIdentifier])) {
    		foreach ($env[$searchIdentifier] as $field => $value) {
    			$field = str_replace("-",".",$field);
    	
    			if (!is_null($value) && $value != '' && $value != Property::EMPTY_VALUE) {
    				$property = $this->_collection->getDataObject()->getRecursiveProperty($field);
    				if ($property instanceof Property\MetaProperty) {
    					$this->_collection->having($property->getParameter('property'))->contains($value);
    				} else if ($property instanceof Property\ObjectProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->equals($value);
    				} else if ($property instanceof Property\DateProperty) {
    					if (is_array($value)) {
    						if (isset($value['from']) && ! empty($value['from'])) {
    							$this->_collection->having($field)->greaterOrEquals($value['from']);
    						}
    						if (isset($value['to']) && ! empty($value['to'])) {
    							$this->_collection->having($field)->lowerOrEquals($value['to']);
    						}
    					} else {
    						$this->_collection->having($field)->equals($value);
    					}
    				} else if ($property instanceof Property\IntegerProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->equals($value);
    				} else if ($property instanceof Property\AbstractProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->contains($value);
    				}
    				$uriAdapter->setArgument($searchIdentifier . '[' . $field . ']', $value);
    			}
    		}
    	}
    	 
    	// set query sorting from context
    	if (isset($env[$sortIdentifier]) && is_array($env[$sortIdentifier])) {
    		foreach ($env[$sortIdentifier] as $field => $value) {
    			$this->_collection->setSorting(array($field, $value));
    		}
    	}
    	
    	// define offset parameter value from context
    	if (isset($env[$offsetIdentifier])) {
    		$this->setParameter('offset', (int) $env[$offsetIdentifier]);
    		$this->_collection->setBoundaryOffset($env[$offsetIdentifier]);
    	}
    	
    	if ($this->_collection->getParameter('populated') !== true) {
    		//$this->_collection->debug(); die;
    		$this->_collection->find(ObjectModel::DATA);
    		$this->setParameter('max', $this->_collection->getMax());
    	}
    }
    
    
    /**
     * 
     * @param string $type
     * @param Element\ButtonElement|string $button
     * @param array $params Decorator parameters
     * @throws Exception
     */
    public function addRowAction($link, $button = null, array $params = null)
    {
    	if (! $button instanceof ButtonElement) {
    		$title = $button;
    		$button = new ButtonElement();
    		$button->setTitle($title);
    	}
    	
    	$button->setLink($link);
    	if (is_array($params)) {
    		$button->setDecoratorParams($params);
    	}
    	    	
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
    
    
    public function reduce(array $params = array(), $cache = true)
    {
    	$uuid = Registry::set($this, null, true);
    	return array_merge(parent::reduce($params), array('uuid' => $uuid, 'obj' => $this->_collection->reduce($params, $cache)));
    }
}
