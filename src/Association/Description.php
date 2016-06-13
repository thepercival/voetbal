<?php

namespace Voetbal\Association;

/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 8-2-2016
 * Time: 11:40
 */
class Description
{
	private $m_description;
	const MAX_LENGTH = 100;

	public function __construct( $description )
	{
		if ( strlen( $description ) < 1 or strlen( $description ) > static::MAX_LENGTH )
			throw new \InvalidArgumentException( "de omschrijving moet minimaal 1 karakter bevatten en mag maximaal ".static::MAX_LENGTH." karakters bevatten", E_ERROR );

		$this->m_description = $description;
	}

	public function __toString()
	{
		return $this->m_description;
	}
}