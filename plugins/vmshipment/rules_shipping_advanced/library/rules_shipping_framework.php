<?php

if ( !defined( 'ABSPATH' ) && !defined('_JEXEC') ) { 
	die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' );
}

/**
 * Shipping By Rules Framework for general, rules-based shipments, like regular postal services with complex shipping cost structures
 *
 * @package ShippingByRules e-commerce system-agnostic framework for shipping plugins.
 * @subpackage Plugins - shipment
 * @copyright Copyright (C) 2013 Reinhold Kainhofer, reinhold@kainhofer.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.txt
 *
 * @author Reinhold Kainhofer, Open Tools
 *
 */
// Only declare the class once...
if (class_exists ('RulesShippingFramework')) {
	return;
}


function print_array($obj) {
	$res = "";
	if (is_array($obj)) {
		$res .= "array(";
		$sep = "";
		foreach ($obj as $e) {
			$res .= $sep . print_array($e);
			$sep = ", ";
		}
		$res .= ")";
	} elseif (is_string($obj)) {
		$res .= "\"$obj\"";
	} else {
		$res .= (string)$obj;
	}
	return $res;
}

function is_equal($a, $b) {
	if (is_array($a) && is_array($b)) {
		return !array_diff($a, $b) && !array_diff($b, $a);
	} elseif (is_string($a) && is_string($b)) {
		return strcmp($a,$b) == 0;
	} else {
		return $a == $b;
	}
}

class RulesShippingFramework {
	static $_version = "0.1";
	protected $callbacks = array();
	// Store the parsed and possibly evaluated rules for each method (method ID is used as key)
	protected $rules = array();
	protected $match = array();
	protected $custom_functions = array ();
	protected $available_scopings = array();
	
	function __construct() {
// 		$this->registerCallback('addCustomCartValues',	array($this, 'addCustomCartValues'));
	}
	
	
	
	/* Callback handling */
	
	/**
	 * Register a callback for one of the known callback hooks. 
	 * Valid callbacks are (together with their arguments):
	 *   - translate($string)
	 *  @param string $callback 
	 *     The name of the callback hook (string)
	 *  @param function $func 
	 *     The function (usually a member of the plugin object) for the callback
	 *  @return none
	 */
	public function registerCallback($callback, $func) {
		$this->callbacks[$callback] = $func;
	}
	
	/**
	 * Register all possible scopings to the framework in the form
	 *    array("skus" => "products" , "products" => "products")
	 * This registers functions evaluate_for_skus and evaluate_for_products,
	 * which both filter products (they are identical).
	 */
	 public function registerScopings($scopings) {
		$this->available_scopings = $scopings;
	}
	
	/**
	 * Get the list of all scopings the framework implementation claims to have
	 * implemented.
	 */
	public function getScopings() {
		return $this->available_scopings;
	}
	
	public function readableString($string) {
		switch ($string) {
			case "OTSHIPMENT_RULES_CUSTOMFUNCTIONS_ALREADY_DEFINED":
					return "Custom function %s already defined. Ignoring this definition and using previous one.";
			case "OTSHIPMENT_RULES_CUSTOMFUNCTIONS_NOARRAY":
					return "Definition of custom functions (returned by a plugin) is not a proper array. Ignoring.";
			case "OTSHIPMENT_RULES_EVALUATE_ASSIGNMENT_TOPLEVEL":
					return "Assignments are not allowed inside expressions (rule given was '%s')";
			case "OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_ARGS":
					return "List function '%s' requires all arguments to be lists. (Full rule: '%s')";
			case "OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_CONTAIN_ARGS":
					return "List function '%s' requires the first argument to be lists. (Full rule: '%s')";
			case "OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_UNKNOWN":
					return "Unknown list function '%s' encountered. (Full rule: '%s')";
			case "OTSHIPMENT_RULES_EVALUATE_SYNTAXERROR":
					return "Syntax error during evaluation, RPN is not well formed! (Full rule: '%s')";
			case "OTSHIPMENT_RULES_EVALUATE_UNKNOWN_ERROR":
					return "Unknown error occurred during evaluation of rule '%s'.";
			case "OTSHIPMENT_RULES_EVALUATE_UNKNOWN_FUNCTION":
					return "Unknown function '%s' encountered during evaluation of rule '%s'.";
			case "OTSHIPMENT_RULES_EVALUATE_UNKNOWN_VALUE":
					return "Evaluation yields unknown value while evaluating rule part '%s'.";
			case "OTSHIPMENT_RULES_NOSHIPPING_MESSAGE":
					return "%s";
			case "OTSHIPMENT_RULES_PARSE_FUNCTION_NOT_CLOSED":
					return "Error during parsing expression '%s': A function call was not closed properly!";
			case "OTSHIPMENT_RULES_PARSE_MISSING_PAREN":
					return "Error during parsing expression '%s': Opening parenthesis cannot be found!";
			case "OTSHIPMENT_RULES_PARSE_PAREN_NOT_CLOSED":
					return "Error during parsing expression '%s': A parenthesis was not closed properly!";
			case "OTSHIPMENT_RULES_UNKNOWN_OPERATOR":
					return "Unknown operator '%s' in shipment rule '%s'";
			case "OTSHIPMENT_RULES_UNKNOWN_TYPE":
					return "Unknown rule type '%s' encountered for rule '%s'";
			case "OTSHIPMENT_RULES_SCOPING_UNKNOWN":
					return "Unknown scoping function 'evaluate_for_%s' encountered in rule '%s'";
			case "OTSHIPMENT_RULES_UNKNOWN_VARIABLE":
					return "Unknown variable '%s' in rule '%s'";
			default:
					return $string;
		}
	}
	
	public function __($string) {
		$args = func_get_args();

		if (isset($this->callbacks["translate"])) {
			return call_user_func_array($this->callbacks["translate"], $args);
		} else {
			if (count($args)>1) {
				return call_user_func_array("sprintf", $args);
			} else {
				return $string;
			}
		}
	}

	/** @tag system-specific
	 *  @function getCustomFunctions() 
	 *    Let other plugins add custom functions! 
	 *    This function is expected to return an array of the form:
	 *        array ('functionname1' => 'function-to-be-called',
	 *               'functionname2' => array($classobject, 'memberfunc')),
	 *               ...);
	 */
	function getCustomFunctions() {
		return array ();
	}
	
	function getCustomFunctionDefinitions() {
		return $this->custom_functions;
	}
	
	/** @tag public-api
	 *  @tag system-specific
	 *  @function message()
	 *    Print a message (to be translated) with given type in the system-specific way.
	 *  @param $message the message to be printed
	 *  @param $type the type of message (one of "error", "warning", "message"/"notice" or "debug")
	 *  @param $args optional arguments to be inserted into the translated message in sprintf-style
	 */
	public function message($message, $type) {
		$args = func_get_args();
		// Remove the $type from the args passed to __
		unset($args[1]);
		$msg = call_user_func_array(array($this, "__"), $args);
		$this->printMessage($msg, $type);
	}
	
	/** @tag public-api
	 *  @tag system-specific
	 *  @function error()
	 *    Print an error message (to be translated) in the system-specific way.
	 *  @param $message the error message to be printed 
	 *  @param $args optional arguments to be inserted into the translated message in sprintf-style
	 */
	public function error($message) {
		$args = func_get_args();
		array_splice($args, 1, 0, 'error'); // insert msg type in second position
		call_user_func_array(array($this, "message"), $args);
	}
	
