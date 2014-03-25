/*exported GptSlotConfig*/
/*global define*/
var GptSlotConfig = function() {
	'use strict';

	var slotMapConfig = {
		'gpt': {
			'CORP_TOP_LEADERBOARD': {'size': '728x90,1030x130,1030x65,1030x250,970x250,970x90,970x66,970x180,980x150,1x1', 'loc': 'top'},
			'CORP_TOP_RIGHT_BOXAD': {'size': '300x250,300x600,300x1050,1x1', 'loc': 'top'},
			'EXIT_STITIAL_BOXAD_1': {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'exit'},
			'HOME_TOP_LEADERBOARD': {'size': '728x90,1030x130,1030x65,1030x250,970x250,970x90,970x66,970x180,980x150,1x1', 'loc': 'top'},
			'HOME_TOP_RIGHT_BOXAD': {'size': '300x250,300x600,300x1050,1x1', 'loc': 'top'},
			'HUB_TOP_LEADERBOARD':  {'size': '728x90,1030x130,1030x65,1030x250,970x250,970x90,970x66,970x180,980x150,1x1', 'loc': 'top'},
			'INVISIBLE_SKIN': {'size': '1000x1000,1x1', 'loc': 'top', 'gptOnly': true},
			'LEFT_SKYSCRAPER_2': {'size': '160x600,1x1', 'loc': 'middle'},
			'LEFT_SKYSCRAPER_3': {'size': '160x600,1x1', 'loc': 'footer'},
			'MODAL_INTERSTITIAL':   {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'modal'},
			'MODAL_INTERSTITIAL_1': {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'modal'},
			'MODAL_INTERSTITIAL_2': {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'modal'},
			'MODAL_INTERSTITIAL_3': {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'modal'},
			'MODAL_INTERSTITIAL_4': {'size': '300x250,600x400,800x450,550x480,1x1', 'loc': 'modal'},
			'MODAL_RECTANGLE': {'size': '300x100,1x1', 'loc': 'modal'},
			'TEST_TOP_RIGHT_BOXAD': {'size': '300x250,300x600,300x1050,1x1', 'loc': 'top'},
			'TEST_HOME_TOP_RIGHT_BOXAD': {'size': '300x250,300x600,300x1050,1x1', 'loc': 'top'},
			'TOP_LEADERBOARD': {'size': '728x90,1030x130,1030x65,1030x250,970x250,970x90,970x66,970x180,980x150,1x1', 'loc': 'top'},
			'TOP_RIGHT_BOXAD': {'size': '300x250,300x600,300x1050,1x1', 'loc': 'top'},
			'WIKIA_BAR_BOXAD_1': {'size': '320x50,320x70,320x100,1x1', 'loc': 'bottom'},
			'GPT_FLUSH': 'flushonly'
		},
		'mobile': {
			'MOBILE_TOP_LEADERBOARD': {size: '320x50,1x1'},
			'MOBILE_IN_CONTENT': {size: '300x250,1x1'},
			'MOBILE_PREFOOTER': {size: '300x250,1x1'}
		},
		'rh': {
			'EXIT_STITIAL_BOXAD_1': {'size': '300x250,1x1', 'loc': 'exit'},
			'HOME_TOP_LEADERBOARD': {'size': '728x90,1x1', 'loc': 'top'},
			'HOME_TOP_RIGHT_BOXAD': {'size': '300x250,1x1', 'loc': 'top'},
			'LEFT_SKYSCRAPER_2': {'size': '160x600,1x1', 'loc': 'middle'},
			'LEFT_SKYSCRAPER_3': {'size': '160x600,1x1', 'loc': 'footer'},
			'TEST_TOP_RIGHT_BOXAD': {'size': '300x250,1x1', 'loc': 'top'},
			'TEST_HOME_TOP_RIGHT_BOXAD': {'size': '300x250,1x1', 'loc': 'top'},
			'TOP_BUTTON_WIDE': {'size': '292x90,1x1', 'loc': 'top'},

			'TOP_LEADERBOARD': {'size': '728x90,1x1', 'loc': 'top'},
			'TOP_RIGHT_BOXAD': {'size': '300x250,1x1', 'loc': 'top'},
			'PREFOOTER_LEFT_BOXAD': {'size': '300x250,1x1', 'loc': 'footer'},
			'PREFOOTER_RIGHT_BOXAD': {'size': '300x250,1x1', 'loc': 'footer'},
			'WIKIA_BAR_BOXAD_1': {'size': '300x70,1x1', 'loc': 'bottom'}
		},
		'mobile_remnant': {
			MOBILE_TOP_LEADERBOARD: {size: '320x50,1x1'},
			MOBILE_IN_CONTENT: {size: '300x250,1x1'},
			MOBILE_PREFOOTER: {size: '300x250,1x1'}
		}
	};

	function getConfig(src) {
		if (src) {
			return slotMapConfig[src];
		}

		return slotMapConfig;
	}

	return {
		getConfig: getConfig
	};

};

define('ext.wikia.adengine.gptslotconfig', [], GptSlotConfig);
