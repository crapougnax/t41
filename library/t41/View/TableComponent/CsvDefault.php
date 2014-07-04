<?php

namespace t41\View\TableComponent;

use t41\View\Decorator\AbstractDecorator;

class CsvDefault extends AbstractDecorator {

    
    private $_sep = null;
    
    private $_addChar = null;
    
    private $_content = null;
    
    
    public function render()
    {
    	$this->_sep = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mac_PowerPC|Macintosh/', $_SERVER['HTTP_USER_AGENT']) ? str_replace('\\t', "\011", ";") : str_replace('\\t', "\011", ",");

    	$this->_addChar = "\015\012";
	    $this->_addChar = str_replace('\\r', "\015", $this->_addChar);
	    $this->_addChar = str_replace('\\n', "\012", $this->_addChar);
	    $this->_addChar = str_replace('\\t', "\011", $this->_addChar);        
    
        $this->_make();
      
	    return $this->_content;
    }
    

    private function _make()
    {
    	$row = $this->_content = null;
    	
	    foreach ($this->_obj->getColumns() as $col) {
	    	
	        if ($row) $row .= $this->_sep;
	        
	        $row .= "\"" . str_replace("\"","\\\"", $col->getTitle()) . "\"";
	    }
	    
	    $row .= $this->_addChar;
	    //$row = ereg_replace("\015(\012)?", "\012", $row);
    	$this->_content .= $row;
    	
    	foreach ($this->_obj->getRows() as $line) {
    		$row = null;
    		
    		foreach ($line as $value) {
    			
    		    if ($row) $row .= $this->_sep;
    		    
    		    if (is_float($value + 0)) {
    		    //	$value = str_replace('.',',', $value);
    		    }
    		    
    		    $row .= '"' . str_replace(array('\r\n', '"'), array(', ', '""'), $value) . '"';
    		}
    		
    		$row .= $this->_addChar;
    		//$row = ereg_replace("\015(\012)?", "\012", $row);
    		$this->_content .= $row;
    	}
    }
}
