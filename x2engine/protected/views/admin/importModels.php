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

?>
<div class="page-title"><h2><?php echo Yii::t('admin','Import {model} from Template', array('{model}'=>X2Model::getModelTitle ($model))); ?></h2></div>
<div class="form">

<?php if(!empty($errors)){
    echo "<span class='error' style='font-weight:bold'>".Yii::t('admin',$errors)."</span>";
    unset($_SESSION['errors']);
} ?>

<?php if (!empty($model)) { ?>
    <div id="info-box" style="width:600px;">
    <?php echo Yii::t('admin','To import your records, please fill out a CSV file where the first row contains the column headers for your records (e.g. first_name, last_name, title etc.).  A properly formatted example can be found below.'); ?>
    <br><br>
    <?php echo Yii::t('admin','The application will attempt to automatically map your column headers to our fields in the database.  If a match is not found, you will be given the option to choose one of our fields to map to, ignore the field, or create a new field within X2.'); ?>
    <br><br>
    <?php echo Yii::t('admin','If you decide to map the "Create Date", "Last Updated", or any other explicit date field, be sure that you have a valid date format entered so that the software can convert to a UNIX Timestamp (if it is already a UNIX Timestamp even better).  Visibility should be either "1" for Public or "0" for Private (it will default to 1 if not provided).'); ?>

    <br><br><?php echo Yii::t('admin','Example');?> <a href="#" id="example-link">[+]</a>
    <div id="example-box" style="display:none;"><img src="<?php echo Yii::app()->getBaseUrl()."/images/examplecsv.png" ?>"/></div>
    <br><br>
    </div>
    <div class="form" style="width:600px;">
<?php
    unset($_SESSION['model']);
    echo "<h3>".Yii::t('admin','Upload File')."</h3>";
    echo CHtml::form('importModels','post',array('enctype'=>'multipart/form-data','id'=>'importModels'));
    echo CHtml::fileField('data', '', array('id'=>'data'))."<br>";
    echo CHtml::hiddenField('model', $model);
    echo "<i>".Yii::t('app','Allowed filetypes: .csv')."</i><br><br>";
    echo "<h3>".Yii::t('admin', 'Upload Import Map')." <a href='#' id='toggle-map-upload'>[+]</a></h3>";
    echo "<div id='upload-map' style='display:none;'>";
    echo CHtml::fileField('mapping', '', array('id'=>'mapping'))."<br>";
    echo "<i>".Yii::t('app','Allowed filetypes: .json')."</i>";
    echo "</div><br><br>";
    echo CHtml::submitButton(Yii::t('app','Submit'),array('class'=>'x2-button'));
    echo CHtml::endForm();
    echo "</div>";
} else {
    echo "<h3>".Yii::t('admin','Please select a module to import records into.')."</h3>";
    foreach ($modelList as $class => $modelName) {
        echo CHtml::link($modelName, array('/admin/importModels', 'model'=>$class))."<br />";
    }
}
?>

</div>
<script>
    $('#example-link').click(function(){
       $('#example-box').toggle(); 
    });
    $('#toggle-map-upload').click(function() {
        $('#upload-map').toggle();
    });
    $(document).on('submit','#importModels',function(){
        var fileName=$("#data").val();
        var pieces=fileName.split('.');
        var ext=pieces[pieces.length-1];
        if(ext!='csv'){
            $("#data").val("");
            alert("File must be a .csv file.");
            return false;
        }
        var mapfileName = $('#mapping').val();
        if (mapfileName != '') {
            var pieces = mapfileName.split('.');
            var ext = pieces[pieces.length - 1];
            if (ext != 'json'){
                $('#mapping').val("");
                alert('Map file must be a .json file.');
                return false;
            }
        }
    });
</script>
    
