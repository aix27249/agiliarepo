<?php
/*
 * AgiliaLinux repository template (based on default)
 *
 * Template is used to display stuff.
 * Template can define its own styles in variable $template_styles[] and scripts in variable $template_scripts[]
 * Template should form page body in variable $page_content.
 */

class Template extends GenericTemplate {

	// Template styles (optional, but who wants to use template without CSS?)
	public static $styles = ['defaults.css', 'layout.css', 'header.css', 'content.css', 'footer.css', 'fonts.css'];
	
	// Template scripts (optional)
	public static $scripts = ['jquery-1.9.1.min.js', 'jquery.color-2.1.2.min.js', 'jquery.scrollTo.min.js'];
}

