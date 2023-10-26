<?php
/*
 *   Non-interactive Graph script - for scheduled actions etc where there will not be a user whose logon context we can use
 *
 *
 *   https://katystech.blog/
 */
require_once dirname(__FILE__) . '/base.php';
require_once dirname(__FILE__) . '/exception.php';

class modGraphNI extends baseClass {

	var $Token;
	var $baseURL;

	function __construct() {
	        $this->baseURL = 'https://graph.microsoft.com/v1.0/';
	        $this->Token = $this->getToken();
	}

	function getToken() {
		if (defined('_OAUTH_AUTH_CERTFILE')) {
			// Use the certificate specified
			//https://docs.microsoft.com/en-us/azure/active-directory/develop/active-directory-certificate-credentials
			$cert = file_get_contents(_OAUTH_AUTH_CERTFILE);
			$certKey = openssl_pkey_get_private(file_get_contents(_OAUTH_AUTH_KEYFILE));
			$certHash = openssl_x509_fingerprint($cert);
			$certHash = base64_encode(hex2bin($certHash));
			$caHeader = json_encode(array('alg' => 'RS256', 'typ' => 'JWT', 'x5t' => $certHash));
			$caPayload = json_encode(array('aud' => 'https://login.microsoftonline.com/' . _OAUTH_TENANTID . '/v2.0',
						'exp' => date('U', strtotime('+10 minute')),
						'iss' => _OAUTH_CLIENTID,
						'jti' => $this->uuid(),
						'nbf' => date('U'),
						'sub' => _OAUTH_CLIENTID));
			$caSignature = '';

			$caData = $this->base64UrlEncode($caHeader) . '.' . $this->base64UrlEncode($caPayload);
			openssl_sign($caData, $caSignature, $certKey, OPENSSL_ALGO_SHA256);
			$caSignature = $this->base64UrlEncode($caSignature);
			$clientAssertion = $caData . '.' . $caSignature;
		        $oauthRequest = 'client_id=' . _OAUTH_CLIENTID . '&scope=https%3A%2F%2Fgraph.microsoft.com%2F.default&client_assertion=' . $clientAssertion . '&client_assertion_type=urn:ietf:params:oauth:client-assertion-type:jwt-bearer&grant_type=client_credentials';
		} else {
		        $oauthRequest = 'client_id=' . _OAUTH_CLIENTID . '&scope=https%3A%2F%2Fgraph.microsoft.com%2F.default&client_secret=' . _OAUTH_SECRET . '&grant_type=client_credentials';
		}
	        $reply = $this->sendPostRequest('https://login.microsoftonline.com/' . _OAUTH_TENANTID . '/oauth2/v2.0/token', $oauthRequest);
	        $replyData = json_decode($reply['data']);
		if ($reply['code'] == '401') {
			throw new siteException($replyData->error_description . "<br>" . print_r($replyData, true) . "<br>modGraphNI->getToken");
		}
	        return $replyData->access_token;
	}

	function getApps() {
		if (!$this->Token) {
			throw new siteException('No token defined<br>modGraphNI->getApps');
		}

		$apps = $this->getPagedData($this->baseURL . 'applications');
		return $apps;
	}
	function getPagedData($url) {
		$itemArray;
		$data = json_decode($this->sendGetRequest($url));
		if (property_exists($data, 'error')) {
			throw new siteException(print_r($data->error) . "<br>modGraphNI->getPagedData");
		}
		foreach($data->value as $item) {
			$itemArray[] = $item;
		}
		if (property_exists($data, '@odata.nextLink')) {
			$itemArray = array_merge($itemArray, $this->getPagedData($data->{'@odata.nextLink'}));
		}
		return $itemArray;
	}

	function getApp($appID) {
		if (!$this->Token) {
			throw new siteException('No token defined<br>modGraphNI->getApp');
		}

		$apps = json_decode($this->sendGetRequest($this->baseURL . 'applications/' . $appID));
		$appOwners = json_decode($this->sendGetRequest($this->baseURL . 'applications/' . $appID . '/owners'));

		foreach ($appOwners->value as $owner) {
			$apps->owners[] = array('displayName' => $owner->displayName, 'userPrincipalName' => $owner->userPrincipalName);
		}
		return $apps;
	}

