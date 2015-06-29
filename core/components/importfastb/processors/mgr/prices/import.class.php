<?php

/**
 * Create a Link
 */
class rldImportProcessor extends modProcessor {
    public $languageTopics = array('importfastb');

    public function process() {
        $object = array();
        $object['log'] = array();
        $key = 'importfastb/import';
        if ($_POST['parsed']) {
            $data = $this->modx->cacheManager->get($key);
            //$object['log'][] = $this->modx->toJSON(array_shift($data));
            $link = array_shift($data);
            $processorProps = array('processors_path' => $this->modx->getOption('core_path') . 'components/importfastb/processors/');
            $response = $this->modx->runProcessor('mgr/link/create', $link, $processorProps);
            if ($response->isError()) {
                if ($response->getMessage()) {
                    $error = $response->getMessage();
                } else {
                    $error = array();
                    foreach($response->response['errors'] as $err) {
                        if (!in_array($err['msg'], $error)) {
                            $error[] = $err['msg'];
                        }
                    }
                    $error = implode(".", $error);
                }
                $object['log'][] = '<span class="red">' . $_POST['step'].'. ' .
                                    $this->modx->lexicon('error') .
                                    '. ' . $error . '</span>';
                $this->modx->error->reset();
            } else {
                $object['log'][] = '<span>' . $_POST['step'].'. ' .
                                    $this->modx->lexicon('success') . '</span>';
            }
            $this->modx->cacheManager->set($key, $data);
            if (empty($data)) {
                $object['complete'] = true;
                $object['log'][] = '<b>'.$this->modx->lexicon('finish').'</b>';
            }
            $object['step'] = $_POST['step'] + 1;
        } else {
            if (!empty($_FILES['csv-file']['name']) && !empty($_FILES['csv-file']['tmp_name'])) {
                $data = false;
                $handle = fopen($_FILES['csv-file']['tmp_name'], "r");
                $rownum = 1;
                while (($csv = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    $row = array('anchor' => $csv[0],'url' => $csv[1],'page' => $csv[2]);
                    if (!$row['anchor'] || !$row['url'] || !$row['page'] || count($csv) != 3) {
                        $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileparsefailed').' '.$rownum);
                        break;
                    }
                    //$object['log'][] = $row['anchor'].' - '.$row['url'].' ('.$row['page'].')';
                    $data[] = $row;
                    $rownum++;
                }
                fclose($handle);
                if (!$this->hasErrors()) {
                    if ($data === false) {
                        $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
                    } else {
                        $this->modx->cacheManager->set($key, $data);
                        $object['log'][] = $this->modx->lexicon('importfastb_import_file_parsed') . ' ' . count($data);
                    }
                }
            } else {
                $this->modx->error->addField('csv-file-btn', $this->modx->lexicon('importfastb_import_fileuploadfailed'));
            }
        }

        if ($this->hasErrors()) {
            $o = $this->failure();
        } else {
            $o = $this->success('', $object);
        }
        return $o;
    }

}

return 'rldImportProcessor';