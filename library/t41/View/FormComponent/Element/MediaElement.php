<?php

namespace t41\View\FormComponent\Element;

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

use t41\View\FormComponent\Element\AbstractElement;
use t41\ObjectModel\ObjectUri;
use t41\ObjectModel\MediaObject;

/**
 * Form field element for handling medias
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class MediaElement extends AbstractElement {

	
	public function setValue($val)
	{
		if ( ! is_null($val) && ! $val instanceof ObjectUri && ! $val instanceof MediaObject) {
			$val = new MediaObject($val);
		}
		return parent::setValue($val);
	}
	
	
	static public function getDownloadUrl(ObjectUri $uri)
	{
		return '/t41/medias/download/obj/' . rawurlencode(base64_encode($uri->__toString()));
	}
}
