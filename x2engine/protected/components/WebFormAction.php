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


class WebFormAction extends CAction {

    public static function sanitizeGetParams () {
        //sanitize get params
        $whitelist = array(
            'fg', 'bgc', 'font', 'bs', 'bc', 'iframeHeight'
        );
        $_GET = array_intersect_key($_GET, array_flip($whitelist));
        //restrict param values, alphanumeric, # for color vals, comma for tag list, . for decimals
        $_GET = preg_replace('/[^a-zA-Z0-9#,.]/', '', $_GET);
    }

    private static function addTags ($model) {
        // add tags
        if(!empty($_POST['tags'])){
            $taglist = explode(',', $_POST['tags']);
            if($taglist !== false){
                foreach($taglist as &$tag){
                    if($tag === '')
                        continue;
                    if(substr($tag, 0, 1) != '#')
                        $tag = '#'.$tag;
                    $tagModel = new Tags;
                    $tagModel->taggedBy = 'API';
                    $tagModel->timestamp = time();
                    $tagModel->type = get_class ($model);
                    $tagModel->itemId = $model->id;
                    $tagModel->tag = $tag;
                    $tagModel->itemName = $model->name;
                    $tagModel->save();

                    X2Flow::trigger('RecordTagAddTrigger', array(
                        'model' => $model,
                        'tags' => $tag,
                    ));
                }
            }
        }
    }

    

    

    private function handleWebleadFormSubmission (X2Model $model, $extractedParams) {
        $newRecord = $model->isNewRecord;
        if(isset($_POST['Contacts'])) {

            $model->createEvent = false;
            $model->setX2Fields($_POST['Contacts'], true);
            // Extra sanitizing
            $p = Fields::getPurifier();
            foreach($model->attributes as $name=>$value) {
                if($name != $model->primaryKey() && !empty($value)) {
                    $model->$name = $p->purify($value);
                }
            }
            $now = time();

            //require email field, check format
            /*if(preg_match(
                "/[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}/",
                $_POST['Contacts']['email']) == 0) {
                $this->renderPartial('application.components.views.webFormSubmit',
                    array (
                        'type' => 'weblead',
                        'error' => Yii::t('contacts', 'Invalid Email Address')
                    )
                );
                return;
            }*/

            


            $model->validate ();
            if(!$model->hasErrors()){

                $duplicates = array ();
                if(!empty($model->email)){

                    //find any existing contacts with the same contact info
                    $criteria = new CDbCriteria();
                    $criteria->compare('email', $model->email, false, "OR");
                    $duplicates = $model->findAll($criteria);
                }

                if(count($duplicates) > 0){ //use existing record, update background info
                    /**/AuxLib::debugLogR ('found dup');
                    $newBgInfo = $model->backgroundInfo;
                    $model = $duplicates[0];
                    $oldBgInfo = $model->backgroundInfo;
                    if ($newBgInfo !== $oldBgInfo) {
                        $model->backgroundInfo .= 
                            (($oldBgInfo && $newBgInfo) ? "\n" : '') . $newBgInfo;
                    }

                    

                    

                    $success = $model->save();
                }else{ //create new record
                    $model->assignedTo = $this->controller->getNextAssignee();
                    $model->visibility = 1;
                    $model->createDate = $now;
                    $model->lastUpdated = $now;
                    $model->updatedBy = 'admin';

                    

                    $success = $model->save();

                    

                    //TODO: upload profile picture url from webleadfb
                }
                
                if($success){

                    self::generateLead ($model, $extractedParams['leadSource']);

                    self::addTags ($model);
                    $tags = ((!isset($_POST['tags']) || empty($_POST['tags'])) ? 
                        array() : explode(',',$_POST['tags']));
                    if($newRecord) {
                        X2Flow::trigger(
                            'WebleadTrigger', array('model' => $model, 'tags' => $tags));
                    }

                    //use the submitted info to create an action
                    $action = new Actions;
                    $action->actionDescription = Yii::t('contacts', 'Web Lead')
                            ."\n\n".Yii::t('contacts', 'Name').': '.
                            CHtml::decode($model->firstName)." ".
                            CHtml::decode($model->lastName)."\n".Yii::t('contacts', 'Email').": ".
                            CHtml::decode($model->email)."\n".Yii::t('contacts', 'Phone').": ".
                            CHtml::decode($model->phone)."\n".
                            Yii::t('contacts', 'Background Info').": ".
                            CHtml::decode($model->backgroundInfo);

                    // create action
                    $action->type = 'note';
                    $action->assignedTo = $model->assignedTo;
                    $action->visibility = '1';
                    $action->associationType = 'contacts';
                    $action->associationId = $model->id;
                    $action->associationName = $model->name;
                    $action->createDate = $now;
                    $action->lastUpdated = $now;
                    $action->completeDate = $now;
                    $action->complete = 'Yes';
                    $action->updatedBy = 'admin';
                    $action->save();

                    // create a notification if the record is assigned to someone
                    $event = new Events;
                    $event->associationType = 'Contacts';
                    $event->associationId = $model->id;
                    $event->user = $model->assignedTo;
                    $event->type = 'weblead_create';
                    $event->save();

                    

                    if($model->assignedTo != 'Anyone' && $model->assignedTo != '') {

                        $notif = new Notification;
                        $notif->user = $model->assignedTo;
                        $notif->createdBy = 'API';
                        $notif->createDate = time();
                        $notif->type = 'weblead';
                        $notif->modelType = 'Contacts';
                        $notif->modelId = $model->id;
                        $notif->save();

                        $profile = Profile::model()->findByAttributes(
                            array('username' => $model->assignedTo));

                        /* send user that's assigned to this weblead an email if the user's email
                        address is set and this weblead has a user email template */
                        if($profile !== null && !empty($profile->emailAddress)){

                            
                                $subject = Yii::t('marketing', 'New Web Lead');
                                $message =
                                    Yii::t('marketing',
                                        'A new web lead has been assigned to you: ').
                                    CHtml::link(
                                        $model->firstName.' '.$model->lastName,
                                        array('/contacts/contacts/view', 'id' => $model->id)).'.';
                                $address = array('to' => array(array('', $profile->emailAddress)));
                                $emailFrom = Credentials::model()->getDefaultUserAccount(
                                    Credentials::$sysUseId['systemNotificationEmail'], 'email');
                                if($emailFrom == Credentials::LEGACY_ID)
                                    $emailFrom = array(
                                        'name' => $profile->fullName,
                                        'address' => $profile->emailAddress
                                    );

                                $status = $this->controller->sendUserEmail(
                                    $address, $subject, $message, null, $emailFrom);
                            
                        }

                    }

                    
                } else {
                    $errMsg = 'Error: WebListenerAction.php: model failed to save';
                    /**/AuxLib::debugLog ($errMsg);
                    Yii::log ($errMsg, '', 'application.debug');
                }

                $this->controller->renderPartial('application.components.views.webFormSubmit',
                    array ('type' => 'weblead'));

                Yii::app()->end(); // success!
            }
        } 

        self::sanitizeGetParams ();

        
            $this->controller->renderPartial(
                'application.components.views.webForm', array('type' => 'weblead'));
        

    }


