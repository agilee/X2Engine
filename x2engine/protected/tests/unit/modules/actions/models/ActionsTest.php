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

Yii::import('application.models.*');
Yii::import('application.modules.actions.models.*');
Yii::import('application.modules.groups.models.*');
Yii::import('application.modules.users.models.*');
Yii::import('application.components.*');
Yii::import('application.components.permissions.*');

/**
 * Test for the Actions class
 * @package application.tests.unit.modules.actions.models
 * @author Demitri Morgan <demitri@x2engine.com>
 */
class ActionsTest extends CDbTestCase {

    public $fixtures = array(
        'actions'=>array ('Actions', '.ActionsTest'),
        'users'=> 'User',
        'profiles'=> 'Profile',
        'groupToUser'=>array ('GroupToUser', '.ActionsTest'),
        'groups'=>array ('Groups', '.ActionsTest'),
    );

    /**
     * Test special validation that avoids empty association when the type is
     * something meant to be associated, i.e. a logged call, note, etc.
     */
    public function testValidate() {
        $action = new Actions();
        $action->type = 'call';
        $action->actionDescription = 'Contacted. Will call back later';
        $this->assertFalse($action->validate());
        $this->assertTrue($action->hasErrors('associationId'));
        $this->assertTrue($action->hasErrors('associationType'));
        // Do the same thing but with "None" association type. Validation should fail.
        $action = new Actions();
        $action->type = 'call';
        $action->associationType = 'None';
        $this->assertFalse($action->validate());
        $this->assertTrue($action->hasErrors('associationId'));
        $this->assertTrue($action->hasErrors('associationType'));
    }

    public function testIsAssignedTo () {
        $action = $this->actions('action1');

        // test assignedTo field consisting of single username
        $this->assertTrue ($action->isAssignedTo ('testuser'));
        $this->assertFalse ($action->isAssignedTo ('testuser2'));

        $action = $this->actions('action2');

        // test assignedTo field consisting of a group id
        $this->assertTrue ($action->isAssignedTo ('testuser'));
        $this->assertFalse ($action->isAssignedTo ('testuser2'));

        $action = $this->actions('action3');

        // test assignedTo field consisting of username and group id
        $this->assertTrue ($action->isAssignedTo ('testuser'));
        $this->assertTrue ($action->isAssignedTo ('testuser2'));
        $this->assertFalse ($action->isAssignedTo ('testuser3'));

        $action = $this->actions('action4');

        // test assignedTo field consisting of 'Anyone'
        $this->assertTrue ($action->isAssignedTo ('testuser4'));
        $this->assertFalse ($action->isAssignedTo ('testuser4', true));

        // test assignedTo field consisting of '' (i.e. no one)
        $this->assertTrue ($action->isAssignedTo ('testuser4'));
        $this->assertFalse ($action->isAssignedTo ('testuser4', true));
    }

    public function testGetProfilesOfAssignees () {
        // action assignedTo field consists of username and group id
        $action = $this->actions('action3');
        $profiles = $action->getProfilesOfAssignees ();

        // this should return profile for username and all profiles in group, without duplicates
        $profileUsernames = array_map (function ($a) { return $a->username; }, $profiles);

        VERBOSE_MODE && print ('sizeof ($profiles) = ');
        VERBOSE_MODE && print (sizeof ($profiles)."\n");

        VERBOSE_MODE && print ('$profileUsernames  = ');
        VERBOSE_MODE && print ($profileUsernames);

        $this->assertTrue (sizeof ($profiles) === 2);
        $this->assertTrue (in_array ('testuser', $profileUsernames));
        $this->assertTrue (in_array ('testuser2', $profileUsernames));

        /* 
        action assignedTo field consists of username and group id. Here the username is included
        twice: once explicitly in the assignedTo field and a second time, implicitly, by its 
        membership to the group.
        */
        $action = $this->actions('action6');
        $profiles = $action->getProfilesOfAssignees ();

        // this should return profile for username and all profiles in group, without duplicates
        $profileUsernames = array_map (function ($a) { return $a->username; }, $profiles);

        VERBOSE_MODE && print ('sizeof ($profiles) = ');
        VERBOSE_MODE && print (sizeof ($profiles)."\n");

        VERBOSE_MODE && print ('$profileUsernames  = ');
        VERBOSE_MODE && print ($profileUsernames);

        $this->assertTrue (sizeof ($profiles) === 2);
        $this->assertTrue (in_array ('testuser', $profileUsernames));
        $this->assertTrue (in_array ('admin', $profileUsernames));
        
    }

