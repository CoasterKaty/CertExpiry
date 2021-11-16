<?php
/* oauth.php Azure AD oAuth web callback script
 *
 * Katy Nicholson, last updated 16/10/2021
 *
 * https://github.com/CoasterKaty
 * https://katytech.blog/
 * https://twitter.com/coaster_katy
 *
 */
require_once '../inc/mysql.php';
function base64UrlEncode($toEncode) {
                return str_replace('=', '', strtr(base64_encode($toEncode), '+/', '-_'));
        }


        function uuid() {
                //uuid function is not my code, but unsure who the original author is. KN
                //uuid version 4
                return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    // 32 bits for "time_low"
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
                    // 16 bits for "time_mid"
                    mt_rand( 0, 0xffff ),
                    // 16 bits for "time_hi_and_version",
                    // four most significant bits holds version number 4
                    mt_rand( 0, 0x0fff ) | 0x4000,
                    // 16 bits, 8 bits for "clk_seq_hi_res",
                    // 8 bits for "clk_seq_low",
                    // two most significant bits holds zero and one for variant DCE1.1
                    mt_rand( 0, 0x3fff ) | 0x8000,
                    // 48 bits for "node"
                    mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
                );
        }

session_start();
$modDB = new modDB();
if ($_GET['error']) {
    die($_GET['error_description']);
    exit;
}
//retrieve session data from database
$sessionData = $modDB->QuerySingle('SELECT * FROM tblAuthSessions WHERE txtSessionKey=\'' . $modDB->Escape($_SESSION['sessionkey']) . '\'');

if ($sessionData) {
    // Request token from Azure AD
        if (_OAUTH_AUTH_CERTFILE) {
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
                                                'jti' => uuid(),
                                                'nbf' => date('U'),
                                                'sub' => _OAUTH_CLIENTID));
                        $caSignature = '';

                        $caData = base64UrlEncode($caHeader) . '.' . base64UrlEncode($caPayload);
                        openssl_sign($caData, $caSignature, $certKey, OPENSSL_ALGO_SHA256);
                        $caSignature = base64UrlEncode($caSignature);
                        $clientAssertion = $caData . '.' . $caSignature;
			$oauthRequest = 'grant_type=authorization_code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . '/oauth.php') . '&code=' . $_GET['code'] . '&code_verifier=' . $sessionData['txtCodeVerifier'] . '&client_assertion=' . $clientAssertion . '&client_assertion_type=urn:ietf:params:oauth:client-assertion-type:jwt-bearer';

                } else {
		        $oauthRequest = 'grant_type=authorization_code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . '/oauth.php') . '&code=' . $_GET['code'] . '&client_secret=' . urlencode(_OAUTH_SECRET) . '&code_verifier=' . $sessionData['txtCodeVerifier'];
                }



    $ch = curl_init(_OAUTH_SERVER . 'token');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $oauthRequest);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if ($cError = curl_error($ch)) {
        die($cError);
    }
    curl_close($ch);
    // Decode response from Azure AD. Extract JWT data from supplied access_token and update database.
    $reply = json_decode($response);
    if ($reply->error) {
        die($reply->error_description);
    }
    if (strpos($reply->accessToken, '.') === false) {
	    $accessToken = base64_decode($reply->access_token);
    } else {
	    $accessToken = base64_decode(explode('.', $reply->access_token)[1]);
    }
    $idToken = base64_decode(explode('.', $reply->id_token)[1]);
    $modDB->Update('tblAuthSessions', array('txtToken' => $reply->access_token, 'txtRefreshToken' => $reply->refresh_token, 'txtIDToken' => $idToken, 'txtRedir' => '', 'dtExpires' => date('Y-m-d H:i:s', strtotime('+' . $reply->expires_in . ' seconds'))), array('intAuthID' => $sessionData['intAuthID']));
    // Redirect user back to where they came from.
    header('Location: ' . $sessionData['txtRedir']);
} else {
    header('Location: /');
}
?>
