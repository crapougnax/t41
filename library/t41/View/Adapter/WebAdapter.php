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
 * @version    $Revision: 865 $
 */

use t41\View;

/**
 * Class providing the view engine with a Web context adapter.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebAdapter extends AdapterAbstract {

	
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
			'js_documentready' => new \t41\Parameter(\t41\Parameter::BOOLEAN, true)
		));
		parent::__construct($parameters);
	}

	
	/**
	 * Ajoute un composant externe à la page sous réserve que le fichier de référence existe
	 *
	 * @param string $file
	 * @param string $type
	 * @return boolean
	 */
    public function componentAdd($file, $type, $lib = null, $priority = 0)
    {
        if (!in_array($type, $this->_allowedComponents)) return false;

        if (substr($file, 0, 4) != 'http') {
	        if ($lib) {
		        $filePath = '/lib/' . $lib . '/' . $type . '/' . $file . '.' . $type;
        	} else {
        		$filePath = '/' . $type . '/' . $file . '.' . $type;
        	}
        } else {
        	$filePath = $file;
        }
        
        // return true if component is already listed
        if (in_array($filePath, $this->_component[$type])) return false;

        if (substr($filePath, 0, 4) == 'http') {
        
        	if ($priority == -1) {
        		array_unshift($this->_component[$type], $filePath);
        	} else {
	           $this->_component[$type][] = $filePath;
        	}
           
        } else if (file_exists(\t41\Core::getBasePath() . $this->_componentsBasePath . $filePath)) {

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
	 * Ajout d'un événement à la vue
	 *
	 * @param string $event
	 * @param string $type
	 * @param boolean $isFile
	 * @return boolean
	 */
    public function eventAdd($event, $type, $isFile = false)
    {
        if (!in_array($type, $this->_allowedEvents)) return false;
    	
    	if (! isset($this->_event[$type])) {
    		
    		$this->_event[$type] = array();
    	}
    	
        $eventHash = md5($event);
        if (array_key_exists($eventHash, $this->_event)) return false;

        if ($isFile) {
        	$event = @file_get_contents(APP_PATH . $event);
        }
        $this->_event[$type][$eventHash] = $event;
        
        return true;
    }

    
    public function eventAttach()
    {
    	if (count($this->_event) == 0) return;
    	
    	$code = '';
    	
        foreach($this->_event as $type => $events) {
        	
        	$str = '';
        	foreach ($events as $event) {

	            $str .= $event;

	            if ($type == 'js' && substr($str, -1)!=';') { $str .= ';'; }
	            $str .= "\n";
        	}
        	

        	switch ($type) {
        		
        		case 'js':
        			if (strstr(implode(' ', $this->_component['js']), 'jquery') !== false && $this->getParameter('js_documentready') === true) {
        				$str = "jQuery(document).ready(function() {\n" . $str . "\n});";
        			}
        			$code .= "\n<script language=\"javascript\">\n$str\n</script>\n";
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
    	
    	$components = $this->_component[$type]; // t41_View::getRequiredLibs($type);
    	
/*    	if (! isset($this->_component[$type]) || count($this->_component[$type]) == 0) {
    		return null; 
    	}
*/
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

    
    public function display($content = null, $error = false)
    {
    	if ($this->_subContext == 'ajax') exit;
    	
    	if ($this->_subContext == 'popup') {

    		$this->componentAdd('page_popup', 'css');
            $this->componentAdd('donut/popup', 'js');
            $this->eventAdd("document.getElementById('bandeau').innerHTML = '" . $this->_title . "';", 'js');
    		
    		$this->setTemplate('popup.tpl');
    	}
    	
    	if ($this->_template) {

    		if (\t41\View::getTheme('web')) {
    			
    			$this->componentAdd(\t41\View::getTheme('web'), 'css', 't41');
    		}
    		
    	    if (\t41\View::getColor('web')) {
    			
    			$this->componentAdd(\t41\View::getColor('web'), 'css', 't41');
    		}
    		    		
    		echo $this->_render();
    		exit();
    	}
    }

    
    protected function _render()
    {
    	$template = file_get_contents($this->_template);
    	$tagPattern = "/%([a-z0-9]+)\\:([a-z0-9.]*)\\{*([a-zA-Z0-9:,\\\"']*)\\}*%/";
    	
    	$tags = array();
    	
    	preg_match_all($tagPattern, $template, $tags, PREG_SET_ORDER);
    	
    	// PHASE 1 : analyse et interpretation des tags generant du contenu
    	foreach ($tags as $tag) {

    		$content = false;
    		
    		switch ($tag[1]) {
    				
    			case 'helper':
    				$tmp = explode('.', $tag[2]);
    				$class = $tmp[0] . '\View\Web\\' . ucfirst($tmp[1]);
    				try {
    					$helper = new $class;
    					$content = $helper->render();
    				} catch (Exception $e) {
    					
    					if (\t41\Core::getEnvData('Env') == \t41\Core::ENV_DEV) {
    						$content = $e->getMessage();
    					}
    				}
    				break;
    				
    			case 'container':
    				
    				$elems = \t41\View::getObjects($tag[2]);
    				
    				if (is_array($elems)) {
    					
    					foreach ($elems as $elem) {
    						
    						$object = $elem[0];
    						$params = $elem[1];

    						if (! is_object($object)) continue;
    						
    						// on cherche d'abord à récupérer le décorateur par la voie "moderne"
    						if (method_exists($object, 'getDecorator')) {
    							
    							try {
    								$decorator = \t41\View\Decorator::factory($object, $params);
        							$content .= $decorator->render();
        							
    							} catch (Exception $e) {
    								
    								$content .= 'ERREUR : ' . $e->getMessage() . $e->getTraceAsString() . "<br/>";
    							}
    							
    						}
    					}
    				}
    				break;
    		}
    		
    		if ($content !== false) {
    			
	    		$template = str_replace($tag[0], $content, $template);
    		}
    	}

    	preg_match_all($tagPattern, $template, $tags, PREG_SET_ORDER);
    	
        // PHASE 2 : analyse et interprétation des tags générants des liens vers des fichiers (js, css) ou l'affichage de variables d'environnement
    	foreach ($tags as $tag) {

    		$content = null;
    		
    		switch ($tag[1]) {
    			
    			case 'components':
    				
    				$content = $this->_renderComponents($tag[2], $tag[3]);
    				break;
    				    				
    			case 'env':
    				
    				$content = \t41\Core::htmlEncode(View::getEnvData($tag[2]));
    				break;
    		}
    		
       		$template = str_replace($tag[0], $content, $template);
    	}	

    	// PHASE 3: events attachment
        $template = str_replace('</body>', $this->eventAttach() . '</body>', $template);
        
        // PHASE 4: display logged errors in dev mode
        if (\t41\Core::getEnvData('webEnv') == \t41\Core::ENV_DEV) {
        	
	        $errors = \t41\View::getErrors();
        
	//        Zend_Debug::dump($errors);
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
