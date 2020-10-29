<?php
/**
 * MyBB 1.8 plugin: Mail Filter
 * Website: https://github.com/yuliu/mybb-plugin-mail-filter
 * License: https://github.com/yuliu/mybb-plugin-mail-filter/blob/master/LICENSE
 * Copyright Yu 'noyle' Liu, All Rights Reserved
 */

// Make sure we can't access this file directly from the browser.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

define('PLUGIN_MAIL_FILTER_FOLDER', dirname(__FILE__).'/mail_filter');
require_once PLUGIN_MAIL_FILTER_FOLDER . '/email_validator.php';

use Noyle\MyBBPlugin\MailFilter;

/**
 * Do not modify constants below.
 */
define('PLUGIN_MAIL_FILTER_LOGGING', 1);
define('PLUGIN_MAIL_FILTER_VALIDATIONS', MailFilter\EmailValidator::VALIDATOR_RFC_PHP | MailFilter\EmailValidator::VALIDATOR_BLOCKLIST);
define('PLUGIN_MAIL_FILTER_FORCE_MAIL_FROM', 0);
define('PLUGIN_MAIL_FILTER_FORCE_MAIL_FROM_EMAIL', "example@example.com");
define('PLUGIN_MAIL_FILTER_REROUTE_ALL_MAIL_TO', 0);
define('PLUGIN_MAIL_FILTER_REROUTE_ALL_MAIL_TO_EMAIL', "example@example.com");

/**
 * Functions for MyBB integration.
 */

function mail_filter_info()
{
	return array(
		'name'          => 'Mail Filter',
		'description'   => 'A very primitive MyBB plugin for filtering mails sending from MyBB.',
		'website'       => 'https://github.com/yuliu/mybb-plugin-mail-filter',
		'author'        => 'Yu \'noyle\' Liu',
		'authorsite'    => 'https://github.com/yuliu/mybb-plugin-mail-filter',
		'version'       => '0.1.1',
		'compatibility' => '18*, 1825, 1826, 1827, 1828, 1829, 183*, 184*, 185*',
		'codename'      => 'noyle_mail_filter',
	);
}

/**
 * Manually add some MyBB settings, temporarily.
 */
global $mybb;
if(isset($mybb->settings))
{
	$mybb->settings['noyle_mail_filter_logging'] = PLUGIN_MAIL_FILTER_LOGGING;
	$mybb->settings['noyle_mail_filter_validation_methods'] = PLUGIN_MAIL_FILTER_VALIDATIONS;
	$mybb->settings['noyle_mail_filter_force_mail_from'] = PLUGIN_MAIL_FILTER_FORCE_MAIL_FROM;
	$mybb->settings['noyle_mail_filter_force_mail_from_email'] = PLUGIN_MAIL_FILTER_FORCE_MAIL_FROM_EMAIL;
	$mybb->settings['noyle_mail_filter_reroute_all_mail_to'] = PLUGIN_MAIL_FILTER_REROUTE_ALL_MAIL_TO;
	$mybb->settings['noyle_mail_filter_reroute_all_mail_to_email'] = PLUGIN_MAIL_FILTER_REROUTE_ALL_MAIL_TO_EMAIL;
}

/**
 * Hooks to MyBB.
 */

// Hook to the mail parameter part so that we could re-assign some parameter such as the from/to email addresses, etc.
$plugins->add_hook('my_mail_parameters', 'mail_filter_hook_to_my_mail_parameters');
function mail_filter_hook_to_my_mail_parameters($my_mail_parameters)
{
	global $mybb;

	if($mybb->settings['noyle_mail_filter_force_mail_from'])
	{
		$my_mail_parameters['from'] = $mybb->settings['noyle_mail_filter_force_mail_from_email'];
	}

	if($mybb->settings['noyle_mail_filter_reroute_all_mail_to'])
	{
		$my_mail_parameters['to'] = $mybb->settings['noyle_mail_filter_reroute_all_mail_to_email'];
	}

	return $my_mail_parameters;
}

// Hook to the mail send part so that we may rule some mails out. We hope to make this plugin light weight so we won't bother building a mail handler ourselves.
$plugins->add_hook('my_mail_send', 'mail_filter_hook_to_my_mail_send');
function mail_filter_hook_to_my_mail_send($my_mail_parameters)
{
	global $mybb;
	static $email_validator;

	if(!($email_validator instanceof MailFilter\EmailValidator))
	{
		$email_validator = new MailFilter\EmailValidator();
	}

	$sender = $my_mail_parameters['from'];
	$receiver = $my_mail_parameters['to'];
	$is_blocked = ! $email_validator->is_valid($receiver, $mybb->settings['noyle_mail_filter_validation_methods']);

	if($is_blocked)
	{
		// Log the message if it's blocked by our plugin.
		// Currently we mark it as a contact email log entry because MyBB's AdminCP can't display other mail log types.
		if($mybb->settings['noyle_mail_filter_logging'])
		{
			global $db;
			if(empty($sender))
			{
				global $mybb;
				$sender = $mybb->settings['adminemail'];
			}
			$log_entry = array(
				"subject"   => $db->escape_string("Email wasn't sent (invalid/blocklisted addr.)"),
				"message"   => $db->escape_string("Original Email Subject:\n=========================\n" . $my_mail_parameters['subject'] . "\n\n\nOriginal Email Message:\n=========================\n" . $my_mail_parameters['message']),
				"dateline"  => time(),
				"fromuid"   => 0,
				"fromemail" => $db->escape_string($sender),
				"touid"     => 0,
				"toemail"   => $db->escape_string($receiver),
				"tid"       => 0,
				"ipaddress" => $db->escape_binary(my_inet_pton('fe80::1')),
				"type"      => 3,
			);
			$db->insert_query("maillogs", $log_entry);
		}

		// Actually we should return false. Here we return true due to MyBB hook's restriction.
		return true;
	}
}
