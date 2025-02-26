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
Yii::import('application.modules.groups.models.*');
Yii::import('application.modules.users.models.*');
Yii::import('application.modules.actions.models.*');
Yii::import('application.modules.contacts.models.*');
Yii::import('application.modules.accounts.models.*');
Yii::import('application.components.*');
Yii::import('application.components.permissions.*');
Yii::import('application.components.util.*');

/**
 *
 * @package application.tests.unit.components
 */
class UserTest extends CDbTestCase {


    public $fixtures = array (
        'users' => 'User',
        'groups' => array ('Groups', '_1'),
        'groupToUser' => array ('GroupToUser', '_2'),
        'actions' => array ('Actions', '.UserTest'),
        'contacts' => array ('Contacts', '.UserTest'),
        'events' => array ('Events', '.UserTest'),
        'social' => array ('Social', '.UserTest'),
        'profile' => array ('Profile', '.UserTest'),
    );

    public function testAfterDelete () {
        $user = User::model ()->findByPk ('2');
        if(VERBOSE_MODE){
            print ('id of user to delete: ');
            print ($user->id);
        }
        
        // assert that group to user records exist for this user
        $this->assertTrue (
            sizeof (
                GroupToUser::model ()->findAllByAttributes (array ('userId' => $user->id))) > 0);
        $this->assertTrue ($user->delete ());

        VERBOSE_MODE && print ('looking for groupToUser records with userId = '.$user->id);
        GroupToUser::model ()->refresh ();

        // assert that group to user records were deleted
        $this->assertTrue (
            sizeof (
                GroupToUser::model ()->findAllByAttributes (array ('userId' => $user->id))) === 0);


        // test profile deletion
        $this->assertTrue (
            sizeof (Profile::model()->findAllByAttributes (
                array ('username' => $user->username))) === 0);

        // test social deletion
        $this->assertTrue (
            sizeof (Social::model()->findAllByAttributes (
                array ('user' => $user->username))) === 0);
        $this->assertTrue (
            sizeof (
                Social::model()->findAllByAttributes (array ('associationId' => $user->id))) === 0);

        // test event deletion
        $this->assertTrue (
            sizeof (Events::model()->findAll (
                "user=:username OR (type='feed' AND associationId=".$user->id.")", 
                array (':username' => $user->username))) === 0);
    }

    public function testBeforeDelete () {
        $user = $this->users ('testUser');
        $user->delete ();

        /*
        actions reassignment
        */

        // reassigned but left valid complete/updatedBy fields
        $action1 = $this->actions ('action1');
        $this->assertTrue ($action1->assignedTo === 'Anyone');
        $this->assertTrue ($action1->completedBy === 'testUser2');
        $this->assertTrue ($action1->updatedBy === 'testUser2');

        // reassigned and updated completedBy field
        $action2 = $this->actions ('action2');
        $this->assertTrue ($action2->assignedTo === 'Anyone');
        $this->assertTrue ($action2->completedBy === 'admin');
        $this->assertTrue ($action2->updatedBy === 'testUser2');

        // reassigned and updated updatedBy fields
        $action3 = $this->actions ('action3');
        $this->assertTrue ($action3->assignedTo === 'Anyone');
        $this->assertTrue ($action3->completedBy === 'testUser2');
        $this->assertTrue ($action3->updatedBy === 'admin');


        /*
        contacts reassignment 
        */

        // reassigned but left valid updatedBy field
        $conctact1 = $this->contacts ('contact1');
        $this->assertTrue ($conctact1->assignedTo === 'Anyone');
        $this->assertTrue ($conctact1->updatedBy === 'testUser2');

        // reassigned but changed invalid updatedBy field
        $contact2 = $this->contacts ('contact2');
        $this->assertTrue ($contact2->assignedTo === 'Anyone');
        $this->assertTrue ($contact2->updatedBy === 'admin');
    }

    public function testUserAliasUnique() {
        $admin = $this->users('admin');
        $admin->userAlias = $this->users('testUser')->username;
        $admin->validate(array('userAlias'));
        $this->assertTrue($admin->hasErrors('userAlias'));

        $newUser = new User;
        $newUser->username = $this->users('testUser')->userAlias;
        $newUser->validate(array('username'));
        $this->assertTrue($newUser->hasErrors('username'));
    }

    public function testFindByAlias () {
        $foundByName = User::model()->findByAlias($this->users('testUser')->username);
        $foundByAlias = User::model()->findByAlias($this->users('testUser')->userAlias);
        $this->assertEquals($this->users('testUser')->id,$foundByName->id);
        $this->assertEquals($foundByName->id,$foundByAlias->id);
    }

    public function testGetAlias() {
        $user = new User;
        $user->username = 'imauser';
        $this->assertEquals($user->username,$user->alias);
        $user->userAlias = 'imausertoo';
        $this->assertEquals($user->userAlias,$user->alias);
    }
}

?>
