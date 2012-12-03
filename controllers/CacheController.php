<?php

/**
 * DefaultController
 * 
 * @author
 * @version 
 */

use t41\Core,
	t41\ObjectModel,
	t41\ObjectModel\Collection,
	t41\Backend,
	t41\View,
	t41\View\Action;

require_once 'Zend/Controller/Action.php';

class t41_CacheController extends Zend_Controller_Action {

	
	
	public function init() {
		
	}
	
	
	public function indexAction()
	{
		$cache = Core::cacheGetAdapter();
		var_dump($cache->getFillingPercentage());
		$html = '<table border="1"><tr><th>Id</th><th>Type</th><th>Date</th><th>TTL</th><th>Expire</th></tr>';
		foreach ($cache->getIds() as $id) {
			$metas = $cache->getMetadatas($id);
			$html .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>'
							, $id
							, $metas['tags'][0]
							, date('d/m/Y H:i:s', $metas['mtime'])
							, $metas['expire'] - $metas['mtime']
							, date('d/m/Y H:i:s', $metas['expire'])
					
					);
		}
		$html .= '</table>';
		
		echo $html;
	}
	
	
	public function cleanAction()
	{
		$cache = Core::cacheGetAdapter();
		$cache->clean();
	}
}
