<?php
include '../inc/page.php';
if ($_GET['flyout'] == '1') $isFlyout = 1;
$thisPage = new sitePage('Site Template Testbed', '', '1');
$thisPage->logo = '/images/logo.png';
$thisPage->initFlyout();

if ($_GET['action'] == 'echopost') {
	echo '<pre>';
	print_r($_POST);
	echo '</pre>';
	exit;
}
if ($_GET['action'] == 'examine') {
	echo 'Examine item ' . $_GET['id'];
	exit;
}
if ($_GET['action'] == 'create') {
                $createForm = new pageForm('addLink', 'testbed2.php?action=echopost');
		$createForm->method = 'ajax';
                $domainField = $createForm->addField(new pageFormField('domain', 'dropdown'));
                $domainField->placeholder = 'Dropdown Field';
                $domainField->label = 'Domain';
                $domainField->options = array('1' => 'https://k80.cat/', '2' => 'https://aka.ms/', '3' => 'https://bit.ly', '4' => 'https://goo.gl/');
		$domainField->value = '4';
		$domainField->required = 1;
                $slugField = $createForm->addField(new pageFormField('slug', 'bigtext'));
                $slugField->label = 'Short Link';
                $slugField->placeholder = 'link';
		$slugField->required = 1;
                $linkField = $createForm->addField(new pageFormField('created', 'date'));
                $linkField->label = 'Date';
		$linkField->required = 1;
		$toggleField = $createForm->addField(new pageFormField('toggle', 'toggle'));
		$toggleField->label = 'Toggle the thing';
		$toggleField->value = 1;
		$linkField = $createForm->addField(new pageFormField('link', 'link'));
		$linkField->label = 'The link thing';
		$linkField->value = 'Clicky';
		$linkField->link = 'index.php';
                $listField = $createForm->addField(new pageFormField('list', 'list'));
                $listField->label = 'list';
                $listField->options = array('1' => 'Item 1', '2' => 'Item 2', '3' => 'Item 3', '4' => 'Item 4', '5' => 'Item 5', '6' => 'Item 6 is a longer item, which is quite long');
		$listField->disabled = 1;
                $textField = $createForm->addField(new pageFormField('text', 'text'));
		$textField->help = 'This is the help text for this field. "in quotes" Yummy.';
                $textField->label = 'Text field';
                $textField->placeholder = 'Another text field';
                $list2Field = $createForm->addField(new pageFormField('list2', 'list'));
                $list2Field->label = 'list with multiselect';
		$list2Field->multiselect = 1;
                $list2Field->options = array('1' => 'Item 1', '2' => 'Item 2', '3' => 'Item 3', '4' => 'Item 4', '5' => 'Item 5', '6' => 'Item 6 is a longer item, which is quite long');
		$list2Field->required = 1;
                $submitButton = $createForm->addField(new pageFormField('save', 'submit'));
                $submitButton->value = 'Create';
                $thisPage->addContent($createForm);
                echo $thisPage->printFlyoutPage();
                exit;
}

$createButton = new navigationItem('Flyout Test', 'sub');
$createButton->flyoutAction = 'testbed2.php?action=create&flyout=1';
$createButton->flyoutTitle = 'Flyout Form Sample';
$thisPage->addNavigation($createButton);

$bigLinkButton = new navigationItem('Item', 'sub');
$bigLinkButton2 = new navigationItem('Click me', 'sub');
$bigLinkButton3 = new navigationItem('Item', 'sub');
$bigLinkButton4 = new navigationItem('Item', 'sub');
$bigLinkButton5 = new navigationItem('Item', 'sub');
$bigLinkButton6 = new navigationItem('Item', 'sub');
$bigLinkButton7 = new navigationItem('Item', 'sub');
$bigLinkButton8 = new navigationItem('Item', 'sub');

