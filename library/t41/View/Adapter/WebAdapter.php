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
 * @copyright  Copyright (c) 2006-2014 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

use t41\View;
use t41\Core;
use t41\Parameter;
use t41\ObjectModel\BaseObject;
use t41\ObjectModel\Property\AbstractProperty;
use t41\ObjectModel\ObjectUri;

/**
 * Class providing the view engine with a Web context adapter.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2014 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebAdapter extends AbstractAdapter {

	
	const ID = 'Web';
	
	
	protected $_context = 'Web';
	
	protected $_allowedComponents = array('css', 'js');
	
	protected $_componentDependancies = array('js' => array('css'));

	protected $_allowedEvents = array('js', 'css');
	
	protected $_displayContexts = array('popup', 'ajax');
	
	protected $_componentsBasePath = 'html';
	

	public function __construct(array $parameters = null)
	{
		$this->_setParameterObjects(array(
			'js_documentready' => new Parameter(Parameter::BOOLEAN, true)
		));
		parent::__construct($parameters);
	}

	
	/**
	 * Add a library to the page after some checks
	 *
	 * @param string $file
	 * @param string $type
	 * @return boolean
	 */
    public function componentAdd($file, $type, $lib = null, $priority = 0)
    {
        if (! in_array($type, $this->_allowedComponents)) {
    		return false;
    	}
    	
        if (substr($file, 0, 4) != 'http' && substr($file, 0, 4) != '/t41') {
	        if ($lib) {
		        $filePath = '/lib/' . $lib . '/' . $type . '/' . $file . '.' . $type;
        	} else {
        		$filePath = '/' . $type . '/' . $file . '.' . $type;
        	}
        } else {
        	$filePath = $file;
        }
        
        // return false if component is already listed
        if (in_array($filePath, $this->_component[$type])) {
        	return false;
        }

        if (substr($filePath, 0, 4) == 'http' || substr($filePath, 0, 4) == '/t41') {
        	if (($cacheassets = Core::getEnvData('cache_assets')) != false) {
	        	if (strstr($filePath, '/t41/') !== false) {
    	    		$filePath = $this->minify($filePath, $cacheassets);
	        	}
        	}
        	
        	// return false if component is already listed
        	if (in_array($filePath, $this->_component[$type])) {
        		return false;
        	}
        	
        	if ($priority == -1) {
        		array_unshift($this->_component[$type], $filePath);
        	} else {
	           $this->_component[$type][] = $filePath;
        	}
           
        } else if (file_exists(Core::$basePath . $this->_componentsBasePath . $filePath)) {
            if ($priority == -1) {
        		array_unshift($this->_component[$type], $filePath);
        	} else {
	           $this->_component[$type][] = $filePath;
        	}
           if (isset($this->_componentDependancies[$type])) {
           		foreach ($this->_componentDependancies[$type] as $chainedType) {
           			$this->componentAdd($file, $chainedType, $lib, $priority);
           		}
           }
           return true;
        }
        return false;
    }
    
    
    public function mediaAdd($file, $lib = null)
    {
    	if (substr($file, 0, 1) != '/') $file = '/' . $file;
    	
    	if ($lib == 't41') {
    		
    		$file = '/t41' . $file;
    	} else {
    		
    		$file = '/externals/' . $lib . $file; 
    	}
    	
    	return '__MEDIA_PATH__' . $file;
    }
    
    
	/**
	 * Add an event to the view
	 *
	 * @param string $event
	 * @param string $type
	 * @param boolean $isFile
	 * @param boolean $bottom
	 * @return boolean
	 */
    public function eventAdd($event, $type, $isFile = false, $bottom = false)
    {
        if (!in_array($type, $this->_allowedEvents)) return false;
    	
        $store = $bottom ? 'bottom' : 'default';
        
    	if (! isset($this->_event[$type])) {
    		$this->_event[$type] = array('default' => array(), 'bottom' => array());
    	}
    	
        $eventHash = md5($event);
        if (array_key_exists($eventHash, $this->_event)) return false;

        if ($isFile != false) {
        	$path = Core::$basePath;
        	if (is_string($isFile)) {
        		$path .= $isFile;
        		if (substr($path,-1) != '/') $path .= '/';
        	}
        	$event = file_get_contents($path . $event);
        }
        $this->_event[$type][$store][$eventHash] = $event;
        return true;
    }

    
    public function actionAttach()
    {
    	if (count($this->_action) == 0) return;
    	 
    	foreach ($this->_action as $key => $action) {
    		$this->eventAdd(sprintf("t41.core.store.actions['%s'] = %s", $key, \Zend_Json::encode($action->reduce())), 'js');
    	}
    }
    
    
    public function eventAttach()
    {
    	if (count($this->_event) == 0) return;
    	
    	$code = '';
    	
        foreach($this->_event as $type => $stores) {
        	$str = '';
        	foreach ($stores as $events) {
        	
    	    	foreach ($events as $event) {
	    	        $str .= $event;
	        	    if ($type == 'js' && substr($str, -1)!=';') { $str .= ';'; }
	        	    $str .= "\n";
        		}
        	}
	        	
	        switch ($type) {
        		
    	    	case 'js':
        			if (strstr(implode(' ', $this->_component['js']), 'jquery') !== false && $this->getParameter('js_documentready') === true) {
        				$str = "jQuery(document).ready(function() {\n" . $str . "\n});";
        			}
        			$code .= "\n<script type=\"text/javascript\">\n$str\n</script>\n";
        			break;
        			
        		case 'css':
        			$code .= "\n<style>\n$str\n</style>\n";
        			break;
        	}
        }
        
		return $code;
    }

    
    /**
     * @deprecated
     * @param string $type 
     */
    public function componentAttach($type = null)
    {
    	$p = null;
    	
        if (count($this->_component) == 0) return;

        $array = $type ? $this->_component[$type] : $this->_component;


        foreach ($array as $key => $val) {

            if (is_array($val)) {

                foreach($val as $comp) {
                    if ($key == 'js') $p .= sprintf('<script src="%s" type="text/javascript"></script>' . "\n", $comp);
                    else $p .= sprintf('<link rel="stylesheet" href="%s" type="text/css" />' . "\n", $comp);
                }
            } else {
                if ($key == 'js') $p .= sprintf('<script src="%s" type="text/javascript"></script>' . "\n", $val);
                else $p .= sprintf('<link rel="stylesheet" href="%s" type="text/css" />' . "\n", $val);

            }
        }

        return $p;
    }
    
    
    protected function _renderComponents($type, $params = null)
    {
    	if (! isset($this->_component[$type])) {
    		return '';
    	}
    	 
    	$components = $this->_component[$type];

    	if (count($components) == 0) return null;
    	    	
    	if ($params) { // params come as a pseudo json string
    		$params = \Zend_Json::decode('{' . $params . '}');
    		$baseUrl = sprintf('http%s://%s', $_SERVER['SERVER_PORT'] == 443 ? 's' : null, $_SERVER['SERVER_NAME']);
    	}
    	
    	$html = '';
    	
    	foreach ($components as $component) {
    	    if (isset($params['fullUrl']) && $params['fullUrl'] == true && substr($component, 0, 4) != 'http') {
    			$component = $baseUrl . $component;
    		}
    		
    		// use CDN url if provided
    		if (false !== ($cdn = Core::getEnvData('cdn'))) {
    			$component = '//' . $cdn . $component;
    		}
    	
    		switch ($type) {
    			
    			case 'css':
    				$html .= sprintf('<link rel="stylesheet" href="%s" type="text/css" />' . "\n", $component);
    				break;
    				
    			case 'js':
    				$html .= sprintf('<script src="%s" type="text/javascript"></script>' . "\n", $component);
    				break;
    		}
    	}
    	return $html;
    }

    
    /**
     * Minify and cache JS & CSS files
     * @param string $file
     * @param string configuration string fs path ; web path (ex: html/cache;cache)
     * @return string web uri of cached file in case of success, $file in case of failure
     */
    public function minify($file, $confstr)
    {
    	$confstr = explode(';', $confstr);
    	$cachedir = Core::$basePath . $confstr[0];
    	if (substr($cachedir,-1) != DIRECTORY_SEPARATOR) $cachedir .= DIRECTORY_SEPARATOR;
    	
    	$ext = substr($file,strrpos($file,'.')+1);
    	 
    	if (strstr($file, '/core/')) {
    		$filepath = Core::$basePath . 'vendor/quatrain' . str_replace(array(':','core/'), array('/',''), $file);
    		$prefix = 'core';
    	} else if (strstr($file, '/vendor/')) {
    		$filepath = Core::$basePath . 'vendor/quatrain' . $file;
    		$prefix = 'vendor';
    	} else {
    		$filepath = substr_replace($file, '/assets/' . $ext, strrpos($file, '/'), -strlen(substr($file,strrpos($file,'/'))));
    		$filepath = Core::$basePath . str_replace('/t41/app', 'application/modules/', $file);
    		$prefix = 'app';
    	}
    	
    	clearstatcache(true,$filepath);
    	if (file_exists($filepath)) {
    		$md5 = filemtime($filepath); //md5_file($file);
    		$basecached = substr($filepath, strrpos($filepath, '/')+1, strrpos($filepath,'.')-strlen($filepath));
    		$cached = $prefix . '/' . $basecached . '-' . $md5 . '.' .$ext;
    		if (! file_exists($basecached . $cached)) {
    			// remove possible previous outdated file(s)
    			foreach (glob($cachedir . $basecached . '-*.' . $ext) as $oldfile) {
    				unlink($oldfile);
    			}
    			require_once 'vendor/tedivm/jshrink/src/JShrink/Minifier.php';
    			$shrinked = $ext == 'css' || $prefix == 'vendor' ? file_get_contents($filepath) : \JShrink\Minifier::minify(file_get_contents($filepath), array('flaggedComments' => false));
    			file_put_contents($cachedir . $cached, $shrinked);
    		}
    		$file = $confstr[1];
    		if (substr($file, 0, 1) != DIRECTORY_SEPARATOR) $file = DIRECTORY_SEPARATOR . $file;
    		if (substr($file,-1) != DIRECTORY_SEPARATOR) $file .= DIRECTORY_SEPARATOR;
    		$file .= $cached;
    	}
    	
    	return $file;
    }
    
    
    public function display($content = null, $error = false)
    {    	
    	if ($this->_template) {
    		if (View::getTheme('web')) {
    			$this->componentAdd(View::getTheme('web'), 'css', 't41');
    		}
    	    if (View::getColor('web')) {
    			$this->componentAdd(View::getColor('web'), 'css', 't41');
    		}
    		return $this->_render();
    	}
    }

    
    protected function _render()
    {
    	$template = file_get_contents($this->_template);
    	$tagPattern = "/%([a-z0-9]+)\\:([a-z0-9.]*)\\{*([a-zA-Z0-9:,\\\"']*)\\}*%/";
    	$tagPattern = "/%([a-z0-9]+)\\:([a-z0-9_.]*)\\{*([a-zA-Z0-9:_,\\\"']*)\\}*%/";
    	 
    	
    	$tags = array();
    	
    	preg_match_all($tagPattern, $template, $tags, PREG_SET_ORDER);
    	
    	// PHASE 1: analyse et parsing of content-generating tags
    	foreach ($tags as $tag) {

    		$content = false;
    		
    		switch ($tag[1]) {
    				
    			case 'helper':
    				$tmp = explode('.', $tag[2]);
    				$class = sprintf('%s\View\Web\%s', $tmp[0], ucfirst($tmp[1]));
    				try {
    					$helper = new $class;
    					$content = $helper->render();
    				} catch (Exception $e) {
    					if (Core::$env == Core::ENV_DEV) {
    						$content = $e->getMessage();
    					}
    				}
    				break;
    				
    			case 'container':
    				$elems = View::getObjects($tag[2]);
    				if (is_array($elems)) {
    					foreach ($elems as $elem) {
    						$object = $elem[0];
    						$params = $elem[1];
    						if (! is_object($object)) continue;
    						
    						// look for a custom decorator
    						$decorator = View\Decorator::factory($object, $params);
        					$content .= $decorator->render();
    					}
    				}
    				break;
    		}
    		
    		if ($content !== false) {
	    		$template = str_replace($tag[0], $content, $template);
    		}
    	}

    	preg_match_all($tagPattern, $template, $tags, PREG_SET_ORDER);
    	
        // PHASE 2: analyze & parsing of other tags (components linking & env variables)
    	foreach ($tags as $tag) {
    		$content = null;
    		
    		switch ($tag[1]) {
    			
    			case 'components':
    				$content = $this->_renderComponents($tag[2], $tag[3]);
    				break;
    				    				
    			case 'env':
    			case 'var':
    				$content = View::getEnvData($tag[2]);
    				break;
    				
    			case 'obj': // obj:
    				$tmp = explode('.', $tag[2]);
    				$obj = View::getEnvData($tmp[0]);
    				if ($obj instanceof MediaObject && isset($tmp[1])) {
    					// meta properties handling
    					switch ($tmp[1]) {
    				
    						case '_base64':
    							$content = sprintf('data:%s;base64,%s', $obj->getMime(), base64_encode($obj->loadBlob('media')));
    							break;
    									
    						case '_icon':
    							$content = 'file-' . $obj->getExtension();
    							break;
    									
    						case '_size':
    							$content = MediaElement::getDisplaySize($obj->getSize());
    							break;
    									
    						case '_url':
    							$content = MediaElement::getDownloadUrl($obj->getUri());
    							break;
    							
    						default:
    							break;
    					}
    				}
    				
    				if (! $content) {
    					if ($obj instanceof BaseObject) {
    						$content = isset($tmp[1]) && $tmp[1] == ObjectUri::IDENTIFIER ? $obj->getIdentifier() : $obj->getProperty($tmp[1]);
    						$content = ($content instanceof AbstractProperty)  ? $content->getDisplayValue() : $content;
    					} else {
    						$content = Core::$env == Core::ENV_DEV ? sprintf("Can't substitute any value to '%s'", $tag[0]) : null;
    					}
    				}
    				break;
    		}
       		$template = str_replace($tag[0], $content, $template);
    	}	

    	// PHASE 3: actions attachment
    	$this->actionAttach();
    	
    	// PHASE 4: events attachment
        $template = str_replace('</body>', $this->eventAttach() . '</body>', $template);
        
        // PHASE 5: display logged errors in dev mode
        if (Core::$env == Core::ENV_DEV) {
	        $errors = View::getErrors();
    	    if (count($errors) > 0) {
        		$str = "\n";
        		foreach ($errors as $errorCode => $errorbloc) {
        			$str .= $errorCode . "\n";
        			foreach ($errorbloc as $error) {
        				$str .= "\t" . $error[0] . "\n";
        			}
        		}
        		$template = str_replace('</body>', '</body><!--' . $str . ' -->' , $template);
        	}
        }
        return $template;
    }
}
