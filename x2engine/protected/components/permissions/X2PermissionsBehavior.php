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

Yii::import('application.components.util.AuxLib');

/**
 * CModelBehavior class for permissions lookups on classes.
 *
 * X2PermissionsBehavior is a CModelBehavior which allows consistent lookup of
 * access levels and whether or not a user is allowed to view or edit a record.
 *
 * @property string $assignmentAttr Name of the attribute to use for permissions
 * @property string $visibilityAttr Name of the attribute to use for visibility setting
 * @package application.components.permissions
 * @author Jake Houser <jake@x2engine.com>, Demitri Morgan <demitri@x2engine.com>
 */
class X2PermissionsBehavior extends ModelPermissionsBehavior {

    /**
     * The access level for administrators.
     *
     * All records, public and private, will be included in indexes and searches.
     */
    const QUERY_ALL = 3;
    /**
     * The access level for users granted general access.
     *
     * All records marked public or viewable to groupmates (providing the user
     * in question shares a group in common with the assignee(s)) will be
     * included in indexes and searches.
     */
    const QUERY_PUBLIC = 2;
    /**
     * The access level for users granted "private" access.
     *
     * Only records assigned to the user in question, or assigned to the user's
     * groups, will be included.
     */
    const QUERY_SELF = 1;

    /**
     * The access level for users granted no access.
     *
     * No records will be retrieved.
     */
    const QUERY_NONE = 0;

    /**
     * This visibility value implies "private"; ordinarily visible only to
     * assignee(s)/owner(s) of the record
     */
    const VISIBILITY_PRIVATE = 0;

    /**
     * This visibility setting implies the record is public/shared, and anyone
     * can view.
     */
    const VISIBILITY_PUBLIC = 1;

    /**
     * This visibility setting implies that the record is visible to the owners
     * and other members of groups to which the owners belong ("groupmates").
     */
    const VISIBILITY_GROUPS = 2;

    private $_assignmentAttr;
    private $_visibilityAttr;
    
    /**
     * "Caches" whether the assignment applies to a given user, for each user.
     *
     * Keyed with usernames; values are boolean for whether the assignment applies.
     * @var type
     */
    private $_isAssignedTo = array();

    /**
     * Similar to {@link _isAssignedTo} but for visibility, which also utilizes
     * the visibility setting on the model.
     * @var type
     */
    private $_isVisibleTo = array();

    /**
     * Returns a CDbCriteria containing record-level access conditions.
     * @return CDbCriteria
     */
    public function getAccessCriteria(){
        $criteria = new CDbCriteria;
        $accessLevel = $this->getAccessLevel();
        $conditions=$this->getAccessConditions($accessLevel);
        foreach($conditions as $arr){
            $criteria->addCondition($arr['condition'],$arr['operator']);
            if(!empty($arr['params']))
                $criteria->params = array_merge($criteria->params,$arr['params']);
        }
        
        return $criteria;
    }

    /**
     * Returns a number from 0 to 3 representing the current user's access level using the Yii 
     * auth manager.
     * Assumes authItem naming scheme like "ContactsViewPrivate", etc.
     * This method probably ought to overridden, as there is no reliable way to determine the 
     * module a model "belongs" to.
     * @return integer The access level. 0=no access, 1=own records, 2=public records, 3=full access
     */
    public function getAccessLevel(){
        $module = ucfirst($this->owner->module);

        if(Yii::app()->isInSession){ // Web request
            $uid = Yii::app()->user->id;
        }else{ // User session not available; doing an operation through API or console
            $uid = Yii::app()->getSuID();
        }
        $accessLevel = self::QUERY_NONE;
        if(Yii::app()->authManager->checkAccess($module.'Admin', $uid)){
            if($accessLevel < self::QUERY_ALL)
                $accessLevel = self::QUERY_ALL;
        }elseif(Yii::app()->authManager->checkAccess($module.'ReadOnlyAccess', $uid)){
            if($accessLevel < self::QUERY_PUBLIC)
                $accessLevel = self::QUERY_PUBLIC;
        }elseif(Yii::app()->authManager->checkAccess($module.'PrivateReadOnlyAccess', $uid)){
            if($accessLevel < self::QUERY_SELF)
                $accessLevel = self::QUERY_SELF;
        }
        return $accessLevel;
    }

