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
 * Widget class for the relationships form.
 *
 * Relationships lists the relationships a model has with other models,
 * and provides a way to add existing models to the models relationships.
 *
 * @package application.components 
 */
class InlineRelationships extends X2Widget {

	public $model = null;
	public $startHidden = false;
	public $modelName = "";
    public $moduleName = "";

    /**
     * Used to prepopulate create relationship forms
     * @var array (<model class> => <array of default values indexed by attr name>)
     */
    public $defaultsByRelatedModelType = array ();

	private $_relatedModels;

	public function init(){
		parent::init();
	}

    private function checkModuleUpdatePermissions () {
        $moduleName = '';
        if (is_object (Yii::app()->controller->module)) {
            $moduleName = Yii::app()->controller->module->name;
        } 
        $actionAccess = ucfirst($moduleName).'Update';
        $authItem = Yii::app()->authManager->getAuthItem($actionAccess);
        return (!isset($authItem) || Yii::app()->user->checkAccess($actionAccess, array(
            'X2Model' => $this->model
        )));
    }

	public function run(){
        $linkableModels = X2Model::getModelTypesWhichSupportRelationships(true);

        // used to instantiate html dropdown
        $linkableModelsOptions = $linkableModels;
        //array_walk ($linkableModelsOptions, function (&$val, $key) { $val = $key; });

        $modelsWhichSupportQuickCreate = 
            QuickCreateRelationshipBehavior::getModelsWhichSupportQuickCreate ();

        // get create action urls for each linkable model
        $createUrls = QuickCreateRelationshipBehavior::getCreateUrlsForModels (
            $modelsWhichSupportQuickCreate);

        // get create relationship tooltips for each linkable model
        $tooltips = QuickCreateRelationshipBehavior::getDialogTooltipsForModels (
            $modelsWhichSupportQuickCreate, $this->modelName);

        // get create relationship dialog titles for each linkable model
        $dialogTitles = QuickCreateRelationshipBehavior::getDialogTitlesForModels (
            $modelsWhichSupportQuickCreate);

        $hasUpdatePermissions = $this->checkModuleUpdatePermissions ();

		$this->render('inlineRelationships', array(
			'model' => $this->model,
			'modelName' => $this->model->myModelName,
			'startHidden' => $this->startHidden,
            'moduleName' => $this->moduleName,
            'linkableModelsOptions' => $linkableModelsOptions,
            'dialogTitles' => $dialogTitles,
            'tooltips' => $tooltips,
            'createUrls' => $createUrls,
            'defaultsByRelatedModelType' => $this->defaultsByRelatedModelType,
            'modelsWhichSupportQuickCreate' => $modelsWhichSupportQuickCreate,
            'hasUpdatePermissions' => $hasUpdatePermissions
		));
	}


}

?>
