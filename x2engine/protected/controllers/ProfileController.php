<?php

/*****************************************************************************************
 * X2Engine Open Source Edition is a customer relationship management program developed by
 * X2Engine, Inc. Copyright (C) 2011-2014 X2Engine Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY X2ENGINE, X2ENGINE DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact X2Engine, Inc. P.O. Box 66752, Scotts Valley,
 * California 95067, USA. or at email address contact@x2engine.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * X2Engine" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by X2Engine".
 *****************************************************************************************/

/**
 * User profiles controller
 *
 * @package application.controllers
 */
class ProfileController extends x2base {

    /**
     * @var string The class of the model most often handled by this controller.
     */
    public $modelClass = 'Profile';

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array(
                    'index', 'view', 'update', 'search', 'addPost', 'deletePost', 'uploadPhoto',
                    'getEvents', 'getEventsBetween', 'broadcastEvent', 'loadComments',
                    'loadLikeHistory', 'likePost', 'flagPost', 'stickyPost', 'minimizePosts',
                    'publishPost', 'createChartSetting', 'ajaxExportTheme',
                    'deleteChartSetting', 'addComment',
                    'toggleFeedControls', 'toggleFeedFilters', 'setWidgetSetting',
                    'showWidgetContents', 'getWidgetContents',
                    'setWidgetOrder', 'profiles', 'settings', 'deleteSound', 'deleteBackground',
                    'changePassword', 'setResultsPerPage', 'hideTag', 'unhideTag', 'resetWidgets',
                    'updatePost', 'loadTheme', 'createTheme', 'saveTheme', 'saveMiscLayoutSetting',
                    'createUpdateCredentials', 'manageCredentials', 'deleteCredentials',
                    'setDefaultCredentials', 'activity', 'ajaxSaveDefaultEmailTemplate',
                    'deleteActivityReport','createActivityReport'),
                'users' => array('@'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function behaviors() {
        return array_merge(parent::behaviors(), array(
            'ImportExportBehavior' => array('class' => 'ImportExportBehavior'),
                )
        );
    }

    public function filters() {
        return array_merge(parent::filters(), array(
            'accessControl',
            'setPortlets',
        ));
    }

    public function actionHideTag($tag) {
        $tag = "#" . $tag;
        $profile = Yii::app()->params->profile;
        if (isset($profile)) {
            $hiddenTags = json_decode($profile->hiddenTags, true);
            if (!is_array($hiddenTags))
                $hiddenTags = array();
            if (!in_array($tag, $hiddenTags)) {
                array_push($hiddenTags, $tag);
                $profile->hiddenTags = json_encode($hiddenTags);
                $profile->save();
            }
        }
    }

    public function actionUnhideTag($tag) {
        $tag = "#" . $tag;
        $profile = Yii::app()->params->profile;
        if (isset($profile)) {
            $hiddenTags = json_decode($profile->hiddenTags, true);
            if (!is_array($hiddenTags))
                $hiddenTags = array();
            if (in_array($tag, $hiddenTags)) {
                unset($hiddenTags[array_search($tag, $hiddenTags)]);
                $profile->hiddenTags = json_encode($hiddenTags);
                $profile->save();
            }
        }
    }

    public function actionUpdatePost($id, $profileId) {
        $post = Events::model()->findByPk($id);
        if (isset($_POST['Events'])) {
            $post->text = $_POST['Events']['text'];
            $post->save();
            $this->redirect(array('view', 'id' => $profileId));
            //$this->redirect(array('/profile/profile'));
        }
        $commentDataProvider = new CActiveDataProvider('Events', array(
            'criteria' => array(
                'order' => 'timestamp ASC',
                'condition' => "type='comment' AND associationType='Events' AND associationId=$id",
        )));
        $this->render('updatePost', array(
            'id' => $id,
            'model' => $post,
            'commentDataProvider' => $commentDataProvider,
            'profileId' => $profileId
        ));
    }

    /**
     * Deletes a post in the public feed for the current user.
     * @param integer $id
     */
    public function actionDeletePost($id, $profileId) {
        $post = Events::model()->findByPk($id);
        if (isset($post)) {
            if ($post->type == 'comment') {
                $postParent = X2Model::model('Events')->findByPk($post->associationId);
                $user = Profile::model()->findByPk($postParent->associationId);
            } else {
                $user = Profile::model()->findByPk($post->associationId);
            }
            if (isset($postParent) && $post->user != Yii::app()->user->getName()) {
                if ($postParent->associationId == Yii::app()->user->getId())
                    $post->delete();
            }
            if ($post->user == Yii::app()->user->getName() || $post->associationId == Yii::app()->user->getId() || Yii::app()->params->isAdmin) {
                if ($post->delete()) {
                    
                }
            }
        }
        $this->redirect(array('view', 'id' => $profileId));
    }

    /**
     * Saves settings as a property of the miscLayoutSettings JSON field of the profile model. This 
     * should be used to make miscellaneous layout settings persistent.
     * POST Parameters:
     *  settingName - string - must be an existing property name of the JSON field
     *  settingVal - mixed - the value to which the JSON field property will get set
     */
    public function actionSaveMiscLayoutSetting() {
        if (!isset($_POST['settingName']) || !isset($_POST['settingVal'])) {
            echo 'failure';
            return;
        }
        Profile::setMiscLayoutSetting($_POST['settingName'], $_POST['settingVal']);
    }

    /**
     */
    public function actionLoadTheme($themeId) {
        $theme = Yii::app()->db->createCommand()
                ->select('description')
                ->from('x2_media')
                ->where('id=:id and associationType="theme"', array(':id' => $themeId))
                ->queryScalar();
        echo $theme;
    }

    /**
      Overwrite an existing theme that the user uploaded.
     */
    public function actionSaveTheme($themeAttributes) {
        $themeAttributesArr = CJSON::decode($themeAttributes);
        if (!in_array('themeName', array_keys($themeAttributesArr)))
            return;

        $themeModel = X2Model::model('Media')->findByAttributes(array(
            'uploadedBy' => Yii::app()->user->name,
            'fileName' => $themeAttributesArr['themeName'],
            'associationType' => 'theme'
        ));
        if ($themeModel !== null) {
            $themeModel->fileName = $themeAttributesArr['themeName'];
            $themeModel->description = $themeAttributes;
            if ($themeModel->save()) {
                echo Yii::t('profile', 'Theme saved successfully.');
            }
        }
    }

    private static function getThemeErrorMsg() {
        $errorArr = array(
            'success' => false,
            'errorListHeader' => Yii::t('profile', 'Please fix the following errors:'),
            'errorMsg' => Yii::t('profile', 'Theme name already exists or is invalid.'));
        return CJSON::encode($errorArr);
    }

    private static function getThemeSuccessMsg($data = array()) {
        $successArr = array_merge(array(
            'success' => true,
            'msg' => Yii::t('profile', 'Theme created successfully.')), $data);
        return CJSON::encode($successArr);
    }

    /**
      Create a new theme record in the Media table, prevent duplicate filenames.
      If theme cannot be saved, error message object is returned.
     */
    public function actionCreateTheme($themeAttributes) {
        $themeAttributesArr = CJSON::decode($themeAttributes);
        if (!in_array('themeName', array_keys($themeAttributesArr)) ||
                !in_array('themeName', array_keys($themeAttributesArr))) {
            echo self::getThemeErrorMsg();
        }
        $theme = new Media;
        $theme->setScenario('themeCreate');
        $theme->fileName = $themeAttributesArr['themeName'];
        $theme->private = $themeAttributesArr['private'];
        $theme->associationType = "theme";
        $theme->uploadedBy = Yii::app()->user->name;
        $theme->description = $themeAttributes;
        if (!$theme->save()) {
            echo self::getThemeErrorMsg();
        } else {
            echo self::getThemeSuccessMsg(array('id' => $theme->id));
        }
    }

    

    /**
     * Display/set user profile settings.
     */
    public function actionSettings() {
        $model = $this->loadModel(Yii::app()->user->getId());

        

        if (isset($_POST['Profile']) || isset($_POST['preferences'])) {
            if (isset($_POST['Profile'])) {
                $model->attributes = $_POST['Profile'];

                if ($model->save()) {
                    //$this->redirect(array('view','id'=>$model->id));
                }
            }
            if (isset($_POST['preferences'])) {
                $model->theme = $_POST['preferences'];
                $model->save();
            }
            $this->refresh();
        }

        $modules = Modules::model()->findAllByAttributes(array('visible' => 1));
        $menuItems = array();
        foreach ($modules as $module) {
            if ($module->name == 'document') {
                $menuItems[$module->title] = $module->title;
            } else {
                $menuItems[$module->name] = Yii::t('app', $module->title);
            }
        }
        $menuItems = array('' => Yii::t('app', 'Activity Feed')) + $menuItems;

        $languageDirs = scandir('./protected/messages'); // scan for installed language folders

        $languages = array('en' => 'English');

        foreach ($languageDirs as $code) {  // look for langauges name
            $name = $this->getLanguageName($code, $languageDirs);  // in each item in $languageDirs
            if ($name !== false)
                $languages[$code] = $name; // add to $languages if name is found
        }
        $times = $this->getTimeZones();

        $myThemeProvider = new CActiveDataProvider('Media', array(
            'criteria' => array(
                'condition' => "((private = 1 AND uploadedBy = '" . Yii::app()->user->name . "') OR private = 0) AND associationType = 'theme'",
                'order' => 'createDate DESC'
            ),
        ));
        $myBackgroundProvider = new CActiveDataProvider('Media', array(
            'criteria' => array(
                'condition' => "(associationType = 'bg-private' AND associationId = '" . Yii::app()->user->getId() . "') OR associationType = 'bg'",
                'order' => 'createDate DESC'
            ),
        ));
        $myLoginSoundProvider = new CActiveDataProvider('Media', array(
            'criteria' => array(
                'condition' => "(associationType='loginSound' AND (private=0 OR private IS NULL OR uploadedBy='" . Yii::app()->user->getName() . "'))",
                'order' => 'createDate DESC'
            ),
        ));
        $myNotificationSoundProvider = new CActiveDataProvider('Media', array(
            'criteria' => array(
                'condition' => "(associationType='notificationSound' AND (private=0 OR private IS NULL OR uploadedBy='" . Yii::app()->user->getName() . "'))",
                'order' => 'createDate DESC'
            ),
        ));
        $hiddenTags = json_decode(Yii::app()->params->profile->hiddenTags, true);
        if (empty($hiddenTags))
            $hiddenTags = array();

        if (sizeof($hiddenTags)) {
            $tagParams = AuxLib::bindArray($hiddenTags);
            $allTags = Yii::app()->db->createCommand()
                    ->select('COUNT(*) AS count, tag')
                    ->from('x2_tags')
                    ->group('tag')
                    ->where('tag IS NOT NULL AND tag IN (' .
                            implode(',', array_keys($tagParams)) . ')', $tagParams)
                    ->order('tag ASC')
                    ->limit(20)
                    ->queryAll();
        } else {
            $allTags = array();
        }

        $admin = Yii::app()->settings;

        $this->render('settings', array(
            'model' => $model,
            'languages' => $languages,
            'times' => $times,
            'myThemes' => $myThemeProvider,
            'myBackgrounds' => $myBackgroundProvider,
            'myLoginSounds' => $myLoginSoundProvider,
            'myNotificationSounds' => $myNotificationSoundProvider,
            'menuItems' => $menuItems,
            'allTags' => $allTags,
            
        ));
    }

    public function actionManageCredentials() {
        $this->pageTitle = Yii::t('app', 'Manage Credentials');
        Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl() . '/js/manageCredentials.js');
        $this->render('manageCredentials', array('profile' => Yii::app()->params->profile));
    }