	/** @tag public-api
	 *  @tag system-specific
	 *  @function warning()
	 *    Print a warning (to be translated) in the system-specific way.
	 *  @param $message the warning message to be printed 
	 *  @param $args optional arguments to be inserted into the translated message in sprintf-style
	 */
	public function warning($message) {
		$args = func_get_args();
		array_splice($args, 1, 0, 'warning'); // insert msg type in second position
		call_user_func_array(array($this, "message"), $args);
	}
	
	/** @tag public-api
	 *  @tag system-specific
	 *  @function notice()
	 *    Print a message (to be translated) in the system-specific way.
	 *  @param $message the message to be printed 
	 *  @param $args optional arguments to be inserted into the translated message in sprintf-style
	 */
	public function notice($message) {
		$args = func_get_args();
		array_splice($args, 1, 0, 'notice'); // insert msg type in second position
		call_user_func_array(array($this, "message"), $args);
	}
	
	/** @tag public-api
	 *  @tag system-specific
	 *  @function debug()
	 *    Print a debug message in the system-specific way.
	 *  @param $message the message to be printed 
	 *  @param $args optional arguments to be inserted into the translated message in sprintf-style
	 */
	public function debug($message) {
		$args = func_get_args();
		array_splice($args, 1, 0, 'debug'); // insert msg type in second position
		call_user_func_array(array($this, "message"), $args);
	}
	
	/** @tag system-specific
	 *  @function printMessage()
	 *    Print a message of given type in the system-specific way.
	 *  @param $message the message to be printed (already properly translated)
	 *  @param $type the type of message (one of "error", "warning", "message"/"notice" or "debug")
	 */
	protected function printMessage($message, $type) {
		echo($message);
	}
	
	/** @tag public-api
	 *  @function setup
	 *    Initialize the framework. Currently this only sets up plugin-defined custom functions
	 */
	public function setup() {
		$custfuncdefs = $this->getCustomFunctions();
		// Now loop through all custom function definitions of this plugin
		// If a function was registered before, print a warning and use the first definition
		foreach ($custfuncdefs as $fname => $func) {
			if (isset($this->custom_functions[$fname]) && $this->custom_functions[$fname]!=$func) {
				$this->warning('OTSHIPMENT_RULES_CUSTOMFUNCTIONS_ALREADY_DEFINED', $fname);
			} else {
				$this->debug("Defining custom function $fname");
				$this->custom_functions[strtolower($fname)] = $func;
			}
		}
	}
	
	protected function getMethodId($method) {
		return 0;
	}
	protected function getMethodName($method) {
		return '';
	}

	/**
	 * Functions to calculate the cart variables:
	 *   - getOrderArticles($cart, $products)
	 *   - getOrderProducts
	 *   - getOrderDimensions
	 */
	/** Functions to calculate all the different variables for the given cart and given (sub)set of products in the cart */
	protected function getOrderCounts ($cart, $products, $method) {
		return array(
			'articles' => 0, 
			'products' => count($products),
			'minquantity' => 9999999999,
			'maxquantity' => 0,
		);
	}
	
	protected function getDateTimeVariables($cart, $products, $method) {
		$utime = microtime(true);
		$milliseconds = (int)(1000*($utime - (int)$utime));
		$millisecondsstring = sprintf('%03d', $milliseconds);
		return array(
			'year'        => date("Y", $utime),
			'year2'       => date("y", $utime),
			'month'       => date("m", $utime),
			'day'         => date("d", $utime),
			'weekday'     => date("N", $utime),
			'hour'        => date("H", $utime),
			'hour12'      => date("h", $utime),
			'ampm'        => date("a", $utime),
			'minute'      => date("i", $utime),
			'second'      => date("s", $utime),
			'decisecond'  => $millisecondsstring[0],
			'centisecond' => substr($millisecondsstring, 0, 2),
			'millisecond' => $millisecondsstring,
		);
	}

	protected function getOrderDimensions ($cart, $products, $method) {
		return array();
	}
	
	protected function getOrderWeights ($cart, $products, $method) {
		return array();
	}
	
	protected function getOrderListProperties ($cart, $products, $method) {
		return array();
	}
	
	protected function getOrderUser ($cart, $method) {
		return array();
	}
	
	protected function getOrderAddress ($cart, $method) {
		return array();
	}
	
	protected function getOrderPrices ($cart, $products, $method) {
		return array();
	}
	
	protected function getDebugVariables ($cart, $products, $method) {
		
		return array(
			'debug_cart'=> ''/*print_r($cart,1)*/,
			'debug_products' => ''/*print_r($products, 1)*/,
		);
	}

	/** 
	 * Extract information about non-numerical zip codes (UK and Canada) from the postal code
	 */
	public function getAddressZIP ($zip) {
		$values = array();

		// Postal code Check for UK postal codes: Use regexp to determine if ZIP structure matches and also to extract the parts.
		// Also handle UK overseas areas/islands that use four-letter outward codes rather than "A{1,2}0{1,2}A{0,1} 0AA"
		$zip=strtoupper($zip);
		if (isset($zip) and preg_match('/^\s*(([A-Z]{1,2})(\d{1,2})([A-Z]?)|[A-Z]{4}|GIR)\s*(\d[A-Z]{2})\s*$/', $zip, $match)) {
			$values['uk_outward'] = $match[1];
			$values['uk_area'] = $match[2];
			$values['uk_district'] = $match[3];
			$values['uk_subdistrict'] = $match[4];
			$values['uk_inward'] = $match[5];
		} else {
			$values['uk_outward'] = NULL;
			$values['uk_area'] = NULL;
			$values['uk_district'] = NULL;
			$values['uk_subdistrict'] = NULL;
			$values['uk_inward'] = NULL;
		}
		// Postal code Check for Canadian postal codes: Use regexp to determine if ZIP structure matches and also to extract the parts.
		if (isset($zip) and preg_match('/^\s*(([A-Za-z])(\d)([A-Za-z]))\s*(\d[A-Za-z]\d)\s*$/', $zip, $match)) {
			$values['canada_fsa'] = $match[1];
			$values['canada_area'] = $match[2];
			$values['canada_urban'] = $match[3];
			$values['canada_subarea'] = $match[4];
			$values['canada_ldu'] = $match[5];
		} else {
			$values['canada_fsa'] = NULL;
			$values['canada_area'] = NULL;
			$values['canada_urban'] = NULL;
			$values['canada_subarea'] = NULL;
			$values['canada_ldu'] = NULL;
		}
		// print("<pre>values: ".print_r($values,1)."</pre>");
		return $values;
	}

	/** Allow child classes to add additional variables for the rules or modify existing one
	 */
	protected function addCustomCartValues ($cart, $products, $method, &$values) {
		// Pass all args through to the callback, if it exists
		if (isset($this->callbacks['addCustomCartValues'])) {
			return call_user_func_array($this->callbacks['addCustomCartValues'], array($cart, $products, $method, &$values)/*func_get_args()*/);
		}
		return $values;
	}
	protected function addPluginCartValues($cart, $products, $method, &$values) {
		return $values;
	}
	
