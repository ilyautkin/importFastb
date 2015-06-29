<?php

/**
 * The home manager controller for importFastb.
 *
 */
class importFastbHomeManagerController extends importFastbMainController {
	/* @var importFastb $importFastb */
	public $importFastb;


	/**
	 * @param array $scriptProperties
	 */
	public function process(array $scriptProperties = array()) {
	}


	/**
	 * @return null|string
	 */
	public function getPageTitle() {
		return $this->modx->lexicon('importfastb');
	}


	/**
	 * @return void
	 */
	public function loadCustomCssJs() {
		$this->addCss($this->importFastb->config['cssUrl'] . 'mgr/main.css');
		$this->addCss($this->importFastb->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/misc/utils.js');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/widgets/export.panel.js');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/widgets/items.windows.js');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/widgets/import.panel.js');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/widgets/home.panel.js');
		$this->addJavascript($this->importFastb->config['jsUrl'] . 'mgr/sections/home.js');
		$this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
			MODx.load({ xtype: "importfastb-page-home"});
		});
		</script>');
	}


	/**
	 * @return string
	 */
	public function getTemplateFile() {
		return $this->importFastb->config['templatesPath'] . 'home.tpl';
	}
}