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
			$headers = $this->modx->cacheManager->get($key . '_headers');
			if (empty($headers)) {
			    return $this->failure();
			}
			$h = $tvnames = array();
			foreach ($headers as $k => $header) {
			    if (!empty($header)) {
			        if (substr($header, 0, 3) == 'TV_') {
			            $tvnames[str_replace('TV_', '', $header)] = $k;
			        } else {
			            $h[$header] = $k;
			        }
			    }
			}
			foreach($data as $k => $row) {
			    $data = array('id' => $row[$h['ID']]);
			    if (isset($h['Category']) && $h['Category']) {
			        $data['category'] = $row[$h['Category']];
			    }
			    if (isset($h['Title']) && $h['Title']) {
			        $data['pagetitle'] = $row[$h['Title']];
			    }
			    if (isset($h['H1']) && $h['H1']) {
			        $data['longtitle'] = $row[$h['H1']];
			    }
			    if (isset($h['Description']) && $h['Description']) {
			        $data['description'] = $row[$h['Description']];
			    }
			    if (isset($h['URL']) && $h['URL']) {
			        $data['alias'] = $row[$h['URL']];
			    }
			    if (isset($h['Content']) && $h['Content']) {
			        $data['content'] = $row[$h['Content']];
			    }
			    $tvs = array();
			    foreach ($tvnames as $tv => $pos) {
			        $tvs[$tv] = $row[$pos];
			    }
			    // Проверяем - не пустая ли строка получена
			    $row_is_empty = true;
			    foreach($data as $j => $v) {
			        if ($j != 'id' && $v) {
			            $row_is_empty = false;
			        }
			    }
			    if ($row_is_empty) {
			        continue;
			    }
			    
			    // Получаем и указываем parent
		        if (isset($data['category']) && $data['category']) {
		            if ($parent = $this->modx->getObject('modResource', array('pagetitle' => $data['category']))) {
		                $data['parent'] = $parent->id;
		            }
		        }
			    if (!$data['id'] || !$resource = $this->modx->getObject('modResource', $data['id'])) {
			        if (!$data['pagetitle']) {
			            $data['pagetitle'] = 'No title';
			        }
    		        if (!isset($data['parent'])) {
    		            $data['parent'] = 0;
    		        }
			        $response = $this->modx->runProcessor('resource/create', array('pagetitle' => $data['pagetitle'], 'parent' => $data['parent']));
			        if ($response->isError()) {
                        $object['log'][] = 'Ошибка: '.$response->getMessage();
                        $this->modx->error->reset();
                        continue;
                    }
                    $data['id'] = $response->response['object']['id'];
			        $resource = $this->modx->getObject('modResource', $data['id']);
			        $resource->set('published', true);
			    }
			    foreach ($data as $k => $v) {
    			    if ($data[$k]) {
    			        $data[$k] = str_replace('&lt;',   '<', $data[$k]);
    			        $data[$k] = str_replace('&gt;',   '>', $data[$k]);
    			        $data[$k] = str_replace('&quot;', '"', $data[$k]);
    			        $data[$k] = str_replace('&amp;',  '&', $data[$k]);
    			    }
			    }
			    
			    $resource->fromArray($data);
			    if (!empty($tvs)) {
			        foreach($tvs as $tv => $val) {
			            $resource->setTVValue($tv,$val);
			        }
			    }
			    $resource->save();
			    
			    $log_row = '';
			    $log_row .= $data['id'] . '. ';
			    if ($data['pagetitle']) {
			        $log_row .= $data['pagetitle'];
			    }
			    if ($data['alias']) {
			        $log_row .= ' (' . $data['alias'] . ')';
			    }
                $object['log'][] = $log_row;
			}
            $this->modx->cacheManager->set($key, $remains);
            if (empty($remains)) {
                $object['complete'] = true;
                $this->modx->cacheManager->refresh();
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
                require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel/Writer/Excel5.php');
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
                		$field_empty = false;
                		if ($i == 1) {
                		    $headers = array();
                		    $j = 0;
                		    while (!$field_empty) {
                		        $val = trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow($j, $i)->getValue()));
                		        if ($val) {
                    		        $headers[] = $val;
                    		        $j++;
                		        } else {
                		            $field_empty = true;
                		        }
                		    }
                            if (!empty($headers)) {
                                $this->modx->cacheManager->set($key . '_headers', $headers);
                            }
                		} else {
                		    $data_row = array();
                		    for ($j = 0; $j < count($headers); $j++) {
                		        $data_row[] = trim(htmlspecialchars($objWorksheet->getCellByColumnAndRow($j, $i)->getValue()));
                		    }
						    $data[] = $data_row;
                		}
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