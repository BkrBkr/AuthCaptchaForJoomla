# JoomlaAuthCaptcha
A BruteForce attack is a major security risk to any website. In this type of attack, a hacker tries to guess a user's password with millions of requests. Especially weak passwords are vulnerable to this type of attack. In addition, a BruteForce attack can cause availability issues. 

Joomla has no built-in BruteForce protection. This Joomla System plugin fills this gap by adding a captcha challenge to all Joomla login forms. It slows down the attack considerably and therefore prevents it.


* It works with the default Joomla login-component, the default Joomla login-module and the administrator page. 
* Tested with the recaptcha-plugins shipped by Joomla.

If you want to use other common login components or other common captcha plugins, you are welcome to open an issue. If you have problems with common templates, please also open an issue. (Only free and open source components, ....)

Joomla 4 ready!

# Installation

1. [Configure and enable a captcha-plugin in Joomla (e.g. recaptcha)](https://docs.joomla.org/J3.x:Google_ReCaptcha)
2. Install and enable this plugin.
3. Clear Joomla cache if enabled

# Troubleshooting

**The captcha is not working and I can't login.**

You can disable the plugin via the database. Please refer to the Joomla documentation or google.

# Disclaimer
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
