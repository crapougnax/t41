<?php

namespace t41\View\Form\Element\DateElement;


use t41\View,
	t41\View\Decorator\AbstractWebDecorator;

class WebDefault extends AbstractWebDecorator {

	
	public function render()
	{
//		t41_Externals::enablejQueryUI();
//		t41_View::addRequiredLib('t41_icons', 'css', 't41');

		switch ($this->getParameter('mode')) {

			case t41_Form::SEARCH:
				
				$so = t41_View_Uri::getUriAdapter()->getIdentifier('search');
				
				$dataArray = isset($_GET[$so][$this->_obj->getAltId()]) ? $_GET[$so][$this->_obj->getAltId()] : array();
				$html	= $this->_renderField($so . '[' . $this->_obj->getAltId() . '][from]'
											, isset($dataArray['from']) ? $dataArray['from'] : NULL
											, 'du')
					  	. $this->_renderField($so . '[' . $this->_obj->getAltId() . '][to]'
					  						, isset($dataArray['to']) ? $dataArray['to'] : NULL
					  						, 'au');
				break;
				
			default:
				if (($this->_obj->getConstraint('neditable') == 'Y' || $this->getParameter('noteditable') == 'Y') 
				     && $this->_obj->getValue() != null) {
					$html = $this->_obj->formatValue($this->_obj->getValue());	
				} else {

					$html = $this->_renderField($this->_obj->getAltId(), $this->_obj->getValue());
				}
				break;
		}
		
		return $html;
	}
	
	
	protected function _renderField($name, $value = null, $prefix = null)
	{
		$id = 't41_' . md5($name);
		$dispField = 'disp_' . $id;
		
		$pickerArgs = array( 'dateFormat'	=> 'dd/mm/yy'
							,'firstDay' 	=> 1
							,'changeYear'	=> true
							,'changeMonth'	=> true
							,'altField'		=> '#' . $id
							,'altFormat'	=> 'yy-mm-dd'
							,'monthNames'	=> array('Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre')
							,'dayNames'		=> array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi')
							,'dayNamesMin'	=> array('Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam')	
						   );
						   
		View::addEvent(sprintf('jQuery(function() { jQuery("#%s").datepicker(%s); });'
									, $dispField
									, \Zend_Json::encode($pickerArgs)
									), 'js');

		$html  = sprintf('%s<input type="hidden" name="%s" id="%s" value="%s"/>'
						, $prefix ? htmlspecialchars($prefix) . '&nbsp;' : null
						, $name
						, $id
						, $value
						);
						
		$dispValue = $value ? $this->_obj->formatValue($value) : null;
		
		$html .= sprintf('<input type="text" id="%s" value="%s" size="10" maxlength="10"/>'
						, $dispField
						, $dispValue
						);

			if ($this->_obj->getParameter('enable_quickset')) {
			$buttons = array(
				array('title' => 'Hier', 			'value' => '-1', 	'icon'=>'playbackw'),
				array('title' => 'Aujourd\'hui', 	'value' => '+0d',	'icon'=>'pause'),
				array('title' => 'Demain',	 		'value' => '+1', 	'icon'=>'playforw')
			);
			foreach ($buttons as $k => $v) {
				$html .= sprintf('<button type="button" class="icon %s" onclick="javascript:jQuery(\'#%s\').datepicker(\'setDate\', \'%s\'); return false;" title="%s"></button>'
								, $v['icon'], $dispField, $v['value'], $v['title']);
			}
		}
		return $html;
	}
}
