<?php
class pageMarkdown {
	public $meta;
	public $content;
	public $sidebarContent;
	public $topBar;
	private $mode = 'body';
	private $prevLineEmpty = 0;
	private $prevLineType = '';
	function __construct($markdown) {
		$this->parse($markdown);
	}

	function sidebar($inThisPost = 'yes') {

		if ($inThisPost != 'no') {
			$sidebar = '<h3>In this post:</h3><ul>';
			$sidebar .= '<li><a href="#top">Introduction</a></li>';
			foreach ($this->content as $block) {
			        if ($block->type == 'h2') {
			                $sidebar .= '<li><a href="#' . $block->id . '">' . $block->data . '</a></li>';
			        }
			}
			$sidebar .= '</ul>';
		}
		$sidebar .= $this->sidebarContent;
		return $sidebar;

	}

	function parse($markdown) {
		$markdown = preg_replace_callback('/\[\!INCLUDE\]\((.+?)\)/', function ($matches) {
				return  file_get_contents('../content' . $matches[1]);
			},
			$markdown);

		$markdown = preg_replace_callback('/```((.|\v)+?)```/', function($matches) { 
			return '<pre class="code">' . htmlentities($matches[1]) . '</pre>';
		}, $markdown);


		$data = preg_split('/\r\n|\r|\n/', $markdown);
		$table;
		foreach ($data as $line) {
			$this->parseLine($line, $table);
		}
	}
	function closeList() {
		if ($this->mode == 'ol') {
			$this->content[] = new pageMDBlock('/ol');
			$this->mode = 'body';
		}
		if ($this->mode == 'ul') {
			 $this->content[] = new pageMDBlock('/ul');
			$this->mode = 'body';
		}

	}
	function parseLine($line, &$table) {
		if (strlen($line) > 0) {
			if ($space = strpos($line, ' ')) {
				$type = substr($line, 0, $space);
				$line = substr($line, $space);
			} else {
				$type = rtrim($line);
				$line = '';
			}
			if ($type != '|' && $this->prevLineType == '|') {
				$this->content[] = new pageMDBlock('table', $table);
				$table = array();
			}
			switch ($type) {
				case '---':
					$this->mode = ($this->mode == 'meta' ? 'body' : 'meta');
					break;
				case '#':
					$this->closeList();
					$this->content[] = new pageMDBlock('h1', $line);
					$this->mode = 'body';
					break;
				case '##':
					$this->closeList();
					$this->content[] = new pageMDBlock('h2', $this->parseInLine($line));
					$this->mode = 'body';
					break;
				case '###':
					$this->closeList();
					$this->content[] = new pageMDBlock('h3', $line);
					$this->mode = 'body';
					break;
				case '####':
					$this->closeList();
					$this->content[] = new pageMDBlock('h4', $line);
					$this->mode = 'body';
					break;
				case '|':
					/*
| Syntax      | Description | Test Text     |
| :---        |    :----:   |          ---: |
| Header      | Title       | Here's this   |
| Paragraph   | Text        | And more      |
					*/

					$row = explode('|', rtrim($line, '|'));

					if (!$table) {
						$table['headings'] = $row;
					} else {
						if ($table['align']) $table['data'][] = $row;
						// look for alignment
						foreach ($row as $cell) {
							if (preg_match('/:-{3,}:/', $cell)) {
								$table['align'][] = 'center';
							}
							if (preg_match('/:-{3,}(?!:)\s/', $cell)) {
								$table['align'][] = 'left';
							}
							if (preg_match('/\s(?<!:)-{3,}:/', $cell)) {
								$table['align'][] = 'right';
							}
							if (preg_match('/\s-{3,}\s/', $cell)) {
								$table['align'][] = 'left';
							}
						}
					}
					break;
				case '1.':
					/*
					Recode this to cope with nested lists.
					Position of first character denotes which level it lives at e.g.

					1. Thing
					   Another block within the above <li>
					1. Thing2
					1. Thing3
					   1. Thing which will be a child of Thing3
					   1. Thing which will also be a child of Thing3

					but also increasing number is valid
					*/
					if ($this->mode == 'olli') {
						$this->content[] = new pageMDBlock('/li');
						$this->mode == 'ol';
					}
					if ($this->mode != 'ol') {
						$this->content[] = new pageMDBlock('ol');
						$this->mode = 'ol';
					}
					$this->content[] = new pageMDBlock('li', $this->parseInLine($line));
					break;
				case '-':
					if ($this->mode == 'ulli') {
						$this->content[] = new pageMDBlock('/li');
						$this->mode == 'ul';
					}
					if ($this->mode != 'ul') {
						$this->content[] = new pageMDBlock('ul');
						$this->mode = 'ul';
					}
					$this->content[] = new pageMDBlock('li', $this->parseInLine($line));
					break;
				default:
					switch ($this->mode) {
						case 'meta':
							$metaEl = substr($type, 0, strlen($type)-1);
							$metaVal = trim($line);
							if (substr($metaVal, 0, 1) == '"' && substr($metaVal, -1) == '"') {
								$metaVal = substr($metaVal, 1, strlen($metaVal)-2);
							}
							$this->meta[$metaEl] = $metaVal;
							break;
						default:
							if ($this->prevLineEmpty) $this->closeList();
							// we need to cope with \n\n = <p> and \n = <br> somehow
							$this->content[] = new pageMDBlock(($this->prevLineEmpty ? 'p' : 'br'), $this->parseInLine($type . $line));
//							$this->content[] = new pageMDBlock('p', $type . $line);
							break;
					}
					break;
			}
			$this->prevLineEmpty = 0;
			$this->prevLineType = $type;
		} else {
			$this->prevLineEmpty = 1;
		}
	}

