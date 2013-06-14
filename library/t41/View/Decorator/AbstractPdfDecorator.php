<?php

namespace t41\View\Decorator;

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
 * @version    $Revision: 832 $
 */

use t41\View\ViewObject;
use t41\View\Adapter\PdfAdapter;
use t41\View\Decorator;
use t41\View\Decorator\AbstractDecorator;

/**
 * Class providing basic parameters and methods to PDF decorators.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
abstract class AbstractPdfDecorator extends AbstractDecorator {


	protected $_formattingData = array();
	
	protected $_width = 0;

	protected $_pdf;
	
	
	public function render(\TCPDF $pdf)
	{
		return $pdf;
	}
	
	
	/**
	 * Calculate each column width and behavior based on field description and average length of a data sample
	 * 
	 * @param array $rows		data to be sampled
	 */
	protected function _calculateColsWidth(array $rows = null)
	{
		$this->_formattingData = array();
		
	    // calculate columns & alignment for each column
        $colsWidth = $colAlignment = array();
        
        foreach ($this->_obj->getColumns() as $key => $column) {
        	
        	// minimum column width should be based on label width
        	$colsWidth[$key] = strlen($column->getTitle());
        	
        	switch (get_class($column)) {
        		
        		case 'CurrencyElement':
        			$align = PdfAdapter::ALIGN_RIGHT;
        			break;
        			
        		default:
        			$align = PdfAdapter::ALIGN_LEFT;
        			break;
        	}
        	
        	$colAlignment[$key] = $align;
        }
        
        $i = 0;
        $sampleLength = (count($rows) > 10) ? 10 : count($rows);
        
        
        // now walk through a sample of all data rows to find longest value for each column
        while ($i < $sampleLength) {

        	// get a random row
        	$data = $rows[rand(0, count($rows)-1)];
        		
        	foreach ($this->_obj->getColumns() as $key => $column) {

        		if (! isset($data[$column->getId()])) continue;
        		
        		// simply ignore objects
        		if ($data[$column->getAltId()] instanceof ViewObject) continue;
                    $strlen = strlen($column->formatValue($data[$column->getAltId()]));
                    if ($strlen > $colsWidth[$key]) {
                        $colsWidth[$key] = $strlen;
                    }                    
        	}
        	$i++;
        }

        // compute total length of columns
        $fullLine = array_sum($colsWidth);
        
        // attribute width in percent of total width
        foreach ($colsWidth as $key => $colWidth) {
        	$colsWidth[$key] = round($this->_width * ($colWidth / $fullLine));
        }

        $this->_formattingData['width'] = $colsWidth;
        $this->_formattingData['align'] = $colAlignment;
	}
	
	
	protected function _formatValue($field, $value, $width = null)
	{
		if ($value instanceof ViewObject) {
			$deco = Decorator::factory($value);
			$this->_pdf->setXY($this->_pdf->getX()+1, $this->_pdf->getY()+1);
			return $deco->render($this->_pdf, $width * .8);
		
		} else {
			return $field->formatValue($value);
		}
	}
}
