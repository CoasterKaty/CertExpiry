<?php
/*
Site Template page builder class
Katy Nicholson
https://katystech.blog/
*/

/*TO DO list:

- Forms:	Disabled elements - expand to cover all fields rather than just text
		Dropdown and lists: If you set required="required" it requires every element to be selected at once
		Dropdown and lists: Scroll bar for large lists
		List: Allow unselect of item in single select list
		Form validation - highlight missed/incorrect fields in red
		Form validation - don't enable button until validation passes (different style for disabled buttons)
- Table:	ISSUE: paging doesn't work if we use the pageTable class's sort routine, as we are sorting only the data on screen

*/



class pageForm {
	public $action;
	public $name;
	public $fields;
	public $method = 'post';		//post or ajax. post acts as a normal form, reloading the entire page. ajax handles it in ajax with no page reload.
	public $id;
	function __construct($name, $action) {
		$this->name = $name;
		$this->action = $action;
		$this->id = uniqid();
	}

	function addField($field) {
		$this->fields[] = $field;
		return $field;
	}

	function output(&$preloadImages) {
		$output = '<form action="' . $this->action . '" name="' . $this->name . '" method="POST" onsubmit="return submitForm(\'' . $this->id . '\');" data-method="' . $this->method . '" id="' . $this->id . '">' . _NL;
		$output .= '<input type="hidden" name="httpReferer" value="' . urlencode($_SERVER['HTTP_REFERER']) . '">';
		foreach ($this->fields as $field) {
			$output .= $field->output($preloadImages);
		}
		$output .= '</form>' . _NL;
		return $output;
	}
}

class pageFormField {
	public $id;
	public $type;
	public $name;
	public $link;
	public $value;
	public $placeholder;
	public $label;
	public $options;
	public $multiselect;	//list only, 1 = yes
	public $required;
	public $height;		//bigtext only
	public $min;		//minimum value, numeric or date fields
	public $max;		//maximum value, numeric or date fields
	public $maxLength;	//maxlength textarea
	public $disabled;
	public $help;		//help text

	function __construct($name, $type) {
		$this->name = $name;
		$this->type = $type;
		$this->id = 'f' . uniqid();
	}

