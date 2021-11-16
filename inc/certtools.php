<?php
require_once dirname(__FILE__) . '/base.php';

require_once dirname(__FILE__) . '/mysql.php';
require_once dirname(__FILE__) . '/graph-noninteractive.php';

class modCertTools  extends baseClass {
	private $modDB;
	private $modGraph;
	private $settings;

	function __construct() {
		$this->modDB	= new modDB();
		$this->modGraph = new modGraphNI();
		$this->settings	= $this->getSettings();
	}

	function getApps($page = 0, &$pageCount) {
		// Usually I'd just use LIMIT and OFFSET in a SQL query to cope with the paging, but this data isn't from SQL. So we have to grab everything then sort then filter.

		// get all app registrations on tenant
		$allApps = $this->modGraph->getApps();
		foreach($allApps as $app) {
			if ($app->passwordCredentials) {
				foreach($app->passwordCredentials as $appSecret) {


					$status = 'WARN';
					if (strtotime($appSecret->endDateTime) < strtotime('+0 day')) $status = 'EXPIRED';
					if (strtotime($appSecret->endDateTime) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

					$appLD = array('muteAlert' => '', 'notes' => '');
					$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $app->id . '\' AND txtKeyID=\'' . $appSecret->keyId . '\'');
					if ($appLocalData) {
						$appLD['muteAlert'] = $appLocalData['intMuteAlert'];
						$appLD['notes'] = $appLocalData['txtNotes'];
					}



					$appList[] = array(	'id'			=> $app->id,
								'appId'			=> $app->appId,
								'link'			=> 'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' . $app->appId . '/isMSAApp/',
								'createdDateTime' 	=> $app->createdDateTime,
								'displayName' 		=> $app->displayName . ($appSecret->displayName ? ' - ' . $appSecret->displayName : ''),
								'endDateTime'		=> $appSecret->endDateTime,
								'status'		=> $status,
								'keyId'			=> $appSecret->keyId,
								'typeDisplayName'	=> 'Azure AD App Reg Secret',
								'type'			=> 'appreg',
								'editable'		=> '0',
								'source'		=> 'Graph API',
								'muteAlert'		=> $appLD['muteAlert'],
								'notes'			=> $appLD['notes']);

				}
			}
			if ($app->keyCredentials) {
				foreach($app->keyCredentials as $appKey) {

					$status = 'WARN';
					if (strtotime($appKey->endDateTime) < strtotime('+0 day')) $status = 'EXPIRED';
					if (strtotime($appKey->endDateTime) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

					$appLD = array('muteAlert' => '', 'notes' => '');

					$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $app->id . '\' AND txtKeyID=\'' . $appKey->keyId . '\'');
					if ($appLocalData) {
						$appLD['muteAlert'] = $appLocalData['intMuteAlert'];
						$appLD['notes'] = $appLocalData['txtNotes'];
					}


					$appList[] = array(	'id'			=> $app->id,
								'appId'			=> $app->appId,
								'link'			=> 'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' . $app->appId . '/isMSAApp/',
								'createdDateTime' 	=> $app->createdDateTime,
								'displayName' 		=> $app->displayName . ($appKey->displayName ? ' - ' . $appKey->displayName : ''),
								'endDateTime'		=> $appKey->endDateTime,
								'status'		=> $status,
								'keyId'			=> $appKey->keyId,
								'typeDisplayName'	=> 'Azure AD App Reg Certificate',
								'type'			=> 'appreg',
								'editable'		=> '0',
								'source'		=> 'Graph API',
								'muteAlert'		=> $appLD['muteAlert'],
								'notes'			=> $appLD['notes']);

				}
			}
		}

		//Get the Intune certificates/tokens etc
		$intuneCerts = $this->modGraph->getIntune();
		foreach ($intuneCerts as $id => $cert) {
			$status = 'WARN';
			if (strtotime($cert['endDateTime']) < strtotime('+0 day')) $status = 'EXPIRED';
			if (strtotime($cert['endDateTime']) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

			$appLD = array('muteAlert' => '', 'notes' => '');
			$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $cert['id'] . '\' AND txtKeyID=\'intune\'');
			if ($appLocalData) {
				$appLD['muteAlert'] = $appLocalData['intMuteAlert'];
				$appLD['notes'] = $appLocalData['txtNotes'];
			}

			// there is no "created date" so use last modified instead
			$appList[] = array(	'id'			=> $cert['id'],
						'appId'			=> $cert['id'],
						'link'			=> $cert['link'],
						'createdDateTime'	=> $cert['lastModifiedDateTime'],
						'displayName'		=> $cert['displayName'],
						'endDateTime'		=> $cert['endDateTime'],
						'status'		=> $status,
						'typeDisplayName'	=> $cert['typeDisplayName'],
						'type'			=> 'intune',
						'editable'		=> '0',
						'source'		=> 'Intune/Graph API',
						'muteAlert'		=> $appLD['muteAlert'],
						'notes'			=> $appLD['notes']);

		}


		// Get the manually added items
		$manualCerts = $this->modDB->QueryArray('SELECT tblCerts.intCertID, tblCerts.intMuteAlert, tblCerts.dtCreatedDate, tblCerts.dtExpiresDate, tblCerts.txtName, tblCerts.txtNotes, tblCerts.intType, tblCertTypes.txtName as txtType FROM tblCerts INNER JOIN tblCertTypes ON tblCerts.intType = tblCertTypes.intTypeID');
		if ($manualCerts) {
			foreach ($manualCerts as $cert) {
				$status = 'WARN';
				if (strtotime($cert['dtExpiresDate']) < strtotime('+0 day')) $status = 'EXPIRED';
				if (strtotime($cert['dtExpiresDate']) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

				$appList[] = array(	'appId'			=> '',
							'dbId'			=> $cert['intCertID'],
							'link'			=> '',
							'createdDateTime'	=> $cert['dtCreatedDate'],
							'displayName'		=> $cert['txtName'],
							'endDateTime'		=> $cert['dtExpiresDate'],
							'status'		=> $status,
							'typeDisplayName'	=> $cert['txtType'],
							'type'			=> 'manual',
							'editable'		=> '1',
							'muteAlert'		=> $cert['intMuteAlert'],
							'notes'			=> $cert['txtNotes'],
							'source'		=> 'Manual Entry');

			}
		}

		if ($appList) {
			// Sort by endDateTime, ASC
	                foreach ($appList as $id => $app) {
				$toSort[$id] = $app['endDateTime'];
	                }
        	        asort($toSort, SORT_NATURAL);

			foreach ($toSort as $id => $val) {
				$toRet[] = $appList[$id];
			}

			if ($page) {
				$pageCount = ceil(count($appList) / $this->settings['listItems']);
				$pagedArray = array_slice($toRet, (($page - 1) * $this->settings['listItems']), $this->settings['listItems']);
				return $pagedArray;
			}
			return $toRet;
		} else {
			return array();
		}

	}


	// Return a single manual entry

	function getCert($certID) {
		// Get the manually added items
		$manualCerts = $this->modDB->QueryArray('SELECT tblCerts.intCertID, tblCerts.intMuteAlert, tblCerts.dtCreatedDate, tblCerts.txtCreator, tblCerts.dtExpiresDate, tblCerts.txtName, tblCerts.txtNotes, tblCerts.intType, tblCertTypes.txtName as txtType FROM tblCerts INNER JOIN tblCertTypes ON tblCerts.intType = tblCertTypes.intTypeID WHERE tblCerts.intCertID=' . $certID);
		foreach ($manualCerts as $cert) {
			$status = 'WARN';
			if (strtotime($cert['dtExpiresDate']) < strtotime('+0 day')) $status = 'EXPIRED';
			if (strtotime($cert['dtExpiresDate']) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

			$appList = array(	'appId'			=> '',
						'dbId'			=> $cert['intCertID'],
						'link'			=> '',
						'createdDateTime'	=> $cert['dtCreatedDate'],
						'displayName'		=> $cert['txtName'],
						'endDateTime'		=> $cert['dtExpiresDate'],
						'status'		=> $status,
						'typeDisplayName'	=> $cert['txtType'],
						'type'			=> 'manual',
						'editable'		=> '1',
						'owners'		=> array(array('displayName' => $cert['txtCreator'], 'userPrincipalName' => $cert['txtCreator'])),
						'notes'			=> $cert['txtNotes'],
						'muteAlert'		=> $cert['intMuteAlert'],
						'source'		=> 'Manual Entry');

		}
		return $appList;

	}

	// Return a single Intune cert
	function getIntuneCert($appID) {
		//Get the Intune certificates/tokens etc
		$intuneCerts = $this->modGraph->getIntune();
		foreach ($intuneCerts as $id => $cert) {
			$status = 'WARN';
			if (strtotime($cert['endDateTime']) < strtotime('+0 day')) $status = 'EXPIRED';
			if (strtotime($cert['endDateTime']) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';

			// there is no "created date" so use last modified instead
			$appList[$cert['id']] = array(	'id'			=> $cert['id'],
							'appId'			=> $cert['id'],
							'link'			=> $cert['link'],
							'createdDateTime'	=> $cert['lastModifiedDateTime'],
							'displayName'		=> $cert['displayName'],
							'endDateTime'		=> $cert['endDateTime'],
							'status'		=> $status,
							'typeDisplayName'	=> $cert['typeDisplayName'],
							'type'			=> $cert['type'],
							'editable'		=> '0',
							'source'		=> 'Intune/Graph API');
			$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $cert['id'] . '\' AND txtKeyID=\'intune\'');
			if ($appLocalData) {
				$appList[$cert['id']]['muteAlert'] = $appLocalData['intMuteAlert'];
				$appList[$cert['id']]['notes'] = $appLocalData['txtNotes'];
			}

		}

		return $appList[$appID];

	}

	// Return a single Azure AD App Reg secret
	function getApp($appID, $keyID = '') {

		$app = $this->modGraph->getApp($appID);
                if ($app->passwordCredentials) {
	                foreach($app->passwordCredentials as $appSecret) {

				if ($keyID && $keyID == $appSecret->keyId) {

	        	                $status = 'WARN';
	                                if (strtotime($appSecret->endDateTime) < strtotime('+0 day')) $status = 'EXPIRED';
	                                if (strtotime($appSecret->endDateTime) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';
	                                $appList = array(       'id'                    => $app->id,
	                                                        'appId'                 => $app->appId,
	                                                        'link'                  => 'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' . $app->appId . '/isMSAApp/',
	                                                        'createdDateTime'       => $app->createdDateTime,
	                                                        'displayName'           => $app->displayName . ($appSecret->displayName ? ' - ' . $appSecret->displayName : ''),
	                                                        'endDateTime'           => $appSecret->endDateTime,
	                                                        'status'                => $status,
								'keyId'			=> $appSecret->keyId,
	                                                        'typeDisplayName'       => 'Azure AD App Reg Secret',
	                                                        'type'                  => 'appreg',
	                                                        'editable'              => '0',
								'owners'		=> $app->owners,
	                                                        'source'                => 'Graph API');
					$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $app->id . '\' AND txtKeyID=\'' . $appSecret->keyId . '\'');
					if ($appLocalData) {
						$appList['muteAlert'] = $appLocalData['intMuteAlert'];
						$appList['notes'] = $appLocalData['txtNotes'];
					}

				}

                        }
                }
                if ($app->keyCredentials) {
	                foreach($app->keyCredentials as $appKey) {

				if ($keyID && $keyID == $appKey->keyId) {

	        	                $status = 'WARN';
	                                if (strtotime($appKey->endDateTime) < strtotime('+0 day')) $status = 'EXPIRED';
	                                if (strtotime($appKey->endDateTime) > strtotime('+' . $this->settings['alertDays'] . ' day')) $status = 'OK';
	                                $appList = array(       'id'                    => $app->id,
	                                                        'appId'                 => $app->appId,
	                                                        'link'                  => 'https://portal.azure.com/#blade/Microsoft_AAD_RegisteredApps/ApplicationMenuBlade/Credentials/appId/' . $app->appId . '/isMSAApp/',
	                                                        'createdDateTime'       => $app->createdDateTime,
	                                                        'displayName'           => $app->displayName . ($appKey->displayName ? ' - ' . $appKey->displayName : ''),
	                                                        'endDateTime'           => $appKey->endDateTime,
	                                                        'status'                => $status,
								'keyId'			=> $appKey->keyId,
	                                                        'typeDisplayName'       => 'Azure AD App Reg Certificate',
	                                                        'type'                  => 'appreg',
	                                                        'editable'              => '0',
								'owners'		=> $app->owners,
	                                                        'source'                => 'Graph API');
					$appLocalData = $this->modDB->QuerySingle('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $app->id . '\' AND txtKeyID=\'' . $appKey->keyId . '\'');
					if ($appLocalData) {
						$appList['muteAlert'] = $appLocalData['intMuteAlert'];
						$appList['notes'] = $appLocalData['txtNotes'];
					}

				}

                        }
                }
		return $appList;
	}

	function addCert($args) {
		$this->modDB->Insert('tblCerts', array(		'txtName' => $args['displayName'],
								'dtExpiresDate' => $args['renewalDate'],
								'txtNotes' => $args['notes'],
								'intType' => $args['type'],
								'txtCreator' => $args['creator']));
	}

	function updateCert($id, $args) {
		$this->modDB->Update('tblCerts', array(		'txtName' => $args['displayName'],
								'dtExpiresDate' => $args['renewalDate'],
								'txtNotes' => $args['notes'],
								'intMuteAlert' => $args['muteAlert']),
						array('intCertID' => $id));
	}
	function setLastAlert($id, $key) {
		// We need a table to store azureCertID or certID, recipient ID, last alert time

	}
	function deleteCert($id) {
		$this->modDB->Delete('tblCerts', array('intCertID' => $id));
	}

	function updateAzureCert($id, $key, $args) {
		$count = $this->modDB->Count('SELECT * FROM tblAzureCerts WHERE txtAppID=\'' . $id . '\' AND txtKeyID=\'' . $key . '\'');
		if ($count) {
			$this->modDB->Update('tblAzureCerts', array('txtNotes' => $args['notes'], 'intMuteAlert' => $args['muteAlert']), array('txtAppID' => $id, 'txtKeyID' => $key));
		} else {
			$this->modDB->Insert('tblAzureCerts', array('txtNotes' => $args['notes'], 'intMuteAlert' => $args['muteAlert'], 'txtAppID' => $id, 'txtKeyID' => $key));
		}
	}

	function getCertTypes($typeFilter = '') {
		// optional parameter is the ID of the type we want to look up
		$types = $this->modDB->QueryArray('SELECT * FROM tblCertTypes' . ($typeFilter ? ' WHERE intTypeID=' . $typeFilter : ''));
		if ($typeFilter) {
			return $types[0]['txtName'];
		}
		foreach ($types as $type) {
			$toRet[$type['intTypeID']] = $type['txtName'];
		}
		return $toRet;
	}

	function getRecipients($page = 1, &$pageCount) {
		$totalItems = $this->modDB->Count('SELECT * FROM tblAlertRecipients');
		$pageCount = ceil($totalItems / $this->settings['listItems']);

		return $this->modDB->QueryArray('SELECT * FROM tblAlertRecipients ORDER BY txtEmail ASC LIMIT ' . $this->settings['listItems'] . ' OFFSET ' . (($page-1) * $this->settings['listItems']));
	}

	function addRecipient($args) {
		$this->modDB->Insert('tblAlertRecipients', array('txtEmail' => $args['email'], 'txtCreator' => $args['creator']));
	}

	function deleteRecipient($id) {
		$this->modDB->Delete('tblAlertRecipients', array('intRecipientID' => $id));
	}

	function getSettings() {
		$settings = $this->modDB->Query('SELECT * FROM tblSettings');
		foreach ($settings as $setting) {
			$toRet[$setting['txtName']] = $setting['txtValue'];
		}
		return $toRet;
	}

	function saveSettings($args) {
		foreach ($args as $name => $value) {
			$this->modDB->Update('tblSettings', array('txtValue' => $value), array('txtName' => $name));
		}
	}

}
?>
