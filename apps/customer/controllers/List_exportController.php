<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * List_exportController
 *
 * Handles the actions for list export related tasks
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class List_exportController extends Controller
{
    public function init()
    {
        parent::init();

        if (Yii::app()->options->get('system.exporter.enabled', 'yes') != 'yes') {
            $this->redirect(array('lists/index'));
        }

        $customer = Yii::app()->customer->getModel();
        if ($customer->getGroupOption('lists.can_export_subscribers', 'yes') != 'yes') {
            $this->redirect(array('lists/index'));
        }

        $this->getData('pageScripts')->add(array('src' => AssetsUrl::js('list-export.js')));
    }

    /**
     * Display the export options
     */
    public function actionIndex($list_uid)
    {
        $list = $this->loadListModel($list_uid);

        $this->setData(array(
            'pageMetaTitle'     => $this->data->pageMetaTitle . ' | '. Yii::t('list_export', 'Export subscribers from your list'),
            'pageHeading'       => Yii::t('list_export', 'Export subscribers'),
            'pageBreadcrumbs'   => array(
                Yii::t('lists', 'Lists') => $this->createUrl('lists/index'),
                $list->name . ' ' => $this->createUrl('lists/overview', array('list_uid' => $list->list_uid)),
                Yii::t('list_export', 'Export subscribers')
            )
        ));

        $this->render('list', compact('list'));
    }

    /**
     * Handle the CSV export option
     */
    public function actionCsv($list_uid)
    {
        $list = $this->loadListModel($list_uid);
        $request = Yii::app()->request;
        $options = Yii::app()->options;

        $export = new ListCsvExport();
        $export->list_id = $list->list_id; // should not be assigned in attributes
	    
        $processAtOnce  = (int)$options->get('system.exporter.process_at_once', 500);
        $pause          = (int)$options->get('system.exporter.pause', 1);

        set_time_limit(0);
        if ($memoryLimit = $options->get('system.exporter.memory_limit')) {
            ini_set('memory_limit', $memoryLimit);
        }
        ini_set("auto_detect_line_endings", true);

        if ($request->isPostRequest && ($attributes = (array)$request->getPost($export->modelName, array()))) {
            $export->attributes = $attributes;
        }

        if (!$export->count) {
            $export->count = $export->countSubscribers();
        }

        if (!$request->isPostRequest || !$request->isAjaxRequest) {
            $this->setData(array(
                'pageMetaTitle'     => $this->data->pageMetaTitle.' | '.Yii::t('list_export', 'Export subscribers'),
                'pageHeading'       => Yii::t('list_export', 'Export subscribers'),
                'pageBreadcrumbs'   => array(
                    Yii::t('lists', 'Lists') => $this->createUrl('lists/index'),
                    $list->name . ' ' => $this->createUrl('lists/overview', array('list_uid' => $list->list_uid)),
                    Yii::t('list_export', 'CSV Export')
                )
            ));
            return $this->render('csv', compact('list', 'export', 'processAtOnce', 'pause'));
        }
        
        if ($export->count == 0) {
            return $this->renderJson(array(
                'result'    => 'error',
                'message'   => Yii::t('list_export', 'Your list has no subscribers to export!'),
            ));
        }

	    $storageDir     = Yii::getPathOfAlias('common.runtime.list-export');
	    $prefix         = strtolower(preg_replace('/[^a-z0-9]/i', '-', $list->name));
        $csvFile        = $prefix . '-subscribers-' . $list->list_uid . '.csv';
		$isFirstBatch   = $export->is_first_batch;
		
        if ($export->is_first_batch) {
        	
            // old csv
            if (is_file($oldCsvFile = $storageDir . '/' . $csvFile)) {
                 @unlink($oldCsvFile);
            }

            if (!file_exists($storageDir) && !is_dir($storageDir) && !@mkdir($storageDir, 0777, true)) {
                return $this->renderJson(array(
                    'result'    => 'error',
                    'message'   => Yii::t('list_export', 'Cannot create the storage directory for your export!'),
                ));
            }

            if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                $logAction->listExportStart($list, $export);
            }

            $export->is_first_batch = 0;
        }
        
        if (!($fp = @fopen($storageDir . '/' . $csvFile, 'a'))) {
            return $this->renderJson(array(
                'result'    => 'error',
                'message'   => Yii::t('list_export', 'Cannot open the storage file for your export!'),
            ));
        }
        
        $exportLog       = array();
        $hasData         = false;
        $counter         = 0;
        $startFromOffset = ($export->current_page - 1) * $processAtOnce;
	    $subscribers     = $export->findSubscribers($processAtOnce, $startFromOffset);
	    
	    if (!empty($subscribers)) {

		    if ($isFirstBatch) {
			    fputcsv($fp, array_keys($subscribers[0]), ',', '"');
		    }

		    foreach ($subscribers as $subscriberData) {
			    fputcsv($fp, array_values($subscriberData), ',', '"');
			    $exportLog[] = array(
				    'type'      => 'success',
				    'message'   => Yii::t('list_export', 'Successfully added the email "{email}" to the export list.', array(
					    '{email}' => $subscriberData['EMAIL'],
				    )),
				    'counter'   => true,
			    );
		    }
	    }

	    if (!$hasData && !empty($subscribers)) {
		    $hasData = true;
	    }

	    $counter += count($subscribers);
	    
        fclose($fp);
		
        if ($counter > 0) {
            $exportLog[] = array(
                'type'      => 'info',
                'message'   => Yii::t('list_export', 'Exported {count} subscribers, from {start} to {end}.', array(
                    '{count}'   => $counter,
                    '{start}'   => ($export->current_page - 1) * $processAtOnce,
                    '{end}'     => (($export->current_page - 1) * $processAtOnce) + $processAtOnce,
                )),
            );
        }

        // is it done ?
        if (!$hasData || ($export->current_page * $processAtOnce >= $export->count)) {
            if ($logAction = Yii::app()->customer->getModel()->asa('logAction')) {
                $logAction->listExportEnd($list, $export);
            }

            $exportLog[] = array(
                'type'    => 'success',
                'message' => Yii::t('list_export', 'The export is now complete, starting the packing process...')
            );
            
            $downloadUrl = $this->createUrl('list_export/csv_download', array('list_uid' => $list_uid));
			
            return $this->renderJson(array(
                'result'        => 'success',
                'message'       => Yii::t('list_export', 'Packing done, your file will be downloaded now, please wait...'),
                'download'      => $downloadUrl,
                'export_log'    => $exportLog,
                'recordsCount'  => $export->count,
            ));
        }

        $export->current_page++;
        return $this->renderJson(array(
            'result'        => 'success',
            'message'       => Yii::t('list_export', 'Please wait, starting another batch...'),
            'attributes'    => $export->attributes,
            'export_log'    => $exportLog,
            'recordsCount'  => $export->count,
        ));
    }

    /**
     * Download the csv created from export
     */
    public function actionCsv_download($list_uid)
    {
        $list       = $this->loadListModel($list_uid);
        $storageDir = Yii::getPathOfAlias('common.runtime.list-export');
        $prefix     = strtolower(preg_replace('/[^a-z0-9]/i', '-', $list->name));
        $csvName    = $prefix . '-subscribers-' . $list->list_uid . '.csv';
        $csvPath    = $storageDir . '/' . $csvName;

        if (!is_file($csvPath)) {
            Yii::app()->notify->addError(Yii::t('list_export', 'The export file has been deleted.'));
            $this->redirect(array('list_export/index'));
        }

        if (!($fp = @fopen($csvPath, "rb"))) {
            @unlink($csvPath);
            Yii::app()->notify->addError(Yii::t('list_export', 'The export file has been deleted.'));
            $this->redirect(array('list_export/index'));
        }
        
        /* Set the download headers */
        HeaderHelper::setDownloadHeaders($csvName, filesize($csvPath));

        while(!feof($fp)) {
            echo fread($fp, 8192);
            flush();
            if (connection_status() != 0) {
                @fclose($fp);
                @unlink($csvPath);
                die();
            }
        }
        @fclose($fp);
        @unlink($csvPath);
    }

    /**
     * Helper method to load the list AR model
     */
    public function loadListModel($list_uid)
    {
        $model = Lists::model()->findByAttributes(array(
            'list_uid'      => $list_uid,
            'customer_id'   => (int)Yii::app()->customer->getId(),
        ));

        if ($model === null) {
            throw new CHttpException(404, Yii::t('app', 'The requested page does not exist.'));
        }

        return $model;
    }
}