	function parseInLine($line) {
		//look for in line code like **this** or [This](link.php)

		$line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
		$line = preg_replace('/(?<![\\\\])\*(.+?)\*/', '<em>$1</em>', $line);
//		$line = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $line);
		$line = preg_replace('/```(.+?)```/', '<pre>$1</pre>', $line);
		$line = preg_replace('/`(.+?)`/', '<span class="code">$1</span>', $line);
		$line = preg_replace('/(?<![\\\\])\_(.+?)\_/', '<u>$1</u>', $line);
		$line = preg_replace('/[\\\\]\_/', '_', $line);
		$line = preg_replace('/[\\\\]\*/', '*', $line);


		$line = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $line);
		if (preg_match('/:::image(.+?):::/', $line, $matches)) {
			$image = $matches[1];
			// type="content" source="media/access-education-hub/browsing-in-private.png" alt-text="Open a private browser window." border="false" size="100x200"
			if (preg_match_all('/([a-zA-Z]*)=\"(.*?)(?<![\\\\])\"/', $image, $attribs)) {
				$thisImage = new pageMDBlock('img');
				foreach ($attribs[1] as $index => $attribT) {
					$thisImage->attribs[$attribT] = htmlentities($this->unescapeString($attribs[2][$index]));
				}
				if (!$thisImage->attribs['align']) $thisImage->attribs['align'] = 'centre';
				$line = $thisImage->output();
			}
		}

		$line = $this->unescapeString($line);
		return $line;
	}
	function unescapeString($line) {
		$line = preg_replace('/[\\\\]\"/', '"', $line);
		$line = preg_replace('/[\\\\]\[/', '[', $line);
		$line = preg_replace('/[\\\\]\]/', ']', $line);

		return $line;
	}
}

class pageMDBlock {
	public $type;
	public $data;
	public $attribs;
	public $id;

	function __construct($type, $data = '') {
		$this->type = $type;
		$this->data = $data;
		$this->id = uniqid();
	}
	function output() {
		switch ($this->type) {
			case 'h1':
				return '<h1>' . $this->data . '</h1>';
			case 'h2':
				return '<h2><a name="' . $this->id . '"></a>' . $this->data . '</h2>';
//				return '<h2><a name="' . $this->id . '">' . $this->data . '</a></h2>';
			case 'h3':
				return '<h3>' . $this->data . '</h3>';
			case 'h4':
				return '<h4>' . $this->data . '</h4>';
			case 'p':
				return '<p>' . $this->data;
			case '/p':
				return '</p>';
			case 'ol':
				return '<ol>';
			case '/ol':
				return '</ol>';
			case 'ul':
				return '<ul>';
			case '/ul':
				return '</ul>';
			case 'li':
				return '<li>' . $this->data;
			case '/li':
				return '</li>';
			case 'img':
				return '<figure' . ($this->attribs['size'] ? ' style="width: ' . (explode('x', $this->attribs['size']))[0] . 'px;"' : '') . 
					' class="' . ($this->attribs['align'] ?  $this->attribs['align'] : '') . '">' . 
					($this->attribs['expand'] == 'yes' ? '<a href="' . $this->attribs['source'] . '" target="_blank">' : '') . 
					'<img src="' . $this->attribs['source'] . '"' . 
					($this->attribs['text'] ? ' alt="' . $this->attribs['text'] . '" title="' . $this->attribs['text'] . '"' : '') . 
					($this->attribs['size'] ? ' style="width: ' . (explode('x', $this->attribs['size']))[0] . 'px;' . 
					(explode('x', $this->attribs['size'])[1] ? ' height: ' . (explode('x', $this->attribs['size']))[1] . 'px;' : '') . '"' : '') . 
					' />' . ($this->attribs['expand'] == 'yes' ? '</a>' : '') . '<figcaption>' . $this->attribs['caption'] . '</figcaption></figure>'; 
			case 'br':
				return '<br />' . $this->data;
			case 'table':
				/* arr['headings'], ['align'], ['data'][x] */
				$toRet = '<table>';
				$toRet .= '<tr>';
				foreach ($this->data['headings'] as $index => $heading) {
					$toRet .= '<th class="' . $this->data['align'][$index] . '">' . trim($heading) . '</th>';
				}
				$toRet .= '</tr>';
				foreach ($this->data['data'] as $row) {
					$toRet .= '<tr>';
					foreach ($row as $index => $cell) {
						$toRet .= '<td class="' . $this->data['align'][$index] . '">' . trim($cell) . '</td>';
					}
					$toRet .= '</tr>';
				}
				$toRet .= '</table>';

				return $toRet;
		}
	}
}

?>
