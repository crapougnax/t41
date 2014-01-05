<?php

namespace t41\View\ListComponent\Element\ColumnElement;

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

use t41\View\Decorator\AbstractWebDecorator,
	t41\Parameter,
	t41\View,
	t41\View\ViewUri,
	t41\Core;
use t41\View\FormComponent\Element\MediaElement;

/**
 * Web decorator for the FieldElement class
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebMedia extends AbstractWebDecorator {

	
	public function render()
	{
		if (! is_null($uri = $this->_obj->getValue())) {
			return sprintf('<a href="%s" target="_blank">%s</a>'
				, MediaElement::getDownloadUrl($uri)
				, 'Télécharger'
				);
		} else {
			return 'Pas de fichier lié';
		}
	}
}
