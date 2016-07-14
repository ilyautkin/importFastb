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
    		$object['filepath'] = $cacheDir.$object['filename'].'.csv';
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
      			$this->modx->addPackage('customextra', $this->modx->getOption('core_path').'components/customextra/model/');
      			foreach($data as $k => $row) {
      				if ($row[0] && $row[1]) {
      				    if (!$word = $this->modx->getObject('customExtraItem', array('name' => $row[0], 'string1' => $row[1]))) {
      				      $word = $this->modx->newObject('customExtraItem', array('name' => $row[0], 'string1' => $row[1]));
      				      $word->save();
      				    }
      				    if (!$category = $this->modx->getObject('customExtraOrder', array('name' => $row[2]))) {
      				      $category = $this->modx->newObject('customExtraOrder', array('name' => $row[2]));
      				      $category->save();
      				    }
      				    if (!$link = $this->modx->getObject('customExtraLink', array('id1' => $word->id, 'id2' => $category->id))) {
                    $link = $this->modx->newObject('customExtraLink', array('id1' => $word->id, 'id2' => $category->id));
                    $link->save();
                  }
                  $object['log'][] = $row[0].' ('.$row[1].') — '.$row[2];
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
                /*
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
                */
                $data = array();
                /*
                $row = 0;
                $handle = fopen($object['filepath'], "r");
                while (($row_data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $num = count($row_data);
                    //echo "<p> $num полей в строке $row: <br /></p>\n";
                    $row++;
                    for ($c=0; $c < $num; $c++) {
                        //echo $row_data[$c] . "<br />\n";
                        $data[$c] = $row_data[$c];
                    }
                }
                fclose($handle);
                */
                $i = $this->modx->cacheManager->get($key . 'ftell');
                $fp = fopen($object['filepath'], "r");
                if ($i > 0) {
                  fseek($fp,$i);
                }
                $i = 0;
                $nextStep = false;
                /*
                while (!feof($fp) && $nextStep) {
                     $i++;
                     $row = fgetcsv($fp, 1024, ";");
                     if (!empty($row) && $row[0] != 'pagetitle') {
                      $data[] = $row; 
                     }
                     if ($i > 100) {
                       $nextStep = true;
                     }
                }
                */
                // построчное считывание и анализ строк из файла
                while (($data_f = fgetcsv($fp, 1000, "&")) !== FALSE && !$nextStep) {
              	 if (!empty($data_f) && $data_f[0] != 'pagetitle') {
              	  $data[] = $data_f; 
              	 }
              	 if ($i > 99) {
              	   $nextStep = true;
              	   $this->modx->cacheManager->set($key . 'ftell', ftell($fp));
              	 }
              	 $i++;
                }
                  
                fclose($handle_f);
                if (!$this->hasErrors()) {
                    if (empty($data)) {
                        $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
                    } else {
                        if ($nextStep) {
                          $data = array_merge($cache, $data);
                          $this->modx->cacheManager->set($key, $data);
                          $object['parsed'] = false;
                          $object['i'] = count($cache);
                          $object['log'][] = 'Обнаружено ' . count($data) . ' записей';
                        } else {
                          $data = array_merge($cache, $data);
                          $this->modx->cacheManager->set($key, $data);
                          $object['log'][] = $this->modx->lexicon('importfastb_import_file_parsed') . ' ' . count($data);
                          $object['parsed'] = true;
                          unlink($object['filepath']);
                        }
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
