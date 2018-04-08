<?php
/**
 * Output library
 */

defined('INTERNAL_SCRIPT') || die;

require_once($CFG->libdir.'/classes/xhtml_container_stack.php');
require_once($CFG->libdir.'/classes/output/notification.php');
require_once($CFG->libdir.'/classes/output/base_renderer.php');
require_once($CFG->libdir.'/classes/output/post_renderer.php');


interface renderable {
	public function render();
}