    /**
     * Resolves/returns the assignment attribute to use in permission checks
     * @return type
     */
    public function getAssignmentAttr(){
        if(!isset($this->_assignmentAttr)){
            $this->_assignmentAttr = false;
            if($this->owner->hasAttribute('assignedTo')){
                return $this->_assignmentAttr = 'assignedTo';
            }elseif($this->owner->hasAttribute('createdBy')){
                return $this->_assignmentAttr = 'createdBy';
            }elseif($this->owner instanceof X2Model){
                $fields = $this->owner->getFields();
                foreach($fields as $field){
                    // Use the first assignment field available:
                    if($field->type == 'assignment'){
                        $assignAttr = $field->fieldName;
                        return $this->_assignmentAttr = $field->fieldName;
                    }
                }
            }
        }
        return $this->_assignmentAttr;
    }

    /**
     * Resolves/returns the visibility attribute to use in permission checks
     */
    public function getVisibilityAttr(){
        if(!isset($this->_visibilityAttr)){
            $this->_visibilityAttr = false;
            if($this->owner->hasAttribute('visibility')) {
                return $this->_visibilityAttr = 'visibility';
            } elseif($this->owner instanceof X2Model) {
                $fields = $this->owner->getFields();
                foreach($fields as $field){
                    // Use the first assignment field available:
                    if($field->type == 'visibility'){
                        $assignAttr = $field->fieldName;
                        return $this->_visibilityAttr = $field->fieldName;
                    }
                }
            }
        }
        return $this->_visibilityAttr;
    }
    
    /**
     * Returns visibility dropdown menu options.
     * @return type
     */
    public static function getVisibilityOptions() {
        return array(
            self::VISIBILITY_PUBLIC => Yii::t('app', 'Public'),
            self::VISIBILITY_PRIVATE => Yii::t('app', 'Private'),
            self::VISIBILITY_GROUPS => Yii::t('app', 'User\'s Groups')
        );
    }

    /**
     * Generates SQL condition to filter out records the user doesn't have
     * permission to see.
     *
     * This method is used by the 'accessControl' filter.
     *
     * @param Integer $accessLevel The user's access level. 0=no access, 1=own
     *  records, 2=public records, 3=full access
     * @return String The SQL conditions
     */
    public function getAccessConditions($accessLevel){
        $user = Yii::app()->getSuModel()->username;
        $userId = Yii::app()->getSuModel()->id;
        $assignmentAttr = $this->getAssignmentAttr();
        $visibilityAttr = $this->getVisibilityAttr();
        
        if($assignmentAttr)
            list($assignedToCondition,$params) = $this->getAssignedToCondition(false,'t');

        if($accessLevel === self::QUERY_PUBLIC && $visibilityAttr === false) // level 2 access only works if we consider visibility,
            $accessLevel = self::QUERY_ALL;  // so upgrade to full access
        $ret = array();
        switch($accessLevel){
            case self::QUERY_ALL:
                // User can view everything
                $ret[] = array('condition'=>'TRUE', 'operator'=>'AND','params'=>array());
                break;
            case self::QUERY_PUBLIC:
                // User can view any public (shared) record
                if($visibilityAttr != false){
                    $ret[] = array(
                        'condition' => "t.$visibilityAttr=".self::VISIBILITY_PUBLIC,
                        'operator' => 'OR',
                        'params' => array()
                    );
                }
                // Made visible among the user(s)' groupmates via "User's Groups"
                // visibility setting:
                $groupmatesRegex = self::getGroupmatesRegex();
                if(!empty($groupmatesRegex)){
                    $ret[] = array(
                        'condition' => "(t.$visibilityAttr=".self::VISIBILITY_GROUPS.' '
                        ."AND t.$assignmentAttr REGEXP BINARY :groupmatesRegex)",
                        'operator' => 'OR',
                        'params' => array(
                            ':groupmatesRegex' => $groupmatesRegex
                        ),
                    );
                }
            // Continue to case 1 for group visibility / assignment
            case 1:
                // User can view records they (or one of their groups) own or
                // have permission to view
                if($assignmentAttr != false){
                    $ret[] = array(
                        'condition' => $assignedToCondition,
                        'operator' => 'OR',
                        'params' => $params
                    );
                }
                // Visible to user groups:
                $groupRegex = self::getGroupIdRegex();
                if(!empty($groupRegex)){
                    $ret[] = array(
                        'condition' => "(t.$assignmentAttr REGEXP BINARY :visibilityGroupIdRegex)",
                        'operator' => 'OR',
                        'params' => array(
                            ':visibilityGroupIdRegex' => $groupRegex
                        )
                    );
                }
                break;
            case 0:  // can't view anything
            default:
                $ret[] = array('condition'=>'FALSE', 'operator'=>'AND');
        }
        return $ret;
    }

