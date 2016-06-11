<?php

namespace Voetbal;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */
class LeagueAbbreviation
{
	private $m_abbreviation;
	const MAX_LENGTH = 5;

	public function __construct( $abbreviation )
	{
		if ( strlen( $abbreviation ) < 1 or strlen( $abbreviation ) > static::MAX_LENGTH )
			throw new \InvalidArgumentException( "de afkorting moet minimaal 1 karakter bevatten en mag maximaal ".static::MAX_LENGTH." karakters bevatten", E_ERROR );

		$this->m_abbreviation = $abbreviation;
	}

	public function __toString()
	{
		return $this->m_abbreviation;
	}
}