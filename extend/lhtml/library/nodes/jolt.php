<?php

namespace Bundles\LHTML\Nodes;
use Bundles\LHTML\Node;
use Bundles\LHTML\Parser;
use Bundles\LHTML\Scope;
use Bundles\LHTML\UseAlternateStack;
use Exception;
use e;

/**
 * Jolt quick templating system
 * @author Nate Ferrero
 */
class Jolt extends Node {

	/**
	 * Handle Jolt sections
	 * @author Nate Ferrero
	 */
	private static $sections = array();
	private static $parents = array();

	/**
	 * Placeholder content
	 * @author Nate Ferrero
	 */
	private static $placeholderContent = array();
	private static $placeholderContentOverride = array();

	/**
	 * Templates
	 * @author Nate Ferrero
	 */
	private static $templates = array();

	/**
	 * Special variables
	 */
	private $slug;
	private $section;
	private $finalized = false;
	private $json = false;

	/**
	 * Allow setting to the place holder
	 * @author Kelly Becker
	 */
	public static function setPlaceholder($name, $var, $position = null, $ns = 'global') {
		if(is_numeric($position))
			self::$placeholderContent[$ns][$name][$position] = $var;
		else
			self::$placeholderContent[$ns][$name] = $var;
	}

	/**
	 * Reset placeholder data
	 * @author Kelly Becker
	 */
	public static function resetPlaceholder($ns = 'global') {
		self::$placeholderContent[$ns] = array();
	}

	/**
	 * Save a section stack for later use
	 * @author Nate Ferrero
	 */
	private static function setSection($section, $file, &$stack) {
		/**
		 * Initialize array
		 */
		if(!isset(self::$sections[$section]))
			self::$sections[$section] = array();

		/**
		 * Check if the section is already being included, i.e. won't be used again later
		 */
		if(isset(self::$sections[$section][$file])
			&& self::$sections[$section][$file] == '@default')
			return false;

		/**
		 * Save the section stack
		 */
		self::$sections[$section][$file] = $stack;
		return true;
	}

	/**
	 * Load a section stack
	 * @author Nate Ferrero
	 */
	private static function getSection($section, $default) {
		if(isset(self::$sections[$section])) {
			/**
			 * If a non-default area has been defined, return it first
			 */
			foreach(array_reverse(array_keys(self::$sections[$section])) as $file)
				return self::$sections[$section][$file];
		} else {
			self::$sections[$section] = array();
		}

		/**
		 * There was no section to get, load the default
		 */
		self::$sections[$section][$default] = '@default';
		return e::$lhtml->file($default)->parse();
	}
	
	/**
	 * Process the jolt tag
	 * @author Nate Ferrero
	 */
	public function ready() {

		/**
		 * We must ready children first, so page functionality can work
		 * Pass false to avoid infinite loop, since we're within ready()
		 * @author Nate Ferrero
		 */
		$this->_ready(false);

		/**
		 * If this node has already been finalized, return
		 */
		if($this->finalized)
			return;

		/**
		 * Get current directory
		 * @author Nate Ferrero
		 */
		$jdata = $this->_data();
		$dir = realpath(dirname($jdata->__file__));

		/**
		 * Check if this is jolt section logic
		 */
		if(isset($this->attributes['section'])) {
			return $this->doSection($dir);
		}

		/**
		 * Check if this is jolt placeholder logic
		 * @author Nate Ferrero
		 */
		if(isset($this->attributes['placeholder'])) {
			return $this->doPlaceholder();
		}

		/**
		 * Jolt templating
		 * @author Nate Ferrero
		 */
		if(!isset($this->attributes['template']))
			throw new Exception('Jolt template is not specified');
		
		/**
		 * Disable rendering of this element
		 * @author Nate Ferrero
		 */
		$this->element = false;

		/**
		 * Load template
		 * @author Nate Ferrero
		 */
		$template = $this->attributes['template'];

		/**
		 * Template overrides
		 * @author Nate Ferrero
		 */
		if($template == '@override')
			return $this->doOverride();
		else
			return $this->doTemplate("$dir/$template");
	}