    /**
     * Checks assignment list, including membership to groups in assignment list
     *
     * @param string $username The username of the user for which to check assignment
     * @param bool $excludeAnyone If true, isAssignedTo will not return true if
     *  the record is assigned to anyone or no one.
     * @return bool true of action is assigned to specified user, false otherwise
     */
    public function isAssignedTo ($username, $excludeAnyone=false) {
        if(isset($this->_isAssignedTo[$username][$excludeAnyone]))
            return $this->_isAssignedTo[$username][$excludeAnyone];
        if(!$this->assignmentAttr) // No way to determine assignment
            return true;

        $user = $username === Yii::app()->getSuName()
                ? Yii::app()->getSuModel()
                : User::model ()->findByAttributes (array (
                    'username' => $username
                  ));

        if (!($user instanceof User)) {
            throw new CException (Yii::t('app', 'Invalid username'));
        }

        $isAssignedTo = false;
        $assignees = explode(', ',$this->owner->getAttribute($this->assignmentAttr));
        $groupIds = array_filter($assignees,'ctype_digit');
        $usernames = array_diff($assignees,$groupIds);
        
        // Check for individual assignment (or "anyone" if applicable):
        foreach ($usernames as $assignee) {
            if ($assignee === 'Anyone' || (sizeof ($assignees) === 1 && $assignee === '')) {
                if (!$excludeAnyone) {
                    $isAssignedTo = true;
                    break;
                } else {
                    continue;
                }
            } else if ($assignee === $username) {
                $isAssignedTo = true;
                break;
            }
        }

        // Check for group assignment:
        if(!$isAssignedTo && !empty($groupIds)) {
            $userGroupsAssigned = array_intersect($groupIds,Groups::getUserGroups($user->id));
            if(!empty($userGroupsAssigned)) {
                $isAssignedTo = true;
            }
        }
        $this->_isAssignedTo[$username][$excludeAnyone] = $isAssignedTo;
        return $isAssignedTo;
    }

    /**
     * Uses the visibility attribute and the assignment of the model to determine
     * if a given named user has permission to view it.
     *
     * This property is superseded by the "PrivateReadOnlyAccess" class of 
     * permission items, which are intended as restriction of users to view only
     * items to which they are assigned.
     *
     * Rather, this determines the default visibility if there are no special
     * restrictions in place.
     * 
     * @param string $username The username of the user for which to check visibility
     * @param boolean $excludeAnyone Whether to avoid counting assignment "Anyone"
     *  as assignment to the current user. The accepted behavior is that if
     *  assignment is "Anyone" and visibility is private (0), non-admin users
     *  should not be able to see the record, hence the default for this is true.
     * @return type
     */
    public function isVisibleTo($username, $excludeAnyone = true){
        if(!isset($this->_isVisibleTo[$username][$excludeAnyone])){
            $this->_isVisibleTo[$username][$excludeAnyone] =
                // Visible if assigned to current user
                $this->isAssignedTo($username, $excludeAnyone)
                // Visible if there is no visibility attribute
                || !(bool) $this->visibilityAttr
                || ( // Visibility setting in the model permits viewing
                    // Visible if marked "public"
                    $this->owner->getAttribute($this->visibilityAttr) == 1 
                    || (
                        // Visible if marked with visibility "Users' Groups"
                        // and the current user has groups in common with
                        // assignees of the current user:
                        $this->owner->getAttribute($this->visibilityAttr) == 2
                        && (bool) $this->assignmentAttr // Assignment attribute must exist
                        && (bool) ($groupmatesRegex = self::getGroupmatesRegex())
                        && preg_match('/'.$groupmatesRegex.'/',
                                $this->owner->getAttribute($this->assignmentAttr))
                    )
                );
        }
        return $this->_isVisibleTo[$username][$excludeAnyone];
    }

