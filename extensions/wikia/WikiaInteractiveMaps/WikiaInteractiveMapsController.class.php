<?php
/**
 * Class WikiaInteractiveMapsController
 * @desc Special:WikiaInteractiveMaps controller
 */
class WikiaInteractiveMapsController extends WikiaSpecialPageController {

	const MAP_HEIGHT = 300;
	const MAP_WIDTH = 1600;

	/**
	 * @desc Special page constructor
	 *
	 * @param null $name
	 * @param string $restriction
	 * @param bool $listed
	 * @param bool $function
	 * @param string $file
	 * @param bool $includable
	 */
	public function __construct( $name = null, $restriction = 'editinterface', $listed = true, $function = false, $file = 'default', $includable = false ) {
		parent::__construct( 'InteractiveMaps', $restriction, $listed, $function, $file, $includable );
	}

	/**
	 * Interactive maps special page
	 */
	public function index() {
		$this->wg->SuppressPageHeader = true;
		$this->wg->out->setHTMLTitle( wfMessage( 'wikia-interactive-maps-title' )->escaped() );

		$mapsModel = new WikiaMaps( $this->wg->IntMapConfig );
		$params = [
			'city_id' => $this->app->wg->CityId
		];

		$maps = $mapsModel->cachedRequest( 'getMapsFromApi', $params );

		// Add map size to maps
		array_walk( $maps, function( &$map ) {
			$map[ 'map_width' ] = self::MAP_WIDTH;
			$map[ 'map_height' ] = self::MAP_HEIGHT;
		});

		$this->setVal( 'maps', $maps );
		$this->setVal( 'hasMaps', !empty( $maps ) );
		$messages = [
			'wikia-interactive-maps-title' => wfMessage( 'wikia-interactive-maps-title' ),
			'wikia-interactive-maps-create-a-map' => wfMessage( 'wikia-interactive-maps-create-a-map' ),
			'wikia-interactive-maps-no-maps' => wfMessage( 'wikia-interactive-maps-no-maps' )
		];
		$this->setVal( 'messages', $messages );

		$this->response->addAsset( 'extensions/wikia/WikiaInteractiveMaps/css/WikiaInteractiveMaps.scss' );
		$this->response->setTemplateEngine( WikiaResponse::TEMPLATE_ENGINE_MUSTACHE );
	}

	/**
	 * @desc Displays map page
	 *
	 * @requestParam Integer $map_id
	 */
	public function map() {

		$mapId = $this->request->getInt( 'map_id' );
		$title = Title::newFromID( $mapId );

		if( !is_null( $title ) ) {
			$this->setVal( 'notCreated', false );

			$article = Article::newFromID( $title->getArticleId() );
			$mapId = $article->getContent();

			$mapsModel = new WikiaMaps( $this->wg->IntMapConfig );

			$mapUrl = $mapsModel->buildUrl( 'render/' . $mapId );
			$this->setVal( 'map_url', $mapUrl );
			$this->setVal( 'width', '100%' );
			$this->setVal( 'height', '600' );

		} else {
			$this->setVal( 'notCreated', true );
		}

		$this->response->setTemplateEngine( WikiaResponse::TEMPLATE_ENGINE_MUSTACHE );
	}
}