    /**
     * Basic CRUD for application credentials
     * @param type $id
     * @param type $class
     * @throws CHttpException
     */
    public function actionCreateUpdateCredentials($id = null, $class = null) {
        $this->pageTitle = Yii::t('app', 'Edit Credentials');
        $profile = Yii::app()->params->profile;
        // Create or retrieve model:
        if (empty($id)) {
            if (empty($class))
                throw new CHttpException(400, 'Class must be specified when creating new credentials.');
            $model = new Credentials();
            $model->modelClass = $class;
        } else {
            $model = Credentials::model()->findByPk($id);
            if (empty($model))
                throw new CHttpException(404);
        }
        $model->scenario = $model->isNewRecord ? 'create' : 'update';

        // Apply changes if any:
        $message = null;
        if (isset($_POST['Credentials'])) {
            $model->attributes = $_POST['Credentials'];
            // Check to see if user has permission:
            if (!Yii::app()->user->checkAccess('CredentialsCreateUpdate', array('model' => $model)))
                $this->denied();
            // Save the model:
            if ($model->validate()) {
                // Set timestamps
                $time = time();
                if ($model->isNewRecord)
                    $model->createDate = $time;
                $model->lastUpdated = $time;
                if ($model->save()) {
                    $message = Yii::t('app', 'Saved') . ' ' . Formatter::formatLongDateTime($time);
                    $this->redirect(array('manageCredentials'));
                }
            }
        }
        $this->render('createUpdateCredentials', array('model' => $model, 'profile' => $profile, 'message' => $message));
    }

    /**
     * Make a credentials record default for the active user.
     * @param type $id
     * @throws CHttpException
     */
    public function actionSetDefaultCredentials($id) {
        $model = Credentials::model()->findByPk($id);

        if (!isset($_POST['userId']))
            throw new Exception(400, 'Invalid request');
        $userId = $_POST['userId'];
        if (empty($model))
            throw new CHttpException(404);
        if (!Yii::app()->user->checkAccess('CredentialsSetDefault', array('model' => $model, 'userId' => $userId)))
            $this->denied();
        if (!is_array($_POST['default'])) { // It's a yes or no
            if ($_POST['default']) {
                $defaults = $model->defaultSubstitutesInv[$model->modelClass];
            }
        } else { // It's a selector
            $defaults = $_POST['default'];
        }

        if (isset($defaults)) {
            foreach ($defaults as $serviceType) {
                $model->makeDefault($userId, $serviceType);
            }
        }
        $this->redirect(array('manageCredentials'));
    }