    private function handleServiceFormSubmission ($model, $extractedParams) {
        if(isset($_POST['Services'])){ // web form submitted
            if(isset($_POST['Services']['firstName'])){
                $firstName = $_POST['Services']['firstName'];
                $fullName = $firstName;
            }

            if(isset($_POST['Services']['lastName'])){
                $lastName = $_POST['Services']['lastName'];
                if(isset($fullName)){
                    $fullName .= ' '.$lastName;
                }else{
                    $fullName = $lastName;
                }
            }

            if(isset($_POST['Services']['email'])){
                $email = $_POST['Services']['email'];
            }
            if(isset($_POST['Services']['phone'])){
                $phone = $_POST['Services']['phone'];
            }
            if(isset($_POST['Services']['desription'])){
                $description = $_POST['Services']['description'];
            }

            

            // Extra sanitizing
            $p = Fields::getPurifier();
            foreach($model->attributes as $name=>$value) {
                if($name != $model->primaryKey() && !empty($value)) {
                    $model->$name = $p->purify($value);
                }
            }

            $contact = Contacts::model()->findByAttributes(array('email' => $email));

            if(isset($email) && $email) {
                $contact = Contacts::model()->findByAttributes(array('email' => $email));
            } else {
                $contact = false;
            }

            if($contact){
                $model->contactId = $contact->id;
            }else{
                $model->contactId = "Unregistered";
            }

            if(isset($fullName) || isset($email)){
                $model->subject = Yii::t('services', 'Web Form Case entered by {name}', array(
                            '{name}' => isset($fullName) ? $fullName : $email,
                ));
            }else{
                $model->subject = Yii::t('services', 'Web Form Case');
            }

            $model->origin = 'Web';
            if(!isset($model->impact) || $model->impact == '')
                $model->impact = Yii::t('services', '3 - Moderate');
            if(!isset($model->status) || $model->status == '')
                $model->status = Yii::t('services', 'New');
            if(!isset($model->mainIssue) || $model->mainIssue == '')
                $model->mainIssue = Yii::t('services', 'General Request');
            if(!isset($model->subIssue) || $model->subIssue == '')
                $model->subIssue = Yii::t('services', 'Other');
            $model->assignedTo = $this->controller->getNextAssignee();
            $model->email = CHtml::encode($email);
            $now = time();
            $model->createDate = $now;
            $model->lastUpdated = $now;
            $model->updatedBy = 'admin';
            if (isset ($description))
                $model->description = CHtml::encode($description);

            

            if(!$model->hasErrors()){

                if($model->save()){
                    $model->name = $model->id;
                    $model->update(array('name'));

                    self::addTags ($model);

                    //use the submitted info to create an action
                    $action = new Actions;
                    $action->actionDescription = Yii::t('contacts', 'Web Form')."\n\n".
                            (isset($fullName) ? (Yii::t('contacts', 'Name').': '.$fullName."\n") : '').
                            (isset($email) ? (Yii::t('contacts', 'Email').": ".$email."\n") : '').
                            (isset($phone) ? (Yii::t('contacts', 'Phone').": ".$phone."\n") : '').
                            (isset($description) ?
                                (Yii::t('services', 'Description').": ".$description) : '');

                    // create action
                    $action->type = 'note';
                    $action->assignedTo = $model->assignedTo;
                    $action->visibility = '1';
                    $action->associationType = 'services';
                    $action->associationId = $model->id;
                    $action->associationName = $model->name;
                    $action->createDate = $now;
                    $action->lastUpdated = $now;
                    $action->completeDate = $now;
                    $action->complete = 'Yes';
                    $action->updatedBy = 'admin';
                    $action->save();

                    if(isset($email)){

                        //send email
                        $emailBody = Yii::t('services', 'Hello').' '.$fullName.",<br><br>";
                        $emailBody .= Yii::t('services',
                            'Thank you for contacting our Technical Support '.
                            'team. This is to verify we have received your request for Case# '.
                            '{casenumber}. One of our Technical Analysts will contact you shortly.',
                            array('{casenumber}' => $model->id));

                        $emailBody = Yii::app()->settings->serviceCaseEmailMessage;
                        if(isset($firstName))
                            $emailBody = preg_replace('/{first}/u', $firstName, $emailBody);
                        if(isset($lastName))
                            $emailBody = preg_replace('/{last}/u', $lastName, $emailBody);
                        if(isset($phone))
                            $emailBody = preg_replace('/{phone}/u', $phone, $emailBody);
                        if(isset($email))
                            $emailBody = preg_replace('/{email}/u', $email, $emailBody);
                        if(isset($description))
                            $emailBody = preg_replace('/{description}/u', $description, $emailBody);
                        $emailBody = preg_replace('/{case}/u', $model->id, $emailBody);
                        $emailBody = preg_replace('/\n|\r\n/', "<br>", $emailBody);

                        $uniqueId = md5(uniqid(rand(), true));
                        $emailBody .= '<img src="'.$this->controller->createAbsoluteUrl(
                            '/actions/actions/emailOpened', array('uid' => $uniqueId, 'type' => 'open')).'"/>';

                        $emailSubject = Yii::app()->settings->serviceCaseEmailSubject;
                        if(isset($firstName))
                            $emailSubject = preg_replace('/{first}/u', $firstName, $emailSubject);
                        if(isset($lastName))
                            $emailSubject = preg_replace('/{last}/u', $lastName, $emailSubject);
                        if(isset($phone))
                            $emailSubject = preg_replace('/{phone}/u', $phone, $emailSubject);
                        if(isset($email))
                            $emailSubject = preg_replace('/{email}/u', $email, $emailSubject);
                        if(isset($description))
                            $emailSubject = preg_replace('/{description}/u', $description,
                                $emailSubject);
                        $emailSubject = preg_replace('/{case}/u', $model->id, $emailSubject);
                        if(Yii::app()->settings->serviceCaseEmailAccount != 
                           Credentials::LEGACY_ID) {
                            $from = (int) Yii::app()->settings->serviceCaseEmailAccount;
                        } else {
                            $from = array(
                                'name' => Yii::app()->settings->serviceCaseFromEmailName,
                                'address' => Yii::app()->settings->serviceCaseFromEmailAddress
                            );
                        }
                        $useremail = array('to' => array(array(isset($fullName) ?
                            $fullName : '', $email)));

                        $status = $this->controller->sendUserEmail(
                            $useremail, $emailSubject, $emailBody, null, $from);

                        if($status['code'] == 200){
                            if($model->assignedTo != 'Anyone'){
                                $profile = X2Model::model('Profile')->findByAttributes(
                                    array('username' => $model->assignedTo));
                                if(isset($profile)){
                                    $useremail['to'] = array(
                                        array(
                                            $profile->fullName,
                                            $profile->emailAddress,
                                        ),
                                    );
                                    $emailSubject = 'Service Case Created';
                                    $emailBody = "A new service case, #".$model->id.
                                        ", has been created in X2Engine. To view the case, click ".
                                        "this link: ".$model->getLink();
                                    $status = $this->controller->sendUserEmail(
                                        $useremail, $emailSubject, $emailBody, null, $from);
                                }
                            }
                            //email action
                            $action = new Actions;
                            $action->associationType = 'services';
                            $action->associationId = $model->id;
                            $action->associationName = $model->name;
                            $action->visibility = 1;
                            $action->complete = 'Yes';
                            $action->type = 'email';
                            $action->completedBy = 'admin';
                            $action->assignedTo = $model->assignedTo;
                            $action->createDate = time();
                            $action->dueDate = time();
                            $action->completeDate = time();
                            $action->actionDescription = '<b>'.$model->subject."</b>\n\n".
                                $emailBody;
                            if($action->save()){
                                $track = new TrackEmail;
                                $track->actionId = $action->id;
                                $track->uniqueId = $uniqueId;
                                $track->save();
                            }
                        } else {
                            $errMsg = 'Error: actionWebForm.php: sendUserEmail failed';
                            /**/AuxLib::debugLog ($errMsg);
                            Yii::log ($errMsg, '', 'application.debug');
                        }
                    }
                    $this->controller->renderPartial('application.components.views.webFormSubmit',
                        array('type' => 'service', 'caseNumber' => $model->id));

                    Yii::app()->end(); // success!
                }
            }
        }

        self::sanitizeGetParams ();

        
            $this->controller->renderPartial (
                'application.components.views.webForm',
                array('model' => $model, 'type' => 'service'));
        
    }