	function output(&$preloadImages) {
		$output = '';
		$required = ($this->required ? '<span class="required" title="This field is required">*</span>' : '');
		$help = ($this->help ? '<span class="help" title="' . htmlentities($this->help) . '"></span>' : '');
		// for dropdown and list, $this->required needs some JS maybe, as just putting "required" in the input means all of them have to be checked/selected.
		switch ($this->type) {
			case 'link':
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label . $help . '</label>' : '') . '<a href="' . $this->link . '" target="_blank">' . $this->value . '</a>';
				break;
			case 'text':
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label . $help . '</label>' : '') . '<input ' . ($this->maxLength ? 'maxlength="' . $this->maxLength . '" ' : '') . ($this->disabled ? 'disabled="disabled" ' : '') . ($this->required ? 'required="required" ' : '') . 'id="' . $this->id . '" type="text" name="' . $this->name . '" value="' . $this->value . '" placeholder="' . $this->placeholder . '" onchange="JavaScript:setUnsavedFlyout();"/>';
				break;
			case 'number':
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label . $help . '</label>' : '') . '<input ' . ($this->disabled ? 'disabled="disabled" ' : '') . ($this->min ? 'min="' . $this->min . '" ' : '') .  ($this->max ? 'max="' . $this->max . '" ' : '') . ($this->required ? 'required ' : '') . 'id="' . $this->id . '" type="number" name="' . $this->name . '" value="' . $this->value . '" placeholder="' . $this->placeholder . '" onchange="JavaScript:setUnsavedFlyout();" />';
				break;
			case 'bigtext': case 'textarea':
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label . $help . '</label>' : '') . '<textarea ' . ($this->maxLength ? 'maxlength="' . $this->maxLength . '" ' : '') . 'style="height: ' . ($this->height ? $this->height : '90') . 'px;" ' . ($this->disabled ? 'disabled="disabled" ' : '') . ($this->required ? 'required ' : '') . 'id="' . $this->id . '" type="text" name="' . $this->name . '"  placeholder="' . $this->placeholder . '" onchange="JavaScript:setUnsavedFlyout();"/>' . $this->value . '</textarea>';
				break;
			case 'date':
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label . $help . '</label>' : '') . '<input ' . ($this->disabled ? 'disabled="disabled" ' : '') . ($this->required ? 'required ' : '') . 'id="' . $this->id . '" type="date" name="' . $this->name . '" value="' . $this->value . '" onchange="JavaScript:setUnsavedFlyout();"' . ($this->min ? 'min="' . $this->min . '" ' : '') .  ($this->max ? 'max="' . $this->max . '" ' : '') . ' />';
				break;
			case 'button':
				$output .= '<input type="button" name="' . $this->name . '" value="' . $this->value . '" />';
				break;
			case 'submit':
				$output .= '<input type="submit" name="' . $this->name . '" value="' . $this->value . '" />';
				break;
			case 'toggle':
				$output .= '<label for="chk' . $this->id . '">' . $required . $this->label . $help . '</label>'; //label shouldn't switch the toggle, so set for= to something that doesn't exist
				$output .= '<input type="checkbox" class="hidden toggle" ' . ($this->disabled ? 'disabled="disabled" ' : '') . ($this->value ? 'checked="checked" ' : '') . 'name="' . $this->name . '" id="' . $this->id . '" value="1">';
				$output .= '<label class="toggle" for="' . $this->id . '"><span class="toggle"></span></label>';
				break;
			case 'list':
				// $this->options should be an array of value=>text, e.g. value="item1" text="This is a thing that clicks stuff"
				if (!$this->label) $this->label = 'List';
				$outputTable = new pageTable();
				$headerRow = $outputTable->addColumn(new pageTableColumn($this->label));
				$outputTable->skipHeaderRow = 1;
				$output .= ($this->label ? '<label for="' . $this->id . '">' . $required . $this->label .  $help .'</label>' : '');
				foreach ($this->options as $listValue => $listText) {
					$tableRow = $outputTable->addRow();
					$thisRowID = 'r' . uniqid();
					if ($this->disabled) {
						$tableRow->column[$this->label]->text = '<label class="list" for="' . $thisRowID . '">' . $listText . '</label>';
					} else {
						if ($this->multiselect) {
							//TO DO: Work out how to pass all the current values for a multiselect list
							$tableRow->column[$this->label]->text = '<input onchange="JavaScript:setUnsavedFlyout();" type="checkbox" class="hidden" name="' . $this->name . '" id="' . $thisRowID . '" value="' . $listValue . '" /><label class="list" for="' . $thisRowID . '">' . $listText . '</label>';
						} else {
							$tableRow->column[$this->label]->text = '<input ' . ($this->value == $listValue ? 'checked="checked" ' : '') . 'onchange="JavaScript:setUnsavedFlyout();" type="radio" class="hidden" name="' . $this->name . '" id="' . $thisRowID . '" value="' . $listValue . '" /><label class="list" for="' . $thisRowID . '">' . $listText . '</label>';
						}
					}
					$tableRow->column[$this->label]->value = $listValue;
				}
				$output .= $outputTable->output($preloadImages);
				break;
			case 'dropdown':
				if (!$this->label) $this->label = 'Dropdown';
				$output .= '<label for="' . $this->id . '">' . $required . $this->label .  $help .'</label>' . '<div class="dropdown" tabindex="-2"><span onclick="dropdownClose(\'' . $this->id . '\');" id="' . $this->id . ($this->value ? '">' . $this->options[$this->value] : '" class="placeholder">' . $this->placeholder) . '</span><ul id="u' . $this->id . '">';
				foreach ($this->options as $listValue => $listText) {
					$thisRowID = 'r' . uniqid();
					$thisLabelID = 'l' . uniqid();
					$output .= '<li><input ' . ($this->value == $listValue ? 'checked="checked" ' : '') . 'type="radio" class="hidden" name="' . $this->name . '" id="' . $thisRowID . '" value="' . $listValue . '" onclick="JavaScript:dropdownSelected(\'' . $this->id . '\', \'' . $thisLabelID . '\');"><label id="' . $thisLabelID . '" for="' . $thisRowID . '">' . $listText . '</label></li>';
				}
				$output .= '</ul></div>';
				break;
			default:
				$output .= 'Unknown Field type: ' . $this->type;
				break;
		}
		return $output . _NL;

	}

}
?>
