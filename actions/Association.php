<?php
/**
 * Created by PhpStorm.
 * User: coen
 * Date: 2-2-17
 * Time: 21:49
 */

namespace Voetbal\Action;

use JMS\Serializer\Serializer;
use Voetbal\Association\Service as AssociationService;
use Voetbal\Association\Repository as AssociationRepository;
use Voetbal;

final class Association
{
    /**
     * @var AssociationService
     */
	protected $service;
    /**
     * @var AssociationRepository
     */
    protected $repos;
    /**
     * @var Serializer
     */
    protected $serializer;

	public function __construct(AssociationService $service, AssociationRepository $repos, Serializer $serializer)
	{
		$this->repos = $repos;
        $this->service = $service;
		$this->serializer = $serializer;
	}

	public function fetch( $request, $response, $args)
	{

		$objects = $this->repos->findAll();
		return $response
			->withHeader('Content-Type', 'application/json;charset=utf-8')
			->write( $this->serializer->serialize( $objects, 'json') );
		;

	}

	public function fetchOne( $request, $response, $args)
	{
        $object = $this->repos->find($args['id']);
		if ($object) {
			return $response
				->withHeader('Content-Type', 'application/json;charset=utf-8')
				->write($this->serializer->serialize( $object, 'json'));
			;
		}
		return $response->withStatus(404, 'geen bond met het opgegeven id gevonden');
	}

	public function add( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Association $associationSer */
            $associationSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Association', 'json');
            if ( $associationSer === null ) {
                throw new \Exception("er kan geen bond worden toegevoegd o.b.v. de invoergegevens", E_ERROR);
            }
            $associationWithSameName = $this->repos->findOneBy( array('name' => $associationSer->getName() ) );
            if ( $associationWithSameName !== null ){
                throw new \Exception("de bond ".$associationSer->getName()." bestaat al", E_ERROR );
            }
            $parentAssociation = null;
            if( $associationSer->getParent() !== null ) {
                $parentAssociation = $this->repos->find($associationSer->getParent()->getId());
            }

            $associationSer->setParent($parentAssociation);

            $associationRet = $this->repos->save( $associationSer );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $associationRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function edit( $request, $response, $args)
	{
        $sErrorMessage = null;
        try {
            /** @var \Voetbal\Association $associationSer */
            $associationSer = $this->serializer->deserialize(json_encode($request->getParsedBody()), 'Voetbal\Association', 'json');
            if ( $associationSer === null ) {
                throw new \Exception("er kan geen bond worden gewijzigd o.b.v. de invoergegevens", E_ERROR);
            }

            $association = $this->repos->find($associationSer->getId());
            if ( $association === null ) {
                throw new \Exception("de bond kon niet gevonden worden o.b.v. de invoer", E_ERROR);
            }
            $parentAssociation = null;
            if( $associationSer->getParent() !== null ) {
                $parentAssociation = $this->repos->find($associationSer->getParent()->getId());
            }

            $associationWithSameName = $this->repos->findOneBy( array( 'name' => $associationSer->getName() ) );
            if ( $associationWithSameName !== null and $association->getId() !== $associationWithSameName->getId() ){
                throw new \Exception("de bond ".$associationSer->getName()." bestaat al", E_ERROR );
            }

            $association->setName( $associationSer->getName() );
            $association->setDescription( $associationSer->getDescription() );
            $association->setParent( $parentAssociation );
            $associationRet = $this->repos->save( $association );

            return $response
                ->withStatus(201)
                ->withHeader('Content-Type', 'application/json;charset=utf-8')
                ->write($this->serializer->serialize( $associationRet, 'json'));
            ;
        }
        catch( \Exception $e ){
            $sErrorMessage = $e->getMessage();
        }
        return $response->withStatus(404)->write( $sErrorMessage );
	}

	public function remove( $request, $response, $args)
	{
		$association = $this->repos->find($args['id']);
		$sErrorMessage = null;
		try {
			$this->service->remove($association);

			return $response
				->withStatus(204);
			;
		}
		catch( \Exception $e ){
			$sErrorMessage = $e->getMessage();
		}
		return $response->withStatus(404)->write( $sErrorMessage );
	}
}