	/**
	 * Placeholder logic
	 * @author Nate Ferrero
	 */
	private function doPlaceholder() {
		$this->element = false;

		/**
		 * Get the namespace
		 * @author Kelly Becker
		 */
		$ns = !$this->_data()->namespace ? 'global' : $this->_data()->namespace;
		
		/**
		 * Check if placeholder content exists
		 * @author Nate Ferrero
		 */
		$placeholder = $this->attributes['placeholder'];

		/**
		 * Check for page:content etc.
		 */
		if(isset(self::$placeholderContentOverride[$ns][$placeholder])) {
			$node = self::$placeholderContentOverride[$ns][$placeholder];
			$slug = $node->fake_element;
			$applyTo = $node->getElementsByTagName("page:$slug");
			foreach($applyTo as $applyNow) {
				$applyNow->element = false;
				$final = false;
				if(isset(self::$placeholderContent[$ns][$slug])) {

					/**
					 * If this is a final="true" tag, prevent overriding
					 * @author Nate Ferrero
					 */
					if(isset(self::$placeholderContent[$ns][$slug]->attributes['final']) &&
						self::$placeholderContent[$ns][$slug]->attributes['final'] === 'true') {
						$final = true;
						break;
					}
					self::$placeholderContent[$ns][$slug]->element = false;
					self::$placeholderContent[$ns][$slug]->appendTo($applyNow);
				}
				break;
			}

			/**
			 * Override the content if not final
			 * @author Nate Ferrero
			 */
			if(!$final)
				self::$placeholderContent[$ns][$placeholder] = $node;
		}

		/**
		 * Apply the placeholder to content
		 */
		if(isset(self::$placeholderContent[$ns][$placeholder])) {
			$node = self::$placeholderContent[$ns][$placeholder];

			/**
			 * Sort the nodes by keys
			 * @author Kelly Becker
			 */
			if(is_array($node)) ksort($node);

			/**
			 * If node is an array loop through them and append one by one
			 * @author Kelly Becker
			 */
			if(is_array($node)) foreach($node as $n) {
				$n->element = false;
				$n->appendTo($this);	
			}
			
			else {
				$node->element = false;
				$node->appendTo($this);
			}
		}

		/**
		 * Allow default content to be loaded by including
		 * @author Nate Ferrero
		 */
		else if(isset($this->attributes['default'])) {
			$include = $this->_nchild(':include', $this->_code);
			$include->attributes['file'] = $this->attributes['default'];
		}

		/**
		 * Apply templates
		 */
		$this->applyTemplates();

		/**
		 * All done!
		 */
		return;
	}

