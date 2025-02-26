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
Yii::app()->clientScript->registerScriptFile(
    Yii::app()->getBaseUrl().'/js/multiselect/js/ui.multiselect.js');
Yii::app()->clientScript->registerCssFile(
    Yii::app()->getBaseUrl().'/js/multiselect/css/ui.multiselect.css');
Yii::app()->clientScript->registerCss('manageRolesCss',"
#content {
    border: none; background: none; 
}
.multiselect {
	width: 460px;
	height: 200px;
}
#switcher {
	margin-top: 20px;
}

#roles-grid-container {
    padding-bottom: 5px;
}

#set-session-timeout-row {
    margin: 5px 0;
}


");

?>
<div id='roles-grid-container' class='x2-layout-island'>
<?php

$this->widget('zii.widgets.grid.CGridView', array(
	'id'=>'roles-grid',
	'baseScriptUrl'=>Yii::app()->request->baseUrl.'/themes/'.Yii::app()->theme->name.
        '/css/gridview',
	'template'=> '<div class="page-title"><h2>'.
        Yii::t('admin','Role List').'</h2><div class="title-bar">'
		.'{summary}</div></div>{items}{pager}',
		'summaryText'=>Yii::t('app','<b>{start}&ndash;{end}</b> of <b>{count}</b>'),
	'dataProvider'=>$dataProvider,
	'columns'=>array(
		'name',
        array(
            'name'=>'timeout',
            'value'=>'isset($data->timeout)? $data->timeout / 60 : null',
            'header'=>Yii::t('admin', 'Session Timeout')
        ),
	),
)); ?>
<br>
<a style='margin-left: 5px;' href="#" onclick="$('#addRole').toggle();$('#deleteRole').hide();$('#editRole').hide();$('#exception').hide();" class="x2-button">Add Role</a>
<a href="#" onclick="$('#deleteRole').toggle();$('#addRole').hide();$('#editRole').hide();$('#exception').hide();" class="x2-button">Delete Role</a>
<a href="#" onclick="$('#editRole').toggle();$('#addRole').hide();$('#deleteRole').hide();$('#exception').hide();" class="x2-button">Edit Role</a>
<a href="#" onclick="$('#exception').toggle();$('#addRole').hide();$('#deleteRole').hide();$('#editRole').hide();" class="x2-button">Add Exception</a>
<br>
</div>
<br>
<div id="addRole"<?php if(!$model->hasErrors()) echo ' style="display:none;"';?> class='x2-layout-island'>
<?php $this->renderPartial('roleEditor',array(
    'model'=>$model,
)); ?>
</div>

<div id="deleteRole" style="display:none;" class='x2-layout-island'>
<?php $this->renderPartial('deleteRole',array(
    'roles'=>$roles,
)); ?>
</div>

<div id="editRole" style="display:none;" class='x2-layout-island'>
<?php $this->renderPartial('editRole',array(
    'model'=>$model,
)); ?>
</div>
<div id="exception" style="display:none;" class='x2-layout-island'>
<?php $this->renderPartial('roleException',array(
    'model'=>$model,
    'workflows'=>$workflows,
)); ?>
</div>
