<?php

class UserProfilePageController extends WikiaController {
	const AVATAR_DEFAULT_SIZE = 150;
	const AVATAR_MAX_SIZE = 512000;
	const MAX_TOP_WIKIS = 4;

	/**
	 * @var $profilePage UserProfilePage
	 */
	protected $profilePage = null;
	protected $allowedNamespaces = null;
	protected $title = null;

	protected $defaultAvatars = null;
	protected $defaultAvatarPath = 'http://images.wikia.com/messaging/images/';

	public function __construct(WikiaApp $app = null) {
		global $UPPNamespaces;
		if( is_null( $app ) ) {
			$app = F::app();
		}
		$this->app = $app;
		$this->allowedNamespaces = $UPPNamespaces;
		$this->title = $app->wg->Title;
	}

	/**
	 * @brief main entry point
	 *
	 * @requestParam User $user user object
	 * @requestParam string $userPageBody original page body
	 * @requestParam int $wikiId current wiki id
	 */
	public function index() {
		wfProfileIn(__METHOD__);

		/**
		 * @var $user User
		 */
		$user = $this->getVal('user');

		$pageBody = $this->getVal('userPageBody');

		if ($this->title instanceof Title) {
			$namespace = $this->title->getNamespace();
			$isSubpage = $this->title->isSubpage();
		} else {
			$namespace = $this->app->wg->NamespaceNumber;
			$isSubpage = false;
		}

		$useOriginalBody = true;

		if ($user instanceof User) {
			$this->profilePage = new UserProfilePage($user);
			if ($namespace == NS_USER && !$isSubpage) {
				//we'll implement interview section later
				//$this->setVal( 'questions', $this->profilePage->getInterviewQuestions( $wikiId, true ) );
				$this->setVal('stuffSectionBody', $pageBody);
				$useOriginalBody = false;
			}

			$this->setVal('isUserPageOwner', (($user->getId() == $this->wg->User->getId()) ? true : false));
		}

		if ($useOriginalBody) {
			$this->response->setBody($pageBody);
		}

		if ( $this->app->checkSkin( 'wikiamobile' ) ) {
			$this->overrideTemplate( 'WikiaMobileIndex' );
		}

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Renders new user identity box
	 *
	 * @desc Creates array of user's data and passes it to the template
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function renderUserIdentityBox() {
		wfProfileIn(__METHOD__);

		$this->setVal('wgBlankImgUrl', $this->wg->BlankImgUrl);

		$this->app->wg->Out->addStyle(AssetsManager::getInstance()->getSassCommonURL('extensions/wikia/UserProfilePageV3/css/UserProfilePage.scss'));
		$this->wg->Out->addScriptFile($this->wg->ExtensionsPath . '/wikia/UserProfilePageV3/js/UserProfilePage.js');

		$sessionUser = $this->wg->User;

		$this->setRequest(new WikiaRequest($this->app->wg->Request->getValues()));
		$user = UserProfilePageHelper::getUserFromTitle();
		/**
		 * @var $userIdentityBox UserIdentityBox
		 */
		$userIdentityBox = new UserIdentityBox( $user );
		$isUserPageOwner = (!$user->isAnon() && $user->getId() == $sessionUser->getId()) ? true : false;

		if ($isUserPageOwner) {
//		this is a small trick to remove some
//		probably cache/session lag. before, i used
//		phalanx to block my user globally. but just
//		after i blocked him the session user object
//		still returned false when i called on it getBlockedStatus()
//		in the same time user object retrived from title
//		returned id of user block. after more or less an hour
//		both instances of User object returned me id of user block
//		when i called getBlockedStatus() on them
			$sessionUser = $user;
		}

		$userData = $userIdentityBox->getFullData();

		$this->setVal('isBlocked', ($user->isBlocked() || $user->isBlockedGlobally()));
		$this->setVal('zeroStateCssClass', ($userData['showZeroStates']) ? 'zero-state' : '');

		$this->setVal('user', $userData);
		$this->setVal('deleteAvatarLink', SpecialPage::getTitleFor('RemoveUserAvatar')->getFullUrl('av_user=' . $userData['name']));
		$this->setVal('canRemoveAvatar', $sessionUser->isAllowed('removeavatar'));
		$this->setVal('isUserPageOwner', $isUserPageOwner);

		$canEditProfile = $isUserPageOwner || $sessionUser->isAllowed('editprofilev3');
		//if user is blocked locally (can't edit anything) he can't edit masthead too
		$canEditProfile = $sessionUser->isAllowed('edit') ? $canEditProfile : false;
		//if user is blocked globally he can't edit
		$blockId = $sessionUser->getBlockId();
		$canEditProfile = empty($blockId) ? $canEditProfile : false;
		$this->setVal('canEditProfile', $canEditProfile);
		$this->setVal('isWikiStaff', $sessionUser->isAllowed('staff'));
		$this->setVal('canEditProfile', ($isUserPageOwner || $sessionUser->isAllowed('staff') || $sessionUser->isAllowed('editprofilev3')));

		if (!empty($this->title)) {
			$this->setVal('reloadUrl', htmlentities($this->title->getFullUrl(), ENT_COMPAT, 'UTF-8'));
		} else {
			$this->setVal('reloadUrl', '');
		}

		$this->response->addAsset('resources/wikia/modules/aim.js');

		if ( $this->app->checkSkin( 'wikiamobile' ) ) {
			$this->overrideTemplate( 'WikiaMobileRenderUserIdentityBox' );
		}

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Renders new action button
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function renderActionButton() {
		wfProfileIn(__METHOD__);

		$namespace = $this->title->getNamespace();

		$this->setRequest(new WikiaRequest($this->app->wg->Request->getValues()));
		$user = UserProfilePageHelper::getUserFromTitle();

		/**
		 * @var $sessionUser User
		 */
		$sessionUser = $this->wg->User;
		$canRename = $sessionUser->isAllowed('renameprofilev3');
		$canProtect = $sessionUser->isAllowed('protect');
		$canDelete = $sessionUser->isAllowed('deleteprofilev3');
		$isUserPageOwner = ($user instanceof User && !$user->isAnon() && $user->getId() == $sessionUser->getId()) ? true : false;

		$editQuery = array('action' => 'edit');

		// check if this is an older version of the page
		$oldid = $this->app->wg->Request->getInt('oldid', 0);
		if ($oldid) {
			$editQuery['oldid'] = $oldid;
		}

		$actionButtonArray = array();
		if ($namespace == NS_USER) {
			//profile page
			$actionButtonArray = array(
				'action' => array(
					'href' => $this->title->getLocalUrl($editQuery),
					'text' => wfMsg('user-action-menu-edit-profile'),
				),
				'image' => MenuButtonController::EDIT_ICON,
				'name' => 'editprofile',
			);
		} else {
			if ($namespace == NS_USER_TALK && empty($this->app->wg->EnableWallExt)) {
				//talk page
				/**
				 * @var $title Title
				 */
				$title = Title::newFromText($user->getName(), NS_USER_TALK);

				if ($title instanceof Title) {
					//sometimes title isn't created, i've tried to reproduce it on my devbox and i couldn't
					//checking if $title is instance of Title is a quick fix -- if it isn't no action button will be shown
					if ($isUserPageOwner || $this->app->wg->Request->getVal('oldid')) {
						$actionButtonArray = array(
							'action' => array(
								'href' => $this->title->getLocalUrl($editQuery),
								'text' => wfMsg('user-action-menu-edit'),
							),
							'image' => MenuButtonController::EDIT_ICON,
							'name' => 'editprofile',
						);
					} else {
						$actionButtonArray = array(
							'action' => array(
								'href' => $title->getLocalUrl(array_merge($editQuery, array('section' => 'new'))),
								'text' => wfMsg('user-action-menu-leave-message'),
							),
							'image' => MenuButtonController::MESSAGE_ICON,
							'name' => 'leavemessage',
							'dropdown' => array(
								'edit' => array(
									'href' => $this->title->getFullUrl($editQuery),
									'text' => wfMsg('user-action-menu-edit'),
								)
							),
						);
					}
				}
			} else {
				if (defined('NS_BLOG_ARTICLE') && $namespace == NS_BLOG_ARTICLE && $isUserPageOwner) {
					//blog page
					global $wgCreateBlogPagePreload;

					$actionButtonArray = array(
						'action' => array(
							'href' => SpecialPage::getTitleFor('CreateBlogPage')->getLocalUrl(!empty($wgCreateBlogPagePreload) ? "preload=$wgCreateBlogPagePreload" : ""),
							'text' => wfMsg('blog-create-post-label'),
						),
						'image' => MenuButtonController::BLOG_ICON,
						'name' => 'createblogpost',
					);
				}
			}
		}

		if (in_array($namespace, array(NS_USER, NS_USER_TALK))) {
			//profile & talk page
			if ($canRename) {
				/**
				 * @var $specialMovePage Title
				 */
				$specialMovePage = SpecialPage::getTitleFor('MovePage');
				$renameUrl = $specialMovePage->getLocalUrl() . '/' . $this->title->__toString();
				$actionButtonArray['dropdown']['rename'] = array(
					'href' => $renameUrl,
					'text' => wfMsg('user-action-menu-rename'),
				);
			}

			if ($canProtect) {
				$protectStatus = $this->title->isProtected() ? 'unprotect' : 'protect';

				$actionButtonArray['dropdown']['protect'] = array(
					'href' => $this->title->getLocalUrl(array('action' => $protectStatus)),
					'text' => wfMsg('user-action-menu-' . $protectStatus),
				);
			}

			if ($canDelete) {
				$actionButtonArray['dropdown']['delete'] = array(
					'href' => $this->title->getLocalUrl(array('action' => 'delete')),
					'text' => wfMsg('user-action-menu-delete'),
				);
			}

			$actionButtonArray['dropdown']['history'] = array(
				'href' => $this->title->getLocalUrl(array('action' => 'history')),
				'text' => wfMsg('user-action-menu-history'),
			);
		}

		wfRunHooks('UserProfilePageAfterGetActionButtonData', array(&$actionButtonArray, $namespace, $canRename, $canProtect, $canDelete, $isUserPageOwner));

		$actionButton = wfRenderModule('MenuButton', 'Index', $actionButtonArray);
		$this->setVal('actionButton', $actionButton);

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Gets lightbox data
	 *
	 * @requestParam string $userId user's unique id
	 * @requestParam string $tab current tab
	 *
	 * @author ADi
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function getLightboxData() {
		wfProfileIn(__METHOD__);

		$selectedTab = $this->getVal('tab');
		$userId = $this->getVal('userId');
		$sessionUser = $this->wg->User;
		$canEditProfile = $sessionUser->isAllowed('editprofilev3');

		//checking if user is blocked locally
		$isBlocked = !$sessionUser->isAllowed('edit');
		//checking if user is blocked globally
		$isBlocked = empty($isBlocked) ? $sessionUser->getBlockId() : $isBlocked;

		if (($sessionUser->isAnon() && !$canEditProfile) || $isBlocked) {
			throw new WikiaException(wfMsg('userprofilepage-invalid-user'));
		} else {
			$this->profilePage = new UserProfilePage($sessionUser);

			$this->setVal('body', (string)$this->sendSelfRequest('renderLightbox', array('tab' => $selectedTab, 'userId' => $userId)));

			if (!empty($this->wg->AvatarsMaintenance)) {
				$this->setVal('avatarsDisabled', true);
				$this->setVal('avatarsDisabledMsg', wfMessage('user-identity-avatars-maintenance')->text());
			}

			//we'll implement interview section later
			//$this->setVal( 'interviewQuestions', $this->profilePage->getInterviewQuestions( $wikiId, false, true ) );
		}

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Renders lightbox
	 *
	 * @requestParam string $userId user's unique id
	 * @requestParam string $tab current tab
	 *
	 * @desc Mainly passes two variables to the template: tabs, selectedTab but also if it's about tab or avatar uses private method to pass more data
	 */
	public function renderLightbox() {
		wfProfileIn(__METHOD__);

		$selectedTab = $this->getVal('tab');
		$userId = $this->getVal('userId');
		$sessionUser = $this->wg->User;

		$tabs = array(
			array('id' => 'avatar', 'name' => wfMsg('user-identity-box-avatar')),
			array('id' => 'about', 'name' => wfMsg('user-identity-box-about-me')),
			//array( 'id' => 'interview', 'name' => 'User Interview' ), //not yet --nAndy, 2011-06-15
		);

		$this->renderAvatarLightbox($userId);
		$this->renderAboutLightbox($userId);

		$this->setVal('tabs', $tabs);
		$this->setVal('selectedTab', $selectedTab);
		$this->setVal('isUserPageOwner', (($userId == $sessionUser->getId()) ? true : false));

		$this->setVal('wgBlankImgUrl', $this->wg->BlankImgUrl);

		$this->setVal('facebookPrefsLink', Skin::makeSpecialUrl('Preferences'));

		wfProfileOut(__METHOD__);
	}

	public function saveInterviewAnswers() {
		wfProfileIn(__METHOD__);

		$user = User::newFromId($this->getVal('userId'));
		$wikiId = $this->wg->CityId;

		$answers = json_decode($this->getVal('answers'));

		$status = 'error';
		$errorMsg = wfMsg('userprofilepage-interview-save-internal-error');

		if (!$user->isAnon() && is_array($answers)) {
			$this->profilePage = new UserProfilePage($user);

			if (!$this->profilePage->saveInterviewAnswers($wikiId, $answers)) {
				$status = 'error';
				$errorMsg = wfMsg('userprofilepage-interview-save-internal-error');
			}
			else {
				$status = 'ok';
			}
		}

		$this->setVal('status', $status);
		if ($status == 'error') {
			$this->setVal('errorMsg', $errorMsg);
		}

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Recives data from AJAX call, validates and saves new user data
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function saveUserData() {
		wfProfileIn(__METHOD__);

		$user = User::newFromId($this->getVal('userId'));
		$isAllowed = ($this->app->wg->User->isAllowed('editprofilev3') || intval($user->getId()) === intval($this->app->wg->User->getId()));

		$userData = json_decode($this->getVal('data'));

		$status = 'error';
		$errorMsg = wfMsg('user-identity-box-saving-internal-error');

		if ($isAllowed && is_object($userData)) {
			/**
			 * @var $userIdentityBox UserIdentityBox
			 */
			$userIdentityBox = new UserIdentityBox( $user );

			if (!empty($userData->website) && 0 !== strpos($userData->website, 'http')) {
				$userData->website = 'http://' . $userData->website;
			}

			if (!$userIdentityBox->saveUserData($userData)) {
				$status = 'error';
				$errorMsg = wfMsg('userprofilepage-interview-save-internal-error');
			} else {
				$status = 'ok';
			}
		}

		if ($isAllowed && is_null($userData)) {
			$errorMsg = wfMsg('user-identity-box-saving-error');
		}

		$this->setVal('status', $status);
		if ($status === 'error') {
			$this->setVal('errorMsg', $errorMsg);
			wfProfileOut(__METHOD__);
			return true;
		}

		if (!empty($userData->avatarData)) {
			$status = $this->saveUsersAvatar($user->getID(), $userData->avatarData);
			if ($status !== true) {
				$this->setVal('errorMsg', $errorMsg);
				wfProfileOut(__METHOD__);
				return true;
			}
		}

		wfProfileOut(__METHOD__);
		return null;
	}

	/**
	 * @brief Validates and saves new user's avatar
	 *
	 * @param integer $userId id of user which avatar is going to be saved; taken from request if not given
	 * @param object $data data object with source of file and url/name of avatar's file; taken from request if not given
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function saveUsersAvatar($userId = null, $data = null) {
		wfProfileIn(__METHOD__);

		if (is_null($userId)) {
			$user = User::newFromId($this->getVal('userId'));
		} else {
			$user = User::newFromId($userId);
		}

		$isAllowed = ($this->app->wg->User->isAllowed('editprofilev3') || intval($user->getId()) === intval($this->app->wg->User->getId()));

		if (is_null($data)) {
			$data = json_decode($this->getVal('data'));
		}

		$errorMsg = wfMsg('userprofilepage-interview-save-internal-error');
		$result = array('success' => true, 'error' => $errorMsg);

		if ($isAllowed && isset($data->source) && isset($data->file)) {
			switch ($data->source) {
				case 'sample':
					$user->setOption('avatar', $data->file);
					break;
				case 'facebook':
				case 'uploaded':
					$avatar = $this->saveAvatarFromUrl($user, $data->file, $errorMsg);
					$user->setOption('avatar', $avatar);
					break;
				default:
					array('success' => false, 'error' => $errorMsg);
					wfMsg('userprofilepage-interview-save-internal-error');
					break;
			}

			// TODO: $user->getTouched() get be used to invalidate avatar URLs instead
			$user->setOption('avatar_rev', date('U'));
			$user->saveSettings();
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @brief Gets avatar from url, saves it on server and resize it if needed then returns path
	 *
	 * @param User $user user object
	 * @param string $url url to user's facebook avatar
	 * @param string $errorMsg reference to a string variable where errors messages are returned
	 *
	 * @return string | boolean
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function saveAvatarFromUrl(User $user, $url, &$errorMsg) {
		wfProfileIn(__METHOD__);

		$userId = $user->getId();

		$errorNo = $this->uploadByUrl(
			$url,
			array(
				'userId' => $userId,
				'username' => $user->getName(),
				'user' => $user,
				'localPath' => '',
			),
			$errorMsg
		);

		$localPath = $this->getLocalPath($user);

		if ($errorNo != UPLOAD_ERR_OK) {
			$res = false;
		} else {
			$res = $localPath;
		}
		wfProfileOut(__METHOD__);
		return $res;
	}

	/**
	 * @brief Submits avatar form and genarate url for preview
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function onSubmitUsersAvatar() {
		wfProfileIn(__METHOD__);

		$this->response->setContentType('text/html; charset=utf-8');

		$user = User::newFromId($this->getVal('userId'));

		$errorMsg = wfMsg('userprofilepage-interview-save-internal-error');
		$result = array('success' => false, 'error' => $errorMsg);

		if (!$user->isAnon() && $this->request->wasPosted()) {
			$avatarUploadFiled = 'UPPLightboxAvatar';
			$uploadError = $this->app->wg->Request->getUploadError($avatarUploadFiled);

			if ($uploadError != 0) {
				$thumbnail = $uploadError;
			} else {
				$fileName = $this->app->wg->Request->getFileTempName($avatarUploadFiled);

				$fileuploader = new WikiaTempFilesUpload();

				$thumbnail = $this->storeInTempImage($fileName, $fileuploader);
			}

			if( false === $thumbnail || is_int( $thumbnail ) ) {
				$result = array('success' => false, 'error' => $this->validateUpload($thumbnail));
				$this->setVal('result', $result);
				wfProfileOut(__METHOD__);
				return;
			}

			$this->setVal('result', $result);

			$result = array('success' => true, 'avatar' => $thumbnail->url . '?cb=' . date('U'));
			$this->setVal('result', $result);

			wfProfileOut(__METHOD__);
			return;
		}

		$result = array('success' => false, 'error' => $errorMsg);
		$this->setVal('result', $result);
		wfProfileOut(__METHOD__);
		return;
	}

	/**
	 * @param $fileName String
	 * @param $fileuploader WikiaTempFilesUpload
	 * @return bool|int|MediaTransformOutput
	 */
	protected function storeInTempImage($fileName, $fileuploader) {
		wfProfileIn(__METHOD__);

		if (filesize($fileName) > self::AVATAR_MAX_SIZE) {
			wfProfileOut(__METHOD__);
			return UPLOAD_ERR_FORM_SIZE;
		}

		$tempName = $fileuploader->tempFileName($this->wg->User);
		$title = Title::makeTitle(NS_FILE, $tempName);
		$localRepo = RepoGroup::singleton()->getLocalRepo();

		/**
		 * @var $ioh ImageOperationsHelper
		 */
		$ioh = new ImageOperationsHelper();

		$out = $ioh->postProcessFile($fileName);
		if ($out !== true) {
			wfProfileOut(__METHOD__);
			return $out;
		}

		$file = new FakeLocalFile($title, $localRepo);
		$status = $file->upload( $fileName, '', '' );

		if( $status->ok ) {
			$width = min( self::AVATAR_DEFAULT_SIZE, $file->width );
			$height = min( self::AVATAR_DEFAULT_SIZE, $file->height );

			$thumbnail = $file->transform( [
				'height' => $height,
				'width' => $width,
			] );
		} else {
			$errors = $status->getErrorsArray();
			$errMsg = 'Unable to upload temp file fo avatar. Error(s): ';
			foreach( $errors as $error ) {
				$errMsg .= $error[0] . ', ';
			}
			$errMsg = rtrim( $errMsg, ', ' );

			wfDebugLog( __METHOD__, $errMsg );
			$thumbnail = false;
		}

		wfProfileOut(__METHOD__);
		return $thumbnail;
	}

	/**
	 * @brief Validates file upload (whenever it's regular upload or by-url upload) and sets status and errorMsg
	 *
	 * @param integer $errorNo variable being checked
	 * @param string $status status depends on value of $errorNo can be returned as 'error' or 'success'
	 * @param string $errorMsg error message for humans
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function validateUpload($errorNo) {
		switch ($errorNo) {
			case UPLOAD_ERR_NO_FILE:
				return wfMsg('user-identity-box-avatar-error-nofile');
				break;

			case UPLOAD_ERR_CANT_WRITE:
				return wfMsg('user-identity-box-avatar-error-cantwrite');
				break;

			case UPLOAD_ERR_FORM_SIZE:
				return wfmsgExt('user-identity-box-avatar-error-size', array('parsemag'), (int)(self::AVATAR_MAX_SIZE / 1024));
				break;
			case UPLOAD_ERR_EXTENSION;
				return wfMsg('userprofilepage-avatar-error-type', $this->wg->Lang->listToText(ImageOperationsHelper::getAllowedMime()));
				break;
			case ImageOperationsHelper::UPLOAD_ERR_RESOLUTION:
				return wfMsg('userprofilepage-avatar-error-resolution');
				break;
			default:
				return wfMsg('user-identity-box-avatar-error');
		}
	}

	/**
	 * @brief get Local Path to avatar
	 */
	private function getLocalPath($user) {
		/**
		 * @var $oAvatarObj Masthead
		 */
		$oAvatarObj = Masthead::newFromUser($user);
		return $oAvatarObj->getLocalPath();
	}

	/**
	 * @brief Saves the file on the server
	 *
	 * @param WebRequest $request    WebRequest instance
	 * @param array $userData     user data array; contains: user id (key: userId), full page url (fullPageUrl), user name (username)
	 * @param String $input       name of file input in form
	 * @param String $errorMsg           optional string containing details on what went wrong if there is an UPLOAD_ERR_EXTENSION
	 *
	 * @return Integer an error code of operation
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function uploadFile($request, $userData, $input, &$errorMsg = '') {
		wfProfileIn(__METHOD__);

		$errorNo = $this->wg->Request->getUploadError($input);

		if ($errorNo != UPLOAD_ERR_OK) {
			wfProfileOut(__METHOD__);
			return $errorNo;
		}

		$errorMsg = "";

		if (class_exists('Masthead')) {
			/**
			 * @var $oAvatarObj Masthead
			 */
			$oAvatarObj = Masthead::newFromUser($userData['user']);
			$errorNo = $oAvatarObj->uploadFile($this->wg->Request, 'UPPLightboxAvatar', $errorMsg);


		} else {
			$errorNo = UPLOAD_ERR_EXTENSION;
		}

		wfProfileOut(__METHOD__);
		return $errorNo;
	}

	/**
	 * @desc While this is technically downloading the URL, the function's purpose is to be similar
	 * to uploadFile, but instead of having the file come from the user's computer, it comes
	 * from the supplied URL.
	 *
	 * @param String $url        the full URL of an image to download and apply as the user's Avatar i.e. user's facebook avatar
	 * @param array $userData    user data array; contains: user id (key: userId), full page url (fullPageUrl), user name (username)
	 * @param String $errorMsg          optional string containing details on what went wrong if there is an UPLOAD_ERR_EXTENSION
	 *
	 * @return Integer error code of operation
	 */
	public function uploadByUrl($url, $userData, &$errorMsg = '') {
		wfProfileIn(__METHOD__);

		//start by presuming there is no error
		//$errorNo = UPLOAD_ERR_OK;
		$user = $userData['user'];
		if (class_exists('Masthead')) {
			/**
			 * @var $oAvatarObj Masthead
			 */
			$oAvatarObj = Masthead::newFromUser($user);
			$localPath = $this->getLocalPath($user);
			$errorNo = $oAvatarObj->uploadByUrl($url);
			/**
			 * @var $userIdentityBox UserIdentityBox
			 */
			$userIdentityBox = new UserIdentityBox( $user );
			$userData = $userIdentityBox->getFullData();
			$userData['avatar'] = $localPath;
			$userIdentityBox->saveUserData($userData);
		} else {
			$errorNo = UPLOAD_ERR_EXTENSION;
		}

		wfProfileOut(__METHOD__);
		return $errorNo;
	}

	/**
	 * @brief Passes more data to the template to render avatar modal box
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function renderAvatarLightbox($userId) {
		wfProfileIn(__METHOD__);

		$user = User::newFromId($userId);

		$this->setVal('defaultAvatars', $this->getDefaultAvatars());
		$this->setVal('isUploadsPossible', $this->wg->EnableUploads && $this->wg->User->isAllowed('upload') && empty($this->wg->AvatarsMaintenance));

		$this->setVal('avatarName', $user->getOption('avatar'));
		$this->setVal('userId', $userId);
		$this->setVal('avatarMaxSize', self::AVATAR_MAX_SIZE);
		$this->setVal('avatar', AvatarService::renderAvatar($user->getName(), self::AVATAR_DEFAULT_SIZE) );
		$this->setVal('fbAvatarConnectButton', '<fb:login-button perms="user_about_me" onlogin="UserProfilePage.fbConnectAvatar();">' . wfMsg('user-identity-box-connect-to-fb') . '</fb:login-button>');

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Gets an array of sample avatars
	 *
	 * @desc Method based on Masthead::getDefaultAvatars()
	 *
	 * @param string $thumb a thumb
	 *
	 * @return array multidimensional array with default avatars defined on messaging.wikia.com
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function getDefaultAvatars($thumb = '') {
		wfProfileIn(__METHOD__);

		//parse message only once per request
		if (empty($thumb) && is_array($this->defaultAvatars) && count($this->defaultAvatars) > 0) {
			wfProfileOut(__METHOD__);
			return $this->defaultAvatars;
		}

		$this->defaultAvatars = array();
		$images = getMessageForContentAsArray('blog-avatar-defaults');

		if (is_array($images)) {
			foreach ($images as $image) {
				$hash = FileRepo::getHashPathForLevel($image, 2);
				$this->defaultAvatars[] = array('name' => $image, 'url' => $this->defaultAvatarPath . $thumb . $hash . $image);
			}
		}

		wfProfileOut(__METHOD__);
		return $this->defaultAvatars;
	}

	/**
	 * @brief Passes more data to the template to render about modal box
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function renderAboutLightbox($userId) {
		wfProfileIn(__METHOD__);

		$user = User::newFromId($userId);

		/**
		 * @var $userIdentityBox UserIdentityBox
		 */
		$userIdentityBox = new UserIdentityBox( $user );

		$userData = $userIdentityBox->getFullData();

		$this->setVal('user', $userData);
		$this->setVal('fbConnectButton', '<fb:login-button perms="user_about_me,user_birthday,user_location,user_work_history,user_website" onlogin="UserProfilePage.fbConnect();">' . wfMsg('user-identity-box-connect-to-fb') . '</fb:login-button>');

		$this->setVal('charLimits', array(
			'name' => UserIdentityBox::USER_NAME_CHAR_LIMIT,
			'location' => UserIdentityBox::USER_LOCATION_CHAR_LIMIT,
			'occupation' => UserIdentityBox::USER_OCCUPATION_CHAR_LIMIT,
			'gender' => UserIdentityBox::USER_GENDER_CHAR_LIMIT,
		));

		if (!empty($userData['birthday']['month'])) {
			$this->setVal('days', cal_days_in_month(CAL_GREGORIAN, $userData['birthday']['month'], 2000 /* leap year */));
		}

		wfProfileOut(__METHOD__);
	}




	/**
	 *
	 * @brief create preview for avatar from FB
	 *
	 * @author Tomasz Odrobny
	 */

	public function onFacebookConnectAvatar() {
		wfProfileIn(__METHOD__);

		$user = $this->app->wg->User;

		$result = array('success' => false, 'error' => wfMsg('userprofilepage-interview-save-internal-error'));
		$this->setVal('result', $result);

		if (!$user->isAnon()) {
			/**
			 * @var $fbConnectAPI FBConnectAPI
			 */
			$fbConnectAPI = new FBConnectAPI();
			$fbUserId = $fbConnectAPI->user();

			$userFbData = $fbConnectAPI->getUserInfo(
				$fbUserId,
				array('pic_big')
			);

			/**
			 * @var $oAvatarObj Masthead
			 */
			$oAvatarObj = Masthead::newFromUser($user);
			$tmpFile = '';
			$oAvatarObj->uploadByUrlToTempFile($userFbData['pic_big'], $tmpFile);

			$fileuploader = new WikiaTempFilesUpload();
			$thumbnail = $this->storeInTempImage($tmpFile, $fileuploader);

			$result = array('success' => true, 'avatar' => $thumbnail->url . '?cb=' . date('U'));
			$this->setVal('result', $result);
		}

		wfProfileOut(__METHOD__);
		return true;
	}

	/**
	 * @brief Gets facebook user data from database or tries to connect via FB API and get those data then returns it as a JSON data
	 *
	 * @desc Checks if user is logged-in only because we decided to put facebook connect button only for owners of a profile; staff can not see the button
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function onFacebookConnect() {
		wfProfileIn(__METHOD__);

		$user = $this->app->wg->User;
		//$result = array('success' => false);

		if (!$user->isAnon()) {
			/** @var $fb_ids FBConnectDB */
			$fb_ids = FBConnectDB::getFacebookIDs($user);
			$fbConnectAPI = new FBConnectOpenGraphAPI();

			if (count($fb_ids) > 0) {
				$fbUserId = $fb_ids[0];
			} else {
				$fbUserId = $fbConnectAPI->user();
			}

			if ($fbUserId > 0) {
				$userFbData = $fbConnectAPI->getUserInfo(
					$fbUserId,
					array('first_name, current_location, hometown_location, work_history, profile_url, sex, birthday_date, pic_big, website')
				);
				$userFbData = $this->cleanFbData($userFbData);

				$result = array('success' => true, 'fbUser' => $userFbData);
			} else {
				$result = array('success' => false, 'error' => wfMsg('user-identity-box-invalid-fb-id-error'));
			}
		} else {
			$result = array('success' => false, 'error' => wfMsg('userprofilepage-interview-save-internal-error'));
		}

		$this->setVal('result', $result);

		wfProfileOut(__METHOD__);
	}

