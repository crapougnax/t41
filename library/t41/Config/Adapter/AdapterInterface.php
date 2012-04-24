<?php

namespace t41\Config\Adapter;

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
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 832 $
 */

/**
 * Interface for Configuration Adapters
 *
 * @category   t41
 * @package    t41_Config
 * @copyright  Copyright (c) 2006-2012 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */

interface AdapterInterface {
	
	
	/**
	 * Set path(s) to search into
	 * @param mixed $path
	 */
	public function setPath($path);
	
	
	/**
	 * Validate the loaded file
	 * 
	 */
	public function validate();

	
	/**
	 * Load the Configuration files into an array
	 * 
	 * @param array $filePath Array containing the complete paths to the files to load and parse 
	 * @return array
	 */
	public function load(array $filePath = array());

	
	/**
	 * Save an array into a configuration file
	 * 
	 * @param array $config	Configuration Array
	 * @param bool $add Add data on true, overwrite on false
	 */
	public function save(array $config, $add = true);	
}
