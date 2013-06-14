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

	
    public function render()
    {
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
    			if (! empty($value)) { // @todo also test array values for empty values
	    			//$this->_obj->setCondition($field, $value);
	    			$this->_uriAdapter->setArgument($this->_searchIdentifier . '[' . $field . ']', $value);
    			}
    		}
    	}
    	
    	// set query sorting from context
        if (isset($this->_env[$this->_sortIdentifier]) && is_array($this->_env[$this->_sortIdentifier])) {
        	foreach ($this->_env[$this->_sortIdentifier] as $field => $value) {
    			$this->_obj->setSorting($field, $value);
    		}
    	}

    	// define offset parameter value from context
    	if (isset($this->_env[$this->_offsetIdentifier])) {
    		$this->_obj->setBoundaryOffset($this->_env[$this->_offsetIdentifier]);
    	}
    	
    	// export all relevant data (no limit)
    	//$this->_obj->setBoundaryBatch(0);
    	
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
        
	            if ($column instanceof Element\MetaElement) {
        			$attrib = ($column->getParameter('type') == 'currency') ? ' class="cellcurrency"' : null;
        			$value = $column->getDisplayValue($this->_do);

        		} else {
        		 
        			$property = $this->_do->getProperty($column->getParameter('property'));
        		 
	        		if ($column->getParameter('recursion')) {
    	    			foreach ($column->getParameter('recursion') as $recursion) {
        					$property = $property->getValue(ObjectModel::DATA);
        					if ($property) $property = $property->getProperty($recursion);
        				}
        			}
        		 
        			$value = ($property instanceof AbstractProperty) ? $property->getDisplayValue(): null;
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
