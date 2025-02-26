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
<script>
var record=0;
</script>
<style>
    .clean-link{
        text-decoration:none;
    }
</style>
<?php
    if (isset($model))
        unset($_SESSION['model']);
?>
<div class="page-title"><h2><?php echo Yii::t('admin','{model} Import', array('{model}'=>X2Model::getModelTitle ($model))); ?></h2></div>
<div class="form" >
<div style="width:600px">
<?php
if ($preselectedMap) {
    echo Yii::t('admin', 'You have selected to upload and use the following import mapping: ')."<br><br>";
    echo "<table>";
    echo "<tr>";
    echo "<td><strong>".Yii::t('admin','Your Field')."</strong></td>";
    echo "<td><strong>".Yii::t('admin','Our Field')."</strong></td>";
    echo "</tr>";
    foreach ($importMap as $key => $val) {
        echo "<tr>";
        echo "<td style='width: 50%'>".$key."</td>";
        echo "<td style='width: 50%'>".$val."</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo Yii::t('admin',"First, we'll need to make sure your fields have mapped properly for import. ");
    echo Yii::t('admin','Select the fields you wish to map. Fields that have been detected as matching an existing field have been selected. ').'<br /><br />';
    echo Yii::t('admin','If the ID field is selected to be imported, the import tool will attempt to overwrite pre-existing records with that ID.  Do not map the ID field if you don\'t want this to happen.').'<br /><br />';
    echo Yii::t('admin','Fields that are not selected will not be mapped. To override a mapping, select the appropriate field from the corresponding drop down. If you believe a field was detected incorrectly, deselect it below, then manually override the mapping.');
}
?>

</div></<div><br /></div>
<div id="import-container" class='form'>
<div id="super-import-map-box">
<h2> <span class="import-hide">Import Map</span></h2>
<div id="import-map-box" class="import-hide form" style="width:600px">
    <div id="form-error-box" style="color:red">

    </div>

<?php
    Yii::app()->clientScript->registerCssFile(Yii::app()->getBaseUrl().'/js/multiselect/css/ui.multiselect.css','screen, projection');
    Yii::app()->clientScript->registerScriptFile(Yii::app()->getBaseUrl().'/js/multiselect/js/ui.multiselect.js');
    Yii::app()->clientScript->registerScript('renderMultiSelect',"
        $('.multiselect').multiselect();
    ",CClientScript::POS_READY);
    $fields = array();
    $selected = array();
    $newFields = array();
    foreach($meta as $attribute) {
        if (isset($importMap[$attribute])) {
            // Automatically detected fields
            $fields[$attribute] = $importMap[$attribute];
            $selected[] = $attribute;
        } else {
            // New fields
            $fields[$attribute] = $attribute;
            $newFields[] = $attribute;
        }
    }
    echo CHtml::dropDownList('attributes', $selected, $fields, array(
        'id' => 'importMapping',
        'class' => 'multiselect',
        'multiple' => 'multiple',
        'size' => 8,
        'style'=> 'width: 460px; height: 200px;',
    ));
?>

<br />
<?php
    echo CHtml::link(Yii::t('admin', 'Export Mapping'), '#', array('id'=>'export-map', 'class'=>'x2-button'));
    echo CHtml::link(Yii::t('acmin', 'Download Mapping'), '#', array('id'=>'download-map', 'class'=>'x2-button', 'style'=>'display:none'));
?>

<h2><a href='#' class='clean-link' onclick="$('#mapping-overrides').toggle();">[-] </a><?php echo Yii::t('admin', 'Override Mappings'); ?></h2>
<div id='mapping-overrides' style='display: none'>
<?php echo Yii::t('admin','Below is a list of our fields, the fields you provided, and a few sample records that you are importing. ');?>
<?php echo Yii::t('admin','Selecting "DO NOT MAP" will ignore the field and use the settings chosen above. Selecting "CREATE NEW FIELD" will generate a new text field within X2 and map your field to it. ') ?>
<?php echo Yii::t('admin','This override takes precedence over the selector above.') ?>
<br /><br />
<table id="import-map" >
    <tr>
        <td><strong><?php echo Yii::t('admin','Your Field');?></strong></td>
        <td><strong><?php echo Yii::t('admin','Our Field');?></strong></td>
        <td><strong><?php echo Yii::t('admin','Sample Record');?></strong> <a href="#" class="clean-link" onclick="prevRecord();"><?php echo Yii::t('admin','[Prev]');?></a> <a href="#" class="clean-link" onclick="nextRecord();"><?php echo Yii::t('admin','[Next]');?></a></td>
    </tr>
<?php
    foreach($meta as $attribute){
        echo "<tr>";
        echo "<td style='width:33%'>$attribute</td>";
        echo "<td style='width:33%'>".CHtml::dropDownList($attribute,
                isset($importMap[$attribute])?$importMap[$attribute]:'',
                array_merge(array(''=>Yii::t('admin','DO NOT MAP'),'createNew'=>Yii::t('admin','CREATE NEW FIELD')),X2Model::model($model)->attributeLabels()),
                array('class'=>'import-attribute')
                )."</td>";
        echo "<td style='width:33%'>";
        for($i=0;$i<5;$i++){
            if(isset($sampleRecords[$i])){
                if($i>0){
                    echo "<span class='record-$i' id='record-$i-$attribute' style='display:none;'>".$sampleRecords[$i][$attribute]."</span>";
                }else{
                    echo "<span class='record-$i' id='record-$i-$attribute'>".$sampleRecords[$i][$attribute]."</span>";
                }
            }
        }
        echo "</td>";
        echo "</tr>";
    }


?>
</table>
</div>
<br />
</div>
</div>
<br /><br />
<h2><?php echo Yii::t('admin','Process Import Data'); ?></h2>
<div class="form" style="width:600px">
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"This will attempt to create a record for any field that links to another record type (e.g. Account)"),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Create records for link fields?'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('create-records-box','checked');?></div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"These tags will be applied to any record created by the import. Example: web,newlead,urgent."),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('marketing','Tags'); ?></strong></div>
        <div class="cell"><?php echo CHtml::textField('tags'); ?></div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"These fields will be applied to all imported records and override their respective mapped fields from the import."),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Automatically fill certain fields?'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('fill-fields-box');?></div>

        <div id="fields" class="row" style="display:none;">
            <div>
                <div id="field-box">

                </div>
            </div>
            &nbsp;&nbsp;&nbsp;&nbsp;<a href="#" id="add-link" class="clean-link">[+]</a>
        </div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"Anything entered here will be created as a comment and logged as an Action in the imported record's history."),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Automatically log a comment on these records?'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('log-comment-box');?></div>
        <div class="row">
            <div id="comment-form" style="display:none;">
                <div class="text-area-wrapper" >
                    <textarea name="comment" id="comment" style="height:70px;"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"If this box is checked, all records will be assigned to users based on your lead routing settings."),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Assign records via lead-routing?'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('lead-routing-box');?></div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin',"If this box is checked, the activity feed will not be populated with the new records."),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Skip posting new records to activity feed?'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('activity-feed-box');?></div>
    </div>
    <div class="row">
        <div class="cell"><?php echo X2Html::hint(Yii::t('admin','If checked, certain shortcuts will be taken to increase the speed of the import, which includes skipping the "record created" X2Flow trigger.'
                . ' Leaving this option enabled is highly recommended, especially for large data sets.'),false); ?></div>
        <div class="cell"><strong><?php echo Yii::t('admin','Performance mode'); ?></strong></div>
        <div class="cell"><?php echo CHtml::checkBox('performance-mode','checked');?></div>
    </div>
