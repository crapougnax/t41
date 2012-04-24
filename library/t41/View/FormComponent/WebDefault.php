<?php

namespace t41\View\FormComponent;


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


    public function render()
    {
    	// cache object and create its client-side js counterpart
    	//$action = new View\Action\TestAction($this->_obj->getSource());
    	$reduced = $this->_obj->getSource()->reduce();
    	$this->_id = 't41_' . md5(time());
    	
    	
		View::addEvent(sprintf("%s = new t41.view.form('%s',%s)"
									, $this->_id
									, $this->_id
									, \Zend_Json::encode($reduced)
//									, \Zend_Json::encode($this->getJsArgs())
								  ), 'js');
    	
        return $this->_headerRendering() . $this->_contentRendering() . $this->_footerRendering();
    }
    
    
    protected function _contentRendering()
    {
		$p = '<fieldset>';
		$p .= '<legend></legend>';
		

		$focus = null;
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
        	
        	if (! $focus) {

        		if ($element instanceof t41_View_Form_Element_Generic) {
        			
	        		View::addEvent(sprintf("jQuery('%s').focus()", $field->getAltId()), 'js');
	        		$focus = $key;
        		}
        	}
        	
        	$label = '&nbsp;';
	        $label = $this->_escape($element->getTitle());
    	    if ($field->getConstraint('mandatory') == 'Y') {
    	    
    	    	$label = '<strong>' . $label . '</strong>';
    	        $mandatory = ' mandatory';
    	    } else {
    	        $mandatory = '';
    	    }

    	    $line = sprintf('<div class="clear"></div><div class="label"><label for="%s">%s</label></div>'
            				, $field->getAltId()
            				, $label
            			 );
            			 
            if ($field->getValue() != null && $field->getConstraint(Property::CONSTRAINT_PROTECTED) == true) {
            	
            	$p .= $line . '<div class="field">' . $field->formatValue($field->getValue()) . '</div>';
            	
            	// if field is defined and not editable BUT data is not in backend, we need to provide field value
            	if ($this->_obj->getParameter('rowid') == null) {
            		
            		$p .= sprintf('<input type="hidden" name="%s" value="%s" />' . "\n", $field->getAltId(), $field->getValue()); 
            	}
            	continue;
            }
            
            /* look for a required decorator */
            if (isset($altDecorators[$element->getId()])) {
            	
            	$element->setDecorator($altDecorators[$element->getId()]);
            }
            
            $deco = View\Decorator::factory($element);

            if ($element instanceof t41_View_Form_Element_Multiplekey) {
            	
            	$line = sprintf('<p>%s</p>'
            					, $deco->render()
            				 );
            } else {
            	
	            $line .= sprintf('<div class="field" id="elem_%s">%s</div>'
    	        				, $element->getId()
        	    				, $deco->render()
            				 );
            }
            
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
/*    	$status = $this->_obj->getParameter('open_default') ? 'open' :  'close';
		//$status .= $this->_obj->getParameter('locked') ? ' locked' : '';
		$title = $this->_obj->getTitle() ? $this->_obj->getTitle() : 'Formulaire';
		
		$html = <<<HTML
	<div class="t41_wrapper {$this->_cssStyle} {$this->_getTheme()} {$this->_getColor()}" id="{$this->_formId}_wrapper">
		<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
		<div class="content">
HTML;
*/
		return  parent::_headerRendering() . $this->_formHeader();
		
    }

    
    protected function _footerRendering()
    {
    	return $this->_formFooter() . '</div>';
    }
    
    
    protected function _formHeader()
    {
		return sprintf('<form id="%s_form" method="post">', $this->_id);
    }
    
    
    protected function _formFooter()
    {
    	$p = '<fieldset><input type="button" onclick="history.go(-1)" value="Retour"/><input type="button" name="t41_save" value="Sauver" onclick="'.$this->_formId.'.check(false);" />';
    	if ($this->_obj->getParameter('save_mode') == 'multiple') {
    		$p .= '&nbsp;<input type="button" name="t41_save2" value="Sauver et suivant" onclick="'.$this->_formId.'.check(true);"/>';
    	}
    	$p .= '&nbsp;<input type="reset" name="t41_reset" value="Vider formulaire"/>'
    	    .'</p></fieldset></form>';
    	
    	$submit = new Element\ButtonElement();
    	$submit->setTitle("Sauver");
    	$submit->setLink('t41_form.save()');
    	$deco = View\Decorator::factory($submit);
    	$p = $deco->render();
    	
    	return sprintf('<fieldset id="actions"></fieldset>');
    }
    
    
    public function getJsArgs()
    {
    	$array = array(	'rules' => array()
    				  , 'messages' => array()
    		//		  , 'errorLabelContainer' => "#t41_errors_summary"
    				  );
    	        
		/* @var $field t41_Form_Element_Abstract */
    	foreach ($this->_obj->getAdapter()->getElements() as $element) {
    		
    		$field = $element;
    		$fieldId = $field->getId();
    		
//    	    if ($field instanceof Element\EnumElement && $field->getParameter('render') == Element\EnumElement::RENDER_CHECKBOX) {
//    			$fieldId .= '[]';
//    		}
       		$farray = array();
    		$msg = '';
    		
    		if ($field->getConstraint('mandatory') == '1') {
    			$farray['required'] = '';
    			$msg = "Champ requis";
    		}
    		
    		if (! $field instanceof Element\Foreignkey) {
    			
    			// these rules don't apply to foreign keys
	    		if ($field->getValueConstraint('minval')) $farray['minlength'] = $field->getValueConstraint('minval');
    			if ($field->getValueConstraint('maxval')) $farray['maxlength'] = $field->getValueConstraint('maxval');
    		}
    		
    		if ($field instanceof Element\UriElement ) {
    			
    			switch ($field->getParameter('uritype')) {
    				
    				case 'email':
    					$farray['email'] = '';
    					break;
    					
    				case 'url':
    					$farray['url'] = '';
    					break;
    			}
    		}
    		
    		if (count($farray) == 1 && isset($farray['required'])) {
    			
    			$farray = "required";
    		}

    		$array['rules'][$fieldId] = $farray;
    		if (! empty($msg)) $array['messages'][$fieldId]	= $msg;
    	}
    	
    	return $array;
    }
    
    
    public function save($data)
    {
    	return $this->_obj->save($data);
    }
}
