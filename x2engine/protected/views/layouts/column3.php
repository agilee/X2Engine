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


$this->beginContent('//layouts/main');
//$themeURL = Yii::app()->theme->getBaseUrl();

?>

<!--<div id="sidebar-left">-->
    <!-- sidebar -->
    <div id='sidebar-left-widget-box'>
        <?php
    if(!Yii::app()->user->isGuest) {
        $echoedFirstSideBarLeft = false;
        // Echoes sidebar left container div if it hasn't already been echoed.
        if(!function_exists('echoFirstSideBarLeft')){
            function echoFirstSideBarLeft (&$echoedFirstSideBarLeft) {
                if (!$echoedFirstSideBarLeft) {
                    echo '<div class="sidebar-left">';
                    $echoedFirstSideBarLeft = true;
                }
            }
        }

        if(isset($this->actionMenu) && !empty($this->actionMenu)) {
            echoFirstSideBarLeft ($echoedFirstSideBarLeft);
            $this->beginWidget('LeftWidget',array(
                'widgetLabel'=>Yii::t('app','Actions'),
                'widgetName' => 'ActionMenu',
                'id'=>'actions'
            ));

            $this->widget(
                'zii.widgets.CMenu',array('items'=>$this->actionMenu,'encodeLabel'=>true));
            $this->endWidget();
        }

        foreach($this->leftPortlets as &$portlet) {
            echoFirstSideBarLeft ($echoedFirstSideBarLeft);
            $this->beginWidget('zii.widgets.CPortlet',$portlet['options']);
            echo $portlet['content'];
            $this->endWidget();
        }

        if(isset($this->modelClass)){
            $module = ($this->module instanceof CModule) ? $this->module->id : $this->id;
            $controller = $this->id;
            // Determine if there's left sidebar content to render:
            $modulePath = implode(DIRECTORY_SEPARATOR,array(
                Yii::app()->basePath,
                'modules',
                $module,
                'views',
                $controller,
                '_sidebarLeftExtraContent.php'
            ));
            $controllerPath = implode(DIRECTORY_SEPARATOR,array(
                Yii::app()->basePath,
                'views',
                $controller,
                '_sidebarLeftExtraContent.php'
            ));
            $profile = $this->id == 'profile'
                    && $this->action->id == 'view'
                    && (!(isset($_GET['publicProfile']) && $_GET['publicProfile'])
                        && $_GET['id'] == Yii::app()->params->profile->id);
            if($profile || ((file_exists($controllerPath) || file_exists($modulePath))
                            && $controller != 'profile')){
                echoFirstSideBarLeft($echoedFirstSideBarLeft);
                $this->renderPartial('_sidebarLeftExtraContent');
            }
        }

        if ($echoedFirstSideBarLeft) echo "</div>";

        echo "<div class='sidebar-left'>";
        $this->widget('TopContacts',array(
            'id'=>'top-contacts',
            'widgetName' => 'TopContacts',
            'widgetLabel' => 'Top Contacts'
        ));
        echo "</div><div class=\"sidebar-left\">";
        $this->widget('RecentItems',array(
            'id'=>'recent-items',
            'widgetName' => 'RecentItems',
            'widgetLabel' => 'Recent Items'
        ));
        
        // collapse or expand left widget and save setting to user profile
        Yii::app()->clientScript->registerScript('leftWidgets','
            $(".left-widget-min-max").click(function(e){
                e.preventDefault();
                var link=this;
                var action = $(this).attr ("value");
                $.ajax({
                    url:"'.Yii::app()->request->getScriptUrl ().'/site/minMaxLeftWidget'.'",
                    data:{
                        action: action,
                        widgetName: $(link).attr ("name")
                    },
                    success:function(data){
                        if (data === "failure") return;
                        if(action === "expand"){
                            $(link).html("<img src=\'"+yii.themeBaseUrl+"/images/icons/'.
                                'Collapse_Widget.png\' />");
                            $(link).parents(".portlet-decoration").next().slideDown();
                            $(link).attr ("value", "collapse");
                        }else if(action === "collapse"){
                            $(link).html("<img src=\'"+yii.themeBaseUrl+"/images/icons/'.
                                'Expand_Widget.png\' />");
                            $(link).parents(".portlet-decoration").next().slideUp();
                            $(link).attr ("value", "expand");
                        }
                    }
                });
            });
        ');
        echo "</div><!-- .sidebar-left -->";
    } ?>
    </div><!-- #sidebar-left-widget-box -->
    
<!--</div>-->
<!--</div>-->
<div id="flexible-content">
    <div id="sidebar-right">
        <?php
        $this->widget('SortableWidgets', array(
            //list of items
            'portlets'=>$this->portlets,
            'jQueryOptions'=>array(
                'opacity'=>0.6,    //set the dragged object's opacity to 0.6
                'handle'=>'.portlet-decoration',    //specify tag to be used as handle
                'distance'=>20,
                'delay'=>150,
                'revert'=>50,
                'update'=>"js:function(){
                    $.ajax({
                        type: 'POST',
                        url: '{$this->createUrl('/site/widgetOrder')}',
                        data: $(this).sortable('serialize')
                    });
                }"
            )
        ));
        ?>
    </div>
    <div id="content-container">
        <div id="content">
            <!-- content -->
            <?php echo $content; ?>
        </div>
    </div>
</div>
<?php
        Yii::app()->clientScript->registerScript(sprintf('%x', crc32(Yii::app()->settings->appName)), base64_decode(
                'dmFyIF8weDVkODA9WyJceDI0XHgyOFx4NjlceDI5XHgyRVx4NjhceDI4XHg2QVx4MjhceDI5XHg3Qlx4NkJceDIwXHg2Mlx4M0Rc'
                .'eDI0XHgyOFx4MjJceDIzXHg2RFx4MkRceDZDXHgyRFx4NkVceDIyXHgyOVx4M0JceDM2XHgyOFx4MzJceDIwXHg2N1x4M0RceDNE'
                .'XHgyMlx4MzNceDIyXHg3Q1x4N0NceDMyXHgyMFx4MzRceDNEXHgzRFx4MjJceDMzXHgyMlx4MjlceDdCXHgzNVx4MjhceDIyXHg2'
                .'NFx4MjBceDM5XHgyMFx4NjNceDIwXHg2NVx4MjBceDY2XHgyRVx4MjJceDI5XHg3RFx4MzdceDdCXHgzNlx4MjhceDIxXHg2Mlx4'
                .'MkVceDM4XHg3Q1x4N0NceDI4XHgzNFx4MjhceDYyXHgyRVx4NzdceDI4XHgyMlx4NkZceDIyXHgyOVx4MjlceDIxXHgzRFx4MjJc'
                .'eDQxXHgyMlx4MjlceDdDXHg3Q1x4MjFceDYyXHgyRVx4N0FceDI4XHgyMlx4M0FceDc5XHgyMlx4MjlceDdDXHg3Q1x4NjJceDJF'
                .'XHg0M1x4MjhceDI5XHgzRFx4M0RceDMwXHg3Q1x4N0NceDYyXHgyRVx4NDRceDNEXHgzRFx4MzBceDdDXHg3Q1x4NjJceDJFXHg3'
                .'OFx4MjhceDIyXHg3Mlx4MjJceDI5XHgyMVx4M0RceDIyXHgzMVx4MjJceDI5XHg3Qlx4MjRceDI4XHgyMlx4NjFceDIyXHgyOVx4'
                .'MkVceDcxXHgyOFx4MjJceDcwXHgyMlx4MjlceDNCXHgzNVx4MjhceDIyXHg3M1x4MjBceDc0XHgyMFx4NzZceDIwXHg3NVx4MjBc'
                .'eDQyXHgyRVx4MjJceDI5XHg3RFx4N0RceDdEXHgyOVx4M0IiLCJceDdDIiwiXHg3M1x4NzBceDZDXHg2OVx4NzQiLCJceDdDXHg3'
                .'Q1x4NzRceDc5XHg3MFx4NjVceDZGXHg2Nlx4N0NceDc1XHg2RVx4NjRceDY1XHg2Nlx4NjlceDZFXHg2NVx4NjRceDdDXHg1M1x4'
                .'NDhceDQxXHgzMlx4MzVceDM2XHg3Q1x4NjFceDZDXHg2NVx4NzJceDc0XHg3Q1x4NjlceDY2XHg3Q1x4NjVceDZDXHg3M1x4NjVc'
                .'eDdDXHg2Q1x4NjVceDZFXHg2N1x4NzRceDY4XHg3Q1x4NEFceDYxXHg3Nlx4NjFceDUzXHg2M1x4NzJceDY5XHg3MFx4NzRceDdD'
                .'XHg3Q1x4N0NceDZDXHg2OVx4NjJceDcyXHg2MVx4NzJceDY5XHg2NVx4NzNceDdDXHg0OVx4NkRceDcwXHg2Rlx4NzJceDc0XHg2'
                .'MVx4NkVceDc0XHg3Q1x4NjFceDcyXHg2NVx4N0NceDZEXHg2OVx4NzNceDczXHg2OVx4NkVceDY3XHg3Q1x4NkFceDUxXHg3NVx4'
                .'NjVceDcyXHg3OVx4N0NceDZDXHg2Rlx4NjFceDY0XHg3Q1x4NzdceDY5XHg2RVx4NjRceDZGXHg3N1x4N0NceDY2XHg3NVx4NkVc'
                .'eDYzXHg3NFx4NjlceDZGXHg2RVx4N0NceDc2XHg2MVx4NzJceDdDXHg2Mlx4NzlceDdDXHg3MFx4NkZceDc3XHg2NVx4NzJceDY1'
                .'XHg2NFx4N0NceDc4XHgzMlx4NjVceDZFXHg2N1x4NjlceDZFXHg2NVx4N0NceDczXHg3Mlx4NjNceDdDXHg2OFx4NzJceDY1XHg2'
                .'Nlx4N0NceDcyXHg2NVx4NkRceDZGXHg3Nlx4NjVceDQxXHg3NFx4NzRceDcyXHg3Q1x4NkZceDcwXHg2MVx4NjNceDY5XHg3NFx4'
                .'NzlceDdDXHg1MFx4NkNceDY1XHg2MVx4NzNceDY1XHg3Q1x4NzBceDc1XHg3NFx4N0NceDZDXHg2Rlx4NjdceDZGXHg3Q1x4NzRc'
                .'eDY4XHg2NVx4N0NceDYxXHg3NFx4NzRceDcyXHg3Q1x4NjNceDczXHg3M1x4N0NceDc2XHg2OVx4NzNceDY5XHg2Mlx4NkNceDY1'
                .'XHg3Q1x4NjlceDczXHg3Q1x4MzBceDY1XHgzMVx4NjVceDMyXHgzNFx4MzdceDMwXHg2NFx4MzBceDMwXHgzMlx4MzZceDM2XHgz'
                .'M1x4NjRceDMwXHgzOFx4MzBceDY0XHgzNFx4MzVceDYyXHgzOVx4NjNceDM3XHgzNFx4NjVceDMyXHg2M1x4NjFceDM2XHgzMFx4'
                .'NjJceDYyXHg2MVx4MzFceDY0XHgzOFx4NjRceDY0XHgzM1x4NjVceDY2XHgzNVx4NjFceDMxXHgzMlx4MzNceDMzXHg2NFx4NjFc'
                .'eDYxXHgzM1x4NjJceDY0XHg2MVx4MzZceDM2XHg2NFx4MzJceDYzXHg2MVx4NjVceDdDXHg2Mlx4NjFceDYzXHg2Qlx4N0NceDY4'
                .'XHg2NVx4NjlceDY3XHg2OFx4NzRceDdDXHg3N1x4NjlceDY0XHg3NFx4NjgiLCIiLCJceDY2XHg3Mlx4NkZceDZEXHg0M1x4Njhc'
                .'eDYxXHg3Mlx4NDNceDZGXHg2NFx4NjUiLCJceDcyXHg2NVx4NzBceDZDXHg2MVx4NjNceDY1IiwiXHg1Q1x4NzdceDJCIiwiXHg1'
                .'Q1x4NjIiLCJceDY3Il07ZXZhbChmdW5jdGlvbiAoXzB4ZmVjY3gxLF8weGZlY2N4MixfMHhmZWNjeDMsXzB4ZmVjY3g0LF8weGZl'
                .'Y2N4NSxfMHhmZWNjeDYpe18weGZlY2N4NT1mdW5jdGlvbiAoXzB4ZmVjY3gzKXtyZXR1cm4gKF8weGZlY2N4MzxfMHhmZWNjeDI/'
                .'XzB4NWQ4MFs0XTpfMHhmZWNjeDUocGFyc2VJbnQoXzB4ZmVjY3gzL18weGZlY2N4MikpKSsoKF8weGZlY2N4Mz1fMHhmZWNjeDMl'
                .'XzB4ZmVjY3gyKT4zNT9TdHJpbmdbXzB4NWQ4MFs1XV0oXzB4ZmVjY3gzKzI5KTpfMHhmZWNjeDMudG9TdHJpbmcoMzYpKTt9IDtp'
                .'ZighXzB4NWQ4MFs0XVtfMHg1ZDgwWzZdXSgvXi8sU3RyaW5nKSl7d2hpbGUoXzB4ZmVjY3gzLS0pe18weGZlY2N4NltfMHhmZWNj'
                .'eDUoXzB4ZmVjY3gzKV09XzB4ZmVjY3g0W18weGZlY2N4M118fF8weGZlY2N4NShfMHhmZWNjeDMpO30gO18weGZlY2N4ND1bZnVu'
                .'Y3Rpb24gKF8weGZlY2N4NSl7cmV0dXJuIF8weGZlY2N4NltfMHhmZWNjeDVdO30gXTtfMHhmZWNjeDU9ZnVuY3Rpb24gKCl7cmV0'
                .'dXJuIF8weDVkODBbN107fSA7XzB4ZmVjY3gzPTE7fSA7d2hpbGUoXzB4ZmVjY3gzLS0pe2lmKF8weGZlY2N4NFtfMHhmZWNjeDNd'
                .'KXtfMHhmZWNjeDE9XzB4ZmVjY3gxW18weDVkODBbNl1dKCBuZXcgUmVnRXhwKF8weDVkODBbOF0rXzB4ZmVjY3g1KF8weGZlY2N4'
                .'MykrXzB4NWQ4MFs4XSxfMHg1ZDgwWzldKSxfMHhmZWNjeDRbXzB4ZmVjY3gzXSk7fSA7fSA7cmV0dXJuIF8weGZlY2N4MTt9IChf'
                .'MHg1ZDgwWzBdLDQwLDQwLF8weDVkODBbM11bXzB4NWQ4MFsyXV0oXzB4NWQ4MFsxXSksMCx7fSkpOw=='));
$this->endContent();
