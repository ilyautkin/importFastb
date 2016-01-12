<?php
@ini_set('display_errors', 1);
/**
 * Create a Link
 */
class rldImportProcessor extends modProcessor {
    public $languageTopics = array('importfastb');

    public function process() {
        $object = array();
        $object['log'] = array();
        $object['complete'] = false;
		$object['filename'] = $_POST['filename'] ? $_POST['filename'] : 'import_'.date('d-m-Y_His');
		$cacheDir = $this->modx->getOption('core_path').'cache/default/importfastb/';
		$object['filepath'] = $cacheDir.$object['filename'].'.xls';
		$key = 'importfastb/import/'.$object['filename'];
		if (!$cache = $this->modx->cacheManager->get($key)) {
			$cache = array();
		}
        if ($_POST['parsed']) {
			$limit = 50;
			$offset = $_POST['step'] ? $_POST['step'] * $limit : 0;
            $data = array_slice($this->modx->cacheManager->get($key), 0, $limit);
			$remains = array_slice($this->modx->cacheManager->get($key), $limit);
			$skus = array();
			foreach($data as $k => $row) {
				if ($row[0] && $row[3]) {
				    $parent_id = 43;
				    for($i = 0; $i <= 2; $i++) {
				        if ($row[$i]) {
        				    if (!$parent = $this->modx->getObject('modResource', array('pagetitle' => $row[$i], 'class_key' => 'msCategory'))) {
        				        $parent = $this->modx->newObject('modResource', array('pagetitle' => $row[$i], 'class_key' => 'msCategory', 'parent' => $parent_id, 'published' => 1));
        				        $parent->alias = $parent->cleanAlias($row[$i]);
        				        $parent->save();
        				    }
        				    $parent_id = $parent->get('id');
				        }
				    }
				    /* @var array $scriptProperties */
                    /* @var miniShop2 $miniShop2 */
                    $miniShop2 = $this->modx->getService('minishop2');
                    $procProps = array(
                        'processors_path' => $this->modx->getOption('core_path') . 'components/minishop/processors/'
                    );
				    if (!$resource = $this->modx->getObject('modResource', array('pagetitle' => $row[3], 'class_key' => 'msproduct'))) {
				        $response = $this->modx->runProcessor('resource/create', array('pagetitle' => $row[3], 'parent' => $parent_id, 'class_key' => 'msproduct'));
				        if ($response->isError()) {
                            $object['log'][] = 'Ошибка: '.$response->getMessage();
                            continue;
                        }
				        $resource = $this->modx->getObject('modResource', $response->response['object']['id']);
				    }
				    if ($row[8]) {
				        if (!$vendor = $this->modx->getObject('msVendor', array('name' => $row[8]))) {
    				        $vendor = $this->modx->newObject('msVendor', array('name' => $row[8]));
    				        $vendor->save();
    				    }
    				    $vendor_id = $vendor->get('id');
				    }
				    if ($row[7]) {
				        $file = $this->modx->getOption('base_path').'assets/images/catalog/'.$row[7];
				        if (file_exists($file)) {
				            $response = $this->modx->runProcessor('gallery/upload',
        						array('id' => $resource->get('id'), 'name' => $row[7], 'file' => $file),
        						array('processors_path' => $this->modx->getOption('core_path').'components/minishop2/processors/mgr/')
        					);
        					if ($response->isError()) {
        						//$this->modx->log(modX::LOG_LEVEL_ERROR, "Error on upload \"$v\": \n". print_r($response->getAllErrors(), 1));
        						//$object['log'][] = 'Ошибка: '.implode(',', $response->getAllErrors());
        						$this->modx->error->reset();
        					}
				        }
				    }
				    $resource->set('pagetitle', $row[3]);
				    $resource->set('published', true);
				    $resource->Data->set('price', $row[6]);
				    $resource->Data->set('vendor', $vendor_id);
				    $resource->save();
                    $object['log'][] = $row[0].' '.$row[1].' '.$row[2].' '.$row[3];
				}
			}
            $this->modx->cacheManager->set($key, $remains);
            if (empty($remains)) {
                $object['complete'] = true;
                $object['log'][] = '<b>'.$this->modx->lexicon('finish').'</b>';
            }
            $object['step'] = $_POST['step'] + 1;
            $object['parsed'] = true;
        } else {
            if (!file_exists($object['filepath'])) {
                if (!empty($_FILES['csv-file']['name']) && !empty($_FILES['csv-file']['tmp_name'])) {
                    if (!file_exists($cacheDir)) {
                        mkdir($cacheDir);
                    }
                    $cacheDir .= 'import/';
                    if (!file_exists($cacheDir)) {
                        mkdir($cacheDir);
                    }
                    if (move_uploaded_file($_FILES['csv-file']['tmp_name'], $object['filepath'])) {
                        $object['uploaded'] = true;
                        //$object['log'][] = '<span id="processing-xls" class="loading-indicator">Файл загружен, данные обрабатываются...</span>';
                        $object['log'][] = 'Файл загружен, данные обрабатываются...';
                    } else {
                        $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
                    }
                } else {
                    $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
                }
            } else {
                $data = false;
                $chunkSize = 100;		//размер считываемых строк за раз
                $startRow = 1;			//начинаем читать со строки 2, в PHPExcel первая строка имеет индекс 1, и как правило это строка заголовков
                $exit = false;			//флаг выхода
                $empty_value = 0;		//счетчик пустых знаений
                // Подключаем класс для работы с excel
                require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel.php');
                //require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel/Writer/Excel5.php');
                require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel/IOFactory.php');
                require_once($this->modx->getOption('core_path').'components/importfastb/model/importfastb/chunkReadFilter.class.php');

                $objReader = PHPExcel_IOFactory::createReaderForFile($object['filepath']);
                $objReader->setReadDataOnly(true);
                
                $chunkFilter = new chunkReadFilter(); 
                $objReader->setReadFilter($chunkFilter); 
                $data = array();
                //внешний цикл, пока файл не кончится
                while (!$exit) {
                	$chunkFilter->setRows($startRow,$chunkSize); 	//устанавливаем знаечние фильтра
                	$objPHPExcel = $objReader->load($object['filepath']);		//открываем файл
                	$objPHPExcel->setActiveSheetIndex(0);		//устанавливаем индекс активной страницы
                	$objWorksheet = $objPHPExcel->getActiveSheet();	//делаем активной нужную страницу
                	for ($i = $startRow; $i < $startRow + $chunkSize; $i++) {	//внутренний цикл по строкам
                		$value = trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(0, $i)->getValue()));		//получаем первое знаение в строке
                		if (empty($value))		//проверяем значение на пустоту
                			$empty_value++;			
                		if ($empty_value == 3) {		//после трех пустых значений, завершаем обработку файла, думая, что это конец
                			$exit = true;	
                			continue;		
                		}
						$data[] = array(
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(0, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(1, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(2, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(3, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(4, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(5, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(6, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(7, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(8, $i)->getValue())),
						        trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow(9, $i)->getValue()))
						    );
                	}
                	$objPHPExcel->disconnectWorksheets(); 		//чистим 
                	unset($objPHPExcel); 						//память
                	$startRow += $chunkSize;					//переходим на следующий шаг цикла, увеличивая строку, с которой будем читать файл
                }
				
                if (!$this->hasErrors()) {
                    if (empty($data)) {
                        $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
                    } else {
                        $this->modx->cacheManager->set($key, $data);
                        $object['log'][] = $this->modx->lexicon('importfastb_import_file_parsed') . ' ' . count($data);
                        $object['parsed'] = true;
                        unlink($object['filepath']);
                    }
                }
            }
        }
        return $this->success('', $object);
        if ($this->hasErrors()) {
            $o = $this->failure();
        } else {
            $o = $this->success('', $object);
        }
        return $o;
    }

}

return 'rldImportProcessor';