	function getIntune() {
		if (!$this->Token) {
			throw new siteException('No token defined<br>modGraphNI->getIntune');
		}


		// GET /deviceAppManagement/vppTokens
		// GET /deviceManagement/applePushNotificationCertificate

		$vppCert = json_decode($this->sendGetRequest($this->baseURL . 'deviceAppManagement/vppTokens'));
		if (!property_exists($vppCert, 'error')) {
			if ($vppCert->value) {
				foreach ($vppCert->value as $vppc) {
					$toRet[] = array(	'typeDisplayName' => 'Intune Apple VPP Token',
								'type' => 'vpp',
								'id' => $vppc->id,
								'link' => 'https://endpoint.microsoft.com/#blade/Microsoft_Intune_DeviceSettings/TenantAdminConnectorsMenu/appleVpp',
								'displayName' => $vppc->appleId,
								'lastModifiedDateTime' => $vppc->lastModifiedDateTime,
								'endDateTime' => $vppc->expirationDateTime);
				}
			}
		}

		$apnc = json_decode($this->sendGetRequest($this->baseURL . 'deviceManagement/applePushNotificationCertificate'));
		if (!property_exists($apnc, 'error')) {
				$toRet[] = array(	'typeDisplayName' => 'Intune Apple Push Cert',
							'type' => 'apnc',
							'id' => 'apnc',
							'link' => 'https://endpoint.microsoft.com/#blade/Microsoft_Intune_DeviceSettings/DevicesEnrollmentMenu/appleEnrollment',
							'displayName' => $apnc->appleIdentifier,
							'lastModifiedDateTime' => $apnc->lastModifiedDateTime,
							'endDateTime' => $apnc->expirationDateTime);

		}



		// enrollment program tokens - beta API only currently
		$epTokens = json_decode($this->sendGetRequest('https://graph.microsoft.com/beta/deviceManagement/depOnboardingSettings'));
		if (!property_exists($epTokens, 'error')) {
			if ($epTokens->value) {
				foreach ($epTokens->value as $epToken) {
					$toRet[] = array(	'typeDisplayName' => 'Intune Enrollment Program Token',
								'type' => 'erpt',
								'id' => $epToken->id,
								'link' => 'https://endpoint.microsoft.com/#blade/Microsoft_Intune_DeviceSettings/DevicesEnrollmentMenu/appleEnrollment',
								'displayName' => $epToken->tokenName . ' (' . $epToken->appleIdentifier . ')',
								'lastModifiedDateTime' => $epToken->lastModifiedDateTime,
								'endDateTime' => $epToken->tokenExpirationDateTime);

				}
			}
		}
		return ($toRet ? $toRet : array());

	}


    function sendMail($mailbox, $messageArgs ) {
        if (!$this->Token) {
            throw new siteException('No token defined<br>modGraphNI->sendMail');
        }

        /*
        $messageArgs[   subject,
                replyTo{'name', 'address'},
                toRecipients[]{'name', 'address'},
                ccRecipients[]{'name', 'address'},
                importance,
                conversationId,
                body,
                images[],
                attachments[]
                ]
        */

        foreach ($messageArgs['toRecipients'] as $recipient) {
            if ($recipient['name']) {
                $messageArray['toRecipients'][] = array('emailAddress' => array('name' => $recipient['name'], 'address' => $recipient['address']));
            } else {
                $messageArray['toRecipients'][] = array('emailAddress' => array('address' => $recipient['address']));
            }
        }
	if ($messageArgs['ccRecipients']) {
            foreach ($messageArgs['ccRecipients'] as $recipient) {
                if ($recipient['name']) {
                    $messageArray['ccRecipients'][] = array('emailAddress' => array('name' => $recipient['name'], 'address' => $recipient['address']));
                } else {
                    $messageArray['ccRecipients'][] = array('emailAddress' => array('address' => $recipient['address']));
                }
	    }
        }
        $messageArray['subject'] = $messageArgs['subject'];
        $messageArray['importance'] = ($messageArgs['importance'] ? $messageArgs['importance'] : 'normal');
        if (isset($messageArgs['replyTo'])) $messageArray['replyTo'] = array(array('emailAddress' => array('name' => $messageArgs['replyTo']['name'], 'address' => $messageArgs['replyTo']['address'])));
        $messageArray['body'] = array('contentType' => 'HTML', 'content' => $messageArgs['body']);
        $messageJSON = json_encode($messageArray);
        $response = $this->sendPostRequest($this->baseURL . 'users/' . $mailbox . '/messages', $messageJSON, array('Content-type: application/json'));

        $response = json_decode($response['data']);
        $messageID = $response->id;

	if ($messageArgs['images']) {
            foreach ($messageArgs['images'] as $image) {
                $messageJSON = json_encode(array('@odata.type' => '#microsoft.graph.fileAttachment', 'name' => $image['Name'], 'contentBytes' => base64_encode($image['Content']), 'contentType' => $image['ContentType'], 'isInline' => true, 'contentId' => $image['ContentID']));
                $response = $this->sendPostRequest($this->baseURL . 'users/' . $mailbox . '/messages/' . $messageID . '/attachments', $messageJSON, array('Content-type: application/json'));
            }
	}

	if ($messageArgs['attachments']) {
            foreach ($messageArgs['attachments'] as $attachment) {
                $messageJSON = json_encode(array('@odata.type' => '#microsoft.graph.fileAttachment', 'name' => $attachment['Name'], 'contentBytes' => base64_encode($attachment['Content']), 'contentType' => $attachment['ContentType'], 'isInline' => false));
                $response = $this->sendPostRequest($this->baseURL . 'users/' . $mailbox . '/messages/' . $messageID . '/attachments', $messageJSON, array('Content-type: application/json'));
            }
	}
        //Send
        $response = $this->sendPostRequest($this->baseURL . 'users/' . $mailbox . '/messages/' . $messageID . '/send', '', array('Content-Length: 0'));
        if ($response['code'] == '202') return true;
        return false;

    }

    function basicAddress($addresses) {
        foreach ($addresses as $address) {
            $ret[] = $address->emailAddress->address;
        }
        return $ret;
    }

    function sendDeleteRequest($URL) {
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->Token, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        echo $response;
    }

    function sendPostRequest($URL, $Fields, $Headers = false) {
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($Fields) curl_setopt($ch, CURLOPT_POSTFIELDS, $Fields);
        if ($Headers) {
            $Headers[] = 'Authorization: Bearer ' . $this->Token;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $Headers);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return array('code' => $responseCode, 'data' => $response);
    }

    function sendGetRequest($URL) {
        $ch = curl_init($URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->Token, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
?>
