CREATE TABLE `tblAuthSessions` (
  `intAuthID` int(11) NOT NULL AUTO_INCREMENT,
  `txtSessionKey` varchar(255) DEFAULT NULL,
  `dtExpires` datetime DEFAULT NULL,
  `txtRedir` varchar(255) DEFAULT NULL,
  `txtRefreshToken` text DEFAULT NULL,
  `txtCodeVerifier` varchar(255) DEFAULT NULL,
  `txtToken` text DEFAULT NULL,
  `txtIDToken` text DEFAULT NULL,
  PRIMARY KEY (`intAuthID`)
);

CREATE TABLE `tblAlertRecipients` (
  `intRecipientID` int(11) NOT NULL AUTO_INCREMENT,
  `txtEmail` varchar(255) DEFAULT NULL,
  `txtCreator` varchar(255) DEFAULT NULL,
  `dtDateCreated` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`intRecipientID`)
);


CREATE TABLE `tblCerts` (
  `intCertID` INT NOT NULL AUTO_INCREMENT,
  `dtCreatedDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dtExpiresDate` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `intType` INT NOT NULL DEFAULT 1,
  `txtName` VARCHAR(255) NULL,
  `txtCreator` VARCHAR(255) NULL,
  `txtNotes` TEXT NULL,
  `intMuteAlert` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`intCertID`));

CREATE TABLE `tblCertTypes` (
  `intTypeID` INT NOT NULL AUTO_INCREMENT,
  `txtName` VARCHAR(45) NULL,
  PRIMARY KEY (`intTypeID`));


CREATE TABLE `tblSettings` (
  `intSettingID` int(11) NOT NULL AUTO_INCREMENT,
  `txtName` varchar(255) DEFAULT NULL,
  `txtValue` TEXT DEFAULT NULL,
  PRIMARY KEY (`intSettingID`)
);


CREATE TABLE `tblAzureCerts` (
  `intCertID` INT NOT NULL AUTO_INCREMENT,
  `txtAppID` VARCHAR(255) NOT NULL,
  `txtKeyID` VARCHAR(255) NULL,
  `txtNotes` TEXT NULL,
  `intMuteAlert` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`intCertID`));


INSERT INTO `tblCertTypes` (`intTypeID`, `txtName`) VALUES ('1', 'SSL Certificate');
INSERT INTO `tblCertTypes` (`intTypeID`, `txtName`) VALUES ('2', 'Azure AD App Reg Secret');
INSERT INTO `tblCertTypes` (`intTypeID`, `txtName`) VALUES ('3', 'Azure AD App Reg Certificate');
INSERT INTO `tblCertTypes` (`intTypeID`, `txtName`) VALUES ('4', 'Other');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('alertDays', '14');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('senderEmail', '');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('alertFrequency', '7');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('emailAlerts', '1');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('listItems', '25');
INSERT INTO `tblSettings` (`txtName`, `txtValue`) VALUES ('alertTemplate', '<html>
<head>
<style>
* { font-family: "Segoe UI", Calibri, sans; }
thead tr td { font-weight: bold; }
</style>
</head>
<body>
<p><b>Expiring Certificates and Secrets Notification</b></p>
The following certificates and secrets are due to expire soon!<br>
{$data}
<br><br>
There were {$alertMuted} muted alerts.<br>
Alert threshold is set to: {$alertDays} days
<br><br>
You are receiving this because you were configured as an alert recipient on the Certificate Expiry Notification system at {$url}.<br>
</body>
</html>');


