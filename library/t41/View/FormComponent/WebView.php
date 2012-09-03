<?php

namespace t41\View\FormComponent;


use t41\View,
	t41\ObjectModel\Property,
	t41\View\FormComponent\Element,
	t41\View\SimpleComponent;

class WebView extends WebDefault {

	
    public function render()
    {
        return $this->_headerRendering() . $this->_contentRendering() . $this->_footerRendering();
    }
    
    
    protected function _contentRendering()
    {
		$p = '<fieldset>';
		$p .= '<legend></legend>';
		
		$altDecorators = (array) $this->_obj->getParameter('decorators');
		
        foreach ($this->_obj->getColumns() as $key => $element) {
        	
        	$field = $element;

        	/* hidden fields treatment */
        	if ($element->getConstraint(Element\AbstractElement::CONSTRAINT_HIDDEN) === true) {
        		continue;
        	}
        	
        	$label = '&nbsp;';
	        $label = $this->_escape($element->getTitle());

    	    $line = sprintf('<div class="clear"></div><div class="label"><label for="%s" data-help="%s">%s</label></div>'
    	    				, $field->getAltId()
    	    				, $field->getHelp()
            				, $label
            			 );
            			 
            	
            $p .= $line . '<div class="field">' . $field->formatValue($field->getValue()) . '</div>';
        }

        $p .= '</fieldset>';
        
        return $p;
    }


    protected function _headerRendering()
    {
		return parent::_headerRendering() . '<form>';
    }

    
    protected function _footerRendering()
    {
    	return '</form></div></div>';
    }
}
