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
 * @package application.modules.contacts.controllers
 */
class ContactsController extends x2base {

    public $modelClass = 'Contacts';

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * No longer actually called since the permissions system changes
     * @return array access control rules
     * @deprecated
     */
    public function accessRules(){

        return array(
            array('allow',
                'actions' => array('getItems', 'getLists', 'ignoreDuplicates', 'discardNew',
                    'weblead', 'weblist'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array(
                    'index',
                    'list',
                    'lists',
                    'view',
                    'myContacts',
                    'newContacts',
                    'update',
                    'create',
                    'quickContact',
                    'import',
                    'importContacts',
                    'viewNotes',
                    'search',
                    'addNote',
                    'deleteNote',
                    'saveChanges',
                    'createAction',
                    'importExcel',
                    'export',
                    'getTerms',
                    'getContacts',
                    'delete',
                    'shareContact',
                    'viewRelationships',
                    'createList',
                    'createListFromSelection',
                    'updateList',
                    'addToList',
                    'removeFromList',
                    'deleteList',
                    'inlineEmail',
                    'quickUpdateHistory',
                    'subscribe',
                    'qtip',
                    'cleanFailedLeads',
                ),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array(
                    'admin', 'testScalability'
                ),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    /**
     * Return a list of external actions which need to be included.
     * @return array A merge of the parent class's imported actions in addition to the ones that 
     *  are specific to the Contacts controller
     */
    public function actions(){
        return array_merge(parent::actions(), array(
            'weblead' => array(
                'class' => 'WebFormAction',
            ),
            'LeadRoutingBehavior' => array(
                'class' => 'LeadRoutingBehavior'
            ),
        ));
    }

    /**
     * Return a list of external behaviors which are necessary.
     * @return array A merge of the parent class's behaviors with the ContactsController specific 
     *  ones
     */
    public function behaviors(){
        return array_merge(parent::behaviors(), array(
            'LeadRoutingBehavior' => array(
                'class' => 'LeadRoutingBehavior'
            ),
            'ImportExportBehavior' => array(
                'class' => 'ImportExportBehavior'
            ),
            'QuickCreateRelationshipBehavior' => array(
                'class' => 'QuickCreateRelationshipBehavior',
                'attributesOfNewRecordToUpdate' => array (
                    'Accounts' => array (
                        'website' => 'website',
                        'phone' => 'phone',
                    ),
                    'Opportunity' => array (
                        'accountName' => 'company',
                    )
                )
            ),
        ));
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id){
        $contact = $this->loadModel($id);
        if($this->checkPermissions($contact, 'view')){

            // Modify the time zone widget to display Contact time
            if(isset($this->portlets['TimeZone'])){
                $this->portlets['TimeZone']['params']['localTime'] = false;
                $this->portlets['TimeZone']['params']['model'] = &$contact;
            }
            // Only load the Google Maps widget if we're on a Contact with an address
            if(isset($this->portlets['GoogleMaps']))
                $this->portlets['GoogleMaps']['params']['location'] = $contact->cityAddress;

            // Update the VCR list information to preserve what list we came from
            if(isset($_COOKIE['vcr-list'])){
                Yii::app()->user->setState('vcr-list', $_COOKIE['vcr-list']);
            }
            /*
             * This block is the duplicate check code. It checks if two contacts
             * have the same first/last name or if they have the same email address
             * If that is the case, then it will render the duplicateCheck view
             * and prompt the user to take action. As a safety measure, only
             * the first five duplicates are shown unless the user explicitly
             * requests them, this is in case of a situation in which a large number
             * of duplicates are detected and rendering them all would slow down
             * the system. If a duplicate is not found, render the view file instead.
             */
            if($contact->dupeCheck != '1' && !empty($contact->firstName) && !empty($contact->lastName)){
                $criteria = new CDbCriteria();
                $criteria->compare(
                    'CONCAT(firstName," ",lastName)', $contact->firstName." ".$contact->lastName, false, "OR");

                if(!empty($contact->email))
                    $criteria->compare('email', $contact->email, false, "OR");

                $criteria->compare('id', "<>".$contact->id, false, "AND");

                if(!Yii::app()->user->checkAccess('ContactsAdminAccess')){
                    $condition = 'visibility="1" OR (assignedTo="Anyone" AND visibility!="0") '.
                        'OR assignedTo="'.Yii::app()->user->getName().'"';
                    /* x2temp */
                    $groupLinks = Yii::app()->db->createCommand()
                        ->select('groupId')
                        ->from('x2_group_to_user')
                        ->where('userId='.Yii::app()->user->getId())
                        ->queryColumn();

                    if(!empty($groupLinks))
                        $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

                    $condition .= 'OR ('.
                        'visibility=2 AND assignedTo IN ('.
                            'SELECT username '.
                            'FROM x2_group_to_user '.
                            'WHERE groupId IN ('.
                                'SELECT groupId '.
                                'FROM x2_group_to_user '.
                                'WHERE userId='.Yii::app()->user->getId().')))';

                    $criteria->addCondition($condition);
                }

                $count = X2Model::model('Contacts')->count($criteria);
                if(!isset($_GET['showAll']) || $_GET['showAll'] != 'true')
                    $criteria->limit = 5;
                $duplicates = Contacts::model()->findAll($criteria);
                if(count($duplicates) > 0){
                    $this->render('duplicateCheck', array(
                        'count' => $count,
                        'newRecord' => $contact,
                        'duplicates' => $duplicates,
                        'ref' => 'view'
                    ));
                }else{
                    $contact->dupeCheck = 1;
                    $contact->scenario = 'noChangelog';
                    $contact->update(array('dupeCheck'));
                    User::addRecentItem('c', $id, Yii::app()->user->getId()); ////add contact to user's recent item list
                    parent::view($contact, 'contacts');
                }
            }else{
                User::addRecentItem('c', $id, Yii::app()->user->getId()); ////add contact to user's recent item list
                parent::view($contact, 'contacts');
            }
        } else
            $this->redirect('index');
    }

    /**
     * This is a prototype function designed to re-build a record from the changelog.
     *
     * This method is largely a work in progress though it is functional right
     * now as is, it could just use some refactoring and improvements. On the
     * "View Changelog" page in the Admin tab there's a link on each Contact
     * changelog entry to view the record at that point in the history. Clicking
     * that link brings you here.
     * @param int $id The ID of the Contact to be viewed
     * @param int $timestamp The timestamp to view the Contact at... this should probably be refactored to changelog ID
     */
    public function actionRevisions($id, $timestamp){
        $contact = $this->loadModel($id);
        // Find all the changelog entries associated with this Contact after the given
        // timestamp. Realistically, this would be more accurate if Changelog ID
        // was used instead of the timestamp.
        $changes = X2Model::model('Changelog')->findAll('type="Contacts" AND itemId="'.$contact->id.'" AND timestamp > '.$timestamp.' ORDER BY timestamp DESC');
        // Loop through the changes and apply each one retroactively to the Contact record.
        foreach($changes as $change){
            $fieldName = $change->fieldName;
            if($contact->hasAttribute($fieldName) && $fieldName != 'id')
                $contact->$fieldName = $change->oldValue;
        }
        // Set our widget info
        if(isset($this->portlets['TimeZone']))
            $this->portlets['TimeZone']['params']['model'] = &$contact;
        if(isset($this->portlets['GoogleMaps']))
            $this->portlets['GoogleMaps']['params']['location'] = $contact->cityAddress;

        if($this->checkPermissions($contact, 'view')){

            if(isset($_COOKIE['vcr-list']))
                Yii::app()->user->setState('vcr-list', $_COOKIE['vcr-list']);

            User::addRecentItem('c', $id, Yii::app()->user->getId()); ////add contact to user's recent item list
            // View the Contact with the data modified to this point
            parent::view($contact, 'contacts');
        } else
            $this->redirect('index');
    }

    /**
     * Displays the a model's relationships with other models.
     * This has been largely replaced with the relationships widget.
     * @param type $id The id of the model to display relationships of
     * @deprecated
     */
    public function actionViewRelationships($id){
        $model = $this->loadModel($id);
        $dataProvider = new CActiveDataProvider('Relationships', array(
                    'criteria' => array(
                        'condition' => '(firstType="Contacts" AND firstId="'.$id.'") OR (secondType="Contacts" AND secondId="'.$id.'")',
                    )
                ));
        $this->render('viewOpportunities', array(
            'dataProvider' => $dataProvider,
            'model' => $model,
        ));
    }

    /**
     * Used for accounts auto-complete method.  May be obsolete.
     */
    public function actionGetTerms(){
        $sql = 'SELECT id, name as value FROM x2_accounts WHERE name LIKE :qterm ORDER BY name ASC';
        $command = Yii::app()->db->createCommand($sql);
        $qterm = $_GET['term'].'%';
        $command->bindParam(":qterm", $qterm, PDO::PARAM_STR);
        $result = $command->queryAll();
        echo CJSON::encode($result);
        exit;
    }

    /**
     * Used for auto-complete methods.  This method is likely obsolete.
     */
    public function actionGetContacts(){
        $sql = 'SELECT id, CONCAT(firstName," ",lastName) as value FROM x2_contacts WHERE firstName LIKE :qterm OR lastName LIKE :qterm OR CONCAT(firstName," ",lastName) LIKE :qterm ORDER BY firstName ASC';
        $command = Yii::app()->db->createCommand($sql);
        $qterm = $_GET['term'].'%';
        $command->bindParam(":qterm", $qterm, PDO::PARAM_STR);
        $result = $command->queryAll();
        echo CJSON::encode($result);
        exit;
    }

    /**
     *  Used for auto-complete methods.  This method is likely obsolete.
     */
    public function actionGetItems(){
        $model = new Contacts('search');
        $visCriteria = $model->getAccessCriteria();
        $sql = 
            'SELECT id, city, state, country, email, 
                IF(assignedTo > 0, (SELECT name FROM x2_groups WHERE id=assignedTo), 
                   (SELECT fullname from x2_profile WHERE username=assignedTo)) 
            as assignedTo, CONCAT(firstName," ",lastName) as value 
            FROM x2_contacts t 
            WHERE (firstName LIKE :qterm OR lastName LIKE :qterm OR 
                CONCAT(firstName," ",lastName) LIKE :qterm) AND ('.$visCriteria->condition.') 
            ORDER BY firstName ASC';
        $command = Yii::app()->db->createCommand($sql);
        $qterm = $_GET['term'].'%';
        $command->bindParam(":qterm", $qterm, PDO::PARAM_STR);
        $result = $command->queryAll();
        echo CJSON::encode($result);
        exit;
    }

    /**
     * Return a JSON encoded list of Contact lists
     */
    public function actionGetLists(){
        if(!Yii::app()->user->checkAccess('ContactsAdminAccess')){
            $condition = ' AND (visibility="1" OR assignedTo="Anyone"  OR assignedTo="'.Yii::app()->user->getName().'"';
            /* x2temp */
            $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
            if(!empty($groupLinks))
                $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

            $condition .= ' OR (visibility=2 AND assignedTo IN
                (SELECT username FROM x2_group_to_user WHERE groupId IN
                (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().'))))';
        } else{
            $condition = '';
        }
        // Optional search parameter for autocomplete
        $qterm = isset($_GET['term']) ? $_GET['term'].'%' : '';
        $result = Yii::app()->db->createCommand()
                ->select('id,name as value')
                ->from('x2_lists')
                ->where('modelName="Contacts" AND type!="campaign" AND name LIKE :qterm'.$condition, array(':qterm' => $qterm))
                ->order('name ASC')
                ->queryAll();
        echo CJSON::encode($result);
    }

    /**
     * Synchronize a Contact record with its related Account.
     * This function will load the linked Account record from the company field
     * and overwrite any shared fields with the Account's version of that field.
     * @param int $id The ID of the Contact
     */
    public function actionSyncAccount($id){
        $contact = $this->loadModel($id);
        if($contact->hasAttribute('company') && is_numeric($contact->company)){
            $account = X2Model::model('Accounts')->findByPk($contact->company);
            if(isset($account)){
                foreach($account->attributes as $key => $value){
                    // Don't change ID or any of the date fields.
                    if($contact->hasAttribute($key) && $key != 'id' && $key != 'createDate' && $key != 'lastUpdated' && $key != 'lastActivity'){
                        $contact->$key = $value;
                    }
                }
            }
        }
        $contact->save();
        $this->redirect(array('view', 'id' => $id));
    }

    /**
     * Loads a Google Maps interface with Contact location data plotted on it
     * This will generate a Google Map frame on a page with several possible
     * additional features. By default it provides a heat map of contact location
     * data. However, if a Contact ID is also provided, it will center the map
     * on that Contact's location and place a marker there. Filtering based on
     * tags or assignment is also possible with the $params array
     * @param int $contactId The ID of a Contact to center the map on
     * @param array $params Additional filter parameters to limit the visible dataset
     * @param int $loadMap The ID of a saved map to re-load previously saved settings
     */
    public function actionGoogleMaps($contactId = null, $params = array(), $loadMap = null){
        if(isset($_POST['contactId']))
            $contactId = $_POST['contactId'];
        if(isset($_POST['params'])){
            $params = $_POST['params'];
        }
        if(!empty($loadMap)){ // If we have a map ID, duplicate whatever information was saved there
            $map = Maps::model()->findByPk($loadMap);
            if(isset($map)){
                $contactId = $map->contactId;
                $params = json_decode($map->params, true);
            }
        }
        $conditions = "TRUE";
        $parameters = array();
        $tagCount = 0;
        $tagFlag = false;
        $contactFields = array_flip (Contacts::model()->attributeNames());

        // Loop through params and add conditions to limit the contact data set
        foreach($params as $field => $value){
            if ($field != 'tags' && !isset ($contactFields[$field])) continue; // prevents SQL injection by verifying field name
            if($field != 'tags' && $value != ''){
                $conditions.=" AND x2_contacts.$field=:$field";
                $parameters[":$field"] = $value;
            }elseif($value != ''){
                $tagFlag = true;
                if(!is_array($value)){
                    $value = explode(",", $value);
                }
                $tagCount = count($value);
                $tagStr = "(";
                for($i = 0; $i < count($value); $i++){
                    $tagStr.=':tag'.$i.', ';
                    $parameters[":tag$i"] = $value[$i];
                }
                $tagStr = substr($tagStr, 0, strlen($tagStr) - 2).")";
                $conditions.=" AND x2_tags.type='Contacts' AND x2_tags.tag IN $tagStr";
            }
        }
        /*
         * These two CDbCommands generate the query to grab all the location lat
         * and lon data to be used on the map. If tags are being filtered on,
         * we need a double join to grab all the requisite data, otherwise we
         * only need to join x2_contacts to x2_locations
         */
        if($tagFlag){
            $locations = Yii::app()->db->createCommand()
                    ->select('x2_locations.*')
                    ->from('x2_locations')
                    ->join('x2_contacts', 'x2_contacts.id=x2_locations.contactId')
                    ->join('x2_tags', 'x2_tags.itemId=x2_locations.contactId')
                    ->where($conditions, $parameters)
                    ->group('x2_tags.itemId')
                    ->having('COUNT(x2_tags.itemId)>='.$tagCount)
                    ->queryAll();
        }else{
            $locations = Yii::app()->db->createCommand()
                    ->select('x2_locations.*')
                    ->from('x2_locations')
                    ->join('x2_contacts', 'x2_contacts.id=x2_locations.contactId')
                    ->where($conditions, $parameters)
                    ->queryAll();
        }
        $locationCodes = array();
        // Loop through the SQL result and convert the data to an array that Google can read
        foreach($locations as $location){
            if(isset($location['lat']) && isset($location['lon'])){
                $tempArr['lat'] = $location['lat'];
                $tempArr['lng'] = $location['lon'];
                $locationCodes[] = $tempArr;
            }
        }
        /*
         * $locationCodes[0] is the first location on the map and where the map
         * will be centered. If we have a Contact ID, center it on that contact's
         * location. Otherwise center it on the first location in the set
         */
        if(isset($contactId)){
            $location = X2Model::model('Locations')->findByAttributes(array('contactId' => $contactId));
            if(isset($location)){
                $loc = array("lat" => $location->lat, "lng" => $location->lon);
                $markerLoc = array("lat" => $location->lat, "lng" => $location->lon);
                $markerFlag = true;
            }elseif(count($locationCodes) > 0){
                $loc = $locationCodes[0];
                $markerFlag = "false";
            }else{
                $loc = array('lat' => 0, 'lng' => 0);
                $markerFlag = "false";
            }
        }else{
            if(isset($locationCodes[0])){
                $loc = $locationCodes[0];
            }else{
                $loc = array('lat' => 0, 'lng' => 0);
            }
            $markerFlag = "false";
        }
        // If we already have a map, use the previous center & zoom settings
        if(isset($map)){
            $loc['lat'] = $map->centerLat;
            $loc['lng'] = $map->centerLng;
            $zoom = $map->zoom;
        }
        /*
         * This view file is actually really complicated as it uses a lot of
         * Google's JS files to render the map.
         */
        $this->render('googleEarth', array(
            'locations' => json_encode($locationCodes),
            'center' => json_encode($loc),
            'markerLoc' => isset($markerLoc) ? json_encode($markerLoc) : json_encode($loc),
            'markerFlag' => $markerFlag,
            'contactId' => isset($contactId) ? $contactId : 0,
            'assignment' => isset($_POST['params']['assignedTo']) || isset($params['assignedTo']) ? (isset($_POST['params']['assignedTo']) ? $_POST['params']['assignedTo'] : $params['assignedTo']) : '',
            'leadSource' => isset($_POST['params']['leadSource']) ? $_POST['params']['leadSource'] : '',
            'tags' => ((isset($_POST['params']['tags']) && !empty($_POST['params']['tags'])) ? Tags::parseTags($_POST['params']['tags']) : array()),
            'zoom' => isset($zoom) ? $zoom : null,
            'mapFlag' => isset($map) ? 'true' : 'false',
            'noHeatMap' => isset($_GET['noHeatMap']) && $_GET['noHeatMap'] ? true : false,
        ));
    }

    /**
     * An AJAX called function to save map settings.
     */
    public function actionSaveMap(){
        if(isset($_POST['centerLat']) && isset($_POST['centerLng']) && isset($_POST['mapName'])){
            $zoom = $_POST['zoom'];
            $centerLat = $_POST['centerLat'];
            $centerLng = $_POST['centerLng'];
            $contactId = isset($_POST['contactId']) ? $_POST['contactId'] : '';
            $params = isset($_POST['parameters']) ? $_POST['parameters'] : array();
            $mapName = $_POST['mapName'];

            $map = new Maps;
            $map->name = $mapName;
            $map->owner = Yii::app()->user->getName();
            $map->contactId = $contactId;
            $map->zoom = $zoom;
            $map->centerLat = $centerLat;
            $map->centerLng = $centerLng;
            $map->params = json_encode($params);
            if($map->save()){

            }else{

            }
        }
    }

    /**
     * Display an index of saved maps.
     */
    public function actionSavedMaps(){
        if(Yii::app()->user->checkAccess('ContactsAdmin')){
            $dataProvider = new CActiveDataProvider('Maps');
        }else{
            $dataProvider = new CActiveDataProvider('Maps', array(
                        'criteria' => array(
                            'condition' => 'owner="'.Yii::app()->user->getName().'"',
                        )
                    ));
        }
        $this->render('savedMaps', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Delete a saved map
     * @param int $id ID of the map to delete
     */
    public function actionDeleteMap($id){
        $map = Maps::model()->findByPk($id);
        if(isset($map) && ($map->owner == Yii::app()->user->getName() || Yii::app()->user->checkAccess('ContactsAdmin')) && Yii::app()->request->isPostRequest){
            $map->delete();
        }
        $this->redirect('savedMaps');
    }

    /**
     * An AJAX called function to update the location of a Contact record
     * @param int $contactId The ID of the contact
     * @param float $lat The lattitutde of the location
     * @param float $lon The longitude of the location
     */
    public function actionUpdateLocation($contactId, $lat, $lon){
        $location = Locations::model()->findByAttributes(array('contactId' => $contactId));
        if(!isset($location)){
            $location = new Locations;
            $location->contactId = $contactId;
            $location->lat = $lat;
            $location->lon = $lon;
            $location->save();
        }else{
            if($location->lat != $lat || $location->lon != $lon){
                $location->lat = $lat;
                $location->lon = $lon;
                $location->save();
            }
        }
    }

    /**
     * Generates an email template to share Contact data
     * @param int $id The ID of the Contact
     */
    public function actionShareContact($id){
        $users = User::getNames();
        $model = $this->loadModel($id);
        $body = "\n\n\n\n".Yii::t('contacts', 'Contact Record Details')." <br />
<br />".Yii::t('contacts', 'Name').": $model->firstName $model->lastName
<br />".Yii::t('contacts', 'E-Mail').": $model->email
<br />".Yii::t('contacts', 'Phone').": $model->phone
<br />".Yii::t('contacts', 'Account').": $model->company
<br />".Yii::t('contacts', 'Address').": $model->address
<br />$model->city, $model->state $model->zipcode
<br />".Yii::t('contacts', 'Background Info').": $model->backgroundInfo
<br />".Yii::t('app', 'Link').": ".CHtml::link($model->name, $this->createAbsoluteUrl('/contacts/contacts/view',array('id'=>$model->id)));

        $body = trim($body);

        $errors = array();
        $status = array();
        $email = array();
        if(isset($_POST['email'], $_POST['body'])){

            $subject = Yii::t('contacts', 'Contact Record Details');
            $email['to'] = $this->parseEmailTo($this->decodeQuotes($_POST['email']));
            $body = $_POST['body'];
            // if(empty($email) || !preg_match("/[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/",$email))
            if($email['to'] === false)
                $errors[] = 'email';
            if(empty($body))
                $errors[] = 'body';

            $emailFrom = Credentials::model()->getDefaultUserAccount(Credentials::$sysUseId['systemNotificationEmail'], 'email');
            if($emailFrom == Credentials::LEGACY_ID)
                $emailFrom = array(
                    'name' => Yii::app()->params->profile->fullName,
                    'address' => Yii::app()->params->profile->emailAddress
                );

            if(empty($errors))
                $status = $this->sendUserEmail($email, $subject, $body, null, $emailFrom);

            if(array_search('200', $status)){
                $this->redirect(array('view', 'id' => $model->id));
                return;
            }
            if($email['to'] === false)
                $email = $_POST['email'];
            else
                $email = $this->mailingListToString($email['to']);
        }
        $this->render('shareContact', array(
            'model' => $model,
            'users' => $users,
            'body' => $body,
            'currentWorkflow' => $this->getCurrentWorkflow($model->id, 'contacts'),
            'email' => $email,
            'status' => $status,
            'errors' => $errors
        ));
    }

    /**
     * Called by the duplicate checker to keep the current record
     */
    public function actionIgnoreDuplicates(){
        if(isset($_POST['data'])){

            $arr = json_decode($_POST['data'], true);
            if($_POST['ref'] != 'view'){
                if($_POST['ref'] == 'create')
                    $model = new Contacts;
                else{
                    $id = $arr['id'];
                    $model = Contacts::model()->findByPk($id);
                }
                $temp = $model->attributes;
                foreach($arr as $key => $value){
                    $model->$key = $value;
                }
            }else{
                $id = $arr['id'];
                $model = X2Model::model('Contacts')->findByPk($id);
            }
            $model->dupeCheck = 1;
            $model->disableBehavior('X2TimestampBehavior');
            if($model->save()){

            }
            // Optional parameter to determine what other steps to take, default null
            $action = $_POST['action'];
            if(!is_null($action)){
                $criteria = new CDbCriteria();
                if(!empty($model->firstName) && !empty($model->lastName))
                    $criteria->compare('CONCAT(firstName," ",lastName)', $model->firstName." ".$model->lastName, false, "OR");
                if(!empty($model->email))
                    $criteria->compare('email', $model->email, false, "OR");
                $criteria->compare('id', "<>".$model->id, false, "AND");
                if(!Yii::app()->user->checkAccess('ContactsAdminAccess')){
                    $condition = 'visibility="1" OR (assignedTo="Anyone" AND visibility!="0")  OR assignedTo="'.Yii::app()->user->getName().'"';
                    /* x2temp */
                    $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
                    if(!empty($groupLinks))
                        $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

                    $condition .= 'OR (visibility=2 AND assignedTo IN
                        (SELECT username FROM x2_group_to_user WHERE groupId IN
                            (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().')))';
                    $criteria->addCondition($condition);
                }
                // If the action was hide all, hide all the other records.
                if($action == 'hideAll'){
                    $duplicates = Contacts::model()->findAll($criteria);
                    foreach($duplicates as $duplicate){
                        $duplicate->dupeCheck = 1;
                        $duplicate->assignedTo = 'Anyone';
                        $duplicate->visibility = 0;
                        $duplicate->doNotCall = 1;
                        $duplicate->doNotEmail = 1;
                        $duplicate->save();
                        $notif = new Notification;
                        $notif->user = 'admin';
                        $notif->createdBy = Yii::app()->user->getName();
                        $notif->createDate = time();
                        $notif->type = 'dup_discard';
                        $notif->modelType = 'Contacts';
                        $notif->modelId = $duplicate->id;
                        $notif->save();
                    }
                // If it was delete all...
                }elseif($action == 'deleteAll'){
                    Contacts::model()->deleteAll($criteria);
                }
            }
            echo $model->id;
        }
    }

    /**
     * Called by the duplicate checker when discarding the new record.
     */
    public function actionDiscardNew(){

        if(isset($_POST['id'])){
            $ref = $_POST['ref']; // Referring action
            $action = $_POST['action'];
            $oldId = $_POST['id'];
            if($ref == 'create' && is_null($action) || $action == 'null'){
                echo $oldId;
                return;
            }elseif($ref == 'create'){
                $oldRecord = X2Model::model('Contacts')->findByPk($oldId);
                if(isset($oldRecord)){
                    $oldRecord->disableBehavior('X2TimestampBehavior');
                    Relationships::model()->deleteAllByAttributes(array('firstType' => 'Contacts', 'firstId' => $oldRecord->id));
                    Relationships::model()->deleteAllByAttributes(array('secondType' => 'Contacts', 'secondId' => $oldRecord->id));
                    if($action == 'hideThis'){
                        $oldRecord->dupeCheck = 1;
                        $oldRecord->assignedTo = 'Anyone';
                        $oldRecord->visibility = 0;
                        $oldRecord->doNotCall = 1;
                        $oldRecord->doNotEmail = 1;
                        $oldRecord->save();
                        $notif = new Notification;
                        $notif->user = 'admin';
                        $notif->createdBy = Yii::app()->user->getName();
                        $notif->createDate = time();
                        $notif->type = 'dup_discard';
                        $notif->modelType = 'Contacts';
                        $notif->modelId = $oldId;
                        $notif->save();
                        echo $_POST['id'];
                        return;
                    }elseif($action == 'deleteThis'){
                        $oldRecord->delete();
                        echo $_POST['id'];
                        return;
                    }
                }
            }elseif(isset($_POST['newId'])){
                $newId = $_POST['newId'];
                $oldRecord = X2Model::model('Contacts')->findByPk($oldId);
                $oldRecord->disableBehavior('X2TimestampBehavior');
                $newRecord = Contacts::model()->findByPk($newId);
                $newRecord->disableBehavior('X2TimestampBehavior');
                $newRecord->dupeCheck = 1;
                $newRecord->save();
                if($action === ''){
                    $newRecord->delete();
                    echo $oldId;
                    return;
                }else{
                    if(isset($oldRecord)){

                        if($action == 'hideThis'){
                            $oldRecord->dupeCheck = 1;
                            $oldRecord->assignedTo = 'Anyone';
                            $oldRecord->visibility = 0;
                            $oldRecord->doNotCall = 1;
                            $oldRecord->doNotEmail = 1;
                            $oldRecord->save();
                            $notif = new Notification;
                            $notif->user = 'admin';
                            $notif->createdBy = Yii::app()->user->getName();
                            $notif->createDate = time();
                            $notif->type = 'dup_discard';
                            $notif->modelType = 'Contacts';
                            $notif->modelId = $oldId;
                            $notif->save();
                        }elseif($action == 'deleteThis'){
                            Relationships::model()->deleteAllByAttributes(array('firstType' => 'Contacts', 'firstId' => $oldRecord->id));
                            Relationships::model()->deleteAllByAttributes(array('secondType' => 'Contacts', 'secondId' => $oldRecord->id));
                            Tags::model()->deleteAllByAttributes(array('type' => 'Contacts', 'itemId' => $oldRecord->id));
                            Actions::model()->deleteAllByAttributes(array('associationType' => 'Contacts', 'associationId' => $oldRecord->id));
                            $oldRecord->delete();
                        }
                    }

                    echo $newId;
                }
            }
        }
    }

    /**
     * Creates a new Contact record
     */
    public function actionCreate(){
        $model = new Contacts;
        $name = 'Contacts';
        $renderFlag = true;
        $users = User::getNames();

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if(isset($_POST['Contacts'])){
            $oldAttributes = $model->attributes;
            $model->setX2Fields($_POST['Contacts']);

            $criteria = new CDbCriteria();
            if(!empty($model->firstName) && !empty($model->lastName)){
                $criteria->compare(
                    'CONCAT(firstName," ",lastName)', $model->firstName." ".$model->lastName, 
                    false, "OR");
            }
            if(!empty($model->email)){
                $criteria->compare('email', $model->email, false, "OR");
            }
            if(isset($_POST['x2ajax'])){
                $ajaxErrors = $this->quickCreate ($model);
            }else{
                if(!empty($criteria->condition)){
                    if(!Yii::app()->user->checkAccess('ContactsAdminAccess')){
                        $condition = 
                            'visibility="1" OR (assignedTo="Anyone" AND visibility!="0")
                                OR assignedTo="'.Yii::app()->user->getName().'"';
                        /* x2temp */
                        $groupLinks = Yii::app()->db->createCommand()
                            ->select('groupId')
                            ->from('x2_group_to_user')
                            ->where('userId='.Yii::app()->user->getId())->queryColumn();
                        if(!empty($groupLinks))
                            $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

                        $condition .= 
                            'OR (visibility=2 AND assignedTo IN (
                                SELECT username 
                                FROM x2_group_to_user 
                                WHERE groupId IN (
                                    SELECT groupId 
                                    FROM x2_group_to_user 
                                    WHERE userId='.Yii::app()->user->getId().')))';

                        $criteria->addCondition($condition);
                    }

                    $count = X2Model::model('Contacts')->count($criteria);
                    if(!isset($_GET['viewAll']) || $_GET['viewAll'] != 'true')
                        $criteria->limit = 5;
                    $duplicates = X2Model::model('Contacts')->findAll($criteria);
                    if(count($duplicates) > 0){
                        $this->render('duplicateCheck', array(
                            'count' => $count,
                            'newRecord' => $model,
                            'duplicates' => $duplicates,
                            'ref' => 'create'
                        ));
                        $renderFlag = false;
                    }else{
                        if($model->save())
                            $this->redirect(array('view', 'id' => $model->id));
                    }
                } else{
                    if($model->save())
                        $this->redirect(array('view', 'id' => $model->id));
                }
            }
        }

        if($renderFlag){
            if(isset($_POST['x2ajax'])){
                $this->renderInlineCreateForm ($model, isset ($ajaxErrors) ? $ajaxErrors : false);
            } else {
                $this->render('create', array(
                    'model' => $model,
                    'users' => $users,
                ));
            }
        }
    }

    /**
     * Method of creating a Contact called by the Quick Create widget
     */
    public function actionQuickContact(){

        $model = new Contacts;
        // collect user input data
        if(isset($_POST['Contacts'])){
            // clear values that haven't been changed from the default
            //$temp=$model->attributes;
            $model->setX2Fields($_POST['Contacts']);

            $model->visibility = 1;
            // validate user input and save contact
            // $changes = $this->calculateChanges($temp, $model->attributes, $model);
            // $model = $this->updateChangelog($model, $changes);
            $model->createDate = time();
            //if($model->validate()) {
            if($model->save()){

            }else{
                //echo CHtml::errorSummary ($model);
                echo CJSON::encode($model->getErrors());
            }
            return;
            //}
            //echo '';
            //echo CJSON::encode($model->getErrors());
        }
        $this->renderPartial('application.components.views.quickContact', array(
            'model' => $model
        ));
    }

    // Controller/action wrapper for update()
    public function actionUpdate($id){
        $model = $this->loadModel($id);
        $users = User::getNames();
        $renderFlag = true;

        if(isset($_POST['Contacts'])){
            $oldAttributes = $model->attributes;

            //AuxLib::debugLogR ($_POST);
            $model->setX2Fields($_POST['Contacts']);
            if($model->dupeCheck != '1'){
                $model->dupeCheck = 1;
                $criteria = new CDbCriteria();
                $criteriaFlag = false;
                if(!empty($model->firstName) && !empty($model->lastName)){
                    $criteria->compare('CONCAT(firstName," ",lastName)', $model->firstName." ".$model->lastName, false, "OR");
                    $criteriaFlag = true;
                }
                if(!empty($model->email)){
                    $criteria->compare('email', $model->email, false, "OR");
                    $criteriaFlag = true;
                }
                $criteria->compare('id', "<>".$model->id, false, "AND");
                if(!Yii::app()->user->checkAccess('ContactsAdminAccess')){
                    $condition = 'visibility="1" OR (assignedTo="Anyone" AND visibility!="0")  OR assignedTo="'.Yii::app()->user->getName().'"';
                    /* x2temp */
                    $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
                    if(!empty($groupLinks))
                        $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

                    $condition .= 'OR (visibility=2 AND assignedTo IN
                        (SELECT username FROM x2_group_to_user WHERE groupId IN
                            (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().')))';
                    $criteria->addCondition($condition);
                }
                $count = X2Model::model('Contacts')->count($criteria);
                if(!empty($criteria) && $criteriaFlag){
                    $duplicates = X2Model::model('Contacts')->findAll($criteria);
                    if(count($duplicates) > 0){
                        $this->render('duplicateCheck', array(
                            'newRecord' => $model,
                            'duplicates' => $duplicates,
                            'ref' => 'update',
                            'count' => $count,
                        ));
                        $renderFlag = false;
                    }else{
                        // $this->update($model, $oldAttributes, 0);
                        if($model->save())
                            $this->redirect(array('view', 'id' => $model->id));
                    }
                } else{
                    // $this->update($model, $oldAttributes, 0);
                    if($model->save())
                        $this->redirect(array('view', 'id' => $model->id));
                }
            } else{
                // $this->update($model, $oldAttributes, 0);
                if($model->save())
                    $this->redirect(array('view', 'id' => $model->id));
            }
        }
        if($renderFlag){

            if(isset($_POST['x2ajax'])){
                Yii::app()->clientScript->scriptMap['*.js'] = false;
                Yii::app()->clientScript->scriptMap['*.css'] = false;
                if(isset($x2ajaxCreateError) && $x2ajaxCreateError == true){
                    $page = $this->renderPartial('application.components.views._form', array('model' => $model, 'users' => $users, 'modelName' => 'contacts'), true, true);
                    echo json_encode(
                            array(
                                'status' => 'userError',
                                'page' => $page,
                            )
                    );
                }else{
                    $this->renderPartial('application.components.views._form', array('model' => $model, 'users' => $users, 'modelName' => 'contacts'), false, true);
                }
            }else{
                $this->render('update', array(
                    'model' => $model,
                    'users' => $users,
                ));
            }
        }
    }

    // Displays all visible Contact Lists
    public function actionLists(){
        $criteria = new CDbCriteria();
        $criteria->addCondition('type="static" OR type="dynamic"');
        if(!Yii::app()->params->isAdmin){
            $condition = 'visibility="1" OR assignedTo="Anyone"  OR assignedTo="'.Yii::app()->user->getName().'"';
            /* x2temp */
            $groupLinks = Yii::app()->db->createCommand()->select('groupId')->from('x2_group_to_user')->where('userId='.Yii::app()->user->getId())->queryColumn();
            if(!empty($groupLinks))
                $condition .= ' OR assignedTo IN ('.implode(',', $groupLinks).')';

            $condition .= 'OR (visibility=2 AND assignedTo IN
                (SELECT username FROM x2_group_to_user WHERE groupId IN
                    (SELECT groupId FROM x2_group_to_user WHERE userId='.Yii::app()->user->getId().')))';
            $criteria->addCondition($condition);
        }

        $perPage = Profile::getResultsPerPage();

        //$criteria->offset = isset($_GET['page']) ? $_GET['page'] * $perPage - 3 : -3;
        //$criteria->limit = $perPage;
        $criteria->order = 'createDate DESC';

        $contactLists = X2Model::model('X2List')->findAll($criteria);

        $totalContacts = X2Model::model('Contacts')->count();
        $totalMyContacts = X2Model::model('Contacts')->count('assignedTo="'.Yii::app()->user->getName().'"');
        $totalNewContacts = X2Model::model('Contacts')->count('assignedTo="'.Yii::app()->user->getName().'" AND createDate >= '.mktime(0, 0, 0));

        $allContacts = new X2List;
        $allContacts->attributes = array(
            'id' => 'all',
            'name' => Yii::t('contacts', 'All Contacts'),
            'description' => '',
            'type' => 'dynamic',
            'visibility' => 1,
            'count' => $totalContacts,
            'createDate' => 0,
            'lastUpdated' => 0,
        );
        $newContacts = new X2List;
        $newContacts->attributes = array(
            'id' => 'new',
            'assignedTo' => Yii::app()->user->getName(),
            'name' => Yii::t('contacts', 'New Contacts'),
            'description' => '',
            'type' => 'dynamic',
            'visibility' => 1,
            'count' => $totalNewContacts,
            'createDate' => 0,
            'lastUpdated' => 0,
        );
        $myContacts = new X2List;
        $myContacts->attributes = array(
            'id' => 'my',
            'assignedTo' => Yii::app()->user->getName(),
            'name' => Yii::t('contacts', 'My Contacts'),
            'description' => '',
            'type' => 'dynamic',
            'visibility' => 1,
            'count' => $totalMyContacts,
            'createDate' => 0,
            'lastUpdated' => 0,
        );
        $contactListData = array(
            $allContacts,
            $myContacts,
            $newContacts,
        );

        $dataProvider = new CArrayDataProvider(array_merge($contactListData, $contactLists), array(
                    'pagination' => array('pageSize' => $perPage),
                    'totalItemCount' => count($contactLists) + 3,
                ));

        $this->render('listIndex', array(
            'contactLists' => $dataProvider,
        ));
    }

    // Lists all contacts assigned to this user
    public function actionMyContacts(){
        $model = new Contacts('search');
        Yii::app()->user->setState('vcr-list', 'myContacts');
        $this->render('index', array('model' => $model));
    }

    // Lists all contacts assigned to this user
    public function actionNewContacts(){
        $model = new Contacts('search');
        Yii::app()->user->setState('vcr-list', 'newContacts');
        $this->render('index', array('model' => $model));
    }

    // Lists all visible contacts
    public function actionIndex(){
        $model = new Contacts('search');

        Yii::app()->user->setState('vcr-list', 'index');
        $this->render('index', array('model' => $model));
    }

    // Shows contacts in the specified list
    public function actionList($id = null){
        $list = X2List::load($id);

        if(!isset($list)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('lists'));
        }

        $model = new Contacts('search');
        Yii::app()->user->setState('vcr-list', $id);
        $dataProvider = $model->searchList($id);
        $list->count = $dataProvider->totalItemCount;
        $list->save();

        X2Flow::trigger('RecordViewTrigger',array('model'=>$list));
        $this->render('list', array(
            'listModel' => $list,
            'dataProvider' => $dataProvider,
            'model' => $model,
        ));
    }

    public function actionCreateList(){
        $list = new X2List;
        $list->modelName = 'Contacts';
        $list->type = 'dynamic';
        $list->assignedTo = Yii::app()->user->getName();
        $list->visibility = 1;

        $contactModel = new Contacts;
        $comparisonList = X2List::getComparisonList();
        if(isset($_POST['X2List'])){

            $list->attributes = $_POST['X2List'];
            $list->modelName = 'Contacts';
            $list->createDate = time();
            $list->lastUpdated = time();

            if(isset($_POST['X2List'], $_POST['X2List']['attribute'], $_POST['X2List']['comparison'], $_POST['X2List']['value'])){

                $attributes = &$_POST['X2List']['attribute'];
                $comparisons = &$_POST['X2List']['comparison'];
                $values = &$_POST['X2List']['value'];

                if(count($attributes) > 0 && count($attributes) == count($comparisons) && count($comparisons) == count($values)){

                    $list->attributes = $_POST['X2List'];
                    $list->modelName = 'Contacts';

                    $list->lastUpdated = time();

                    if($list->save()){
                        $this->redirect(array('/contacts/contacts/list','id'=>$list->id));
                    }
                }
            }
        }

        if(empty($criteriaModels)){
            $default = new X2ListCriterion;
            $default->value = '';
            $default->attribute = '';
            $default->comparison = 'contains';
            $criteriaModels[] = $default;
        }

        $this->render('createList', array(
            'model' => $list,
            'criteriaModels' => $criteriaModels,
            'users' => User::getNames(),
            // 'attributeList'=>$attributeList,
            'comparisonList' => $comparisonList,
            'listTypes' => array(
                'dynamic' => Yii::t('contacts', 'Dynamic'),
                'static' => Yii::t('contacts', 'Static')
            ),
            'itemModel' => $contactModel,
        ));
    }

    public function actionUpdateList($id){
        $list = X2List::model()->findByPk($id);

        if(!isset($list))
            throw new CHttpException(400, Yii::t('app', 'This list cannot be found.'));

        if(!$this->checkPermissions($list, 'edit'))
            throw new CHttpException(403, Yii::t('app', 'You do not have permission to modify this list.'));

        $contactModel = new Contacts;
        $comparisonList = X2List::getComparisonList();
        $fields = $contactModel->getFields(true);

        if($list->type == 'dynamic'){
            $criteriaModels = X2ListCriterion::model()->findAllByAttributes(array('listId' => $list->id), new CDbCriteria(array('order' => 'id ASC')));

            if(isset($_POST['X2List'], $_POST['X2List']['attribute'], $_POST['X2List']['comparison'], $_POST['X2List']['value'])){

                $attributes = &$_POST['X2List']['attribute'];
                $comparisons = &$_POST['X2List']['comparison'];
                $values = &$_POST['X2List']['value'];

                if(count($attributes) > 0 && count($attributes) == count($comparisons) && count($comparisons) == count($values)){

                    $list->attributes = $_POST['X2List'];
                    $list->modelName = 'Contacts';
                    $list->lastUpdated = time();

                    if($list->save()){
                        $this->redirect(array('/contacts/contacts/list','id'=>$list->id));
                    }
                }
            }
        } else{ //static or campaign lists
            if(isset($_POST['X2List'])){
                $list->attributes = $_POST['X2List'];
                $list->modelName = 'Contacts';
                $list->lastUpdated = time();
                $list->save();
                $this->redirect(array('/contacts/contacts/list','id'=>$list->id));
            }
        }

        if(empty($criteriaModels)){
            $default = new X2ListCriterion;
            $default->value = '';
            $default->attribute = '';
            $default->comparison = 'contains';
            $criteriaModels[] = $default;
        } else {
            if($list->type = 'dynamic'){
                foreach($criteriaModels as $criM){
                    if($fields[$criM->attribute]->type == 'link'){
                        $criM->value = implode(',', array_map(function($c){
                                    list($name,$id) = Fields::nameAndId($c);
                                    return $name;
                                }, explode(',', $criM->value)
                                )
                        );
                    }
                }
            }
        }

        $this->render('updateList', array(
            'model' => $list,
            'criteriaModels' => $criteriaModels,
            'users' => User::getNames(),
            // 'attributeList'=>$attributeList,
            'comparisonList' => $comparisonList,
            'listTypes' => array(
                'dynamic' => Yii::t('contacts', 'Dynamic'),
                'static' => Yii::t('contacts', 'Static')
            ),
            'itemModel' => $contactModel,
        ));
    }

    // Yii::app()->db->createCommand()->select('id')->from($tableName)->where(array('like','name',"%$value%"))->queryColumn();
    public function actionRemoveFromList(){

        if(isset($_POST['gvSelection'], $_POST['listId']) && !empty($_POST['gvSelection']) && is_array($_POST['gvSelection'])){

            foreach($_POST['gvSelection'] as $contactId)
                if(!ctype_digit((string) $contactId))
                    throw new CHttpException(400, Yii::t('app', 'Invalid selection.'));

            $list = CActiveRecord::model('X2List')->findByPk($_POST['listId']);

            // check permissions
            if($list !== null && $this->checkPermissions($list, 'edit'))
                $list->removeIds($_POST['gvSelection']);

            echo 'success';
        }
    }

    public function actionDeleteList(){

        $id = isset($_GET['id']) ? $_GET['id'] : 'all';

        if(is_numeric($id))
            $list = X2Model::model('X2List')->findByPk($id);
        if(isset($list)){

            // check permissions
            if($this->checkPermissions($list, 'edit'))
                $list->delete();
            else
                throw new CHttpException(403, Yii::t('app', 'You do not have permission to modify this list.'));
        }
        $this->redirect(array('/contacts/contacts/lists'));
    }

    /**
     * Contacts export function which generates human friendly data and also
     * works for exporting particular lists of Contacts
     * @param int $listId The ID of the list to be exported, if null it will be all Contacts
     */
    public function actionExportContacts($listId = null){
        unset($_SESSION['contactExportFile'], $_SESSION['exportContactCriteria'], $_SESSION['contactExportMeta']);
        if(is_null($listId)){
            $file = "contact_export.csv";
            $listName = CHtml::link(Yii::t('contacts', 'All Contacts'), array('/contacts/contacts/index'), array('style' => 'text-decoration:none;'));
            $_SESSION['exportContactCriteria'] = array('with' => array()); // Forcefully disable eager loading so it doesn't go super-slow)
        }else{
            $list = X2List::load($listId);
            $criteria = $list->queryCriteria();
            $criteria->with = array();
            $_SESSION['exportContactCriteria'] = $criteria;
            $file = "list".$listId.".csv";
            $listName = CHtml::link(Yii::t('contacts', 'List')." $listId: ".$list->name, array('/contacts/contacts/list','id'=>$listId), array('style' => 'text-decoration:none;'));
        }
        $filePath = $this->safePath($file);
        $_SESSION['contactExportFile'] = $file;
        $attributes = X2Model::model('Contacts')->attributes;
        $meta = array_keys($attributes);
        if(isset($list)){
            // Figure out gridview settings to export those columns
            $gridviewSettings = json_decode(Yii::app()->params->profile->gridviewSettings, true);
            if(isset($gridviewSettings['contacts_list'.$listId])){
                $tempMeta = array_keys($gridviewSettings['contacts_list'.$listId]);
                $meta = array_intersect($tempMeta, $meta);
            }
        }
        // Set up metadata
        $_SESSION['contactExportMeta'] = $meta;
        $fp = fopen($filePath, 'w+');
        fputcsv($fp, $meta);
        fclose($fp);
        $this->render('exportContacts', array(
            'listId' => $listId,
            'listName' => $listName,
        ));
    }

    /**
     * An AJAX called function which exports Contact data to a CSV via pagination
     * @param int $page The page of the data provider to export
     */
    public function actionExportSet($page){
        Contacts::$autoPopulateFields = false;
        $file = $this->safePath($_SESSION['contactExportFile']);
        $fields = X2Model::model('Contacts')->getFields();
        $fp = fopen($file, 'a+');
        // Load data provider based on export criteria
        $dp = new CActiveDataProvider('Contacts', array(
                    'criteria' => isset($_SESSION['exportContactCriteria']) ? $_SESSION['exportContactCriteria'] : array(),
                    'pagination' => array(
                        'pageSize' => 100,
                    ),
                ));
        // Flip through to the right page.
        $pg = $dp->getPagination();
        $pg->setCurrentPage($page);
        $dp->setPagination($pg);
        $records = $dp->getData();
        $pageCount = $dp->getPagination()->getPageCount();
        // We need to set our data to be human friendly, so loop through all the
        // records and format any date / link / visibility fields.
        foreach($records as $record){
            foreach($fields as $field){
                $fieldName = $field->fieldName;
                if($field->type == 'date' || $field->type == 'dateTime'){
                    if(is_numeric($record->$fieldName))
                        $record->$fieldName = Formatter::formatLongDateTime($record->$fieldName);
                }elseif($field->type == 'link'){
                    $name = $record->$fieldName;
                    if(!empty($field->linkType)){
                        list($name, $id) = Fields::nameAndId($name);
                    }
                    if(!empty($name))
                        $record->$fieldName = $name;
                }elseif($fieldName == 'visibility'){
                    $record->$fieldName = $record->$fieldName == 1 ? 'Public' : 'Private';
                }
            }
            // Enforce metadata to ensure accuracy of column order, then export.
            $combinedMeta = array_combine($_SESSION['contactExportMeta'], $_SESSION['contactExportMeta']);
            $tempAttributes = array_intersect_key($record->attributes, $combinedMeta);
            $tempAttributes = array_merge($combinedMeta, $tempAttributes);
            fputcsv($fp, $tempAttributes);
        }

        unset($dp);

        fclose($fp);
        if($page + 1 < $pageCount){
            echo $page + 1;
        }
    }

    public function actionDelete($id){
        if(Yii::app()->request->isPostRequest){
            $model = $this->loadModel($id);
            $model->clearTags();
            $model->delete();

            Actions::model()->deleteAllByAttributes(array('associationType' => 'contacts', 'associationId' => $id));
        } else {
            throw new CHttpException(400, Yii::t('app', 'Invalid request. Please do not repeat this request again.'));
        }

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if(!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
    }

    public function actionSubscribe(){
        if(isset($_POST['ContactId']) && isset($_POST['Checked'])){
            $id = $_POST['ContactId'];

            $checked = json_decode($_POST['Checked']);

            if($checked){ // user wants to subscribe to this contact
                $result = Yii::app()->db->createCommand()
                        ->select()
                        ->from('x2_subscribe_contacts')
                        ->where(array('and', 'contact_id=:contact_id', 'user_id=:user_id'), array(':contact_id' => $id, 'user_id' => Yii::app()->user->id))
                        ->queryAll();
                if(empty($result)){ // ensure user isn't already subscribed to this contact
                    Yii::app()->db->createCommand()->insert('x2_subscribe_contacts', array('contact_id' => $id, 'user_id' => Yii::app()->user->id));
                }
            }else{ // user wants to unsubscribe to this contact
                $result = Yii::app()->db->createCommand()
                        ->select()
                        ->from('x2_subscribe_contacts')
                        ->where(array('and', 'contact_id=:contact_id', 'user_id=:user_id'), array(':contact_id' => $id, 'user_id' => Yii::app()->user->id))
                        ->queryAll();
                if(!empty($result)){ // ensure user is subscribed before unsubscribing
                    Yii::app()->db->createCommand()->delete('x2_subscribe_contacts', array('contact_id=:contact_id', 'user_id=:user_id'), array(':contact_id' => $id, ':user_id' => Yii::app()->user->id));
                }
            }
        }
    }

    public function actionQtip($id){
        $contact = $this->loadModel($id);

        $this->renderPartial('qtip', array('contact' => $contact));
    }

    public function actionCleanFailedLeads(){
        $file = $this->safePath('failed_leads.csv');

        if(file_exists($file)){
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.filesize($file));
            ob_clean();
            flush();
            readfile($file);
            unlink($file);
        }
    }

}
