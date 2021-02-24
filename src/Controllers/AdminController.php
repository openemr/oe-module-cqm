<?php
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
use OpenEMR\Services\Qdm\PatientService;
use OpenEMR\Services\Qdm\MeasureService;

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

        // For now, we're only using the measures from the 'projecttacoma/cqm-execution' node module
        // because they have the value_sets.json we need to pass to cqm-service
        $this->view->measures = MeasureService::fetchMeasureOptions('projecttacoma/cqm-execution');
        $this->view->patientJson = "";
        $this->view->health = $health;
        $this->view->title = "CQM Tools";
        $this->setViewScript( 'admin/settings.php', 'layout.php' );
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
        $patientService = new PatientService();
        $qdmPatient = $patientService->makePatient($pid);
        echo json_encode($qdmPatient);
        exit;
    }

    public function _action_execute_measure()
    {
        $pid = $this->request->getParam('pid');
        $measure = $this->request->getParam('measure');

        $patientService = new PatientService();
        $qdmPatient = $patientService->makePatient($pid);
        $patients = [
            $qdmPatient
        ];
        $patientStream = Psr7\Utils::streamFor(json_encode($patients));
        $measureFiles = MeasureService::fetchMeasureFiles($measure);
        $measureFileStream = new LazyOpenStream($measureFiles['measure'], 'r');
        $valueSetFileStream = new LazyOpenStream($measureFiles['valueSets'], 'r');

        $response = $this->client->calculate(
            $patientStream,
            $measureFileStream,
            $valueSetFileStream
        );

        echo json_encode($response);
        exit;
    }
}
