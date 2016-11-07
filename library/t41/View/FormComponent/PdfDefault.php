<?php

namespace t41\View\FormComponent;

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

use t41\ObjectModel\DataObject;

use t41\Parameter,
	t41\ObjectModel,
	t41\View\Decorator\AbstractPdfDecorator,
	t41\View\Decorator,
	t41\View\TableComponent,
	t41\View,
	t41\View\Adapter\PdfAdapter,
	t41\View\Exception;

/**
 * Decorator class for form objects in a PDF context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class PdfDefault extends AbstractPdfDecorator {
	
	
	protected $_borderType;
	
	
	public function __construct($obj, array $params = null)
	{
		$this->_setParameterObjects(array('borders' => new Parameter(Parameter::STRING)));
		
		parent::__construct($obj, $params);
	}
	
	
	/**
	 * 
	 * @param TCPDF $pdf
	 * @param integer $width
	 */
    public function render(\TCPDF $pdf, $width = null)
	{
		// Define border value from parameter
		if ($this->getParameter('borders')) {
			
			$this->_borderType = '';
			
			$bordersVal = array('T', 'L', 'R', 'B');
			$borders = explode(' ', $this->getParameter('borders'));
			
			foreach ($borders as $key => $border) {
				$this->_borderType .= ($border != 0) ? $bordersVal[$key] : null;
			}
		}
		
		
		$this->_width = $width;
		$this->_pdf = $pdf;
		

		// calculate first col width based on longest label
		$col1width = 0;
		foreach ($this->_obj->getColumns() as $field) {
			
			if (strlen($field->getTitle()) > $col1width) $col1width = strlen($field->getTitle());
		}
		
		$colRow = array();
		
		foreach ($this->_obj->getColumns() as $field) {
			
	        // draw header row
    	    $currentStyle = $this->_pdf->getFontStyle();
        	$this->_pdf->SetFont($this->_pdf->getFontFamily(), 'B');
			$this->_pdf->Cell($this->_width * .20
					 		, $this->_pdf->getFontSize()+2
					 		, $field->getTitle()
					 		, $this->_borderType
					 		, 0
					 		, PdfAdapter::ALIGN_RIGHT
					 		, 0 // fill
					 		);
					 
        	$this->_pdf->SetFont($this->_pdf->getFontFamily(), $currentStyle);
			
			$this->_pdf->MultiCell(0
							 		, $this->_pdf->getFontSize()+2
									, $field->formatValue($field->getValue())
							 		, $this->_borderType
							 		, PdfAdapter::ALIGN_LEFT
							 		  ); // draw a cell and go down
		}
	}
}
