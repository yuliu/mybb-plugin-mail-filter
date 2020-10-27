<?php
/**
 * MyBB 1.8 plugin: Mail Filter
 * Website: https://github.com/yuliu/mybb-plugin-mail-filter
 * License: https://github.com/yuliu/mybb-plugin-mail-filter/blob/master/LICENSE
 * Copyright Yu 'noyle' Liu, All Rights Reserved
 */

namespace Noyle\MyBBPlugin\MailFilter;

class EmailValidator
{
	public const VALIDATOR_RFC_PHP = 1;

	public const VALIDATOR_RFC = 2;

	// All warnings will be regarded as errors in _NORFCWARNING mode.
	public const VALIDATOR_NORFCWARNING = 4;

	public const VALIDATOR_DNSCHECK = 8;

	public const VALIDATOR_SPOOFCHECK = 16;

	public const VALIDATOR_BLOCKLIST = 32;

	public const VALIDATOR_ALL = self::VALIDATOR_RFC + self::VALIDATOR_DNSCHECK + self::VALIDATOR_SPOOFCHECK;

	public const VALIDATOR_ALL_NORFCWARNING = self::VALIDATOR_NORFCWARNING + self::VALIDATOR_DNSCHECK + self::VALIDATOR_SPOOFCHECK;

	private $blocklist = array(
		'domains' => array(),
		'emails' => array(),
		'regexp' => array(),
	);

	public function load_blocklist_from_file($file)
	{
		if(!file_exists($file))
		{
			return false;
		}
		$entries = file_get_contents($file);
		if($entries !== false)
		{
			$entries = str_replace("\r", "\n", $entries);
			$entries = array_unique(array_filter(explode("\n", $entries)));
			return $entries;
		}
		else
		{
			return false;
		}
	}

	public function __construct()
	{
		$BLOCK_LIST_FILE = dirname(__FILE__).'/blocklist.txt';
		$entries = $this->load_blocklist_from_file($BLOCK_LIST_FILE);
		if(is_array($entries))
		{
			foreach($entries as $entry)
			{
				if(!empty($entry))
				{
					// Comment line.
					if($entry[0] == '#')
					{
						continue;
					}
					// A domain starts with an '@'.
					if($entry[0] == '@')
					{
						$this->blocklist['domains'][] = substr($entry, 1);
					}
					// A domain without an '@'.
					else if(strpos($entry,'@') === false)
					{
						$this->blocklist['domains'][] = $entry;
					}
					// An email address.
					else
					{
						$this->blocklist['emails'][] = $entry;
					}
				}
			}
		}
	}

	public function is_valid($email, $selected_validators = self::VALIDATOR_RFC_PHP | self::VALIDATOR_BLOCKLIST)
	{
		// If the email address is a valid one.
		$is_valid = false;

		// If the address is in our blocklist.
		$is_blocked = false;

		if(($selected_validators & self::VALIDATOR_RFC_PHP) && filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			$is_valid = true;
		}

		if($is_valid && ($selected_validators & self::VALIDATOR_BLOCKLIST))
		{
			// Check the domain part.
			$email_domain = substr($email, strpos($email,'@') + 1);
			if(in_array($email_domain, $this->blocklist['domains']))
			{
				$is_blocked = true;
			}

			// Check the whole part.
			if(!$is_blocked && in_array($email, $this->blocklist['emails']))
			{
				$is_blocked = true;
			}
		}

		// It's a valid email and isn't blocked.
		if($is_valid && !$is_blocked)
		{
			return true;
		}
		// Otherwise, no!
		else
		{
			return false;
		}
	}
}