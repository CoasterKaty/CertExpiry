<?php
/*
Site Template page builder class
Katy Nicholson
https://katystech.blog/
*/
require_once dirname(__FILE__) . '/auth.php';
require_once dirname(__FILE__) . '/graph.php';
require_once dirname(__FILE__) . '/pageBuilder_tables.php';
require_once dirname(__FILE__) . '/pageBuilder_forms.php';
require_once dirname(__FILE__) . '/exception.php';
define('_NL', "\r\n");


class sitePage {
	var $page;
	var $title;
	var $script;
	var $mainNavigation = array();
	var $modAuth;
	var $modGraph;
	var $flyout;
	var $preloadImages = array();
	var $displayRole = 1;

	public $logo;

	function __construct($title = '', $script = '', $allowAnonymous = '0') {
		$this->title = $title;
		$this->script = $script;
		try {
			$this->modAuth = new modAuth($allowAnonymous);
		} catch (Exception $e) {
			throw new siteException($e->getMessage() . "<br>sitePage->__construct()");
		}

		if (!$allowAnonymous || $this->modAuth->isLoggedIn) $this->modGraph = new modGraph();
	}


	function addNavigation($navItem) {
		$this->mainNavigation[] = $navItem;
		return $navItem;
	}

	function printLoginItem() {
		if ($this->modAuth->isLoggedIn) {
			$profile = $this->modGraph->getProfile();
			$photo = $this->modGraph->getPhoto();

			return '<div class="login" tabindex="-1" id="m_login"><div><span>' . $profile->displayName . '</span><span class="light">' . $profile->userPrincipalName . '</span></div>' . $photo . '<ul>' . ($this->displayRole ? '<li>Role: ' . ($this->modAuth->checkUserRole('Role.User') ? 'User' : '') . ($this->modAuth->checkUserRole('Role.Admin') ? 'Admin' : '') . ($this->modAuth->checkUserRole('Default Access') ? 'Read Only' : '') . '</li>' : '') . '<li><a href="' . (strpos($_SERVER['REQUEST_URI'], '?') ? explode('?', $_SERVER['REQUEST_URI'])[0] : $_SERVER['REQUEST_URI']) . '?action=logout">Sign Out</a></li></ul></div>';
		}
		$this->preloadImages[] = '/images/notLoggedIn.png';
		return '<div class="login" tabindex="-1" id="m_login"><div><span class="loggedout">Not signed in</span></div><span class="userPhoto notLoggedIn"><img src="/images/notLoggedIn.png" /></span><ul><li><a href="' . $_SERVER['REQUEST_URI'] . (strstr($_SERVER['REQUEST_URI'], '?') ? '&' : '?') . 'login=1">Sign in</a></li></ul></div>';

	}

	function initFlyout() {
		$this->preloadImages[] = '/images/Close.png';
		$this->flyout = '<div id="flyout" tabindex="-5"><div id="flyoutTitle">Loading...</div><div id="flyoutClose" title="Close" onclick="JavaScript:closeFlyout();"> </div><div id="flyoutFrame"></div></div>';
	}

	function printNavigation() {
		$nav['main'] = '<div id="navMain" class="nav"><div ' . ($this->logo ? ' style="background-image: url(\'' . $this->logo . '\'); padding-left: 50px; margin-left: 3px;"' : '') . ' class="title"><span>' . $this->title . '</span></div>' . _NL;
		$nav['main'] .= $this->printLoginItem();
		$nav['sub'] = '<div id="navSub" class="nav">' . _NL;
		foreach ($this->mainNavigation as $navItem) {
			if ($navItem->type == 'main' || $navItem->type == 'sub') {
				$nav[$navItem->type] .= '<div style="float: ' . $navItem->position . ';' . 
					($navItem->icon ? ' background-image: url(\'/images/' . $navItem->icon . '\'); ' : '') . 
					($navItem->width ? ' width: ' . $navItem->width . 'px;" ' : '" ') . 
					($navItem->tooltip ? 'title="' . htmlentities($navItem->tooltip) . '" ' : '') .  
					($navItem->selected ? 'class="selected"' : '') . 
					'tabindex="-1" id="' . $navItem->id . '" ' . 
					($navItem->flyoutAction ? ' onclick="JavaScript:openFlyout(\'' . $navItem->flyoutAction . '\', \'' . $navItem->flyoutTitle . '\');"' : '') . 
					($navItem->link ? ' onclick="JavaScript:' . ($navItem->newWindow ? 'window.open(' : 'location.href=') . '\'' . $navItem->link . '\'' . ($navItem->newWindow ? ')' : '') . ';"' : '') . '><span>' .  $navItem->name  . '</span>';
				if ($navItem->subMenu) {
					$nav[$navItem->type] .= $this->printNavigationItem($navItem);
				}
				$nav[$navItem->type] .= '</div>' . _NL;
			}
		}
		$nav['main'] .= '</div>' . _NL;
		$nav['sub'] .= '</div>' . _NL;
		return $nav['main'] . $nav['sub'];
	}