$bigLinkButton2->link = '#clicked';
$bigLinkButton->addItem($bigLinkButton2);
$bigLinkButton->addItem($bigLinkButton4);
$bigLinkButton->addItem($bigLinkButton5);
$bigLinkButton4->addItem($bigLinkButton3);
$bigLinkButton3->addItem($bigLinkButton6);
$bigLinkButton3->addItem($bigLinkButton7);
$bigLinkButton6->addItem($bigLinkButton8);

$thisPage->addNavigation($bigLinkButton);

$thingBtn = new navigationItem('Prev', 'sub', 'right');
$thingBtn->link = 'testbed.php';
$thingBtn->selected = 1;
$thisPage->addNavigation($thingBtn);



$sideNav1 = new navigationItem('', 'side');
$action1 = new navigationItem('New thing', 'side');
$action1->flyoutAction = 'testbed2.php?action=create&flyout=1';
$action1->flyoutTitle = 'Flyout Form Sample';
$action1->icon = 'new.png';
$sideNav1->addItem($action1);
$action2 = new navigationItem('Other  thing', 'side');
$action2->link = '#other';
$action2->icon = 'other.png';
$sideNav1->addItem($action2);

$sideNav2 = new navigationItem('Header 2', 'side');
$action3 = new navigationItem('New thing', 'side');
$action3->link = '#new';
$action3->icon = 'new.png';
$sideNav2->addItem($action3);
$action4 = new navigationItem('Other  thing', 'side');
$action4->link = '#other';
$action4->icon = 'other.png';
$sideNav2->addItem($action4);

$thisPage->addNavigation($sideNav1);
$thisPage->addNavigation($sideNav2);

 $urlTable = new pageTable();
        $urlTable->addColumn(new pageTableColumn('Number'));
        $urlTable->addColumn(new pageTableColumn('Thing2'));
$urlTable->pages = 1;
$urlTable->pageURL = 'testbed2.php';
$urlTable->pageCount = '20';
$urlTable->page = ($_GET['page'] ? $_GET['page'] : '1');

$tableMenu = new pageTableMenu();
$del2 = $tableMenu->addItem(new pageTableMenuItem('Delete', 'testbed2.php?delete$ID'));
$del2->icon = 'Delete.png';
$del2->newWindow = 1;
$del = $tableMenu->addItem(new pageTableMenuItem('Delete Confirm', 'testbed2.php?delete$ID'));
$del->icon = 'Delete.png';
$del->confirm = 'Really delete $ID?';
$item = $tableMenu->addItem(new pageTableMenuItem('Expand', ''));
$item->flyoutAction = 'testbed2.php?action=examine&id=$ID';
$item->flyoutTitle = '$NAME';


	for ($i = 1; $i < 20; $i++) {
                $tableRow = $urlTable->addRow();
		if ($i < 8 || $i > 12) {
			$tableRow->menu = $tableMenu;
			$tableRow->name = 'This hasn\'t an apostrophe, honest!';
			$tableRow->linkID = $i;
		}
		$tableRow->icon = 'StatusGood.png';
		if ($i == 5 || $i == 6 || $i == 18) $tableRow->icon = 'StatusWarning.png';
		if ($i == 7 || $i == 14 || $i == 15) $tableRow->icon = 'StatusError.png';
                $tableRow->column['Number']->text = $i;
		$tableRow->column['Number']->flyoutAction = 'testbed2.php?action=examine&id=' . $i;
		$tableRow->column['Number']->flyoutTitle = 'Item ' . $i;
		$tableRow->column['Thing2']->text = 'A row in a table ' . $i . ' a long row in a long row in a long row in a long';

	}

	$urlTable->sort('desc', 'Number');

	$thisPage->addContent(new infoTip('This is an info tip'));
	$thisPage->addContent(new infoTip('This is a warning tip', 'warning'));
	$thisPage->addContent(new infoTip('This is an error tip', 'error'));

        $thisPage->addContent($urlTable);








echo $thisPage->printPage();


?>
