<?php
/** @noinspection PhpIncludeInspection */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var importFastb $importFastb */
$importFastb = $modx->getService('importfastb', 'importFastb', $modx->getOption('importfastb_core_path', null, $modx->getOption('core_path') . 'components/importfastb/') . 'model/importfastb/');
$modx->lexicon->load('importfastb:default');

// handle request
$corePath = $modx->getOption('importfastb_core_path', null, $modx->getOption('core_path') . 'components/importfastb/');
$path = $modx->getOption('processorsPath', $importFastb->config, $corePath . 'processors/');
$modx->request->handleRequest(array(
	'processors_path' => $path,
	'location' => '',
));