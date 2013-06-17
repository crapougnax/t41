<?php

namespace t41\View\ListComponent;

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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 963 $
 */

use t41\ObjectModel;
use t41\ObjectModel\Property\AbstractProperty;
use t41\View\Decorator\AbstractCsvDecorator;
use t41\View\ViewUri;
use t41\View\ListComponent\Element;
use t41\ObjectModel\Property\MetaProperty;
use t41\ObjectModel\Property;
use t41\ObjectModel\Property\ObjectProperty;
use t41\ObjectModel\DataObject;
use t41\View\Decorator;

/**
 * List view object default Web Decorator
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class CsvDefault extends AbstractCsvDecorator {

	
	protected $_instanceof = 't41\View\ListComponent';
		
	protected $_offsetIdentifier;
	
	protected $_sortIdentifier;
	
	protected $_searchIdentifier;
	
	
	/**
	 * Array where user parameters can be found (typically $_GET or $_POST)
	 *
	 * @var array
	 */
	protected $_env;
	
	
	/**
	 * t41\View\ListComponent instance
	 *
	 * @var t41\View\ListComponent
	 */
	protected $_obj;


	/**
	 * t41\ObjectModel\Collection instance
	 *
	 * @var t41\ObjectModel\Collection
	 */
	protected $_collection;
	
	
    public function render()
    {
    	$this->_collection = $this->_obj->getCollection();
    	 
    	// set relevant uri adapter and get some identifiers 
    	if (! ViewUri::getUriAdapter() instanceof ViewUri\Adapter\GetAdapter) {
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter();
    	} else {
    		$this->_uriAdapter = ViewUri::getUriAdapter();
    	}
    	
    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	
    	
    	// set data source for environment
    	$this->_env = $this->_uriAdapter->getEnv();
    	
        	// set query parameters from context
    	if (isset($this->_env[$this->_searchIdentifier]) && is_array($this->_env[$this->_searchIdentifier])) {

    		foreach ($this->_env[$this->_searchIdentifier] as $field => $value) {
    			
    			$field = str_replace("-",".",$field);

    			if (! empty($value) && $value != Property::EMPTY_VALUE) { // @todo also test array values for empty values
    				$property = $this->_collection->getDataObject()->getProperty($field);
    				
    				if ($property instanceof MetaProperty) {
    					$this->_collection->having($property->getParameter('property'))->contains($value);
    				} else if ($property instanceof ObjectProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->equals($value);
    				} else if ($property instanceof AbstractProperty) {
       					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->contains($value);
    				}
	    			$this->_uriAdapter->setArgument($this->_searchIdentifier . '[' . $field . ']', $value);
    			}
    		}
    	}
    	
    	// set query sorting from context
        if (isset($this->_env[$this->_sortIdentifier]) && is_array($this->_env[$this->_sortIdentifier])) {
        	foreach ($this->_env[$this->_sortIdentifier] as $field => $value) {
    			$this->_collection->setSorting(array($field, $value));
    		}
    	}

    	// define offset parameter value from context
    	if (isset($this->_env[$this->_offsetIdentifier])) {
    		$this->_obj->setParameter('offset', (int) $this->_env[$this->_offsetIdentifier]);
    		$this->_collection->setBoundaryOffset($this->_env[$this->_offsetIdentifier]);
    	}
    	
    	$this->_collection->setBoundaryBatch(1000);
    	
        $this->_obj->query();

        $row = '';
        $sep = str_replace('\\t', "\011", ",");
        
        // print out result rows
        foreach ($this->_obj->getCollection()->getMembers(ObjectModel::DATA) as $this->_do) {
        	        
        	$p = null;
        
	        foreach ($this->_obj->getColumns() as $key => $column) {
        		        
        		if ($p) {
        			$p .= $sep;
        		}
        
	            if ($column instanceof Element\IdentifierElement) {
            		$value = $this->_do->getUri()->getIdentifier();
            	} else if ($column instanceof Element\MetaElement) {
            		$attrib = ($column->getParameter('type') == 'currency') ? ' class="cellcurrency"' : null;
            		$p .= "<td$attrib>" . $column->getDisplayValue($this->_do) . '</td>';
            	} else {
	            	$property = $this->_do->getProperty($column->getParameter('property'));
    	        	$column->setValue($property->getValue());
            	            	 
            		if ($column->getParameter('recursion')) {
						foreach ($column->getParameter('recursion') as $recursion) {
							if ($property instanceof AbstractProperty) {
								$property = $property->getValue(ObjectModel::DATA);
							}
							if ($property instanceof ObjectModel || $property instanceof DataObject) {
								$property = $property->getProperty($recursion);
							}
						}
            		}
            	
            		if ($property instanceof Property\MediaProperty) {
            			$value = '';
            		} else {
	  					$value = ($property instanceof Property\AbstractProperty) ? $property->getDisplayValue() : null;
            		}
            	}
        
        		$fv = str_replace('\r\n', ', ', stripslashes($value));
        		$p .= "\"" . str_replace("\"","\\\"",$fv) . "\"";
	        }
        	$row .= $p . "\r\n";
        }
        
        return $this->_drawHeaderRow() . $row;
    }
    
    
    /**
     * Draw table header
     */
    protected function _drawHeaderRow()
    {
        $row = "";
        $sep = str_replace('\\t', "\011", ",");
        $add_character = "\015\012";
        $add_character = str_replace('\\r', "\015", $add_character);
        $add_character = str_replace('\\n', "\012", $add_character);
        $add_character = str_replace('\\t', "\011", $add_character);
        
        /* @var $val t41_Form_Element_Abstract */
        foreach ($this->_obj->getColumns() as $colonne) {
        
        	if ($row) {
        		$row .= $sep;
        	}
        	$row .= '"' . str_replace("\"","\\\"", $colonne->getTitle()) . '"';
        }
        
        $row .= $add_character;
        //$row = preg_replace("\015(\012)?", "\012", $row);
        
        return $row;
    }
}