    /**
     * Create a web lead form with a custom style
     *
     * There are currently two methods of specifying web form options. 
     *  Method 1 (legacy):
     *      Web form options are sent in the GET parameters (limited options: css, web form
     *      id for retrieving custom header)
     *  Method 2 (new):
     *      CSS options are passed in the GET parameters and all other options (custom fields, 
     *      custom html, and email templates) are stored in the database and accessed via a
     *      web form id sent in the GET parameters.
     *
     * This get request is for weblead/service type only, marketing/weblist/view supplies
     * the form that posts for weblist type
     *
     */
    public function run(){
        $modelClass = $this->controller->modelClass;
        if ($modelClass === 'Campaign') $modelClass = 'Contacts';

        if ($modelClass === 'Contacts')
            $model = new Contacts ('webForm');
        elseif ($modelClass === 'Services')
            $model = new Services ('webForm');

        $extractedParams = array ();

        if (isset ($_GET['webFormId'])) { 
            $webForm = WebForm::model()->findByPk($_GET['webFormId']);
        } 
        $extractedParams['leadSource'] = null;
        if (isset ($webForm)) { // new method
            if (!empty ($webForm->leadSource)) 
                $extractedParams['leadSource'] = $webForm->leadSource;
        }

        

        if ($modelClass === 'Contacts') {
            $this->handleWebleadFormSubmission ($model, $extractedParams);
        } else if ($modelClass === 'Services') {
            $this->handleServiceFormSubmission ($model, $extractedParams);
        }

    }

    /**
     * Creates a new lead and associates it with the contact
     * @param Contacts $contact
     * @param null|string $leadSource
     */
    private static function generateLead (
        Contacts $contact, $leadSource=null) {

        $lead = new X2Leads ('webForm');
        $lead->name = $contact->firstName.' '.$contact->lastName;
        $lead->leadSource = $leadSource;
        // disable validation to prevent saving from failing if leadSource isn't set
        if ($lead->save (false)) {
            Relationships::create ('X2Leads', $lead->id, 'Contacts', $contact->id);
        }

    }

}

?>