</div>
<br /><br />
<?php echo CHtml::link(Yii::t('admin',"Process Import"),"#",array('id'=>'process-link','class'=>'x2-button highlight'));?>
<br /><br />
</div>
<h3 id="import-status" style="display:none;"><?php echo Yii::t('admin','Import Status'); ?></h3>
<div id="prep-status-box" style="color:green">

</div>
<br />
<div id="status-box" style="color:green">

</div>
<div id="failures-box" style="color:red">

</div>
<script>
    $(function() {
        // Hide the import map box if a mapping was uploaded
        if (<?php echo ($preselectedMap)? 'true':'false'; ?>)
            $('#super-import-map-box').hide();
    });
    
    var attributeLabels = <?php echo json_encode(X2Model::model($model)->attributeLabels(), false);?>;
    $('#process-link').click(function(){
       prepareImport();
    });
    $('#fill-fields-box').change(function(){
        $('#fields').toggle();
    });
    $('#log-comment-box').change(function(){
       $('#comment-form').toggle();
    });



    function prepareImport(){
        $('#import-container').hide();
        var attributes=[];
        var keys=[];
        var forcedAttributes=[];
        var forcedValues=[];
        var comment="";
        var routing=0;
        var skipActivityFeed=0;
        var newFields = <?php echo CJSON::encode($newFields); ?>;
        $('#importMapping').find(":checked").each(function(){
            keys.push($(this).val());
            if (jQuery.inArray($(this).text(), newFields) != -1)
                attributes.push('createNew');
            else
                attributes.push($(this).text());
        });
        $('.import-attribute').each(function(){
            if ($(this).val() != '') {
                // Add mapping overrides that are not marked 'DO NOT MAP'
                attributes.push($(this).val());
                keys.push($(this).attr('name'));
            }
        });
        if($('#fill-fields-box').attr('checked')=='checked'){
            $('.forced-attribute').each(function(){
            forcedAttributes.push($(this).val());
            });
            $('.forced-value').each(function(){
                forcedValues.push($(this).val());
            });
        }
        if($('#log-comment-box').attr('checked')=='checked'){
            comment=$("#comment").val();
        }
        if($('#lead-routing-box').attr('checked')=='checked'){
            routing=1;
        }
        if($('#activity-feed-box').attr('checked')=='checked'){
            skipActivityFeed=1;
        }
        $.ajax({
            url:'prepareModelImport',
            type:"POST",
            data:{
                attributes:attributes,
                keys:keys,
                forcedAttributes:forcedAttributes,
                forcedValues:forcedValues,
                createRecords:$('#create-records-box').attr('checked')=='checked'?'checked':'',
                tags:$('#tags').val(),
                comment:comment,
                routing:routing,
                skipActivityFeed:skipActivityFeed,
                model:"<?php echo $model; ?>",
                preselectedMap:<?php echo ($preselectedMap)? 'true' : 'false'; ?>
            },
            success:function(data){
                data=JSON.parse(data);
                if (data[0]==0) {
                    $('#import-status').show();
                    var str="Import setup completed successfully...<br />Beginning import.";
                    importData(25);
                    $('#prep-status-box').html(str);
                } else if(data[0]==1) {
                    var str="Import preparation failed.  Failed to create the following fields: ";
                    str = str + data[1] + "<br /><br />";
                    $('#import-container').show();
                    $('#form-error-box').html(str);
                } else if (data[0]==2) {
                    var str="Import preparation failed.  The following fields already exist: ";
                    str = str + data[1] + "<br /><br />";
                    $('#import-container').show();
                    $('#form-error-box').html(str);
                } else if(data[0]==3) {
                    $('#import-status').show();
                    $('#import-container').show();
                    var str="Import Preparation failed. The following required fields were not mapped: ";
                    str = str + data[1] + "<br /><br />";
                    $('#form-error-box').html(str);
                }
            },
            error:function(){
                var str="Import preparation failed.  Aborting import.";
                $('#prep-status-box').css({'color':'red'});
                $('#prep-status-box').html(str);
            }
        });
    }
    function importData(count){
        $.ajax({
            url:'importModelRecords',
            type:"POST",
            data:{
                count:count,
                model:"<?php echo $model; ?>"
            },
            success:function(data){
                data=JSON.parse(data);
                if(data[0]!=1){
                    str=data[1]+" <b><?php echo $model; ?></b> have been successfully imported.";
                    created=JSON.parse(data[3]);
                    for(type in created){
                        if(created[type]>0){
                            str+="<br />"+created[type]+" <b>"+type+"</b> were created and linked to <?php echo $model; ?>.";
                        }
                    }
                    $('#status-box').html(str);
                    if(data[2]>0){
                        str=data[2]+" <b><?php echo $model; ?></b> have failed validation and were not imported.";
                        $("#failures-box").html(str);
                    }
                    importData(count);
                }else{
                    str=data[1]+" <b><?php echo $model; ?></b> have been successfully imported.";
                    created=JSON.parse(data[3]);
                    for(type in created){
                        if(created[type]>0){
                            str+="<br />"+created[type]+" <b>"+type+"</b> were created and linked to <?php echo $model; ?>.";
                        }
                    }
                    $('#status-box').html(str);
                    if(data[2]>0){
                        str=data[2]+" <b><?php echo $model; ?></b> have failed validation and were not imported. Click here to recover them: <a href=\"#\" id=\"download-link\" class=\"x2-button\">Download</a>";
                        $("#failures-box").html(str);
                        $('#download-link').click(function(e) {
                            e.preventDefault();  //stop the browser from following
                            window.location.href = '<?php echo $this->createUrl('/admin/downloadData',array('file'=>'failedRecords.csv')); ?>';
                        });
                    }
                    $.ajax({
                        url:'cleanUpModelImport',
                        complete:function(){
                            var str="<strong>Import Complete.</strong>";
                            $('#prep-status-box').html(str);
                            alert('Import Complete!');
                        }
                    });
                }
            }
        });
    }
    function prevRecord(){
        $('.record-'+record).hide();
        if(record==0){
            record=4;
        }else{
            record--;
        }
        $('.record-'+record).show();
    }

    function nextRecord(){
        $('.record-'+record).hide();
        if(record==4){
            record=0;
        }else{
            record++;
        }
        $('.record-'+record).show();
    }

    function createDropdown(list, ignore) {
        var sel = $(document.createElement('select'));
        $.each(list, function(key, value) {
            if ($.inArray(key, ignore) == -1) {
                sel.append('<option value=\"' + key  + '\">' + value + '</option>');
            }
        });
        return sel;
    }

    function createAttrCell(){
        var div = $(document.createElement('div'));
        div.attr('class', 'field-row');
        var dropdown = createDropdown(attributeLabels);
        dropdown.attr('class', 'forced-attribute');
        var input = $('<input size="30" type="text" value="" class="forced-value">');
        input.attr('name', 'force-values[]');
        var link= $('<a href="#" class="del-link clean-link">[x]</a>');
        return div.append(dropdown).append(input).append(link);
    }
    $('#add-link').click(function(e){
       e.preventDefault();
       $('#field-box').append(createAttrCell());
       $('.del-link').click(function(e){
            e.preventDefault();
            $(this).closest('.field-row').remove();;
        });
    });

    $('#export-map').click(function() {
        var keys = new Array();
        var attributes = new Array();
        var newFields = <?php echo CJSON::encode($newFields) ?>;
        $('#importMapping').find(':checked').each(function(){
            keys.push($(this).val());
            if (jQuery.inArray($(this).text(), newFields) != -1)
                attributes.push('createNew');
            else
                attributes.push($(this).text());
        });
        $('.import-attribute').each(function(){
            if ($(this).val() != '') {
                // Add mapping overrides that are not marked 'DO NOT MAP'
                attributes.push($(this).val());
                keys.push($(this).attr('name'));
            }
        });
        $.ajax({
            url: 'exportMapping',
            type: 'POST',
            data: {
                model: "<?php echo $model; ?>",
                attributes: attributes,
                keys: keys
            },
            success: function() {
                $('#download-map').show();
            },
            error: function() {
                var str="Preparing the import map failed.  Aborting.";
                $('#prep-status-box').css({'color':'red'});
                $('#prep-status-box').html(str);
            }
        });
    });
    $('#download-map').click(function(e) {
        e.preventDefault();
        window.location.href = '<?php echo $this->createUrl('admin/downloadData', array('file'=>'importMapping.json')) ?>';
    });
</script>
