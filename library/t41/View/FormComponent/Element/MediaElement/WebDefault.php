<?php

namespace t41\View\FormComponent\Element\MediaElement;

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
 * @version    $Revision: 876 $
 */

use t41\View\Decorator\AbstractWebDecorator;
use t41\View\Action\UploadAction;
use t41\View;
use t41\View\FormComponent\Element\MediaElement;
use t41\Core;

/**
 * t41 default web decorator for list elements
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
		// set correct name for field name value depending on 'mode' parameter value
		$name = $this->_obj->getId();
		$prefix = Core::getController('medias');
		if (! $prefix) {
			$prefix = '/t41/medias/';
		}
		$uri = $prefix . 'upload';
		View::addCoreLib(array('core.js','locale.js','view.js','uploader.css','view:action:upload.js'));
		View::addEvent(sprintf("new t41.view.action.upload(jQuery('#%s_ul'),'%s')", $name, $uri), 'js');
		
		$html = '';
		// @todo code media deletion JS
		if (($this->_obj->getValue()) != null) {
			$html .= sprintf('<a href="%s" target="_blank">%s %s</a> | <a href="#" onclick="">%s</a>'
					, MediaElement::getDownloadUrl($this->_obj->getValue()->getUri())
					, 'Télécharger'
					, $this->_obj->getValue()->getLabel()
					, 'Supprimer'
			);
		}
		$html .= sprintf('<div id="%s_ul" class="qq-upload-list"></div>', $this->_nametoDomId($name));
		$html .= sprintf('<input type="hidden" name="%s" id="%s" value="%s" class="hiddenfilename"/>'
				, $name
				, $this->_nametoDomId($name)
				, $this->_obj->getValue() ? $this->_obj->getValue()->getIdentifier() : null
		);
		
		return $html;
		
		$action = new UploadAction($this->_obj);
		
		$deco = View\Decorator::factory($action);
		$deco->render();
		
		return $html . "\n";
	}
}