    public function testGetAssignees () {
        // action assignedTo field consists of username and group id
        $action = $this->actions('action3');
        $assignees = $action->getAssignees (true);

        VERBOSE_MODE && print ('sizeof ($assignees) = ');
        VERBOSE_MODE && print (sizeof ($assignees)."\n");

        $this->assertTrue (sizeof ($assignees) === 2);
        $this->assertTrue (in_array ('testuser', $assignees));
        $this->assertTrue (in_array ('testuser2', $assignees));

        /* 
        action assignedTo field consists of username and group id. Here the username is included
        twice: once explicitly in the assignedTo field and a second time, implicitly, by its 
        membership to the group.
        */
        $action = $this->actions('action6');

        /* 
        here assignees usernames are retrieved, if a group id is in the assignedTo string,  
        usernames of all users in that group are also retrieved. duplicate usernames should
        get removed.
        */
        $assignees = $action->getAssignees (true);

        VERBOSE_MODE && print ('sizeof ($assignees) = ');
        VERBOSE_MODE && print (sizeof ($assignees)."\n");

        $this->assertTrue (sizeof ($assignees) === 2);
        $this->assertTrue (in_array ('testuser', $assignees));
        $this->assertTrue (in_array ('admin', $assignees));
        
    }

    public function testCreateNotification () {
        // assigned to testuser and group 1
        $action = $this->actions('action6');

        $notifs = $action->createNotifications ('assigned');
        VERBOSE_MODE && print (sizeof ($notifs));
        $this->assertTrue (sizeof ($notifs) === 2);
        $notifAssignees = array_map (function ($a) { return $a->user; }, $notifs);
        $this->assertTrue (in_array ('admin', $notifAssignees));
        $this->assertTrue (in_array ('testuser', $notifAssignees));

        $notifs = $action->createNotifications ('me');
        $this->assertTrue (sizeof ($notifs) === 1);
        $notifAssignees = array_map (function ($a) { return $a->user; }, $notifs);
        VERBOSE_MODE && print ($notifAssignees);
        $this->assertTrue (in_array ('Guest', $notifAssignees));

        $notifs = $action->createNotifications ('both');
        $this->assertTrue (sizeof ($notifs) === 3);
        $notifAssignees = array_map (function ($a) { return $a->user; }, $notifs);
        $this->assertTrue (in_array ('admin', $notifAssignees));
        $this->assertTrue (in_array ('testuser', $notifAssignees));
        $this->assertTrue (in_array ('Guest', $notifAssignees));
    }

    public function testChangeCompleteState () {
        TestingAuxLib::login ('admin', 'admin');
        VERBOSE_MODE && print (Yii::app()->user->name ."\n");
        VERBOSE_MODE && print ((int) Yii::app()->params->isAdmin);
        VERBOSE_MODE && print ("\n");
        $action = $this->actions('action6');
        $completedNum = Actions::changeCompleteState ('complete', array ($action->id));
        $this->assertTrue ($completedNum === 1);
        $action = Actions::model()->findByPk ($action->id);
        VERBOSE_MODE && print ($action->complete."\n");
        $this->assertTrue ($action->complete === 'Yes');
        Actions::changeCompleteState ('uncomplete', array ($action->id));
        $action = Actions::model()->findByPk ($action->id);
        $this->assertTrue ($action->complete === 'No');

    }
}

?>
