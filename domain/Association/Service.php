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
	 * @param AssociationRepository $associationRepos
	 */
	public function __construct( AssociationRepository $repos )
	{
		$this->repos = $repos;
	}

    /**
     * @param Association $associationSer
     * @return mixed
     * @throws \Exception
     */
    public function create( Association $associationSer )
    {
        $associationWithSameName = $this->repos->findOneBy( array('name' => $associationSer->getName() ) );
        if ( $associationWithSameName !== null ){
            throw new \Exception("de bond met de naam ".$associationSer->getName()." bestaat al", E_ERROR );
        }
        return $this->repos->save($associationSer);
    }

    public function changeBasics( Association $association, $name, $description )
    {
        $associationWithSameName = $this->repos->findOneBy( array('name' => $name ) );
        if ( $associationWithSameName !== null and $associationWithSameName !== $association ){
            throw new \Exception("de bond met de naam ".$name." bestaat al", E_ERROR );
        }

        $association->setName($name);
        $association->setDescription($description);

        return $this->repos->save($association);
    }

    public function changeParent( Association $association, Association $parentAssociation )
    {
        $descendants = $association->getDescendants();
        $descendants[$association->getId()] = $association;
        $ancestors = $parentAssociation->getAncestors();
        $ancestors[$parentAssociation->getId()] = $parentAssociation;
        foreach( $ancestors as $ancestor ) {
            if( array_key_exists( $ancestor->getId(), $descendants ) ) {
                throw new \Exception("er ontstaat een circulaire relatie tussen de bonden", E_ERROR );
            }
        }
        $association->setParent($parentAssociation);
        return $this->repos->save($association);
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

    /**
     * @param Association $association
     */
	public function remove( Association $association )
	{
		$this->repos->remove($association);
	}
}