    public function actionDeleteCredentials($id) {
        $cred = Credentials::model()->findByPk($id);
        if (empty($cred))
            throw new CHttpException(404);
        if (!Yii::app()->user->checkAccess('CredentialsDelete', array('model' => $cred)))
            $this->denied();
        $cred->delete();
        $this->redirect(array('/profile/manageCredentials'));
    }

    /**
     * Updates a particular model.
     *
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        if ($id == Yii::app()->user->getId() || Yii::app()->params->isAdmin) {
            $model = $this->loadModel($id);
            $users = User::getNames();

            if (isset($_POST['Profile'])) {

                foreach ($_POST['Profile'] as $name => $value) {
                    if ($value == $model->getAttributeLabel($name)) {
                        $_POST['Profile'][$name] = '';
                    }
                    $model->$name = $value;
                }
                if ($model->save()) {
                    $this->redirect(array('view', 'id' => $model->id));
                }
            }

            $this->render('update', array(
                'model' => $model,
                'users' => $users,
            ));
        } else {
            $this->redirect(array('/profile/view', 'id' => $id));
        }
    }

    /**
     * Changes the password for the user given by its record ID number.
     * @param integer $id ID of the user to be updated.
     */
    public function actionChangePassword($id) {
        if ($id == Yii::app()->user->getId()) {
            $user = UserChild::model()->findByPk($id);
            if (isset($_POST['oldPassword']) && isset($_POST['newPassword']) && isset($_POST['newPassword2'])) {

                $oldPass = $_POST['oldPassword'];
                $newPass = $_POST['newPassword'];
                $newPass2 = $_POST['newPassword2'];
                if ((crypt($oldPass, '$5$rounds=32678$' . $user->password) == '$5$rounds=32678$' . $user->password) || md5($oldPass) == $user->password) {
                    if ($newPass == $newPass2) {
                        $user->password = md5($newPass);
                        $user->save();

                        $this->redirect($this->createUrl('/profile/view', array('id' => $id)));
                    }
                } else {
                    Yii::app()->clientScript->registerScript('alertPassWrong', "alert('Old password is incorrect.');");
                }
            }

            $this->render('changePassword', array(
                'model' => $user,
            ));
        }
    }

    /**
     * Upload a profile photo.
     * @param integer $id ID of the user in question.
     */
    public function actionUploadPhoto($id) {
        if ($id == Yii::app()->user->getId()) {
            $prof = Profile::model()->findByPk($id);
            if(isset($_FILES['photo'])){
                if($_FILES["photo"]["error"]) {
                    Yii::app()->user->setFlash('error', Yii::t('app', "There was an error uploading the file."));
                } else if($_FILES["photo"]["size"] < 2000000){
                    if($prof->avatar != '' && isset($prof->avatar) && file_exists($prof->avatar)){
                        unlink($prof->avatar);
                    }
                    $temp = CUploadedFile::getInstanceByName('photo');
                    $name = $this->generatePictureName();
                    $ext = $temp->getExtensionName();
                    $temp->saveAs('uploads/' . $name . '.' . $ext);

                    $prof->avatar = 'uploads/' . $name . '.' . $ext;
                    if ($prof->save()) {
                        
                    }
                }else{
                    Yii::app()->user->setFlash('error', Yii::t('app', "File is too large!"));
                }
            }
        }
        $this->redirect(array('view', 'id' => $id));
    }

    /**
     * Delete a background image.
     *
     * @param type $id
     */
    public function actionDeleteBackground($id) {

        $image = X2Model::model('Media')->findByPk($id);
        if ($image->associationId == Yii::app()->user->getId() && ($image->associationType == 'bg' || $image->associationType == 'bg-private')) {

            $profile = X2Model::model('Profile')->findByPk(Yii::app()->user->getId());

            if ($profile->backgroundImg == $image->fileName) { // if this BG is currently in use, clear user's background image setting
                $profile->backgroundImg = '';
                $profile->save();
            }

            if ($image->delete()) {
                unlink('uploads/' . $image->fileName); // delete file
                echo 'success';
            }
        }
    }

    public function actionDeleteSound($id, $sound) {
        $sound = X2Model::model('Media')->findByPk($id);
        $profile = Yii::app()->params->profile;
        $type = $sound->associationType;
        if ($profile->$type == $sound->fileName) { // if this sound is currently in use, clear user's sound setting
            $profile->$type = '';
            $profile->update(array($sound->associationType));
        }
        if ($sound->delete()) {
            unlink('uploads/media/' . $sound->uploadedBy . '/' . $sound->fileName); // delete file
            echo 'success';
        }
        return true;
    }

    /**
     * Generate a random filename for a picture.
     *
     * @return string
     */
    private function generatePictureName() {

        $time = time();
        $rand = chr(rand(65, 90));
        $salt = $time . $rand;
        $name = md5($salt . md5($salt) . $salt);
        return $name;
    }

    /**
     * Add a new post to the social feed.
     *
     * @param integer $id ID of the user.
     */
    public function actionAddPost($id, $redirect) {
        $post = new Events;
        // $user = $this->loadModel($id);
        if (isset($_POST['Events']) && $_POST['Events']['text'] != Yii::t('app', 'Enter text here...')) {
            $post->text = $_POST['Events']['text'];
            $post->visibility = $_POST['Events']['visibility'];
            if (isset($_POST['Events']['associationId']))
                $post->associationId = $_POST['Events']['associationId'];
            //$soc->attributes = $_POST['Social'];
            //die(var_dump($_POST['Social']));
            $post->user = Yii::app()->user->getName();
            $post->type = 'feed';
            $post->subtype = $_POST['Events']['subtype'];
            $post->lastUpdated = time();
            $post->timestamp = time();
            if (!isset($post->associationId) || $post->associationId == 0)
                $post->associationId = $id;
            if ($post->save()) {
                if ($post->associationId != Yii::app()->user->getId()) {

                    $notif = new Notification;

                    $notif->type = 'social_post';
                    $notif->createdBy = $post->user;
                    $notif->modelType = 'Profile';
                    $notif->modelId = $post->associationId;

                    $notif->user = Yii::app()->db->createCommand()
                            ->select('username')
                            ->from('x2_users')
                            ->where('id=:id', array(':id' => $post->associationId))
                            ->queryScalar();

                    // $prof = X2Model::model('Profile')->findByAttributes(array('username'=>$post->user));
                    // $notif->text = "$prof->fullName posted on your profile.";
                    // $notif->record = "profile:$prof->id";
                    // $notif->viewed = 0;
                    $notif->createDate = time();
                    // $subject=X2Model::model('Profile')->findByPk($id);
                    // $notif->user = $subject->username;
                    $notif->save();
                }
            }
        }
        if ($redirect == "view")
            $this->redirect(array('view', 'id' => $id));
        else
            $this->redirect(array('/profile/profile'));
    }

