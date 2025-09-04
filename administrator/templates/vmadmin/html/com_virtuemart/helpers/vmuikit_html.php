<?php
/**
 *
 * HTML helper class
 *
 * This class was developed to provide some standard HTML functions.
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers, Valerie Isaksen
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2004 - 2021 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: vmuikit_html.php 10990 2024-04-08 20:21:17Z  $
 */


// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

/**
 * HTML Helper
 *
 * @package VirtueMart
 * @subpackage Helpers
 * @author RickG
 */
class VmuikitHtml extends VmHtml {

    /**
     * Generate HTML code for a row using VmHTML function
     * works also with shopfunctions, for example
	 * $html .= VmHTML::row (array('ShopFunctions', 'renderShopperGroupList'),
	 * 			'VMCUSTOM_BUYER_GROUP_SHOPPER', $field->shopper_groups, TRUE, 'custom_param['.$row.'][shopper_groups][]', ' ');
	 *
     * @func string  : function to call
     * @label string : Text Label
     * @args array : arguments
     * @return string: HTML code for row table
     */
    static function row($func,$label){
		$VmHTML="VmuikitHtml";
		
		if (!is_array($func)) {
			$func = array($VmHTML, $func);
		}
		$passedArgs = func_get_args();
		array_shift( $passedArgs );//remove function
		array_shift( $passedArgs );//remove label
			$args = array();
			foreach ($passedArgs as $k => $v) {
			    $args[] = &$passedArgs[$k];
			}

	    $lang = vmText::$language; //vmLanguage::getLanguage();
		
		$tooltiptext = "";
	    $class="uk-clearfix";
		/*if($lang->hasKey($label.'_TIP')){
			$tooltiptext = htmlentities(vmText::_($label.'_TIP'));
		} //Fallback
		else if($lang->hasKey($label.'_EXPLAIN')){
			$tooltiptext = htmlentities(vmText::_($label.'_EXPLAIN'));
		}
	    if ($tooltiptext) {
		    $tooltiptext=' data-content="'.$tooltiptext.'"';
		    $class.=' hasPopover';
	    }*/

	    $tip = '';
	    if($lang->hasKey($label.'_TIP',true )){
		    $tip = $label.'_TIP' ;
	    } //Fallback
	    else if($lang->hasKey($label.'_EXPLAIN')){
		    $tip = $label.'_EXPLAIN' ;
	    }

	    $popUP = '';
		if ($tip) {
			/*$tooltiptext=' data-content="'.htmlentities(vmText::_($tip)).'" ';
			$popUP .=' hasPopover';*/
			$tooltiptext = ' uk-tooltip="'.htmlentities(vmText::_($tip)).'" ';
		}
		$label = vmText::_($label);
		if ($func[1]=="checkbox" OR $func[1]=="input") {
			$label = "\n\t" . '<label class="uk-form-label'.$popUP.'" 
			for="'. $args[0] . '" 
			id="' . $args[0] . '-lbl"'.$tooltiptext.'>'.$label."</label>";
		}else {
			$label='
			<div class="uk-form-label'.$popUP.'" '.$tooltiptext.'>'
				.$label.
			'</div>';
		}
		$html = '';
		$html .= '<div class="uk-clearfix">';
		//$html .= '<div class="uk-form-label">';
		$html .= $label;
		//$html .= '</div>';
		$html .= '<div class="uk-form-controls">';
	// 	public static function input($name,$value,$class='class="inputbox"',$readonly='',$size='37',$maxlength='255',$more=''){
		$html .= call_user_func_array($func, $args);
		$html .= '</div>';
		$html .= '</div>';
		/*
		$html = '
		
		<tr>
			<td class="key">
				'.$label.'
			</td>
			<td>';
		if($func[1]=='radioList'){
			$html .= '<fieldset class="checkboxes">';
		}

		$html .= call_user_func_array($func, $args).'
			</td>';
		if($func[1]=='radioList'){
			$html .= '</fieldset>';
		}
		$html .= '</tr>'; */
		return $html ;
	}

	/* simple value display */
	static function value( $value ){
		$lang =vmLanguage::getLanguage();
		return $lang->hasKey($value) ? vmText::_($value) : $value;
	}
	
