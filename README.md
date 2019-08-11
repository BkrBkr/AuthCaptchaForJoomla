# JoomlaAuthCaptcha
A BruteForce attack is a major security risk to any website. In this type of attack, a hacker tries to guess a user's password with millions of requests. Especially weak passwords are vulnerable to this type of attack. In addition, a BruteForce attack can cause availability issues. 

Joomla has no built-in BruteForce protection. This Joomla System plugin fills this gap by adding a captcha challenge to all Joomla login forms. It slows down the attack considerably and therefore prevents it.


* It works with the default Joomla login-component, the default Joomla login-module and the administrator page. 
* Tested with the recaptcha-plugins shipped by Google.

If you'd like to use other common login-components or other common captcha-plugins please feel free to open an issue. If you have problems with common templates please open an issue, too. (Only free and open source components, ...)

Warning: This Plugin is an alpha version and at the moment not recommended for productive use.

# Installation

1. Enable a captcha-plugin in Joomla (e.g. recaptcha)

   For details about joomla captcha plugins, please refer to the Joomla documentation or google.
2. Install and enable this plugin.

# Troubleshooting

**The captcha is not working and I can't login.**

You can disable the plugin via the database. Please refer to the Joomla documentation or google.

# Todo
This Plugin is an alpha version. Open Todos:

1. Add code comments
2. Cross-Browser tests
3. Tests

# Disclaimer
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
