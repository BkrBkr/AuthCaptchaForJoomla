# AuthCaptcha for Joomla
A BruteForce attack is a major security risk to any website. In this type of attack, a hacker tries to guess a user's password with millions of requests. Especially weak passwords are vulnerable to this type of attack. In addition, a BruteForce attack can cause availability issues. 

Joomla has no built-in BruteForce protection. This Joomla System plugin fills this gap by adding a captcha challenge to all Joomla login forms. It slows down the attack considerably and therefore prevents it.


* It works with the default Joomla login-component, the default Joomla login-module and on the administrator page. 
* Tested with the recaptcha-plugin shipped by Joomla.

Joomla 4 ready! **Joomla 3 support will end on 01.01.2023.**

# Installation

1. [Configure and enable a captcha-plugin in Joomla (e.g. recaptcha)](https://docs.joomla.org/J3.x:Google_ReCaptcha)
2. Install and enable this plugin.
3. Clear Joomla cache if enabled

## Custom Captcha position
You can customize the position of the captcha. Just add the placeholder {authCaptchaPlaceholder} at the desired position. It has to be placed within the html \<form> tag of the login form.


# Troubleshooting

**The captcha is not working and I can't login.**

You can disable the plugin via the database. Please refer to the Joomla documentation or google.


# Support

Please open an issue.
Please understand that I can't support layout problems of custom Joomla designs.

# Disclaimer
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
