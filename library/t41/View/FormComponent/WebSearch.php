<?php

require_once 't41/View/Abstract.php';


class t41_View_Form_Web_Search extends t41_View_Component_Web_Default {

	/**
	 * Uri adapter
	 *
	 * @var t41_View_Uri_Adapter_Abstract
	 */
	protected $_uriAdapter;
	
	protected $_css = 'search_default';
	
	protected $_cssLib = 't41';
	
	protected $_cssStyle = 't41_search_default';
	
	protected $_instanceof = 't41_View_Form';
	
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
	 * t41_Form_Search instance
	 *
	 * @var t41_Form_Search
	 */
	protected $_obj;
	
	
    public function render()
    {
    	// set relevant uri adapter and get some identifiers 
    	if (! t41_View_Uri::getUriAdapter() instanceof t41_View_Uri_Adapter_Get ) {
    		$this->_uriAdapter = new t41_View_Uri_Adapter_Get(); // t41_View_Uri::setUriAdapter('get');
    	} else {
    		$this->_uriAdapter = t41_View_Uri::getUriAdapter();
    	}
    	
    	// Restore saved search terms if they exist
    	$searchTermsSessionKey = get_class($this->_obj) . '_' . $this->_obj->getId();
    	$this->_uriAdapter->restoreSearchTerms($searchTermsSessionKey);

    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	
    	// set data source for environment
      	$this->_env = $this->_uriAdapter->getEnv();
      	
      	t41_View::addRequiredLib('base','js','t41');
    	
    	// set query parameters from context
/*    	if (isset($this->_env[$this->_searchIdentifier]) && is_array($this->_env[$this->_searchIdentifier])) {

    		foreach ($this->_env[$this->_searchIdentifier] as $field => $value) {
    			
    			if (! empty($value)) { // @todo also test array values for empty values
    				
	    			$this->_uriAdapter->setArgument($this->_searchIdentifier . '[' . $field . ']', $value);
	    		//	$this->_obj->setCondition($field, $value);
    			}
    		}
    	}
*/
/*    		
    	// set query sorting from context
        if (isset($this->_env[$this->_sortIdentifier]) && is_array($this->_env[$this->_sortIdentifier])) {

    		foreach ($this->_env[$this->_sortIdentifier] as $field => $value) {
    			
    			$this->_obj->setSorting($field, $value);
    		}
    	}

    	if (isset($this->_env[$this->_offsetIdentifier])) {
    		$this->_obj->setBoundaryOffset($this->_env[$this->_offsetIdentifier]);
    	}
*/
        $p  = $this->_headerRendering();
        $p .= $this->_contentRendering();
       // $p .= $this->_footerRendering();
       
        $status = $this->_obj->getParameter('open_default') ? 'open' : 'close';
		$status .= $this->_obj->getParameter('locked') ? ' locked' : '';
		$title = $this->_obj->getTitle() ? $this->_obj->getTitle() : 'Recherche';
		

		$html_head = <<<HTML
	<div class="t41_wrapper {$this->_cssStyle} {$this->_getTheme()} {$this->_getColor()}" id="{$this->_instanceof}_{$this->_obj->getId()}">
		<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
		<div class="content">
HTML;

		// save search terms
    	$this->_uriAdapter->saveSearchTerms($searchTermsSessionKey);

        return $html_head . $p . '</form></div></div>';
    }


    protected function _renderButton(t41_Form_Element_Button $button)
    {
    	$deco = t41_View_Decorator::factory($button, array('data' => $this->row));
    	return $deco->render();
    }
    
    
    protected function _headerRendering()
    {
        $p = sprintf('<form method="get" action="%s">', $this->_obj->getParameter('baseurl'));
        
        /**
         * Preserve the view uri arguments by injecting them in the code
         */
        if ($this->_uriAdapter->getArguments()) {
        	
        	foreach ($this->_uriAdapter->getArguments() as $key => $val) {
        		
        		$p .= sprintf('<input type="hidden" name="%s" value="%s" />' . "\n", $key, $val);
        	}
        }
    	
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
        					, t41_Core::htmlEncode($field->getTitle())
        				 );
            
