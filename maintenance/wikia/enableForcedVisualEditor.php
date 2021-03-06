<?php
/**
 * Enables the $wgForceVisualEditor WikiFactory variable for groups of wikis
 * depending on WAM score. The WF variable itself forces the Visual Editor
 * as the preferred editor on a wiki for anonymous and newly-registered users.
 *
 * @author Matt Klucsarits <mattk@wikia-inc.com>
 */
// Enable the WAM API extension so that the controller is loaded
$wgEnableWAMApiExt = true;
ini_set( 'include_path', dirname(__FILE__).'/../' );
require_once( 'commandLine.inc' );

function getVEForcedValue( $wikiId ) {
	$wikiFactoryVar = WikiFactory::getVarByName( 'wgForceVisualEditor', $wikiId );
	return ( is_object( $wikiFactoryVar ) && $wikiFactoryVar->cv_value ) ?
		unserialize( $wikiFactoryVar->cv_value ) : false;
}

// Use the --disable option to set $wgForceVisualEditor to false
$forceVisualEditor = isset( $options['disable'] ) ? false : true;
// Highest WAM rank at which to change the variable (lower number = higher rank)
if ( !isset( $options['wam'] ) ) {
	die( "You must specify a max WAM score with the --wam=N option (use 0 for all wikis).\n");
}
elseif ( $options['wam'] > 5000 || $options['wam'] < 0 ) {
	die( "WAM score must be a value between 0 and 5000.\n");
}
$wamThreshold = $options['wam'];

// First get the WAM index
$wamWikis = array();
$app = F::app();
$offset = 0;
$limit = WAMApiController::DEFAULT_PAGE_SIZE;
echo "Gathering WAM rankings";
while ( $offset < 5000 ) {
	echo '.';
	$wamData = $app->sendRequest( 'WAMApi', 'getWAMIndex', array( 'offset' => $offset ) )->getData();

	if ( empty( $wamData ) || !is_array( $wamData['wam_index'] ) ) {
		// Unexpected return values -- is something broken?
		echo "\nWarning: Invalid or missing data returned from WAM API.\n";
		break;
	}
	elseif ( empty( $wamData['wam_index'] ) ) {
		// Unexpectedly reached end of list
		echo "\nWarning: Unexpectedly reached end of WAM list (less than 5000 wikis indexed).\n";
		break;
	}

	foreach ( $wamData['wam_index'] as $wikiId => $data ) {
		$wamWikis[$wikiId] = $data['wam_rank'];
	}

	$offset += $limit;
}

echo "\n";
// Get all wiki IDs from the database
$dbr = wfGetDB( DB_MASTER, array(), $wgExternalSharedDB );

echo "Fetching all wikis from database...\n";
$result = $dbr->select( 'city_list', 'city_id, city_title, city_url' );

$allWikis = array();
while ( $row = $dbr->fetchObject( $result ) ) {
	$allWikis[] = $row;
}

foreach ( $allWikis as $wiki ) {
	if ( !isset( $wamWikis[$wiki->city_id] ) ) {
		$wamText = 'No WAM score';
	} elseif ( $wamWikis[$wiki->city_id] >= $wamThreshold ) {
		$wamText = 'WAM rank '.$wamWikis[$wiki->city_id];
	} else {
		// If the wiki has a WAM score and the score is higher (numerically less) than the WAM score threshold parameter, ignore it
		continue;
	}

	$wikiFactoryVar = WikiFactory::getVarByName( 'wgForceVisualEditor', $wiki->city_id );
	if ( getVEForcedValue( $wiki->city_id ) !== $forceVisualEditor ) {
		echo 'Setting $wgForceVisualEditor to '.( $forceVisualEditor ? 'TRUE' : 'FALSE' ).' for '.$wiki->city_title.' ('.$wiki->city_url.") -- $wamText\n";
		// Safety switch: Uncomment two lines below if you know what you are doing.
		//WikiFactory::setVarByName( 'wgForceVisualEditor', $wiki->city_id, $forceVisualEditor );
		//WikiFactory::clearCache( $wiki->city_id );
	}
}

echo "Done.\n";
