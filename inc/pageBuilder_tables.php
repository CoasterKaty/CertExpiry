<?php
/*
Site Template page builder class
Katy Nicholson
https://katystech.blog/
*/

class pageTable {
	private $columns;
	private $rows;
	private $hasIcons;
	private $sortOrder;
	public $skipHeaderRow;	//1 to skip output of header row
	public $pages;		//1 to enable pages
	public $pageSize = 37;	//number of rows on page - note this won't trim table contents, you must pass the correct amount when building the table
	public $page = 1;	//current page
	public $pageCount = 1;	//total page count
	public $pageURL;	//URL to be used in the links between pages

	function __construct() {
	}


        function buildPageNavigation() {

		$omitFullNav = 0;
		if ($this->pageCount >= 10) {
			$omitFullNav = 1;
			$startPage = $this->page - 5;
			$endPage = $this->page + 5;
			if ($endPage > $this->pageCount) {
				$startPage += ($this->pageCount - $endPage);
				$endPage = $this->pageCount;
			}
			if ($startPage < 1) {
				$endPage += (-$startPage) + 1;
				$startPage = 1;
			}
		} else {
			$startPage = 1;
			$endPage = $this->pageCount;
		}
                $pageString = '<span>' . ($this->page == 1 ? '' :'<a class="pageNavItem" href="' . $this->pageURL . (strpos($this->pageURL, '?') ? '&' : '?') . 'page=' . ($this->page - 1) . '">') . '<span class="pageNavItem">Prev</span>' . ($this->page == 1? '' : '</a>') . '</span>';
                for ($page = $startPage; $page <= $endPage; $page++) {
                        $pageString .= '<span>' . ($page == $this->page ? '<span class="pageNavItem">' . $page . '</span>' : '<a class="pageNavItem" href="' . $this->pageURL . (strpos($this->pageURL, '?') ? '&' : '?') . 'page=' . $page . '">' . $page . '</a>') . '</span>';
                }
                $pageString .= '<span>' . ($this->page == $this->pageCount ? '<span class="pageNavItem">Next</span>' :'<a class="pageNavItem" href="' . $this->pageURL . (strpos($this->pageURL, '?') ? '&' : '?') . 'page=' . ($this->page + 1) . '">Next</a>') . '</span>';

                return $pageString;
        }

	function sort($sortDirection = 'asc', $sortColumn) {
		foreach ($this->rows as $row) {
			foreach ($row->column as $column => $data) {
				if ($sortColumn == $column) {
					$toSort[$row->id] = $data->text;
				}
			}
		}
		if ($toSort) {
			if ($sortDirection == 'asc') asort($toSort, SORT_NATURAL);
			if ($sortDirection == 'desc') arsort($toSort, SORT_NATURAL);
		}
		$this->sortOrder = $toSort;

	}

	function addColumn($column) {
		$this->columns[] = $column;
		return $column;
	}

	function addRow() {
		$newRow = new pageTableRow($this->columns);
		$this->rows[] = $newRow;
		return $newRow;
	}

	function rowCount() {
		try {
			return count($this->rows);
		} catch (Exception $e) {
			return 0;
		}
	}

	function getColumn($colName) {
		foreach ($this->columns as $column) {
			if ($column->name == $colName) return $column;
		}
		return new pageTableColumn();
	}

