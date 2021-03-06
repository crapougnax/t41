<?php

namespace t41\View\FormComponent;


use t41\ObjectModel\BaseObject;

use t41\View,
	t41\ObjectModel\Property,
	t41\View\FormComponent\Element,
	t41\View\SimpleComponent;

class WebDefault extends SimpleComponent\WebDefault {

	
	protected $_css = 'mask_default';
	
	protected $_cssLib = 't41';
	
	protected $_cssStyle = 't41_mask_default';
	
	protected $_instanceof = '\t41\View\FormComponent';
	
	/**
	 * Form ID
	 *
	 * @var string
	 * @todo find a better way to make the form id changeable
	 */
	protected $_formId = 't41_form_mask';
	
	/**
	 * Array where user parameters can be found (typically $_GET or $_POST)
	 *
	 * @var array
	 */
	protected $_env;
	
	
	/**
	 * t41_View_Form instance
	 *
	 * @var t41_View_Form
	 */
	protected $_obj;


	/**
	 * Render form HTML and trigger t41.view.form() JS object
	 * @see \t41\View\SimpleComponent\WebDefault::render()
	 */
    public function render()
    {
    	View::addCoreLib(array('style.css','buttons.css','sprites.css'));
    	View::addCoreLib(array('core.js','view.js','view:form.js','view:alert.js'));
    	
    	// cache object and create its client-side js counterpart
    	$reduced = $this->_obj->getSource()->reduce();
    	$this->_id = $this->_obj->getId() ? $this->_obj->getId() : 't41_' . md5(time());
    	
    	View::addCoreLib(array('buttons.css','sprites.css'));
		View::addEvent(sprintf("t41.view.register('%s', new t41.view.form('%s',%s,%s))"
									, $this->_id
									, $this->_id
									, \Zend_Json::encode($reduced)
									, \Zend_Json::encode($this->_obj->reduce())
								  ), 'js');
    	
        return $this->_headerRendering() . $this->_contentRendering() . $this->_footerRendering();
    }
    
    
    protected function _contentRendering()
    {
		$p = '<fieldset id="form_fields">';
		$p .= '<legend></legend>';
		
		$altDecorators = (array) $this->_obj->getParameter('decorators');
		
		/* @var $val t41_View_Element_Abstract */
        foreach ($this->_obj->getColumns() as $key => $element) {
        	
        	$field = $element;
        	 
        	/* hidden fields treatment */
        	if ($element->getConstraint(Element\AbstractElement::CONSTRAINT_HIDDEN) === true) {
        		$p .= sprintf('<input type="hidden" name="%s" id="%s" value="%s" />'
        					, $field->getAltId()
        					, $field->getAltId()
        					, $field->getValue()
        					);
        					
        		continue;
        	}
        	
        	if (! isset($focus) && $element instanceof Element\FieldElement) {
	        	View::addEvent(sprintf("jQuery('#%s').focus()", $element->getAltId()), 'js');
	        	$focus = $element;
        	}
        	
        	$label = '&nbsp;';
	        $label = $this->_escape($element->getTitle());
    	    if ($element->getConstraint(Property::CONSTRAINT_MANDATORY) == true) {
    	    	$class=' mandatory';
    	        $mandatory = ' mandatory';
    	    } else {
    	    	$class ='';
    	        $mandatory = '';
    	    }

    	    $line = sprintf('<div class="clear"></div><div id="label_%s" class="label%s"><label for="%s" data-help="%s">%s</label></div>'
            				, $field->getAltId()
    	    				, $class
    	    				, $field->getAltId()
    	    				, $field->getHelp()
            				, $label
            			 );
            
    	    // value is already defined and can't be changed
    	    if ($field->getConstraint(Property::CONSTRAINT_PROTECTED) == true && $field->getValue()) {
	            if (! is_object($field->getValue()) || ($field->getValue() instanceof BaseObject && $field->getValue()->getUri())) {
    	        	$p .= $line . '<div class="field">' . $field->formatValue($field->getValue()) . '</div>';
        	    	continue;
            	}
    	    }
            
            /* look for a required decorator */
            if (isset($altDecorators[$element->getId()])) {
            	$element->setDecorator($altDecorators[$element->getId()]);
            }
            
            $deco = View\Decorator::factory($element);
	        $line .= sprintf('<div class="field" id="elem_%s">%s</div>', $element->getId(), $deco->render());
            $p .= $line . "\n";
        }

        $p .= '</fieldset>';
        
        return $p;
    }


    protected function _renderButton(Element\ButtonElement $button)
    {
    	$deco = View\Decorator::factory($button, array('size' => 'medium'));
    	return $deco->render();
    }
    
    
    protected function _headerRendering()
    {
		return  parent::_headerRendering() . $this->_formHeader();
		
    }

    
    protected function _footerRendering()
    {
    	return $this->_formFooter() . parent::_footerRendering();
    }
    
    
    protected function _formHeader()
    {
		return sprintf('<form id="%s_form" method="post">', $this->_id);
    }
    
    
    protected function _formFooter()
    {
    	/**
    	 * Buttons are displayed by t41.view.form.js
    	 */    	
    	return sprintf('<fieldset class="t41 form_actions"></fieldset></form>');
    }
    
    
    public function save($data)
    {
    	return $this->_obj->save($data);
    }
}