	public function getCartValues ($cart, $products, $method) {
		$cartvals = array_merge (
			$this->getDateTimeVariables($cart, $products, $method),
			$this->getOrderCounts($cart, $products, $method),
			// Add the prices, optionally calculated from the products subset of the cart
			$this->getOrderPrices ($cart, $products, $method),
			// Add 'skus', 'categories', 'vendors' variables:
			$this->getOrderListProperties ($cart, $products, $method),
			// Add country / state variables:
			$this->getOrderAddress ($cart, $method),
			// Add Customer information:
			$this->getOrderUser ($cart, $method),
			// Add Total/Min/Max weight and dimension variables:
			$this->getOrderWeights ($cart, $products, $method),
			$this->getOrderDimensions ($cart, $products, $method),
			//$this->getDebugVariables ($cart, $products, $method)

		);
		// Let child classes update the $cartvals array, or add new variables
		$this->addCustomCartValues($cart, $products, $method, $cartvals);
		// Let custom plugins update the $cartvals array or add new variables
		$this->addPluginCartValues($cart, $products, $method, $cartvals);

		return $cartvals;
	}
	
	protected function getCartProducts($cart, $method) {
		return array();
	}

	/** This function evaluates all rules, one after the other until it finds a matching rule that
	 *  defines shipping costs (or uses NoShipping). If a modifier or definition is encountered,
	 *  its effect is stored, but the loop continues */
	protected function evaluateMethodRules ($cart, $method) {
		$id = $this->getMethodId($method);
		// $this->match will cache the matched rule and the modifiers
		if (isset($this->match[$id])) {
			return $this->match[$id];
		} else {
			// Evaluate all rules and find the matching ones (including modifiers and definitions!)
			$cartvals = $this->getCartValues ($cart, $this->getCartProducts($cart, $method), $method);
			$result = array(
				"rule" => Null,
				"rule_name" => "",
				"modifiers_add"=> array(),
				"modifiers_multiply" => array(),
				"cartvals" => $cartvals,
			);
			// Pass a callback function to the rules to obtain the cartvals for a subset of the products
			$this_class = $this;
			$cartvals_callback = function ($products) use ($this_class, $cart, $method) {
				return $this_class->getCartValues ($cart, $products, $method, NULL);
			};
			if (isset($this->rules[$id])) {
				foreach ($this->rules[$id] as $r) {
					if ($r->matches($cartvals, $this->getCartProducts($cart, $method), $cartvals_callback)) {
						$rtype = $r->getType();
						switch ($rtype) {
							case 'shipping': 
							case 'shippingwithtax':
							case 'noshipping': 
									$result["rule"] = $r;
									$result["rule_name"] = $r->getRuleName();
									break;
							case 'modifiers_add':
							case 'modifiers_multiply':
									$result[$rtype][] = $r;
									break;
							case 'definition': // A definition updates the $cartvals, but has no other effects
									$cartvals[strtolower($r->getRuleName())] = $r->getValue();
									break;
							default:
									$this->warning('OTSHIPMENT_RULES_UNKNOWN_TYPE', $r->getType(), $r->rulestring);
									break;
						}
						// Handle messages (error, warning, message/notice, debug:
						foreach ($r->messages as $k=>$msgs) {
							foreach ($msgs as $msg) {
								$this->message($msg, $k);
							}
						}
					}
					if (!is_null($result["rule"])) {
						$this->match[$id] = $result;
						return $result; // <- This also breaks out of the foreach loop!
					}
				}
			}
		}
		// None of the rules matched, so return NULL, but keep the evaluated results;
		$this->match[$id] = NULL;
		return NULL;
	}

