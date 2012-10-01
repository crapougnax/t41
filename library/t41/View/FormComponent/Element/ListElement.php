<?php

namespace t41\View\FormComponent\Element;

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
 * @version    $Revision: 876 $
 */

use t41\Parameter;
use t41\ObjectModel;
use t41\ObjectModel\Property;
use t41\Backend;

/**
 * t41 Data Object handling a set of properties tied to an object
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ListElement extends AbstractElement {

	
	/**
	 * 
	 * t41_Object_Collection instance used to query values
	 * @var t41_Object_Collection
	 */
	protected $_collection;
	
	
	protected $_totalValues;

	
	public function setCollection(ObjectModel\Collection $collection)
	{
		$this->_collection = $collection;
		$this->_collection->setBoundaryBatch($this->getParameter('selectmax')+1);
		if ($this->getParameter('sorting')) {
		
			if (! is_array($this->getParameter('sorting'))) {
					
				$sortings = explode(',', $this->getParameter('sorting'));
				$array = array();
				foreach ($sortings as $sorting) {
					$array[] = explode(' ', $sorting);
				}
				$this->_collection->setSortings($array);
				
			} else {
				$this->_collection->setSortings($this->getParameter('sorting'));
			}
		}
		return $this;
	}
	
	
	/**
	 * Returns objects collection
	 * @return t41\ObjectModel\Collection
	 */
	public function getCollection()
	{
		return $this->_collection;
	}
	
	
	public function getTotalValues()
	{
		if (is_null($this->_totalValues)) {
			$this->_collection->find();
			$this->_totalValues = $this->_collection->getTotalMembers();
		}
		return $this->_totalValues;
	}
	
	
	public function setEnumValues($str = null)
	{
		if (is_array($str)) {
	        $this->_enumValues = $str;
			return;
		}

		$this->_enumValues = array();

        $this->_collection->find();
        
        foreach ($this->_collection->getMembers() as $member) {
        	// define value key (property val if altkey parameter is setted or uri's identifier by default
        	$key = $this->getParameter('altkey') ? $member->getProperty($this->getParameter('altkey'))->getValue() : $member->getIdentifier();
            $this->_enumValues[$key] = Property::parseDisplayProperty($member, $this->getParameter('display'));
        }
       return $this->_enumValues;
	}

	
    public function formatValue($key = null)
    {
        if ($key == null) return '';

        // value is already available (foreign key with no specific constraint in it
        if (isset($this->_enumValues[$key])) {
        	return $this->_enumValues[$key];
        }

        // value no more available to select, though we need to display it !
        if (is_string($key)) {
	        $uri = new ObjectModel\ObjectUri($key);
    	    $uri->setClass($this->getCollection()->getClass());
        
        	$_do = clone $this->_collection->getDataObject();
     		$_do->setUri($uri);
        	Backend::read($_do);
        } else {
        	$_do = $key->getDataObject();
        }

        return Property::parseDisplayProperty($_do, $this->getParameter('display'));
    }
    
    
    public function getEnumValues($str = null)
    {
    	if (is_null($str)) {
    		if (is_null($this->_enumValues)) {
    			$this->setEnumValues();
    		}
    		return $this->_enumValues;
    	
    	} else {
    		$this->setEnumValues($str);
    		return $this->_enumValues;
    	}
    }

    
	public function setValue($val)
	{
		if (! $val instanceof ObjectModel\ObjectUri && ! $val instanceof ObjectModel\BaseObject) {
			$class = $this->getCollection()->getClass();
			$val = new $class($val);
		}
		parent::setValue($val);
	}
}
