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


use t41\ObjectModel,
	t41\ObjectModel\Property,
	t41\ObjectModel\Property\MetaProperty,
	t41\ObjectModel\Property\ObjectProperty,
	t41\View\Decorator\AbstractWebDecorator,
	t41\View,
	t41\View\ViewUri,
	t41\View\Decorator,
	t41\View\FormComponent\Element\ButtonElement,
	t41\View\ListComponent\Element;
use t41\ObjectModel\Property\AbstractProperty;
use t41\ObjectModel\DataObject;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\Property\DateProperty;
use t41\ObjectModel\Collection\StatsCollection;
use t41\ObjectModel\Property\ArrayProperty;
use t41\ObjectModel\BaseObject;

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
	 * @var t41\View\Uri\AbstractAdapter
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
	
	
	/**
	 * Current data object
	 * @var t41\ObjectModel\DataObject
	 */
	protected $_do;
	
	
	protected $_key;
	
	protected $_uuid;
	
	
    public function render()
    {
    	View::addCoreLib(array('style.css','buttons.css','sprites.css'));
    	View::addCoreLib(array('core.js','view.js','view:alert.js'));
    	
    	
    	// @todo temp fix, sorting by headers is only available on page refresh
    	if ($this->getParameter('paginator') == false) {
    		$this->setParameter('sortable', false);
    	}
    	
    	$tmp = $this->_obj->reduce();
    	$this->_uuid = $tmp['uuid'];
    	
    	// set relevant uri adapter and get some identifiers
    	/* @var $_uriAdapter t41\View\ViewUri\AbstractAdapter */
    	if (! ViewUri::getUriAdapter() instanceof ViewUri\Adapter\GetAdapter ) {
    		$tmp = explode('?', $_SERVER['REQUEST_URI']);
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter($tmp[0]);
    	} else {
    		$this->_uriAdapter = ViewUri::getUriAdapter();
    	}
    	
    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	
    	// try and restore cached search terms for the current uri
    	$this->_uriAdapter->restoreSearchTerms();

    	$this->_env = $this->_uriAdapter->getEnv();
    	
    	// set query parameters from context
    	if (isset($this->_env[$this->_searchIdentifier]) && is_array($this->_env[$this->_searchIdentifier])) {
    		foreach ($this->_env[$this->_searchIdentifier] as $field => $value) {
    			$field = str_replace("-",".",$field);

    			if (! empty($value) && $value != Property::EMPTY_VALUE) { // @todo also test array values for empty values
    				$property = $this->_obj->getCollection()->getDataObject()->getRecursiveProperty($field);
    				
    				if ($property instanceof MetaProperty) {
    					$this->_obj->getCollection()->having($property->getParameter('property'))->contains($value);
    				} else if ($property instanceof ObjectProperty) {
    					$this->_obj->getCollection()->resetConditions($field);
    					$this->_obj->getCollection()->having($field)->equals($value);
    				} else if ($property instanceof DateProperty) {
    					if (is_array($value)) {
    						if (isset($value['from']) && ! empty($value['from'])) {
    							$this->_obj->getCollection()->having($field)->greaterOrEquals($value['from']);
    						}
    						if (isset($value['to']) && ! empty($value['to'])) {
    							$this->_obj->getCollection()->having($field)->lowerOrEquals($value['to']);
    						}
    					} else {
    						$this->_obj->getCollection()->having($field)->equals($value);
    					}
    				} else if ($property instanceof AbstractProperty) {
    					$this->_obj->getCollection()->resetConditions($field);
    					$this->_obj->getCollection()->having($field)->contains($value);
    				}
	    			$this->_uriAdapter->setArgument($this->_searchIdentifier . '[' . $field . ']', $value);
    			}
    		}
    	}
    	
    	// set query sorting from context
        if (isset($this->_env[$this->_sortIdentifier]) && is_array($this->_env[$this->_sortIdentifier])) {
        	foreach ($this->_env[$this->_sortIdentifier] as $field => $value) {
    			$this->_obj->getCollection()->setSorting(array($field, $value));
    		}
    	}

    	// define offset parameter value from context
    	if (isset($this->_env[$this->_offsetIdentifier])) {
    		$this->_obj->setParameter('offset', (int) $this->_env[$this->_offsetIdentifier]);
    		$this->_obj->getCollection()->setBoundaryOffset($this->_env[$this->_offsetIdentifier]);
    	}
    	
        if (! $this->_obj->getCollection() instanceof StatsCollection) {
	    	$this->_obj->query();
        }
            
        $p = '';
        
        $p = $this->_headerRendering();
        $p .= sprintf('<table class="t41 list" id="%s">', $this->getId());
        $p .= $this->_headlineRendering();
        
		$p .= $this->_contentRendering();
        $p .= '</table>';

        // inject extra content
        $p .= '<div class="actions">' . parent::_contentRendering() . '</div>';
        
        if ($this->getParameter('paginator') !== false) {
        	$p .= $this->_footerRendering();
        } else {
        	$p .= '</div></div>';
        }
        	 
        return $p;
    }


    /**
     * Returns a rendered button
     * @param ButtonElement $button
     * @return string
     */
    protected function _renderButton(ButtonElement $button)
    {
    	$params = $button->getDecoratorParams();
    	$params['size'] = 'medium';
    	$params['data'] = array(
    							'member' => $this->_key, 
    							'uuid' => $this->_uuid ,
    							'id' => $this->_do->getUri() ? $this->_do->getUri()->getIdentifier() : null
    						   );
    	$deco = View\Decorator::factory($button, $params);
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
<div class="{$this->getParameter('css')}" id="{$this->_instanceof}_{$this->_obj->getId()}">
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
        
        if ($this->_obj->getParameter('selectable') !== false) {
        	$cbid = 'toggler_' . $this->_id;
        	$line .= sprintf('<td><input type="checkbox" id="%s"/></td>', $cbid);
        	
        	View::addEvent(sprintf('t41.view.bindLocal(jQuery("#%s"), "change", function() { t=jQuery("#%s").attr("checked");jQuery("#%s").find(\':checkbox[id=""]\').each(function(i,o){o.checked=t});})'
        					, $cbid
        					, $cbid
        					, $this->_id
        					), 'js');
        }
        
        foreach ($this->_obj->getColumns() as $val) {
        	$line .= sprintf('<th class="tb-%s"><strong>', $val->getId());
        	if ($this->getParameter('sortable') == false) {
        		$line .= $this->_escape($val->getTitle()) . '</strong></th>';
        		continue;
        	}
        	
        	if (is_object($val) && array_key_exists($val->getId(), $sortIdentifiers)) {
        		$by = ($sortIdentifiers[$val->getId()] == 'ASC') ? 'DESC' : 'ASC';
        		
        		// save correct sorting field reference to re-inject in uri after column construction
        		// @todo think twice ! this is crappy as hell!
        		$sort = array($val->getId(), $sortIdentifiers[$val->getId()]);
        		
        	} else {
        		$by = 'ASC';
        	}
        	
            if (is_object($val)) $this->_uriAdapter->setArgument($val->getId(), $by, $this->_sortIdentifier);
            
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
        	$line .= '<th class="tb-actions">&nbsp;</th>';
        } else {
        	$line .= '<th class="tb-actions">&nbsp;</th>';
        }
        
        return $line . "</tr>\n";
    }

       
    protected function _contentRendering()
    {
        $i = 0;
        $p = '';
        
        // print out rows
        foreach ($this->_obj->getCollection()->getMembers(ObjectModel::DATA) as $this->_key => $this->_do) {
        	$css = $i%2 == 0 ? 'odd' : 'even';
        	
        	// @todo handle objects coming from different backends
			$p .= sprintf('<tr data-member="%s" data-id="%s" class="%s">'
					, $this->_key
					, $this->_do->getUri() ? $this->_do->getUri()->getIdentifier() : $this->_key
					, $css
        		  );
        	$i++;
			
			if ($this->_obj->getParameter('selectable') === true) {
				// make list items selectable
				$p .= sprintf('<td><input type="checkbox" name="t41_selection[]" value="%s"/></td>'
							, $this->_do->getUri()->getIdentifier()
							 );
			}
			
			$altDec = (array) $this->_obj->getParameter('decorators');
            foreach ($this->_obj->getColumns() as $column) {
            	if ($column instanceof Element\IdentifierElement) {
            		$p .= sprintf('<td>%s</td>', $this->_do->getUri()->getIdentifier());
            		continue;
            	}
            	if ($column instanceof Element\MetaElement) {
            		$attrib = ($column->getParameter('type') == 'currency') ? ' class="cellcurrency"' : null;
            		$p .= "<td$attrib>" . $column->getDisplayValue($this->_do) . '</td>';
            		continue;
            	}
            	$property = $this->_do->getProperty($column->getParameter('property'));
            	if (! $property) {
            		$p .= '<td>??</td>';
            		continue;
            	}
            	
            	$column->setValue($property->getValue());
            	
            	/* if a decorator has been declared for property/element, use it */
            	if (isset($altDec[$column->getId()])) {
            		$column->setDecorator($altDec[$column->getId()]);
            		$deco = Decorator::factory($column);
            		$p .= sprintf('<td>%s</td>', $deco->render());
            		continue;
            	}
            	
            	$attrib = sprintf(' class="tb-%s', $column->getId());
            	$attrib .= ($property instanceof Property\CurrencyProperty) ? ' cellcurrency"' : '"';
            	 
            	if ($column->getParameter('recursion')) {
            		$parts = $column->getParameter('recursion');
					foreach ($parts as $rkey => $recursion) {
						
						if ($property instanceof ArrayProperty) {
							// property won't be a property here !
							$property = $property->getValue();
							$property = $property[$recursion];
							if ($property instanceof BaseObject && isset($parts[$rkey+1])) {
								$property = $property->{$parts[$rkey+1]};
							}
							break;
						}
						
						// property is an object property
						if ($property instanceof AbstractProperty && $property->getValue()) {
							$property = $property->getValue(ObjectModel::DATA)->getProperty($recursion);
						}
						
						if ($property instanceof ObjectModel || $property instanceof DataObject) {
							$property = $property->getProperty($recursion);
						} else if ($property instanceof ObjectUri) {
							$property = ObjectModel::factory($property)->getProperty($recursion);
						}
					}
					
					//\Zend_Debug::dump($property->getDisplayValue());
            	}
            	
            	if ($property instanceof Property\MediaProperty) {
            		$column->setValue($property->getDisplayValue());
            		$deco = Decorator::factory($column);
            		$value = $deco->render();
            	} else {
	  				$value = ($property instanceof Property\AbstractProperty) ? $property->getDisplayValue() : $property;
            	}
            	//$p .= "<td$attrib>" . $this->_escape($value) . '</td>';
            	$p .= "<td$attrib>" . $value . '</td>';
            }

            $p .= '<td class="tb-actions">';
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