	/**
	 * @brief Clears all data recivied from Facebook so all of them are strings
	 *
	 * @param array $fbData facebook user data recivied from FBConnectAPI::getUserInfo()
	 *
	 * @return array
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function cleanFbData($fbData) {
		unset($fbData['uid']);

		foreach ($fbData as $key => $data) {
			if (!is_string($data)) {
				switch ($key) {
					case 'work_history':
						$fbData['work_history'] = $this->extractFbFirstField($fbData['work_history'], 'position');
						break;
					case 'current_location':
						$fbData['current_location'] = $this->extractFbFirstField($fbData['current_location'], 'city');
						break;
				}
			}
		}

		if (!empty($fbData['website'])) {
			$websites = nl2br($fbData['website']);
			$websites = explode('<br />', $websites);
			$fbData['website'] = (isset($websites[0]) ? $websites[0] : '');
		}

		if (empty($fbData['current_location'])) {
			$this->extractFbFirstField($fbData['hometown_location'], 'city');
		} else {
			unset($fbData['hometown_location']);
		}

		if (!empty($fbData['first_name'])) {
			$fbData['name'] = $fbData['first_name'];
			unset($fbData['first_name']);
		}

		return $fbData;
	}

	/**
	 * @brief Searches for a string data to return
	 *
	 * @param array | string    $fbData data from facebook
	 * @param string            $field of an array which we want to find and return
	 *
	 * @return string
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	private function extractFbFirstField($fbData, $field = null) {
		if (is_null($field)) {
			return '';
		}

		if (!empty($fbData[$field]) && is_string($fbData[$field])) {
			return $fbData[$field];
		}

		if (!empty($fbData[0]) && !empty($fbData[0][$field])) {
			return $fbData[0][$field];
		}

		return '';
	}

	/**
	 * @brief Gets wikiId from request and hides from faviorites wikis
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function onHideWiki() {
		$result = array('success' => false);
		$userId = intval($this->getVal('userId'));
		$wikiId = intval($this->getVal('wikiId'));

		$user = User::newFromId($userId);
		$isAllowed = ($this->app->wg->User->isAllowed('editprofilev3') || intval($user->getId()) === intval($this->app->wg->User->getId()));

		if ($isAllowed && $wikiId > 0) {
			/**
			 * @var $userIdentityBox UserIdentityBox
			 */
			$userIdentityBox = new UserIdentityBox( $user );
			$success = $userIdentityBox->hideWiki($wikiId);