    /**
     * Returns SQL condition which can be used to determine if an action is assigned to the
     *  current user.
     * @param bool $includeAnyone If true, SQL condition will evaluate to true for actions assigned
     *  to anyone or no one.
     * @return array array (<SQL condition string>, <array of parameters>)
     */
    public function getAssignedToCondition ($includeAnyone=true,$alias=null) {
        $prefix = empty($alias)?'':"$alias.";
        $groupIdsRegex = self::getGroupIdRegex();
        $condition =
            "(". ($includeAnyone ? ($prefix.$this->assignmentAttr."='Anyone' OR assignedTo='' OR ") : '').
             $prefix.$this->assignmentAttr." REGEXP BINARY :userNameRegex";
        $params = array (
            ':userNameRegex' => self::getUserNameRegex (),
        );
        if ($groupIdsRegex !== '') {
            $condition .= " OR $prefix".$this->assignmentAttr." REGEXP BINARY :groupIdsRegex";
            $params[':groupIdsRegex'] = $groupIdsRegex;
        }
        $condition .= ')';
        return array ($condition, $params);
    }

    /**
     * Determines all users to whom a record is assigned.
     * 
     * @param bool $getUsernamesFromGroups If true, usernames of all users in groups whose ids
     *  are in the assignedTo string will also be returned
     * @return array assignees of this action
     */
    public function getAssignees ($getUsernamesFromGroups = false) {
        $assignment = $this->owner->getAttribute($this->getAssignmentAttr());
        $assignees = !is_array($assignment)
                ? explode (', ', $assignment)
                : $assignment;

        $assigneesNames = array ();

        if($getUsernamesFromGroups){
            // Obtain usernames from the groups assignment table
            $groupIds = array_filter($assignees, 'ctype_digit');
            if(!empty($groupIds)){
                
                $groupIdParam = AuxLib::bindArray($groupIds);
                $groupUsers = Yii::app()->db->createCommand()
                        ->select('username')
                        ->from('x2_group_to_user')
                        ->where('groupId IN '.AuxLib::arrToStrList(array_keys($groupIdParam)),$groupIdParam)
                        ->queryColumn();
                foreach($groupUsers as $username)
                    $assigneesNames[] = $username;
            }
        }
        foreach($assignees as $assignee){
            if($assignee === 'Anyone'){
                continue;
            }else if(!ctype_digit($assignee)) {
                // Not a group ID but a username
                if(CActiveRecord::model('Profile')->exists('username=:u',array(
                        ':u' => $assignee))){
                    $assigneesNames[] = $assignee;
                }
            }
        }

        return array_unique ($assigneesNames);
    }

    /**
     * Returns regex for performing SQL assignedTo field comparisons.
     * @return string This can be inserted (with parameter binding) into SQL queries to
     *  determine if an action is assigned to a given group.
     */
    public static function getGroupIdRegex () {
        $groupIds = Groups::getUserGroups(Yii::app()->getSuId());
        $groupIdRegex = '';
        $i = 0;
        foreach ($groupIds as $id) {
            if ($i++ > 0) $groupIdRegex .= '|';
            $groupIdRegex .= '((^|, )'.$id.'($|,))';
        }
        return $groupIdRegex;
    }

    /**
     * Regular expression for matching against a list of users
     *
     * @param array $userNames
     */
    public static function getUsernameListRegex($usernames) {
        return '(^|, )('.implode('|',$usernames).')($|, )';
    }

    public static function getGroupmatesRegex() {
        $groupmates = Groups::getGroupmates(Yii::app()->getSuId());
        return empty($groupmates)?null:self::getUsernameListRegex($groupmates);
    }

    /*
     * Returns regex for performing SQL assignedTo field comparisons.
     * @return string This can be inserted (with parameter binding) into SQL queries to
     *  determine if an action is assigned to a given user.
     */
    public static function getUserNameRegex ($username=null) {
        return '(^|, )'.($username===null?Yii::app()->getSuName():$username).'($|, )';
    }



}

?>
