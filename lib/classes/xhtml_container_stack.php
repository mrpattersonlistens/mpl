<?php
/**
 * This class keeps track of which HTML tags are currently open.
 *
 * This makes it much easier to always generate well formed XHTML output, even
 * if execution terminates abruptly. Any time you output some opening HTML
 * without the matching closing HTML, you should push the necessary close tags
 * onto the stack.
 *
 * @copyright 2009 Tim Hunt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.0
 * @package core
 * @category output
 */
class xhtml_container_stack {

    /**
     * @var array Stores the list of open containers.
     */
    protected $opencontainers = array();

    /**
     * @var array In developer debug mode, stores a stack trace of all opens and
     * closes, so we can output helpful error messages when there is a mismatch.
     */
    protected $log = array();

    /**
     * @var boolean Store whether we are developer debug mode. We need this in
     * several places including in the destructor where we may not have access to $CFG.
     */
    protected $isdebugging = true;

    /**
     * Constructor
     */
    public function __construct() {
        global $CFG;
        $this->isdebugging = $CFG->debugdeveloper;
    }

    /**
     * Push the close HTML for a recently opened container onto the stack
     * and return the HTML open tag
     *
     * @param straing $tag the HTML tag
     * @param string $name The name of container. This is checked when {@link pop()}
     *      is called and must match, otherwise a developer debug warning is output.
     * @param array $attributes An optional array of attributes for this tag
     * @param return string The HTML open tag
     */
    public function push($tag, $name, $attributes=array()) {
        $container = new stdClass;
        $container->tag = $tag;
        $container->name = $name;
        $container->attributes = array();
        
        if (is_array($attributes)) {
        	$container->attributes = $attributes;
        } else {
        	throw new coding_exception('Non-array value passed to $attributes in xhtml_container_stack::push.');
        }
        
        $attr = '';
        if (!empty($container->attributes)) {
        	foreach ($container->attributes as $property => $value) {
        		$attr .= " $property=\"$value\"";
        	}
        }
        
        $container->openhtml = "<{$tag}{$attr}><!--$name-->\n";
    	$container->closehtml = "</$tag><!--$name-->\n";
        
        if ($this->isdebugging) {
            $this->log('Open', $tag, $name);
        }
        
        array_push($this->opencontainers, $container);
        return $container->openhtml;
    }

    /**
     * Pop the HTML for the next closing container from the stack. The $name
     * must match the name passed when the container was opened, otherwise a
     * warning will be output.
     *
     * @param string $name The name of the container.
     * @return string the HTML required to close the container.
     */
    public function pop($name) {
        if (empty($this->opencontainers)) {
            debugging('<p>There are no more open containers. This suggests there is a nesting problem.</p>' .
                    $this->output_log(), DEBUG_DEVELOPER);
            return;
        }

        $container = array_pop($this->opencontainers);
        if ($container->name != $name) {
            debugging('<p>The name of container to be closed (' . $container->name .
                    ') does not match the name of the next open container (' . $name .
                    '). This suggests there is a nesting problem.</p>' .
                    $this->output_log(), DEBUG_DEVELOPER);
        }
        if ($this->isdebugging) {
            $this->log('Close', $container->tag, $name);
        }
        return $container->closehtml;
    }

    /**
     * Close all but the last open container. This is useful in places like error
     * handling, where you want to close all the open containers (apart from <body>)
     * before outputting the error message.
     *
     * @param bool $shouldbenone assert that the stack should already be empty anyway - causes a
     *      developer debug warning if it isn't.
     * @return string the HTML required to close any open containers inside <body>.
     */
    public function pop_all_but_last($shouldbenone = false) {
        if ($shouldbenone && count($this->opencontainers) !== 1) {
            debugging('<p>Some HTML tags were opened in the body of the page but not closed.</p>' .
                    $this->output_log(), DEBUG_DEVELOPER);
        }
        $output = '';
        // debugging('<p>pop_all_but_last()</p>', DEBUG_DEVELOPER, false);
        // debugging('<pre>'.clean_text(print_r($this->opencontainers,true)).'</pre>', DEBUG_DEVELOPER, false);
        while (count($this->opencontainers) > 1) {
            $container = array_pop($this->opencontainers);
            // debugging('POP:<pre>'.clean_text(print_r($container,true)).'</pre>', DEBUG_DEVELOPER);
            $output .= $container->closehtml;
        }
        // debugging('<pre>'.clean_text(print_r($this->opencontainers,true)).'</pre>', DEBUG_DEVELOPER, false);
        // debugging('<pre>'.clean_text(print_r($output,true)).'</pre>', DEBUG_DEVELOPER, false);
        return $output;
    }

    /**
     * You can call this function if you want to throw away an instance of this
     * class without properly emptying the stack (for example, in a unit test).
     * Calling this method stops the destruct method from outputting a developer
     * debug warning. After calling this method, the instance can no longer be used.
     */
    public function discard() {
        $this->opencontainers = null;
    }

    /**
     * Adds an entry to the log.
     *
     * @param string $action The name of the action
     */
    protected function log($action, $tag, $name) {
        $this->log[] = '<li>' . $action . ' ' . $tag . ' ' . $name . ' at:' .
                format_backtrace(debug_backtrace()) . '</li>';
    }

    /**
     * Outputs the log's contents as a HTML list.
     *
     * @return string HTML list of the log
     */
    public function output_log() {
        return '<ul>' . implode("\n", $this->log) . '</ul>';
    }
}