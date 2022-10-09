<?php

/**
 * @Copyright
 * @package        AuthCaptchaForJoomla
 * @author         Björn Kremer 
 *
 * @license        GNU/GPL
 *	 AuthCaptcha for Joomla (OR short AuthCaptcha) - Is a Joomla system plugin that adds a captcha challenge to all joomla login-forms to prevent bruteforce attacks
 *   Copyright (C) 2021 Björn Kremer
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\CMS\Captcha\Captcha;

class PlgSystemAuthCaptcha extends JPlugin
{
	private $app;
	private $request;
	private $config;
	protected $autoloadLanguage = true;
	private $version;
	private $captcha;


	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->app = Factory::getApplication();
		$this->request = $this->app->input;
		$this->config = Factory::getConfig();
		$this->version = new Version();
	}

	/**
	 * Does the current page requires a captcha challenge?
	 */
	private function getCaptchaPluginIncludeRequired()
	{
		$captchaConfig = $this->config->get('captcha');
		if (empty($captchaConfig) || $captchaConfig == '0' || $captchaConfig === 0)
			return false;

		if (!PluginHelper::isEnabled("captcha", $captchaConfig))
			return false;

		$option = $this->request->getCmd('option');
		$view = $this->request->getCmd('view');

		return  $this->isModLogin() || $this->isUserLogin($option, $view) || $this->isAdminLogin($option, $view);
	}

	/**
	 * Is the login-module enabled?
	 */
	private function isModLogin()
	{
		return ModuleHelper::isEnabled("mod_login");
	}

	/**
	 * Is the current page the login component?
	 */
	private function isUserLogin($option, $view)
	{
		return ($option == "com_users" && $view == "login");
	}

	/**
	 * Is the current page the administrator login page?
	 */
	private function isAdminLogin($option, $view)
	{
		return ($option == "com_login" && $view == "login");
	}


	/**
	 * - Initialize the captcha plugin
	 * - Validate the captcha response if necessary
	 */
	public function onAfterRoute()
	{

		if ($this->getCaptchaPluginIncludeRequired()) {
			$this->loadLanguage();
			$this->app->loadDocument();
			$captcha = $this->getCaptcha();
			$captcha->initialise('authCaptcha');

			$option = $this->request->getCmd('option');
			$task = $this->request->getCmd('task');
			
			if (($option == 'com_users' && $task == 'user.login') || ($option == 'com_login' && $task == 'login')) {
				try {
					$res = $captcha->checkAnswer($this->request->get('authCaptcha', null, 'RAW'));
					if ($res !== true) {
						$this->redirect();
					}
				} catch (Exception $e) {
					$this->redirect();
				}
			}
		}
	}

	/**
	 * Cancel the request if the captcha challenge failed.
	 * Reload the page and display an error message
	 */
	private function redirect()
	{

		$this->app->enqueueMessage(Text::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'), 'error');

		$this->app->redirect(Uri::current());
		jexit(Text::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'));
		die("Invalid Captcha"); //Should not be reached
	}

	/**
	 * Adds the captcha control to all available login forms
	 */
	public function onAfterRender()
	{

		if ($this->getCaptchaPluginIncludeRequired()) {

			$body = $this->app->getBody();

		
		
			$formRegex = "/(?<=<form)(?<formAll>[^>]+>(?<formBody>.*?))(?=<\/\s*form\s*>)/si";
			$passwordFieldRegex = "/<\s*input[^>]+type\s*=[\"']+password/si";
			if (preg_match_all($formRegex, $body, $matches, PREG_OFFSET_CAPTURE)) {

				if (count($matches["formAll"]) > 0) {
					for ($idx = count($matches["formAll"]) - 1; $idx >= 0; $idx--) {

						$formAll =  $matches["formAll"][$idx];

						if (!empty($formAll[0]) && preg_match($passwordFieldRegex, $formAll[0]) && (strpos($formAll[0], "user.login") !== false || strpos($formAll[0], "com_login") !== false)) {

							$offset = $matches["formBody"][$idx][1];
							$formBody = $matches["formBody"][$idx][0];

							$this->matchLoginPlaceholder($formBody, $offset, $body) ||
								$this->matchLoginModule($formBody, $offset, $body) ||
								$this->matchLoginComponent($formBody, $offset, $body) ||
								$this->matchLoginAdmin($formBody, $offset, $body) ||
								$this->matchLoginFallback($formBody, $offset, $body);
						}
					}
					$this->app->setBody($body);
				}
			}
		}
	}

	/**
	 * Adds the captcha control to the login modul form
	 */
	private function matchLoginModule($formBody, $offset, &$body)
	{
		if ($this->version::MAJOR_VERSION == 4) {
			$moduleRegex = "/(?<=<div)(?<match>[^>]+mod-login__password[^>]+>.*?(?=<\/\s*div\s*>)\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div class=\"mod-login__captcha form-group\">\n<div class=\"input-group\">\n";
			$suffix = "\n</div>\n</div>\n";
			$scale = 0.65;
		} else {
			$moduleRegex = "/(?<match>(?<=<div)[^>]+form-login-password.*?<\/div>\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div id=\"form-login-captcha\" class=\"control-group\">\n<div class=\"controls\">\n<div class=\"input-prepend\">\n";
			$suffix = "\n</div>\n</div>\n</div>\n";
			$scale = 0.58;
		}

		return $this->addCaptcha($moduleRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}


	/**
	 *  Adds the captcha control to the login component form
	 */
	private function matchLoginComponent($formBody, $offset, &$body)
	{
		if ($this->version::MAJOR_VERSION == 4) {
			$componentRegex = "/(?<match>(?<=<div)[^>]+control-group.*?[^>]+>.*?<input[^>]+type=\"password\"[^>]+>.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div class=\"com-users-login__input control-group\">\n<div class=\"controls\"><div class=\"captcha-group\">\n<div class=\"input-group\">\n";
			$suffix = "\n</div>\n</div>\n</div>\n";
			$scale = 0.79;
		} else {
			$componentRegex = "/(?<match>(?<=<div)[^>]+control-group.*?<\/div>\s*<\s*div[^>]+>\s*<\s*input[^>]+type=\"password\"[^>]+>\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div class=\"control-group\">\n<div class=\"control-label\"></div>\n<div class=\"controls\">\n";
			$suffix = "\n</div>\n</div>\n";
			$scale = 0.73;
		}

		return $this->addCaptcha($componentRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}

	/**
	 *  Adds the captcha control to the administrator login form
	 */
	private function matchLoginAdmin($formBody, $offset, &$body)
	{
		if ($this->version::MAJOR_VERSION == 4) {
			$adminRegex = "/(?<match><input\s*name=\"passwd\"\s*id=\"mod-login-password\"[^>]+>\s*<button.*?<\/button>\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div class=\"form-group\">\n<div class=\"input-group\">\n";
			$suffix = "\n</div>\n</div>\n";
			$scale = 1.21;
		} else {
			$adminRegex = "/(?<match>(?<=<div)[^>]+control-group.*?[^>]+>.*?<input[^>]+type=\"password\"[^>]+>.*?<\/div>\s*<\/div>\s*<\/div>)/si";
			$praefix = "\n<div class=\"control-group\">\n<div class=\"controls\">\n<div class=\"input-prepend input-append\">\n";
			$suffix = "\n</div>\n</div>\n</div>\n";
			$scale = 0.87;
		}

		return $this->addCaptcha($adminRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}

	/**
	 *  Adds the captcha control by placeholder
	 */
	private function matchLoginPlaceholder($formBody, $offset, &$body)
	{
		
		$adminRegex = '/(?<match>{authCaptchaPlaceholder})/si';
		$praefix = "";
		$suffix = "";
		$scale = null;
		
		return $this->addCaptcha($adminRegex, $formBody, $offset, $body, $praefix, $suffix, $scale,strlen('{authCaptchaPlaceholder}'));
	}
	
	/**
	 * Login-Fallback
	 */
	private function matchLoginFallback($formBody, $offset, &$body)
	{
		$adminRegex = "/(?<match>^)/si";
		$praefix = "";
		$suffix = "";
		$scale = 0.7;

		return $this->addCaptcha($adminRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}

	/**
	 * Adds a captcha challenge to $body at the position specified by $regex and $offset
	 */
	private function addCaptcha($regex, $formBody, $offset, &$body, $praefix, $suffix, $scale,$replaceLen=0)
	{
		if (preg_match($regex, $formBody, $match, PREG_OFFSET_CAPTURE)) {
			
			$position = strlen($match["match"][0]) + $match["match"][1] -$replaceLen;
			$captcha = $this->getCaptchaHtml($scale);
			$body = substr_replace($body, $praefix . $captcha . $suffix, $position + $offset,$replaceLen);

			return true;
		}
		return false;
	}

	/**
	 * Returns an instance of Joomla\CMS\Captcha\Captcha
	 */
	private function getCaptcha()
	{
		if ($this->captcha === null)
		{
			$this->captcha = Captcha::getInstance($this->config->get('captcha'));
		}
		
		return $this->captcha;
	}

	/**
	 * Generates the captcha control 
	 */
	private function getCaptchaHtml($scale)
	{
		$captcha = $this->getCaptcha()->display('authCaptcha', 'authCaptcha');

		if (empty($captcha))
			throw new UnexpectedValueException('Captcha generation failed');

		if (strpos($captcha, "g-recaptcha") !== false && strpos($captcha, "invisible") === false && $scale != null) {
			$captcha = str_replace("></div>", ' style="transform:scale(' . $scale . ');-webkit-transform:scale(' . $scale . ');transform-origin:0 0;-webkit-transform-origin:0 0;max-width:100px;max-height:80px;"></div>', $captcha);
		}
		return $captcha;
	}
}
