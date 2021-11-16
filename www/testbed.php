<?php
include '../inc/page.php';
if ($_GET['flyout'] == '1') $isFlyout = 1;
$thisPage = new sitePage('Site Template Testbed', '', '1');
if (!$isFlyout) {
        $thisPage->logo = '/images/redirecttool_logo.png';
        $thisPage->initFlyout();
}

if ($_GET['action'] == 'create') {
                $createForm = new pageForm('addLink', 'testbed.php?action=addLink&submitted=1');
                $domainField = $createForm->addField(new pageFormField('domain', 'dropdown'));
                $domainField->placeholder = 'Dropdown Field';
                $domainField->label = 'Domain';
                $domainField->options = array('1' => 'https://k80.cat/', '2' => 'https://aka.ms/', '3' => 'https://bit.ly', '4' => 'https://goo.gl/');
		$domainField->required = 1;
                $slugField = $createForm->addField(new pageFormField('slug', 'text'));
                $slugField->label = 'Short Link';
                $slugField->placeholder = 'link';
		$slugField->required = 1;
                $linkField = $createForm->addField(new pageFormField('url', 'text'));
                $linkField->label = 'Redirect to';
                $linkField->placeholder = 'https://long/url/here.txt';
		$linkField->required = 1;
                $listField = $createForm->addField(new pageFormField('list', 'list'));
                $listField->label = 'list';
                $listField->options = array('1' => 'Item 1', '2' => 'Item 2', '3' => 'Item 3', '4' => 'Item 4', '5' => 'Item 5', '6' => 'Item 6 is a longer item, which is quite long');
                $textField = $createForm->addField(new pageFormField('text', 'text'));
                $textField->label = 'Text field';
                $textField->placeholder = 'Another text field';
                $list2Field = $createForm->addField(new pageFormField('list', 'list'));
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

$thisPage->addNavigation(new navigationItem('Link', 'main', 'right'));
$createButton = new navigationItem('Test', 'sub');
$createButton->flyoutAction = 'testbed.php?action=create&flyout=1';
$createButton->flyoutTitle = 'Flyout Form Sample';
$thisPage->addNavigation($createButton);

$bigLinkButton = new navigationItem('Item', 'sub');
$bigLinkButton2 = new navigationItem('Item', 'sub');
$bigLinkButton3 = new navigationItem('Item', 'sub');
$bigLinkButton4 = new navigationItem('Item', 'sub');
$bigLinkButton5 = new navigationItem('Item', 'sub');
$bigLinkButton6 = new navigationItem('Item', 'sub');
$bigLinkButton7 = new navigationItem('Item', 'sub');
$bigLinkButton8 = new navigationItem('Item', 'sub');

$bigLinkButton->addItem($bigLinkButton2);
$bigLinkButton->addItem($bigLinkButton4);
$bigLinkButton->addItem($bigLinkButton5);
$bigLinkButton4->addItem($bigLinkButton3);
$bigLinkButton3->addItem($bigLinkButton6);
$bigLinkButton3->addItem($bigLinkButton7);
$bigLinkButton6->addItem($bigLinkButton8);

$thisPage->addNavigation($bigLinkButton);

$thisPage->addNavigation(new navigationItem('Thing', 'sub', 'right'));


 $urlTable = new pageTable();
        $urlTable->addColumn(new pageTableColumn('Number'));
        $urlTable->addColumn(new pageTableColumn('Thing2'));

	for ($i = 1; $i < 20; $i++) {
                $tableRow = $urlTable->addRow();
                $tableRow->column['Number']->text = $i;
		$tableRow->column['Thing2']->text = 'A row in a table ' . $i;

	}
        $thisPage->addContent($urlTable);








echo $thisPage->printPage();


?>
