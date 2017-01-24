<?php

namespace Voetbal\Association;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */
class Name
{
	private $m_name;
	const MAX_LENGTH = 20;

	public function __construct( $name )
	{
		if ( strlen( $name ) < 1 or strlen( $name ) > static::MAX_LENGTH )
			throw new \InvalidArgumentException( "de naam moet minimaal 1 karakter bevatten en mag maximaal ".static::MAX_LENGTH." karakters bevatten", E_ERROR );

		// alleen letters spaties en punten en streepjes
		if(preg_match('/[^a-z .]/i', $name))
		{
			throw new \InvalidArgumentException( "de naam mag alleen letters, spaties en punten bevatten", E_ERROR );
		}

		$this->m_name = $name;
	}

	public function __toString()
	{
		return $this->m_name;
	}
}