	function output(&$preloadImages) {
		foreach ($this->rows as $row) {
			if ($row->icon) $this->hasIcons = 1;
			if ($row->menu) {
				$hasMenu = 1;
				$preloadImages['Menu.png'] = '/images/Menu.png';
				$preloadImages['MenuHot.png'] = '/images/MenuHot.png';
			}
		}
		$output = '<div class="table">' . _NL;
		if (!$this->skipHeaderRow) {
			$output .= '<div class="head">' . _NL . '<div class="row">' . _NL;
			foreach ($this->columns as $column) {
				if (!$column->hidden) $output .= '<div class="cell' . ($this->hasIcons ? ' cellicon' : '') . (!$column->resize ? ' noresize' : '') . '" ' . ($column->width ? ' style="width: ' . $column->width . 'px;"' : '') . '>' . $column->text . '</div>' . _NL;
			}
			if ($hasMenu) $output .= '<div class="cell noresize"></div>';
			$output .= '</div>' . _NL . '</div>' . _NL;
		}
		$output .= '<div class="body">' . _NL;
		foreach ($this->rows as $row) {
			if ($row->icon) $preloadImages[$row->icon] = '/images/' . $row->icon;
			$rowOutput[$row->id] = '<div class="row"' . ($row->icon ? ' style="background-image: url(\'/images/' . $row->icon . '\');"' : '') . '>' . _NL;
			foreach ($row->column as $column => $data) {
				$thisColumn = $this->getColumn($column);
				if (!$thisColumn->hidden) {
					// setting max-width on the cell allows it to resize to smaller than "fitting the content".
					$rowOutput[$row->id] .= '<div ' . 
					($data->flyoutAction ? 'onclick="JavaScript: openFlyout(\'' . $data->flyoutAction . '\', \'' . str_replace('\'', '\\\'', $data->flyoutTitle) . '\');" ' : '') . 'title="' . $data->tooltip . '" class="cell' . ($this->hasIcons ? ' cellicon' : '') . 
					($data->flyoutAction ? ' link' : '') . '" ' . 
					($thisColumn->width && !$thisColumn->resize ? ' style="max-width: ' . $thisColumn->width  . 'px;"' : '') .
					((($thisColumn->width && $thisColumn->resize) || (!$thisColumn->width && $thisColumn->resize)) ? ' style="max-width: 100px;"' : '') . '>' .
					($data->link ? '<a href="' . $data->link . '" target="_blank">' . $data->text . '</a>' : $data->text) .  '</div>' . _NL;
				}
			}
			if ($row->menu) {
				$instanceID = uniqid();
				$rowOutput[$row->id] .= '<div class="menu" tabindex="-5" onclick="JavaScript:positionTableMenu(\'' . $instanceID . '\');">' . $row->menu->output($row, $instanceID, $preloadImages) . '</div>';
			}
			if (!$row->menu && $hasMenu) {
				// If there is a menu in this table, but not this row, add a cell so the table lines up
				$rowOutput[$row->id] .= '<div class="cell"></div>';
			}
			$rowOutput[$row->id] .= '</div>' . _NL;
		}
		if ($this->sortOrder) {
			foreach ($this->sortOrder as $rowID => $val) {
				$output .= $rowOutput[$rowID];
			}
		} else {
			foreach ($rowOutput as $row) {
				$output .= $row;
			}
		}
		$output .= '</div></div>' . _NL;
		if ($this->pages) {
			$output .= '<div class="tableNavigation">' . $this->buildPageNavigation() . '</div>';
		}
		return $output;

	}
}
class pageTableRow {
	public $column;
	public $icon;
	public $id;
	public $linkID;		// for use with pageTableMenu, ID of item to insert into action URL for menu items
	public $name;		// used with pageTableMenu, name of item for flyout title
	public $menu;		// contains a pageTableMenu if set
	public $attr1;		//used with links etc
	public $attr2;
	public $attr3;

	function __construct($columns) {
		foreach ($columns as $column) {
			$this->column[$column->name] = new pageTableCell();
		}
		$this->id = 'r' . uniqid();
	}
	function output() {
	}
}

class pageTableCell {
	public $text;
	public $value;
	public $tooltip;
	public $link;
	public $flyoutAction;
	public $flyoutTitle;
}

class pageTableColumn {
	public $name;
	public $text;
	public $width;
	public $hidden;
	public $resize = 1;
	function __construct($name, $width = '') {
		$this->name = $name;
		$this->text = $name;
		$this->width = $width;
	}
}

class pageTableMenu {
	public $items;