        	// get current field value from env
        	if (isset($this->_env[$this->_searchIdentifier][$field->getId()])) {
            	$field->setValue($this->_env[$this->_searchIdentifier][$field->getId()]);
        	}
        	
        	$data = isset($this->_env[$this->_searchIdentifier]) ? $this->_env[$this->_searchIdentifier] : null;
            $deco = t41_View_Decorator::factory($field, array('mode' => t41_Form::SEARCH, 'data' => $data));

            $p .= '&nbsp;' . $deco->render();
            $p .= '</span>';
        }
        
        $p .= sprintf('<div class="clear"><input type="submit" value="OK"/> <input type="button" value="RAZ" onclick="jQuery(this).closest(\'form\').clearForm();" /></div>'
        			, $this->_obj->getParameter('baseurl'));
        $p .= '</fieldset>';
        			       
        return $p;
    }
    

    protected function _footerRendering()
    {
    	
    	return '</form>';
    	
    	
    	$offset = $this->_obj->getParameter('offset');
    	$batch  = $this->_obj->getParameter('batch');
    	$max    = $this->_obj->getParameter('max');
    	$sbatch = 20;

        if ($max == 0) return '<div class="NumerodePage">Aucune fiche</a>';
    	
        foreach ($this->_env as $key => $val) {
        	
            if(is_array($val)) {
                foreach($val as $key2 => $val2) $this->baseUrl .= $key . '[' . $key2 . ']' . '=' . @rawurlencode($val2) . '&';
            } else {
                if ($key != $this->_offsetIdentifier) $this->baseUrl .= $key . '=' . rawurlencode($val) . '&';
            }
        }


	   $premier = floor($offset/($batch * $sbatch)) * $sbatch;

	   $i = $j = 0;

	   $p = sprintf('<p align="center"><div class="NumerodePage">Fiches %s &agrave; %s sur %s<br/>'
	               , ($offset+1)
	               , ($offset + $batch > $max)
	                 ? $max : ($offset + $batch)
	               , $max
	   			   );

	   // no navigation links needed if total of rows is =< batch value
	   if ($max <= $batch) return $p . '</div>';

	   // add one page backward link if applicable
	   if ($offset > 0) {
	       $p .= $this->_navigationLink($offset - $batch, '<<');
	   }

	   // jump to minus ($sbatch value) * $batch
       if ($offset >= $sbatch * $batch) {
		  $p .= $this->_navigationLink($offset - ($sbatch * $batch), $sbatch . ' préc.');
	   }

    	while ($i+($premier * $batch) < $max) {
    		
		  $p .= $this->_navigationLink($i + ($premier * $batch), ($premier + $j + 1));
		  $i += $batch;
		  if (++$j > $sbatch-1) break;
	   }

	   // add 'jump 20 pages forward' link if applicable
	   if ($offset + ($sbatch * $batch) < $max) {
	       $p .= $this->_navigationLink($i + ($premier * $batch), $sbatch . ' suiv.');
	   }

	   // add 'jump to next page' link if applicable
	   if ($max > $offset + $batch) {
		  $p .= $this->_navigationLink($offset + $batch, '>>');
	   }

	   return "$p</div>";
    }

    
    protected function _navigationLink($offset, $page)
    {
	   if ($offset != $this->_obj->getParameter('offset')) {
	   	
	   	  $this->_uriAdapter->setArgument($this->_offsetIdentifier, $offset);
	   	  return sprintf('<a href="%s" class="NumerodePage">%s</a> ', $this->_uriAdapter->makeUri(), $page);

	   } else {
	   	
		  return '<span class="active">' . htmlspecialchars($page) . '</span> ';
	   }
    }
}