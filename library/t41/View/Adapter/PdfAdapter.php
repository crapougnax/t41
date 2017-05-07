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
 * @copyright  Copyright (c) 2006-2017 Quatrain Technologies SAS
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\View;

/**
 * Class providing the view engine with a PDF-context adapter.
 * For more details on the PDF file format, see adobe.org
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SAS
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class PdfAdapter extends AbstractAdapter {


	const ID = 'Pdf';
	
	const ORIENTATION_PORTRAIT = 'P';
	
	const ORIENTATION_LANDSCAPE = 'L';
	
	
	const ALIGN_LEFT = 'L';
	
	const ALIGN_CENTER = 'C';
	
	const ALIGN_RIGHT = 'R';
	
	const FORMAT_A4 = 'A4';
	
	const FORMAT_A3	= 'A3';

	
	protected $_context = 'Pdf';
	
	protected $_width;
	
	protected $_document;
	
	
	public function __construct(array $parameters = null)
	{
		if (! defined('K_PATH_IMAGES')) {
			define ('K_PATH_IMAGES', '');
		}
		
		$params = [];
		
		$params['format']	 	= new \t41\Parameter(\t41\Parameter::STRING, self::FORMAT_A4, false, array(self::FORMAT_A3, self::FORMAT_A4));
		$params['orientation'] 	= new \t41\Parameter(\t41\Parameter::STRING, self::ORIENTATION_PORTRAIT, false, array(self::ORIENTATION_PORTRAIT, self::ORIENTATION_LANDSCAPE));
		$params['destination']	= new \t41\Parameter(\t41\Parameter::STRING, 'D');
		$params['copies']		= new \t41\Parameter(\t41\Parameter::INTEGER, 1);
		
		$params['fontSize']		= new \t41\Parameter(\t41\Parameter::ANY, 9);
		$params['fontName'] 	= new \t41\Parameter(\t41\Parameter::STRING, 'Helvetica');
		$params['title'] 		= new \t41\Parameter(\t41\Parameter::STRING);
		$params['author'] 		= new \t41\Parameter(\t41\Parameter::STRING);
		$params['bookmarks']	= new \t41\Parameter(\t41\Parameter::BOOLEAN, false);
		
		$params['logo'] 		= new \t41\Parameter(\t41\Parameter::STRING);
		
		$params['fillColor']	= new \t41\Parameter(\t41\Parameter::INTEGER, 220);
		$params['drawColor']	= new \t41\Parameter(\t41\Parameter::INTEGER, 0);

		$params['tableBorders']	= new \t41\Parameter(\t41\Parameter::STRING, '1 1 1 1');
		
		$params['headerMargin'] = new \t41\Parameter(\t41\Parameter::INTEGER, 10);
		$params['headerFont']	= new \t41\Parameter(\t41\Parameter::STRING);
		$params['headerFontSize']	= new \t41\Parameter(\t41\Parameter::INTEGER);
		
		$params['footerMargin'] = new \t41\Parameter(\t41\Parameter::INTEGER, 10);
		$params['footerFont']	= new \t41\Parameter(\t41\Parameter::STRING);
		$params['footerFontSize']	= new \t41\Parameter(\t41\Parameter::INTEGER);
		
		$params['marginTop']	= new \t41\Parameter(\t41\Parameter::INTEGER, 20);
		$params['marginLeft']	= new \t41\Parameter(\t41\Parameter::INTEGER, 20);
		$params['marginRight']	= new \t41\Parameter(\t41\Parameter::INTEGER, 20);
		$params['marginBottom']	= new \t41\Parameter(\t41\Parameter::INTEGER, 20);
		
		$params['addHeader']	= new \t41\Parameter(\t41\Parameter::BOOLEAN, true);
		$params['addFooter']	= new \t41\Parameter(\t41\Parameter::BOOLEAN, true);
		
		$params['autoPageBreak']	= new \t41\Parameter(\t41\Parameter::BOOLEAN, true); 
		
		$this->_setParameterObjects($params);
		
		parent::__construct($parameters);
	}
	
	
    public function display()
    {
        $this->_document = new \TCPDF(
            $this->getParameter('orientation'), 
            'mm', 
            $this->getParameter('format'), 
            true
        );
        
        $this->_document->setPrintHeader($this->getParameter('addHeader'));
        $this->_document->setPrintFooter($this->getParameter('addFooter'));
        
        // set document information
        $this->_document->SetCreator('t41 using ' . get_class($this->_document));
        
        $this->_document->SetAuthor($this->getParameter('author'));

        $this->_document->SetTitle($this->getParameter('title'));
        $this->_document->SetSubject($this->getParameter('title'));

        //set auto page breaks
        $this->_document->SetAutoPageBreak($this->getParameter('autoPageBreak'), $this->getParameter('marginBottom'));
        
        // header and footer declaration
        if ($this->getParameter('headerMargin') > 0) {
        	
        	if ($this->getParameter('logo')) {
        		$imgformat = getimagesize($this->getParameter('logo'));
        		$this->setParameter('marginTop', 50);// (int) round($this->getParameter('marginTop') + $imgformat[1]/7));
	       		$this->setParameter('headerMargin', 50); //(int) ($this->getParameter('marginTop') + ($imgformat[1]/7)));
        		$this->_document->setHeaderData($this->getParameter('logo'), $imgformat[0]/4);
        	} else {
            
	        	$this->_document->setHeaderMargin($this->getParameter('headerMargin'));
    	    	$this->_document->setHeaderData('','', utf8_encode($this->getParameter('title')));
        	}
        }
        
        $this->_document->SetFooterMargin($this->getParameter('footerMargin'));

        //set margins
        $this->_document->SetMargins( $this->getParameter('marginLeft')
        		, $this->getParameter('marginTop')
        		, $this->getParameter('marginRight')
        );
        
        
        // font definition
        if ($this->getParameter('fontName')) {
        	
	        $this->_document->setHeaderFont([
	            $this->getParameter('fontName'), 
	            '',
	            $this->getParameter('headerFontSize') ?? $this->getParameter('fontSize') - 2
	        ]);
    	    $this->_document->setFooterFont([
    	        $this->getParameter('fontName'), 
    	        '',
    	        $this->getParameter('footerFontSize') ?? $this->getParameter('fontSize') - 2
    	    ]);
        	$this->_document->SetFont($this->getParameter('fontName'));
        }
        
        // colors definition
        $this->_document->SetFillColor($this->getParameter('fillColor'));
        $this->_document->SetDrawColor($this->getParameter('drawColor'));
        $this->_document->setLanguageArray([
            'a_meta_charset'  => 'UTF-8',
            'a_meta_dir'      => 'ltr',
            'a_meta_language' => 'fr',
            'w_page'          => 'page'
        ]);
                                     
        // default display size is 100%                             
        $this->_document->SetDisplayMode(100);

        // largeur utile de la page
        $this->_width = $this->_document->getPageWidth() - (PDF_MARGIN_LEFT + PDF_MARGIN_RIGHT);
        
        $this->_document->SetFontSize($this->getParameter('fontSize'));
        
        //initialize document
        $this->_document->getAliasNbPages();
        $this->_document->AddPage();    
        
        if ($this->_template) {
        	// document mode
        	$this->_();
        } else {
        	// registered objects rendering mode
        	$this->_render();
        }
        
        // duplicate pages x times if parameter is set to
        if ($this->getParameter('copies') > 1) {
        	$total = $this->_document->getNumPages()+1;
        	for ($i = 1 ; $i < $this->getParameter('copies') ; $i++) {
        		for ($j = 1 ; $j < $total ; $j++) {
        			$this->_document->copyPage($j);
        		}
        	}
        }
        
        
        $doc = $this->getParameter('title') ? str_replace('/', '-', $this->getParameter('title')) . '.pdf' : 'Export.pdf';
        return $this->_document->Output($doc, $this->getParameter('destination'));
    }
    
    protected function _render()
    {
		$newpage = false;
    	$elems = View::getObjects(View::PH_DEFAULT);
    	
    	if (is_array($elems)) {
    		
    		foreach ($elems as $key => $elem) {
    		
				$object = $elem[0];
    			$params = $elem[1];

    			if ($this->getParameter('bookmarks') === true && ($key == 0 || $object->getParameter('newpage') == 'after') && $object->getTitle()) {
	    			$this->_document->Bookmark($object->getTitle());
	    		}
	    		
    			$params['borders'] = $this->getParameter('tableBorders');
    			
    			try {
   					$decorator = \t41\View\Decorator::factory($object, $params);
    				} catch (Exception $e) {
    					/* @todo create new t41_View_Error object with exception message */
    					$this->_document->Write($e->getMessage());
    				}
    		    			
    			// check wether we need to jump to a new page
    			if (in_array($decorator->getParameter('newpage'), array('before','both')) || $newpage === true) {
    				
	    				$this->_document->AddPage();
	    				if ($this->getParameter('bookmarks') === true) {
	    					$this->_document->Bookmark($object->getTitle());
	    				}
	    				
	    				$newpage = false;
    			}
    			
    			$decorator->render($this->_document, $this->_width);
 			
    		    if (in_array($decorator->getParameter('newpage'), array('after','both'))) {
    		    	// new page will be added only if it is necessary (other view elements to print)
    				$newpage = true;
    				
    			} else {
	    			// new line
    				$this->_document->Ln();
    			}
    		}
    	}
    }
    
    
    protected function _()
    {
    	$template = file_get_contents($this->_template);
   	
    	// transform some characters
    	$template = str_replace("\t", str_repeat("&nbsp;", 15), $template);
    	$template = str_replace("\n", "<br/>", $template);
    	
    	$tagPattern = "/%([a-z0-9]+)\\:([a-z0-9.]*)\\{*([a-zA-Z0-9:,\\\"']*)\\}*%/";
    	
    	$tags = array();
    	
    	preg_match_all($tagPattern, $template, $tags, PREG_SET_ORDER);
    	
    	// PHASE 1
    	foreach ($tags as $key => $tag) {
    		
    		$content = '';
    		
    		if($tag[1] == 'env') {
    			$content = \t41\Core::htmlEncode(\t41\View::getEnvData($tag[2]));
    			unset($tag[$key]);
    		}
    		
       		if (isset($tag[0])) {
       			$template = str_replace($tag[0], $content, $template);
       		}
    	}
    	
    	$this->_document->writeHTML($template);
    	
    	// PHASE 2 - other tags
        	foreach ($tags as $tag) {
    		
    		$content = '';
    		
    		switch ($tag[1]) {
    			
    			case 'container':
    				$elems = \t41\View::getObjects($tag[2]);
    				if (is_array($elems)) {
    					foreach ($elems as $elem) {
    						$object = $elem[0];
    						$params = $elem[1];
    						if (! is_object($object)) {
    						    continue;
    						}
    						
    					    /* @var $object t41_Form_Abstract */ 
    						switch (get_class($object)) {
    				
			    				case 't41_Form_List':
    							case 't41_View_Table':
    							case 't41_View_Image':
    							case 't41_View_Component':
    							case 't41_View_Error':
    							case 't41_View_Spacer':
    								$decorator = \t41\View\Decorator::factory($object, $params);
    								$decorator->render($this->_document, $this->_width);
    								break;
    					
    							default:
    								break;
    						}
    					}
    				}
    				break;
    		}
    	}
    }
    
    
    public function addPage()
    {
        $this->_document->AddPage(); 
    }
    
    
    public function setTitle($title)
    {
    	$this->setParameter('title', $title);
    }
    
    
    public function isNewPage()
    {
    	return ($this->_document->getY() == $this->getParameter('marginTop')); 
    }
}
