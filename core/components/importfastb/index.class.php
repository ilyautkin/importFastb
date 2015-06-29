<?php

/**
 * Class importFastbMainController
 */
abstract class importFastbMainController extends modExtraManagerController {
	/** @var importFastb $importFastb */
	public $importFastb;


	/**
	 * @return void
	 */
	public function initialize() {
		$corePath = $this->modx->getOption('importfastb_core_path', null, $this->modx->getOption('core_path') . 'components/importfastb/');
		require_once $corePath . 'model/importfastb/importfastb.class.php';

		$this->importFastb = new importFastb($this->modx);
		$this->addCss($this->importFastb->config['cssUrl'] . 'mgr/main.css');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/importfastb.js');
		$this->addHtml('
		<script type="text/javascript">
			importFastb.config = ' . $this->modx->toJSON($this->importFastb->config) . ';
			importFastb.config.connector_url = "' . $this->importFastb->config['connectorUrl'] . '";
		</script>
		');

		parent::initialize();
	}


	/**
	 * @return array
	 */
	public function getLanguageTopics() {
		return array('importfastb:default');
	}


	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return true;
	}
}


/**
 * Class IndexManagerController
 */
class IndexManagerController extends importFastbMainController {

	/**
	 * @return string
	 */
	public static function getDefaultController() {
		return 'home';
	}
}