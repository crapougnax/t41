<?php

namespace t41\View\TemplateComponent;

use t41\ObjectModel\ObjectUri;

use t41\View\Decorator;

use t41\View\ViewObject;

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

use t41\ObjectModel\BaseObject;

use t41\ObjectModel\Property\AbstractProperty;

use t41\Core,
	t41\View,
	t41\View\Decorator\AbstractWebDecorator;
use t41\ObjectModel\MediaObject;
use t41\View\FormComponent\Element\MediaElement;

/**
 * Decorator class for template objects in a web context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {
	
	
	const TAG_START = "%";
	
	const TAG_END	= "%";
	
	
	protected $_instanceof = 't41\View\TemplateComponent';
	
	
	/**
	 * Template content
	 * 
	 * @var string
	 */
	protected $_template;
		
	
	/**
	 * Parse a HTML template
	 * 
	 * @return string
	 */
    public function render()
	{
    	$this->_template = $this->_obj->getTemplate();
   	
    	$tagPattern = "/" . self::TAG_START . "([a-z0-9]+)\\:([a-z0-9_.]*)\\{*([a-zA-Z0-9:_,\\\"']*)\\}*" . self::TAG_END . "/";
    	
    	$tags = array();
    	
    	preg_match_all($tagPattern, $this->_template, $tags, PREG_SET_ORDER);

    	// transform some characters (if template is not an html snippet)
    	if (strlen($this->_template) == strlen(strip_tags($this->_template))) {
	    	$this->_template = str_replace("\t", str_repeat("&nbsp;", 12), $this->_template);
    		$this->_template = str_replace("\n", "<br/>", $this->_template);
    	}	
		$this->_parseTags($tags);
    	
    	return $this->_template;
	}
	
	
	/**
	 * Parse given tags against current template
	 * 
	 * @param array $tags
	 */
	protected function _parseTags($tags)
	{
	    foreach ($tags as $tag) {
    		
	    	$value = null;
    		
    		switch($tag[1]) {
    			
    			case 'var':
	    			$keys = explode('.', $tag[2]);
    				$value = $this->_obj->getVariable($keys[0]);
    			
	    			if (count($keys) > 1) {
   						$value = $value[$keys[1]];
   					}
   					break;
    				
   				case 'env':
   					$value = View::getEnvData($tag[2]);
   					break;
   					
   				case 'container':
   					if (($templates = $this->_obj->getSubtemplates($tag[2])) !== false) {
   						$value = '';
   						foreach ($templates as $template) {
   							$deco = Decorator::factory($template);
   							$value .= $deco->render();
   						}
   					} 
   					break;
   					
   				default: // obj:
   					$tmp = explode('.', $tag[2]);
   					$obj = $this->_obj->getVariable($tmp[0]);
   					if ($obj instanceof MediaObject) {
   						// meta properties handling
   						switch ($tmp[1]) {
   							
   							case '_icon':
   								$value = 'file-' . $obj->getExtension();
   								break;
   								
   							case '_size':
   								$value = MediaElement::getDisplaySize($obj->getSize());
   								break;
   								
   							case '_url':
   								$value = MediaElement::getDownloadUrl($obj->getUri());
   							default:
   								break;
   						}
   					}
   					
   					if (! $value) {
	   					if ($obj instanceof BaseObject) {
		   					$value = $tmp[1] == ObjectUri::IDENTIFIER ? $obj->getIdentifier() : $obj->getProperty($tmp[1]);
   							$value = ($value instanceof AbstractProperty)  ? $value->getDisplayValue() : $value;
   						} else {
   							$value = Core::$env == Core::ENV_DEV ? sprintf("Can't substitute any value to '%s'", $tag[0]) : null;
   						}
   					}
   					break;
   			}
   			
   			if ($value instanceof ViewObject) {
   				$deco = Decorator::factory($value);
   				$value = $deco->render();
   			} else {
   				//$value = $this->_escape($value);
   			}
    				
       		$this->_template = str_replace($tag[0], $value, $this->_template);
	    }
	}
}