    /**
     * Generate HTML code for a checkbox
     *
     * @param string Name for the checkbox
     * @param mixed Current value of the checkbox
     * @param mixed Value to assign when checkbox is checked
     * @param mixed Value to assign when checkbox is not checked
     * @return string HTML code for checkbox
     */
    static function checkbox($name, $value, $checkedValue=1, $uncheckedValue=0, $extraAttribs = '', $id = null) {
		
		if (!$id){
			$id ='id="' . $name.'"';
		} else {
			$id = 'id="' . $id.'"';
		}

		if ($value == $checkedValue) {
			$checked = 'checked="checked"';
		} else {
			$checked = '';
		}

		if($extraAttribs == "yesno") {
			$checked1 = "";
			$checked2 = "";

			if($value == 1)	{
				$checked1 = 'checked="checked"';
			} else {
				$checked2 = 'checked="checked"';
			}
			
			$htmlcode  = '';
			$htmlcode  .= '<fieldset id="'.$name.'" class="btn-group btn-group-yesno radio">';
			$htmlcode .= '<input type="radio" id="'.$name.'0" name="'.$name.'" value="1" '.$checked1.' />';
			$htmlcode .= '<label for="'.$name.'0" > Yes </label>';
			$htmlcode .= '<input type="radio" id="'.$name.'1" name="'.$name.'" value="0" '.$checked2.' />';
			$htmlcode .= '<label for="'.$name.'1" > No </label>';
			$htmlcode .= '</fieldset>';

		} else {
			$htmlcode = '<input type="hidden" name="' . $name . '" value="' . $uncheckedValue . '" />';
			$htmlcode .= '<input '.$extraAttribs.' ' . $id . ' type="checkbox" name="' . $name . '" value="' . $checkedValue . '" ' . $checked . ' />';
		}
		return $htmlcode;
    }

	/**
	 * Creates a Radio Input List
	 *
	 * @param string $name
	 * @param string $value default value
	 * @param string $arr
	 * @param string $extra
	 * @return string
	 */
	static function radioList($name, $value, &$arr, $extra="", $separator='') {
		$html = '';
		if( empty( $arr ) ) {
			$arr = array();
		}
		$html = '<div class="controls uk-margin-small-top uk-margin-small-bottom">';
		$i = 0;
		foreach($arr as $key => $val) {
			$checked = '';
			if( is_array( $value )) {
				if( in_array( $key, $value )) {
					$checked = 'checked="checked"';
				}
			}
			else {
				if(strtolower($value) == strtolower($key) ) {
					$checked = 'checked="checked"';
				}
			}
			$id = $name.$key;
			$html .= "\n\t" . '<label for="' . $id . '" id="' . $id . '-lbl" class="radio">';
			$html .= "\n\t\n\t" . '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . vRequest::vmSpecialChars($key, ENT_QUOTES) . '" '.$checked.' ' . $extra. ' />' . $val;
			$html .= "\n\t" . "</label>".$separator."\n";
		}

		$html .= "\n";
		$html .= '</div>';
		$html .= "\n";

		return $html;
	}

	/**
	 * Creating rows with boolean list
	 *
	 * @author Patrick Kohl
	 * @param string $label
	 * @param string $name
	 * @param string $value
	 *
	 */
	public static function booleanlist (  $name, $value,$class='class="inputbox"'){

		$checked1 = "";
		$checked2 = "";

		if($value == 1) {
			$checked1 = 'checked="checked"';
		} else {
			$checked2 = 'checked="checked"';
		}

		$htmlcode  = '<fieldset id="'.$name.'" class="btn-group btn-group-yesno radio">';
		$htmlcode .= '<input type="radio" id="'.$name.'0" name="'.$name.'" value="1" '.$checked1.' />';
		$htmlcode .= '<label for="'.$name.'0" >'.vmText::_('COM_VIRTUEMART_YES').'</label>';
		$htmlcode .= '<input type="radio" id="'.$name.'1" name="'.$name.'" value="0" '.$checked2.' />';
		$htmlcode .= '<label for="'.$name.'1" >'.vmText::_('COM_VIRTUEMART_NO').'</label>';
		$htmlcode .= '</fieldset>';
		return $htmlcode;
	}


	static function mediaRow($VmMediaHandler,$descr, $name,$readonly='',$value = null){
		$v = (isset($value))? $value: $VmMediaHandler->{$name};
		$html='
				<div class="uk-clearfix">
						<label class="uk-form-label" >
							'. vmText::_($descr) .
			'</label>
						<div class="uk-form-controls">

							'.'<input type="text" '.$readonly.'  name="media['.$name.']" size="70" value="'.$v.'" />' .
			'</div>
					</div>
		';
		return $html;
	}

}