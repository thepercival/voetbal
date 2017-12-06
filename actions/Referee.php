<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 26-11-17
 * Time: 12:35
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Referee\Repository as RefereeRepository;
use Voetbal\Competitionseason\Repository as CompetitionseasonRepos;

final class Referee
{
    /**
     * @var RefereeRepository
     */
    protected $repos;
    /**
     * @var CompetitionseasonRepos
     */
    protected $csRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        RefereeRepository $repos,
        CompetitionseasonRepos $csRepos,
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
            /** @var \Voetbal\Referee $referee */
            $referee = $this->serializer->deserialize( json_encode($request->getParsedBody()), 'Voetbal\Referee', 'json');

            if ( $referee === null ) {
                throw new \Exception("er kan geen veld worden aangemaakt o.b.v. de invoergegevens", E_ERROR);
            }

            $competitionseasonid = (int) $request->getParam("competitionseasonid");
            $competitionseason = $this->csRepos->find($competitionseasonid);
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $referee->setCompetitionseason( $competitionseason );
            $refereeRet = $this->repos->save( $referee, $competitionseason );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $refereeRet, 'json'));
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
            $referee = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Referee', 'json');

            $competitionseasonid = (int) $request->getParam("competitionseasonid");
            $competitionseason = $this->csRepos->find($competitionseasonid);
            if ( $competitionseason === null ) {
                throw new \Exception("het competitieseizoen kan niet gevonden worden", E_ERROR);
            }

            $referee = $this->repos->editFromJSON($referee, $competitionseason);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($referee, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(400, $sErrorMessage)->write($sErrorMessage);
    }

    public function remove( $request, $response, $args)
    {
        $referee = $this->repos->find($args['id']);

        if( $referee === null ) {
            return $response->withStatus(404, 'het te verwijderen veld kan niet gevonden worden');
        }

        $sErrorMessage = null;
        try {
            $this->repos->remove($referee);

            return $response->withStatus(204);
        }
        catch( \Exception $e ){
            $sErrorMessage = urlencode($e->getMessage());
        }
        return $response->withStatus(404, $sErrorMessage );
    }

}