    /**
     * Redirect to current user's profile page
     */
    public function actionIndex() {
        $this->redirect(array('view', 'id' => Yii::app()->user->getId()));
    }

    /**
     * Lists users profiles.
     */
    public function actionProfiles() {
        $model = new Profile('search');
        $this->render('profiles', array('model' => $model));
    }

    /**
     * Obtain the name of the language given its 2-5 letter code.
     *
     * If a language pack was found for the language code, return its full
     * name. Otherwise, return false.
     *
     * @param string $code
     * @param array $languageDirs
     * @return mixed
     */
    private function getLanguageName($code, $languageDirs) { // lookup language name for the language code provided
        if (in_array($code, $languageDirs)) { // is the language pack here?
            $appMessageFile = "protected/messages/$code/app.php";
            if (file_exists($appMessageFile)) { // attempt to load 'app' messages in
                $appMessages = include($appMessageFile);     // the chosen language
                if (is_array($appMessages) and isset($appMessages['languageName']) && $appMessages['languageName'] != 'Template')
                    return $appMessages['languageName'];       // return language name
            }
        }
        return false; // false if languge pack wasn't there
    }

    /**
     * Return a mapping of Olson TZ code names to timezone names.
     * @return array
     */
    private function getTimeZones() {
        return array(
            'Pacific/Midway' => "(GMT-11:00) Midway Island",
            'US/Samoa' => "(GMT-11:00) Samoa",
            'US/Hawaii' => "(GMT-10:00) Hawaii",
            'US/Alaska' => "(GMT-09:00) Alaska",
            'US/Pacific' => "(GMT-08:00) Pacific Time (US & Canada)",
            'America/Tijuana' => "(GMT-08:00) Tijuana",
            'US/Arizona' => "(GMT-07:00) Arizona",
            'US/Mountain' => "(GMT-07:00) Mountain Time (US & Canada)",
            'America/Chihuahua' => "(GMT-07:00) Chihuahua",
            'America/Mazatlan' => "(GMT-07:00) Mazatlan",
            'America/Mexico_City' => "(GMT-06:00) Mexico City",
            'America/Monterrey' => "(GMT-06:00) Monterrey",
            'Canada/Saskatchewan' => "(GMT-06:00) Saskatchewan",
            'US/Central' => "(GMT-06:00) Central Time (US & Canada)",
            'US/Eastern' => "(GMT-05:00) Eastern Time (US & Canada)",
            'US/East-Indiana' => "(GMT-05:00) Indiana (East)",
            'America/Bogota' => "(GMT-05:00) Bogota",
            'America/Lima' => "(GMT-05:00) Lima",
            'America/Caracas' => "(GMT-04:30) Caracas",
            'Canada/Atlantic' => "(GMT-04:00) Atlantic Time (Canada)",
            'America/La_Paz' => "(GMT-04:00) La Paz",
            'America/Santiago' => "(GMT-04:00) Santiago",
            'Canada/Newfoundland' => "(GMT-03:30) Newfoundland",
            'America/Buenos_Aires' => "(GMT-03:00) Buenos Aires",
            'Greenland' => "(GMT-03:00) Greenland",
            'Atlantic/Stanley' => "(GMT-02:00) Stanley",
            'Atlantic/Azores' => "(GMT-01:00) Azores",
            'Atlantic/Cape_Verde' => "(GMT-01:00) Cape Verde Is.",
            'Africa/Casablanca' => "(GMT) Casablanca",
            'Europe/Dublin' => "(GMT) Dublin",
            'Europe/Lisbon' => "(GMT) Lisbon",
            'Europe/London' => "(GMT) London",
            'Africa/Monrovia' => "(GMT) Monrovia",
            'UTC' => "(UTC)",
            'Europe/Amsterdam' => "(GMT+01:00) Amsterdam",
            'Europe/Belgrade' => "(GMT+01:00) Belgrade",
            'Europe/Berlin' => "(GMT+01:00) Berlin",
            'Europe/Bratislava' => "(GMT+01:00) Bratislava",
            'Europe/Brussels' => "(GMT+01:00) Brussels",
            'Europe/Budapest' => "(GMT+01:00) Budapest",
            'Europe/Copenhagen' => "(GMT+01:00) Copenhagen",
            'Europe/Ljubljana' => "(GMT+01:00) Ljubljana",
            'Europe/Madrid' => "(GMT+01:00) Madrid",
            'Europe/Paris' => "(GMT+01:00) Paris",
            'Europe/Prague' => "(GMT+01:00) Prague",
            'Europe/Rome' => "(GMT+01:00) Rome",
            'Europe/Sarajevo' => "(GMT+01:00) Sarajevo",
            'Europe/Skopje' => "(GMT+01:00) Skopje",
            'Europe/Stockholm' => "(GMT+01:00) Stockholm",
            'Europe/Vienna' => "(GMT+01:00) Vienna",
            'Europe/Warsaw' => "(GMT+01:00) Warsaw",
            'Europe/Zagreb' => "(GMT+01:00) Zagreb",
            'Europe/Athens' => "(GMT+02:00) Athens",
            'Europe/Bucharest' => "(GMT+02:00) Bucharest",
            'Africa/Cairo' => "(GMT+02:00) Cairo",
            'Africa/Harare' => "(GMT+02:00) Harare",
            'Europe/Helsinki' => "(GMT+02:00) Helsinki",
            'Europe/Istanbul' => "(GMT+02:00) Istanbul",
            'Asia/Jerusalem' => "(GMT+02:00) Jerusalem",
            'Europe/Kiev' => "(GMT+02:00) Kyiv",
            'Europe/Minsk' => "(GMT+02:00) Minsk",
            'Europe/Riga' => "(GMT+02:00) Riga",
            'Europe/Sofia' => "(GMT+02:00) Sofia",
            'Europe/Tallinn' => "(GMT+02:00) Tallinn",
            'Europe/Vilnius' => "(GMT+02:00) Vilnius",
            'Asia/Baghdad' => "(GMT+03:00) Baghdad",
            'Asia/Kuwait' => "(GMT+03:00) Kuwait",
            'Europe/Moscow' => "(GMT+03:00) Moscow",
            'Africa/Nairobi' => "(GMT+03:00) Nairobi",
            'Asia/Riyadh' => "(GMT+03:00) Riyadh",
            'Europe/Volgograd' => "(GMT+03:00) Volgograd",
            'Asia/Tehran' => "(GMT+03:30) Tehran",
            'Asia/Baku' => "(GMT+04:00) Baku",
            'Asia/Muscat' => "(GMT+04:00) Muscat",
            'Asia/Tbilisi' => "(GMT+04:00) Tbilisi",
            'Asia/Yerevan' => "(GMT+04:00) Yerevan",
            'Asia/Kabul' => "(GMT+04:30) Kabul",
            'Asia/Yekaterinburg' => "(GMT+05:00) Ekaterinburg",
            'Asia/Karachi' => "(GMT+05:00) Karachi",
            'Asia/Tashkent' => "(GMT+05:00) Tashkent",
            'Asia/Kolkata' => "(GMT+05:30) Kolkata",
            'Asia/Kathmandu' => "(GMT+05:45) Kathmandu",
            'Asia/Almaty' => "(GMT+06:00) Almaty",
            'Asia/Dhaka' => "(GMT+06:00) Dhaka",
            'Asia/Novosibirsk' => "(GMT+06:00) Novosibirsk",
            'Asia/Bangkok' => "(GMT+07:00) Bangkok",
            'Asia/Jakarta' => "(GMT+07:00) Jakarta",
            'Asia/Krasnoyarsk' => "(GMT+07:00) Krasnoyarsk",
            'Asia/Chongqing' => "(GMT+08:00) Chongqing",
            'Asia/Hong_Kong' => "(GMT+08:00) Hong Kong",
            'Asia/Irkutsk' => "(GMT+08:00) Irkutsk",
            'Asia/Kuala_Lumpur' => "(GMT+08:00) Kuala Lumpur",
            'Australia/Perth' => "(GMT+08:00) Perth",
            'Asia/Singapore' => "(GMT+08:00) Singapore",
            'Asia/Taipei' => "(GMT+08:00) Taipei",
            'Asia/Ulaanbaatar' => "(GMT+08:00) Ulaan Bataar",
            'Asia/Urumqi' => "(GMT+08:00) Urumqi",
            'Asia/Seoul' => "(GMT+09:00) Seoul",
            'Asia/Tokyo' => "(GMT+09:00) Tokyo",
            'Asia/Yakutsk' => "(GMT+09:00) Yakutsk",
            'Australia/Adelaide' => "(GMT+09:30) Adelaide",
            'Australia/Darwin' => "(GMT+09:30) Darwin",
            'Australia/Brisbane' => "(GMT+10:00) Brisbane",
            'Australia/Canberra' => "(GMT+10:00) Canberra",
            'Pacific/Guam' => "(GMT+10:00) Guam",
            'Australia/Hobart' => "(GMT+10:00) Hobart",
            'Australia/Melbourne' => "(GMT+10:00) Melbourne",
            'Pacific/Port_Moresby' => "(GMT+10:00) Port Moresby",
            'Australia/Sydney' => "(GMT+10:00) Sydney",
            'Asia/Vladivostok' => "(GMT+10:00) Vladivostok",
            'Asia/Magadan' => "(GMT+11:00) Magadan",
            'Pacific/Auckland' => "(GMT+12:00) Auckland",
            'Pacific/Fiji' => "(GMT+12:00) Fiji",
            'Asia/Kamchatka' => "(GMT+12:00) Kamchatka",
        );
    }