	function printNavigationItem($navItem, $level = 1) {
		$output .= '<ul class="' . ($level == 1 ? 'odd' : 'even') . '">' . _NL;
		foreach ($navItem->subMenu as $subItem) {
			$output .= '<li ' . ($subItem->subMenu ? ' tabindex="-2" class="hasSubMenu"' : '') . ' id="' . $subItem->id . '"' . 
				($subItem->flyoutAction ? ' onclick="JavaScript:openFlyout(\'' . $subItem->flyoutAction . '\', \'' . $subItem->flyoutTitle . '\');"' : '')  . '>' . 
				($subItem->link ? '<a href="' . $subItem->link . '">' . $subItem->name . '</a>' : $subItem->name);
			if ($subItem->subMenu) {
				$output .= $this->printNavigationItem($subItem, ($level == 1 ? 2 : 1));
			}
			$output .= '</li>' . _NL;
		}
		$output .= '</ul>' . _NL;
		return $output;
	}

	function printSideNavigation() {
		$output = '';
		foreach ($this->mainNavigation as $navItem) {
			if ($navItem->type == 'side') {
				if (!$output) {
					$output = '<div id="navSide">' . _NL;
				}
				$output .= '<div class="navSideGroup">' . ($navItem->name ? '<span>' . $navItem->name . '</span>' : '') . _NL;
				if ($navItem->subMenu) {
					$output .= '<ul>';
					foreach ($navItem->subMenu as $subItem) {
						if ($subItem->type == 'dropdown') {
							$output .= '<li class="dropdown"> ' . $subItem->outputDropdown() . '</li>';
						} else {
							if ($subItem->icon) $this->preloadImages[] = '/images/sidenav/' . $subItem->icon;
							$output .= '<li' .  ($subItem->link ? ' onclick="JavaScript:location.href=\'' . $subItem->link . '\';"' : '') . ($subItem->flyoutAction ? ' onclick="JavaScript:openFlyout(\'' . $subItem->flyoutAction . '\', \'' . $subItem->flyoutTitle . '\');"' : '') . ($subItem->icon ? ' style="background-image: url(\'/images/sidenav/' . $subItem->icon . '\');"' : '') . ($subItem->selected ? ' class="selected"' : '') . '>' . ($subItem->link ? '<a href="' . $subItem->link . '">'  . $subItem->name . '</a>' : $subItem->name) .  '</li>' . _NL;
						}
					}
					$output .= '</ul>' . _NL;
				}
				$output .= '</div>' . _NL;
			}
		}
		if ($output) $output .= '</div>' . _NL;
		return $output;
	}


	function printPage() {
		return $this->printHead() . _NL .  $this->flyout . _NL . $this->printNavigation() . '<div id="mainContainer">' . $this->printSideNavigation() . '<div id="mainBody">' . $this->page  . _NL . '</div></div>' . _NL . $this->printFoot();
	}

	function printFlyoutPage() {
		return $this->page;
	}

