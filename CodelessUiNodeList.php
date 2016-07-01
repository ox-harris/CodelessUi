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
 * @copyright		2016 Ox-Harris Creative
 * @license		GPL-3.0
 * @version		Release: 1.0.0
 * @link		https://www.facebook.com/CodelessUi/
 * @used-by		CodelessUiMachine
 * @see			CodelessUiMachine
 */	 



/** 
 * CodelessUiNodeList.
 * Smartly orders a list of DOMNode elements right on the main template.
 * Able to duplicate elements to render extra data items where data items outnumbers the available number of elements.
 */
 
 Class CodelessUiNodeList
{
	/**
     * Holds a list of DOMNode elements.
	 *
	 * A combination of elements from retrieved DOMNodeList on the container class CodelessUiMachine and any extra ones auto-generated
	 * to complete the ideal number for the current data stack.
     *
     * @var array $nodeList
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $nodeList = array();
	
	/**
     * An internal counter use by seek().
	 *
	 * Initialized to 0; increased each time seek() is called.
     *
     * @var int $cursor
	 *
	 * @see seek().
	 *
	 * @access private
     */
	private $cursor = null;
	
	/**
     * Holds list of element selectors provided by the container class CodelessUiMachine.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var array $calls_list
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $calls_list = array();
	
	/**
     * Total count of the number of calls expected.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $num_node_calls_expected
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $num_node_calls_expected = 0;
	
	/**
     * Total count of calls list provided.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $num_node_calls_expected
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $num_nodes_provided = 0;
	
	/**
     * Half of total count of calls list provided.
	 *
	 * Used to determine when to auto-generate more elements for the given number of items in data stack.
     *
     * @var int $num_node_calls_expected
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $half_num_nodes_provided;
	
	/**
     * The remainder after dividing total number of calls list provided with 2. That is, modulo 2 of count calls list.
	 *
	 * This will be the offset integer added to second half of the total count of calls list provided.
     *
     * @var int $num_node_calls_expected
	 *
	 * @see populateNodeList().
	 *
	 * @access private
     */
	private $half_num_nodes_provided_offset;
	
	/**
     * An instance of the container class CodelessUiMachine.
	 *
	 * This is here in order to access certain information from the current state of CodelessUiMachine.
     *
     * @var object $CodelessUiMachine
	 *
	 * @see __constructor().
	 *
	 * @access private
     */
	public $CodelessUiMachine;
	
	/**
     * A string name of the algarithm to use when ordering elements in list.
	 *
	 * This is obtained from the current state of CodelessUiMachine.
     *
     * @var string $current_repeat_fn
	 *
	 * @see __constructor().
	 *
	 * @access private
     */
	public $current_repeat_fn = array();

	
	
	/**
     * Constructor for CodelessUiNodeList.
     *
     * Loads the CodelessUiMachine and sets the current_repeat_fn.
	 *
     * @param CodelessUiMachine 	$CodelessUiMachine 		An instance CodelessUiMachine.
	 *
	 * @return void
     */
    public function __construct(CodelessUiMachine $CodelessUiMachine)
	{
		$this->CodelessUiMachine = $CodelessUiMachine;
		$this->current_repeat_fn = (array)$this->CodelessUiMachine->current_repeat_fn;
    }
	


	/**
     * Destructor for CodelessUiNodeList.
     *
     * Frees up all memory used. May not be neccessary.
     * 
	 * @return void
     */
	public function __destruct()
	{
        // Free memory associated with the nodeList
		unset($this->nodeList);
    }
	
	
	/**
     * Add a DOMNode item to the list.
     *
     * @param object 	$node 			A DOMNode element.
	 * @param bool 		$before_cursor 	The numeric key location of an item in list before which to add this.
     * 
	 * @return void
     */
	private function addToNodeList($node, $before_cursor = null)
	{
		if ($before_cursor)
		{
			$nodeList_second_half = array_splice($this->nodeList, $before_cursor);
			$this->nodeList[] = $node;
			$this->nodeList = array_merge($this->nodeList, $nodeList_second_half);
		}
		else
		{
			$this->nodeList[] = $node;
		}
	}
	
	
	
	/**
     * Fill CodelessUiNodeList::$calls_list with DOMNode elements. This usually called CodelessUiMachine.
     *
     * @param DOMNode 	$node 				A DOMNode element.
	 * @param array 	$calls_list 		A list of element selectors provided by the container class CodelessUiMachine.
     * @param bool	 	$is_switch_to_self 	Sets whether to populate data on element itself or child elements.
     * 
	 * @see				CodelessUiMachine::setElementData().
	 *
	 * @return void
     */
	public function populateNodeList($element, $calls_list, $is_switch_to_self = false)
	{
		// Set these before any duplication begins... may be needed
		$this->calls_list = $calls_list;
		$this->num_node_calls_expected = count($this->calls_list);
		
		if (!empty($element))
		{
			# 1. Attempt to populate nodeList
			# Default action is to populate children, but if switch_population_element is set, find the element for the later repeat
			
			if (!$is_switch_to_self)
			{
				# A. We populate chidren of element
				if (is_string($this->calls_list[0]))
				{
					# a. This element has specified which named children to populate
					# We're working with named children
					for($i = 0; $i < count($this->calls_list); $i ++)
					{
						$elements = $this->CodelessUiMachine->getElementsBySelector($this->calls_list[$i], $this->CodelessUiMachine->current_node_path);
						if (!empty($elements))
						{
							$this->addToNodeList($elements->item(0));
						}
					}
				}
				else
				{
					# b. This element does not specify named children.
					# So we populate all children except SPACES and COMMENTS
					$children = $element->childNodes;
					foreach($children as $key => $node)
					{
						if(!($node instanceof DOMText || $node instanceof DOMComment))
						{
							$this->addToNodeList($node);
						}
					}
					
					# IF the provided element cannot produce a child, duplicate it to get a child 
					if (empty($this->nodeList))
					{
						$node = $this->duplicateNode($element, 'sub_child');
						$this->addToNodeList($node);
					}
				}
			}
			elseif ($is_switch_to_self)
			{
				# A. This element does not want to populate its children.
				# Then we populate itself
				$this->addToNodeList($element);
			}
			
			$this->num_nodes_provided = count($this->nodeList);
			$this->half_num_nodes_provided = round($this->num_nodes_provided / 2);
			$this->half_num_nodes_provided_offset = ($this->half_num_nodes_provided * 2 < $this->num_nodes_provided ? 1 : ($this->half_num_nodes_provided * 2 > $this->num_nodes_provided ? -1 : 0));
			
			# 2. If number of items populated is smaller than expected number of calls, then we fill the list up
			if ($this->num_nodes_provided < $this->num_node_calls_expected)
			{
				$this->completeNodeList();
			}
			
			$this->orderNodeList();
		}
	}
	
	
		
		
	/**
     * Completes CodelessUiNodeList::$calls_list with auto generated (duplicated) DOMNode elements.
	 *
	 * @return void
     */
	private function completeNodeList()
	{
		if ($this->num_nodes_provided < $this->num_node_calls_expected)
		{
			$cursor = null;
			$last_cursor_move = null;
			
			for($current_node_key = $this->num_nodes_provided; $current_node_key < $this->num_node_calls_expected; $current_node_key ++)
			{
				// Initialize this flag
				$insert_before = false;
				
				// The cursor movement
				// Increment or decrement the cursor
				if ($last_cursor_move == "+")
				{
					if ($cursor < $this->num_nodes_provided)
					{
						// Cursor can well increment to exactly total $this->num_nodes_provided which will be a node key that is beyond range
						// But this helps indicate end of $this->nodeList, which the next section handles with a repeat
						$cursor ++;
					}
				}
				elseif ($last_cursor_move == "-")
				{
					if ($cursor > 0)
					{
						// Cursor may well decrement to exactly 0, which is the minimum valid node key
						// But this '0' condition will be detected on the next call to seek and a repeat will begin
						$cursor --;
					}
					elseif ($cursor === 0)
					{
						$cursor = 0;
						$last_cursor_move = "+";
					}
				}
				
				// Use the repeat function defined Or if this is first run where $cursor is null
				// 1. When end of nodeList is reached.
				if ((int)$cursor === (int)($this->num_nodes_provided) || $cursor === null)
				{
					// Simply repeat from begining
					if (in_array("simple", $this->current_repeat_fn))
					{
						// 1. Everytime nodelist end is reached and the 'once' flag is not set
						// 2. Or when the 'once' flag is set, begins from the very point where repeating backwards will just satisfy the total num_node_calls_expected.
						if ((!in_array("once", $this->current_repeat_fn))
						|| (in_array("once", $this->current_repeat_fn) && $current_node_key + $this->num_nodes_provided >= $this->num_node_calls_expected))
						{
							$cursor = 0;
							$last_cursor_move = "+";
						}
					}
					// Repeat from end back to begining
					elseif (in_array("mirror", (array)$this->current_repeat_fn))
					{
						// 1. Everytime nodelist end is reached and the 'once' flag is not set
						// 2. Or when the 'once' flag is set, begins from the very point where repeating backwards will just satisfy the total num_node_calls_expected.
						if ((!in_array("once", $this->current_repeat_fn))
						|| (in_array("once", $this->current_repeat_fn) && $current_node_key + $this->num_nodes_provided >= $this->num_node_calls_expected))
						{
							$cursor = $this->num_nodes_provided - 1;
							$last_cursor_move = "-";
						} 
					}
					
					// If none of the conditions above were met and $cursor is still null (on first run)
					// Initialize the cursor - start with forward movement.
					if ($cursor === null)
					{
						$cursor = $this->num_nodes_provided - 1;
						$last_cursor_move = "+";
					}
				}
				// 2. There is inner_padded repeat
				if (in_array("inner_padded", (array)$this->current_repeat_fn) || in_array("#inner_padded", (array)$this->current_repeat_fn))
				{
					$middle_point = $this->half_num_nodes_provided + $this->half_num_nodes_provided_offset;
					
					// Inner_padded must always use the middle element for padding
					// If inner_padded is set to use the LEFT next element for padding can only work when $this->num_nodes_provided must be EVEN.
					if (in_array("#inner_padded", $this->current_repeat_fn) && $this->num_nodes_provided % 2 == 0 && $middle_point > 0)
					{
						$middle_point --;
					}
					
					$cursor = $middle_point;
					$insert_before = true;
				}
				
				
				// Add a duplicate of this node
				$node = $this->nodeList[$cursor];
				if ($insert_before)
				{
					// Tear $this->nodeList into halves, do $this->duplicateNode with insert before, then join $this->nodeList back
					$this->addToNodeList($this->duplicateNode($node, false, $node), $cursor);
				}
				else
				{
					$this->addToNodeList($this->duplicateNode($node));
				}
			}
		}
	}
	
	
	
	/**
     * Order items in CodelessUiNodeList::$calls_list using certain algarithms called repeat unctions.
	 *
	 * @return void
     */
	private function orderNodeList()
	{
		// Nodes have been populated... Apply other Fns: Justify or Shuffle
		if (in_array("simple", (array)$this->current_repeat_fn) || in_array("mirror", (array)$this->current_repeat_fn))
		{
			/* Justify should only work WITH repeats*/
			if (in_array("justify", $this->current_repeat_fn))
			{
				if ($this->num_nodes_provided < $this->num_node_calls_expected
				&& ($this->num_nodes_provided * 2 > $this->num_node_calls_expected) || !in_array("once", $this->current_repeat_fn))
				{
					// Start patching...
					$slice_start = $this->num_node_calls_expected % $this->num_nodes_provided;
					$patch_count = (int)(($this->num_nodes_provided - $slice_start) / 2);

					if ($patch_count)
					{
						// Get the original list - subject to reverse
						$nodeList = array_slice($this->nodeList, 0, $this->num_nodes_provided);
						
						if (in_array("mirror", $this->current_repeat_fn))
						{
							// How many times have $this->num_nodes_provided fit into $this->num_node_calls_expected?
							$num_rounds = (int)($this->num_node_calls_expected / $this->num_nodes_provided);
							// Is $num_rounds even or odd? If odd, reverse $nodeList
							if ($num_rounds % 2 != 0)
							{
								$nodeList = array_reverse($nodeList);
							}
						}
						
						$patches = array_slice($nodeList, $slice_start, $patch_count);
						
						for ($i = 0; $i < $patch_count; $i++)
						{
							$delete = array_shift($this->nodeList);
							array_push($this->nodeList, $patches[$i]->parentNode->appendChild($patches[$i]->cloneNode(true)));
							$delete->parentNode->removeChild($delete);
						}
					}
				}
			}
		}
		else
		{
			/*justify should only work WITHOUT repeats*/
			if (in_array("shuffle", (array)$this->current_repeat_fn))
			{
				shuffle($this->nodeList);
			}
		}
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
	private function duplicateNode($node, $insert_duplicate_as = 'immediate_sibling', $insert_before = false)
	{
		$parent = $node->parentNode;
		if ($insert_duplicate_as == 'sub_child')
		{
			$parent = $node;
			if ($this->CodelessUiMachine->allow_html_formatting)
			{
				if (is_object($node->firstChild))
				{
					$value = !empty($node->firstChild->nodeValue) && empty(trim($node->firstChild->nodeValue)) ? $node->firstChild->nodeValue : "\r\n";
					$beforeNode = $parent->ownerDocument->createTextNode($value);
				}
				if (is_object($node->lastChild))
				{
					/*if ($node->lastChild->nodeName == "#comment" || ($node->lastChild->nodeName == "#text" && (trim($node->lastChild->nodeValue))))
					{
						$afterNode = $node->lastChild->cloneNode();
					}*/
					$afterNode = $parent->ownerDocument->createTextNode((!empty($node->lastChild->nodeValue) && empty(trim($node->lastChild->nodeValue)) && $node->lastChild->nodeValue !== $value ? $node->lastChild->nodeValue : "\t"));
				}
			}
		}
		else
		{
			if ($insert_duplicate_as == 'immediate_sibling' && !$insert_before)
			{
				// Insert an anchor immediately the first element to be duplicated
				// We will placing things right before this anchor. Which automatically is after the first element duplicated
				// Placing an element befored this anchor will push the anchor forward... and so on
				if (!isset($this->insert_before))
				{
					$this->insert_before = $parent->ownerDocument->createTextNode("\r\n");
					$parent->insertBefore($this->insert_before, $node->nextSibling);
				}
				
				$insert_before = $this->insert_before;
			}

			if ($this->CodelessUiMachine->allow_html_formatting)
			{
				if (is_object($node->previousSibling))
				{
					$value = !empty($node->previousSibling->nodeValue) && empty(trim($node->previousSibling->nodeValue)) ? $node->previousSibling->nodeValue : "\r\n";
					$beforeNode = $parent->ownerDocument->createTextNode($value);
				}
				if (is_object($node->nextSibling))
				{
					/*if ($node->nextSibling->nodeName == "#comment" || ($node->nextSibling->nodeName == "#text" && empty(trim($node->nextSibling->nodeValue))))
					{
						$afterNode = $node->nextSibling->cloneNode();
					}*/
					$value = !empty($node->nextSibling->nodeValue) && empty(trim($node->nextSibling->nodeValue)) ? $node->nextSibling->nodeValue : "\r\n";
					$afterNode = $parent->ownerDocument->createTextNode((!empty($node->nextSibling->nodeValue) && empty(trim($node->nextSibling->nodeValue)) && $node->nextSibling->nodeValue !== $value ? $node->nextSibling->nodeValue : "\t"));
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
     * Get the next item in CodelessUiNodeList::$calls_list
     *
	 * @param int 	$insert_before 		The numeric key location of an item in list to retrieve.
     * 
	 * @return void
     */
	public function seek($cursor = null)
	{
		if ($cursor === null)
		{
			if ($this->cursor === null)
			{
				$this->cursor = 0;
			}
			else
			{
				$this->cursor ++;
			}
			
			$cursor = $this->cursor;
		}
		elseif (is_string($cursor))
		{
			// This is a strict retrieve from main document and not a reference
			if (in_array($cursor, $this->calls_list))
			{
				$cursor = array_search($cursor, $this->calls_list);
			}
		}
		
		if (array_key_exists($cursor, $this->nodeList))
		{
			return $this->nodeList[$cursor];
		}
		else
		{
			//throw new Exception('Couldn\'t retrieve item for cursor '.$cursor.'!');
		}
   }
}
