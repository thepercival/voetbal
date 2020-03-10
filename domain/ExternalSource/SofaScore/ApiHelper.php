<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 6-3-18
 * Time: 20:53
 */

namespace Voetbal\ExternalSource\SofaScore;

use Voetbal\ExternalSource;
use Voetbal\ExternalSource\Season as ExternalSeason;
use Voetbal\ExternalSource\League as ExternalLeague;
use Voetbal\CacheItemDb\Repository as CacheItemDbRepository;
use Voetbal\CacheItemDb;
use GuzzleHttp\Client;

class ApiHelper
{
    /**
     * @var ExternalSource
     */
    private $externalSource;
    /**
     * @var CacheItemDbRepository
     */
    private $cacheItemDbRepos;
    /**
     * @var Client
     */
    private $client;

    public function __construct(
        ExternalSource $externalSource,
        CacheItemDbRepository $cacheItemDbRepos
    ) {
        $this->cacheItemDbRepos = $cacheItemDbRepos;
        $this->externalSource = $externalSource;
    }

    protected function getClient()
    {
        if ($this->client === null) {
            $this->client = new Client();
        }
        return $this->client;
    }

    protected function getHeaders()
    {
        return [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 5
            ],
            'headers' => []
        ];
    }

    public function getData(string $postUrl, int $cacheMinutes)
    {
        $data = $this->cacheItemDbRepos->getItem( $postUrl );
        if ( $data !== null ) {
            return json_decode($data);
        }

        $response = $this->getClient()->get(
            $this->externalSource->getApiurl() . $postUrl . $this->getUrlPostfix(),
            $this->getHeaders()
        );
        return json_decode(
            $this->cacheItemDbRepos->saveItem( $postUrl, $response->getBody()->getContents(), $cacheMinutes )
        );
    }

    protected function getUrlPostfix()
    {
        return "?_=" . (new \DateTimeImmutable())->getTimestamp();
    }

    public function getCurrentDateAsString(): string
    {
        return (new \DateTimeImmutable())->format("Y-m-d");
    }

    /*
        public function getLeague(ExternalLeague $externalLeague): ?\stdClass
        {
            $leagues = $this->getData("competitions/?plan=TIER_ONE")->competitions;
            $foundLeagues = array_filter(
                $leagues,
                function ($league) use ($externalLeague) {
                    return $league->id === (int)$externalLeague->getExternalId();
                }
            );
            if (count($foundLeagues) !== 1) {
                return null;
            }
            return reset($foundLeagues);
        }

        public function getCompetition(ExternalLeague $externalLeague, ExternalSeason $externalSeason): ?\stdClass
        {
            $externalSystemLeague = $this->getLeague($externalLeague);
            if ($externalSystemLeague === null) {
                return null;
            }

            $externalSystemLeagueDetails = $this->getData("competitions/" . $externalSystemLeague->id);

            $leagueSeaons = $externalSystemLeagueDetails->seasons;
            $foundLeagueSeaons = array_filter(
                $leagueSeaons,
                function ($leagueSeaon) use ($externalSeason) {
                    return substr($leagueSeaon->startDate, 0, 4) === $externalSeason->getExternalId();
                }
            );
            if (count($foundLeagueSeaons) !== 1) {
                return null;
            }
            return reset($foundLeagueSeaons);
        }

        public function getRounds(ExternalLeague $externalLeague, ExternalSeason $externalSeason): ?array
        {
            $matches = $this->getGames($externalLeague, $externalSeason);
            if ($this->getRoundsHelperAllMatchesHaveDate($matches) !== true) {
                return [];
            }
            uasort(
                $matches,
                function ($matchA, $matchB) {
                    return $matchA->utcDate < $matchB->utcDate;
                }
            );

            $rounds = [];
            foreach ($matches as $match) {
                if (array_key_exists($match->stage, $rounds) === false) {
                    $round = new \stdClass();
                    $round->name = $match->stage;
                    $round->poules = $this->getRoundsHelperGetPoules($matches, $match->stage);
                    $rounds[$match->stage] = $round;
                }
            }
            return $rounds;
        }

        protected function getRoundsHelperAllMatchesHaveDate(array $matches): bool
        {
            foreach ($matches as $match) {
                if (strlen($match->utcDate) === 0) {
                    return false;
                }
            }
            return true;
        }


        protected function getRoundsHelperGetPoules(array $matches, string $stage): array
        {
            $stageMatches = array_filter(
                $matches,
                function ($match) use ($stage) {
                    return $match->stage === $stage;
                }
            );
            $poules = $this->getRoundsHelperGetPoulesHelper($stageMatches);
            if (count($poules) === 0) {
                throw new \Exception("no places to be found for stage " . $stage, E_ERROR);
            }
            return $poules;
        }

        protected function getRoundsHelperGetPoulesHelper(array $stageMatches): array
        {
            $movePlaces = function (&$poules, $oldPoule, $newPoule) {
                $newPoule->places = array_merge($oldPoule->places, $newPoule->places);
                unset($poules[array_search($oldPoule, $poules)]);
            };

            $poules = [];
            foreach ($stageMatches as $stageMatch) {
                $homeCompetitorId = $stageMatch->homeTeam->id;
                $awayCompetitorId = $stageMatch->awayTeam->id;
                if ($homeCompetitorId === null || $awayCompetitorId === null) {
                    continue;
                }
                $homePoule = $this->getRoundsHelperGetPoule($poules, $homeCompetitorId);
                $awayPoule = $this->getRoundsHelperGetPoule($poules, $awayCompetitorId);
                if ($homePoule === null && $awayPoule === null) {
                    $poule = new \stdClass();
                    $poule->places = [$homeCompetitorId, $awayCompetitorId];
                    $poule->games = [];
                    $poules[] = $poule;
                } elseif ($homePoule !== null && $awayPoule === null) {
                    $homePoule->places[] = $awayCompetitorId;
                } elseif ($homePoule === null && $awayPoule !== null) {
                    $awayPoule->places[] = $homeCompetitorId;
                } elseif ($homePoule !== $awayPoule) {
                    $movePlaces($poules, $awayPoule, $homePoule);
                }
            }
            $this->getRoundsHelperGetPoulesHelperExt($poules, $stageMatches);
            return $poules;
        }

        protected function getRoundsHelperGetPoulesHelperExt(array &$poules, array $stageMatches)
        {
            foreach ($stageMatches as $stageMatch) {
                $homeCompetitorId = $stageMatch->homeTeam->id;
                $awayCompetitorId = $stageMatch->awayTeam->id;
                if ($homeCompetitorId === null || $awayCompetitorId === null) {
                    continue;
                }
                $homePoule = $this->getRoundsHelperGetPoule($poules, $homeCompetitorId);
                $awayPoule = $this->getRoundsHelperGetPoule($poules, $awayCompetitorId);
                if ($homePoule === null || $homePoule !== $awayPoule) {
                    continue;
                }
                $homePoule->games[] = $stageMatch;
            }

            foreach ($poules as $poule) {
                $nrOfPlaces = count($poule->places);
                $nrOfGames = count($poule->games);

                $nrOfGamesPerGameRound = ($nrOfPlaces - ($nrOfPlaces % 2)) / 2;
                $nrOfGameRounds = ($nrOfGames / $nrOfGamesPerGameRound);
                $poule->nrOfHeadtohead = $nrOfGameRounds / ($nrOfPlaces - 1);
            }
        }

        protected function getRoundsHelperGetPoule($poules, $competitorId): ?\stdClass
        {
            foreach ($poules as $poule) {
                if (array_search($competitorId, $poule->places) !== false) {
                    return $poule;
                }
            }
            return null;
        }

        public function getCompetitors(ExternalLeague $externalLeague, ExternalSeason $externalSeason): ?array
        {
            $retVal = $this->getData(
                "competitions/" . $externalLeague->getExternalId() . "/teams?season=" . $externalSeason->getExternalId()
            );
            return $retVal->teams;
        }

        public function getGames(
            ExternalLeague $externalLeague,
            ExternalSeason $externalSeason,
            string $stage = null // round
        ): ?array
        {
            $retVal = $this->getData(
                "competitions/" . $externalLeague->getExternalId() . "/matches?season=" . $externalSeason->getExternalId()
            );
            if ($stage === null) {
                return $retVal->matches;
            }
            return array_filter(
                $retVal->matches,
                function ($match) use ($stage) {
                    return $match->stage === $stage;
                }
            );
        }

        public function getGame(
            ExternalLeague $externalLeague,
            ExternalSeason $externalSeason,
            string $stage = null
            // round
            ,
            int $externalSystemGameId
        ): ?stdClass {
            $games = $this->getGames($externalLeague, $externalSeason, $stage);
            $filteredGames = array_filter(
                $games,
                function ($game) use ($externalSystemGameId) {
                    return $game->id === $externalSystemGameId;
                }
            );
            return reset($filteredGames);
        }

        public function getDate(string $date)
        {
            if (strlen($date) === 0) {
                return null;
            }
            return \DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $date);
        }*/
}