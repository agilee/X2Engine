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
 * Controller to handle creating and mailing campaigns.
 *
 * @package application.modules.marketing.controllers
 */
class MarketingController extends x2base {
    
    public $modelClass = 'Campaign';

    public function behaviors(){
        return array_merge(parent::behaviors(), array(
                    'CampaignMailingBehavior' => array('class' => 'application.modules.marketing.components.CampaignMailingBehavior'),
                    'ResponseBehavior' => array('class' => 'application.components.ResponseBehavior', 'isConsole' => false),
                ));
    }

    public function accessRules(){
        return array(
            array('allow', // allow all users
                'actions' => array('click'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform the following actions
                'actions' => array(
					'index', 'view', 'create', 'createFromTag', 'update', 'search', 'delete', 'launch', 
					'toggle', 'complete', 'getItems', 'inlineEmail', 'mail', 'deleteWebForm',
					'webleadForm'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' action
                'actions' => array('admin'),
                'users' => array('admin'),
            ),
            array('deny', // deny all users
                'users' => array('*'),
            ),
        );
    }

    public function actions(){
        return array_merge(parent::actions(), array(
            'webleadForm' => array(
                'class' => 'CreateWebFormAction',
            ),
            'inlineEmail' => array(
                'class' => 'InlineEmailAction',
            ),
        ));
    }

    /**
     * Deletes a web form record with the specified id 
     * @param int $id
     */
    /*public function actionAjaxDeleteWebForm ($id) {
        $model = WebForm::model ()->findByPk ($id); 
        $success = false;
        if ($model) {
            $success = $model->delete ();
        }
        AuxLib::ajaxReturn (
            $success,  
            Yii::t('app', 'Success'),
            Yii::t('app', 'Unable to delete web form')
        );
    }*/

    

    /**
     * Returns a JSON array of the names of all campaigns filtered by a search term.
     *
     * @return string A JSON array of strings
     */
    public function actionGetItems(){
        $sql = 'SELECT id, name as value FROM x2_campaigns WHERE name LIKE :qterm ORDER BY name ASC';
        $command = Yii::app()->db->createCommand($sql);
        $qterm = '%'.$_GET['term'].'%';
        $command->bindParam(":qterm", $qterm, PDO::PARAM_STR);
        $result = $command->queryAll();
        echo CJSON::encode($result);
        exit;
    }

    /**
     * Displays a particular model.
     *
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id){
        $model = $this->loadModel($id);

        if(!isset($model)){
            Yii::app()->user->setFlash(
                'error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        if(isset($model->list)){
            //set this as the list we are viewing, for use by vcr controls
            Yii::app()->user->setState('contacts-list', $model->list->id);
        }

        // add campaign to user's recent item list
        User::addRecentItem('p', $id, Yii::app()->user->getId()); 

        $this->view($model, 'marketing', array('contactList' => $model->list));
    }

    /**
     * Displays the content field (email template) for a particular model.
     *
     * @param integer $id the ID of the model to be displayed
     */
    public function actionViewContent($id){
        $model = $this->loadModel($id);

        if(!isset($model)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        echo $model->content;
    }

    /**
     * Override of {@link CommonControllerBehavior::loadModel()}; expected
     * behavior is in this case deference to the campaign model's
     * {@link Campagin::load()} function.
     *
     * @param type $id
     * @return type
     */
    public function loadModel($id){
        return Campaign::load($id);
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate(){
        $model = new Campaign;
        $model->type = 'Email'; //default choice for now

        if(isset($_POST['Campaign'])){
            $model->setX2Fields($_POST['Campaign']);
            $model->createdBy = Yii::app()->user->getName();
            if($model->save()){
                if(isset($_POST['AttachmentFiles'])){
                    if(isset($_POST['AttachmentFiles']['id'])){
                        foreach($_POST['AttachmentFiles']['id'] as $mediaId){
                            $attachment = new CampaignAttachment;
                            $attachment->campaign = $model->id;
                            $attachment->media = $mediaId;
                            $attachment->save();
                        }
                    }
                }
                $this->redirect(array('view', 'id' => $model->id));
            }
        }elseif(isset($_GET['Campaign'])){
            //preload the create form with query params
            $model->setAttributes($_GET['Campaign']);
            $model->setX2Fields($_GET['Campaign']);
        }

        $this->render('create', array('model' => $model));
    }

    /**
     * Create a campaign for all contacts with a certain tag.
     *
     * This action will create and save the campaign and redirect the user to
     * edit screen to fill in the email message, etc.  It is intended to provide
     * a fast workflow from tags to campaigns.
     *
     * @param string $tag
     */
    public function actionCreateFromTag($tag){
        //enusre tag sanity
        if(empty($tag) || strlen(trim($tag)) == 0){
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'Invalid tag value'));
            $this->redirect(Yii::app()->request->getUrlReferrer());
        }

        //ensure sacred hash
        if(substr($tag, 0, 1) != '#'){
            $tag = '#'.$tag;
        }

        //only works for contacts
        $modelType = 'Contacts';
        $now = time();

        //get all contact ids from tags
        $ids = Yii::app()->db->createCommand()
                ->select('itemId')
                ->from('x2_tags')
                ->where('type=:type AND tag=:tag')
                ->group('itemId')
                ->order('itemId ASC')
                ->bindValues(array(':type' => $modelType, ':tag' => $tag))
                ->queryColumn();

        //create static list
        $list = new X2List;
        $list->name = Yii::t('marketing', 'Contacts for tag').' '.$tag;
        $list->modelName = $modelType;
        $list->type = 'campaign';
        $list->count = count($ids);
        $list->visibility = 1;
        $list->assignedTo = Yii::app()->user->getName();
        $list->createDate = $now;
        $list->lastUpdated = $now;

        //create campaign
        $campaign = new Campaign;
        $campaign->name = Yii::t('marketing', 'Mailing for tag').' '.$tag;
        $campaign->type = 'Email';
        $campaign->visibility = 1;
        $campaign->assignedTo = Yii::app()->user->getName();
        $campaign->createdBy = Yii::app()->user->getName();
        $campaign->updatedBy = Yii::app()->user->getName();
        $campaign->createDate = $now;
        $campaign->lastUpdated = $now;

        $transaction = Yii::app()->db->beginTransaction();
        try{
            if(!$list->save())
                throw new Exception(array_shift(array_shift($list->getErrors())));
            $campaign->listId = $list->nameId;
            if(!$campaign->save())
                throw new Exception(array_shift(array_shift($campaign->getErrors())));

            foreach($ids as $id){
                $listItem = new X2ListItem;
                $listItem->listId = $list->id;
                $listItem->contactId = $id;
                if(!$listItem->save())
                    throw new Exception(array_shift(array_shift($listItem->getErrors())));
            }

            $transaction->commit();
            $this->redirect($this->createUrl('update', array('id' => $campaign->id)));
        }catch(Exception $e){
            $transaction->rollBack();
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'Could not create mailing').': '.$e->getMessage());
            $this->redirect(Yii::app()->request->getUrlReferrer());
        }
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id){
        $model = $this->loadModel($id);

        if(!isset($model)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        if(isset($_POST['Campaign'])){
            $oldAttributes = $model->attributes;
            $model->setX2Fields($_POST['Campaign']);

            if($model->save()){
                CampaignAttachment::model()->deleteAllByAttributes(array('campaign' => $model->id));
                if(isset($_POST['AttachmentFiles'])){
                    if(isset($_POST['AttachmentFiles']['id'])){
                        foreach($_POST['AttachmentFiles']['id'] as $mediaId){
                            $attachment = new CampaignAttachment;
                            $attachment->campaign = $model->id;
                            $attachment->media = $mediaId;
                            $attachment->save();
                        }
                    }
                }
                $this->redirect(array('view', 'id' => $model->id));
            }
        }

        $this->render('update', array('model' => $model));
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id){
        if(Yii::app()->request->isPostRequest){
            $model = $this->loadModel($id);

            if(!isset($model)){
                Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
                $this->redirect(array('index'));
            }
            // now in X2ChangeLogBehavior
            // $event=new Events;
            // $event->type='record_deleted';
            // $event->associationType=$this->modelClass;
            // $event->associationId=$model->id;
            // $event->text=$model->name;
            // $event->user=Yii::app()->user->getName();
            // $event->save();
            $list = $model->list;
            if(isset($list) && $list->type == "campaign")
                $list->delete();
            // $this->cleanUpTags($model);	// now in TagBehavior
            $model->delete();

            // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
            if(!isset($_GET['ajax']))
                $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('index'));
        } else{
            Yii::app()->user->setFlash('error', Yii::t('app', 'Invalid request. Please do not repeat this request again.'));
            $this->redirect(array('index'));
        }
    }

