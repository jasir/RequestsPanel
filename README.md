# RequestsPanel for Nette v2

Usage in <strong>BasePresenter</strong>:


	use \Extras\Debug\RequestsPanel;

	public function startup() {
		parent::startup();
		RequestsPanel::register();
	}

Usage anywhere:

	use \Extras\Debug\RequestsPanel;
	RequestsPanel::dump($variable, 'My Variable'); // label can be omitted