	function printHead() {
		$output = '<!DOCTYPE html>
			<html lang="en">
			<head>
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title>' . $this->title . '</title>
				<link rel="stylesheet" type="text/css" href="style.css?' . mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15) . '" />';

		foreach ($this->preloadImages as $img) {
			$output .= '<link rel="preload" as="image" href="' . $img . '">';
		}
		$output .= '	<script type="text/javascript" src="sitetemplate.js?' . mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15). mt_rand(5, 15) . '"></script>
				' . $this->script . '
			</head>
			<body id="body" tabindex="-5">
			<div id="ajaxError"><div id="ajaxErrorInner"><span>Something\'s gone wrong!</span>An error ocurred performing that action. Please try refreshing the page.</div></div>';
		return $output;
	}

	function printfoot() {
		return '</body>' . _NL . '</html>';
	}

	function addContent($content) {
		switch (gettype($content)) {
			case 'object':
				switch (get_class($content)) {
					case 'pageTable': case 'pageForm': case 'infoTip':
						$this->page .= $content->output($this->preloadImages);
						break;

					default:
						$this->page .= '!!!Unable to handle content, type ' . get_class($content);
						break;
				}
				break;
			default:
				$this->page .= $content;
				break;
		}
	}


	function prettyDate($date) {
		//$date should be in timestamp form, Y-m-d H:i:s
		$pastDate = strtotime($date);
		$curDate = time();
		$timeElapsed = $curDate - $pastDate;
		$hours = round($timeElapsed / 3600);
		$days = round($timeElapsed / 86400);
		$weeks = round($timeElapsed / 604800);
		$months = round($timeElapsed / 2600640);
		$years = round($timeElapsed / 31207680);
		if ($years > 0) return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
		if ($months > 0) return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
		if ($weeks > 0) return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
		if ($days > 0) return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
		if ($hours > 0) return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
		return 'Just now';
	}
}


class navigationItem {
	public $id;
	public $name;
	public $subMenu;
	public $position = 'left';	//left, centre, right
	public $type = 'main';		//main, sub, side, dropdown
	public $flyoutAction = '';	//URL to open in flyout on click
	public $flyoutTitle = '';	//Title to show in flyout
	public $link = '';		//URL to open on click, in main window
	public $selected = 0;		//0: not selected, 1: item is selected. Applies to top level menu item only
	public $icon = '';		//image link for side nav item
	public $tooltip;		//tooltiptext
	public $newWindow;		// open link in new window
	public $width;			//use with icon to set fixed width
	public $options;		//use with dropdown only
	public $action;			//use with dropdown only

	function __construct($name, $type='main', $position='left') {
		$this->name = $name;
		$this->position = $position;
		$this->type = $type;
		$this->id = 'm' . uniqid();
	}

	function addItem($subItem) {
		$this->subMenu[] = $subItem;
		return $subItem;
	}

	function outputDropdown() {

		$output = '<div class="dropdown" tabindex="-2"><span onclick="dropdownClose(\'' . $this->id . '\');" id="' . $this->id . ($this->value ? '">' . $this->options[$this->value] : '" class="placeholder">' . $this->name) . '</span><ul id="u' . $this->id . '">';
		foreach ($this->options as $listValue => $listText) {
		        $thisRowID = 'r' . uniqid();
		        $thisLabelID = 'l' . uniqid();
		        $output .= '<li><input ' . ($this->value == $listValue ? 'checked="checked" ' : '') . 'type="radio" class="hidden" name="' . $this->name . '" id="' . $thisRowID . '" value="' . $listValue . '" onclick="JavaScript:dropdownSelected(\'' . $this->id . '\', \'' . $thisLabelID . '\');" onchange="JavaScript:location.href=\'' . str_replace('$VALUE', $listValue, $this->action) . '\';"><label id="' . $thisLabelID . '" for="' . $thisRowID . '">' . $listText . '</label></li>';
		}
		$output .= '</ul></div>';
		return $output;
	}

}

class infoTip {
	public $type;		// info, warning, error
	private $text;

	function __construct($text, $type='info') {
		$this->text = $text;
		$this->type = $type;
	}

	function output(&$preloadImages) {
		$img = '/images/Status' . ucfirst($this->type) . '.png';
		$preloadImages[$img] = $img;
		return '<div class="infotip' . ($this->type == 'warning' || $this->type == 'error' || $this->type == 'info' ? ' ' . $this->type : '') . '"><span>' . $this->text . '</span></div>' . _NL;
	}
}

?>
