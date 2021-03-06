<?php

namespace t41\View\Form\Element;

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
class ForeignkeyElement extends ElementAbstract {

	
	/**
	 * 
	 * t41_Object_Collection instance used to query values
	 * @var t41_Object_Collection
	 */
	protected $_collection;
	
	
	protected $_foreign = array();
	
	protected $_totalValues;
	
	protected $_displayProps = array();
	
	
	public function __construct($id = null, array $params = null)
	{
		$this->_setParameterObjects(array('select_max_values'	=> new Parameter(Parameter::INTEGER, 100)
										, 'display'				=> new Parameter(Parameter::STRING)
										, 'sorting'				=> new Parameter(Parameter::MULTIPLE)	
										
										 )
								   );
		
		parent::__construct($id, $params);

		if (isset($this->_has['maxval']) && $this->_has['maxval'] != 0) {
			
			// maxval has no meaning within this field so we use it to define boundary value between select and autocompleter
			$this->setParameter('select_max_values',  $this->_has['maxval']);
		}
	}
	

	public function setCollection(ObjectModel\Collection $collection)
	{
		$this->_collection = $collection;
		return $this;
	}
	
	
	/**
	 * Returns objects collection
	 * @return t41_Object_Collection
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

    //    $sorts = $this->getParameter('sorting') ? $this->getParameter('sorting') : array(array($this->_foreign['pkey'], Zend_Db_Select::SQL_ASC));
        
        if (! is_null($str)) {
	        
      //  	$select->order(new Zend_Db_Expr(sprintf("LENGTH(%s)", $sorts[0][0])));
        }
        
       // foreach ($sorts as $sort) {
            	
        //    $select->order(new Zend_Db_Expr(sprintf("%s %s", $sort[0], isset($sort[1]) ? $sort[1] : Zend_Db_Select::SQL_ASC))); 
       // }
        
            if (!is_null($str) && trim($str) != '%') {
            	
           // 	foreach ($this->_foreign['fields'] as $field) {
	       //     	$select->orWhere("$field LIKE ?", '%' . $str . '%');
           // 	}
            }
            
/*	        if (count($this->_conditions) > 0) {
            	
            	foreach ($this->_conditions as $condition) {
            		
            		if ($condition['obj'] instanceof t41_Form_Element_Abstract) {

	            		$select->where(sprintf("%s %s ?", $condition['obj']->getId(), $condition['operator']), $condition['obj']->getValue());
              		
            		} else if (isset($condition['obj'])) {
            		
	            		$select->where(sprintf("%s %s ?", $condition['obj'], $condition['operator']), $condition['val']);
            		}
            	}
            }
            
            $select->limit(($this->getParameter('select_max_values') > 1) ? $this->getParameter('select_max_values') + 1 : 20);
*/            
        $this->_collection->find();
        
        foreach ($this->_collection->getMembers() as $member) {
                    
        	$this->_displayProps = explode(',', $this->getParameter('display'));
        	$str = array();
        	foreach ($this->_displayProps as $disProp) {
                	
            	$str[] = $member->getProperty($disProp)->getValue();
            }
                
            $this->_enumValues[$member->getUri()->__toString()] = implode(' ', $str);
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

        $uri = new ObjectModel\ObjectUri($key);
        $uri->setClass($this->getCollection()->getClass());
        
        $_do = clone $this->_collection->getDataObject();
        $_do->setUri($uri);
        
        Backend::read($_do);

        $this->_displayProps = explode(',', $this->getParameter('display'));
        
        $str = array();
        foreach ($this->_displayProps as $disProp) {
        	
            $property = $_do->getProperty($disProp);
        	if (! $property instanceof Property\PropertyAbstract) continue;
            $str[] = $property->getValue();
        }
                
        $str = implode(' ', $str);
        $this->_enumValues[$key] = $str;

        return $str;
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

    
    protected function _setLabelElements($str = null)
    {
    	if ($str == null) {
    		
    		return array(preg_replace('/_id$/', '_label', $this->_foreign['pkey']));
    		
    	} else {
    		
    		$fields = array();
    		
    		if (substr($str, 0, 4) == 'tpl:') {
    			
    			$this->_foreign['tpl'] = substr($str, 5, -1);

    			//$matches = array();
		        //extract fields from motif
    	        //preg_match_all('/\{([a-z0-9_-]+)\}/', $this->_foreign['tpl'], $matches, PREG_SET_ORDER);

    			$matches = explode(',', $this->_foreign['tpl']);
    		
    		} else {
    		
    			$matches = explode(',', $str);
    		}
    			
    		foreach ($matches as $val) $fields[] = trim($val);
        	    
    		return $fields;
    	}
    }
    
    
	public function setValue($val)
	{
		if (! $val instanceof ObjectModel\ObjectUri) {
			
			$val = new ObjectModel\ObjectUri($val);
			$val->setClass($this->getCollection()->getClass());
		}
		
		parent::setValue($val);
	}
}
