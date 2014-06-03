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
    	View::addCoreLib(array('style.css','buttons.css','sprites.css'));
    	 
    	// set relevant uri adapter and get some identifiers
    	if (! ViewUri::getUriAdapter() instanceof ViewUri\Adapter\GetAdapter ) {
    		// set url base
    		$tmp = explode('?', $_SERVER['REQUEST_URI']);
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter($tmp[0]);
    	} else {
    		$this->_uriAdapter = ViewUri::getUriAdapter();
    	}
    	 
    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	 
    	if (count($this->_uriAdapter->getEnv()) != 0) {
    		$this->_uriAdapter->saveSearchTerms();
    	} else {
    		// try and restore cached search terms for the current uri
    		$this->_uriAdapter->restoreSearchTerms();
    	}
    	
    	// set data source for environment
    	$this->_env = $this->_uriAdapter->getEnv();
    	 
        $p  = $this->_headerRendering();
        $p .= $this->_contentRendering();
       
        $status = $this->_obj->getParameter('open_default') ? 'open' : 'close';
		$status .= $this->_obj->getParameter('locked') ? ' locked' : '';
		$title = $this->_obj->getTitle() ? $this->_obj->getTitle() : 'Recherche';
		

		$html_head = <<<HTML
	<div class="t41 component white medium" id="{$this->getId()}">
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
        	
        	// Reset default value to empty
        	$field->setDefaultValue('');
        	
        	$data = isset($this->_env[$this->_searchIdentifier]) ? $this->_env[$this->_searchIdentifier] : null;
            $deco = Decorator::factory($field, array('mode' => FormComponent::SEARCH_MODE, 'data' => $data));

            $p .= '&nbsp;' . $deco->render();
            $p .= '</span>';
        }
        
        if ($this->_obj->getParameter('buttons') != false) {
	        View::addCoreLib(array('buttons.css','sprites.css'));
    	    $p .= sprintf('<div class="clear"><a class="element button medium icon" onclick="jQuery(\'#t41sf\').submit()"><span class="search-blue"></span>Rechercher</a>'
        				. '<a class="element button medium icon" onclick="jQuery(\'#t41sf\').find(\':input\').each(function() {jQuery(this).val(null)})"><span class="refresh"></span>RAZ</a></div>'
        				, $this->_obj->getParameter('baseurl'));
        }
        $p .= '</fieldset></form>';
        			       
        return $p;
    }
}
