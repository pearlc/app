<?php
/**
 * Internationalisation file for API Gate library.
 *
 * Currently, this is meant to be included by a MediaWiki internationalization file, so
 * the format is designed to be compatible with that.
 */

$messages = array();

/** English
 *
 */
$messages['en'] = array(
	'apigate' => 'API Gate',
	'apigate-mysql-error' => 'There was a db error with query<br/>\"$1\"<br/><br/>Error was: \"$2\"',
	
	// Intro module on the main API Gate landing page
	'apigate-intro-header' => 'Welcome to the API Control Panel, $1!',
	'apigate-intro' => 'You will always be able to access this page by logging into your Wikia account and selecting "API Control Panel" in the user drop-down menu on the top, right corner of any page on Wikia or by going to api.wikia.com/Special:ApiGate.',

	// Registration module
	'apigate-register-title' => 'Get an API Key',
	'apigate-register-heading' => 'Get an API Key',
	'apigate-register-name' => 'Name*',
	'apigate-register-hint-firstname' => 'First',
	'apigate-register-hint-lastname' => 'Last',
	'apigate-register-email' => 'Email*',
	'apigate-register-hint-email' => 'Email address',
	'apigate-register-hint-confirm-email' => 'Confirm email',
	'apigate-register-submit' => 'Submit',
	'apigate-register-implicit-agreement' => 'By clicking the button, you agree to the Terms of Service.',
	'apigate-register-error-noname' => 'Please enter your name.',
	'apigate-register-error-invalid-email' => 'Please enter a valid email address.',
	'apigate-register-error-email-doesnt-match' => 'Email addresses entered do not match.',
	'apigate-register-error-mysql_error' => 'Error trying to save new API key to the database.',
	
	// For the module which lists all keys for a user
	'apigate-userkeys-intro' => 'Click on your keys below to view usage statistics for each key or edit your contact information (in case something goes wrong).',
	'apigate-userkeys-footer' => 'If you need another key and want to track it in this account, please <a href="$1">create another key</a> while you are logged in to this account.<br/><br/>If you have any questions regarding our APIs, please contact us at $2. We would be happy to help out.',
	'apigate-userkeys-disabled' => 'disabled',
	
	// Info page for an individual API key
	'apigate-keyinfo-apiKey' => 'API Key: <strong>$1</strong>',
	'apigate-keyinfo-status' => 'Status: $1',
	'apigate-keyinfo-status-enabled' => 'Active',
	'apigate-keyinfo-status-disabled' => 'Disabled',
	'apigate-keyinfo-nickname' => 'Key Nickname',
	'apigate-keyinfo-hint-nickname' => 'Name of application',
	'apigate-keyinfo-name' => 'Name',
	'apigate-keyinfo-email' => 'Email',
	'apigate-keyinfo-submit' => 'Update',

	// Admin-links module
	'apigate-adminlinks-header' => 'Administrator Links',
	'apigate-adminlinks-viewkeys' => 'View table of each individual key',
	'apigate-adminlinks-viewaggregate' => 'View aggregate stats',

	// Module which lists all keys in the system (visible to Admins)
	'apigate-listkeys-th-keyname' => 'Key Nickname',
	'apigate-listkeys-th-apikey' => 'API Key',
	'apigate-listkeys-th-username' => 'Username',
	'apigate-listkeys-th-disabled' => 'Disabled',
);

/** Message documentation (Message documentation)
 *
 */
$messages['qqq'] = array(
	'apigate' => 'The name of the system. Used anywhere branding is needed.',
	'apigate-intro' => 'This description is meant to be the first thing a user sees when they hit the control-panel page.',
	'apigate-register-title' => "The title tag of the registration page.",
	'apigate-register-heading' => "The top-level heading of the registration page.",
	'apigate-register-name' => 'Heading of the Name fields in the registration form. Asterisk is to indicate that it is required.',
	'apigate-register-hint-firstname' => 'HTML 5 Placeholder text in the text field for first name',
	'apigate-register-hint-lastname' => 'HTML 5 Placeholder text in the text field for last name (surname)',
	'apigate-register-email' => 'Heading of the Email field of the registration page. Asterisk is to indicate that it is required.',
	'apigate-register-submit' => 'Text on the submit button on the key-registration form.',
);