	function addItem($newItem) {
		$this->items[] = $newItem;
		return $newItem;
	}
	function output($tableRow, $instanceID, &$preloadImages) {
		$output = '<ul id="' . $instanceID . '">';
		foreach ($this->items as $item) {
			if ($item->flyoutTitle) {
				$flyoutTitle = str_replace('$NAME', $tableRow->name, $item->flyoutTitle);
				$flyoutTitle = str_replace('$ID', $tableRow->linkID, $flyoutTitle);
				$flyoutTitle = str_replace('$ATTR1', $tableRow->attr1, $flyoutTitle);
				$flyoutTitle = str_replace('$ATTR2', $tableRow->attr2, $flyoutTitle);
				$flyoutTitle = str_replace('$ATTR3', $tableRow->attr3, $flyoutTitle);
				$flyoutTitle = str_replace('\'', '\\\'', $flyoutTitle);
			}
			if ($item->flyoutAction) {
				$flyoutAction = str_replace('$NAME', $tableRow->name, $item->flyoutAction);
				$flyoutAction = str_replace('$ID', $tableRow->linkID, $flyoutAction);
				$flyoutAction = str_replace('$ATTR1', $tableRow->attr1, $flyoutAction);
				$flyoutAction = str_replace('$ATTR2', $tableRow->attr2, $flyoutAction);
				$flyoutAction = str_replace('$ATTR3', $tableRow->attr3, $flyoutAction);
			}
			if ($item->link) {
				$link = str_replace('$ID', $tableRow->linkID, $item->link);
				$link = str_replace('$NAME', $tableRow->name, $link);
				$link = str_replace('$ATTR1', $tableRow->attr1, $link);
				$link = str_replace('$ATTR2', $tableRow->attr2, $link);
				$link = str_replace('$ATTR3', $tableRow->attr3, $link);
			}
			if ($item->confirm) {
				$confirm = str_replace('$ID', $tableRow->linkID, $item->confirm);
				$confirm = str_replace('$NAME', $tableRow->name, $confirm);
				$confirm = str_replace('$ATTR1', $tableRow->attr1, $confirm);
				$confirm = str_replace('$ATTR2', $tableRow->attr2, $confirm);
				$confirm = str_replace('$ATTR3', $tableRow->attr3, $confirm);
				$confirm = str_replace('\'', '\\\'', $confirm);
			}
			if ($item->icon) $preloadImages[$item->icon] = '/images/' . $item->icon;
			if ($item->link) {
				$output .= '<li onclick="';
				if ($item->newWindow) {
					$output .= 'window.open(\'' . $link . '\'); document.getElementById(\'body\').focus();';
				} else {
					if ($item->confirm) {
						$output .= 'document.getElementById(\'body\').focus(); if (confirm(\'' . $confirm . '\')) {';
					}
					$output .= 'location.href=\'' . $link . '\';';
					if ($item->confirm) $output .= ' }';
				}
				$output .= '"' . ($item->icon ? ' style="background-image: url(\'/images/' . $item->icon . '\');"' : '') . '>' . $item->name . '</li>';
			}
			if ($item->flyoutAction) $output .= '<li onclick="JavaScript:openFlyout(\'' . $flyoutAction . '\', \'' . $flyoutTitle . '\');"' . ($item->icon ? ' style="background-image: url(\'/images/' . $item->icon . '\');"' : '') . '>' . $item->name . '</li>';
		}
		$output .= '</ul>';
		return $output;
	}
}
class pageTableMenuItem {
	public $name;
	public $icon;
	public $newWindow;		// open link in new window
	public $confirm;		// text message to show in confirm() prompt
	public $link;			// link = URL to go to, optional
	public $flyoutAction;		// flyoutAction = URL to open in flyout, optional
	public $flyoutTitle;		// flyoutTitle = title to display

	function __construct($name, $link = '') {
		$this->name = $name;
		$this->link = $link;
	}
}

?>
