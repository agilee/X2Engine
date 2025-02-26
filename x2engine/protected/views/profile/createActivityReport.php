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

$this->actionMenu = array(
    array('label' => Yii::t('profile', 'View Profile'), 'url' => array('view', 'id' => Yii::app()->user->getId())),
    array('label' => Yii::t('profile', 'Edit Profile'), 'url' => array('update', 'id' => Yii::app()->user->getId())),
    array('label' => Yii::t('profile', 'Change Settings'), 'url' => array('settings'),),
    array('label' => Yii::t('profile', 'Change Password'), 'url' => array('changePassword', 'id' => Yii::app()->user->getId())),
    array('label' => Yii::t('profile', 'Manage Apps'), 'url' => array('manageCredentials'))
);
?>
<div class="page-title icon profile"><h2><?php echo Yii::t('profile', 'Create Activity Feed Report Email'); ?></h2></div>
<div class="form">
    <div style="width:600px;">
        <?php
        echo Yii::t('profile', 'This form will allow you to create a periodic email with information about events in the Activity Feed.') . "<br>";
        echo Yii::t('profile', 'The filters you had checked on the previous page will be used to determine which content to give you information about.') . "<br>";
        echo Yii::t('profile', 'Please note that this report will not function if you do not have the Cron service turned on, please check with your administrator if you are unsure.');
        ?>
    </div>
    <div class='form'>
        <?php echo CHtml::form(); ?>
        <?php echo '<h3>' . Yii::t('profile', 'Report Settings') . '</h3>'; ?>
        <span style='float:left;'>
            <?php echo CHtml::label('Date Range', 'range'); ?>
            <?php
            echo CHtml::dropDownList('range', 'daily', array(
                'daily' => 'Daily',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
            ));
            ?>
        </span>
        <span style='float:left;margin-left:20px;'>
            <?php
            Yii::import('application.extensions.CJuiDateTimePicker.CJuiDateTimePicker');
            echo CHtml::label('Hour', 'hour');
            $this->widget('CJuiDateTimePicker', array(
                'name' => 'hour', //attribute name
                'value' => Formatter::formatTime(strtotime('9 AM')),
                'mode' => 'time', //use "time","date" or "datetime" (default)
                'options' => array(
                    'timeFormat' => Formatter::formatTimePicker(),
                ),
            ));
            ?>
        </span>
        <span style='float:left;margin-left:20px;'>
            <?php echo CHtml::label('Limit', 'limit'); ?>
            <?php echo CHtml::textField('limit', '10'); ?>
        </span>
        <?php echo CHtml::hiddenField('filters', $filters); ?>
        <?php echo CHtml::hiddenField('userId', Yii::app()->user->getId()); ?>
        <?php echo CHtml::submitButton('Create',array(
            'class'=>'x2-button',
            'style'=>'clear:both;'
        )); ?>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
