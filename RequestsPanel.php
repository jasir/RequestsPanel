<?php
/**
 * @author jasir
 * @license LGPL
 *
 * Heavily based on original David Grudl's DebugBar panel from Nette Framework
 * - see nettephp.com
 */
namespace Extras\Debug;

use \Nette\Object;
use \Nette\IDebugPanel;
use \Nette\Environment;
use \Nette\Debug;
use \Nette\DebugHelpers;
use \Nette\Web\Html;
use \Nette\Application\RenderResponse;

class RequestsPanel extends Object implements IDebugPanel {

	private $response;

	static private $presenter;

	static private $dumps = array();

	static private $instance;

	/* --- Properties --- */

	/* --- Public Methods--- */

	public static function register() {

		$presenter = Environment::getApplication()->getPresenter();
		if ($presenter === NULL) {
			throw new Exception('You must instantiate RequestsPanel when presenter is available, i.e. in presenter\'s startup method.', E_WARNING);
		}

		//register panel only once
		if (!self::$instance) {
			self::$instance = new \Extras\Debug\RequestsPanel();
			\Nette\Debug::addPanel(self::$instance);
		}

		//but callback for each new presenter
		if(self::$presenter !== $presenter) {
			self::$presenter = $presenter;
			$presenter->onShutdown[] = array(self::$instance, 'onShutdown');
		}


	}

	public static function dump($var, $label = NULL) {
		$s = DebugHelpers::clickableDump($var);
		if ($label === NULL) {
			self::$dumps[] = $s;
		} else {
			self::$dumps[$label] = $s;
		}
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 * @see IDebugPanel::getTab()
	 */
	public function getTab() {
		$logs = Environment::getSession('debug/RequestsPanel')->logs;
		$s = "<img src=\"data:image/gif;base64,R0lGODlhEAAQAKUkAAAAAIUlEqEtFqkvFrMxGEJdc0VheME1GklngE1shk9vit09HlR2k1d6mOZjSehvV+yKd+2SgJuyxqK3yam9zqu+zrHD0vOzpvO4rPXEusnV4MzX4c/a5PfOxtLc5dXe5vjUzfjWz9ri6dvj6v///////////////////////////////////////////////////////////////////////////////////////////////////////////////yH5BAEKAD8ALAAAAAAQABAAAAZiwJ/wBxgaj0IAqIg8AkIRArNJ7EQih8HU2Vk8IIJAYDsEmC8RgZlsBGDSzLW5nYEnPRXGFhBxqJMcEwV7ckkbgmxlZhqIc0gAHxQWEgkNCYlEHxMTCgaYSSMTCJ9lIqRtRkEAOw%3D%3D\"> Requests ";
		$cnt = count($logs);
		$s .= $cnt > 1 ? Html::el('span')->class('nette-warning')->add("($cnt)") : "(1)";
		return $s;
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 * @see IDebugPanel::getPanel()
	 */
	public function getPanel() {
		$session = Environment::getSession('debug/RequestsPanel');
		$logs = $session->logs;
		if ($this->response instanceOf RenderResponse ) {
			unset($session->logs);
			ob_start();
			require dirname(__FILE__) . '/bar.requests.panel.phtml';
			return ob_get_clean();
		}
	}

	/**
	 * Returns panel ID.
	 * @return string
	 * @see IDebugPanel::getId()
	 */
	public function getId() {
		return __CLASS__;
	}

	/**
	 * @param $presenter Presenter
	 * @param $response PresenterResponse
	 * @internal
	 */
	public function onShutdown($presenter, $response) {

		$this->response = $response;

		$application = Environment::getApplication();
		$presenter   = $application->getPresenter();
		$request     = $presenter->getRequest();
		$httpRequest = Environment::getHttpRequest();

		$entry = array();

		if ($signal = $presenter->getSignal()) {
			$receiver = empty($signal[0]) ? $presenter->name : $signal[0];
			$signal = $receiver . " :: " . $signal[1];
		}

		$rInfo = get_class($response);
		if ($response->getReflection()->hasMethod('getCode')) {
			$rInfo .= ' (' . $response->code . ')';
		}

		$entry['info']['presenter'] = $presenter->backlink();
		$entry['info']['response']  = $rInfo;
		$entry['info']['uri']       = $httpRequest->getUri();
		$entry['info']['uriPath']   = $httpRequest->getUri()->path;
		$entry['info']['request']   = $request->getMethod();
		$entry['info']['signal']    = $signal;

		$entry['dumps']['HttpRequest']       = DebugHelpers::clickableDump($httpRequest);
		$entry['dumps']['PresenterRequest']  = DebugHelpers::clickableDump($request);
		$entry['dumps']['PresenterResponse'] = DebugHelpers::clickableDump($response);


		foreach(self::$dumps as $key => $dump) {
			if (is_numeric($key)) {
				$entry['dumps'][] = $dump;
			} else {
				$entry['dumps'][$key] = $dump;
			}
		}

		$session = Environment::getSession('debug/RequestsPanel');

		if (!isset($session->logs)) {
			$session->logs = array();
		}
		$session->logs[] = $entry;
	}

}