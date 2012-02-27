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
class EnvTag implements TagInterface {

	/**
	 * Return environment value described by tag and optional sub tag
	 * @param string $tag
	 * @param string $sub
	 * @return string
	 */
	static public function get($tag, $sub = null)
	{
		switch ($tag) {
				
			case 'date':
				return self::getDateVal($sub);
				break;
		}
	}
	
	
	/**
	 * Return a date calculated and formatted accordingly with parameters
	 * @param string $when
	 * @param string $pattern
	 * @return string
	 */
	static public function getDateVal($when = null, $pattern = 'Y-m-d H:i:s')
	{
		$ref = time();
	
		switch ($when) {
	
			case 'yesterday':
				$ref -= 86400;
				break;
	
			case 'tomorrow':
				$ref += 86400;
				break;
	
			case 'today':
			default:
				break;
		}
	
		return date($pattern, $ref);
	}
}
