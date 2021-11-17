<?php

include '../inc/page.php';
include '../inc/certtools.php';
$pageAction = ($_GET['action'] ? $_GET['action'] : 'list');
$pageSubmitted = $_GET['submitted'];
$pageNumber = ($_GET['page'] ? $_GET['page'] : 1);

$isFlyout = ($_GET['flyout'] ? '1' : '');

try {
	$thisPage = new sitePage('Certificate Expiry Notification Tool');
	$thisPage->displayRole = 0;
	if (!$isFlyout) {
	        $thisPage->logo = '/images/logo.png';
	        $thisPage->initFlyout();
	}
	$certTools = new modCertTools();
	$settings = $certTools->getSettings();

	if (!$isFlyout) {

		$feedbackBtn = new navigationItem('', 'main', 'right');
		$feedbackBtn->icon = 'Feedback.png';
		$feedbackBtn->tooltip = 'Send feedback or a feature request';
		$feedbackBtn->link = 'https://github.com/CoasterKaty/CertExpiry/issues';
		$feedbackBtn->newWindow = 1;
		$feedbackBtn->width = 40;
		$thisPage->addNavigation($feedbackBtn);


		switch ($pageAction) {
			case 'list':
				$sbNav = new navigationItem('Apps and Certificates', 'side');
				$sbNewItem = new navigationItem('New Item', 'side');
				$sbNewItem->icon = 'new.png';
				$sbNewItem->flyoutAction = 'index.php?action=newcert&flyout=1';
				$sbNewItem->flyoutTitle = 'New Item';
				$sbNav->addItem($sbNewItem);
				$thisPage->addNavigation($sbNav);
				break;
			case 'recipients':
				$sbNav = new navigationItem('Alert Recipients', 'side');
				$sbNewItem = new navigationItem('Add Recipient', 'side');
				$sbNewItem->icon = 'AddRecipient.png';
				$sbNewItem->flyoutAction = 'index.php?action=newrecipient&flyout=1';
				$sbNewItem->flyoutTitle = 'Add Recipient';
				$sbNav->addItem($sbNewItem);
				$thisPage->addNavigation($sbNav);
				break;

		}


		$sbNav2 = new navigationItem('Configuration', 'side');

		$sbCerts = new navigationItem('Apps and Certificates', 'side');
		$sbCerts->icon = 'AppsCertificates.png';
		$sbCerts->link = 'index.php?action=list';
		if ($pageAction == 'list') $sbCerts->selected=1;
		$sbNav2->addItem($sbCerts);

		$sbAlertRecip = new navigationItem('Alert Recipients', 'side');
		$sbAlertRecip->icon = 'Recipients.png';
		$sbAlertRecip->link = 'index.php?action=recipients';
		if ($pageAction == 'recipients') $sbAlertRecip->selected=1;
		$sbNav2->addItem($sbAlertRecip);

		$sbAlertRecip = new navigationItem('Settings', 'side');
		$sbAlertRecip->icon = 'Settings.png';
		$sbAlertRecip->flyoutAction = 'index.php?action=settings&flyout=1';
		$sbAlertRecip->flyoutTitle = 'Settings';
		$sbNav2->addItem($sbAlertRecip);

		$thisPage->addNavigation($sbNav2);
	}

	switch ($pageAction) {
		case 'alert':
			require_once '../inc/alert_mail_task.php';
			$alertMail = new alertMailTask();
			$alertMail->sendAlert();
			break;
		case 'newcert':
			if ($pageSubmitted) {
				$certTools->addCert(array('type' => $_POST['type'], 'displayName' => $_POST['displayName'], 'renewalDate' => $_POST['renewalDate'], 'notes' => $_POST['notes'], 'creator' => $thisPage->modAuth->userName));
				header('Location: ' . urldecode($_POST['httpReferer']));
				exit;
			}


	                $createForm = new pageForm('addCertificate', 'index.php?action=newcert&submitted=1');
			$createForm->method = 'post';
	                $typeField = $createForm->addField(new pageFormField('type', 'dropdown'));
	                $typeField->placeholder = 'Select Item Type';
	                $typeField->label = 'Item Type';
	                $typeField->options = $certTools->getCertTypes();
        	        $typeField->required = 1;

	                $domainField = $createForm->addField(new pageFormField('displayName', 'text'));
	                $domainField->label = 'Name';
	                $domainField->placeholder = 'App name or certificate domain name';
			$domainField->required = 1;
			$domainField->maxLength = 255;
	                $renewalField = $createForm->addField(new pageFormField('renewalDate', 'date'));
	                $renewalField->label = 'Renewal Date';
			$renewalField->min = date('Y-m-d');
			$renewalField->required = 1;
	                $notesField = $createForm->addField(new pageFormField('notes', 'bigtext'));
	                $notesField->label = 'Notes';
			$notesField->maxLength = 65535;
	                $submitButton = $createForm->addField(new pageFormField('save', 'submit'));
	                $submitButton->value = 'Create';
	                $thisPage->addContent($createForm);
	                echo $thisPage->printFlyoutPage();
			exit;

			break;
		case 'settings':
			if ($pageSubmitted) {
				$certTools->saveSettings(array(	'alertDays'	 => $_POST['alertDays'],
								'senderEmail'	 => $_POST['senderEmail'],
								'emailAlerts'	 => ($_POST['emailAlerts'] ? '1' : '0'),
								'alertFrequency' => $_POST['alertFrequency'],
								'alertTemplate'	 => $_POST['alertTemplate'],
								'listItems'	 => $_POST['listItems']
							));
				echo '1';
				exit;
			}
			$settingsForm = new pageForm('settings', 'index.php?action=settings&submitted=1');
			$settingsForm->method = 'ajax';

			$alertDayField = $settingsForm->addField(new pageFormField('alertDays', 'number'));
			$alertDayField->label = 'Alert when expiry is due in (days)';
			$alertDayField->help = 'This controls when items in the list show as Warning status, and is also used by the e-mail alerts.';
			$alertDayField->required = 1;
			$alertDayField->min = 1;
			$alertDayField->max = 180;
			$alertDayField->value = $settings['alertDays'];

			$emailAlertField = $settingsForm->addField(new pageFormField('emailAlerts', 'toggle'));
			$emailAlertField->label = 'Enable e-mail alerts';
			$emailAlertField->value = $settings['emailAlerts'];

			$alertFreqField = $settingsForm->addField(new pageFormField('alertFrequency', 'dropdown'));
			$alertFreqField->help = 'Warning: Do not set this higher than the \'alert days\' setting';
			$alertFreqField->label = 'Alert frequency';
			$alertFreqField->value = $settings['alertFrequency'];
			$alertFreqField->options = array('1' => 'Every day', '7' => 'Every week', '14' => 'Every two weeks');

			$emailField = $settingsForm->addField(new pageFormField('senderEmail', 'text'));
			$emailField->label = 'Send notifications from';
			$emailField->placeholder = 'e-mail address of mailbox';
			$emailField->value = $settings['senderEmail'];
			$emailField->required = 1;

			$templateField = $settingsForm->addField(new pageFormField('alertTemplate', 'bigtext'));
			$templateField->label = 'Template for e-mail notifications';
			$templateField->placeholder = 'Template Here. {$data}';
			$templateField->value = $settings['alertTemplate'];
			$templateField->help = 'Use HTML formatting, with the following variables:' . _NL . '{$alertMuted} - number of muted items in the alert' . _NL . '{$alertDays} - number of days before expiry the warning occurs' . _NL . '{data} - the table of alerts' . _NL . '{$url} - the URL to this web app';
			$templateField->height = 270;
			$templateField->required = 1;

			$listCountField = $settingsForm->addField(new pageFormField('listItems', 'number'));
			$listCountField->label = 'Number of rows to show in list';
			$listCountField->help = 'Results in lists are split into pages. About 25 is about right for 1080p resolution.';
			$listCountField->required = 1;
			$listCountField->min = 10;
			$listCountField->max = 100;
			$listCountField->value = $settings['listItems'];

			$saveButton = $settingsForm->addField(new pageFormField('save', 'submit'));
			$saveButton->value = 'Update';

			$thisPage->addContent($settingsForm);
			echo $thisPage->printFlyoutPage();
			exit;

			break;
		case 'newrecipient':
			if ($pageSubmitted) {
				$certTools->addRecipient(array('email' => $_POST['email'], 'creator' => $thisPage->modAuth->userName));
				header('Location: index.php?action=recipients');
				exit;
			}
        	        $createForm = new pageForm('addRecipient', 'index.php?action=newrecipient&submitted=1');


	                $emailField = $createForm->addField(new pageFormField('email', 'text'));
	                $emailField->label = 'E-mail Address';
			$emailField->maxLength = 255;
	                $emailField->placeholder = 'User@domain.tld';
			$emailField->required = 1;
	                $submitButton = $createForm->addField(new pageFormField('save', 'submit'));
	                $submitButton->value = 'Create';
	                $thisPage->addContent($createForm);
	                echo $thisPage->printFlyoutPage();
			exit;

			break;
		case 'deleterecipient':
			$certTools->deleteRecipient($_GET['id']);
			header('Location: ' . $_SERVER['HTTP_REFERER']);
			exit;
			break;
		case 'recipients':
			$pageCount = 0;
			$recipients = $certTools->getRecipients($pageNumber, $pageCount);
			$thisPage->addContent(new infoTip('Alert recipients will receive e-mail alerts when any app registration secret or certificate is nearing expiry.'));
			if ($recipients) {
				$recipTable = new pageTable();
				$recipTable->pages = 1;
				$recipTable->page = $pageNumber;
				$recipTable->pageSize = $settings['listItems'];
				$recipTable->pageCount = $pageCount;
				$recipTable->pageURL = 'index.php?action=recipients';
				($recipTable->addColumn(new pageTableColumn('E-mail Address')))->width = 400;
				$recipTable->addColumn(new pageTableColumn('Created'));
				($recipTable->addColumn(new pageTableColumn('Created By')))->width = 250;

				$recipientMenu = new pageTableMenu();
				$deleteBtn = $recipientMenu->addItem(new pageTableMenuItem('Delete', 'index.php?action=deleterecipient&id=$ID'));
				$deleteBtn->icon = 'Delete.png';
				$deleteBtn->confirm = 'Are you sure you want to delete <b>$NAME</b>?';

				foreach ($recipients as $key => $recipient) {
					$tableRow = $recipTable->addRow();
					$tableRow->column['E-mail Address']->text = $recipient['txtEmail'];
					$tableRow->column['Created']->text = date('d M Y', strtotime($recipient['dtDateCreated']));
					$tableRow->column['Created By']->text = $recipient['txtCreator'];
					$tableRow->name = $recipient['txtEmail'];
					$tableRow->linkID = $recipient['intRecipientID'];
					$tableRow->menu = $recipientMenu;
				}
				$recipTable->sort('asc', 'E-mail Address');
				$thisPage->addContent($recipTable);
			} else {
				$thisPage->addContent(new infoTip('There are no alert recipients configured. Click on Add Recipient on the left hand menu to begin.', 'warning'));
			}
			break;
		case 'delete':
			$id = $_GET['id'];
			if ($id) {
				$certTools->deleteCert($id);
			}
			header('Location: ' . $_SERVER['HTTP_REFERER']);
			exit;
			break;

		case 'properties':
			$id = $_GET['id'];
			$key = $_GET['key'];
			$type = $_GET['type'];

			if ($pageSubmitted) {
				switch ($type) {
					case 'manual':
						$certTools->updateCert($id, array('displayName' => $_POST['displayName'], 'renewalDate' => $_POST['renewalDate'], 'muteAlert' => ($_POST['muteAlert'] ? '1' : '0'), 'notes' => $_POST['notes']));
						break;
					case 'appreg':
						$certTools->updateAzureCert($id, $key, array('muteAlert' => ($_POST['muteAlert'] ? '1' : '0'), 'notes' => $_POST['notes']));
						break;
					case 'intune':
						$certTools->updateAzureCert($id, 'intune', array('muteAlert' => ($_POST['muteAlert'] ? '1' : '0'), 'notes' => $_POST['notes']));
						break;
				}
				header('Location: ' . urldecode($_POST['httpReferer']));
				exit;
			}

			switch ($type) {
				case 'manual':
					$thisApp = $certTools->getCert($id);
					break;
				case 'appreg':
					$thisApp = $certTools->getApp($id, $key);
					break;
				case 'intune':
					$thisApp = $certTools->getIntuneCert($id);
					break;
			}
			$propertyForm = new pageForm('properties', '');


			$dnField = $propertyForm->addField(new pageFormField('displayName', 'text'));
			$dnField->label = 'Display Name';
			$dnField->value = $thisApp['displayName'];
			$dnField->disabled = ($type == 'manual' ? 0 : 1);
			$dnField->maxLength = 255;
			if ($thisApp['appId']) {
				$appIdField = $propertyForm->addField(new pageFormField('appId', 'text'));
				$appIdField->label = 'App ID';
				$appIdField->value = $thisApp['appId'];
				$appIdField->disabled = 1;
			}
			if ($thisApp['keyId']) {
				$appIdField = $propertyForm->addField(new pageFormField('keyId', 'text'));
				$appIdField->label = 'Key ID';
				$appIdField->value = $thisApp['keyId'];
				$appIdField->disabled = 1;
			}

			$statusField = $propertyForm->addField(new pageFormField('status', 'text'));
			$statusField->label = 'Status';
			switch ($thisApp['status']) {
				case 'EXPIRED':
					$statusField->value = 'WARNING! Certificate or secret has expired!';
					break;
				case 'WARN':
					$statusField->value = 'WARNING! Certificate or secret is due to expire soon.';
					break;
				case 'OK':
					$statusField->value = 'Good - Certificate or secret is not nearing expiry.';
					break;
			}
			$statusField->disabled = 1;

			$createdField = $propertyForm->addField(new pageFormField('createdDate', 'date'));
			$createdField->label = 'Created or Last Modified';
			$createdField->help = 'This will show the date the item was created (app registration and manual entries) or last modified (Intune certificates)';
			$createdField->value = date('Y-m-d', strtotime($thisApp['createdDateTime']));
			$createdField->disabled = 1;
			$renewalField = $propertyForm->addField(new pageFormField('renewalDate', 'date'));
			$renewalField->label = 'Renewal required';
			$renewalField->value = date('Y-m-d', strtotime($thisApp['endDateTime']));
			$renewalField->disabled = ($type == 'manual' ? 0 : 1);
			$typeField = $propertyForm->addField(new pageFormField('type', 'text'));
			$typeField->label = 'Type';
			$typeField->value = $thisApp['typeDisplayName'];
			$typeField->disabled = 1;
			$sourceField = $propertyForm->addField(new pageFormField('source', 'text'));
			$sourceField->label = 'Source';
			$sourceField->value = $thisApp['source'];
			$sourceField->disabled = 1;

			$muteField = $propertyForm->addField(new pageFormField('muteAlert', 'toggle'));
			$muteField->label = 'Disable alerts for this item';
			$muteField->help = 'This will disable e-mail based alerts for this item. E-mail alerts can be configured for the entire application in the Settings menu.';
			$muteField->value = $thisApp['muteAlert'];

			if ($thisApp['link']) {
				$linkField = $propertyForm->addField(new pageFormField('link', 'link'));
				$linkField->label = 'Link to portal';
				$linkField->link = $thisApp['link'];
				$linkField->value = ($type == 'appreg' ? 'Azure AD App Registration Portal' : ($type == 'intune' ? 'Microsoft Endpoint Manager Admin Centre' : 'Unknown'));
			}


			if ($thisApp['owners']) {
				foreach ($thisApp['owners'] as $owner) {
					$owners[] = $owner['displayName'] . ' (' . $owner['userPrincipalName'] . ')';
				}
				$ownerField = $propertyForm->addField(new pageFormField('owners', 'list'));
				$ownerField->help = 'For Azure AD Apps this is anybody listed under Owners. For manual items this is the person who created the entry';
				$ownerField->label = 'Application Owner' . (count($owners) > 1 ? 's' : '');
				$ownerField->options = $owners;
				$ownerField->disabled = 1;
			}


			$notesField = $propertyForm->addField(new pageFormField('notes', 'bigtext'));
			$notesField->label = 'Notes';
			$notesField->help = 'Notes are saved in this application and not written back to Azure';
			$notesField->value = $thisApp['notes'];
			$notesField->maxLength = 65535;


			$propertyForm->action = 'index.php?action=properties&submitted=1&id=' . $id . '&type=' . $type . '&key=' . $key;

			$saveButton = $propertyForm->addField(new pageFormField('update', 'submit'));
			$saveButton->value = 'Update';

	                $thisPage->addContent($propertyForm);
	                echo $thisPage->printFlyoutPage();
			exit;
			break;
		default:

			$pageCount = 0;
			$apps = $certTools->getApps($pageNumber, $pageCount);
			$appTable = new pageTable();
			$appTable->pages = 1;
			$appTable->page = $pageNumber;
			$appTable->pageSize = $settings['listItems'];
			$appTable->pageCount = $pageCount;
			$appTable->pageURL = 'index.php?action=list';
			($appTable->addColumn(new pageTableColumn('Name')))->width = 300;
			($appTable->addColumn(new pageTableColumn('ID')))->width = 250;
			($appTable->addColumn(new pageTableColumn('Type')))->width = 180;
			($appTable->addColumn(new pageTableColumn('Expiry')))->width = 90;
			($appTable->addColumn(new pageTableColumn('Source')))->width = 100;
			$sortableDate = $appTable->addColumn(new pageTableColumn('SortDate'));
			$sortableDate->hidden = 1;


			$editableMenu = new pageTableMenu();
			$propBtn = $editableMenu->addItem(new pageTableMenuItem('More Details', ''));
			$propBtn->icon = 'Properties.png';
			$propBtn->flyoutAction = 'index.php?action=properties&flyout=1&key=$ATTR1&type=$ATTR2&id=$ID';
			$propBtn->flyoutTitle = '$NAME';
			$deleteBtn = $editableMenu->addItem(new pageTableMenuItem('Delete', 'index.php?action=delete&id=$ID'));
			$deleteBtn->icon = 'Delete.png';
			$deleteBtn->confirm = 'Are you sure you want to delete <b>$NAME</b>?';

			$nonEditableMenu = new pageTableMenu();
			$propBtn = $nonEditableMenu->addItem(new pageTableMenuItem('More Details', ''));
			$propBtn->icon = 'Properties.png';
			$propBtn->flyoutAction = 'index.php?action=properties&flyout=1&key=$ATTR1&type=$ATTR2&id=$ID';
			$propBtn->flyoutTitle = '$NAME';
			$azureBtn = $nonEditableMenu->addItem(new pageTableMenuItem('Open Portal', '$ATTR3'));
			$azureBtn->icon = 'OpenPortal.png';
			$azureBtn->newWindow = 1;

			$warnCount = 0;
			$expireCount = 0;

			foreach ($apps as $app) {
				$tableRow = $appTable->addRow();
				$tableRow->column['Name']->text = $app['displayName'];
				$tableRow->column['ID']->text = $app['appId'];
				$tableRow->column['Type']->text = $app['typeDisplayName'];
				$tableRow->column['Expiry']->text = date('d M Y', strtotime($app['endDateTime']));
				$tableRow->column['SortDate']->text = $app['endDateTime'];
				$tableRow->attr3 = $app['link'];
				$tableRow->name = $app['displayName'];
				if ($app['editable']) {
					$tableRow->linkID = $app['dbId'];
					$tableRow->attr2 = $app['type'];
					$tableRow->menu = $editableMenu;
				} else {
					$tableRow->linkID = $app['id'];
					$tableRow->attr2 = $app['type'];
					$tableRow->menu = $nonEditableMenu;
				}
				if ($app['keyId']) $tableRow->attr1 = $app['keyId'];

				$tableRow->column['Name']->flyoutAction = 'index.php?action=properties&flyout=1&id=' . $tableRow->linkID . '&key=' . $tableRow->attr1 . '&type=' . $tableRow->attr2;
				$tableRow->column['Name']->flyoutTitle = $tableRow->name;

				switch ($app['status']) {
					case 'OK':
						$tableRow->icon = 'StatusGood.png';
						break;
					case 'WARN':
						$tableRow->icon = 'StatusWarning.png';
						$warnCount++;
						break;
					case 'EXPIRED':
						$tableRow->icon = 'StatusError.png';
						$expireCount++;
						break;
				}
				$tableRow->column['Source']->text = $app['source'];
			}


			if ($warnCount > 0 || $expireCount > 0) {
				$thisPage->addContent(new infoTip('There are ' . ($warnCount ? $warnCount . ' certificates/secrets nearing expiry' : '') . ($warnCount && $expireCount ? ' and ' : '') . ($expireCount ? $expireCount . ' expired certificates/secrets' : ''), 'warning'));
			}

			$thisPage->addContent(new infoTip('This list will automatically show any Azure AD App Registrations which have had certificates or secrets configured, along with Intune Apple Push Certificate, Apple VPP token, and Enrollment Program tokens. You can also manually add items (such as SSL certificates, or app registrations on an unconnected tenant).'));

			if ($appTable->rowCount() > 0) {
				$thisPage->addContent($appTable);
			} else {
				$thisPage->addContent(new infoTip('There are no app registrations detected in Azure AD, no Intune certificates or tokens detected, and no certificates have been manually added.', 'warning'));
			}
			break;
	}


	echo $thisPage->printPage();

} catch (siteException $e) {
	echo($e->getMessage());
	exit;
}

?>