    /**
     * Sets the the option for the number of results per page.
     * @param integer $results
     */
    public function actionSetResultsPerPage($results) {
        Yii::app()->params->profile->resultsPerPage = $results;
        Yii::app()->params->profile->save();
    }

    public function actionResetWidgets($id) {
        $model = $this->loadModel($id);

        $model->layout = json_encode($model->initLayout());
        $model->update();

        $this->redirect(array('view', 'id' => $id));
    }

    /*     * *********************************************************************
     * Profile Page Methods
     * ********************************************************************* */

    /**
     * Called to save profile widget sort order
     * Expected POST data:
     *  widgetOrder - an array of strings, each corresponding to a widget class name
     * Echoes:
     *  'failure' if the request action fails, 'success' otherwise
     */
    public function actionSetWidgetOrder() {
        if (!isset($_POST['widgetOrder']) ||
                !is_array($_POST['widgetOrder']) || !isset($_POST['widgetType'])) {

            echo 'Failure: invalid post params';
            return;
        }
        $profile = Yii::app()->params->profile;
        $widgetOrder = $_POST['widgetOrder'];
        $widgetType = $_POST['widgetType'];

        if (SortableWidget::setSortOrder($profile, $widgetOrder, $widgetType)) {
            echo 'success';
            return;
        }
        echo 'Failure: failed to set sort order';
    }

    /**
     * Called to retieve widget contents
     * Expected POST data:
     *  widgetClass - the name of the widget class
     * Echoes:
     *  'failure' if the request action fails, an HTML string containing the widget contents 
     *      otherwise
     */
    public function actionShowWidgetContents() {
        if (!isset($_POST['widgetClass']) ||
                !isset($_POST['widgetType'])) {

            echo 'failure';
            return;
        }
        $profile = Yii::app()->params->profile;
        $widgetClass = $_POST['widgetClass'];
        $widgetType = $_POST['widgetType'];
        if ($profile && class_exists($widgetClass)) {
            if ($widgetClass::setJSONProperty($profile, 'hidden', 0, $widgetType)) {
                $widgetClass::getWidgetContents($this, $profile, $widgetType);
                return;
            }
        }
        echo 'failure';
        return;
    }

    /**
     * A wrapper around actionShowWidgetContents () which allows widget contents to be requested
     * with a GET request. This is used for gridview widgets.
     */
    public function actionGetWidgetContents() {
        if (!isset($_GET['widgetClass']) ||
                !isset($_GET['widgetType'])) {

            echo 'failure';
            return;
        }
        $_POST['widgetClass'] = $_GET['widgetClass'];
        $_POST['widgetType'] = $_GET['widgetType'];
        $this->actionShowWidgetContents();
        return;
    }

    /**
     * Called to save settings for a particular profile widget
     * Expected POST data:
     *  widgetClass - the name of the widget class
     *  key - the widget settings JSON property 
     *  value - the value which the JSON property will be set to 
     * Echoes:
     *  'failure' if the request action fails, 'success' otherwise
     */
    public function actionSetWidgetSetting() {
        if (!isset($_POST['widgetClass']) ||
                !isset($_POST['key']) || !isset($_POST['value']) ||
                !isset($_POST['widgetType'])) {

            echo 'failure';
            return;
        }
        $profile = Yii::app()->params->profile;

        $widgetClass = $_POST['widgetClass'];
        $key = $_POST['key'];
        $value = $_POST['value'];
        $widgetType = $_POST['widgetType'];
        if (class_exists($widgetClass) &&
                method_exists($widgetClass, 'setJSONProperty')) {

            if ($widgetClass::setJSONProperty($profile, $key, $value, $widgetType)) {
                echo 'success';
                return;
            }
        }
        echo 'failure';
    }

