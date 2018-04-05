<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Field\Service as FieldService;
use Voetbal\Competition\Repository as CompetitionRepos;

final class Field
{
    /**
     * @var FieldRepository
     */
    protected $repos;
    /**
     * @var FieldService
     */
    protected $service;
    /**
     * @var CompetitionRepos
     */
    protected $competitionRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        FieldRepository $repos,
        FieldService $service,
        CompetitionRepos $competitionRepos,
        Serializer $serializer
    ) {
        $this->repos = $repos;
        $this->service = $service;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
    }

    public function add($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Field $fieldSer */
            $fieldSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Field', 'json');
            if ($fieldSer === null) {
                throw new \Exception("er kan geen veld worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionId = (int)$request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ($competition === null) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }

            $fieldRet = $this->service->create(
                $fieldSer->getNumber(),
                $fieldSer->getName(),
                $competition);

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($fieldRet, 'json'));;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $competitionid = (int)$request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionid);
            if ($competition === null) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }

            /** @var \Voetbal\Field $fieldSer */
            $fieldSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Field', 'json');
            if ($fieldSer === null) {
                throw new \Exception("het veld kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }
            /** @var \Voetbal\Field $field */
            $field = $this->repos->find($args["id"]);
            if ($field === null) {
                throw new \Exception("het veld kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }
            if ($field->getCompetition() !== $competition) {
                throw new \Exception("de competitie van het veld komt niet overeen met de verstuurde competitie",
                    E_ERROR);
            }

            $fieldRet = $this->service->rename( $field, $fieldSer->getName() );

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($fieldRet, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400)->write($sErrorMessage);
    }

    public function remove($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $field = $this->repos->find($args['id']);
            if ($field === null) {
                throw new \Exception('het te verwijderen veld kan niet gevonden worden', E_ERROR);
            }
            $competitionId = (int)$request->getParam("competitionid");
            $competition = $this->competitionRepos->find($competitionId);
            if ($competition === null) {
                throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
            }
            if ($field->getCompetition() !== $competition) {
                throw new \Exception("de competitie van het veld komt niet overeen met de verstuurde competitie",
                    E_ERROR);
            }
            $this->service->remove($field);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write($sErrorMessage);
    }

}