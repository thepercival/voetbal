<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:49
 */


namespace Voetbal\Action\External;

use JMS\Serializer\Serializer;
use Doctrine\ORM\EntityManager;
use Voetbal\External\Object\Service as ExternalObjectService;
use \Doctrine\ORM\EntityRepository;
use Voetbal;

final class Object
{
    /**
     * @var ExternalObjectService
     */
    protected $service;
    /**
     * @var EntityRepository
     */
    protected $repos;
    /**
     * @var EntityRepository
     */
    protected $importableRepos;
    /**
     * @var oetbal\External\System\Repository
     */
    protected $systemRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        ExternalObjectService $service,
        EntityRepository $objectRepository,
        EntityRepository $importableRepos,
        Voetbal\External\System\Repository $systemRepos,
        Serializer $serializer
    )
    {
        $this->service = $service;
        $this->repos = $objectRepository;
        $this->importableRepos = $importableRepos;
        $this->systemRepos = $systemRepos;
        $this->serializer = $serializer;
    }

    public function fetch( $request, $response, $args)
    {
        if ( $this->repos === null ){
            return $response->withStatus(404, 'geen klasse gevonden voor route '.$args["resourceType"]);
        }

        $objects = $this->repos->findAll();
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write( $this->serializer->serialize( $objects, 'json') );
    }

    public function fetchOne( $request, $response, $args)
    {

        /*$system = $this->repos->find($args['id']);
        if ($system) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $system, 'json'));
            ;
        }*/
        return $response->withStatus(404, 'geen extern systeem met het opgegeven id gevonden');
    }

    public function add( $request, $response, $args)
    {
        $externalid = filter_var($request->getParam('externalid'), FILTER_SANITIZE_STRING);
        $externalsystemid = filter_var($request->getParam('externalsystemid'), FILTER_VALIDATE_INT);
        $importableobejctid = filter_var($request->getParam('importableobjectid'), FILTER_VALIDATE_INT);

        $sErrorMessage = null;
        // $sErrorMessage = $externalid . " - " . $externalsystemid . " - " . $importableobejctid;

        $importableobject = $this->importableRepos->find($importableobejctid);
        if ( $importableobject === null ) {
            throw new \Exception("het object waaraan het externe object gekoppeld wordt, kan niet gevonden worden",E_ERROR);
        }
        $externalsystem = $this->systemRepos->find($externalsystemid);
        if ( $externalsystem === null ) {
            throw new \Exception("het externe systeem kan niet gevonden worden",E_ERROR);
        }

        try {
            $externalobject = $this->service->create(
                $importableobject,
                $externalid,
                $externalsystem
            );
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $externalobject, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode( $e->getMessage() );
        }
        return $response->withStatus(404, $sErrorMessage );
    }

    public function remove( $request, $response, $args)
    {
        $externalobject = $this->repos->find($args['id']);

        $sErrorMessage = "hallo";
        try {
            $this->service->remove(
                $externalobject
            );
            return $response
                ->withStatus(204);
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode( $e->getMessage() );
        }
        return $response->withStatus(404, $sErrorMessage );
    }
}