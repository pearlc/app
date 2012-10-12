<?php
/**
 * @author ADi
 * @author Jacek Jursza
 */

class StructuredDataController extends WikiaSpecialPageController {

	protected $config = null;
	/**
	 * @var StructuredDataAPIClient
	 */
	protected $APIClient = null;
	/**
	 * @var StructuredData
	 */
	protected $structuredData = null;

	protected $mainObjectList = null;

	public function __construct() {

		$this->mainObjectList = array(
			"cod:Character" => "Characters",
			"cod:Faction" => "Factions",
			"cod:Timeline" => "Timelines",
			"cod:Weapon" => "Weapons",
			"cod:WeaponClass" => "Weapon Class"
		);

		// parent SpecialPage constructor call MUST be done
		parent::__construct( 'StructuredData', '', false );

	}

	public function init() {
		$this->config = $this->wg->StructuredDataConfig;
		$this->APIClient = F::build( 'StructuredDataAPIClient', array( 'baseUrl' => $this->config['baseUrl'], 'apiPath' => $this->config['apiPath'], 'schemaPath' => $this->config['schemaPath'] ) );
		$this->structuredData = F::build( 'StructuredData', array( 'apiClient' => $this->APIClient ));
	}

	public function index() {
		$this->wg->Out->addHTML( F::build('JSSnippets')->addToStack( array( "/extensions/wikia/StructuredData/js/StructuredData.js" ) ) );
		//$this->response->addAsset('extensions/wikia/StructuredData/css/StructuredData.scss');

		$this->setVal( "mainObjects", $this->mainObjectList );
	}

	public function getObject() {
		// force json format
		$this->getResponse()->setFormat( 'json' );

		$id = $this->request->getVal( 'id', false );

		if(!empty($id)) {
			$object = $this->structuredData->getSDElement( $id );

			$this->response->setBody( (string) $object );
		}
	}

	public function getCollection() {

		$objectType = $this->request->getVal( 'objectType', false );
		if( !empty( $objectType ) ) {
			$collection = $this->APIClient->getCollection( $objectType );
			$this->response->setVal( "list", $collection );
		}
	}

	public function getTemplate() {
		// force json format
		$this->getResponse()->setFormat( 'json' );

		$objectType = $this->request->getVal( 'objectType', false );

		if(!empty($objectType)) {
			$template = $this->APIClient->getTemplate( $objectType, true );

			$this->response->setBody( $template );
		}
	}

}
