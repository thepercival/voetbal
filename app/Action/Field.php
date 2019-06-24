<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace VoetbalApp\Action;

use JMS\Serializer\Serializer;
use Voetbal\Field\Repository as FieldRepository;
use Voetbal\Field\Service as FieldService;
use Voetbal\Competition\Repository as CompetitionRepos;
use Voetbal\Field as FieldBase;

final class Field
{
    /**
     * @var FieldRepository
     */
    protected $repos;
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
        CompetitionRepos $competitionRepos,
        Serializer $serializer
    ) {
        $this->repos = $repos;
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
            $competition = $this->competitionRepos->find((int)$request->getParam("competitionid"));
            if ($competition === null) {
                throw new \Exception("de competitie kan niet gevonden worden", E_ERROR);
            }

            $fieldsWithSameName = $competition->getFields()->filter( function( $fieldIt ) use ( $fieldSer ) {
                return $fieldIt->getName() === $fieldSer->getName() || $fieldIt->getNumber() === $fieldSer->getNumber();
            });
            if( !$fieldsWithSameName->isEmpty() ) {
                throw new \Exception("het veldnummer \"".$fieldSer->getNumber()."\" of de veldnaam \"".$fieldSer->getName()."\" bestaat al", E_ERROR );
            }

            $field = new FieldBase( $competition, $fieldSer->getNumber() );
            $field->setName( $fieldSer->getName() );

            $this->repos->save( $field );
            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($field, 'json'));;
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $field = $this->getField((int)$args["id"], (int)$request->getParam("competitionid"));
            /** @var \Voetbal\Field $fieldSer */
            $fieldSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Field', 'json');
            if ($fieldSer === null) {
                throw new \Exception("het veld kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }

            $competition = $field->getCompetition();
            $fieldsWithSameName = $competition->getFields()->filter( function( $fieldIt ) use ( $fieldSer, $field ) {
                return $field->getName() === $fieldSer->getName() && $field !== $fieldIt;
            });
            if( !$fieldsWithSameName->isEmpty() ) {
                throw new \Exception("het veld \"".$fieldSer->getName()."\" bestaat al", E_ERROR );
            }

            $field->setName( $fieldSer->getName() );

            $this->repos->save( $field );
            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($field, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400)->write($sErrorMessage);
    }

    public function remove($request, $response, $args)
    {
        try {
            $field = $this->getField((int)$args["id"], (int)$request->getParam("competitionid"));
            $this->repos->remove($field);
            return $response->withStatus(204);
        } catch (\Exception $e) {
            return $response->withStatus(404)->write($e->getMessage());
        }
    }

    protected function getField(int $id, int $competitionId): FieldBase
    {
        if ($competitionId === null) {
            throw new \Exception("het competitie-id is niet meegegeven", E_ERROR);
        }
        $field = $this->repos->find($id);
        if ($field === null) {
            throw new \Exception("het veld kon niet gevonden worden o.b.v. de invoer", E_ERROR);
        }
        $competition = $this->competitionRepos->find($competitionId);
        if ($competition === null) {
            throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        if ($field->getCompetition() !== $competition) {
            throw new \Exception("de competitie van de scheidsrechter komt niet overeen met de verstuurde competitie",
                E_ERROR);
        }
        return $field;
    }
}