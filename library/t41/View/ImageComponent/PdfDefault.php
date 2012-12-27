<?php

namespace t41\View\ImageComponent;

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

use t41\Core, t41\View, t41\View\Decorator\AbstractPdfDecorator;

/**
 * Decorator class for image objects in a PDF context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class PdfDefault extends AbstractPdfDecorator {
	
	
	protected $_instanceof = 't41\View\ImageComponent';
	
		
    public function render(\TCPDF $pdf, $width = null)
	{
		$image = $this->_obj->getContent();
		$imgSize = getimagesize(Core::$basePath . $image);
			
		$height = $pdf->pixelsToUnits($imgSize[1]);
		$width  = $pdf->pixelsToUnits($imgSize[0]);
		
		$pdf->setJPEGQuality(75);
		
		if ($this->_obj->getParameter('pos_x') || $this->_obj->getParameter('pos_y')) {
			$currentY = $pdf->getY();
			$currentAPB = $pdf->getAutoPageBreak();
			$currentMB = $pdf->getMargins();
			$currentMB = $currentMB['bottom'];
			$pdf->setAutoPageBreak(false, 0);
			$pdf->write('');
		}
		
		$pdf->Image(  Core::$basePath . $image
					, $this->_obj->getParameter('pos_x') ? $pdf->pixelsToUnits($this->_obj->getParameter('pos_x')) : null
					, $this->_obj->getParameter('pos_y') ? $pdf->pixelsToUnits($this->_obj->getParameter('pos_y')) : null
					, $width * $this->_obj->getParameter('ratio')
					, $height * $this->_obj->getParameter('ratio')
					, null // img type
					, $this->_obj->getParameter('link')
					, 'N'
				  );
		
		if (isset($currentY)) {
			$pdf->setY($currentY);
			$pdf->setAutoPageBreak($currentAPB, $currentMB);
		}
	}
}
