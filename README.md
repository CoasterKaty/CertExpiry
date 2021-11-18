# CertExpiry

Katy Nicholson

https://katystech.blog/

Setup instructions etc available at: https://katystech.blog/2021/11/certexpiry/


PHP project for tracking Azure AD App Registration client secrets due to expire, and (manually) tracking SSL certificates

Project aims:

- Azure AD based logon
- Automatically show app reg client secrets in tenant with expiry date
- Manually add SSL certificate details with renewal dates
- Highlight/sort by date order, showing those due to expire soonest at the top
- Email based alerts near expiry


API permissions: Application.Read.All
Directory.Read.All
DeviceManagementServiceConfig.Read.All
Mail.Send
Mail.ReadWrite
(Restrict the mail further with New-ApplicationAccessPolicy -AppId clientID -PolicyScopeGroupId mailbox@domain.com -AccessRight RestrictAccess)

Note: Requires the PHP curl extensions
