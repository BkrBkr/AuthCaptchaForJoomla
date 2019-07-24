<?php
/**
 * @Copyright
 * @package        JoomlaAuthCaptcha
 * @author         Björn Kremer
 * @version        0.0.1
 *
 * @license        GNU/GPL
 *	 JoomlaAuthCaptcha (OR AuthCaptcha) - This Joomla System Plugin adds a captcha challenge to all joomla login-forms
 *   Copyright (C) <year>  <name of author>
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
defined('_JEXEC') || die('Restricted access');

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
		$this->dispatcher=JDispatcher::getInstance();
		$this->config=JFactory::getConfig();
	}
	
	private function getCaptchaPluginIncludeRequired(){
		if($this->config->get('captcha')=='0')
			return false;
		
		$option = $this->request->getCmd('option');
        $view = $this->request->getCmd('view');
		
		return  $this->isModLogin() || $this->isUserLogin($option, $view) || $this->isAdminLogin($option, $view);
	}
	
	private function isModLogin(){
		return JModuleHelper::isEnabled("mod_login");
	}
	
	private function isUserLogin($option, $view){
		return ($option=="com_users" && $view=="login");
	}
	
	private function isAdminLogin($option, $view){
		return  ($option=="com_login" && $view=="login");
	}

    public function onAfterRoute()
    {
	
		if(!empty($this->request->getCmd("captchaError")))
			$this->app->enqueueMessage(JText::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'), 'error');
		
		if($this->getCaptchaPluginIncludeRequired()){
			JPluginHelper::importPlugin('captcha');
			$this->dispatcher->trigger('onInit', 'dynamic_captcha_loginform');

			$option = $this->request->getCmd('option');
			$task = $this->request->getCmd('task');
			
			if (($option == 'com_users' && $task == 'user.login') || ($option == 'com_login' && $task == 'login')) {
	
				$captchaResp=$this->request->getCmd('g-captcha-response');
				try{
					$res = $this->dispatcher->trigger("onCheckAnswer",$captchaResp);
					if(empty($res) || empty($res[0]) || !$res[0]){
						 $this->redirect();
					}
				} catch (Exception $e) {
					$this->redirect();
				}
				
			}
		}
	}
	private function redirect()
	{
		$currentURI=JUri::current()."?captchaError=1";

		header('Location: ' . $currentURI);
		echo '<!DOCTYPE html><html><head><script type="text/javascript">window.location = "'.htmlentities ($currentURI).'"</script></head><body></body></html>';

		jexit(JText::_('PLG_SYSTEM_AUTHCAPTCHA_INVALIDCAPTCHA'));
		die("Invalid Captcha");
	}

    public function onAfterRender()
    {

        if ($this->getCaptchaPluginIncludeRequired()) {

            $body = $this->app->getBody();
			
			$formRegex="/(?<=<form)(?<formAll>[^>]+>(?<formBody>.*?))(?=<\/\s*form\s*>)/si";
			$passwordFieldRegex="/<\s*input[^>]+type\s*=[\"']+password/si";
			if(preg_match_all($formRegex, $body, $matches, PREG_OFFSET_CAPTURE)){
			

				$positions=array();
				foreach($matches["formAll"] as $idx=>$formAll) {

					if(!empty($formAll[0]) && (strpos($formAll[0], "user.login") !== false ||strpos($formAll[0], "com_login") !== false ) && preg_match($passwordFieldRegex,$formAll[0]))
						$positions[] =$matches["formBody"][$idx][1];
			
				}

				if(count($positions)>0){
					rsort($positions);

					foreach ($positions as $position) {
						$captcha = $this->dispatcher->trigger('onDisplay', array(null, 'dynamic_captcha_loginform', 'required'));
						
						if (!empty($captcha) && !empty($captcha[0])) {
							$body=substr_replace($body, $captcha[0], $position, 0);
						}
						
					}
			
					$this->app->setBody($body);
				}
			}
			
     
        }
    }
	
	

}
