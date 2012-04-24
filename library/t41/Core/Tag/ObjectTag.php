<?php

namespace t41\Core\Tag;

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
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 972 $
 */

/**
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class ObjectTag implements TagInterface {

	
	static public $object;
	
	
	/**
	 * Return environment value described by tag and optional sub tag
	 * @param string $tag
	 * @param string $sub
	 * @return string
	 */
	static public function get($tag, $sub = null)
	{
		if (! is_object(self::$object)) {
			return '#MISSING_BASE_OBJECT#';
		}
		switch ($tag) {
				
			case 'uri':
				return self::getUriPart($sub);
				break;
		}
	}
	
	
	/**
	 * Return a part of the current object uri
	 * @param string $part
	 * @return string
	 */
	static public function getUriPart($part)
	{
		$uri = self::$object->getUri();
		
		switch ($part) {
	
			case 'identifier':
				return $uri->getIdentifier();
				break;
		}
	}
}