	protected function handleNoShipping($match, $method) {
		if ($match['rule']->isNoShipping()) {
			if (!empty($match["rule_name"]))
				$this->warning('OTSHIPMENT_RULES_NOSHIPPING_MESSAGE', $match["rule_name"]);
			$name = $this->getMethodName($method);
			$this->debug('checkConditions '.$name.' indicates NoShipping for this method, specified by rule "'.$match["rule_name"].'" ('.$match['rule']->rulestring.').');
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * @param $cart
	 * @param int             $method
	 * @return bool
	 */
	public function checkConditions ($cart, $method, $cart_prices) {

		$id = $this->getMethodId($method);
		$name = $this->getMethodName($method);
		if (!isset($this->rules[$id])) 
			$this->parseMethodRules($method);
		// TODO: This needs to be redone sooner or later!
		$match = $this->evaluateMethodRules ($cart, $method);
		if ($match && isset($match['rule']) && !is_null ($match['rule'])) {
			$this->setMethodCosts($method, $match, null);
			// If NoShipping is set, this method should NOT offer any shipping at all, so return FALSE, otherwise TRUE
			// If the rule has a name, print it as warning (otherwise don't print anything)
			if ($this->handleNoShipping($match, $method)) {
				return FALSE;
			}
			return TRUE;
		}
		$this->debug('checkConditions '.$name.' does not fulfill all conditions, no rule matches');
		return FALSE;
	}
	
	/**
	 * @tag system-specific
	 */
	protected function setMethodCosts($method, $match, $costs) {
		// Allow some system-specific code, e.g. setting some members of $method, etc.
	}

	/**
	 * @param $cart
	 * @param                $method
	 * @return int
	 */
	function getCosts ($cart, $method) {
		$results = array();
		$id = $this->getMethodId($method);
		if (!isset($this->rules[$id])) 
			$this->parseMethodRules($method);
		$match = $this->evaluateMethodRules ($cart, $method);
		if ($match && isset($match['rule']) && !is_null ($match['rule'])) {
			if ($this->handleNoShipping($match, $method)) {
				return $results;
			}
		
			$r = $match["rule"];
			$this->debug('Rule ' . $match["rule_name"] . ' ('.$r->rulestring.') matched.');

			// Final shipping costs are calculated as:
			//   Shipping*ExtraShippingMultiplier + ExtraShippingCharge
			// with possibly multiple modifiers
			$cost = $r->getShippingCosts();
			foreach ($match['modifiers_multiply'] as $modifier) {
				$cost *= $modifier->getValue();
			}
			foreach ($match['modifiers_add'] as $modifier) {
				$cost += $modifier->getValue();
			}
			$this->setMethodCosts($method, $match, $cost);

			$res = array(
				'method' =>   $id,
				'name' =>     $this->getMethodName($method),
// 				'rulesetname'=>$match['ruleset_name'],
				'rulename' => $match["rule_name"],
				'cost' =>     $cost,
			);
			$results[] = $res;
		}
		
		if (empty($results)) {
			$this->debug('getCosts '.$this->getMethodName($method).' does not return shipping costs');
		}
		return $results;
	}
	
	public function getRuleName($methodid) {
		if (isset($this->match[$methodid])) {
			return $this->match[$methodid]["rule_name"];
		} else {
			return '';
		}
	}

	public function getRuleVariables($methodid) {
		if (isset($this->match[$methodid])) {
			return $this->match[$methodid]["cartvals"];
		} else {
			return array();
		}
	}

	protected function createMethodRule ($r, $countries, $ruleinfo) {
		if (isset($this->callbacks['initRule'])) {
			return call_user_func_array($this->callbacks['initRule'], 
										array($this, $r, $countries, $ruleinfo));
		} else {
			return new ShippingRule($this, $r, $countries, $ruleinfo);
		}
	}

	// Parse the rule and append all rules to the rule set of the current shipment method (country/tax are already included in the rule itself!)
	protected function parseMethodRule ($rulestring, $countries, $ruleinfo, &$method) {
		$id = $this->getMethodId($method);
		foreach ($this->parseRuleSyntax($rulestring, $countries, $ruleinfo) as $r) {
			$this->rules[$id][] = $r;
		}
	}
	
	public function parseRuleSyntax($rulestring, $countries, $ruleinfo) {
		$result = array();
		$rules1 = preg_split("/(\r\n|\n|\r)/", $rulestring);
		foreach ($rules1 as $r) {
			// Ignore empty lines
			if (empty($r) || trim($r)=='') continue;
			$result[] = $this->createMethodRule ($r, $countries, $ruleinfo);
		}
		return $result;
	}
	
	protected function parseMethodRules (&$method) {
		$this->warning("parseMethodRules not reimplemented => No rules will be loaded!");
	}

	/** Filter the given array of products and return only those that belong to the categories, manufacturers, 
	 *  vendors or products given in the $filter_conditions. The $filter_conditions is an array of the form:
	 *     array( 'skus'=>array(....), 'categories'=>array(1,2,3,42), 'manufacturers'=>array(77,78,83), 'vendors'=>array(1,2))
	 *  Notice that giving an empty array for any of the keys means "no restriction" and is exactly the same 
	 *  as leaving out the enty altogether
	 */
	public function filterProducts($products, $filter_conditions) {
		return array();
	}
}

class ShippingRule {
	var $framework = Null;
	var $rulestring = '';
	var $name = '';
	var $ruletype = '';
	var $evaluated = False;
	var $match = False;
	var $value = Null;
	
	var $shipping = 0;
	var $conditions = array();
	var $countries = array();
	var $ruleinfo = 0;
	var $includes_tax = 0;
	var $messages = array('error' => array(), 'warning' => array(), 'message' => array(), 'notice' => array(), 'debug' => array());
	
	function __construct ($framework, $rule, $countries, $ruleinfo) {
		$this->framework = $framework;
		if (is_array($countries)) {
			$this->countries = $countries;
		} elseif (!empty($countries)) {
			$this->countries[0] = $countries;
		}
		$this->ruleinfo = $ruleinfo;
		$this->rulestring = $rule;
		$this->parseRule($rule);
	}
	
	protected function parseRule($rule) {
		$ruleparts=explode(';', $rule);
		foreach ($ruleparts as $p) {
			$this->parseRulePart($p);
		}
	}
	
	protected function handleAssignment ($var, $value, $rulepart) {
		switch (strtolower($var)) {
			case 'name':            $this->name = $value; break;
			case 'shipping':        $this->shipping = $value; $this->includes_tax = False; $this->ruletype='shipping'; break;
			case 'shippingwithtax': $this->shipping = $value; $this->includes_tax = True; $this->ruletype='shipping'; break;
			case 'variable':        // Variable=... is the same as Definition=...
			case 'definition':      $this->name = strtolower($value); $this->ruletype = 'definition'; break;
			case 'value':           $this->shipping = $value; $this->ruletype = 'definition'; break; // definition values are also stored in the shipping member!
			case 'extrashippingcharge': $this->shipping = $value; $this->ruletype = 'modifiers_add'; break; // modifiers are also stored in the shipping member!
			case 'extrashippingmultiplier': $this->shipping = $value; $this->ruletype = 'modifiers_multiply'; break; // modifiers are also stored in the shipping member!
			case 'comment':         break; // Completely ignore all comments!
			case 'error':			$this->messages['error'][] = $value; break;
			case 'warning':			$this->messages['warning'][] = $value; break;
			case 'notice':			$this->messages['notice'][] = $value; break;
			case 'message':			$this->messages['message'][] = $value; break;
			case 'debug':			$this->messages['debug'][] = $value; break;
			case 'condition':       $this->conditions[] = $value; break;
			default:                $this->framework->warning('OTSHIPMENT_RULES_UNKNOWN_VARIABLE', $var, $rulepart);
		}
	}
	
	protected function tokenize_expression ($expression) {
		// First, extract all strings, delimited by quotes, then all text operators 
		// (OR, AND, in; but make sure we don't capture parts of words, so we need to 
		// use lookbehind/lookahead patterns to exclude OR following another letter 
		// or followed by another letter) and then all arithmetic operators
		$re = '/\s*("[^"]*"|\'[^\']*\'|<=|=>|>=|=<|<>|!=|==|<|=|>)\s*/i';
		$atoms = preg_split($re, $expression, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		return $atoms;
	}
	
	protected function parseRulePart($rulepart) {
		/* In the basic version, we only split at the comparison operators and assume each term on the LHS and RHS is one variable or constant */
		/* In the advanced version, all conditions and costs can be given as a full mathematical expression */
		/* Both versions create an expression tree, which can be easily evaluated in evaluateTerm */
		$rulepart = trim($rulepart);
		if (!isset($rulepart) || $rulepart==='') return;

		// Special-case the name assignment, where we don't want to interpret the value as an arithmetic expression!
		if (preg_match('/^\s*(name|variable|definition|error|warning|message|notice|debug)\s*=\s*(["\']?)(.*)\2\s*$/i', $rulepart, $matches)) {
			$this->handleAssignment ($matches[1], $matches[3], $rulepart);
			return;
		}

		// Split at all operators:
		$atoms = $this->tokenize_expression ($rulepart);
		
		/* Starting from here, the advanced plugin is different! */
		$operators = array('<', '<=', '=', '>', '>=', '=>', '=<', '<>', '!=', '==');
		if (count($atoms)==1) {
			$this->shipping = $this->parseShippingTerm($atoms[0]);
			$this->ruletype = 'shipping';
		} elseif ($atoms[1]=='=') {
			$this->handleAssignment ($atoms[0], $atoms[2], $rulepart);
		} else {
			// Conditions, need at least three atoms!
			while (count($atoms)>1) {
				if (in_array ($atoms[1], $operators)) {
					$this->conditions[] = array($atoms[1], $this->parseShippingTerm($atoms[0]), $this->parseShippingTerm($atoms[2]));
					array_shift($atoms);
					array_shift($atoms);
				} else {
					$this->framework->warning('OTSHIPMENT_RULES_UNKNOWN_OPERATOR', $atoms[1], $rulepart);
					$atoms = array();
				}
			}
		}
	}

	protected function parseShippingTerm($expr) {
		/* In the advanced version, shipping cost can be given as a full mathematical expression */
		// If the shipping term starts with a double quote, it is a string, so don't turn it into lowercase.
		// All other expressions need to be turned into lowercase, because variable names are case-insensitive!
		if (substr($expr, 0, 1) === '"') {
			return $expr;
		} else {
			return strtolower($expr);
		}
	}
	
	protected function evaluateComparison ($terms, $vals) {
		while (count($terms)>2) {
			$res = false;
			switch ($terms[1]) {
				case '<':  $res = ($terms[0] < $terms[2]);  break;
				case '<=':
				case '=<': $res = ($terms[0] <= $terms[2]); break;
				case '==': $res = is_equal($terms[0], $terms[2]); break;
				case '!=':
				case '<>': $res = ($terms[0] != $terms[2]); break;
				case '>=':
				case '=>': $res = ($terms[0] >= $terms[2]); break;
				case '>':  $res = ($terms[0] >  $terms[2]);  break;
				case '~':
					$l=min(strlen($terms[0]), strlen($terms[2]));
					$res = (strncmp ($terms[0], $terms[2], $l) == 0);
					break;
				default:
					$this->framework->warning('OTSHIPMENT_RULES_UNKNOWN_OPERATOR', $terms[1], $this->rulestring);
					$res = false;
			}

			if ($res==false) return false;
			// Remove the first operand and the operator from the comparison:
			array_shift($terms);
			array_shift($terms);
		}
		if (count($terms)>1) {
			// We do not have the correct number of terms for chained comparisons, i.e. two terms leftover instead of one!
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_UNKNOWN_ERROR', $this->rulestring);
			return false;
		}
		// All conditions were fulfilled, so we can return true
		return true;
	}
	
	protected function evaluateListFunction ($function, $args) {
		# First make sure that all arguments are actually lists:
		$allarrays = True;
		foreach ($args as $a) {
			$allarrays = $allarrays && is_array($a);
		}
		if (!$allarrays) {
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_ARGS', $function, $this->rulestring);
			return false;
			
		}
		switch ($function) {
			case "length":		return count($args[0]); break;
			case "union": 
			case "join":		return call_user_func_array( "array_merge" , $args); break;
			case "complement":	return call_user_func_array( "array_diff" , $args); break;
			case "intersection":	return call_user_func_array( "array_intersect" , $args); break;
			case "issubset":	# Remove all of superset's elements to see if anything else is left: 
						return !array_diff($args[0], $args[1]); break;
			case "contains":	# Remove all of superset's elements to see if anything else is left: 
						# Notice the different argument order compared to issubset!
						return !array_diff($args[1], $args[0]); break;
			case "list_equal":	return array_unique($args[0])==array_unique($args[1]); break;
			default: 
				$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_UNKNOWN', $function, $this->rulestring);
				return false;
		}
	}
	
	protected function evaluateListContainmentFunction ($function, $args) {
		# First make sure that the first argument is a list:
		if (!is_array($args[0])) {
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_CONTAIN_ARGS', $function, $this->rulestring);
			return false;
		}
		// Extract the array from the args, the $args varialbe will now only contain the elements to be checked:
		$array = array_shift($args);
		switch ($function) {
			case "contains_any": // return true if one of the $args is in the $array
					foreach ($args as $a) { 
						if (in_array($a, $array)) 
							return true; 
					}
					return false;
			
			case "contains_all": // return false if one of the $args is NOT in the $array
					foreach ($args as $a) { 
						if (!in_array($a, $array)) 
							return false; 
					}
					return true;
			case "contains_only": // return false if one of the $array elements is NOT in $args
					foreach ($array as $a) {
						if (!in_array($a, $args))
							return false;
					}
					return true;
			case "contains_none": // return false if one of the $args IS in the $array
					foreach ($args as $a) {
						if (in_array($a, $array))
							return false;
					}
					return true;
			default: 
				$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_LISTFUNCTION_UNKNOWN', $function, $this->rulestring);
				return false;
		}
	}
	
	protected function normalizeScoping($scoping) {
		$scopings = $this->framework->getScopings();
// $this->framework->warning("<pre>normalizing Scoping $scoping. Registered scopings are: ".print_r($scopings,1)."</pre>");
		if (isset($scopings[$scoping])) {
			return $scopings[$scoping];
		} else {
			return false;
		}
	}
	
	/** Evaluate the given expression $expr only for the products that match the filter given by the scoping 
	 * function and the corresponding conditions */
	protected function evaluateScoping($expr, $scoping, $conditionvals, $vals, $products, $cartvals_callback) {
		if (count($conditionvals)<1)
			return $this->evaluateTerm($expr, $vals, $products, $cartvals_callback);
		
// $this->framework->warning("<pre>evaluating scoping $scoping of expression ".print_r($expr,1)." with conditions ".print_r($conditionvals,1)."</pre>");
		// Normalize aliases (e.g. 'skus' and 'products' usually indicate the same scoping
		$normalizedScoping = $this->normalizeScoping($scoping);
		if (!$normalizedScoping) {
			$this->framework->warning('OTSHIPMENT_RULES_SCOPING_UNKNOWN', $scoping, $this->rulestring);
			return false;
		} else {
			$conditions = array($normalizedScoping => $conditionvals);
		}

		// Pass the conditions to the parent plugin class to filter the current list of products:
		$filteredproducts = $this->framework->filterProducts($products, $conditions);
		// We have been handed a callback function to calculate the cartvals for the filtered list of products, so use it:
		$filteredvals = $cartvals_callback($filteredproducts);
		return $this->evaluateTerm ($expr, $filteredvals, $filteredproducts, $cartvals_callback);
	}

	protected function evaluateFunction ($function, $args) {
		$func = strtolower($function);
		// Check if we have a custom function definition and use that if so.
		// This is done first to allow plugins to override even built-in functions!
		$customfunctions = $this->framework->getCustomFunctionDefinitions();
		if (isset($customfunctions[$func])) {
			$this->framework->debug("Evaluating custom function $function, defined by a plugin");
			return call_user_func($customfunctions[$func], $args, $this);
		}

		// Functions with no argument:
		if (count($args) == 0) {
			$dt = getdate();
			switch ($func) {
				case "second": return $dt['seconds']; break;
				case "minute": return $dt['minutes']; break;
				case "hour":   return $dt['hours']; break;
				case "day":    return $dt['mday']; break;
				case "weekday":return $dt['wday']; break;
				case "month":  return $dt['mon']; break;
				case "year":   return $dt['year']; break;
				case "yearday":return $dt['yday']; break;
			}
		}
		// Functions with exactly one argument:
		if (count($args) == 1) {
			switch ($func) {
				case "round": return round($args[0]); break;
				case "ceil":  return ceil ($args[0]); break;
				case "floor": return floor($args[0]); break;
				case "abs":   return abs($args[0]); break;
				case "not":   return !$args[0]; break;
				case "print_r": return print_r($args[0],1); break; 
			}
		}
		if (count($args) == 2) {
			switch ($func) {
				case "digit": return substr($args[0], $args[1]-1, 1); break;
				case "round": return round($args[0]/$args[1])*$args[1]; break;
				case "ceil":  return ceil($args[0]/$args[1])*$args[1]; break;
				case "floor": return floor($args[0]/$args[1])*$args[1]; break;
			}
		}
		if (count($args) == 3) {
			switch ($func) {
				case "substring": return substr($args[0], $args[1]-1, $args[2]); break;
			}
		}
		// Functions with variable number of args
		switch ($func) {
			case "max": 
					return max($args);
			case "min": 
					return min($args);
			case "list": 
			case "array": 
					return $args;
			// List functions:
		    case "length":
		    case "complement":
		    case "issubset":
		    case "contains":
		    case "union":
		    case "join":
		    case "intersection":
		    case "list_equal":
					return $this->evaluateListFunction ($func, $args);
			case "contains_any": 
			case "contains_all":
			case "contains_only":
			case "contains_none":
					return $this->evaluateListContainmentFunction($func, $args);
			
		}
		
		// None of the built-in function 
		// No known function matches => print an error, return 0
		$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_UNKNOWN_FUNCTION', $function, $this->rulestring);
		return 0;
	}

	protected function evaluateVariable ($expr, $vals) {
		$varname = strtolower($expr);
		if (array_key_exists(strtolower($expr), $vals)) {
			return $vals[strtolower($expr)];
		} elseif ($varname=='noshipping') {
			return $varname;
		} elseif ($varname=='values') {
			return $vals;
		} elseif ($varname=='values_debug' || $varname=='debug_values') {
			$tmpvals = $vals;
			unset($tmpvals['debug_cart']);
			unset($tmpvals['debug_products']);
			return print_r($tmpvals,1);
		} else {
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_UNKNOWN_VALUE', $expr, $this->rulestring);
			return null;
		}
	}

	protected function evaluateTerm ($expr, $vals, $products, $cartvals_callback) {
		// The scoping functions need to be handled differently, because they first need to adjust the cart variables to the filtered product list
		// before evaluating its first argument. So even though parsing the rules handles scoping functions like any other function, their 
		// evaluation is fundamentally different and is special-cased here:
		$is_scoping = is_array($expr) && ($expr[0]=="FUNCTION") && (count($expr)>1) && (substr($expr[1], 0, 13)==="evaluate_for_");

		if (is_null($expr)) {
			return $expr;
		} elseif (is_numeric ($expr)) {
			return $expr;
		} elseif (is_string ($expr)) {
			// Explicit strings are delimited by '...' or "..."
			if (($expr[0]=='\'' || $expr[0]=='"') && ($expr[0]==substr($expr,-1)) ) {
				return substr($expr,1,-1);
			} else {
				return $this->evaluateVariable($expr, $vals);
			}
		} elseif ($is_scoping) {
			$op = array_shift($expr); // ignore the "FUNCTION"
			$scope = substr(array_shift($expr), 13); // The scoping function name with "evaluate_for_" cut off
			$expression = array_shift($expr); // The expression to be evaluated
			// the remaining $expr list now contains the conditions. Evaluate them one by one:
			$conditions = array();
			foreach ($expr as $e) {
				$conditions[] = $this->evaluateTerm($e, $vals, $products, $cartvals_callback);
			}
			return $this->evaluateScoping ($expression, $scope, $conditions, $vals, $products, $cartvals_callback);
			
		} elseif (is_array($expr)) {
			// Operator
			$op = array_shift($expr);
			$args = array();
			// First evaluate all operands and only after that apply the function / operator to the already evaluated arguments
			$evaluate = true;
			if ($op == "FUNCTION") {
				$evaluate = false;
			}
			foreach ($expr as $e) {
				$term = $evaluate ? ($this->evaluateTerm($e, $vals, $products, $cartvals_callback)) : $e;
				if ($op == 'COMPARISON') {
					// For comparisons, we only evaluate every other term (the operators are NOT evaluated!)
					// The data format for comparisons is: array('COMPARISON', $operand1, '<', $operand2, '<=', ....)
					$evaluate = !$evaluate;
				}
				if ($op == "FUNCTION") {
					$evaluate = true;
				}
				if (is_null($term)) return null;
				$args[] = $term;
			}
			$res = false;
			// Finally apply the operaton to the evaluated argument values:
			switch ($op) {
				// Logical operators:
				case 'OR':  foreach ($args as $a) { $res = ($res || $a); }; break;
				case '&&':
				case 'AND':  $res = true; foreach ($args as $a) { $res = ($res && $a); }; break;
				case 'IN': $res = in_array($args[0], $args[1]);  break;
				
				// Comparisons:
				case '<':
				case '<=':
				case '=<':
				case '==':
				case '!=':
				case '<>':
				case '>=':
				case '=>':
				case '>':
				case '~':
					$res = $this->evaluateComparison(array($args[0], $op, $args[1]), $vals); break;
				case 'COMPARISON':
					$res = $this->evaluateComparison($args, $vals); break;
				
				// Unary operators:
				case '.-': $res = -$args[0]; break;
				case '.+': $res = $args[0]; break;
				
				// Binary operators
				case "+":  $res = ($args[0] +  $args[1]); break;
				case "-":  $res = ($args[0] -  $args[1]); break;
				case "*":  $res = ($args[0] *  $args[1]); break;
				case "/":  $res = ($args[0] /  $args[1]); break;
				case "%":  $res = (fmod($args[0],  $args[1])); break;
				case "^":  $res = ($args[0] ^  $args[1]); break;
				
				// Functions:
				case "FUNCTION": $func = array_shift($args); $res = $this->evaluateFunction($func, $args); break;
				
				default:   $res = false;
			}
			
			return $res;
		} else {
			// Neither string nor numeric, nor operator...
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_UNKNOWN_VALUE', $expr, $this->rulestring);
			return null;
		}
	}

	protected function calculateShipping($vals, $products, $cartvals_callback) {
		return $this->evaluateTerm($this->shipping, $vals, $products, $cartvals_callback);
	}
	
	protected function stringReplaceVariables($str, $vals) {
		// Evaluate the rule name as a translatable string with variables inserted:
		// Replace all {variable} tags in the name by the variables from $vals
		$matches = array();
		preg_match_all('/{([A-Za-z0-9_]+)}/', $str, $matches);
		
		foreach ($matches[1] as $m) {
			$val = $this->evaluateVariable($m, $vals);
			if ($val !== null) {
				$str = str_replace("{".$m."}", $val, $str);
			}
		}
		return $str;
	
	}

	protected function evaluateRule (&$vals, $products, $cartvals_callback) {
		if ($this->evaluated) 
			return; // Already evaluated

		$this->evaluated = True;
		$this->match = False; // Default, set it to True below if all conditions match...
		// First, check the country, if any conditions are given:
		if (count ($this->countries) > 0 && !in_array ($vals['countryid'], $this->countries)) {
// 			$this->framework->debug('Rule::matches: Country check failed: countryid='.print_r($vals['countryid'],1).', countries are: '.print_r($this->countries,1).'...');
			return;
		}

		foreach ($this->conditions as $c) {
			// All conditions have to match!
			$ret = $this->evaluateTerm($c, $vals, $products, $cartvals_callback);

			if (is_null($ret) || (!$ret)) {
				return;
			}
		}
		// All conditions match
		$this->match = True;
		foreach ($this->messages as $k=>$msgs) {
			foreach ($msgs as $i=>$m) {
				// First translate the messge before replacing variables! Then translate once more. This allows one to collect translatable identifiers for rule names and then insert them into the rule name. These identifiers cannot include any variables, though.
				$this->messages[$k][$i] = $this->framework->__($this->stringReplaceVariables($this->framework->__($m), $vals));
			}
		}
		// Calculate the value (i.e. shipping cost or modifier)
		$this->value = $this->calculateShipping($vals, $products, $cartvals_callback);
		
		$this->rulename = $this->framework->__($this->stringReplaceVariables($this->framework->__($this->name), $vals));
	}

	function matches(&$vals, $products, $cartvals_callback) {
		$this->evaluateRule($vals, $products, $cartvals_callback);
		return $this->match;
	}

	function getType() {
		return $this->ruletype;
	}

	function getRuleName() {
		if (!$this->evaluated)
			$this->framework->debug('WARNING: getRuleName called without prior evaluation of the rule, e.g. by calling rule->matches(...)');
		return $this->rulename;
	}
	
	function getValue() {
		if (!$this->evaluated)
			$this->framework->debug('WARNING: getValue called without prior evaluation of the rule, e.g. by calling rule->matches(...)');
		return $this->value;
	}
	function getShippingCosts() {
		return $this->getValue();
	}
	
	function isNoShipping() {
		// NoShipping is set, so if the rule matches, this method should not offer any shipping at all
		return (is_string($this->shipping) && (strtolower($this->shipping)=="noshipping"));
	}

}

/** Extend the shipping rules by allowing arbitrary mathematical expressions
 */
class ShippingRule_Advanced extends ShippingRule {
	function __construct ($framework, $rule, $countries, $ruleinfo) {
		parent::__construct ($framework, $rule, $countries, $ruleinfo);
	}
	
	function tokenize_expression ($expression) {
		// First, extract all strings, delimited by quotes, then all text operators 
		// (OR, AND, in; but make sure we don't capture parts of words, so we need to 
		// use lookbehind/lookahead patterns to exclude OR following another letter 
		// or followed by another letter) and then all arithmetic operators
		$re = '/\s*("[^"]*"|\'[^\']*\'|(?<![A-Za-z0-9])(?:OR|AND|IN)(?![A-Za-z0-9])|&&|<=|=>|>=|=<|<>|!=|==|<|=|>|~|\+|-|\*|\/|%|\(|\)|\^|,)\s*/i';
		$atoms = preg_split($re, $expression, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		// $this->framework->warning("TOKENIZING '$expression' returns: <pre>".print_r($atoms,1)."</pre>");
		return $atoms;
	}
	

	/** parse the mathematical expressions using the Shunting Yard Algorithm by Dijkstra (with some extensions to allow arbitrary functions):
	 *  First parse the string into an array of tokens (operators and operands) by a simple regexp with known operators as separators)
	 * TODO: Update this description to include unary operators and general function calls
	 *  Then convert the infix notation into postfix (RPN), taking care of operator precedence
	 *    1) Initialize empty stack and empty result variable
	 *    2) Read infix expression from left to right, one atom at a time
	 *    3) If operand => Append to result
	 *    4) If operator:
	 *        4a) Pop operators from stack until opening parenthesis, operator of 
	 *            lower precedence or right-associative symbol of equal precedence.
	 *        4b) Push operator onto stack
	 *    5) If opening parenthesis => push onto stack
	 *    6) If closing parenthesis:
	 *        6a) Pop operators from stack until opening parenthesis is found
	 *        6b) push them to the result (not the opening parenthesis, of course)
	 *    7) At the end of the input, pop all operators from the stack and onto the result
	 *
	 *  Afterwards, convert this RPN list into an expression tree to be evaluated
	 * 
	 *  For the full algorithm, including function parsing, see Wikipedia:
	 *    http://en.wikipedia.org/wiki/Shunting_yard_algorithm
	 *
	 */
	function parseRulePart($rulepart) {
		/* In the basic version, we only split at the comparison operators and assume each term on the LHS and RHS is one variable or constant */
		/* In the advanced version, all conditions and costs can be given as a full mathematical expression */
		/* Both versions create an expression tree, which can be easily evaluated in evaluateTerm */
		$rulepart = trim($rulepart);
		if (!isset($rulepart) || $rulepart==='') return;

		
		// Special-case the name assignment, where we don't want to interpret the value as an arithmetic expression!
		if (preg_match('/^\s*(name|variable|definition|error|warning|message|notice|debug)\s*=\s*(["\']?)(.*)\2\s*$/i', $rulepart, $matches)) {
			$this->handleAssignment ($matches[1], $matches[3], $rulepart);
			return;
		}

		// Split at all operators:
		$atoms = $this->tokenize_expression ($rulepart);
		
		$operators = array(
			".-" => 100, ".+" => 100,
			"IN" => 80, 
			"^"  => 70, 
			"*"  => 60, "/"  => 60, "%"  => 60, 
			"+"  => 50, "-"  => 50, 
			"<"  => 40, "<=" => 40, ">"  => 40, ">=" => 40, "=>" => 40, "=<" => 40,
			"==" => 40, "!=" => 40, "<>" => 40, "~" => 40,
			"&&"  => 21, "AND" => 21,
			"OR"  => 20,
			"="  => 10,
			"("  =>  0, ")"  =>0 
		);
		$unary_ops = array("-" => ".-", "+" => ".+");

		// Any of these indicate a comparison and thus a condition:
		$condition_ops = array('<', '<=', '=<', '<>', '!=', '==', '>', '>=', '=>', '~', 'OR', 'AND', '&&', 'IN');
		$comparison_ops = array('<', '<=', '=<', '<>', '!=', '==', '>', '>=', '=>', '~');
		$is_condition = false;
		$is_assignment = false;
		
		$stack = array ();  // 1)/
		$prev_token_operator = true;
		$function_args = array();
		$out_stack = array();
		foreach ($atoms as $a) { // 2)
			$aupper = strtoupper($a); # All operators are converted to uppercase!
		
			if ($a == ",") { // A function argument separator
				// pop-and-apply all operators back to the left function paren
				while (count($stack)>0) { // 4a)
					$op = array_pop ($stack);
					if ($op != "FUNCTION(") {
						array_push ($out_stack, $op);
					} else {
						// No unary operator -> add it back to stack, exit loop
						array_push ($stack, $op);
						break;
					}
				} while (0);
				$this_func = array_pop($function_args);
				// Add current output stack as argument, reset temporary output stack
				if (!empty($out_stack)) $this_func[] = $out_stack;
				$function_args[] = $this_func;
				$out_stack = array();
				$prev_token_operator = true;
				
			} elseif ($a == "(" and !$prev_token_operator) { // 5) parenthesis after operand -> FUNCTION CALL
				array_push ($stack, "FUNCTION(");
				// retrieve function name from RPN list (remove last entry from operand stack!)
				$function = strtolower(array_pop ($out_stack));
				$new_stack = array();
				// Set up function call data structure on function_args stack:
				$function_args[] = array(/* old operand stack: */$out_stack, $function);
				// Use a the temporary operand stack until the closing paren restores the previous operand stack again
				$out_stack = array();
				$prev_token_operator = true;

			} elseif ($a == "(" and $prev_token_operator) { // 5) real parenthesis 
				$stack[] = $a;
				$prev_token_operator = true;
				
			} elseif ($a == ")") { // 6) parenthesis
				do {
					$op=array_pop($stack); // 6a)
					if ($op == "(") {
						break; // We have found the opening parenthesis
					} elseif ($op =="FUNCTION(") { // Function call
						// Remove function info from the functions stack; Format is array(PREVIOUS_OPERAND_STACK, FUNCTION, ARGS...)
						$this_func = array_pop ($function_args);
						// Append last argument (if not empty)
						if (!empty($out_stack)) $this_func[] = $out_stack;
						// restore old output/operand stack
						$out_stack = array_shift($this_func);
						// Function name is the next entry
						$function = array_shift($this_func);
						// All other entries are function arguments, so append them to the current operand stack
						foreach ($this_func as $a) {
							foreach ($a as $aa) {
								$out_stack[] = $aa;
							}
						}
						$out_stack[] = array("FUNCTION", $function, count($this_func));
						break; // We have found the opening parenthesis
					} elseif (!is_null($op)) {
						$out_stack[]=$op; // 6b) "normal" operators
					} else {
						// no ( and no operator, so the expression is wrong!
						$this->framework->warning('OTSHIPMENT_RULES_PARSE_MISSING_PAREN', $rulepart);
						break;
					}
				} while (true);
				$prev_token_operator = false;
				
			} elseif (isset($unary_ops[$aupper]) && $prev_token_operator) { // 4) UNARY operators
				// Unary and binary operators need to be handled differently: 
				// Unary operators must only pop other unary operators, never any binary operator
				$unary_op = $unary_ops[$aupper];
				// For unary operators, pop other unary operators from the stack until you reach an opening parenthesis, 
				// an operator of lower precedence, or a right associative symbol of equal precedence. 
				while (count($stack)>0) { // 4a)
					$op = array_pop ($stack);
					// Remove all other unary operators:
					if (in_array ($op, $unary_ops)) {
						array_push ($out_stack, $op);
					} else {
						// No unary operator -> add it back to stack, exit loop
						array_push ($stack, $op);
						break;
					}
				} while (0);
				array_push ($stack, $unary_op); // 4b)
				$prev_token_operator = true;
				
			} elseif (isset($operators[$aupper])) { // 4) BINARY operators
				$prec = $operators[$aupper];
				$is_condition |= in_array($aupper, $condition_ops);
				$is_assignment |= ($aupper == "=");
				
				// For operators, pop operators from the stack until you reach an opening parenthesis, 
				// an operator of lower precedence, or a right associative symbol of equal precedence. 
				while (count($stack)>0) { // 4a)
					$op = array_pop ($stack);
					// The only right-associative operator is =, which we allow at most once!
					if ($op == "(" || $op == "FUNCTION(") {
						// add it back to the stack!
						array_push ($stack, $op);
						break;
					} elseif ($operators[$op]<$prec) {
						// We found an operator with lower precedence, add it back to the stack!
						array_push ($stack, $op); // 4b)
						break;
					} else {
						array_push ($out_stack, $op);
					}
				} while (0);
				array_push ($stack, $aupper); // 4b)
				$prev_token_operator = true;
				
			} else { // 3) Everything else is an Operand
				$out_stack[] = $a;
				$prev_token_operator = false;
			}
		}
		// Finally, pop all operators from the stack and append them to the result
		while ($op=array_pop($stack)) {
			// Opening parentheses should not be found on the stack any more. That would mean a closing paren is missing!
			if ($op == "(") {
				$this->framework->warning('OTSHIPMENT_RULES_PARSE_PAREN_NOT_CLOSED', $rulepart);
			} else {
				array_push ($out_stack, $op);
			}
		}
		if (!empty($function_args)) {
				$this->framework->warning('OTSHIPMENT_RULES_PARSE_FUNCTION_NOT_CLOSED', $rulepart);
		}


		/** Now, turn the RPN into an expression tree (i.e. "evaluate" it into a tree structure), according to Knuth:
		 *   1) Initialize an empty stack
		 *   2) Read the RPN from left to right
		 *   3) If operand, push it onto the stack
		 *   4) If operator:
		 *       4a) pop two operands
		 *       4b) perform operation
		 *       4c) push result onto stack
		 *       4d) (If less than two operands => ERROR, invalid syntax)
		 *   5) At the end of the RPN, pop the result from the stack. 
		 *       5a) The stack should now be empty (otherwise, ERROR, invalid syntax)
		 */

		$stack=array(); // 1)
		foreach ($out_stack as $e) { // 2)
			if (is_array($e) && $e[0]=="FUNCTION") { // A function call (#args is saved as $e[2], so remove that number of operands from the stack)
				$function = $e[1];
				$argc = $e[2];
				$args = array();
				for ($i = $argc; $i > 0; $i--) {
					$a = array_pop($stack);
					array_unshift($args, $a);
				}
				array_unshift($args, $function);
				array_unshift($args, "FUNCTION"); 
				$stack[] = $args;
			} elseif (in_array($e, $unary_ops)) { // 4) unary operators
				// Operator => apply to the last value on the stack
				if (count($stack)<1) { // 4d)
					$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_SYNTAXERROR', $rulepart);
					array_push($stack, 0);
					continue;
				}
				$o1 = array_pop($stack);
				// Special-case chained comparisons: if e is a comparison, and operator(o1) is also a comparison, 
				// insert the arguments to the existing comparison instead of creating a new one
				$op = array ($e, $o1); // 4b)
				array_push ($stack, $op); // 4c)
			} elseif (isset($operators[$e])) { // 4) binary operators
				// Operator => apply to the last two values on the stack
				if (count($stack)<2) { // 4d)
					$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_SYNTAXERROR', $rulepart);
					array_push($stack, 0);
					continue;
				}
				$o2 = array_pop($stack); // 4a)
				$o1 = array_pop($stack);
				// Special-case chained comparisons, e.g. 1<=Amount<100:
				// if e is a comparison, and operator(o1) is also a comparison, 
				// insert the arguments to the existing comparison instead of creating a new one
				if (in_array ($e, $comparison_ops)) {
					if ($o1[0]=='COMPARISON') {
						$op = $o1;
						// Append the new comparison to the existing one
						array_push($op, $e, $o2);
					} else {
						$op = array ('COMPARISON', $o1, $e, $o2);
					}
				} else {
					$op = array ($e, $o1, $o2); // 4b)
				}
				array_push ($stack, $op); // 4c)
			} else { // 3)
				// Operand => push onto stack
				array_push ($stack, $e);
			}
			
		}
		// 5a)
		if (count($stack) != 1) {
			$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_UNKNOWN_ERROR', $rulepart);
			$this->framework->warning('Outstack: <pre>%s</pre>', print_r($out_stack,1));
			
			$stack = array (0);
		}
		$res = array_pop($stack); // 5)
		
		if ($is_assignment) { // Assignments are handled first, so conditions can be assigned to variables
			if ($res[0]=='=') {
				$this->handleAssignment ($res[1], $res[2], $rulepart);
			} else {
				// Assignment has to be top-level!
				$this->framework->warning('OTSHIPMENT_RULES_EVALUATE_ASSIGNMENT_TOPLEVEL', $rulepart);
			}
		} elseif ($is_condition) { // Comparisons are conditions
			$this->conditions[] = $res;
		} else {
			// Terms without comparisons or assignments are shipping cost expressions
			$this->shipping = $res;
			$this->ruletype = 'shipping';
			$this->includes_tax = False;
		}
// 		$this->framework->warning("<pre>Rule part '$rulepart' (type $this->ruletype) parsed into (condition=".print_r($is_condition,1).", assignment=".print_r($is_assignment,1)."): ".print_r($res,1)."</pre>");
	}


}

// No closing tag
