<?php
/**
 * Created by PhpStorm.
 * User: cdunnink
 * Date: 9-2-2016
 * Time: 10:50
 */

namespace Voetbal\Command\Handler;

use Voetbal\Association;
use Voetbal\Service;
use Voetbal\DAO\Association as DAOAssociation;

class AssociationAdd
{
    public function handle( \Voetbal\Command\AssociationAdd $command)
    {
        $entityManager = Service::getEntityManager();
        // check if name not exists in repository

        $associationRepos = $entityManager->getRepository("Voetbal\\DAO\\Association");
        $oDAOAssociation = $associationRepos->findOneBy( array('name' => $command->getName() ) );
        if ( $oDAOAssociation !== null )
            throw new \Exception("de bondsnaam ".$command->getName()." bestaat al", E_ERROR );

        $oAssociation = new Association( $command->getName() );
        $oAssociation->setDescription( $command->getDescription() );

        $oDAOParentAssociation = null;
        $oParentAssociation = $command->getParent();
        if ( $oParentAssociation !== null )
        {
            $oDAOParentAssociation = $associationRepos->findOneBy( array('name' => $oParentAssociation->getName() ) );
            if ( $oDAOParentAssociation === null )
                throw new \Exception("de hoofdbond bestaat niet", E_ERROR );
        }
        $oAssociation->setParent( $oParentAssociation );

        $oDAOAssociation = new DAOAssociation();
        $oDAOAssociation->setName( $oAssociation->getName() );
        $oDAOAssociation->setDescription( $oAssociation->getDescription() );
        $oDAOAssociation->setParent( $oDAOParentAssociation );

        $entityManager->persist( $oDAOAssociation );
        $entityManager->flush();

        // return LeagueRepository::add( $oLeague );
        return $oAssociation;
    }
}