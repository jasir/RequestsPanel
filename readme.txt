RequestsPanel for Nette v2

Usage:

 BasePresenter :
 | use \Extras\Debug\RequestsPanel;
 |- public function startup() {
 |-   parent::startup();
 |-   RequestsPanel::register();
 |- }
 |
 Anywhere:
 |  use \Extras\Debug\RequestsPanel;
 |  RequestsPanel::dump($variable, 'My Variable'); //label can be omitted

License: MIT
Author: Mikuláš Dítì