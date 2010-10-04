<?php
/**
 * @author jasir
 * @license LGPL
 *
 * Heavily based on original David Grudl's DebugBar panel from Nette Framework
 * - see nettephp.com
 */
namespace mbase\Debug;

use \Nette\Object;
use \Nette\IDebugPanel;
use \Nette\Environment;
use \Nette\Debug;
use \Nette\Web\Html;

class RequestsPanel extends Object implements IDebugPanel {

	private $response;

	static private $dumps = array();

	/* --- Properties --- */

	/* --- Public Methods--- */

	public function __construct() {
		$presenter = Environment::getApplication()->getPresenter();
		if ($presenter === NULL) {
			throw new Exception('You must instantiate RequestsPanel when presenter is available, i.e. in presenter\'s startup method.', E_WARNING);
		}
		$presenter->onShutdown[] = array($this, 'onShutdown');
	}

	public static function dump($var, $label = NULL) {
		$s = Debug::dump($var, TRUE);
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
		$s = 'Requests (' . count($logs) . ')';
		if (count($logs) > 1) {
			$s = Html::el('span')->class('nette-warning')->add($s);
		}
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
		$presenter = $application->getPresenter();
		$request = $presenter->getRequest();

		$httpRequest = Environment::getHttpRequest();

		$entry = array();
		$entry['request'] = $request->getMethod();

		$pinfo = Html::el('table');

		$row = $pinfo->create('tr');
		$row->create('th', 'Presenter');
		$row->create('td')->add($presenter->backlink());


		if ($signal = $presenter->getSignal()) {
			$row = $pinfo->create('tr');
			$row->create('th','Signal');
			$receiver = empty($signal[0]) ? "&lt;presenter&gt;" : $signal[0];
			$row->create('td')->add($receiver . " :: " . $signal[1]);
		}

		$row = $pinfo->create('tr');
		$row->create('th', 'Uri');
		$row->create('td')->add($httpRequest->getUri()->path);

		$entry['presenter'] = $pinfo;

		$class = get_class($response);

		$entry['response'] = substr($class, 0, strpos($class, 'Response'));
		if ($response->getReflection()->hasMethod('getCode')) {
			$entry['response'] .= ' (' . $response->code . ')';
		}

		$entry['dumps']['HttpRequest'] = Debug::dump($httpRequest, TRUE);
		$entry['dumps']['PresenterRequest'] = Debug::dump($request, TRUE);
		$entry['dumps']['PresenterResponse'] = Debug::dump($response, TRUE);

		foreach(self::$dumps as $key => $dump) {
			if (!is_numeric($key)) {
				$entry['dumps'][$key] = $dump;
			} else {
				$entry['dumps'][] = $dump;
			}
		}

		$session = Environment::getSession('debug/RequestsPanel');

		if (!isset($session->logs)) {
			$session->logs = array();
		}
		$session->logs[] = $entry;
	}

}