    private function getActivityFeedViewParams($id, $publicProfile) {
        Events::deleteOldEvents();

        $isMyProfile = !$publicProfile && $id === Yii::app()->params->profile->id;
        $profile = $this->loadModel($id);

        if (!Yii::app()->request->isAjaxRequest) {
            $_SESSION['lastDate'] = 0;
            unset($_SESSION['lastEventId']);
        }

        unset($_SESSION['feed-condition']);
        unset($_SESSION['feed-condition-params']);
        if (!isset($_GET['filters'])) {
            unset($_SESSION['filters']);
        }
        if (isset(Yii::app()->params->profile->defaultFeedFilters)) {
            $_SESSION['filters'] = json_decode(Yii::app()->params->profile->defaultFeedFilters, true);
        }

        $filters = null;
        $filtersOn = false;
        if (isset($_GET['filters'])) {
            $filters = $_GET;
            if ($_GET['filters']) {
                $filtersOn = true;
            }
        }

        extract(Events::getFilteredEventsDataProvider(
                        $profile, $isMyProfile, $filters, $filtersOn));

        $data = $dataProvider->getData();
        $dataProvider = new CArrayDataProvider(Events::permissionFilter($data, Yii::app()->getSuId()));

        if (isset($data[count($data) - 1]))
            $firstId = $data[count($data) - 1]->id;
        else
            $firstId = 0;

        if ($isMyProfile) {
            $users = User::getUserIds();
        } else {
            $users = array($profile->id => $profile->fullName);
        }

        $_SESSION['firstFlag'] = true;
        $stickyDataProvider = new CActiveDataProvider('Events', array(
            'criteria' => array(
                'condition' => 'sticky=1',
                'order' => 'timestamp DESC, id DESC',
            ),
            'pagination' => array(
                'pageSize' => 20
            ),
        ));
        $_SESSION['stickyFlag'] = false;

        $usersDataProvider = User::getUsersDataProvider();
        return array(
            'model' => $profile,
            'isMyProfile' => !$publicProfile && $id === Yii::app()->params->profile->id,
            'dataProvider' => $dataProvider,
            'users' => $users,
            'lastEventId' => !empty($lastId) ? $lastId : 0,
            'firstEventId' => !empty($firstId) ? $firstId : 0,
            'lastTimestamp' => $lastTimestamp,
            'stickyDataProvider' => $stickyDataProvider,
            'usersDataProvider' => $usersDataProvider
        );
    }

    public function actionDeleteActivityReport($id, $deleteKey) {
        $event = X2Model::model('CronEvent')->findByPk($id);
        $eventData = json_decode($event->data,true);
        if($deleteKey == $eventData['deleteKey']){
            $event->delete();
            echo Yii::t('profile','You will no longer receive this activity feed report.');
        }else{
            echo Yii::t('profile','You do not have permission to delete this activity feed report.');
        }
    }

    public function actionCreateActivityReport($filters) {
        $filters = json_encode($_GET);
        if (isset($_POST['userId'])) {
            $hour = $_POST['hour'];
            $filters = $_POST['filters'];
            $userId = $_POST['userId'];
            $limit = $_POST['limit'];
            $range = $_POST['range'];
            $interval = 0;
            switch ($range) {
                case 'daily':
                    $interval = 24 * 60 * 60;
                    $hour = strtotime('+1 day ' . $hour);
                    break;
                case 'weekly':
                    $interval = 7 * 24 * 60 * 60;
                    $hour = strtotime('+1 week ' . $hour);
                    break;
                case 'monthly':
                    $interval = 30 * 24 * 60 * 60;
                    $hour = strtotime('+1 month ' . $hour);
                    break;
                default:
                    throw new CHttpException (400, Yii::t('profile', 'Bad request'));
            }
            $data = json_encode(array(
                'userId' => $userId,
                'range' => $range,
                'limit' => $limit,
                'filters' => $filters,
                'deleteKey' => sha1(microtime(true).mt_rand(10000,90000)),
            ));
            Yii::app()->db->createCommand()
                    ->insert('x2_cron_events', array(
                        'type' => 'activity_report',
                        'recurring' => 1,
                        'time' => $hour,
                        'interval' => $interval,
                        'data' => $data,
                        'createDate' => time(),
            ));
            $this->redirect('index');
        }
        $this->render('createActivityReport', array(
            'filters' => $filters,
        ));
    }

    public function actionActivity() {
        $id = Yii::app()->params->profile->id;
        $params = $this->getActivityFeedViewParams($id, false);
        $this->render('activity', $params);
    }

    /**
     * Default landing page action for the web application.
     *
     * Displays a feed of new records that have been created since the last
     * login of the current web user.
     */
    public function actionView($id, $publicProfile = false) {
        if (isset($_GET['ajax'])) { // ajax request from grid view widget
            $_POST['widgetClass'] = $_GET['ajax'];
            $_POST['widgetType'] = 'profile';
            $this->actionShowWidgetContents();
            return;
        }
        if (isset($_GET['widgetClass']) && // widget update request
                isset($_GET['widgetType'])) {

            $_POST['widgetClass'] = $_GET['widgetClass'];
            $_POST['widgetType'] = $_GET['widgetType'];
            $this->actionShowWidgetContents();
            return;
        }

        if (!Yii::app()->user->isGuest) {
            $params = $this->getActivityFeedViewParams($id, $publicProfile);

            $this->render('profile', $params);
        } else {
            $this->redirect('login');
        }
    }

    public function actionGetEvents($lastEventId, $lastTimestamp, $myProfileId, $profileId) {

        $myProfile = Profile::model()->findByPk($myProfileId);
        $profile = Profile::model()->findByPk($profileId);
        if (!isset($myProfile) || !isset($profile))
            return false;


        $result = Events::getEvents($lastEventId, $lastTimestamp, null, null, null, $myProfile, $profile);

        $events = $result['events'];
        $eventData = "";
        $newLastEventId = $lastEventId;
        $newLastTimestamp = $lastTimestamp;
        foreach ($events as $event) {
            if ($event instanceof Events) {
                if ($event->id > $newLastEventId) {
                    $newLastEventId = $event->id;
                }
                if ($event->timestamp > $newLastTimestamp) {
                    $newLastTimestamp = $event->timestamp;
                }
                $eventData.=$this->renderPartial(
                        'application.views.profile._viewEvent', array(
                    'data' => $event,
                    'noDateBreak' => true,
                    'profileId' => $profileId,
                        ), true
                );
            }
        }
        $commentCriteria = new CDbCriteria();
        $sqlParams = array(
            ':lastEventId' => $lastEventId
        );
        $condition = "type='comment' AND timestamp <=" . time() . " AND id > :lastEventId";
        $parameters = array('order' => 'id ASC');
        $parameters['condition'] = $condition;
        $parameters['params'] = $sqlParams;
        $commentCriteria->scopes = array('findAll' => array($parameters));
        $comments = X2Model::model('Events')->findAll($commentCriteria);
        $commentCounts = array();
        $lastCommentId = $lastEventId;
        foreach ($comments as $comment) {
            $parentPost = X2Model::model('Events')->findByPk($comment->associationId);
            if (isset($parentPost) && !isset($commentCounts[$parentPost->id])) {
                $commentCounts[$parentPost->id] = count($parentPost->children);
            }
            $lastCommentId = $comment->id;
        }

        echo CJSON::encode(array(
            $newLastEventId,
            $newLastEventId != $lastEventId ? $eventData : '',
            $commentCounts,
            $lastCommentId,
            $newLastTimestamp,
        ));
    }

