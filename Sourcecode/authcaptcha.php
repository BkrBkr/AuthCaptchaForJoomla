<?php

/**
 * @Copyright
 * @package        JoomlaAuthCaptcha
 * @author         Björn Kremer 
 *
 * @license        GNU/GPL
 *	 JoomlaAuthCaptcha (OR AuthCaptcha) - This Joomla System Plugin adds a captcha challenge to all joomla login-forms
 *   Copyright (C) 2019 Björn Kremer
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

class PlgSystemAuthCaptcha extends JPlugin
{
	private $app;
	private $dispatcher;
	private $request;
	private $config;
	protected $autoloadLanguage = true;



	function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->app = JFactory::getApplication();
		$this->request = $this->app->input;
		$this->dispatcher = JDispatcher::getInstance();
		$this->config = JFactory::getConfig();
	}

	/**
	 * Does the current page requires a captcha challenge?
	 */
	private function getCaptchaPluginIncludeRequired()
	{
		if ($this->config->get('captcha') == '0')
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
		return JModuleHelper::isEnabled("mod_login");
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
			JPluginHelper::importPlugin('captcha');
			$this->dispatcher->trigger('onInit', 'dynamic_captcha_loginform');

			$option = $this->request->getCmd('option');
			$task = $this->request->getCmd('task');

			if (($option == 'com_users' && $task == 'user.login') || ($option == 'com_login' && $task == 'login')) {

				try {
					$res = $this->dispatcher->trigger("onCheckAnswer");
					if (empty($res) || empty($res[0]) || !$res[0]) {
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

		$this->app->enqueueMessage(JText::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'), 'error');

		$this->app->redirect(JUri::current());
		jexit(JText::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'));
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

		$moduleRegex = "/(?<match>(?<=<div)[^>]+form-login-password.*?<\/div>\s*<\/div>\s*<\/div>)/si";
		$praefix = "\n<div id=\"form-login-captcha\" class=\"control-group\">\n<div class=\"controls\">\n<div class=\"input-prepend\">\n";
		$suffix = "\n</div>\n</div>\n</div>\n";
		$scale = 0.58;

		return $this->addCaptcha($moduleRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}


	/**
	 *  Adds the captcha control to the login component form
	 */
	private function matchLoginComponent($formBody, $offset, &$body)
	{

		$componentRegex = "/(?<match>(?<=<div)[^>]+control-group.*?<\/div>\s*<\s*div[^>]+>\s*<\s*input[^>]+type=\"password\"[^>]+>\s*<\/div>\s*<\/div>)/si";
		$praefix = "\n<div class=\"control-group\">\n<div class=\"control-label\"></div>\n<div class=\"controls\">\n";
		$suffix = "\n</div>\n</div>\n";
		$scale = 0.73;

		return $this->addCaptcha($componentRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
	}

	/**
	 *  Adds the captcha control to the administrator login form
	 */
	private function matchLoginAdmin($formBody, $offset, &$body)
	{

		$adminRegex = "/(?<match>(?<=<div)[^>]+control-group.*?[^>]+>.*?<input[^>]+type=\"password\"[^>]+>.*?<\/div>\s*<\/div>\s*<\/div>)/si";
		$praefix = "\n<div class=\"control-group\">\n<div class=\"controls\">\n<div class=\"input-prepend input-append\">\n";
		$suffix = "\n</div>\n</div>\n</div>\n";
		$scale = 0.87;

		return $this->addCaptcha($adminRegex, $formBody, $offset, $body, $praefix, $suffix, $scale);
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
	private function addCaptcha($regex, $formBody, $offset, &$body, $praefix, $suffix, $scale)
	{
		if (preg_match($regex, $formBody, $match, PREG_OFFSET_CAPTURE)) {

			$position = strlen($match["match"][0]) + $match["match"][1];

			$captcha = $this->getCaptcha($scale);
			$body = substr_replace($body, $praefix . $captcha . $suffix, $position + $offset, 0);
			return true;
		}
		return false;
	}

	/**
	 * Generates the captcha control 
	 */
	private function getCaptcha($scale)
	{
		$captcha = $this->dispatcher->trigger('onDisplay', array(null, 'dynamic_captcha_loginform', 'required'));
		if (empty($captcha) || empty($captcha[0]))
			throw new UnexpectedValueException('Captcha generation failed');

		if (strpos($captcha[0], "g-recaptcha") !== false && strpos($captcha[0], "invisible") === false) {
			$captcha[0] = str_replace("></div>", ' style="transform:scale(' . $scale . ');-webkit-transform:scale(' . $scale . ');transform-origin:0 0;-webkit-transform-origin:0 0;max-width:100px;max-height:80px;"></div>', $captcha[0]);
		}
		return $captcha[0];
	}
}
