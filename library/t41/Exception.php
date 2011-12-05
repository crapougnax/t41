<?php

namespace t41;

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
 * @version    $Revision: 832 $
 */

/**
 * Class providing basic functions needed to handle environment building.
 *
 * @category   t41
 * @package    t41_Core
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class Exception extends \Exception {

	
	/**
	 * XML file name where messages can be found
	 * 
	 * @var string
	 */
	protected $_store = null;

	/**
	 * 
	 * @param string|array $message
	 * @param integer $code
	 * @param Exception $previous
	 */
	public function __construct($message, $code = 0, Exception $previous = null)
	{
		if (is_array($message)) {
			
			if (count($message) != 2) {
				
				throw new \Exception('Array argument should contain exactly two values');
			}
			
			// key O is key to message, key 1 contains a string or an array of strings
			$txtKey = $message[0];
			
		} else {
			
			$txtKey = $message;
		}
		
		require_once 't41/Core.php';
		
		if (! empty($this->_store)) {
			
			$this->_store = '/' . $this->_store;
		}
		
		$str = Core::getText($txtKey, 'exceptions' . $this->_store);
		
		if ($str !== false && is_array($message)) {
			
			foreach ( (array) $message[1] as $key => $val) {
				
				$str = str_replace('%' . $key, $val, $str);
			}
		}
		
		// if a previously caught exception is passed as parameter, its message is postfixed to current message 
		if ($previous instanceof Exception) {
			
			$str .= ': ' . $previous->getMessage();
		}
		parent::__construct($str, (integer) $code); //, $previous);
	}
}