    function actionGetEventsBetween($startTimestamp, $endTimestamp) {
        echo CJSON::encode(X2Chart::getEventsData($startTimestamp, $endTimestamp));
    }

    public function actionLoadComments($id, $profileId) {
        $commentDataProvider = new CActiveDataProvider('Events', array(
            'criteria' => array(
                'order' => 'timestamp ASC',
                'condition' => "type='comment' AND associationType='Events' AND associationId=$id",
        )));
        $this->widget('zii.widgets.CListView', array(
            'dataProvider' => $commentDataProvider,
            'viewData' => array(
                'profileId' => $profileId
            ),
            'itemView' => '../social/_view',
            'template' => '&nbsp;{items}',
            'id' => $id . '-comments',
        ));
    }

    /*
      Used for both like and unlike buttons. If the user has alread liked the
      post, the post will be unliked and visa versa. The function returns a string
      indicating whether the post was liked or unliked.
      Parameter:
      $id - the user's id
     */

    public function actionLikePost($id) {
        $userId = Yii::app()->user->id;

        $likedPost = Yii::app()->db->createCommand()
                ->select('count(userId)')
                ->from('x2_like_to_post')
                ->where('userId=:userId and postId=:postId', array(':userId' => Yii::app()->user->id, ':postId' => $id))
                ->queryScalar();

        if (!$likedPost) {
            Yii::app()->db->createCommand()
                    ->insert('x2_like_to_post', array('userId' => $userId, 'postId' => $id));
            echo 'liked post';
        } else {
            Yii::app()->db->createCommand()
                    ->delete('x2_like_to_post', 'userId=:userId and postId=:postId', array('userId' => $userId, 'postId' => $id));
            echo 'unliked post';
        }
    }

    /*
      Returns an array of links to the user profiles of users who have liked the
      post.
      Parameter:
      $id - the id of the post
     */

    public function actionLoadLikeHistory($id) {
        $likeHistory = Yii::app()->db->createCommand()
                ->select('concat (firstName, " ", lastName), usrs.id')
                ->from('x2_like_to_post as likes, x2_users as usrs')
                ->where('likes.userId=usrs.id and likes.postId=:postId', array('postId' => $id))
                ->queryAll();

        $likeHistoryLinks = array();
        foreach ($likeHistory as $like) {
            $likeHistoryLinks[] = CHtml::link($like['concat (firstName, " ", lastName)'], array('/profile/view', 'id' => $like['id']));
        }

        echo CJSON::encode($likeHistoryLinks);
    }

    /*
      Indicates that a post is important by changing it's css properties.
      Called via ajax from the make important dialog.
     */

    public function actionFlagPost() {
        if (isset($_GET['id']) && isset($_GET['attr'])) {
            $id = $_GET['id'];
            $important = $_GET['attr'];
            $event = X2Model::model('Events')->findByPk($id);
            if (isset($event)) {
                if (isset($_GET['color']) && !empty($_GET['color'])) {
                    $event->color = $_GET['color'];
                } else {
                    $event->color = null;
                }
                if (isset($_GET['fontColor']) && !empty($_GET['fontColor'])) {
                    $event->fontColor = $_GET['fontColor'];
                } else {
                    $event->fontColor = null;
                }
                if (isset($_GET['linkColor']) && !empty($_GET['linkColor'])) {
                    $event->linkColor = $_GET['linkColor'];
                } else {
                    $event->linkColor = null;
                }
                if ($important == 'important') {
                    $event->important = 1;
                } else {
                    $event->important = 0;
                }
                $event->save();
            }
        }
    }

    /*
      Broadcasts an event via email or notification to a list of users.
      Called via ajax from the broadcast event dialog.
     */

    public function actionBroadcastEvent($id, $email, $notify, $users) {
        $event = X2Model::model('Events')->findByPk($id);
        if (isset($event)) {
            $users = Profile::model()->findAllByPk(CJSON::decode($users));
            if ($email === 'true') { // broadcast via email
                // Check if user has set a default account for email delivery
                $subject = "Event Broadcast";
                $fromName = Yii::app()->params->profile->fullName;
                $body = "$fromName has broadcast an event on your X2Engine Activity Feed:<br><br>" .
                        $event->getText(array('requireAbsoluteUrl' => true));
                $recipients = array('to' => array());
                foreach ($users as $user)
                    $recipients['to'][] = array($user->fullName, $user->emailAddress);
                //$this->sendUserEmail($recipients, $subject, $body, null, Credentials::$sysUseId['systemNotificationEmail']);
                $this->sendUserEmail($recipients, $subject, $body);
            }
            if ($notify === 'true') { // broadcast via notifation
                $time = time();
                foreach ($users as $user) {
                    $notif = new Notification;
                    $notif->modelType = 'Events';
                    $notif->createdBy = Yii::app()->user->getName();
                    $notif->modelId = $event->id;
                    $notif->user = $user->username;
                    $notif->createDate = $time;
                    $notif->type = 'event_broadcast';
                    $notif->save();
                }
            }
        }
    }

    public function actionStickyPost($id) {
        if (Yii::app()->params->isAdmin) {
            $event = X2Model::model('Events')->findByPk($id);
            if (isset($event)) {
                $event->sticky = !$event->sticky;
                $event->update(array('sticky'));
            }
            echo (date("M j", time()) == date("M j", $event->timestamp) ? Yii::t('app', "Today") : Yii::app()->locale->dateFormatter->formatDateTime($event->timestamp, 'long', null));
        }
    }

    public function actionMinimizePosts() {
        if (isset($_GET['minimize'])) {
            $profile = Yii::app()->params->profile;
            if ($_GET['minimize'] == 'minimize') {
                $profile->minimizeFeed = 1;
            } else {
                $profile->minimizeFeed = 0;
            }
            echo $_GET['minimize'] == true;
            $profile->save();
        }
    }

    /**
      Create a new chart setting record in the chart settings table.
      Called via ajax from the chart setting creation dialog.
     */
    function actionCreateChartSetting() {
        if (isset($_POST['chartSettingAttributes'])) {
            $chartSettingAttributes = $_POST['chartSettingAttributes'];
            $chartSetting = new ChartSetting;
            if (is_array($chartSettingAttributes) &&
                    array_key_exists('settings', $chartSettingAttributes) &&
                    array_key_exists('chartType', $chartSettingAttributes) &&
                    array_key_exists('name', $chartSettingAttributes)) {
                $chartSetting->settings = $chartSettingAttributes['settings'];
                $chartSetting->name = $chartSettingAttributes['name'];
                $chartSetting->chartType = $chartSettingAttributes['chartType'];
                $chartSetting->userId = Yii::app()->user->id;
                if ($chartSetting->validate()) {
                    if ($chartSetting->save()) {
                        return;
                    }
                }
                echo CJSON::encode($chartSetting->getErrors());
                return;
            }
        }
        echo CJSON::encode(array('failure'));
    }

