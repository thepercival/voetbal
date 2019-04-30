<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 25-1-17
 * Time: 19:10
 */

namespace Voetbal\Association;

use Voetbal\Association;
use Voetbal\Association\Repository as AssociationRepository;

class Service
{
	/**
	 * @var AssociationRepository
	 */
	protected $repos;

	/**
	 * Service constructor.
	 *
	 * @param AssociationRepository $repos
	 */
	public function __construct( AssociationRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param string $name
     * @param string|null $description
     * @return Association
     * @throws \Exception
     */
    public function create( string $name, string $description = null ): Association
    {
        $associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $associationWithSameName !== null ){
            throw new \Exception("de bond met de naam ".$name." bestaat al", E_ERROR );
        }
        $association = new Association($name);
        $association->setDescription($description);
        return $association;
    }

    public function changeBasics( Association $association, $name, $description )
    {
        $associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $associationWithSameName !== null and $associationWithSameName !== $association ){
            throw new \Exception("de bond met de naam ".$name." bestaat al", E_ERROR );
        }
        $association->setName($name);
        $association->setDescription($description);
        return $association;
    }

    public function changeParent( Association $association, Association $parentAssociation )
    {
        $descendants = $this->getDescendants($association);
        $descendants[$association->getId()] = $association;
        $ancestors = $this->getAncestors($parentAssociation);
        $ancestors[$parentAssociation->getId()] = $parentAssociation;
        foreach( $ancestors as $ancestor ) {
            if( array_key_exists( $ancestor->getId(), $descendants ) ) {
                throw new \Exception("er ontstaat een circulaire relatie tussen de bonden", E_ERROR );
            }
        }
        $association->setParent($parentAssociation);
        return $association;
    }

    protected function getDescendants( Association $association) {
        $descendants = [];
        $this->getDescendantsHelper( $association, $descendants );
        return $descendants;
    }

    protected function getDescendantsHelper( Association $association, &$descendants ) {
        foreach( $association->getChildren() as $child ) {
            $descendants[$association->getId()] = $association;
            $this->getDescendantsHelper( $child, $descendants );
        }
    }

    protected function getAncestors( Association $association) {
        $ancestors = [];
        $this->getAncestorsHelper( $association, $ancestors );
        return $ancestors;
    }

    protected function getAncestorsHelper( Association $association, &$ancestors ) {
        if( $association->getParent() !== null ) {
            $ancestors[$association->getParent()->getId()] = $association->getParent();
            $this->getAncestorsHelper( $association->getParent(), $descendants );
        }
    }
}