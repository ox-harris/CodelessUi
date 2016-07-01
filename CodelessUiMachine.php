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
 * @uses		CodelessUiNodelist	to retrieve elements of a Nodelist or auto-generated elements in a particular order.
 * @see			CodelessUiNodelist
 */	 



/**
 * Include a brother file
 */
/* include "CodelessUiNodeList.php"; */




/** 
 * The CodelessUi Machine.
 * Builds up data stack with every data associated with an element,
 * accepts a HTML template and renders data into it.
 * Built upon the PHP DOMDocument.
 *
 * @todo	Some regex operations within getElementsBySelector().
 *			Currently, they intentional throw Exception as reminders.
 */
Class CodelessUiMachine
{
	/**
     * Holds an array of element selectors with their respective handlers.
     *
     * @var array $value_modifiers
	 *
	 * @see setValueModifier().
	 *
	 * This is used internally. But readonly via magic __get()
	 *
	 * @access private
     */
	private $value_modifiers;
	
	/**
     * The xpath nodepath of the current element being parsed by render().
	 * CodelessUiNodelist will be accessing this too later
	 * when trying to retrieve this element's descendants.
     *
     * @var string $current_node_path
	 *
	 * @see setElementData().
	 * @see CodelessUiNodeList::populateNodeList().
	 *
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $current_node_path = null;
	
	/**
     * The main tree of all assigned data. Usually a multidimensional array
	 * of element identifiers and their respective data.
     *
     * @var array $data_stack
	 *
	 * @see assignData().
	 * @see obtainData().
	 * @see render().
	 *
     * This is used internally. But readonly via obtainData() or magic __get()
     *
     * @access private
     */
	private $data_stack = array();
	
	/**
     * A linear array
	 * of element identifiers and their respective paths to data to be included.
	 *
     * @var array $includes_stack
	 *
	 * @see setInclude().
	 * @see assignData().
	 * @see setAllElementIncludes().
	 *
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $includes_stack = array();
			
	/**
     * The DOMDocument of the currently loaded template.
     *
     * @var object $DOMDocument
	 *
	 * @see setTemplate()
	 * @see __destruct()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
    private $DOMDocument;
	
	/**
     * Handle to all XPath queries on the currently loaded template.
     *
     * @var object $xpath
	 *
	 * @see setTemplate()
	 * @see getElementsBySelector()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $xpath;
	
	/**
     * A linear array of repeat function names to be followed 
	 * when repeating on the x-axis - that is, going deeper in a multidimensional array.
	 * It is especially used by CodelessUiNodeList.
     *
     * @var array $repeat_fn_x: Sample values: "simple"|"mirror"|"once"|"inner_padded"|"justify"
	 *
	 * @see setRepeatFunctions()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	public $repeat_fn_x = array("simple");
	
	/**
     * A linear array of repeat function names to be followed 
	 * when repeating on the y-axis - that is, when looping over items in an array.
	 * It is especially used by CodelessUiNodeList.
     *
     * @var array $repeat_fn_y: Sample values: "simple"|"mirror"|"once"|"inner_padded"|"justify"
	 *
	 * @see setRepeatFunctions()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	public $repeat_fn_y = array("simple");
	
	/**
     * The name of the repeat function currently in use, whether repeat_fn_x and repeat_fn_y.
     *
     * @var string $current_repeat_fn
	 *
	 * @see setRepeatFunctions()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	public $current_repeat_fn;
	
	/**
     * This records how many levels deep the parser has gone in a multidimensional array.
     *
     * @var int $repeat_depth_count
	 *
	 * @see setElementData()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $repeat_depth_count = 0;
	
	/**
     * A status flag set by the parser indicating whether or not it is
	 * re-parsing the whole template over again.
	 * Reparse usually happens when CodelessUiMachine::$parse_inserted_data is set to true.
	 * On reparse, CodelessUi skips already parsed elements (because it is able to recognize them), but looks
	 * out for new elements that were inserted into the template on the previous
	 * render() operation, which are now part of the whole DOMDocument, and which have data assigned to them on CodelessUiMachine::$data_stack.
     *
     * @var bool $on_reparse
	 *
	 * @see CodelessUiMachine::$parse_inserted_data
	 * @see render()
	 *
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $on_reparse = false;
	
	/**
     * A status flag set by the parser indicating whether or not render() has be called on this document.
	 * getRendered() will needs this flag.
     *
     * @var bool $is_rendered
	 *
	 * @see getRendered()
	 * @see render()
	 *
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $is_rendered = false;
	
	/**
     * A configuration property set to tell CodelessUi what to do to an element when
	 * the element's assigned data is empty.
	 * Recognized values are: clear, set_flag, clear_and_set_flag, no_render, do_nothing (the default).
     *
     * @var string $on_content_empty
	 *
	 * @see render()
	 * 
     * This is used internally. But readonly via magic __get()
     *
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $on_content_empty = "do_nothing";


	/**
     * A configuration property that tells CodelessUi how to interprete element selectors.
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
     * A configuration property that tells CodelessUi whether to reparse the whole template
	 * after having inserted data on the first parse.
     *
	 * Reparsing finds only newly inserted elements on the template which have data assigned to them for whenever they are available
	 * on the document.
	 *
     * @var bool $parse_inserted_data
	 *
	 * @see CodelessUiMachine::$on_reparse
	 * @see render()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $parse_inserted_data = false;


	/**
     * A configuration property that tells CodelessUi whether to format the html output of the rendered template.
	 * This will attempt proper indentation and balancing of html tags.
     *
     * @var bool $allow_html_formatting
	 *
	 * @see CodelessUiNodeList::duplicateNode()
	 * @see setTemplate()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $allow_html_formatting = true;

	/**
     * A configuration property that tells CodelessUi whether to automatically echo output with a call to render().
	 * Note that this setting does not affect getRendered(), which always returns and never auto-echoes anything.
     *
     * @var bool $auto_print_output_on_render
	 *
	 * @see render()
	 * @see getRendered()
	 * 
	 * This property will not change until a major release.
	 *
	 * @api
	 *
     * @access public
     */
	public $auto_print_output_on_render = true;

	/**
     * A configuration property that tells CodelessUi whether to include on an element
	 * the BraveCode's UAC algarithm applied on the data inserted.
     *
     * @var bool $show_brvcd_uac_field_access
	 *
	 * @see render()
	 * 
	 * This property may change in a major release. Use is discoraged.
	 *
	 * @api
	 *
     * @access public
     */
	public $show_brvcd_uac_field_access = false;

	/**
     * Holds BraveCode's UAC property list.
	 * CodelessUi extracts these properties from an assigned data using this list.
     *
     * @var array $brvcd_UAC_props
	 *
	 * @see render()
	 * 
     * This is used internally. But readonly via magic __get()
     *
     * @access private
     */
	private $brvcd_UAC_props = array(
		"BRVCD_UAC_TABLE_ACCESS",
		"BRVCD_UAC_FIELDS_LIST",
		"BRVCD_UAC_SUPPORTS_ROW_LEVEL_UAC_SETTING",
		"BRVCD_UAC_ACCESS_PERMISSIONS_IN_USE"
	);
	




	/*
	 *--------------
	 * MAGIC METHODS
	 *--------------
	*/
	
	
	
	
	
	/**
     * Constructor for CodelessUiMachine.
     *
     * Checks version compatibilty.
     * 
	 * @throws Exception
	 *
	 * @return void
     */
	public function __construct()
	{
		//Check the application's minimum supported PHP version. (5.2.7)
		if (version_compare(PHP_VERSION, BrvcdSetup::MIN_PHP_VERSION, '<'))
		{
			die('You need to use PHP 5.2.7 or higher to run this version of CodelessUi!');
		}
    }

	/**
     * Destructor for CodelessUiMachine.
     *
     * Frees up all memory used. May not be neccessary.
     * 
	 * @return void
     */
    public function __destruct()
	{
        // Frees memory associated with the DOMDocument
		unset($this->DOMDocument);
    }
	
	/**
     * Magic get for CodelessUiMachine.
     *
     * Retrieves a private of protected property. This makes them readonly.
     * 
	 * @throws Exception
     * 
	 * @return void
     */
    public function __get($param_name)
	{
		if (isset($this->{$param_name}))
		{
			return $this->{$param_name};
		}
		
		throw new Exception($param_name.' must be an undeclared property!');
    }
	
	
	
	/*
	 *-----------------------
	 * PUBLIC SET METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Give CodelessUi a template to use for rendering.
     *
     * Ok to have used the assignData() method before calling this.
	 * But be sure that setTemplate() has been called before the render() method;
	 * this makes the template available for render()
     * 
     * @param string	$template A valid HTML markup string, or a relative or absolute path to a HTML file
	 *
	 * @see CodelessUiMachine::assignData()
	 * @see CodelessUiMachine::render()
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @throws Exception
	 *
	 * @uses DOMDocument to load a HTML template.
	 * @return void
     */
	public function setTemplate($template)
	{
		// Loads the DOMDocument 
		$doc = new DOMDocument(); 
		$doc->formatOutput = $this->allow_html_formatting;
		
		// Suppress errors 
		libxml_use_internal_errors(true);
		
		// Load template
		if (is_file($template))
		{
			// $template is a file
			$doc->loadHTMLFile($template);
		}
		elseif (!empty($template))
		{
			// $template is a markup string
			$doc->loadHTML($template);
		}
		else
		{
			throw new Exception("No HTML data provided!"); 
		}
	
		$this->DOMDocument = $doc; 
		$this->DOMDocument->normalizeDocument();
		
		// xpath handle on this document
		$this->xpath = new DOMXpath($this->DOMDocument);
	}
	


	/**
     * Assign data to an element using an element selector as key.
     *
     * Use this to build up the total data stack for elements in the template.
	 * Can be called many times for an element selector to either add to already assigned data, or replace it.
     * 
     * @param string		$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
     * @param string|array	$data				Simple string or compound array of data for an element.
     * @param bool			$replace			Whether to replace data already assigned or to add to it.
     * @param bool			$recurse			Whether to replace data recursely when $replace is set to true.
     * @param bool			$is_include_path	Whether the string $data is normal data or a path to a file to include on an element.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self							For method chaining
     */
	public function assignData($element_selector, $data, $replace = true, $recurse = true, $is_include_path = false)
	{
		$data_stack = $is_include_path ? 'includes_stack' : 'data_stack';
		
		if ($replace)
		{
			if ($recurse)
			{
				$this->{$data_stack} = array_replace_recursive($this->{$data_stack}, array($element_selector => $data));
			}
			else
			{
				$this->{$data_stack} = array_replace($this->{$data_stack}, array($element_selector => $data));
			}
		}
		else
		{
			if ($recurse)
			{
				$this->{$data_stack} = array_merge_recursive($this->{$data_stack}, array($element_selector => $data));
			}
			else
			{
				$this->{$data_stack} = array_merge($this->{$data_stack}, array($element_selector => $data));
			}
		}
		
		return $this;
	}
	
	


	/**
     * Assign whole data to CodelessUi.
     *
     * This doesn't build up the data on the stack but sets a pre-built data stack to it. It replaces any value already set here.
	 * This could be very convenient if your application already has a data builder like CodelessUi's. You simply obtain that data and set it here.
     * 
     * @param string|array	$data_stack			Simple strings or compound array of data for all elements.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self							For method chaining
     */
	public function assignDataStack($data_stack)
	{
		$this->data_stack = $data_stack;
		
		return $this;
	}



	/*
	 *-----------------------
	 * ADVANCED PUBLIC SET METHODS
	 *-----------------------
	*/
	
	
	
	/**
     * Assign an include path to an element using an element selector as key.
     *
     * Use this to build up the total include stack for elements in the template.
	 * Can be called many times for an element selector to either add to already assigned path, or replace it.
     * 
     * @param string		$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
     * @param string|array	$data				Simple string or compound array of data for an element.
     * @param bool			$replace			Whether to replace paths already assigned or to add to it.
     * @param bool			$recurse			Whether to replace paths recursely when $replace is set to true.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @uses	CodelessUiMachine::assignData()
	 * @return	self							For method chaining
     */
	public function setInclude($element_selector, $data, $replace = true, $recurse = true)
	{
		return $this->assignData($element_selector, $data, $replace, $recurse, true);
	}
	
	
	
	/**
     * Assign an external handler to an element using an element selector as key.
     *
	 * Can be called many times for an element selector to build up an array of handlers.
     * 
     * @param string				$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
     * @param string|array|closure	$handler			A callable function.
	 *													String: function name (must be globally visible).
	 *													Array: class method in the form of array(class_name_or_object, method_name).
	 *													Closure: an anonymous function or inline function.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self									For method chaining
     */
	public function setValueModifier($element_selector, $handler)
	{
		$this->value_modifiers[$element_selector][] = $handler;
		
		return $this;
	}
	


	/**
     * Assign repeat function(s) globally.
	 *
	 * Override this setting on specific elements by
	 * specifying repeat functions inside the [@params] key element's compound data.
     *
	 * Repeat functions are used to set the particular algorithm or pattern to follow 
	 * when elements have to be repeated in order to render any extra items in data array when data items outnumbers element count.
	 *
     * @param string	$repeat_fn		An algarithm name from CodelessUi's predefined types.
     * @param string	$repeat_fn_x	An algarithm specifically for repeating on the x-axis. If not supplied, $repeat_fn is used.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	self					For method chaining
     */
	public function setRepeatFunctions($repeat_fn, $repeat_fn_x = null)
	{
		// If only $repeat_fn is supplied, use it as both repeat_fn_x & repeat_fn_y
		if (empty($repeat_fn_x))
		{
			$this->repeat_fn_x = $repeat_fn;
			$this->repeat_fn_y = $repeat_fn;
		}
		else
		{
			$this->repeat_fn_x = $repeat_fn_x;
			$this->repeat_fn_y = $repeat_fn;
		}
		
		return $this;
    }
	
	
	
	/*
	 * PUBLIC GET METHODS
	 */
	 
	
	 
	/**
     * Obtain data assigned to an element using an element selector as key.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
     * @param string	$var				A reference to a variable into which to set the obtained data. Note $this is returned instead of the obtained value.
     * @param string	$is_include_path	Whether to obtain from CodelessUiMachine::$data_stack or CodelessUiMachine::$include_stack.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	string|array|self			String or array depending on the data type obtained. But $this for method chaining, when the $var parameter is specified.
     */
	public function obtainData($element_selector = null, & $var = null, $is_include_path = false)
	{
		$data_stack = $is_include_path ? 'includes_stack' : 'data_stack';
		
		if (empty($element_selector))
		{
			if (empty($var))
			{
				return $this->{$data_stack};
			}
			
			$var = $this->{$data_stack};
			
			return $this;
		}
		elseif (array_key_exists($element_selector, $this->{$data_stack}))
		{
			if (empty($var))
			{
				return $this->{$data_stack}[$element_selector];
			}
			
			$var = $this->{$data_stack}[$element_selector];
			
			return $this;
		}
	}
	
	

	/**
     * Obtain include paths assigned to an element using an element selector as key.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
     * @param var		$var				A reference to a variable into which to set the obtained data. Note $this is returned instead of the obtained value.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
	 *
	 * @return	array|self					Array of include paths. But $this for method chaining, when the $var parameter is specified.
     */
	public function obtainInclude($element_selector = null, & $var = null)
	{
		return $this->obtainData($element_selector, $var, true);
	}
	
	
	
	/**
     * Get a DOMNodelist of element in the currently loaded template using an element selector.
	 *
	 * Also used by CodelessUiNodeList::populateNodeList().
	 * Notice how css selectors are converted to respective xpath query equivalent.
     *
     * @param string	$element_selector	A valid css selector or xpath query. {@see CodelessUiMachine::$default_element_selector_type}
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
    public function getElementsBySelector($element_selector, $element_path = null, $evaluate_query = true)
	{
		if (!empty($element_path))
		{
			if (substr($element_path, -1) !== '/' && substr($element_selector, 0, 1) !== '/')
			{
				//$element_path = $element_path.'/';
			}
		}
		elseif (substr($element_selector, 0, 1) !== '/')
		{
			//$element_path = '//';
		}
		
		// Clean up
		$element_selector = strtolower(trim($element_selector));
       
	   // Returns an array of matched elements
		if (($this->default_element_selector_type == 'xpath' && substr($element_selector, 0, 4) != 'css(') || (/*$this->default_element_selector_type == 'css' && */substr($element_selector, 0, 6) == 'xpath('))
		{
			if (substr($element_selector, 0, 6) == 'xpath(')
			{
				$query = substr($element_selector, 6, strrpos($element_selector, ')'));
			}
			
			$elements = $this->xpath->query($element_path.$query);
		}
		elseif (($this->default_element_selector_type == 'css' && substr($element_selector, 0, 6) != 'xpath') || (/*$this->default_element_selector_type == 'xpath' && */substr($element_selector, 0, 4) == 'css('))
		{
			if (substr($element_selector, 0, 4) == 'css(')
			{
				$query = substr($element_selector, 4, strrpos($element_selector, ')'));
			}

			// Sanitize string
			$selector = str_replace(array('  ', '::'), array(' ', ':'), $element_selector);

			$selector_build = '';

			$match_spaces_between_whole_element_selector = '/[ ](?=[^\]\)]*?(?:\[\(|$))/i';
			$whole_element_selectors = preg_split($match_spaces_between_whole_element_selector, $selector);
			
			for($i = 0; $i < count($whole_element_selectors); $i ++)
			{
				$selector = $whole_element_selectors[$i];
				
				$psuedo_modifier = null;
				$relationship_modifier = isset($whole_element_selectors[$i - 1]) ? $whole_element_selectors[$i - 1] : null;
				if (!in_array($selector, array('~', '+', '>')))
				{
					# 1. 
					if (substr($selector, 0, 1) == '#' || substr($selector, 0, 1) == '.' || substr($selector, 0, 1) == '[')
					{
						$selector = '*'.$selector;
					}
					
					# 2. Work with :psuedo_modifiers
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
								throw new Exception('The compound CSS :first-child() is not yet supported');
								$selector = '../child::*[1]['.$this->rewrite($selector).']';
							}
							if ($psuedo_modifier == 'last-child')
							{
								throw new Exception('The compound CSS :last-child() is not yet supported');
								$selector = '../*[last()]['.$this->rewrite($selector).']';
							}
							if ($psuedo_modifier == 'nth-child')
							{
								throw new Exception('The compound CSS :nth-child() is not yet supported');
								$selector = '../*['.(int)$sub_selector.']['.$this->rewrite($selector).']';
							}
							
							if ($psuedo_modifier == 'first-of-type')
							{
								$selector = $this->rewrite($selector).'[1]';
							}
							if ($psuedo_modifier == 'last-of-type')
							{
								$selector = $this->rewrite($selector).'[last()]';
							}
							if ($psuedo_modifier == 'nth-of-type')
							{
								$selector = $this->rewrite($selector).'['.(int)$sub_selector.']';
							}
							if ($psuedo_modifier == 'not')
							{
								$selector = $this->rewrite($selector).'[not('.$this->getElementsBySelector($sub_selector, null, false).')]';
							}
						}
					}
					else
					{
						$selector = $this->rewrite($selector);
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
						// If previous evaluated $selector (prev_selector [tested as $modifier]) was an actual selector and not a modifier, there should be double slash before this current selector (cur_selector)
						// prev_selector//cur_selector i.e all elements that are descendants of prev_selector
						$selector_build .= '//'.$selector;
					}
				}
			}
			
			if (!$evaluate_query)
			{
				return $selector_build;
			}
			
			$query = $selector_build."[not(@data-codelessui-no_parse)]";
			
			$elements = $this->xpath->query($element_path.$query);
		}
		
		if ($elements)
		{
			return $elements;
		}
		else
		{
			throw new Exception('Malformed XPath query: '.$element_path.$query.' !');
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
     * Loop through CodelessUiMachine::$includes_stack and include all data using their associated element selector.
	 *
	 * @throws Exception
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	void
     */
	private function setAllElementIncludes()
	{
		foreach((array)$this->includes_stack as $element_selector => $indlude_path)
		{
			$elements = $this->getElementsBySelector($element_selector);
			
			if ($elements)
			{
				foreach($elements as $element)
				{
					if ($element)
					{
						if (!is_readable($indlude_path))
						{
							throw new Exception("The include path: ".$indlude_path." is not valid!");
						}
						
						$include_data = file_get_contents($indlude_path);
						$documentFragment = $this->DOMDocument->createDocumentFragment();
						$documentFragment->appendXML($include_data);
						$element->appendChild($documentFragment);
					}
				}
			}
		}
	}
	
	
	
	/**
     * Insert data into an element - data encountered while looping within render().
	 *
     * @param DOMNode	$element			An instance of DOMNode representing the element.
     * @param string	$data_value			Data to be inserted.
     * @param string	$fn					A flag to either replace element's already existing data, to prepend or append it.
     * @param array		$properties			List of insertion settings compiled for this element.
     * @param bool		$is_switch_to_self	Parameter to be transfered to CodelessUiNodeList::populateNodeList().
	 *
	 * @see CodelessUiNodeList::populateNodeList()
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|DOMNode				Null on error. The originally provided DOMNode on success.
     */
	private function setElementData($element, $data_value, $fn = 'replace', array $properties = null, $is_switch_to_self = false)
	{
		if (empty($element) || !$element instanceof DOMNode)
		{
			return;
		}
		
		# 1. If $data_value is string, insert into matched elements
		# 2. Organize a loop if $data_value is an array

		if (!is_array($data_value))
		{
			// This is crucial!
			// This element may have already been parsed. Its content - the inserted data - may be also be parseable as well.
			// If this is so, a loop call to reparse the document may already have captured this parseable data in a nodeList.
			// Insert-replacing this element's parseable data will send the data out of the current document and make it unavailable.
			// To avoid this problem, all already-parsed elements have the 'data-codelessui-no_parse' attribute set. Detect this attribute and return!
			if ($element->hasAttribute("data-codelessui-no_parse"))
			{
				$element->removeAttribute("data-codelessui-no_parse");
				
				return;
			}
	
			if (!empty(trim($data_value)))
			// Insert the real data
			{
				// If $data_value is plain text - Check if $data_value contains HTML tags
				if (!preg_match("/<[^<].*>/", $data_value))
				{
					// Create TextNode and append 
					$documentFragment = $this->DOMDocument->createTextNode($data_value);
				}
				else
				{
					$documentFragment = $this->DOMDocument->createDocumentFragment();
					$documentFragment->appendXML($data_value);
				}
				
				// appendChild() throws error if $documentFragment is empty
				if (!empty($documentFragment))
				{
					if ($fn == "prepend" && $element->hasChildNodes())
					{
						$element->insertBefore($documentFragment, $element->firstChild);
					}
					else
					{
						if ($fn == "replace")
						{
							$element->nodeValue = "";
						}
						
						$element->appendChild($documentFragment);
					}
				}
			}
			else
			// Content is empty
			{
				$prop_on_content_empty = $this->getElementProp('on_content_empty', $properties, $element);

				if ($prop_on_content_empty == "clear" || $prop_on_content_empty == "clear_and_set_flag")
				{
					$element->nodeValue = "";
				}
				
				if ($prop_on_content_empty == "set_flag" || $prop_on_content_empty == "clear_and_set_flag")
				{
					$element->setAttribute("data-codelessui-content_empty", "true");
				}
				
				if ($prop_on_content_empty == "no_render")
				{
					$parentNode = $element->parentNode;
					$parentNode->removeChild($element);
					// This element hanle now changes to parent... This is what will be returned
					$element = $parentNode;
				}
			}
			
			// This whole documen may be parsed again to parse inserted data
			// When this happens, the currently parsed elements should not be parsed again... so lets set the flag
			if ($this->parse_inserted_data && !isset($parentNode)/*Elements can sometimes be deleted with the on_content_empty command*/)
			{
				$element->setAttribute("data-codelessui-no_parse", "true");
			}
			
			return $element;
		}
		elseif (is_array($data_value))
		{
			$this->current_node_path = $element->getNodePath();
			
			$this->repeat_depth_count ++;

			# 1. Prepare the $element for smart usage
			
			$current_repeat_fn = $this->current_repeat_fn;
			
			// Determine if this is a repeat on the X or Y axis - for multidimensional array
			$repeating_axis = $this->repeat_depth_count % 2 == 0 ? "x" : "y";
			$repeat_fn_ = $this->getElementProp('repeat_fn_'.$repeating_axis, $properties);
			$this->current_repeat_fn = $repeat_fn_;

			// Use the CodelessUiNodeList to create and/or rearrange element's children in a defined pattern
			$CodelessUiNodeList = new CodelessUiNodeList($this);
			$CodelessUiNodeList->populateNodeList(
				$element,
				array_keys($data_value),
				$is_switch_to_self
			);
			
			# 2. Render
			$this->render($data_value, $CodelessUiNodeList/*, $BRVCD_UAC_FIELDS_LIST_FRAGMENTED*/);
			
			// Return the current current_repeat_fn after the whole operation in the just concluded loop
			$this->current_repeat_fn = $current_repeat_fn;

			$this->repeat_depth_count --;
		}


	}
	
	
	
	/**
     * Insert data into an element's attribute node - data encountered while looping within render().
	 *
     * @param DOMNode	$element			An instance of DOMNode representing the element.
     * @param array		$attr_name			Name of an element attribute.
     * @param string	$data_value			Data to be inserted.
     * @param string	$fn					A flag to either replace element's already existing data, to prepend or append it.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|DOMNode				Null on error. The originally provided DOMNode on success.
     */
	private function setElementAttr($element, $attr_name, $attr_value, $fn = 'replace')
	{
		if (empty($element) || !$element instanceof DOMNode)
		{
			return;
		}
		
		if ($fn == 'append')
		{
			$current_attr_value = $element->getAttribute($attr_name);
			$attr_value = !empty($current_attr_value) ? $current_attr_value. ' ' .$attr_value : $attr_value;
		}
		elseif ($fn == 'prepend')
		{
			$current_attr_value = $element->getAttribute($attr_name);
			$attr_value = !empty($current_attr_value) ? $attr_value. ' ' .$current_attr_value : $attr_value;
		}

		$element->setAttribute($attr_name, $attr_value);
		
		return $element;
	}
	
	
	
	/**
     * Locate external data from the specified import location - location path encountered while looping within render().
	 * 
	 * This external could live in and retrieved from any of the places below:
	 * Local or remote machine with a URL. The URL string must be enclosed within url(...) parentheses.
	 * Local or remote filesystem with a relative or absolute path-to-file. Path-to-file string must be enclosed within file(...) parentheses.
	 * Inside the currently loaded DOMDocument with an xpath query string. A valid xpath query string must be enclosed within xpath(...) parentheses.
	 * Inside the currently loaded DOMDocument with a css stye of element id selector. A unique id attribute of the element which must be prefixed with #.
	 *
     * @param string		$import_location		Path to the importable data.
     * @param string		$current_node_path		XPath path of the current element into which to insert the imported data.
	 *
	 * @throws Exception
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	null|string							Null on error. The retrieved markup string of the element on success.
     */
	private function getReferencedImport($import_location, $current_node_path)
	{
		if (!empty($import_location))
		{
			$import = strtolower(trim($import_location));
			if ((substr($import, 0, 4) == 'url(' || substr($import, 0, 5) == 'file(') && substr($import, -1) == ')')
			{
				$is_url_or_file = 'url';
				if (substr($import, 0, 4) == 'url(')
				{
					$url_or_file = substr($import, 4, -1);
				}
				elseif (substr($import, 0, 5) == 'file(')
				{
					$is_url_or_file = 'file';
					$url_or_file = substr($import, 5, -1);
				};
			}
			elseif (substr($import, 0, 6) == 'xpath(')
			{
				$xpath_query = $import;
			}
			
			if (strpos($import, '#') !== false)
			{
				$url_fragment_id = substr($import, strpos($import, '#') + 1);
				// $url changes from now on
				$url_or_file = substr($import, 0, strpos($import, '#'));
			}
			
			if (!empty($url_or_file))
			{
				if (!is_readable($url_or_file))
				{
					throw new Exception("The url: ".$import_location." is not valid!");
				}

				$DOMDocument = new DOMDocument(); 
				$DOMDocument->formatOutput = true;
				//$url_content = file_get_contents($url_or_file);
				//$DOMDocument->loadHTML($url_content);
				$DOMDocument->loadHTMLFile($url_or_file);
				
				// Get the fragment_id as string
				$content = $DOMDocument->saveHTML(!empty($url_fragment_id) ? $DOMDocument->getElementById($url_fragment_id) : null);
			}
			elseif (!empty($url_fragment_id) || !empty($xpath_query))
			{
				// Find this current document of an element with the $url_fragment_id or $xpath_query
				$element_selector = !empty($url_fragment_id) ? '#'.$url_fragment_id : $xpath_query;
				$fragment_node = $this->getElementsBySelector($element_selector, $current_node_path)->item(0);

				$content = $this->DOMDocument->saveHTML($fragment_node);
			}
			
			if (!empty($content))
			{
				return $content;
			}
		}
	}
	
	
	
	/**
     * Get list of properties associated with an element.
	 * 
	 * Properties could be set on an element in its HTML markup, using data-codelessui-* attributes.
	 * They could be included on the [@param] key of some compound data assigned to an element.
	 * 
	 * Merge all properties on both areas. Give HTML attribute properties first priority.
	 *
     * @param string	$key			A string representing the property name.
     * @param array		$properties		Properties already encountered within the [@param] key of the element's compound data.
     * @param DOMNode	$element		The element.
	 *
     * This is used internally
     *
     * @access private
	 *
	 * @return	bool|string				false on error. The retrieved property value.
     */
	private function getElementProp($key, array $properties, $element = null)
	{
		$prop = null;
		if (is_object($element) && $element->hasAttribute('data-codelessui-'.$key))
		{
			$prop = $element->getAttribute('data-codelessui-'.$key);
		}
		else
		{
			$prop = array_key_exists($key, (array)$properties) ? $properties[$key] : (isset($this->{$key}) ? $this->{$key} : null);
		}
		
		return !empty($prop) ? $prop : false;
	}




	
	/*
	 * PUBLIC RENDERERS
	 */
	 
	 
	
	
	/**
     * Loop through the already built CodelessUiMachine::$data_stack
	 * and render each data into their respective element.
	 * 
	 * Merge all properties on both areas. Give HTML attribute properties first priority.
	 *
     * @param array					$sub_data_stack						An array of data to be rendered recursively by the render() function itself.
	 *																	If this is user-provided, this will be used as total data stack for the document.
     * @param CodelessUiNodeList	$smartNodeList						An instance of CodelessUiNodeList sent in from setElementData()
	 *																	containing a smartly arranged list of DOMNode elements. This will be seek()ed each time render() loops.
     * @param array					$BRVCD_UAC_FIELDS_LIST_FRAGMENTED	An array containing element selectors as key with a value-or-null flag
	 *																	indicating whether data for this element was allowed or blocked by BraveCode UAC engine. The data-brvcd_uac_field_access attribute will thus set.
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	void|string				void if the rendered template is echoed, depending on CodelessUiMachine::$auto_print_output_on_render. String if returned.
     */
	public function render(array $sub_data_stack = null, CodelessUiNodeList $smartNodeList = null, array $BRVCD_UAC_FIELDS_LIST_FRAGMENTED = null)
	{
		# 1. Use the $sub_data_stack as data_stack if looping
		if (!empty($sub_data_stack))
		{
			// This is $data_stack from loop
			$data_stack = $sub_data_stack;
		}
		else
		{
			// This is a root data
			$data_stack = $this->data_stack;
			// Lets include whatever external document fragments that needs to be included in the main template
			// so that everything is available for processing
			$this->setAllElementIncludes();
		}
		
		if (!empty($data_stack))
		{
			foreach($data_stack as $element_selector => $data_value)
			{
				# 2. Obtain the element into which to insert data
				if (!empty($smartNodeList))
				{
					$element = $smartNodeList->seek($element_selector);
					$elements = array($element);
				}
				else
				{
					// This is for root data
					$elements = $this->getElementsBySelector($element_selector);
				}
				
				foreach($elements as $element)
				{
					# 3. Work with $value_modifiers
					if (isset($this->value_modifiers[$element_selector]))
					{
						foreach($this->value_modifiers[$element_selector] as $handler)
						{
							// Any handler wanting to modify $element and $data_value would normally accept both of
							// its first and second parameters by reference
							call_user_func($handler, $element, $data_value);
						}
					}
	  
					# 4. If $BRVCD_UAC_FIELDS_LIST_FRAGMENTED is provided for current item list that this element belongs to...
					if (!empty($BRVCD_UAC_FIELDS_LIST_FRAGMENTED) && array_key_exists($element_selector, $BRVCD_UAC_FIELDS_LIST_FRAGMENTED))
					{
						if ($this->show_brvcd_uac_field_access)
						{
							$attr_value = $BRVCD_UAC_FIELDS_LIST_FRAGMENTED[$element_selector] === null ? 'false' : 'true';
							$this->setElementAttr($element, "data-brvcd_uac_field_access"/*attr_name*/, $attr_value);
						}
					}
					
					$params = array('params', 'params:after', 'params:before', 'attr', 'attr:after', 'attr:before', 'import', 'import:after', 'import:before', 'content', 'content:after', 'content:before', 'children', 'self');
				
					$properties = array();
					
					# 5. Reanalyze $data_stack and obtain the different parts when so constructed
					if (is_array($data_value))
					{
						foreach($params as $param_name)
						{
							if (array_key_exists('@'.$param_name, $data_value))
							{
								if (strpos($param_name, ':after') !== false)
								{
									 $fn = 'append';
								}
								elseif (strpos($param_name, ':before') !== false)
								{
									 $fn = 'prepend';
								}
								else
								{
									 $fn = 'replace';
								}

								$param_type = str_replace(array(':before', ':after'), '', $param_name);
								
								# 1. Work with properties
								if ($param_type === 'params')
								{
									$properties = array_merge($properties, (array)$data_value['@'.$param_name]);
								}
								else
								{
									// By this current $prop item, params has been parsed and $properties have been completely merged
									// Other $prop items can now make use of it
								}
								
								# 2. Work with attributes
								if ($param_type === 'attr')
								{
									foreach((array)$data_value['@'.$param_name] as $attr_name => $attr_value)
									{
										// Attempt to capture brvcd UAC properties and set it on this element
										// If it's a container element and there is BRVCD_UAC_FIELDS_LIST to apply on children, we're setting these properties before looping on children in Work with $data_value
										if (array_key_exists(strtoupper($attr_name), $this->brvcd_UAC_props))
										{
											if ($this->show_brvcd_uac_field_access)
											{
												if (strtoupper($attr_name) == 'BRVCD_UAC_FIELDS_LIST')
												{
													$BRVCD_UAC_FIELDS_LIST_FRAGMENTED = BrvcdArray::fromPropertyList($attr_value);
												}
												
												$attr_name = 'data-'.strtoupper($attr_name);
											}
										}
										
										$this->setElementAttr($element, $attr_name, $attr_value, $fn);
									}
								}
								
								# 3. Work with imports
								if ($param_type === 'import')
								{
									$import_location = $data_value['@'.$param_name];
									$content = $this->getReferencedImport($import_location, $element->getNodePath());
									
									$this->setElementData($element, $content/*$data_value*/, $fn, $properties);
								}
								
								# 4. Work with children
								if ($param_type === 'children' && is_array($data_value['@'.$param_name]))
								{
									$content = $data_value['@'.$param_name];
									$this->setElementData($element, $content/*$data_value*/, $fn, $properties);
								}
								
								# 5. Work with content
								if ($param_type === 'content' || $param_type == 'self')
								{
									$is_switch_to_self = $param_type === 'self';
									$content = $data_value['@'.$param_name];
									$this->setElementData($element, $content/*$data_value*/, $fn, $properties, $is_switch_to_self);
								}
								
								unset($data_value['@'.$param_name]);
							}
						}
					}
									
					// If $data_value is all a string
					// Or the array left after unsetting the other parts - above
					if (!empty($data_value))
					{
						$this->setElementData($element, $data_value, 'replace'/*$fn*/, $properties);
					}
				}
			}
			
				if (!empty($smartNodeList) && empty($element))
				{
					return;
				}
			
			// Should this whole document be parsed again to parse inserted data?
			// Then go just one round more... remember to ignore already parsed elements on this first round.
			if ($this->repeat_depth_count == 0/*This must not run on a mere $data_value array loop*/ && $this->parse_inserted_data)
			{
				$this->on_reparse = true;
				$this->parse_inserted_data = false;
				$HTMLDoc = $this->DOMDocument->saveHTML();
				
				// Frees memory associated with the DOMDocument
				unset($this->DOMDocument);
				self::__construct($this->data_stack, $HTMLDoc);
				$this->render();
				
				// CLEAR ALL ELEMENTS OF ANY ATTR CODELESSUI_NO_PARSE
			}
		}
		
		// Set the 'rendered' flag
		$this->is_rendered = true;
		
		// If not recursing
		if ($this->repeat_depth_count == 0/*This must not run on a mere $data_value array loop*/)
		{
			if (!$this->auto_print_output_on_render)
			{
				return $this->DOMDocument->saveHTML();
			}
			
			echo $this->DOMDocument->saveHTML();
		}
	}




	/**
     * Having rendered the template, get it whole or a spcified element within.
	 * 
     * @param string	$fragment_id	An element id selector specifying the element to return.
	 *
	 * This method will not change until a major release.
	 *
	 * @api
     *
     * @access public
	 *
	 * @return	string					The rendered string.
     */
	public function getRendered($fragment_id = null)
	{
		// If document has not been rrendered.
		if ($this->is_rendered !== true)
		{
			// We want to render. But let's first save the original $this->auto_print_output_on_render setting because we are overriding that for now.
			$auto_print_output_on_render_original = $this->auto_print_output_on_render;
			// Override
			$this->auto_print_output_on_render = false;
			
			// Render
			$this->render();
			
			// Return the original $this->auto_print_output_on_render setting.
			$this->auto_print_output_on_render = $auto_print_output_on_render_original;
		}
		
		return $this->DOMDocument->saveHTML(!empty($fragment_id) ? $this->DOMDocument->getElementById($fragment_id) : null);
	}
}