    /**
      Delete a chart setting record from the chart settings table.
      Called via ajax from the feed chart UI.
     */
    function actionDeleteChartSetting($chartSettingName) {
        $chartSetting = ChartSetting::model()->findByAttributes(array(
            'userId' => Yii::app()->user->id,
            'name' => $chartSettingName
        ));
        if (!empty($chartSetting) && $chartSetting->delete()) {
            echo 'success';
        } else {
            echo 'failure';
        }
    }

    public function actionPublishPost() {
        $post = new Events;
        // $user = $this->loadModel($id);
        if (isset($_POST['text']) && $_POST['text'] != "") {
            $post->text = $_POST['text'];
            $post->visibility = $_POST['visibility'];
            if (isset($_POST['associationId']))
                $post->associationId = $_POST['associationId'];
            //$soc->attributes = $_POST['Social'];
            //die(var_dump($_POST['Social']));
            $post->user = Yii::app()->user->getName();
            $post->type = 'feed';
            $post->subtype = $_POST['subtype'];
            $post->lastUpdated = time();
            $post->timestamp = time();
            if ($post->save()) {
                if (!empty($post->associationId) && $post->associationId != Yii::app()->user->getId()) {

                    $notif = new Notification;

                    $notif->type = 'social_post';
                    $notif->createdBy = $post->user;
                    $notif->modelType = 'Profile';
                    $notif->modelId = $post->associationId;

                    $notif->user = Yii::app()->db->createCommand()
                            ->select('username')
                            ->from('x2_users')
                            ->where('id=:id', array(':id' => $post->associationId))
                            ->queryScalar();

                    // $prof = X2Model::model('Profile')->findByAttributes(array('username'=>$post->user));
                    // $notif->text = "$prof->fullName posted on your profile.";
                    // $notif->record = "profile:$prof->id";
                    // $notif->viewed = 0;
                    $notif->createDate = time();
                    // $subject=X2Model::model('Profile')->findByPk($id);
                    // $notif->user = $subject->username;
                    $notif->save();
                }
            }
        }
    }

    public function actionAddComment() {
        if (isset($_POST['id']) && isset($_POST['text']) && $_POST['text'] != '') {
            $id = $_POST['id'];
            $comment = $_POST['text'];
            $postModel = Events::model()->findByPk($id);

            if ($postModel === null)
                throw new CHttpException(404, Yii::t('app', 'The requested post does not exist.'));

            $commentModel = new Events;
            $commentModel->text = $comment;
            $commentModel->user = Yii::app()->user->name;
            $commentModel->type = 'comment';
            $commentModel->associationId = $postModel->id;
            $commentModel->associationType = 'Events';
            $commentModel->timestamp = time();

            if ($commentModel->save()) {
                $commentCount = X2Model::model('Events')->countByAttributes(array(
                    'type' => 'comment',
                    'associationType' => 'Events',
                    'associationId' => $postModel->id,
                ));
                $postModel->lastUpdated = time();
                $postModel->save();

                $profileUser = Yii::app()->db->createCommand()
                        ->select('username')
                        ->from('x2_users')
                        ->where('id=:id', array(':id' => $postModel->associationId))
                        ->queryScalar();


                // notify the owner of the feed containing the post you commented on (unless that person is you)
                if ($postModel->associationId != Yii::app()->user->getId()) {
                    $postNotif = new Notification;
                    $postNotif->type = 'social_comment';
                    $postNotif->createdBy = $commentModel->user;
                    $postNotif->modelType = 'Profile';
                    $postNotif->modelId = $postModel->associationId;

                    // look up the username of the owner of the feed
                    $postNotif->user = $profileUser;

                    $postNotif->createDate = time();
                    $postNotif->save();
                }
                // now notify the person whose post you commented on (unless they're the same person as the first notification)
                if ($profileUser != $postModel->user && $postModel->user != Yii::app()->user->name) {
                    $commentNotif = new Notification;
                    $commentNotif->type = 'social_comment';
                    $commentNotif->createdBy = $commentModel->user;
                    $commentNotif->modelType = 'Profile';
                    $commentNotif->modelId = $postModel->associationId;

                    $commentNotif->user = $postModel->user;

                    $commentNotif->createDate = time();
                    $commentNotif->save();
                }
            }
            echo $commentCount;
        } else {
            echo "";
        }
    }

    public function actionToggleFeedFilters($filter) {
        $profile = Yii::app()->params->profile;
        if (isset($profile)) {
            $filters = json_decode($profile->feedFilters, true);
            if (isset($filters[$filter])) {
                $filters[$filter] = $filters[$filter] == 1 ? 0 : 1;
            } else {
                $filters[$filter] = 0;
            }
            $flag = $filters[$filter];
            $profile->feedFilters = json_encode($filters);
            $profile->update(array('feedFilters'));
            echo $flag;
        }
    }

    public function actionToggleFeedControls() {
        $profile = Yii::app()->params->profile;
        if (isset($profile)) {
            $profile->fullFeedControls = !$profile->fullFeedControls;
            $profile->update(array('fullFeedControls'));
        }
    }

    /**
     * Saves a default template for the specified module into the users profile settings
     * @param string $moduleName
     * @param int $templateId
     */
    public function actionAjaxSaveDefaultEmailTemplate($moduleName, $templateId) {
        $profile = Yii::app()->params->profile;
        $errors = false;
        $message = '';
        if (isset($profile)) {
            $defaultEmailTemplates = CJSON::decode($profile->defaultEmailTemplates);

            if ($templateId !== '') {
                $template = Docs::model()->findByPk($templateId);
                if (!$this->checkPermissions ($template, 'view')) {
                    $errors = true;
                    $message = Yii::t(
                        'profile', 'You do not have permission to view that template');
                } else {
                    // check that template exists, that it's of the correct doc type, and is 
                    // associated with the correct model type
                    if ($template && $template->type === 'email' && 
                        $template->associationType === X2Model::getModelName ($moduleName)) {

                        $defaultEmailTemplates[$moduleName] = $templateId;
                        $profile->defaultEmailTemplates = CJSON::encode($defaultEmailTemplates);
                        $profile->save();
                    } else {
                        $errors = true;
                        $message = Yii::t('profile', 'Invalid template');
                    }
                }
            } else { // remove default
                if (isset($defaultEmailTemplates[$moduleName]))
                    unset($defaultEmailTemplates[$moduleName]);
                $profile->defaultEmailTemplates = CJSON::encode($defaultEmailTemplates);
                $profile->save();
            }
        } else {
            $message = Yii::t('profile', 'Profile not found');
        }
        echo CJSON::encode(array(
            'success' => !$errors,
            'message' => $message,
        ));
    }

}
