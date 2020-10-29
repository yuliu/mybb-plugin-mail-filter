# Mail Filter
A very primitive MyBB plugin for filtering mails sending from MyBB.

## Features
It's a plugin with magic if you don't know what it does.

## Requirements

- MyBB >= 1.8.25 _(?)_ or 1.8.x with [this `my_mail()` extension to MyBB](https://github.com/yuliu/mybb-mybb/tree/pull-4135-mailhandler-hook)
- PHP >= 5.6 _(?)_

## Version & upgrade notice

The initial version is `0.1.x`. **It's only for test at this moment.**

For detailed install/upgrade/re-install/uninstall notice, please read the [Special Notice](UPGRADE.md).

## Installation

1. Upload all files/folders under the `Upload` directory to your MyBB root folder. Please maintain the folder structure within the `Upload` directory.
2. Activate plugin "Mail Filter" at MyBB's AdminCP > Configuration > Plugins.

## Uninstall

1. Turn to MyBB's AdminCP > Configuration > Plugins, find **Mail Filter** and deactivate it.
2. Remove file `./inc/plugins/mail_filter.php` and folder `./inc/plugins/mail_filter/` from your server.

## Hooks

Currently, the plugin doesn't provide any hook.

## TODO

- Language packs.
- Integrate [egulias's EmailValidator](https://github.com/egulias/EmailValidator).
- Hook to MyBB user registration so that it may detect some spoofed email addresses.
- The ability of detecting if any mailing is failed.
- Hooks or options would be added so that when a mail is blocked from being sent from MyBB, a PM notification will be sent to admins and/or the mail's sending user, with the cause of being blocked.
- Options would be added so that if mailing to an email address failed for several times, add this address to the (auto-)blocklist.
- Some interesting and convenient way of adding email addresses to the blocklist.



## License
See the [LICENSE](https://github.com/yuliu/mybb-plugin-mail-filter/blob/master/LICENSE) file.

## Huh?
...