<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 7-2-2017
 * Time: 09:49
 */


namespace VoetbalApp\Action;

use JMS\Serializer\Serializer;
use Voetbal\ExternalSource\Object\Service as ExternalObjectService;
use \Doctrine\ORM\EntityRepository;
use Voetbal\Repository as VoetbalRepository;
use Voetbal;

final class Attacher
{
    /**
     * @var VoetbalRepository
     */
    protected $repos;
    /**
     * @var EntityRepository
     */
    protected $importableRepos;
    /**
     * @var Voetbal\ExternalSource\Source\Repository
     */
    protected $systemRepos;
    /**
     * @var Serializer
     */
    protected $serializer;
    /**
     * @var ExternalObjectService
     */
    protected $service;

    public function __construct(
        VoetbalRepository $repos,
        EntityRepository $importableRepos,
        Voetbal\ExternalSource\Repository $systemRepos,
        Serializer $serializer
    ) {
        $this->service = new ExternalObjectService($repos);
        $this->repos = $repos;
        $this->importableRepos = $importableRepos;
        $this->systemRepos = $systemRepos;
        $this->serializer = $serializer;
    }

    public function fetch($request, $response, $args)
    {
        if ($this->repos === null) {
            return $response->withStatus(404)->write('geen klasse gevonden voor route '.$args["resourceType"]);
        }

        $objects = $this->repos->findAll();
        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->write($this->serializer->serialize($objects, 'json'));
    }

    public function fetchOne($request, $response, $args)
    {
        $externalSourceId = filter_var($request->getParam('externalSystemId'), FILTER_VALIDATE_INT);
        $importableObjectId = filter_var($request->getParam('importableObjectId'), FILTER_VALIDATE_INT);

        $externalObject = $this->repos->findOneBy(array(
            'externalSource' => $externalSourceId,
            'importable' => $importableObjectId
        ));

        if ($externalObject) {
            return $response
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($this->service->toJSON($externalObject), 'json'));
            ;
        }
        return $response->withStatus(404)->write('geen extern object met het opgegeven id gevonden');
    }

    public function add($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Attacher $externalObjectSer */
            $externalObjectExtSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\External\ObjectExt', 'json');
            if ($externalObjectExtSer === null) {
                throw new \Exception("er kan geen extern object worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }

            $externalObject = $this->service->create(
                $this->importableRepos->find($externalObjectExtSer->getImportableObjectId()),
                $this->systemRepos->find($externalObjectExtSer->getExternalSystemId()),
                $externalObjectExtSer->getExternalId()
            );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($this->service->toJSON($externalObject), 'json'));
            ;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Attacher $externalObjectSer */
            $externalObjectExtSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\External\ObjectExt', 'json');
            if ($externalObjectExtSer === null) {
                throw new \Exception("er kan geen extern object worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }

            $externalObject = $this->repos->find($externalObjectExtSer->getId());
            if ($externalObject === null) {
                throw new \Exception("het externe object kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            // $externalObjectSer = $this->service->fromJSON( $externalObjectExtSer );
            $externalObject->setExternalId($externalObjectExtSer->getExternalId());

            $externalObjectRet = $this->repos->save($externalObject);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($this->service->toJSON($externalObjectRet), 'json'));
            ;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }

    public function remove($request, $response, $args)
    {
        $association = $this->repos->find($args['id']);
        $sErrorMessage = null;
        try {
            $this->service->remove($association);

            return $response
                ->withStatus(204);
            ;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }
}
