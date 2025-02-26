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

Yii::import('application.components.X2LinkableBehavior');
Yii::import('application.modules.users.models.*');
Yii::import('application.components.JSONFieldsBehavior');
Yii::import('application.components.WidgetLayoutJSONFieldsBehavior');
Yii::import('application.components.X2SmartSearchModelBehavior');

/**
 * This is the model class for table "x2_profile".
 * @package application.models
 */
class Profile extends CActiveRecord {

    private $_isActive;

    public function setIsActive ($isActive) {
        if ($isActive === '0' || $isActive === 'false') {
            $this->_isActive = 0;
        } else if ($isActive === '1' || $isActive === 'true') {
            $this->_isActive = 1;
        }
    }

    public function getIsActive () {
        if (isset ($this->_isActive)) {
            return $this->_isActive;
        } else {
            return null;
        }
    }

    /**
     * Returns the static model of the specified AR class.
     * @return Profile the static model class
     */
    public static function model($className = __CLASS__){
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName(){
        return 'x2_profile';
    }

    public function behaviors(){
        return array(
            'X2LinkableBehavior' => array(
                'class' => 'X2LinkableBehavior',
                'baseRoute' => '/profile',
                'autoCompleteSource' => null,
                'module' => 'profile'
            ),
            'ERememberFiltersBehavior' => array(
                'class' => 'application.components.ERememberFiltersBehavior',
                'defaults' => array(),
                'defaultStickOnClear' => false
            ),
            'JSONFieldsBehavior' => array(
                'class' => 'application.components.JSONFieldsBehavior',
                'transformAttributes' => array(
                    'theme' => array(
                        'backgroundColor', 'menuBgColor', 'menuTextColor', 'pageHeaderBgColor',
                        'pageHeaderTextColor', 'activityFeedWidgetBgColor',
                        'activityFeedWidgetTextColor', 'backgroundImg', 'backgroundTiling',
                        'pageOpacity', 'themeName', 'private', 'owner', 'loginSound',
                        'notificationSound', 'gridViewRowColorOdd', 'gridViewRowColorEven'),
                ),
            ),
            'JSONFieldsDefaultValuesBehavior' => array(
                'class' => 'application.components.JSONFieldsDefaultValuesBehavior',
                'transformAttributes' => array(
                    'miscLayoutSettings' => array(
                        'themeSectionExpanded'=>true, // preferences theme sub section
                        'unhideTagsSectionExpanded'=>true, // preferences tag sub section
                        'x2flowShowLabels'=>true, // flow node labels
                        'profileInfoIsMinimized'=>false, // profile page profile info section
                        'fullProfileInfo'=>false, // profile page profile info section
                        'perStageWorkflowView'=>true, // selected workflow view interface
                    ),
                ),
                'maintainCurrentFieldsOrder' => true
            ),
            'WidgetLayoutJSONFieldsBehavior' => array(
                'class' => 'application.components.WidgetLayoutJSONFieldsBehavior',
                'transformAttributes' => array (
                    'profileWidgetLayout' => array (
                        'EventsChartProfileWidget',
                        'UsersChartProfileWidget',
                        'ProfilesGridViewProfileWidget',
                        'ContactsGridViewProfileWidget',
                        'AccountsGridViewProfileWidget',
                        'ActionsGridViewProfileWidget',
                        'OpportunitiesGridViewProfileWidget',
                        'MarketingGridViewProfileWidget',
                        'ServicesGridViewProfileWidget',
                        'QuotesGridViewProfileWidget',
                        'DocViewerProfileWidget',
                    )
                )
            ),
            'X2SmartSearchModelBehavior' => array (
                'class' => 'application.components.X2SmartSearchModelBehavior',
            )
        );
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('fullName, username, status', 'required'),
            array('status, lastUpdated, disableNotifPopup, allowPost, disableAutomaticRecordTagging, disablePhoneLinks, resultsPerPage', 'numerical', 'integerOnly' => true),
            array('enableFullWidth,showSocialMedia,showDetailView,disableTimeInTitle', 'boolean'), //,showWorkflow
            array('emailUseSignature', 'length', 'max' => 10),
            array('startPage', 'length', 'max' => 30),
            array('googleId', 'unique'),
            array('fullName', 'length', 'max' => 60),
            array('username, updatedBy', 'length', 'max' => 20),
            array('officePhone, extension, cellPhone, language', 'length', 'max' => 40),
            array('timeZone', 'length', 'max' => 100),
            array('widgets, tagLine, emailAddress', 'length', 'max' => 255),
            array('widgetOrder', 'length', 'max' => 512),
            array('emailSignature', 'safe'),
            array('notes, avatar, gridviewSettings, formSettings, widgetSettings', 'safe'),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('id, fullName, username, officePhone, extension, cellPhone, emailAddress, lastUpdated, language', 'safe', 'on' => 'search')
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations(){
        return array(
            'user' => array(self::HAS_ONE, 'User', array ('username' => 'username'))
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels(){
        return array(
            'id' => Yii::t('profile', 'ID'),
            'fullName' => Yii::t('profile', 'Full Name'),
            'username' => Yii::t('profile', 'Username'),
            'officePhone' => Yii::t('profile', 'Office Phone'),
            'extension' => Yii::t('profile','Extension'),
            'cellPhone' => Yii::t('profile', 'Cell Phone'),
            'emailAddress' => Yii::t('profile', 'Email Address'),
            'notes' => Yii::t('profile', 'Notes'),
            'status' => Yii::t('profile', 'Status'),
            'tagLine' => Yii::t('profile', 'Tag Line'),
            'lastUpdated' => Yii::t('profile', 'Last Updated'),
            'updatedBy' => Yii::t('profile', 'Updated By'),
            'avatar' => Yii::t('profile', 'Avatar'),
            'allowPost' => Yii::t('profile', 'Allow users to post on your profile?'),
            'disablePhoneLinks' => Yii::t('profile', 'Disable phone field links?'),
            'disableAutomaticRecordTagging' => 
                Yii::t('profile', 'Disable automatic record tagging?'),
            'disableTimeInTitle' => Yii::t('profile','Disable timer display in page title?'),
            'disableNotifPopup' => Yii::t('profile', 'Disable notifications pop-up?'),
            'language' => Yii::t('profile', 'Language'),
            'timeZone' => Yii::t('profile', 'Time Zone'),
            'widgets' => Yii::t('profile', 'Widgets'),
            // 'groupChat'=>Yii::t('profile','Enable group chat?'),
            'widgetOrder' => Yii::t('profile', 'Widget Order'),
            'widgetSettings' => Yii::t('profile', 'Widget Settings'),
            'resultsPerPage' => Yii::t('profile', 'Results Per Page'),
            /* 'menuTextColor' => Yii::t('profile', 'Menu Text Color'),
              'menuBgColor' => Yii::t('profile', 'Menu Color'),
              'menuTextColor' => Yii::t('profile', 'Menu Text Color'),
              'pageHeaderBgColor' => Yii::t('profile', 'Page Header Color'),
              'pageHeaderTextColor' => Yii::t('profile', 'Page Header Text Color'),
              'activityFeedWidgetBgColor' => Yii::t('profile', 'Activity Feed Widget Background Color'),
              'backgroundColor' => Yii::t('profile', 'Background Color'),
              'backgroundTiling' => Yii::t('profile', 'Background Tiling'),
              'pageOpacity' => Yii::t('profile', 'Page Opacity'), */
            'startPage' => Yii::t('profile', 'Start Page'),
            'showSocialMedia' => Yii::t('profile', 'Show Social Media'),
            'showDetailView' => Yii::t('profile', 'Show Detail View'),
            // 'showWorkflow'=>Yii::t('profile','Show Workflow'),
            'gridviewSettings' => Yii::t('profile', 'Gridview Settings'),
            'formSettings' => Yii::t('profile', 'Form Settings'),
            'emailUseSignature' => Yii::t('profile', 'Email Signature'),
            'emailSignature' => Yii::t('profile', 'My Signature'),
            'enableFullWidth' => Yii::t('profile', 'Enable Full Width Layout'),
            'googleId' => Yii::t('profile', 'Google ID'),
            'address' => Yii::t('profile', 'Address'),
        );
    }

    /**
     * Masks method in X2SmartSearchModelBehavior. Enables sorting by lastLogin and isActive.
     */
    public function getSort () {
        $attributes = array();
        foreach($this->owner->attributes as $name => $val) {
            $attributes[$name] = array(
                'asc' => 't.'.$name.' ASC',
                'desc' => 't.'.$name.' DESC',
            );
        }
        $attributes['lastLogin'] = array (
            'asc' => '(SELECT lastLogin from x2_users '.
                'WHERE x2_users.username=t.username) ASC',
            'desc' => '(SELECT lastLogin from x2_users '.
                'WHERE x2_users.username=t.username) DESC',
        );
        $attributes['isActive'] = array (
            'asc' => 
                '(SELECT DISTINCT user '.
                    'FROM x2_sessions '.
                    'WHERE t.username=x2_sessions.user AND '.
                        'x2_sessions.lastUpdated > '.(time () - 900).
                ') DESC ',
            'desc' => 
                '(SELECT DISTINCT user '.
                    'FROM x2_sessions '.
                    'WHERE t.username=x2_sessions.user AND '.
                        'x2_sessions.lastUpdated > '.(time () - 900).
                ') ASC',
        );
        return $attributes;
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search($resultsPerPage=null, $uniqueId=null, $excludeAPI=false){
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.

        $criteria = new CDbCriteria;

        $criteria->distinct = true;
        $criteria->compare('id', $this->id);
        $criteria->compare('fullName', $this->fullName, true);
        $criteria->compare('username', $this->username, true);
        $criteria->compare('officePhone', $this->officePhone, true);
        $criteria->compare('cellPhone', $this->cellPhone, true);
        $criteria->compare('emailAddress', $this->emailAddress, true);
        $criteria->compare('status', $this->status);
        $criteria->compare('tagLine',$this->tagLine,true);


        /*
        Filter on is active model property
        */
        if (isset ($_GET['Profile']) && is_array ($_GET['Profile']) &&
            in_array ('isActive', array_keys ($_GET['Profile']))) {

            $this->isActive = $_GET['Profile']['isActive'];
            if (!isset ($this->isActive)) { // invalid isActive value
            } else if ($this->isActive) { // select all users with new session records
                $criteria->join = 
                    'JOIN x2_sessions ON x2_sessions.user=username and '.
                    'x2_sessions.lastUpdated > "'.(time () - 900).'"';
            } else { // select all users with old session records or no session records
                $criteria->join = 
                    'JOIN x2_sessions ON (x2_sessions.user=username and '.
                    'x2_sessions.lastUpdated <= "'.(time () - 900).'") OR '.
                    'username not in (select x2_sessions.user from x2_sessions as x2_sessions)';
            }
        } 

        if ($excludeAPI) {
            if ($criteria->condition !== '') {
                $criteria->condition .= ' AND username!=\'API\'';
            } else { 
                $criteria->condition = 'username!=\'API\'';
            }
        }

        return $this->smartSearch ($criteria, $resultsPerPage, $uniqueId);
    }

    /**
     * Sets a miscLayoutSetting JSON property to the specified value
     *
     * @param string $settingName The name of the JSON property
     * @param string $settingValue The value that the JSON property will bet set to
     */
    public static function setMiscLayoutSetting (
        $settingName, $settingValue, $suppressEcho=false) {

        $model = Profile::model ()->findByPk (Yii::app()->user->getId());
        $settings = $model->miscLayoutSettings;
        if (!in_array ($settingName, array_keys ($settings))) {
            echo 'failure';
            return;
        }
        $settings[$settingName] = $settingValue;
        $model->miscLayoutSettings = $settings;
        $echoVal = '';
        if (!$model->save ()) {
            AuxLib::debugLog ('Error: setMiscLayoutSetting: failed to save model');
            $echoVal = 'failure';
        } else {
            $echoVal = 'success';
        }

        if (!$suppressEcho) echo $echoVal;
    }

    public static function setDetailView($value){
        $model = Profile::model()->findByPk(Yii::app()->user->getId()); // set user's preference for contact detail view
        $model->showDetailView = ($value == 1) ? 1 : 0;
        $model->upadte(array('showDetailView'));
    }

    public static function getDetailView(){
        $model = Profile::model()->findByPk(Yii::app()->user->getId()); // get user's preference for contact detail view
        return $model->showDetailView;
    }

    // public static function getSocialMedia() {
    // $model = Profile::model()->findByPk(Yii::app()->user->getId());    // get user's preference for contact social media info
    // return $model->showSocialMedia;
    // }

    public function getSignature($html = false){

        $adminRule = Yii::app()->settings->emailUseSignature;
        $userRule = $this->emailUseSignature;
        $signature = '';

        switch($adminRule){
            case 'admin': $signature = Yii::app()->settings->emailSignature;
                break;
            case 'user':
                switch($userRule){
                    case 'user': $signature = $signature = $this->emailSignature;
                        break;
                    case 'admin': Yii::app()->settings->emailSignature;
                        break;
                    case 'group': $signature == '';
                        break;
                    default: $signature == '';
                }
                break;
            case 'group': $signature == '';
                break;
            default: $signature == '';
        }


        $signature = preg_replace(
                array(
            '/\{first\}/',
            '/\{last\}/',
            '/\{phone\}/',
            '/\{group\}/',
            '/\{email\}/',
                ), array(
            $this->user->firstName,
            $this->user->lastName,
            $this->officePhone,
            '',
            $html ? CHtml::mailto($this->emailAddress) : $this->emailAddress,
                ), $signature
        );
        if($html){
            $signature = Formatter::convertLineBreaks($signature);
        }

        return $signature;
    }

    public static function getResultsPerPage(){
        if(!Yii::app()->user->isGuest)
            $resultsPerPage = Yii::app()->params->profile->resultsPerPage;
        // $model = Profile::model()->findByPk(Yii::app()->user->getId());    // get user's preferred results per page
        // $resultsPerPage = $model->resultsPerPage;

        return empty($resultsPerPage) ? 15 : $resultsPerPage;
    }

    public static function getPossibleResultsPerPage(){
        return array(
            10 => Yii::t('app', '{n} rows', array('{n}' => '10')),
            20 => Yii::t('app', '{n} rows', array('{n}' => '20')),
            30 => Yii::t('app', '{n} rows', array('{n}' => '30')),
            40 => Yii::t('app', '{n} rows', array('{n}' => '40')),
            50 => Yii::t('app', '{n} rows', array('{n}' => '50')),
            75 => Yii::t('app', '{n} rows', array('{n}' => '75')),
            100 => Yii::t('app', '{n} rows', array('{n}' => '100')),
        );
    }

    // lookup user's settings for a gridview (visible columns, column widths)
    public static function getGridviewSettings($viewName = null){
        if(!Yii::app()->user->isGuest)
            $gvSettings = json_decode(Yii::app()->params->profile->gridviewSettings, true); // converts JSON string to assoc. array
        if(isset($viewName)){
            $viewName = strtolower($viewName);
            if(isset($gvSettings[$viewName]))
                return $gvSettings[$viewName];
            else
                return null;
        } elseif(isset($gvSettings)){
            return $gvSettings;
        }else{
            return null;
        }
    }

    // add/update settings for a specific gridview, or save all at once
    public static function setGridviewSettings($gvSettings, $viewName = null){
        if(!Yii::app()->user->isGuest){
            if(isset($viewName)){
                $fullGvSettings = Profile::getGridviewSettings();
                $fullGvSettings[strtolower($viewName)] = $gvSettings;
                Yii::app()->params->profile->gridviewSettings = json_encode($fullGvSettings); // encode array in JSON
            }else{
                Yii::app()->params->profile->gridviewSettings = json_encode($gvSettings); // encode array in JSON
            }
            return Yii::app()->params->profile->update(array('gridviewSettings'));
        }else{
            return null;
        }
    }

    // lookup user's settings for a gridview (visible columns, column widths)
    public static function getFormSettings($formName = null){
        if(!Yii::app()->user->isGuest){
            $formSettings = json_decode(Yii::app()->params->profile->formSettings, true); // converts JSON string to assoc. array
            if($formSettings == null)
                $formSettings = array();
            if(isset($formName)){
                $formName = strtolower($formName);
                if(isset($formSettings[$formName]))
                    return $formSettings[$formName];
                else
                    return array();
            } else{
                return $formSettings;
            }
        }else{
            return array();
        }
    }

    // add/update settings for a specific form, or save all at once
    public static function setFormSettings($formSettings, $formName = null){
        if(isset($formName)){
            $fullFormSettings = Profile::getFormSettings();
            $fullFormSettings[strtolower($formName)] = $formSettings;
            Yii::app()->params->profile->formSettings = json_encode($fullFormSettings); // encode array in JSON
        }else{
            Yii::app()->params->profile->formSettings = json_encode($formSettings); // encode array in JSON
        }
        return Yii::app()->params->profile->update(array('formSettings'));
    }

    public static function getWidgets(){

        if(Yii::app()->user->isGuest) // no widgets if the user isn't logged in
            return array();
        // $model = Profile::model('Profile')->findByPk(Yii::app()->user->getId());
        $model = Yii::app()->params->profile;
        if(!isset($model)){
            $model = Profile::model()->findByPk(Yii::app()->user->getId());
        }

        $registeredWidgets = array_keys(Yii::app()->params->registeredWidgets);

        $widgetNames = ($model->widgetOrder == '') ? array() : explode(":", $model->widgetOrder);
        $visibility = ($model->widgets == '') ? array() : explode(":", $model->widgets);

        $widgetList = array();
        $updateRecord = false;

        for($i = 0; $i < count($widgetNames); $i++){

            if(!in_array($widgetNames[$i], $registeredWidgets)){ // check the main cfg file
                unset($widgetNames[$i]);       // if widget isn't listed,
                unset($visibility[$i]);        // remove it from database fields
                $updateRecord = true;
            }else{
                $widgetList[$widgetNames[$i]] = array(
                    'id' => 'widget_'.$widgetNames[$i], 'visibility' => $visibility[$i],
                    'params' => array());
            }
        }

        foreach($registeredWidgets as $class){   // check list of widgets in main cfg file
            if(!in_array($class, array_keys($widgetList))){        // if they aren't in the list,
                $widgetList[$class] = array(
                    'id' => 'widget_'.$class, 'visibility' => 1,
                    'params' => array()); // add them at the bottom

                $widgetNames[] = $class; // add new widgets to widgetOrder array
                $visibility[] = 1;   // and visibility array
                $updateRecord = true;
            }
        }

        if($updateRecord){
            $model->widgetOrder = implode(':', $widgetNames); // update database fields
            $model->widgets = implode(':', $visibility);   // if there are new widgets
            $model->update(array('widgetOrder', 'widgets'));
        }

        return $widgetList;
    }

    public static function getWidgetSettings(){
        if(Yii::app()->user->isGuest) // no widgets if the user isn't logged in
            return array();

        // if widget settings haven't been set, give them default values
        if(Yii::app()->params->profile->widgetSettings == null){
            $widgetSettings = array(
                'ChatBox' => array(
                    'chatboxHeight' => 300,
                    'chatmessageHeight' => 50,
                ),
                'NoteBox' => array(
                    'noteboxHeight' => 200,
                    'notemessageHeight' => 50,
                ),
                'DocViewer' => array(
                    'docboxHeight' => 200,
                ),
                'TopSites' => array(
                    'topsitesHeight' => 200,
                    'urltitleHeight' => 10,
                ),
            );

            Yii::app()->params->profile->widgetSettings = json_encode($widgetSettings);
            Yii::app()->params->profile->update(array('widgetSettings'));
        }

        $widgetSettings = json_decode(Yii::app()->params->profile->widgetSettings);
        if(!isset($widgetSettings->MediaBox)){
            $widgetSettings->MediaBox = array('mediaBoxHeight' => 150, 'hideUsers' => array());
            Yii::app()->params->profile->widgetSettings = json_encode($widgetSettings);
            Yii::app()->params->profile->update(array('widgetSettings'));
        }

        return json_decode(Yii::app()->params->profile->widgetSettings);
    }

    public function getLink(){

        $noSession = Yii::app()->params->noSession;
        if(!$noSession){
            if($this->id == Yii::app()->user->id)
                return CHtml::link(Yii::t('app', 'your feed'), array($this->baseRoute.'/'.$this->id));
            else
                return CHtml::link(Yii::t('app', '{name}\'s feed', array('{name}' => $this->fullName)), array($this->baseRoute.'/'.$this->id));
        } else{
            return CHtml::link($this->fullName, Yii::app()->absoluteBaseUrl.'/index.php'.$this->baseRoute.'/'.$this->id);
        }
    }

    public function syncActionToGoogleCalendar($action){
        try{ // catch google exceptions so the whole app doesn't crash if google has a problem syncing
            $admin = Yii::app()->settings;
            if($admin->googleIntegration){
                if(isset($this->syncGoogleCalendarId) && $this->syncGoogleCalendarId){
//                    // Google Calendar Libraries
//                    $timezone = date_default_timezone_get();
//                    require_once "protected/extensions/google-api-php-client/src/Google_Client.php";
//                    require_once "protected/extensions/google-api-php-client/src/contrib/Google_CalendarService.php";
//                    date_default_timezone_set($timezone);
//
//                    $client = new Google_Client();
//                    $client->setClientId($admin->googleClientId);
//                    $client->setClientSecret($admin->googleClientSecret);
//                    //$client->setDeveloperKey($admin->googleAPIKey);
//                    $client->setAccessToken($this->syncGoogleCalendarAccessToken);
//                    $googleCalendar = new Google_CalendarService($client);
                    $auth = new GoogleAuthenticator();
                    $googleCalendar = $auth->getCalendarService();

                    // check if the access token needs to be refreshed
                    // note that the google library automatically refreshes the access token if 
                    // we need a new one,
                    // we just need to check if this happend by calling a google api function that 
                    // requires authorization,
                    // and, if the access token has changed, save this new access token
                    if(!$googleCalendar){
                        Yii::app()->controller->redirect($auth->getAuthorizationUrl('calendar'));
                    }
//                    if($this->syncGoogleCalendarAccessToken != $client->getAccessToken()){
//                        $this->syncGoogleCalendarAccessToken = $client->getAccessToken();
//                        $this->update(array('syncGoogleCalendarAccessToken'));
//                    }

                    $summary = $action->actionDescription;
                    if($action->associationType == 'contacts' || $action->associationType == 'contact')
                        $summary = $action->associationName.' - '.$action->actionDescription;

                    $event = new Google_Event();
                    $event->setSummary($summary);
                    if(empty($action->dueDate)){
                        $action->dueDate = time();
                    }
                    if($action->allDay){
                        $start = new Google_EventDateTime();
                        $start->setDate(date('Y-m-d', $action->dueDate));
                        $event->setStart($start);

                        if(!$action->completeDate)
                            $action->completeDate = $action->dueDate;
                        $end = new Google_EventDateTime();
                        $end->setDate(date('Y-m-d', $action->completeDate + 86400));
                        $event->setEnd($end);
                    } else{
                        $start = new Google_EventDateTime();
                        $start->setDateTime(date('c', $action->dueDate));
                        $event->setStart($start);

                        if(!$action->completeDate)
                            $action->completeDate = $action->dueDate; // if no end time specified, make event 1 hour long
                        $end = new Google_EventDateTime();
                        $end->setDateTime(date('c', $action->completeDate));
                        $event->setEnd($end);
                    }

                    if($action->color && $action->color != '#3366CC'){
                        $colorTable = array(
                            10 => 'Green',
                            11 => 'Red',
                            6 => 'Orange',
                            8 => 'Black',
                        );
                        if(($key = array_search($action->color, $colorTable)) != false)
                            $event->setColorId($key);
                    }

                    $newEvent = $googleCalendar->events->insert($this->syncGoogleCalendarId, $event);
                    $action->syncGoogleCalendarEventId = $newEvent['id'];
                    $action->save();
                }
            }
        }catch(Exception $e){
            if(isset($auth)){
                $auth->flushCredentials();
            }
        }
    }

    public function updateGoogleCalendarEvent($action){
        try{ // catch google exceptions so the whole app doesn't crash if google has a problem syncing
            $admin = Yii::app()->settings;
            if($admin->googleIntegration){
                if(isset($this->syncGoogleCalendarId) && $this->syncGoogleCalendarId){
//                    // Google Calendar Libraries
//                    $timezone = date_default_timezone_get();
//                    require_once "protected/extensions/google-api-php-client/src/Google_Client.php";
//                    require_once "protected/extensions/google-api-php-client/src/contrib/Google_CalendarService.php";
//                    date_default_timezone_set($timezone);
//
//                    $client = new Google_Client();
//                    $client->setClientId($admin->googleClientId);
//                    $client->setClientSecret($admin->googleClientSecret);
//                    //$client->setDeveloperKey($admin->googleAPIKey);
//                    $client->setAccessToken($this->syncGoogleCalendarAccessToken);
//                    $client->setUseObjects(true); // return objects instead of arrays
//                    $googleCalendar = new Google_CalendarService($client);
                    $auth = new GoogleAuthenticator();
                    $googleCalendar = $auth->getCalendarService();

                    // check if the access token needs to be refreshed
                    // note that the google library automatically refreshes the access token if we need a new one,
                    // we just need to check if this happend by calling a google api function that requires authorization,
                    // and, if the access token has changed, save this new access token
                    $testCal = $googleCalendar->calendars->get($this->syncGoogleCalendarId);
//                    if($this->syncGoogleCalendarAccessToken != $client->getAccessToken()){
//                        $this->syncGoogleCalendarAccessToken = $client->getAccessToken();
//                        $this->update(array('syncGoogleCalendarAccessToken'));
//                    }

                    $summary = $action->actionDescription;
                    if($action->associationType == 'contacts' || $action->associationType == 'contact')
                        $summary = $action->associationName.' - '.$action->actionDescription;

                    $event = $googleCalendar->events->get($this->syncGoogleCalendarId, $action->syncGoogleCalendarEventId);
                    if(is_array($event)){
                        $event = new Google_Event($event);
                    }
                    $event->setSummary($summary);
                    if(empty($action->dueDate)){
                        $action->dueDate = time();
                    }
                    if($action->allDay){
                        $start = new Google_EventDateTime();
                        $start->setDate(date('Y-m-d', $action->dueDate));
                        $event->setStart($start);

                        if(!$action->completeDate)
                            $action->completeDate = $action->dueDate;
                        $end = new Google_EventDateTime();
                        $end->setDate(date('Y-m-d', $action->completeDate + 86400));
                        $event->setEnd($end);
                    } else{
                        $start = new Google_EventDateTime();
                        $start->setDateTime(date('c', $action->dueDate));
                        $event->setStart($start);

                        if(!$action->completeDate)
                            $action->completeDate = $action->dueDate; // if no end time specified, make event 1 hour long
                        $end = new Google_EventDateTime();
                        $end->setDateTime(date('c', $action->completeDate));
                        $event->setEnd($end);
                    }

                    if($action->color && $action->color != '#3366CC'){
                        $colorTable = array(
                            10 => 'Green',
                            11 => 'Red',
                            6 => 'Orange',
                            8 => 'Black',
                        );
                        if(($key = array_search($action->color, $colorTable)) != false)
                            $event->setColorId($key);
                    }

                    $newEvent = $googleCalendar->events->update($this->syncGoogleCalendarId, $action->syncGoogleCalendarEventId, $event);
                }
            }
        }catch(Exception $e){

        }
    }

    public function deleteGoogleCalendarEvent($action){
        try{ // catch google exceptions so the whole app doesn't crash if google has a problem syncing
            $admin = Yii::app()->settings;
            if($admin->googleIntegration){
                if(isset($this->syncGoogleCalendarId) && $this->syncGoogleCalendarId){
                    // Google Calendar Libraries
                    $timezone = date_default_timezone_get();
                    require_once "protected/extensions/google-api-php-client/src/Google_Client.php";
                    require_once "protected/extensions/google-api-php-client/src/contrib/Google_CalendarService.php";
                    date_default_timezone_set($timezone);

                    $client = new Google_Client();
                    $client->setClientId($admin->googleClientId);
                    $client->setClientSecret($admin->googleClientSecret);
                    //$client->setDeveloperKey($admin->googleAPIKey);
                    $client->setAccessToken($this->syncGoogleCalendarAccessToken);
                    $client->setUseObjects(true); // return objects instead of arrays
                    $googleCalendar = new Google_CalendarService($client);

                    $googleCalendar->events->delete($this->syncGoogleCalendarId, $action->syncGoogleCalendarEventId);
                }
            }
        }catch(Exception $e){
            // We may want to look into handling this better, or bugs will cause silent failures.
        }
    }

    /**
     * Inits a layout for viewing a module. The layout is a set of associative arrays
     * with the following format:
     * array (
     * 'left'=> array()
     *  'content' => array(
     *    'widget1'=> array(
     *      'name' => 'widget name',
     *    )
     *  )
     * 'right' => array()
     * )
     *
     * The layout should be json encoded and saved in the layout column of the user's profile.
     *
     * @return array
     */
    function initLayout(){
        $layout = array(
            'left' => array(
                'TopContacts' => array(
                    'title' => 'Top Contacts',
                    'minimize' => false,
                ),
                'RecentItems' => array(
                    'title' => 'Recently Viewed',
                    'minimize' => false,
                ),
                'ActionMenu' => array(
                    'title' => 'Actions',
                    'minimize' => false,
                ),
                'ActionTimer' => array(
                    'title' => 'Action Timer',
                    'minimize' => false,
                ),
                'UserCalendars' => array(
                    'title' => 'User Calendars',
                    'minimize' => false,
                ),
                'CalendarFilter' => array(
                    'title' => 'Filter',
                    'minimize' => false,
                ),
                'GroupCalendars' => array(
                    'title' => 'Group Calendars',
                    'minimize' => false,
                ),
                'FilterControls' => array(
                    'title' => 'Filter Controls',
                    'minimize' => false,
                ),
                'SimpleFilterControlEventTypes' => array(
                    'title' => 'Event Types',
                    'minimize' => false,
                ),
            ),
            'center' => array(
                'RecordViewChart' => array(
                    'title' => 'Record View Chart',
                    'minimize' => false,
                ),
                'InlineTags' => array(
                    'title' => 'Tags',
                    'minimize' => false,
                ),
                'WorkflowStageDetails' => array(
                    'title' => 'Process',
                    'minimize' => false,
                ),
                'InlineRelationships' => array(
                    'title' => 'Relationships',
                    'minimize' => false,
                ),
            ),
            'right' => array(
                'ActionMenu' => array(
                    'title' => 'My Actions',
                    'minimize' => false,
                ),
                'ChatBox' => array(
                    'title' => 'Activity Feed',
                    'minimize' => false,
                ),
                'GoogleMaps' => array(
                    'title' => 'Google Map',
                    'minimize' => false,
                ),
                'OnlineUsers' => array(
                    'title' => 'Active Users',
                    'minimize' => false,
                ),
                'TagCloud' => array(
                    'title' => 'Tag Cloud',
                    'minimize' => false,
                ),
                'TimeZone' => array(
                    'title' => 'Time Zone',
                    'minimize' => false,
                ),
                'MessageBox' => array(
                    'title' => 'Message Board',
                    'minimize' => false,
                ),
                'QuickContact' => array(
                    'title' => 'Quick Contact',
                    'minimize' => false,
                ),
                'NoteBox' => array(
                    'title' => 'Note Pad',
                    'minimize' => false,
                ),
                'MediaBox' => array(
                    'title' => 'Files',
                    'minimize' => false,
                ),
                'DocViewer' => array(
                    'title' => 'Doc Viewer',
                    'minimize' => false,
                ),
                'TopSites' => array(
                    'title' => 'Top Sites',
                    'minimize' => false,
                ),
                'HelpfulTips' => array(
                    'title' => 'Helpful Tips',
                    'minimize' => false,
                ),
            ),
            'hidden' => array(),
            'hiddenRight' => array(), // x2temp, should be merged into 'hidden' when widgets can be placed anywhere
        );
        if(Yii::app()->contEd('pro')){
            if(file_exists('protected/config/proWidgets.php')){
                foreach(include('protected/config/proWidgets.php') as $loc=>$data){
                    $layout[$loc] = array_merge($layout[$loc],$data);
                }
            }
        }
        return $layout;
    }


    /*
      Private helper function to update users layout elements to match the set of layout
      elements specified in initLayout ().
     */
    private function addRemoveLayoutElements($position, &$layout, $initLayout){

        $changed = false;

        $layoutWidgets = array_merge($layout[$position], $layout['hidden']);
        if ($position === 'center') {
            $initLayoutWidgets = array_merge($initLayout[$position], $initLayout['hidden']);
        } else {
            $initLayoutWidgets = $initLayout[$position];
        }

        // add new widgets
        $arrayDiff =
                array_diff(array_keys($initLayoutWidgets), array_keys($layoutWidgets));
        foreach($arrayDiff as $elem){
            //$layout[$position][$elem] = $initLayout[$position][$elem];
            $layout[$position] = array($elem => $initLayout[$position][$elem]) + $layout[$position]; // unshift key-value pair
            $changed = true;
        }

        // remove obsolete widgets
        $arrayDiff =
                array_diff(array_keys($layoutWidgets), array_keys($initLayoutWidgets));
        foreach($arrayDiff as $elem){
            if(in_array ($elem, array_keys ($layout[$position]))) {
                unset($layout[$position][$elem]);
                $changed = true;
            } else if($position === 'center' && in_array ($elem, array_keys ($layout['hidden']))) {
                unset($layout['hidden'][$elem]);
                $changed = true;
            }
        }

        // ensure that widget properties are the same as those in the default layout
        foreach($layout[$position] as $name=>$arr){
            if (in_array ($name, array_keys ($initLayout[$position])) &&
                $initLayout[$position][$name]['title'] !== $arr['title']) {

                $layout[$position][$name]['title'] = $initLayout[$position][$name]['title'];
                $changed = true;
            }
        }
        if ($position === 'center') {
            foreach($layout['hidden'] as $name=>$arr){
                if (in_array ($name, array_keys ($initLayout[$position])) &&
                    $initLayout[$position][$name]['title'] !== $arr['title']) {

                    $layout['hidden'][$name]['title'] = $initLayout[$position][$name]['title'];
                    $changed = true;
                }
            }
        }

        if($changed){
            $this->layout = json_encode($layout);
            $this->update(array('layout'));
        }
    }

    /**
     * Returns the layout for the user's widgets as an associative array.
     *
     * @return array
     */
    public function getLayout(){
        $layout = $this->getAttribute('layout');

        $initLayout = $this->initLayout();

        if(!$layout){ // layout hasn't been initialized?
            $layout = $initLayout;
            $this->layout = json_encode($layout);
            $this->update(array('layout'));
        }else{
            $layout = json_decode($layout, true); // json to associative array
            $this->addRemoveLayoutElements('center', $layout, $initLayout);
            $this->addRemoveLayoutElements('left', $layout, $initLayout);
            $this->addRemoveLayoutElements('right', $layout, $initLayout);
        }

        return $layout;
    }

    public function getHiddenProfileWidgetMenu () {
        $profileWidgetLayout = $this->profileWidgetLayout;

        $hiddenProfileWidgetsMenu = '';
        $hiddenProfile = false;
        foreach($profileWidgetLayout as $name => $widgetSettings){
            $hidden = $widgetSettings['hidden'];
            if ($hidden) {
                $hiddenProfileWidgetsMenu .= '<li><span class="x2-hidden-widgets-menu-item profile-widget" id="'.$name.'">'.
                    $widgetSettings['label'].'</span></li>';
                $hiddenProfile = true;
            }
        }
        $menu = '<div id="x2-hidden-profile-widgets-menu-container" style="display:none;">';
        $menu .= '<ul id="x2-hidden-profile-widgets-menu" class="x2-hidden-widgets-menu-section">';
        $menu .= $hiddenProfileWidgetsMenu;
        $menu .= '<li><span class="no-hidden-profile-widgets-text" '.
                 ($hiddenProfile ? 'style="display:none;"' : '').'>'.
                 Yii::t('app', 'No Hidden Widgets').
                 '</span></li>';
        $menu .= '</ul>';
        $menu .= '</div>';
        return $menu;
    }

    /**
     *  Returns an html list of hidden widgets used in the Widget Menu
     */
    public function getWidgetMenu(){
        $layout = $this->getLayout();

        /*$menu = '<ul id="widget-menu">';
        foreach($layout['hidden'] as $name => $widget){
            $menu .= '<li><span class="x2-widget-menu-item" id="'.$name.'">'.$widget['title'].'</span></li>';
        }
        if(!empty($layout['hidden']) && !empty($layout['hiddenRight'])){
            $menu .= '<li class="x2widget-menu-divider"></li>';
        }
        foreach($layout['hiddenRight'] as $name => $widget){
            $menu .= '<li><span class="x2-widget-menu-item widget-right" id="'.$name.'">'.$widget['title'].'</span></li>';
        }
        $menu .= '</ul>';*/

        // used to determine where section dividers should be placed
        $hiddenCenter = !empty ($layout['hidden']);
        $hiddenRight = !empty ($layout['hiddenRight']);

        $menu = '<div id="x2-hidden-widgets-menu">';
        $menu .= '<ul id="x2-hidden-center-widgets-menu" class="x2-hidden-widgets-menu-section">';
        foreach($layout['hidden'] as $name => $widget){
            $menu .= '<li><span class="x2-hidden-widgets-menu-item widget-center" id="'.$name.'">'.$widget['title'].'</span></li>';
        }
        $menu .= '</ul>';
        $menu .= '<ul id="x2-hidden-right-widgets-menu" class="x2-hidden-widgets-menu-section">';
        $menu .= '<li '.(($hiddenCenter && $hiddenRight) ? '' : 'style="display: none;"').
            'class="x2-hidden-widgets-menu-divider"></li>';
        foreach($layout['hiddenRight'] as $name => $widget){
            $menu .= '<li><span class="x2-hidden-widgets-menu-item widget-right" id="'.$name.'">'.$widget['title'].'</span></li>';
        }
        $menu .= '</ul>';
        $menu .= '</div>';

        return $menu;
    }

    /**
     * Saves a layout to the user's profile as a json string
     *
     * @param array $layout
     */
    public function saveLayout($layout){
        $this->layout = json_encode($layout);
        $this->update(array('layout'));
    }

    /**
     * Renders the avatar image with max dimension 95x95
     * @param int $id the profile id 
     */
    public static function renderFullSizeAvatar ($id, $dimensionLimit=95) {
        $model = Profile::model ()->findByPk ($id);
        if(isset($model->avatar) && $model->avatar!='' && file_exists($model->avatar)) {
            $imgSize = @getimagesize($model->avatar);
            if(!$imgSize)
                $imgSize = array(45,45);

            $maxDimension = max($imgSize[0],$imgSize[1]);

            $scaleFactor = 1;
            if($maxDimension > $dimensionLimit)
                $scaleFactor = $dimensionLimit / $maxDimension;

            $imgSize[0] = round($imgSize[0] * $scaleFactor);
            $imgSize[1] = round($imgSize[1] * $scaleFactor);
            echo '<img id="avatar-image" width="'.$imgSize[0].'" height="'.$imgSize[1].
                '" class="avatar-upload" '.
                'src="'.Yii::app()->request->baseUrl.'/'.$model->avatar.'" />';
        } else {
            echo '<img id="avatar-image" width="'.$dimensionLimit.'" height="'.$dimensionLimit.'" src='.
                Yii::app()->request->baseUrl."/uploads/default.png".'>';
        }
    }

    public function getLastLogin () {
        return $this->user['lastLogin'];
    }

     

    /**
     * Return theme after checking for an enforced default 
     */
    public function getTheme () {
        $admin = Yii::app()->settings;
         
        return $this->theme;
    }

    /**
     * Get the default email template for the specified module 
     * @param string $moduleName
     * @return mixed null if the module has no default template, the id of the default template
     *  otherwise
     */
    public function getDefaultEmailTemplate ($moduleName) {
        $defaultEmailTemplates = CJSON::decode ($this->defaultEmailTemplates);
        if (isset ($defaultEmailTemplates[$moduleName])) {
            return $defaultEmailTemplates[$moduleName];
        } else {
            return null;
        }
    }
}
