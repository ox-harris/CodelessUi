<?php

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * @file
 * @package		CodelessUi
 * @author		Oxford Harrison <ox_harris@yahoo.com>
 * @copyright	2016 Ox-Harris Creative
 * @license		GPL-3.0
 * @version		Release: 1.0.0
 * @link		https://www.facebook.com/CodelessUi/
 * @used-by		CodelessUi
 */	 




/** 
 * The CodelessUiDom.
 * Extends the PHP DOMDocument and provides methods that are natively available.
 *
 * @todo	Some regex operations within getElementsBySelector().
 *			Currently, they intentional throw Exception as reminders.
 */
Class CodelessUiDom extends DOMDocument
{
	/**
     * Handle to all XPath queries on the currently loaded template.
     *
     * @var object $xpath
	 *
	 * @see setTemplate()
	 * @see getElementsBySelector()
	 * 
     * This is used internally
     *
     * @access private
     */
	private $xpath;


	/**
     * A configuration property that tells CodelessUiDom how to interprete element selectors.
	 * Options are: css (the default), xpath.
     *
	 * Element selector string must be a valid css selector if this setting is 'css', or a valid xpath query if otherwise.
	 * To override this global setting and interprete certain selectors as a different type, selector strings must
	 * be enclosed in parentheses prefixed by the selector type, as if a function call to the type. css(#valid-css-selector), xpath(/valid/xpath/query).
	 *
     * @var string $default_element_selector_type
	 *
	 * @see getElementsBySelector()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $default_element_selector_type = 'css';
	
	
	
	
	/**
     * Loads a HTML string into DOMDocument
     * 
     * @param string	$source A valid HTML markup string
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws DOMException
	 *
	 * @uses DOMDocument::loadHTML().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTML($source, $options = null)
	{
		// Loads a HTML string
		$return = parent::loadHTML($source);
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}



	/**
     * Loads a HTML file into DOMDocument
     * 
     * @param string	$source A relative or absolute path to a HTML file
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws DOMException
	 *
	 * @uses DOMDocument::loadHTMLFile().
	 * @return bool TRUE on success or FALSE on error.
     */
	public function loadHTMLFile($filename, $options = null)
	{
		// Loads a HTML file
		$return = parent::loadHTMLFile($filename);
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this);
		
		// Return original return value
		return $return;
	}
	
	
	
	/**
     * Get a DOMNodelist of element in the currently loaded template using an element selector.
	 *
	 * Also used by CodelessUiNodeList::populateNodeList().
	 * Notice how css selectors are converted to respective xpath query equivalent.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see CodelessUi::$default_element_selector_type}
     * @param string	$element_path		An xpath query string specifying from which element to find requested element.
     * @param string	$evaluate_query		In case a css selector was provide, whether or not to run the xpath query it was translated into or return xpath equivalent string.
	 *
	 * @throws Exception
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	string|DOMNodelist			XPath query string or DOMNodelist depending on the $evaluate_query flag.
     */
    public function getElementsBySelector($element_selector, $element_path = '//', $evaluate_query = true)
	{
		// Clean up
		$element_selector = strtolower(trim($element_selector));
       
		$selector_build = '';
		// Returns an array of matched elements
		if (($this->default_element_selector_type == 'xpath' && substr($element_selector, 0, 4) != 'css:') || (/*$this->default_element_selector_type == 'css' && */substr($element_selector, 0, 6) == 'xpath:'))
		{
			if (substr($element_selector, 0, 6) == 'xpath:')
			{
				$query = substr($element_selector, 6);
			}
			
			$selector_build = $query;
		}
		elseif (($this->default_element_selector_type == 'css' && substr($element_selector, 0, 6) != 'xpath:') || (/*$this->default_element_selector_type == 'xpath' && */substr($element_selector, 0, 4) == 'css:'))
		{
			if (substr($element_selector, 0, 4) == 'css:')
			{
				$query = substr($element_selector, 4);
			}

			// Sanitize string
			$element_selector = str_replace(array('  ', '::'), array(' ', ':'), $element_selector);
			// Ususally found in attributes...
			$element_selector = str_replace(array(' = ', '= ', ' ='), '=', $element_selector);
			
			$selector = $element_selector;

			$match_spaces_between_whole_element_selector = '/[ ](?=[^\]\)]*?(?:\[\(|$))/i';
			$whole_element_selectors = preg_split($match_spaces_between_whole_element_selector, $selector);
						
			for($i = 0; $i < count($whole_element_selectors); $i ++)
			{
				$selector = $whole_element_selectors[$i];
				
				$psuedo_modifier = null;
				// Capture the last evaluated item in $whole_element_selectors... we'll test if it was a relationship_modifier modifying the current item.
				$relationship_modifier = isset($whole_element_selectors[$i - 1]) ? $whole_element_selectors[$i - 1] : null;
				
				// If this current item is a relationship_modifier, ignore. Next item evaluation will handle this as previous item.
				if (!in_array($selector, array('~', '+', '>')))
				{
					# 1. Work with :psuedo_modifiers
					if (strpos($selector, ':') !== false)
					{
						$selector_and_psuedo = explode(':', $selector);
						$psuedo_modifier = isset($selector_and_psuedo[1]) ? $selector_and_psuedo[1] : null;
						// $selector changes from now on
						$selector = $selector_and_psuedo[0];
						
						if (strpos($psuedo_modifier, '(') !== false)
						{
							$sub_selector = substr($psuedo_modifier, strpos($psuedo_modifier, '(') + 1, -1);
							// $psuedo_modifier changes from now on
							$psuedo_modifier = substr($psuedo_modifier, 0, strpos($psuedo_modifier, '('));
						}
						
						if (!empty($psuedo_modifier))
						{
							if ($psuedo_modifier == 'first-child')
							{
								// If the current node is same as its parent's first child
								//$selector = $this->rewrite($selector).'[is-same-node(/html, /html)]';
								throw new Exception('The compound CSS :first-child() is not yet supported');
								$selector = '../child::*[1]['.$this->rewrite($selector).']';
							}
							elseif ($psuedo_modifier == 'last-child')
							{
								throw new Exception('The compound CSS :last-child() is not yet supported');
								$selector = '../*[last()]['.$this->rewrite($selector).']';
							}
							elseif ($psuedo_modifier == 'nth-child')
							{
								throw new Exception('The compound CSS :nth-child() is not yet supported');
								$selector = '../*['.(int)$sub_selector.']['.$this->rewrite($selector).']';
							}
							
							elseif ($psuedo_modifier == 'first-of-type')
							{
								$selector = $this->rewrite($selector).'[1]';
							}
							elseif ($psuedo_modifier == 'last-of-type')
							{
								$selector = $this->rewrite($selector).'[last()]';
							}
							elseif ($psuedo_modifier == 'nth-of-type')
							{
								$selector = $this->rewrite($selector).'['.(int)$sub_selector.']';
							}
							elseif ($psuedo_modifier == 'not')
							{
								$sub_selector = $this->getElementsBySelector($sub_selector, null, false);
								
								// Sanitize...
								// If it's something like *[...] now going to be [:not(*[...])] - not what we want, let strip off the beginning *
								if (substr($sub_selector, 0, 1) == '*')
								{
									$sub_selector = 'self::*'.substr($sub_selector, 1);
								}
								
								$selector = $this->rewrite($selector).'[not('.$sub_selector.')]';
							}
							elseif ($psuedo_modifier == 'empty')
							{
								throw new Exception('The compound CSS :empty is not yet supported');
								$selector = $this->rewrite($selector).'[empty(text())]';
							}
							/*else
							{
								// $psuedo_modifier must have been some unrecognized modifier erronously provided by user, like :before or :after.
								// Whatever the case... discard $psuedo_modifier and proceed to rewrite $selector.
								$selector = $this->rewrite($selector);
							}
							CSS parsers don't tolerate such malformed selectors*/
						}
					}
					else
					{
						$selector = $this->rewrite($selector);
					}
					
					# 2. If no tagname is present, use a wildcard *. So we get *[...].
					if (substr($selector, 0, 1) == '[')
					{
						$selector = '*'.$selector;
					}
					
					# 3. Work with relationship_modifiers
					
					// Parent > Direct Child
					if ($relationship_modifier == '>')
					{
						$selector_build .= '/'.$selector;
					}
					// Element + immediate sibling
					elseif ($relationship_modifier == '+')
					{
						$selector_build .= '/following-sibling::'.$selector;
					}
					// Element ~ any sibling
					elseif ($relationship_modifier == '~')
					{
						$selector_build .= '/../'.$selector;
					}
					else
					{
						if (!empty($selector_build))
						{
							// If previous evaluated $selector (prev_selector [tested as $modifier]) was an actual selector and not a modifier, there should be double slash before this current selector (cur_selector)
							// prev_selector//cur_selector i.e all elements that are descendants of prev_selector
							$selector_build .= '//'.$selector;
						}
						else
						{
							// If it's not subquery - e.g queries enclosed in ::not()...
							// Return as is!
							$selector_build .= $selector;
						}
					}
				}
			}
			
			if (!$evaluate_query)
			{
				return $selector_build;
			}
		}
		
		echo '>> '.$element_path.$selector_build;
		
		$selector_build = $selector_build."[not(@data-codelessui-no_parse)]";
		
		$elements = $this->xpath->query($element_path.$selector_build);
		
		if ($elements)
		{
			echo ' -> ('.$elements->length.")\r\n";
			return $elements;
		}
		else
		{
			throw new Exception('Malformed XPath query: '.$element_path.$selector_build.' !');
		}
    }
	
	
		
	/*
	 *-----------------------
	 * PRIVATE WORKING METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Rewrite a unit of css selector into a valid XPath query.
	 *
     * @param string	$css_selector		A css selector to be translated into xpath.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	string			XPath query string.
     */
	private function rewrite($css_selector)
	{
		// Simple Attribute First Before introducing complex brackets
		if (strpos($css_selector, '[') !== false && strpos($css_selector, ']') !== false && strpos($css_selector, '=') === false)
		{
			$css_selector = str_replace('[', '[@', $css_selector);
		}
		// ID selector
		if (strpos($css_selector, '#') !== false)
		{
			// Attr Is Word
			$css = '/\#(\w+)/i';
			$xpath = '[@id="$1"]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Class
		if (strpos($css_selector, '.') !== false)
		{
			// Attr Contains Word
			$css = '/\.(\w+)/i';
			$xpath = '[contains(concat(" ", @class, " "), " $1 ")]';
			$css_selector = preg_replace($css, $xpath, $css_selector);
		}
		// Complex Attribute
		if (strpos($css_selector, '[') !== false && strpos($css_selector, ']') !== false && strpos($css_selector, '=') !== false)
		{
			// Attr Starts With
			if (strpos($css_selector, '^=') !== false)
			{
				$css = '/\[(.*)\^=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(" ", @$1), " $2")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Ends With
			if (strpos($css_selector, '$=') !== false)
			{
				$css = '/\[(.*)\$=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(@$1, " "), "$2 ")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Contains String
			if (strpos($css_selector, '*=') !== false)
			{
				$css = '/\[(.*)\*=(.*)\]/i';
				$xpath = '[contains(@$1, $2)]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Has Dash-delimited Word
			if (strpos($css_selector, '|=') !== false)
			{
				$css = '/\[(.*)\|=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat("-", @$1, "-"), "-$2-")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Contains Word
			if (strpos($css_selector, '~=') !== false)
			{
				$css = '/\[(.*)\~=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[contains(concat(" ", @$1, " "), " $2 ")]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
			// Attr Is Word
			if (strpos($css_selector, '=') !== false)
			{
				$css = '/\[@?(.*)=[\'"]?([^\'"]+)[\'"]?\]/i';
				$xpath = '[@$1="$2"]';
				$css_selector = preg_replace($css, $xpath, $css_selector);
			}
		}
		
		return $css_selector;
	}
	
	
	/**
     * Duplicate an element in the main document. Mainly used to complete calls_list
     *
     * @param DOMNode 	$node 						A DOMNode element.
	 * @param string 	$insert_duplicate_as 		Specifies how to place the duplicated element relative to its source.
	 * @param bool 		$insert_before 				The numeric key location of an item in list before which to add this.
     * 
	 * @return void
     */
	public function duplicateNode($node, $insert_duplicate_as = 'immediate_sibling', $insert_before = false)
	{
		$parent = $node->parentNode;
		if ($insert_duplicate_as == 'sub_child')
		{
			$parent = $node;
			if ($node->ownerDocument->formatOutput)
			{
				if (is_object($node->firstChild))
				{
					$value = !empty($node->firstChild->nodeValue) && empty(trim($node->firstChild->nodeValue)) ? $node->firstChild->nodeValue : "\r\n";
					$beforeNode = $this->createTextNode($value);
				}
				if (is_object($node->lastChild))
				{
					$afterNode = $this->createTextNode((!empty($node->lastChild->nodeValue) && empty(trim($node->lastChild->nodeValue)) && $node->lastChild->nodeValue !== $value ? $node->lastChild->nodeValue : "\t"));
				}
			}
		}
		else
		{
			if ($insert_duplicate_as == 'immediate_sibling' && !$insert_before)
			{
				// Insert an anchor immediately before the first element to be duplicated
				// We will be placing things right before this anchor. Which automatically is after the first element duplicated
				// Placing an element befored this anchor will push the anchor forward... and so on
				if (!isset($insert_before))
				{
					$insert_before = $this->createTextNode("\r\n");
					$parent->insertBefore($insert_before, $node->nextSibling);
				}
			}

			if ($node->ownerDocument->formatOutput)
			{
				if (is_object($node->previousSibling))
				{
					$value = !empty($node->previousSibling->nodeValue) && empty(trim($node->previousSibling->nodeValue)) ? $node->previousSibling->nodeValue : "\r\n";
					$beforeNode = $this->createTextNode($value);
				}
				if (is_object($node->nextSibling))
				{
					/*if ($node->nextSibling->nodeName == "#comment" || ($node->nextSibling->nodeName == "#text" && empty(trim($node->nextSibling->nodeValue))))
					{
						$afterNode = $node->nextSibling->cloneNode();
					}*/
					$value = !empty($node->nextSibling->nodeValue) && empty(trim($node->nextSibling->nodeValue)) ? $node->nextSibling->nodeValue : "\r\n";
					$afterNode = $this->createTextNode((!empty($node->nextSibling->nodeValue) && empty(trim($node->nextSibling->nodeValue)) && $node->nextSibling->nodeValue !== $value ? $node->nextSibling->nodeValue : "\t"));
				}
			}
		}
		
		// Copy leading & lagging space or newline
		if (!empty($beforeNode))
		{
			if ($insert_before)
			{
				$parent->insertBefore($beforeNode, $insert_before);
			}
			else
			{
				$parent->appendChild($beforeNode);
			}
		}
		
		// Copy the main node
		$node_copy = $node->cloneNode(true);
		if ($insert_before)
		{
			$parent->insertBefore($node_copy, $insert_before);
		}
		else
		{
			$parent->appendChild($node_copy);
		}
		
		// Also copy trailing space or newline
		if (!empty($afterNode))
		{
			if ($insert_before)
			{
				$parent->insertBefore($afterNode, $insert_before);
			}
			else
			{
				$parent->appendChild($afterNode);
			}
		}
		
		return $node_copy;
	}
	
	
	
	/**
     * Fill CodelessUiDom::$node_list with DOMNodeList objects. This is usually called by CodelessUi.
     *
	 * @param array 	$element_selectors 	A list of element selectors provided by CodelessUi.
     * @param DOMNode 	$source_element 	A DOMNode element from which to source elements for the population.
     * @param bool	 	$repeat_fn		 	The repeat function to use.
     * @param bool	 	$is_switch_to_self	Sets whether to populate data on element itself or child elements.
     * 
	 * @see				CodelessUi::setElementData().
	 *
	 * @return void
     */
	public function createNodeListFromSelectors(array $element_selectors, $source_element = null, $repeat_fn = null, $switch_population_target = false)
	{
		$source_element = !empty($source_element) ? $source_element : $this;
		return new CodelessUiNodeList($element_selectors, $source_element, $repeat_fn, $switch_population_target);
	}
	
	
	
	
	public function saveHTML($element_selector = null)
	{
		if (!empty($element_selector))
		{
			$markup_strings = array();
			
			$elements = $this->getElementsBySelector($element_selector);
			if ($elements)
			{
				foreach($elements as $element)
				{
					$markup_strings[] = parent::saveHTML($element);
				}
			}
			
			return implode(($this->formatOutput ? "\r\n" : ""), $markup_strings);
		}
		
		return parent::saveHTML();
	}
}