			$result = array('success' => $success, 'wikis' => $userIdentityBox->getTopWikis());
		}

		$this->setVal('result', $result);
	}

	/**
	 * @brief Gets fav wikis information and passes it as JSON
	 *
	 * @author Andrzej 'nAndy' Łukaszewski
	 */
	public function onRefreshFavWikis() {
		$userId = intval( $this->getVal( 'userId' ) );
		$user = User::newFromId( $userId );
		$userIdentityBox = new UserIdentityBox( $user );
		$result = array( 'success' => true, 'wikis' => $userIdentityBox->getTopWikis( true ) );

		$this->setVal( 'result', $result );
	}

	public function getClosingModal() {
		wfProfileIn(__METHOD__);

		$userId = $this->getVal('userId');
		$user = $this->wg->User;

		if (!$user->isAnon()) {
			$this->setVal('body', (string)$this->sendSelfRequest('renderClosingModal', array('userId' => $userId)));
		} else {
			throw new WikiaException('User not logged in');
		}

		wfProfileOut(__METHOD__);
	}

	public function renderClosingModal() {
		wfProfileIn(__METHOD__);

		//we want only the template for now...

		wfProfileOut(__METHOD__);
	}

	public function removeavatar() {
		wfProfileIn(__METHOD__);
		$this->setVal('status', false);

		// macbre: avatars operations are disabled during maintenance
		global $wgAvatarsMaintenance;
		if (!empty($wgAvatarsMaintenance)) {
			$this->setVal('error', wfMsg('user-identity-avatars-maintenance'));
			wfProfileOut(__METHOD__);
			return true;
		}

		if (!$this->app->wg->User->isAllowed('removeavatar')) {
			wfProfileOut(__METHOD__);
			return true;
		}

		if ($this->request->getVal('avUser')) {
			$avUser = User::newFromName($this->request->getVal('avUser'));
			if ($avUser->getID() !== 0) {
				$avatar = Masthead::newFromUser($avUser);
				if ($avatar->removeFile(true)) {
					wfProfileOut(__METHOD__);
					$this->setVal('status', "ok");
					return true;
				}
			}
		}

		$this->setVal('error', wfMsg('user-identity-remove-fail'));

		wfProfileOut(__METHOD__);
		return true;
	}

}
