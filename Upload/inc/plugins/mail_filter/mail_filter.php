<?php
/**
 * MyBB 1.8 plugin: Mail Filter
 * Website: https://github.com/yuliu/mybb-plugin-mail-filter
 * License: https://github.com/yuliu/mybb-plugin-mail-filter/blob/master/LICENSE
 * Copyright Yu 'noyle' Liu, All Rights Reserved
 */

namespace Noyle\MyBBPlugin\MailFilter;

defined('PLUGIN_MAIL_FILTER_FOLDER') or define('PLUGIN_MAIL_FILTER_FOLDER', dirname(__FILE__));
require_once PLUGIN_MAIL_FILTER_FOLDER . '/email_validator.php';

/**
 * Alter parameters of the function my_mail().
 *
 * @param $my_mail_parameters array The parameter is an associative array containing compacted variables $to, $subject, $message, $from, $charset, $headers, $keep_alive, $format, $message_text, $return_email that are parameters of the function my_mail(), and $is_mail_sent, $continue_process that are used for hook processing.
 *
 * @return array The input array that may or may not have been modified by the function.
 */
function mail_filter_hook_to_my_mail_pre_build_message($my_mail_parameters)
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

/**
 * Hooks to 'my_mail_send' so that this function will tell MyBB to send the mail or not.
 *
 * @param $my_mail_parameters array The parameter is an associative array containing compacted variables $to, $subject, $message, $from, $charset, $headers, $keep_alive, $format, $message_text, $return_email that are parameters of the function my_mail(), and $is_mail_sent, $continue_process that are used for hook processing.
 *
 * @return array The input array that may or may not have been modified by the function.
 */
function mail_filter_hook_to_my_mail_pre_send($my_mail_parameters)
{
	// If the mail has already been sent.
	$is_mail_sent = (bool)$my_mail_parameters['is_mail_sent'];
	// Do we want to continue process?
	$continue_process = (bool)$my_mail_parameters['continue_process'];

	if($is_mail_sent || !$continue_process)
	{
		return $my_mail_parameters;
	}

	global $mybb;
	static $email_validator;

	// Get our validator.
	if(!($email_validator instanceof EmailValidator\EmailValidator))
	{
		$email_validator = new EmailValidator\EmailValidator();
	}

	// The sender's address.
	$sender = $my_mail_parameters['from'];
	// The receiver's address.
	$receiver = $my_mail_parameters['to'];

	// Test if the receiver's address is invalid or blocked by us.
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
			// TODO: language pack.
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

		// We didn't send the mail.
		$my_mail_parameters['is_mail_sent'] = false;
		// And we don't suggest to continue process the mail.
		$my_mail_parameters['continue_process'] = false;
	}

	return $my_mail_parameters;
}
