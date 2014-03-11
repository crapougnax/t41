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
 */

use t41\ObjectModel;
use t41\ObjectModel\Property;
use t41\ObjectModel\DataObject;
use t41\ObjectModel\Property\AbstractProperty;
use t41\View\ViewUri;
use t41\View\ListComponent\Element;
use t41\View\Decorator\AbstractCsvDecorator;


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
    	$this->_collection->setBoundaryBatch(-1);
    	 
        // set relevant uri adapter and get some identifiers
    	/* @var $_uriAdapter t41\View\ViewUri\AbstractAdapter */
    	if (! ($this->_uriAdapter = ViewUri::getUriAdapter()) instanceof ViewUri\Adapter\GetAdapter ) {
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter();
    	}
        $this->_obj->query($this->_uriAdapter);

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
            		$value = $column->getDisplayValue($this->_do);
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
        	
        	// preserve memory!
        	$this->_do->reclaimMemory();
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
