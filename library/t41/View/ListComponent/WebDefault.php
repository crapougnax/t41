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

use t41\Core,
	t41\ObjectModel,
	t41\ObjectModel\Property,
	t41\View\Decorator\AbstractWebDecorator,
	t41\View,
	t41\View\ViewUri,
	t41\View\FormComponent\Element;

/**
 * List view object default Web Decorator
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	/**
	 * Uri adapter
	 *
	 * @var t41_View_Uri_Adapter_Abstract
	 */
	protected $_uriAdapter;
	
	protected $_offsetIdentifier;
	
	protected $_sortIdentifier;
	
	protected $_searchIdentifier;
	
	protected $_instanceof = 't41\View\ListComponent';
	
	/**
	 * Array where user parameters can be found (typically $_GET or $_POST)
	 *
	 * @var array
	 */
	protected $_env;
	
	
	/**
	 * t41_View_List instance
	 *
	 * @var t41_View_List
	 */
	protected $_obj;

	
	/**
	 * t41_Object_Collection instance
	 * 
	 * @var t41_Object_Collection
	 */
	protected $_collection;
	
	
	/**
	 * Current data object
	 * @var t41_Data_Object
	 */
	protected $_do;
	
	
    public function render()
    {
    	$this->_collection = $this->_obj->getCollection();
    	
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
    	
    	// set query parameters from context
    	if (isset($this->_env[$this->_searchIdentifier]) && is_array($this->_env[$this->_searchIdentifier])) {

    		foreach ($this->_env[$this->_searchIdentifier] as $field => $value) {
    			
    			if (! empty($value)) { // @todo also test array values for empty values
    				
		    		$this->_collection->having($field)->contains($value);
    				
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
    	
        $this->_obj->query();        
        
        $p = '';
        

        $p = '<table class="t41 component list">';
        $p .= $this->_headlineRendering();
		$p .= $this->_contentRendering();
        $p .= '</table>';

        return $this->_headerRendering() . $p . $this->_footerRendering();
    }


    protected function _renderButton(Element\ButtonElement $button)
    {
    	$deco = View\Decorator::factory($button, array('size' => 'small'));
    	return $deco->render();
    }

    
    protected function _headerRendering()
    {
		if (trim($this->_obj->getTitle()) == '') {
			$title = 'List';
		} else {
			
			$title = $this->_escape($this->_obj->getTitle());
		}

		$status = ($this->_obj->getParameter('open_default') == true) ? 'open' : 'close';
    	$html_head = <<<HTML
<div class="t41_wrapper" id="{$this->_instanceof}_{$this->_obj->getId()}">
<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
<div class="content">
HTML;

    	return $html_head;
    }
    
    
    protected function _headlineRendering()
    {
    	$sort = null;
    	
       // build header line
       
        $line = '<tr>';
        
        $sortIdentifiers = (isset($this->_env[$this->_sortIdentifier]) && is_array($this->_env[$this->_sortIdentifier])) ? $this->_env[$this->_sortIdentifier] : array();
        
        if ($this->_obj->getParameter('selectable') === true) {
        	
        	$line .= '<th>&nbsp;</th>';
        }
        
        /* @var $val t41_View_Property_Abstract */
        foreach ($this->_obj->getColumns() as $val) {

        	if (array_key_exists($val->getId(), $sortIdentifiers)) {
        		
        		$by = ($sortIdentifiers[$val->getAltId()] == 'ASC') ? 'DESC' : 'ASC';
        		
        		// save correct sorting field reference to re-inject in uri after column construction
        		// @todo think twice ! this is crappy as hell!
        		$sort = array($val->getId(), $sortIdentifiers[$val->getId()]);
        		
        	} else {
        		
        		$by = 'ASC';
        	}
        	
            $line .= '<th><strong>';
            
            $this->_uriAdapter->setArgument($val->getId(), $by, $this->_sortIdentifier);
            
            $line .= sprintf('<a href="%s">%s</a></strong></th>'
            				, $this->_uriAdapter->makeUri()
            				, $this->_escape($val->getTitle())
            				);
            				
            $this->_uriAdapter->unsetArgument(null, $this->_sortIdentifier);
        }

        if (is_array($sort)) {
            $this->_uriAdapter->setArgument($sort[0], $sort[1], $this->_sortIdentifier);	
        }

        if (count($this->_obj->getEvents('row')) > 0) {
        	
        	$line .= str_repeat('<th>&nbsp;</th>', count($this->_obj->getEvents('row')));

        } else {
        	
        	$line .= '<th>&nbsp;</th>';
        }
        
        return $line . "</tr>\n";
    }

    
    protected function _contentRendering()
    {
        $i = 0;
        $p = '';
        
        // print out rows
        foreach ($this->_obj->getCollection()->getMembers() as $key => $this->_do) {
        	
        	$css = $i%2 == 0 ? 'odd' : 'even';
			$p .= sprintf('<tr data-member="%s" class="%s">', $key, $css);
        	$i++;
			
			if ($this->_obj->getParameter('selectable') === true) {

				// make list items selectable
				$p .= sprintf('<td><input type="checkbox" name="t41_selection[]" value="%s"/></td>'
							, $this->_do->getUri()->getIdentifier()
							 );
			}
			
			$altDec = (array) $this->_obj->getParameter('decorators');
			
            /* @var $property t41_Property_Abstract */
            foreach ($this->_obj->getColumns() as $column) {
            	
            	/* if a decorator has been declared for property/element, use it */
            	if (isset($altDec[$column->getId()])) {

            		$deco = new $altDec[$column->getId()]($this->_do->getProperty($column->getId()));
            		$p .= sprintf('<td>%s</td>', $deco->render());
            		continue;
            	}
            	
            	$attrib = '';//($property instanceof Property\CurrencyProperty) ? ' class="currency"' : null;
				
            	$property = $this->_do->getProperty($column->getParameter('property'));
            	
            	if ($column->getParameter('recursion')) { // instanceof Property\ObjectProperty) {

					foreach ($column->getParameter('recursion') as $recursion) {
						
							$property = $property->getValue(ObjectModel::DATA)->getProperty($recursion);
					}
            	}
            	
  				$value = $property->getDisplayValue();
  				          	 
            	$p .= "<td$attrib>" . $this->_escape($value) . '</td>';
            }

            $p .= '<td>';
            foreach ($this->_obj->getEvents('row') as $button) {
            	    	
            	$button->setParameter('uri', $this->_do->getUri());
                $p .= $this->_renderButton($button);
            }
            $p .= '</td></tr>' . "\n";
        }
        
        return $p;
    }
    
    
    protected function _footerRendering()
    {
    	$offset = $this->_obj->getParameter('offset');
    	$batch  = $this->_obj->getParameter('batch');
    	$max    = $this->_obj->getParameter('max');
    	$sbatch = 20;

        if ($max == 0) return '<div class="t41 paginator">Aucune fiche</div></div></div>';
    	
        foreach ($this->_env as $key => $val) {
        	
            if (is_array($val)) {
                foreach($val as $key2 => $val2) $this->baseUrl .= $key . '[' . $key2 . ']' . '=' . @rawurlencode($val2) . '&';
            } else if ($key != $this->_offsetIdentifier) {
            	$this->baseUrl .= $key . '=' . rawurlencode($val) . '&';
            }
        }


	   $premier = floor($offset/($batch * $sbatch)) * $sbatch;

	   $i = $j = 0;

	   $p = sprintf('<div class="t41 paginator">Fiches %s &agrave; %s sur %s<br/>'
	               , ($offset+1)
	               , ($offset + $batch > $max)
	                 ? $max : ($offset + $batch)
	               , $max
	   			   );

	   // no navigation links needed if total of rows is =< batch value
	   if ($max <= $batch) return $p . '</div></div></div>';

	   // add one page backward link if applicable
	   if ($offset > 0) {
	       $p .= $this->_navigationLink($offset - $batch, '<<');
	   }

	   // jump to minus ($sbatch value) * $batch
       if ($offset >= $sbatch * $batch) {
		  $p .= $this->_navigationLink($offset - ($sbatch * $batch), $sbatch . ' pr&eacute;c.');
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

	   return "$p</div></div>";
    }

    
    protected function _navigationLink($offset, $page)
    {
	   if ($offset != $this->_obj->getParameter('offset')) {
	   	
	   	  $this->_uriAdapter->setArgument($this->_offsetIdentifier, $offset);
	   	  return sprintf('<a href="%s" class="t41 paginator">%s</a> ', $this->_uriAdapter->makeUri(), $page);

	   } else {
	   	
		  return '<span class="t41 paginator current">' . $this->_escape($page) . '</span> ';
	   }
    }
}
