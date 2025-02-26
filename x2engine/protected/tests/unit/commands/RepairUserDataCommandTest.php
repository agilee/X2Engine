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
class RepairUserDataCommandTest extends CDbTestCase {


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

    public function testConstantSet () {
        // must be set to true so that the command uses the test database
        $this->assertEquals (true, YII_UNIT_TESTING);
    }

    /**
     * Performs user deletion. Asserts that former user's data is corrupt. Repairs data with
     * command line script. Finally, checks are made to ensure that script successfully repaired
     * data.
     */
    public function testCommand () {
        $user = $this->users ('testUser');
        Yii::app()->db->createCommand (
            'delete from x2_users where username="testUser"'
        );

        /*
        actions reassignment
        */

        // reassigned but left valid complete/updatedBy fields
        $action1 = $this->actions ('action1');
        $this->assertTrue ($action1->assignedTo === 'testUser');

        // reassigned and updated completedBy field
        $action2 = $this->actions ('action2');
        $this->assertTrue ($action2->assignedTo === 'testUser');
        $this->assertTrue ($action2->completedBy === 'testUser');
        $this->assertFalse ($action2->updatedBy === 'testUser');

        // reassigned and updated updatedBy fields
        $action3 = $this->actions ('action3');
        $this->assertTrue ($action3->assignedTo === 'testUser');
        $this->assertFalse ($action3->completedBy === 'testUser');
        $this->assertTrue ($action3->updatedBy === 'testUser');

        $action4 = $this->actions ('action4');
        $this->assertTrue ($action4->assignedTo === 'testUser2');
        $this->assertTrue ($action4->updatedBy === 'testUser');
        $this->assertTrue ($action4->updatedBy === 'testUser');


        /*
        contacts reassignment 
        */

        // reassigned but left valid updatedBy field
        $contact1 = $this->contacts ('contact1');
        $this->assertFalse ($contact1->assignedTo === 'Anyone');

        // reassigned but changed invalid updatedBy field
        $contact2 = $this->contacts ('contact2');
        $this->assertFalse ($contact2->assignedTo === 'Anyone');
        $this->assertFalse ($contact2->updatedBy === 'admin');

        $contact3 = $this->contacts ('contact3');
        $this->assertTrue ($contact3->assignedTo === 'testUser2');
        $this->assertTrue ($contact3->updatedBy === 'testUser');

        $return_var;
        $output = array ();
        VERBOSE_MODE && println (exec ("../yiic repairuserdata repair --username='testUser'", $return_var, $output));
        VERBOSE_MODE && println ($output);
        VERBOSE_MODE && print_r ($return_var);

        /*
        actions reassignment
        */

        // reassigned but left valid complete/updatedBy fields
        $action1->refresh ();
        $this->assertEquals ('Anyone', $action1->assignedTo);
        $this->assertTrue ($action1->completedBy === 'testUser2');
        $this->assertTrue ($action1->updatedBy === 'testUser2');

        // reassigned and updated completedBy field
        $action2->refresh ();
        $this->assertTrue ($action2->assignedTo === 'Anyone');
        $this->assertTrue ($action2->completedBy === 'admin');
        $this->assertTrue ($action2->updatedBy === 'testUser2');

        // reassigned and updated complete/updatedBy fields
        $action3->refresh ();
        $this->assertTrue ($action3->assignedTo === 'Anyone');
        $this->assertTrue ($action3->completedBy === 'testUser2');
        $this->assertTrue ($action3->updatedBy === 'admin');

        // should be left untouched
        $action4->refresh ();
        $this->assertTrue ($action4->assignedTo === 'testUser2');
        $this->assertTrue ($action4->updatedBy === 'testUser');
        $this->assertTrue ($action4->updatedBy === 'testUser');


        /*
        contacts reassignment 
        */

        // reassigned but left valid updatedBy field
        $contact1->refresh ();
        $this->assertTrue ($contact1->assignedTo === 'Anyone');
        $this->assertTrue ($contact1->updatedBy === 'testUser2');

        // reassigned but changed invalid updatedBy field
        $contact2->refresh ();
        $this->assertTrue ($contact2->assignedTo === 'Anyone');
        $this->assertTrue ($contact2->updatedBy === 'admin');

        // should be left untouched
        $contact3->refresh ();
        $this->assertTrue ($contact3->assignedTo === 'testUser2');
        $this->assertTrue ($contact3->updatedBy === 'testUser');
    }
}

?>
