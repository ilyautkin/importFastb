<?php

class rldExportProcessor extends modProcessor {
    public $languageTopics = array('importfastb');

    public function process() {
        $object = array();
        $object['log'] = array();
		$object['filename'] = $_POST['filename'] ? $_POST['filename'] : 'export_'.date('d-m-Y_His');
		$key = 'importfastb/export/'.$object['filename'];
		if (!$cache = $this->modx->cacheManager->get($key)) {
			$cache = array();
		}
		if (!$_POST['exported']) {
			$limit = 100;
			$offset = $_POST['step'] ? $_POST['step'] * $limit : 0;

			$q = $this->modx->newQuery('modResource');
			$q->select('
					     `modResource`.`id` AS `resource`
					    ,`modResource`.`pagetitle` as `pagetitle`
					    ,`modResource`.`longtitle` as `longtitle`
					    ,`modResource`.`description` as `description`
					    ,`modResource`.`alias` as `alias`
					    ,`modResource`.`content` as `content`
					    ,`Parent`.`pagetitle` as `category`
					');
			$q->leftJoin('modResource', 'Parent', '`modResource`.`parent` = `Parent`.`id`');
			$q->sortby('`Parent`.`menuindex`','ASC');
			$q->sortby('`modResource`.`menuindex`','ASC');
			$count = $this->modx->getCount('modResource', $q);
			if ($offset == 0) {
				$object['log'][] = 'Общее количество страниц: '.$count;
			}
			$q->limit($limit, $offset);
			$q->prepare();
			//print $q->toSQL();
			//print $this->modx->getCount('modTemplateVarResource', $q);
			$q->stmt->execute();
			$res = $q->stmt->fetchAll(PDO::FETCH_ASSOC);
			//print_r($res); die();
			$this->modx->cacheManager->set($key, array_merge($cache, $res));
			
			$total = $offset + $limit;
			if ($count <= $total) {
				$object['exported'] = true;
				$total = $count;
			}
			$object['step'] = $_POST['step'] + 1;
			$object['log'][] = $object['step'].'. Выгружено страниц '.$total.' из '.$count;
			if ($object['exported'] == true) {
				$object['log'][] = 'Страницы выгружены. Создаём XLS-файл...';
			}
		} else {
			// Подключаем класс для работы с excel
			require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel.php');
			require_once($this->modx->getOption('core_path').'components/importfastb/Classes/PHPExcel/Writer/Excel5.php');
			// require_once($this->modx->getOption('base_path').'importFastb/core/components/importfastb/Classes/PHPExcel.php');
			// require_once($this->modx->getOption('base_path').'importFastb/core/components/importfastb/Classes/PHPExcel/Writer/Excel5.php');
			
			$xls = new PHPExcel();
			// Устанавливаем индекс активного листа
			$xls->setActiveSheetIndex(0);
			// Получаем активный лист
			$sheet = $xls->getActiveSheet();
			// Подписываем лист
			$sheet->setTitle('База страниц сайта');

		    $col = 0;
			$sheet->setCellValueByColumnAndRow($col,1,'ID');
			$col++;

			if ($_POST['category'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'Category');
			    $col++;
			}
			if ($_POST['pagetitle'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'Title');
			    $col++;
			}
			if ($_POST['longtitle'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'H1');
			    $col++;
			}
			if ($_POST['description'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'Description');
			    $col++;
			}
			if ($_POST['alias'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'URL');
			    $col++;
			}
			if ($_POST['content'] == 'true') {
			    $sheet->setCellValueByColumnAndRow($col,1,'Content');
			    $col++;
			}
			
			for ($i = 0; $i < count($cache); $i++) {
			    $col = 0;
				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['resource']);
				$col++;
    			if ($_POST['category'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['category']);
    				$col++;
    			}
    			if ($_POST['pagetitle'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['pagetitle']);
    				$col++;
    			}
    			if ($_POST['longtitle'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['longtitle']);
    				$col++;
    			}
    			if ($_POST['description'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['description']);
    				$col++;
    			}
    			if ($_POST['alias'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['alias']);
    				$col++;
    			}
    			if ($_POST['content'] == 'true') {
    				$sheet->setCellValueByColumnAndRow($col,$i+2,$cache[$i]['content']);
    				$col++;
    			}
			}
			$sheet->getColumnDimension('A')->setAutoSize(true);
			/*
			$sheet->getColumnDimension('B')->setAutoSize(true);
			*/
			// Выводим содержимое файла
			$objWriter = new PHPExcel_Writer_Excel5($xls);
			$cacheDir = $this->modx->getOption('core_path').'cache/default/importfastb/export/';
			$object['filepath'] = $cacheDir.$object['filename'].'.xls';
			$objWriter->save($object['filepath']);
			$object['filepath'] = str_replace($this->modx->getOption('core_path'), 'core/', $object['filepath']);
			$object['complete'] = true;
		}
		if ($object['complete'] == true) {
			$object['log'][] = '<b>'.$this->modx->lexicon('finish').'</b>';
			$this->modx->cacheManager->delete($key);
		}

        if ($this->hasErrors()) {
            $o = $this->failure();
        } else {
            $o = $this->success('', $object);
        }
        return $o;
    }

}

return 'rldExportProcessor';