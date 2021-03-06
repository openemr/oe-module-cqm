<?php
/**
 * @package OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU GeneralPublic License 3
 */

/**
 * Created by PhpStorm.
 * User: kchapple
 * Date: 7/29/19
 * Time: 11:00 AM
 */

namespace Mi2\Cqm\Controllers;

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7;
use Mi2\DataTable\SearchFilter;
use Mi2\Framework\AbstractController;
use Mi2\Framework\Response;
use OpenEMR\Common\System\System;
use OpenEMR\Cqm\CqmClient;
use OpenEMR\Cqm\CqmServiceManager;
use OpenEMR\Cqm\Generator;
use OpenEMR\Services\Qdm\CqmCalculator;
use OpenEMR\Services\Qdm\MeasureService;
use OpenEMR\Services\Qdm\QdmBuilder;
use OpenEMR\Services\Qdm\QdmRequestAll;
use OpenEMR\Services\Qdm\QdmRequestOne;
use OpenEMR\Services\Qrda\ExportCat1Service;
use OpenEMR\Services\Qrda\ExportCat3Service;

class AdminController extends AbstractController
{
    protected $client;
    public function __construct()
    {
        $this->client = CqmServiceManager::makeCqmClient();
    }

    /**
     * This is the "home" page for the Patient Privacy settings
     */
    public function _action_index()
    {
        $health = json_encode($this->client->getHealth());
// Fetch the measures from the 'openemr/cqm-execution' node module
        // because they have the JSON measures and value_sets.json we need to pass to cqm-service
        $this->view->measures = MeasureService::fetchMeasureOptions();
        $this->view->patientJson = "";
        $this->view->health = $health;
        $this->view->title = "CQM Tools";
        $this->setViewScript('admin/settings.php', 'layout.php');
    }

    public function _action_get_health()
    {
        echo json_encode($this->client->getHealth());
        exit;
    }

    public function _action_start_service()
    {
        echo $this->client->start();
        exit;
    }

    public function _action_shutdown_service()
    {
        echo $this->client->shutdown();
        exit;
    }

    public function _action_generate_models()
    {
        $generator = new Generator();
        $generator->execute();
        echo '';
        exit;
    }

    public function _action_generate_patient()
    {
        $pid = $this->request->getParam('pid');
        $request = null;
        if ($pid) {
            $request = new QdmRequestOne($pid);
        } else {
            $request = new QdmRequestAll();
        }
        $builder = new QdmBuilder();
        $models = $builder->build($request);
        echo json_encode($models);
        exit;
    }

    public function _action_execute_measure()
    {
        $pid = $this->request->getParam('pid');
        $measure = $this->request->getParam('measure');
        $effectiveDate = $this->request->getParam('effectiveDate');
        $effectiveEndDate = $this->request->getParam('effectiveEndDate');
        if ($pid) {
            $request = new QdmRequestOne($pid);
        } else {
            $request = new QdmRequestAll();
        }
        $builder = new QdmBuilder();
        $models = $builder->build($request);
        $json_models = json_encode($models);
        $patientStream = Psr7\Utils::streamFor($json_models);
        $measureFiles = MeasureService::fetchMeasureFiles($measure);
        $measureFileStream = new LazyOpenStream($measureFiles['measure'], 'r');
        $valueSetFileStream = new LazyOpenStream($measureFiles['valueSets'], 'r');
        $options = [
            'doPretty' => true,
            'includeClauseResults' => true,
            'requestDocument' => true,
            'effectiveDate' => $effectiveDate,
            'effectiveDateEnd' => $effectiveEndDate
        ];
        $optionsStream = Psr7\Utils::streamFor(json_encode($options));
        $response = $this->client->calculate($patientStream, $measureFileStream, $valueSetFileStream, $optionsStream);
        echo json_encode($response);
        exit;
    }

    public function _action_execute_cat1_export()
    {
        $pid = $this->request->getParam('pid');
        $measure = $this->request->getParam('measure');
        $export = new ExportCat1Service(new QdmBuilder(), new QdmRequestOne($pid));
        $xml = $export->export(
            [$measure] // Only one using the tool, but could be multiple, so we pass in an array
        );
        $directory = $GLOBALS['OE_SITE_DIR'] . DIRECTORY_SEPARATOR .
            'documents' . DIRECTORY_SEPARATOR . 'temp';
        file_put_contents($directory . DIRECTORY_SEPARATOR . "catI_doc_$pid.xml", $xml);
        header('Content-type: text/json');
        echo json_encode($xml);
        exit;
    }

    public function _action_execute_cat3_export()
    {
        $pid = $this->request->getParam('pid');
        $measure = $this->request->getParam('measure');
        $effectiveDate = $this->request->getParam('effectiveDate');
        $effectiveEndDate = $this->request->getParam('effectiveEndDate');
        $export = new ExportCat3Service(new QdmBuilder(), new CqmCalculator(), new QdmRequestOne($pid));
        $xml = $export->export([$measure]);
        $directory = $GLOBALS['OE_SITE_DIR'] . DIRECTORY_SEPARATOR .
            'documents' . DIRECTORY_SEPARATOR . 'temp';
        file_put_contents($directory . DIRECTORY_SEPARATOR . "catIII_doc_$pid.xml", $xml);
        header('Content-type: text/json');
        echo json_encode($xml);
        exit;
    }
}
