<?php

namespace t41\View\Adapter;

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
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\Parameter;
use t41\Core;
use Dispam\Exception;

/**
 * Class providing the view engine with a Pdf context adapter.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2013 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Pdf2Adapter extends WebAdapter {

	
	const ID = 'Pdf2';
	
	
	public function __construct(array $parameters = array())
	{
		$this->_setParameterObjects(array(
					'orientation' 	=> new Parameter(Parameter::STRING, PdfAdapter::ORIENTATION_PORTRAIT, false, array(PdfAdapter::ORIENTATION_PORTRAIT, PdfAdapter::ORIENTATION_LANDSCAPE)),
					'copies'		=> new Parameter(Parameter::INTEGER, 1),
					'title' 		=> new Parameter(Parameter::STRING)
				));

		parent::__construct($parameters);
	}
	

	/**
	 * wkhtmltopdf command should be available in the vendor/bin/ folder
	 * if composer is used to get the sources from message/wkhtmltopdf in the project vendor/ folder
	 * @see \t41\View\Adapter\WebAdapter::display()
	 */
	public function display($content = null, $error = false)
	{
		error_reporting(0);
		$html = parent::display($content, $error);
		$unames = posix_uname();
		$ext = $unames['machine'] == 'x86_64' ? 'amd64' : 'i386';
		$bin = Core::$basePath . 'vendor/bin/wkhtmltopdf-' . $ext;
        
        if (! is_executable($bin)) {
        	throw new Exception("Missing or not executable $bin");
        }
        
        if ($this->getParameter('orientation') == PdfAdapter::ORIENTATION_LANDSCAPE) {
    		$bin .=  ' --orientation Landscape';
    	}

    	if ($this->getParameter('copies') > 1) {
    		$bin .=  ' --copies ' . $this->getParameter('copies');
    	}
    	
    	$dir = '/dev/shm/';
    	
    	$key = hash('md5', $html);
    	
    	file_put_contents($dir . $key . '.html', $html);
    	
    	exec(sprintf("%s %s%s.html %s%s.pdf", $bin, $dir, $key, $dir, $key));
    	
    	$doc = $this->getParameter('title') ? str_replace('/', '-', $this->getParameter('title')) . '.pdf' : 'Export.pdf';

    	header('Content-Type: application/pdf');
    	header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
    	header('Pragma: public');
    	header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    	header('Content-Disposition: inline; filename="' . $doc . '";');
    	header('Content-Length: ' . filesize($dir . $key . '.pdf'));
    	echo file_get_contents($dir . $key . '.pdf');
    	
    	unlink($dir . $key . '.html');
    	unlink($dir . $key . '.pdf');
	}
}