	/**
	 * Override template placeholder logic
	 * @author Nate Ferrero
	 */
	private function doOverride() {

		e\trace_enter("Jolt Template Override", "", $this->children);

		/**
		 * Get content areas and remove them from the jolt tag
		 */
		$contents = $this->detachAllChildren();

		/**
		 * Assemble template content areas
		 */
		for($i = 0; $i < count($contents); $i++) {
			/**
			 * Get the current item
			 */
			$child = $contents[$i];
			if(!($child instanceof Node)) {
				if(trim($child) !== '')
					throw new Exception("Cannot place raw content directly inside a `&lt;:jolt&gt;` tag,
						only placeholder tags are allowed, such as `&lt;content&gt;` &mdash; in `$jdata->__file__`");
				continue;
			}

			/**
			 * Allow transparent nodes
			 */
			if(isset($child->joltTransparent) && $child->joltTransparent) {
				$all = $child->detachAllChildren();
				foreach($all as $item)
					$contents[] = $item;
				continue;
			}

			/**
			 * Save the new overrides
			 */
			e\trace("Jolt Placeholder Override", "", array('placeholder' => $child->fake_element, 'content' => $child->children));
			self::$placeholderContentOverride[$child->fake_element] = $child;
		}

		e\trace_exit();
	}

	/**
	 * Templating logic
	 * @author Nate Ferrero
	 */
	private function doTemplate($template) {
		
		if(pathinfo($template, PATHINFO_EXTENSION) !== 'jolt')
			$template .= '.jolt';

		e\trace_enter("Jolt Template", "Processing `$template`");
		
		/**
		 * Get content areas and remove them from the jolt tag
		 */
		$contents = $this->detachAllChildren();

		/**
		 * Load the .jolt file
		 */
		$stack = e::$lhtml->file($template)->parse();

		/**
		 * Process each jolt template and remove them from the output
		 */
		$jolts = $stack->getElementsByTagName('jolt:templates');
		foreach ($jolts as $jolt) {
			foreach ($jolt->children as $template) {
				self::$templates[] = $template;
			}
			$jolt->remove();
		}

		/**
		 * Add the new stack to the jolt template tag
		 */
		$stack->appendTo($this);

		/**
		 * Add attributes as variables in new stack
		 */
		foreach ($this->attributes as $key => $value)
			if($key !== ':load')
				$this->_data()->$key = $this->_string_parse($value, true); /* Second argument means objects will be returned as-is;
				Because these variables are not final output to the page, we don't need them to strictly be strings */
		
		/**
		 * Assemble template content areas
		 */
		for($i = 0; $i < count($contents); $i++) {
			/**
			 * Get the current item
			 */
			$child = $contents[$i];
			if(!($child instanceof Node)) {
				if(trim($child) !== '')
					throw new Exception("Cannot place raw content directly inside a `&lt;:jolt&gt;` tag,
						only placeholder tags are allowed, such as `&lt;content&gt;` &mdash; in `$jdata->__file__`");
				continue;
			}

			/**
			 * Allow transparent nodes
			 */
			if(isset($child->joltTransparent) && $child->joltTransparent) {
				$all = $child->detachAllChildren();
				foreach($all as $item)
					$contents[] = $item;
				continue;
			}

			/**
			 * Add child to placeholder content
			 * @author Nate Ferrero
			 */
			e\trace("Jolt Placeholder", "", array('placeholder' => $child->fake_element, 'content' => $child->children));

			self::$placeholderContent[$child->fake_element] = $child;
		}

		return e\trace_exit();
	}

	/**
	 * Apply templates to stack
	 * @author Nate Ferrero
	 */
	private function applyTemplates() {

		/**
		 * Apply all templates to stack
		 * @author Nate Ferrero
		 */
		foreach (self::$templates as $template) {
			if (!($template instanceof Node)) continue;
			$applyTo = $this->getElementsByTagName($template->fake_element);
			foreach ($applyTo as $applyNow) {
				$applyNow->element = false;
				$applyNow->_data = new Scope($applyNow);

				/**
				 * Add sources that get executed on build
				 */
				foreach ($applyNow->attributes as $key => $value) {
					$applyNow->_data()->addDeferredSource($key, $value);
				}
				
				/**
				 * First isolate any children in the instance, and keep them for now
				 */
				$applyChildren = $applyNow->detachAllChildren();
				
				/**
				 * Copy template into instance
				 */
				foreach ($template->children as $templateChild) {
					if ($templateChild instanceof Node) {
						$newChild = clone $templateChild;
						$newChild->appendTo($applyNow);
					} else {
						$newChild = $templateChild;
						$applyNow->children[] = $newChild;
					}
				}
				
				/**
				 * Copy children back into content
				 */
				$catchAll = array();
				foreach ($applyChildren as $child) {
					if(!($child instanceof Node) || (strpos($child->fake_element, 'jolt:') !== 0)) {
						$catchAll[] = $child;
						continue;
					}
					$tags = $applyNow->getElementsByTagName($child->fake_element);
					$absorbed = false;
					foreach ($tags as $tag) {
						$absorbed = true;
						$tag->element = false;
						$child->element = false;
						$child->appendTo($tag);
						break;
					}
					if(!$absorbed)
						$catchAll[] = $child;
				}

				/**
				 * Add all remaining items to the catchall
				 * @author Nate Ferrero
				 */
				$tags = $applyNow->getElementsByTagName('jolt:catchall');
				foreach ($tags as $tag) {
					$tag->element = false;
					$tag->absorbAll($catchAll);
					break;
				}
			}
		}
	}
	
	/**
	 * Jolt section logic
	 * @author Nate Ferrero
	 */
	private function doSection() {
		
		/**
		 * Get current directory
		 * @author Nate Ferrero
		 */
		$jdata = $this->_data();
		$this->slug = md5($jdata->__file__);
		$dir = realpath(dirname($jdata->__file__));

		$this->section = $this->attributes['section'];

		/**
		 * Check if this is an outclude or include
		 * @author Nate Ferrero
		 */
		if(isset($this->attributes['parent'])) {

			$parent = $this->attributes['parent'];
			$parentName = explode('/', $parent);
			$parentName = array_pop($parentName);

			/**
			 * Output the jolt tag as a div
			 * @author Nate Ferrero
			 */
			$this->element = 'div';
			$this->finalized = true;
			unset($this->attributes['section']);
			unset($this->attributes['parent']);
			$this->attributes['class'] = 'jolt-content jolt-content-' . $this->slug;

			/**
			 * This will let us know if the current section has already been loaded;
			 * if it has, that means that $outclude will be set to false (i.e. it's being included)
			 * @author Nate Ferrero
			 */
			$outclude = self::setSection($this->section, $jdata->__file__, $this);

			/**
			 * If we are being included instead of outcluding the parent, just return here
			 * @author Nate Ferrero
			 */
			if(!$outclude)
				return;
			
			/**
			 * Check for loading through JSON
			 */
			if(isset($_POST['@jolt'])) {
				$status = $_POST['@jolt'];

				/**
				 * Get the root section as JSON, this will force proper loading
				 * @author Nate Ferrero 
				 */
				if(array_key_exists($this->section, $status)) {
					$this->json = true;
					return;
				}
			}

			/**
			 * We are outcluding, so we need to wait for the parent to re-include this
			 * at the proper location in the stack; for now $this->_ will be null
			 * @author Nate Ferrero
			 */
			$this->_ = null;
			
			e\trace("Jolt", "Loading parent `$parent`");

			/**
			 * Include the parent content area if not included
			 */
			$v = "$dir/$parent";
			if(pathinfo($v, PATHINFO_EXTENSION) !== 'lhtml')
				$v .= '.lhtml';
			$v = realpath($v);

			if($jdata->__file__ == $v)
				throw new Exception("Cannot use a jolt section as it's own parent");

			/**
			 * Render the parent if needed
			 */
			if(!isset(self::$parents[$v])) {
				self::$parents[$v] = new Jolt();
				self::$parents[$v]->slug = md5($v);
				self::$parents[$v]->finalized = true;
				if($this->json)
					self::$parents[$v]->json = true;
				/**
				 * Parse the parent into the new Jolt node
				 * @author Nate Ferrero
				 */
				self::$parents[$v] = e::$lhtml->file($v)->parse(self::$parents[$v]);
			}

			/**
			 * Use a new stack from the parent
			 */
			$use = new UseAlternateStack;
			$use->stack = self::$parents[$v];
			throw $use;
		}

		/**
		 * Content section definitions
		 */
		if(isset($this->attributes['content'])) {

			$content = $this->attributes['content'];

			e\trace("Jolt", "Loading content `$content`");

			$v = "$dir/$content";
			if(pathinfo($v, PATHINFO_EXTENSION) !== 'lhtml')
				$v .= '.lhtml';

			/**
			 * Append the section before building
			 */
			$node = self::getSection($this->section, $v);
			$node->appendTo($this);

			/**
			 * Display the jolt section as a div
			 */
			$this->element = 'div';
			$this->finalized = true;
			unset($this->attributes['section']);
			unset($this->attributes['content']);
			$this->attributes['class'] = 'jolt-section jolt-section-' . $this->section;
			return;
		}

		throw new Exception('Incorrect use of `<:jolt section="'.$this->section.'">` without providing a `parent` or `content` attribute');
	}

	/**
	 * Custom build for JSON output if loaded through Jolt/activate.js
	 * @author Nate Ferrero
	 */
	public function build($pre = true) {

		if($pre)
			$this->_init_scope();

		if($this->json) {

			/**
			 * Delay for testing
			 * @author Nate Ferrero
			 */
			//sleep(25);

			/**
			 * Output in JSON format
			 * @author Nate Ferrero
			 */
			e\disable_trace();
			$data = e\json_encode_safe(array(
				'slug' => $this->slug,
				'section' => $this->section,
				'href' => $_SERVER['REQUEST_URI'],
				'html' => parent::build(false)
			));
			e::$lhtml->setContentType('text/json');
			return $data;
		} else {
			e\trace("Jolt Build", "&lt;$this->fake_element " . $this->_attributes_parse() . "&gt;", null, 5);
			return parent::build(false);
		}
	}

}