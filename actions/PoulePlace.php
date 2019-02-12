<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-12-17
 * Time: 10:17
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\PoulePlace\Repository as PoulePlaceRepository;
use Voetbal\PoulePlace\Service as PoulePlaceService;
use Voetbal\Competitor\Repository as CompetitorRepository;
use Voetbal\Poule\Repository as PouleRepository;
use Voetbal\Competition\Repository as CompetitionRepository;
use Voetbal\Poule;

final class PoulePlace
{
    /**
     * @var PoulePlaceRepository
     */
    protected $repos;
    /**
     * @var PoulePlaceService
     */
    protected $service;
    /**
     * @var CompetitorRepository
     */
    protected $competitorRepos;
    /**
     * @var CompetitionRepository
     */
    protected $competitionRepos;
    /**
     * @var PouleRepository
     */
    protected $pouleRepos;
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(
        PoulePlaceRepository $repos,
        PoulePlaceService $service,
        CompetitionRepository $competitorRepos,
        PouleRepository $pouleRepos,
        CompetitionRepository $competitionRepos,
        Serializer $serializer
    )
    {
        $this->repos = $repos;
        $this->service = $service;
        $this->competitorRepos = $competitorRepos;
        $this->pouleRepos = $pouleRepos;
        $this->competitionRepos = $competitionRepos;
        $this->serializer = $serializer;
    }

    public function edit($request, $response, $args)
    {
        $sErrorMessage = null;
        try {
            $poule = $this->getPoule( (int)$request->getParam("pouleid"), (int)$request->getParam("competitionid") );

            /** @var \Voetbal\PoulePlace $pouleplace */
            $pouleplaceSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\PoulePlace', 'json');
            if ( $pouleplaceSer === null ) {
                throw new \Exception("er kan geen pouleplek worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }
            $poulePlace = $this->repos->find($pouleplaceSer->getId());
            if ( $poulePlace === null ) {
                throw new \Exception("de pouleplek kan niet gevonden worden obv id", E_ERROR);
            }
            if ( $poulePlace->getPoule() !== $poule ) {
                throw new \Exception("de poule van de pouleplek komt niet overeen met de verstuurde poule", E_ERROR);
            }
            $competitor = $pouleplaceSer->getCompetitor() ? $this->competitorRepos->find($pouleplaceSer->getCompetitor()->getId()) : null;
            $poulePlace->setCompetitor($competitor);
            $this->repos->save($poulePlace);

            return $response
                ->withStatus(200)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize($poulePlace, 'json'));
        } catch (\Exception $e) {
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(422)->write($sErrorMessage);
    }

    protected function getPoule( int $pouleId, int $competitionId ): Poule
    {
        if ( $pouleId === null ) {
            throw new \Exception("het poule-id is niet meegegeven", E_ERROR);
        }
        if ( $competitionId === null ) {
            throw new \Exception("het competitie-id is niet meegegeven", E_ERROR);
        }

        $poule = $this->pouleRepos->find($pouleId);
        if ( $poule === null ) {
            throw new \Exception("er kan poule worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        $competition = $this->competitionRepos->find($competitionId);
        if ($competition === null) {
            throw new \Exception("er kan geen competitie worden gevonden o.b.v. de invoergegevens", E_ERROR);
        }
        if ($poule->getRound()->getNumber()->getCompetition() !== $competition) {
            throw new \Exception("de competitie van de poule komt niet overeen met de verstuurde competitie",
                E_ERROR);
        }
        return $poule;
    }

}