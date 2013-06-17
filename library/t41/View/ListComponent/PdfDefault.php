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

use t41\ObjectModel;
use t41\ObjectModel\Property\AbstractProperty;
use t41\View\Decorator\AbstractPdfDecorator;
use t41\View\ViewUri;
use t41\View\ListComponent\Element;
use t41\ObjectModel\Property\MetaProperty;
use t41\ObjectModel\Property;
use t41\ObjectModel\Property\ObjectProperty;
use t41\ObjectModel\DataObject;
use t41\View\Decorator;

/**
 * List view object default Web Decorator
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class PdfDefault extends AbstractPdfDecorator {

	
	protected $_instanceof = 't41\View\ListComponent';
		
	protected $_offsetIdentifier;
	
	protected $_sortIdentifier;
	
	protected $_searchIdentifier;
	
	
	protected $_cols;
	
	protected $_colsWidth = array();
	
	protected $_colsAlignment = array();
	
	protected $_groups;
	
	protected $_headerHeight = 10;
	
	protected $_cellHeight = 10;
	
	
	/**
	 * Array where user parameters can be found (typically $_GET or $_POST)
	 *
	 * @var array
	 */
	protected $_env;
	
	
	/**
	 * t41_Form_List instance
	 *
	 * @var t41_Form_List
	 */
	protected $_obj;

	
	/**
	 * t41\ObjectModel\Collection instance
	 *
	 * @var t41\ObjectModel\Collection
	 */
	protected $_collection;
	
	
    public function render(\TCPDF $pdf, $width)
    {
    	$this->_collection = $this->_obj->getCollection();
    	 
    	// set relevant uri adapter and get some identifiers 
    	if (! ViewUri::getUriAdapter() instanceof ViewUri\Adapter\GetAdapter) {
    		$this->_uriAdapter = new ViewUri\Adapter\GetAdapter();
    	} else {
    		$this->_uriAdapter = ViewUri::getUriAdapter();
    	}
    	
    	$this->_offsetIdentifier	= $this->_uriAdapter->getIdentifier('offset');
    	$this->_sortIdentifier		= $this->_uriAdapter->getIdentifier('sort');
    	$this->_searchIdentifier	= $this->_uriAdapter->getIdentifier('search');
    	
    	
    	// set data source for environment
    	$this->_env = $this->_uriAdapter->getEnv();
    	
    	// set query parameters from context
    	if (isset($this->_env[$this->_searchIdentifier]) && is_array($this->_env[$this->_searchIdentifier])) {

    		foreach ($this->_env[$this->_searchIdentifier] as $field => $value) {
    			
    			$field = str_replace("-",".",$field);

    			if (! empty($value) && $value != Property::EMPTY_VALUE) { // @todo also test array values for empty values
    				$property = $this->_collection->getDataObject()->getProperty($field);
    				
    				if ($property instanceof MetaProperty) {
    					$this->_collection->having($property->getParameter('property'))->contains($value);
    				} else if ($property instanceof ObjectProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->equals($value);
    				} else if ($property instanceof AbstractProperty) {
    					$this->_collection->resetConditions($field);
    					$this->_collection->having($field)->contains($value);
    				}
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

        // calculate columns & alignment for each column
        foreach ($this->_obj->getColumns() as $key => $column) {
        	// minimum column width should be based on label width
        	$this->_colsWidth[$key] = strlen($column->getTitle());        	
        	$this->_colsAlignment[$key] = $column->getParameter('align');
        }
        
        /* add optional extra columns */
        if ($this->getParameter('extra_cols') > 0) {
        	for ($i = 0 ; $i < $this->getParameter('extra_cols') ; $i++) {
        		$this->_colsWidth[] = 10;
        	}
        }

        $i = 0;
        $rows = $this->_obj->getCollection();
        $sampleLength = $rows->getTotalMembers() > 30 ? 30 : $rows->getTotalMembers();
        
        // now walk through a sample of all data rows to find longest value for each column
        while ($i < $sampleLength) {

        	$rnd = rand(0, $rows->getTotalMembers()-1);
        	
        	// get a random row
        	if (($data = $rows->getMember($rnd, ObjectModel::MODEL)) === false) continue;
        		
        	foreach ($this->_obj->getColumns() as $key => $column) {
        		$property = $data->getProperty($column->getParameter('property'));
        		 
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
        		 
        		$value = ($property instanceof AbstractProperty) ? $property->getDisplayValue() : null;
        		$content = explode("\n", $value);
        		
                $strlen = strlen($content[0]);

                if ($strlen > $this->_colsWidth[$key]) {
                    $this->_colsWidth[$key] = $strlen;
                }
        	}
        	
        	$i++;
        }

        $floor = array_sum($this->_colsWidth) * .05;
        
        // adds 50% to cols using less than 5% of total */
        foreach ($this->_colsWidth as $key => $colWidth) {
        	if ($colWidth > $floor) continue;
        	$this->_colsWidth[$key] = $colWidth * 1.5;
        }

        // compute total length of columns
        $fullLine = array_sum($this->_colsWidth);
        
        // attribute width in percent of total width
        foreach ($this->_colsWidth as $key => $colWidth) {
        	$this->_colsWidth[$key] = round($width * ($colWidth / $fullLine));
        }
        
        /* number of columns, useful to know when to terminate a row */
        $this->_cols = $cols = count($this->_colsWidth);
        
		/* draw header row */
        $this->_drawHeaderRow($pdf);
        
        /* number of data columns, useful to retrieve extra ones */
        $dcols = count($this->_obj->getColumns());

        /* calculate the value of the necessary space to draw a new cell */ 
        $increment = $this->_cellHeight + $this->_headerHeight;
        //if (! is_null($this->_groups)) $increment += $this->_headerHeight;
        
        /* print data */
        foreach ($this->_obj->getCollection()->getMembers(ObjectModel::DATA) as $this->_do) {
        	        
        	/* test if a new page is necessary */
        	if ($pdf->getY() + $increment > $pdf->getPageHeight() - 20) {
        		$pdf->AddPage();
		        $this->_drawHeaderRow($pdf);
        	}

        	$index = 0;
        	
        	foreach ($this->_obj->getColumns() as $key => $column) {
        		
        		if ($index+1 == $cols) {
        			$index = 0;
        			$nextPos = 1;
        		} else {
        			$nextPos = 0;
        			$index++;
        		}
        		 
        	    if ($column instanceof Element\IdentifierElement) {
            		$value = $this->_do->getUri()->getIdentifier();
            	} else if ($column instanceof Element\MetaElement) {
            		$attrib = ($column->getParameter('type') == 'currency') ? ' class="cellcurrency"' : null;
            		$p .= "<td$attrib>" . $column->getDisplayValue($this->_do) . '</td>';
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
            			$column->setValue($property->getDisplayValue());
            			$deco = Decorator::factory($column);
            			$value = $deco->render();
            		} else {
	  					$value = ($property instanceof Property\AbstractProperty) ? $property->getDisplayValue() : null;
            		}
            	}
        		
	            // put value in cell
    	        $pdf->Multicell($this->_colsWidth[$key], $this->_cellHeight, $value, 1, $this->_colsAlignment[$key], 0, $nextPos);
        	}
        	
            /* display optional extra cols */
        	if ($this->getParameter('extra_cols') > 0) {
        		for ($i = 0 ; $i < $this->getParameter('extra_cols') ; $i++) {
               		$pdf->Cell($this->_colsWidth[$dcols+$i], $this->_cellHeight, null, 1, (int) ($i+$index+1 == $cols));
        		}
        	}
        }
    }
    
    
    /**
     * Draw table header
     * @param TCPDF $pdf
     */
    protected function _drawHeaderRow(\TCPDF $pdf)
    {
/*    	if (count($this->_obj->getGroups()) > 0) {
    		
    		$this->_drawGroupRow($pdf);
    	}
*/    	
        $currentStyle = $pdf->getFontStyle();
        $pdf->SetFont($pdf->getFontFamily(), 'B');
        
        $index = 0;
        
        foreach ($this->_obj->getColumns() as $key => $colonne) {
        	
        	if ($index+1 == $this->_cols) {
        		$index = 0;
                $nextPos = 1; 
            } else {
                $nextPos = 0;
                $index++;
            }
                
            $pdf->Cell($this->_colsWidth[$key], $this->_headerHeight, $colonne->getTitle(), 1, $nextPos, 'C', 1);
        }
        
        /* display optional extra cols */
        if ($this->getParameter('extra_cols') > 0) {
        	/* number of data columns, useful to retrieve extra ones */
       	 	$dcols = count($this->_obj->getColumns());        	
        	
       	 	for ($i = 0 ; $i < $this->getParameter('extra_cols') ; $i++) {
        		$pdf->Cell($this->_colsWidth[$dcols+$i], 10, null, 1, (int) ($i+$index+1 == $this->_cols), 'L', 1);
        	}
        }
        
        $pdf->SetFont($pdf->getFontFamily(), $currentStyle);
    }
    
    
    /**
     * Draw groups upper row if groups have been defined
     * @param TCPDF $pdf
     */
    protected function _drawGroupRow(\TCPDF $pdf)
    {
    	if (is_null($this->_groups)) {
    	
    		$this->_groups = $this->_obj->getGroups();
    		
    		foreach ($this->_groups as $key => $group) {
    			foreach ($this->_colsWidth as $col => $width) {
    				if ($col >= $group['from'] && $col <= $group['to']) {
    					$this->_groups[$key]['width'] += $width;
    				}
    			}
    		}
    	}
    	
        $currentStyle = $pdf->getFontStyle();
        $pdf->SetFont($pdf->getFontFamily(), 'B');
    	
        $index = 0;
    	foreach ($this->_groups as $group) {
            
        	if ($index+1 == count($this->_groups)) {
        		$index = 0;
                $nextPos = 1; 
            } else {
                $nextPos = 0;
                $index++;
            }
            
    		$pdf->Cell($group['width'], $this->_headerHeight, utf8_encode($group['name']), 1, $nextPos, 'C', 1);
    	}
        $pdf->SetFont($pdf->getFontFamily(), $currentStyle);
    }
}
