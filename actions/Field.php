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
use Voetbal\Competition\Repository as CompetitionRepos;

final class Field
{
    /**
     * @var FieldRepository
     */
    protected $repos;
    /**
     * @var CompetitionRepos
     */
    protected $csRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        FieldRepository $repos,
        CompetitionRepos $csRepos,
        Serializer $serializer
    )
    {
        $this->repos = $repos;
        $this->csRepos = $csRepos;
        $this->serializer = $serializer;
    }

    public function add( $request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Field $field */
            $field = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Field', 'json');

            if ( $field === null ) {
                throw new \Exception("er kan geen veld worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionid = (int) $request->getParam("competitionid");
            $competition = $this->csRepos->find($competitionid);
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $field->setCompetition( $competition );
            $fieldRet = $this->repos->save( $field );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $fieldRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422 )->write( $sErrorMessage );
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $field = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Field', 'json');

            $competitionid = (int) $request->getParam("competitionid");
            $competition = $this->csRepos->find($competitionid);
            if ( $competition === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            // @TODO FROMJSON
            $field = $this->repos->editFromJSON($field, $competition);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($field, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400, $sErrorMessage)->write($sErrorMessage);
    }

    public function remove( $request, $response, $args)
    {
        $field = $this->repos->find($args['id']);

        if( $field === null ) {
            return $response->withStatus(404, 'het te verwijderen veld kan niet gevonden worden');
        }

        $sErrorMessage = null;
        try {
            $this->repos->remove($field);

            return $response->withStatus(204);
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404, $sErrorMessage );
    }

}