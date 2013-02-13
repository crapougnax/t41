<?php

namespace t41\View\FormComponent;


use t41\View;
use	t41\View\ViewUri;
use t41\View\Decorator;
use	t41\View\SimpleComponent;
use t41\View\FormComponent;


class WebSearch extends SimpleComponent\WebDefault {

	/**
	 * Uri adapter
	 *
	 * @var t41\View\ViewUri\AbstractAdapter
	 */
	protected $_uriAdapter;
	
	protected $_offsetIdentifier;
	
	protected $_sortIdentifier;
	
	protected $_searchIdentifier;
	
	protected $_instanceof = 't41\View\FormComponent';
	
	
	/**
	 * Array where user parameters can be found (typically $_GET or $_POST)
	 *
	 * @var array
	 */
	protected $_env;
	
	
	/**
	 * t41\View\FormComponent instance
	 *
	 * @var t41\View\FormComponent
	 */
	protected $_obj;
	
	
    public function render()
    {
    	// set relevant uri adapter and get some identifiers
    	if (! ViewUri::getUriAdapter() instanceof ViewUri\Adapter\GetAdapter ) {
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter();
    	} else {
    		$this->_uriAdapter = ViewUri::getUriAdapter();
    	}
    	 
    	// set url base
    	$this->_uriAdapter->setUriBase(substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')));
    	
    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	 
    	// set data source for environment
    	$this->_env = $this->_uriAdapter->getEnv();
    	 
        $p  = $this->_headerRendering();
        $p .= $this->_contentRendering();
       
        $status = $this->_obj->getParameter('open_default') ? 'open' : 'close';
		$status .= $this->_obj->getParameter('locked') ? ' locked' : '';
		$title = $this->_obj->getTitle() ? $this->_obj->getTitle() : 'Recherche';
		

		$html_head = <<<HTML
	<div class="t41 component" id="{$this->_instanceof}_{$this->_obj->getId()}">
		<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
		<div class="content">
HTML;

		// save search terms
    	//$this->_uriAdapter->saveSearchTerms($searchTermsSessionKey);

        return $html_head . $p . '</form></div></div>';
    }

    
    protected function _headerRendering()
    {
        $p = sprintf('<form method="get" action="%s" id="t41sf">', $this->_obj->getParameter('baseurl'));
        
        /**
         * Preserve the view uri arguments by injecting them in the code
         */
/*        if ($this->_uriAdapter->getArguments()) {
        	
        	foreach ($this->_uriAdapter->getArguments() as $key => $val) {
        		
        		$p .= sprintf('<input type="hidden" name="%s" value="%s" />' . "\n", $key, $val);
        	}
        }
*/    	
        return $p;
    }

    
    public function _contentRendering()
    {
    	$p = '<fieldset>';
        
        foreach ($this->_obj->getColumns() as $key => $element) {
        	
        	$field = $element;
        	
        	$p .= '<span class="field">';
        	$p .= sprintf('<span class="label"><label for="%s[%s]">%s</label></span>'
        					, $this->_searchIdentifier
        					, $field->getId()
        					, $field->getTitle()
        				 );
            
        	// get current field value from env
        	if (isset($this->_env[$this->_searchIdentifier][$field->getId()])) {
            	$field->setValue($this->_env[$this->_searchIdentifier][$field->getId()]);
        	}
        	
        	$data = isset($this->_env[$this->_searchIdentifier]) ? $this->_env[$this->_searchIdentifier] : null;
            $deco = Decorator::factory($field, array('mode' => FormComponent::SEARCH_MODE, 'data' => $data));

            $p .= '&nbsp;' . $deco->render();
            $p .= '</span>';
        }
        
        View::addCoreLib(array('buttons.css','sprites.css'));
        $p .= sprintf('<div class="clear"><a class="element button medium icon" onclick="jQuery(\'#t41sf\').submit()"><span class="search-blue"></span>Rechercher</a>'
        			. '<a class="element button medium icon" onclick="jQuery(\'#t41sf\').find(\':input\').each(function() {jQuery(this).val(null)})"><span class="refresh"></span>RAZ</a></div>'
        			, $this->_obj->getParameter('baseurl'));
        $p .= '</fieldset></form>';
        			       
        return $p;
    }
}
