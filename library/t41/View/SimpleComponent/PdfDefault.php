<?php

namespace t41\View\SimpleComponent;

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

use t41\Core,
	t41\View,
	t41\View\Decorator\AbstractPdfDecorator;

/**
 * Decorator class for template objects in a PDF context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

class PdfDefault extends AbstractPdfDecorator {

	
	protected $_instanceof = 't41\View\SimpleComponent';
		
	
	/**
	 * Turn a HTML-based template into a PDF element
	 * 
	 * @param TCPDF $pdf
	 * @param integer $width
	 */
    public function render(\TCPDF $pdf, $width = null)
	{
		$pdf->writeHTML($this->_contentRendering()); // writeHTMLCell(null, null, $this->_obj->getParameter('pos_x'), $this->_obj->getParameter('pos_y'),);
	}
}