    /**
     * Lists all models.
     */
    public function actionIndex(){
        $model = new Campaign('search');
        $this->render('index', array('model' => $model));
    }

    public function actionAdmin(){
        $this->redirect('index');
    }

    /**
     * Launches the specified campaign, activating it for mailing
     *
     * When a campaign is created, it is specified with an existing contact list.
     * When the campaign is lauched, this list is replaced with a duplicate to prevent
     * the original from being modified, and to allow campaign specific information to
     * be saved in the list.  This includes the email send time, and the times when a
     * contact has opened the mail or unsubscribed from the list.
     *
     * @param integer $id ID of the campaign to launch
     */
    public function actionLaunch($id){
        $campaign = $this->loadModel($id);

        if(!isset($campaign)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        if(!isset($campaign->list)){
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'Contact List cannot be blank.'));
            $this->redirect(array('view', 'id' => $id));
        }

        if(empty($campaign->subject) && $campaign->type === 'Email'){
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'Subject cannot be blank.'));
            $this->redirect(array('view', 'id' => $id));
        }

        if($campaign->launchDate != 0 && $campaign->launchDate < time()){
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'The campaign has already been launched.'));
            $this->redirect(array('view', 'id' => $id));
        }

        if(($campaign->list->type == 'dynamic' && X2Model::model($campaign->list->modelName)->count($campaign->list->queryCriteria()) < 1)
                || ($campaign->list->type != 'dynamic' && count($campaign->list->listItems) < 1)){
            Yii::app()->user->setFlash('error', Yii::t('marketing', 'The contact list is empty.'));
            $this->redirect(array('view', 'id' => $id));
        }

        //Duplicate the list for campaign tracking, leave original untouched
        //only if the list is not already a campaign list
        if($campaign->list->type != "campaign"){
            $newList = $campaign->list->staticDuplicate();
            if(!isset($newList)){
                Yii::app()->user->setFlash('error', Yii::t('marketing', 'The contact list is empty.'));
                $this->redirect(array('view', 'id' => $id));
            }
            $newList->type = 'campaign';
            if($newList->save()) {
                $campaign->list = $newList;
                $campaign->listId = $newList->nameId;
            } else {
                Yii::app()->user->setFlash('error', Yii::t('marketing', 'Failed to save temporary list.'));
            }
        }

        $campaign->launchDate = time();
        $campaign->save();

        Yii::app()->user->setFlash('success', Yii::t('marketing', 'Campaign launched'));
        $this->redirect(array('view', 'id' => $id));
    }

    /**
     * Deactivate a campaign to halt mailings, or resume paused campaign
     *
     * @param integer $id The ID of the campaign to toggle
     */
    public function actionToggle($id){
        $campaign = $this->loadModel($id);

        if(!isset($campaign)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        $campaign->active = $campaign->active ? 0 : 1;
        $campaign->save();
        $message = $campaign->active ? Yii::t('marketing', 'Campaign resumed') : Yii::t('marketing', 'Campaign paused');
        Yii::app()->user->setFlash('notice', Yii::t('app', $message));
        $this->redirect(array('view', 'id' => $id));
    }

    /**
     * Forcibly complete a campaign despite any unsent mail
     *
     * @param integer $id The ID of the campaign to complete
     */
    public function actionComplete($id){
        $campaign = $this->loadModel($id);

        if(!isset($campaign)){
            Yii::app()->user->setFlash('error', Yii::t('app', 'The requested page does not exist.'));
            $this->redirect(array('index'));
        }

        $campaign->active = 0;
        $campaign->complete = 1;
        $campaign->save();
        $message = Yii::t('marketing', 'Campaign complete.');
        Yii::app()->user->setFlash('notice', Yii::t('app', $message));
        $this->redirect(array('view', 'id' => $id));
    }

    /**
     * Sends an individual email to an item in a campaign/newsletter list.
     *
     * @param type $campaignId
     * @param type $itemId
     */
    public function actionMailIndividual($campaignId,$itemId) {
        $this->itemId = $itemId;
        $this->campaign = Campaign::model()->findByPk($campaignId);
        $email = $this->recipient->email;
        if($this->campaign instanceof Campaign && $this->listItem instanceof X2ListItem) {
            $this->sendIndividualMail();
            $this->response['fullStop'] = $this->fullStop;
            $status = $this->status;
            // Actual SMTP (or elsewise) delivery error that should stop the batch:
            $error = ($status['code']!=200 && $this->undeliverable) || $this->fullStop;
            $this->response['status'] = $this->status;
            $this->respond($status['message'],$error);
        } else {
            $this->respond(Yii::t('marketing','Specified campaign does not exist.'),1);
        }
    }

    /**
     * Track when an email is viewed, a link is clicked, or the recipient unsubscribes
     *
     * Campaign emails include an img tag to a blank image to track when the message was opened,
     * an unsubscribe link, and converted links to track when a recipient clicks a link.
     * All those links are handled by this action.
     *
     * @param integer $uid The unique id of the recipient
     * @param string $type 'open', 'click', or 'unsub'
     * @param string $url For click types, this is the urlencoded URL to redirect to
     * @param string $email For unsub types, this is the urlencoded email address
     *  of the person unsubscribing
     */
    public function actionClick($uid, $type, $url = null, $email = null){
        $now = time();
        $item = CActiveRecord::model('X2ListItem')->with('contact', 'list')->findByAttributes(array('uniqueId' => $uid));
        // if($item !== null)
        // $campaign = CActiveRecord::model('Campaign')->findByAttributes(array('listId'=>$item->listId));
        //it should never happen that we have a list item without a campaign,
        //but it WILL happen on x2software or any old db where x2_list_items does not cascade on delete
        //we can't track anything if the listitem was deleted, but at least prevent breaking links
        if($item === null || $item->list->campaign === null){
            if($type == 'click'){
                // VERY legacy; corresponds to the old commented-out tracking
                // links code that was in version 3.6 or so moved into
                // CampaignMailingBehavior.prepareEmail
                $this->redirect(urldecode($url));
            }elseif($type == 'open'){
                //return a one pixel transparent gif
                header('Content-Type: image/gif');
                echo base64_decode('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
            }elseif($type == 'unsub' && !empty($email)){
                Contacts::model()->updateAll(array('doNotEmail' => true), 'email=:email', array(':email' => $email));
                X2ListItem::model()->updateAll(array('unsubscribed' => time()), 'emailAddress=:email AND unsubscribed=0', array('email' => $email));
                $message = Yii::t('marketing', 'You have been unsubscribed');
                echo '<html><head><title>'.$message.'</title></head><body>'.$message.'</body></html>';
            }
            return;
        }

        $contact = $item->contact;
        $list = $item->list;

        $event = new Events;
        $notif = new Notification;

        $action = new Actions;
        $action->completeDate = $now;
        $action->complete = 'Yes';
        $action->updatedBy = 'API';
        $skipActionEvent = true;

        if($contact !== null){
            $skipActionEvent = false;
            if($email === null)
                $email = $contact->email;

            $action->associationType = 'contacts';
            $action->associationId = $contact->id;
            $action->associationName = $contact->name;
            $action->visibility = $contact->visibility;
            $action->assignedTo = $contact->assignedTo;

            $event->associationId = $action->associationId;
            $event->associationType = 'Contacts';

            if($action->assignedTo !== '' && $action->assignedTo !== 'Anyone'){
                $notif->user = $contact->assignedTo;
                $notif->modelType = 'Contacts';
                $notif->modelId = $contact->id;
                $notif->createDate = $now;
                $notif->value = $item->list->campaign->getLink();
            }
        }elseif($list !== null){
            $action = new Actions;
            $action->type = 'note';
            $action->createDate = $now;
            $action->lastUpdated = $now;
            $action->completeDate = $now;
            $action->complete = 'Yes';
            $action->updatedBy = 'admin';

            $action->associationType = 'X2List';
            $action->associationId = $list->id;
            $action->associationName = $list->name;
            $action->visibility = $list->visibility;
            $action->assignedTo = $list->assignedTo;
        }

        if($type == 'unsub'){
            $item->unsubscribe();

            // find any weblists associated with the email address and create unsubscribe actions for each of them
            $sql = 'SELECT t.* FROM x2_lists as t JOIN x2_list_items as li ON t.id=li.listId WHERE li.emailAddress=:email AND t.type="weblist";';
            $weblists = Yii::app()->db->createCommand($sql)->queryAll(true, array('email' => $email));
            foreach($weblists as $weblist){
                $weblistAction = new Actions();
                $weblistAction->disableBehavior('changelog');
                //$weblistAction->id = 0; // this causes primary key contraint violation errors
                $weblistAction->isNewRecord = true;
                $weblistAction->type = 'email_unsubscribed';
                $weblistAction->associationType = 'X2List';
                $weblistAction->associationId = $weblist['id'];
                $weblistAction->associationName = $weblist['name'];
                $weblistAction->visibility = $weblist['visibility'];
                $weblistAction->assignedTo = $weblist['assignedTo'];
                $weblistAction->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".$email." ".Yii::t('marketing', 'has unsubscribed').".";
                $weblistAction->save();
            }

            $action->type = 'email_unsubscribed';
            $notif->type = 'email_unsubscribed';

            if($contact === null)
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".$item->emailAddress.' '.Yii::t('marketing', 'has unsubscribed').".";
            else
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".Yii::t('marketing', 'Contact has unsubscribed').".\n".Yii::t('marketing', '\'Do Not Email\' has been set').".";

            $message = Yii::t('marketing', 'You have been unsubscribed');
            echo '<html><head><title>'.$message.'</title></head><body>'.$message.'</body></html>';
        } elseif($type == 'open'){
            //return a one pixel transparent gif
            header('Content-Type: image/gif');
            echo base64_decode('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
            // Check if it has been marked as opened already, or if the contact
            // no longer exists. If so, exit; nothing more need be done.
            if($item->opened != 0)
                Yii::app()->end();
            $item->markOpened(); // This needs to happen before the skip option to accomodate the case of newsletters
            if($skipActionEvent)
                Yii::app()->end();
            $action->disableBehavior('changelog');
            $action->type = 'campaignEmailOpened';
            $event->type = 'email_opened';
            $notif->type = 'email_opened';
            $event->save();
            if($contact === null)
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".$item->emailAddress.' '.Yii::t('marketing', 'has opened the email').".";
            else
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".Yii::t('marketing', 'Contact has opened the email').".";
        } elseif($type == 'click'){
            // More legacy code corresponding to the disabled tracking links feature:
            $item->markClicked($url);

            $action->type = 'email_clicked';
            $notif->type = 'email_clicked';

            if($contact === null)
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".Yii::t('marketing', 'Contact has clicked a link').":\n".urldecode($url);
            else
                $action->actionDescription = Yii::t('marketing', 'Campaign').': '.$item->list->campaign->name."\n\n".$item->emailAddress.' '.Yii::t('marketing', 'has clicked a link').":\n".urldecode($url);

            $this->redirect(urldecode($url));
        }

        $action->save();
        // if any of these hasn't been fully configured
        $notif->save();  // it will simply not validate and not be saved
    }

    public function actionRemoveWebLeadFormCustomHtml () {
        if(!empty($_POST) && !empty ($_POST['id'])) {
            $model = WebForm::model()->findByPk ($_POST['id']);
            if ($model) {
                $model->header = '';
                if ($model->save ()) {
                    echo CJSON::encode (
                        array ('success', $model->attributes));
                    return;
                }
            }
        }
        echo CJSON::encode (
            array ('error', Yii::t('marketing', 'Custom HTML could not be removed.')));
    }

    public function actionSaveWebLeadFormCustomHtml () {
        if(!empty($_POST) && !empty ($_POST['id']) && !empty ($_POST['html'])){
            $model = WebForm::model()->findByPk ($_POST['id']);
            if ($model) {
                $model->header = $_POST['html'];
                if ($model->save ()) {
                    echo CJSON::encode (array ('success', $model->attributes));
                    return;
                }
            }
        }
        echo CJSON::encode (
            array ('error', Yii::t('marketing', 'Custom HTML could not be saved.')));
    }

    /**
     * Get the web tracker code to insert into your website
     */
    public function actionWebTracker(){
        $admin = Yii::app()->settings;
        if(isset($_POST['Admin']['enableWebTracker'], $_POST['Admin']['webTrackerCooldown'])){
            $admin->enableWebTracker = $_POST['Admin']['enableWebTracker'];
            $admin->webTrackerCooldown = $_POST['Admin']['webTrackerCooldown'];
            
            $admin->save();
        }
        $this->render('webTracker', array('admin' => $admin));
    }

	




}
