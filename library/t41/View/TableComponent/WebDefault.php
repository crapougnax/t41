<?php

namespace t41\View\TableComponent;

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
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 * @version    $Revision: 903 $
 */

use t41\View\FormComponent\Element\CurrencyElement;

use t41\ObjectModel,
	t41\View\Decorator\AbstractWebDecorator,
	t41\View\Decorator,
	t41\View\TableComponent,
	t41\View;

/**
 * Decorator class for table objects in a Web context.
 *
 * @category   t41
 * @package    t41_View
 * @copyright  Copyright (c) 2006-2011 Quatrain Technologies SARL
 * @license    http://www.t41.org/license/new-bsd     New BSD License
 */
class WebDefault extends AbstractWebDecorator {
	

	public function render()
	{
		switch ($this->_obj->_disposition) {
							
			case TableComponent::DISP_ROWS:
				$html = $this->_byRowsRendering();
				break;
				
			case TableComponent::DISP_COLS:
				if ($this->getParameter('sortable') === true) {
					
					$params = array('headers' => array());
					
					foreach ($this->_obj->getFields() as $key => $field) {
						//var_dump($field);
						/* we disable sorting on columns where preserveLabel parameter is set to true as they may contain HTML */
						if ($field->getParameter('preserveLabel') === true) {
							
							$params['headers'][$key] = array('sorter' => false);
						}
						if ($field instanceof t41_Form_Element_Date) {
							
							$params['headers'][$key] = array('sorter' => 'shortDate');
						}
					}
					//View::addRequiredLib('jquery/jquery.tablesorter', 'js', 'externals');
					//View::addEvent(sprintf('jQuery("#'.$this->_obj->getId().' table").tablesorter(%s);', Zend_Json::encode($params)), 'js');
				}
				$html = $this->_byColsRendering();
				break;
		}
		
		return $this->_headerRendering() . $html . $this->_footerRendering();
	}
	
	protected function _byRowsRendering()
	{
		$colRow = array();
		$html = '';
		if ($this->_obj->getParameter('columns')) {
			
			$html .= sprintf('<div class="%s_lcol">', $this->_cssStyle);
		}
		
		$lines = 0;//$this->_obj->lines();
		$i=0;
		
		foreach($this->_obj->getFields() as $field) {
			
			$colRow[$field->getAltId()] = '';
			
			// @todo looks buggy to me
			if ($this->_obj->getParameter('columns') && $i == $lines) {
				
				$colRow[$field->getAltId()] .= sprintf('</div><div class="%s_rcol">', $this->_cssStyle);
			}
			
			$colRow[$field->getAltId()] .= '<div class="line">' . "\r\n"
										. '<span class="key">' . $this->_escape($field->getTitle()) . '</span>';
			
			foreach($this->_obj->getRows() as $key => $row) {
				
			//	\Zend_Debug::dump($row); die;
				
				if ($row instanceof ObjectModel\DataObject) {
					
					$colRow[$field->getId()] .= sprintf('&nbsp;<span class="value">%s</span>'
							, $this->_escape($row->getProperty($field->getId())->getDisplayvalue())
					);
										
				} else {
					
					$colRow[$field->getId()] .= sprintf('&nbsp;<span class="value">%s</span>'
									 , $field->formatValue($row[$field->getId()])
									);
				}
			}
			
			$colRow[$field->getAltId()] .= '</div>';
			$i++;
		}
		
		//if ($this->_obj->getParameter('columns')) $colRow[$colId] .= '</div><div style="clear: both;"></div>';
		
		$html = $html . implode("\n", $colRow)/* . '</div>'*/;
		
		return $html;
	}
	
	
	protected function _byColsRendering()
	{
		$body = $head = '';
		
		foreach($this->_obj->getFields() as $field) {
			
			$label = $field->getTitle();
			
			if ($field->getParameter('preserveLabel') == false) {
				
				$label = $this->_escape($label);
			}
			$head .= '<th>' . $label . '</th>';
		}
		
		if (count($this->_obj->getButtons()) > 0) $head .= '<th>&nbsp;</th>';
		
		$head = sprintf('<tr class="">%s</tr>', $head);
		$i=0;
		
		foreach($this->_obj->getRows() as $key => $row) {
			
			$line = '';

			foreach($this->_obj->getFields() as $field) {

				$colId = $field->getAltId();
				$rowId = $this->_obj->getId() . '_' . $key . '_' . $colId;
				
				$line .= sprintf('<td id="%s" %s>%s</td>'
								 , $rowId
								 , ''
								 , $field->formatValue($row[$colId])
								);
			}
			
			if (count($this->_obj->getButtons()) > 0) $line .= $this->_buttonsRendering($row);
			
			$css = $i%2 == 0 ? 'odd' : 'even';
			$body .= '<tr id="' . $this->_obj->getId() . '_' . $key . '" class="' . $css . '">' . $line . '</tr>';
			$i++;
		}
		
		$thead = '<thead>' . $head . '</thead>';
		$tbody = '<tbody>' . $body . '</tbody>';
		
		$class = $this->getParameter('sortable') ? ' class="tablesorter"' : '';
		
		if ($this->getParameter('sortable')) {
			$class = ' class="tablesorter"';
			$info = '<div class="info">Vous pouvez trier les valeurs d\'une colonne en cliquant sur son ent&ecirc;te, et sur plusieurs colonnes avec shift+clic (maj+clic).</div>';
		}
		
		return @$info.'<table class="t41 component table">' . $thead . $tbody . '</table>';
	}

	
	protected function _headerRendering()
	{
		$title = $this->_obj->getTitle() ? $this->_obj->getTitle() : 'Table';
		$status = $this->_obj->getParameter('open_default') ? 'open' : 'close';
		$html = <<<HTML
<div class="t41 component" id="{$this->_obj->getId()}">
<h4 class="title slide_toggle {$status}"><div class="icon"></div>{$title}</h4>
<div class="content">
HTML;
		return $html;
	}

	
	protected function _footerRendering()
	{
		return '</div></div>' . "\r\n";
	}


	protected function _buttonsRendering(array $data)
	{
		$line = '<td id="buttons">';
		
		foreach ($this->_obj->getButtons() as $button) {
			
			$deco = Decorator::factory($button);
			$deco->setParameter('data', $data);
			
			$line .= $deco->render();
		}
		$line .= '</td>';

		return $line;
	}
}
