<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

/**
 * This hook gives a chance to prepend content or to replace the default view content with a custom content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * In case the content is replaced, make sure to set {@CAttributeCollection $collection->renderContent} to false 
 * in order to stop rendering the default content.
 * @since 1.3.3.1
 */
$hooks->doAction('before_view_file_content', $viewCollection = new CAttributeCollection(array(
    'controller'    => $this,
    'renderContent' => true,
)));

// and render if allowed
if ($viewCollection->renderContent) { ?>
    <div class="pull-left">
        <?php $this->widget('customer.components.web.widgets.MailListSubNavWidget', array(
            'list' => $list,
        ))?>
    </div>
    <div class="clearfix"><!-- --></div>
    <hr />
    <?php 
    /**
     * This hook gives a chance to prepend content before the active form or to replace the default active form entirely.
     * Please note that from inside the action callback you can access all the controller view variables 
     * via {@CAttributeCollection $collection->controller->data}
     * In case the form is replaced, make sure to set {@CAttributeCollection $collection->renderForm} to false 
     * in order to stop rendering the default content.
     * @since 1.3.3.1
     */
    $hooks->doAction('before_active_form', $collection = new CAttributeCollection(array(
        'controller'    => $this,
        'renderForm'    => true,
    )));
    
    // and render if allowed
    if ($collection->renderForm) {
        $form = $this->beginWidget('CActiveForm'); 
        ?>
        <div class="box box-primary borderless">
            <div class="box-header">
                <div class="pull-left">
                    <?php BoxHeaderContent::make(BoxHeaderContent::LEFT)
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-cog') . $pageHeading . '</h3>')
                        ->render();
                    ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(CHtml::link(IconHelper::make('create') . Yii::t('app', 'Create new'), array('list_segments/create', 'list_uid' => $list->list_uid), array('class' => 'btn btn-primary btn-flat', 'title' => Yii::t('app', 'Create new'))), !$segment->isNewRecord)
                        ->add(CHtml::link(IconHelper::make('cancel') . Yii::t('app', 'Cancel'), array('list_segments/index', 'list_uid' => $list->list_uid), array('class' => 'btn btn-primary btn-flat', 'title' => Yii::t('app', 'Cancel'))))
                        ->addIf(CHtml::link(IconHelper::make('export') . Yii::t('list_export', 'Export segment'), array('list_segments_export/index', 'list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid), array('target' => '_blank', 'class' => 'btn btn-primary btn-flat', 'title' => Yii::t('list_export', 'Export segment'))), !$segment->isNewRecord && !empty($canExport))
                        ->render();
                    ?>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-body">
                <?php 
                /**
                 * This hook gives a chance to prepend content before the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables 
                 * via {@CAttributeCollection $collection->controller->data}
                 * @since 1.3.3.1
                 */
                $hooks->doAction('before_active_form_fields', new CAttributeCollection(array(
                    'controller'    => $this,
                    'form'          => $form    
                )));
                ?>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($segment, 'name');?>
                            <?php echo $form->textField($segment, 'name', $segment->getHtmlOptions('name')); ?>
                            <?php echo $form->error($segment, 'name');?>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <?php echo $form->labelEx($segment, 'operator_match');?>
                            <?php echo $form->dropDownList($segment, 'operator_match', $segment->getOperatorMatchArray(), $segment->getHtmlOptions('operator_match')); ?>
                            <?php echo $form->error($segment, 'operator_match');?>
                        </div>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="conditions-container">
                        <div class="col-lg-12">
                            <h5>
                                <div class="pull-left">
                                    <?php echo Yii::t('list_segments', 'Conditions that apply to your list custom fields:');?>
                                </div>
                                <div class="pull-right">
                                    <a href="javascript:;" class="btn btn-primary btn-flat btn-add-condition"><?php echo IconHelper::make('create');?></a>
                                    <a href="#conditions-value-tags" data-toggle="modal" class="btn btn-primary btn-flat"><?php echo IconHelper::make('info');?></a>
                                </div>
                                <div class="clearfix"><!-- --></div>
                            </h5>
                        </div>
                        <?php if (!empty($conditions)) { foreach ($conditions as $index => $cond) {?>
                        <div class="item">
                            <div class="col-lg-3">
                                <?php echo CHtml::activeLabelEx($cond, 'field_id');?>
                                <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][field_id]', $cond->field_id, $segment->getFieldsDropDownArray(), $cond->getHtmlOptions('field_id')); ?>
                                <?php echo CHtml::error($cond, 'field_id');?>
                            </div>
                            <div class="col-lg-3">
                                <?php echo CHtml::activeLabelEx($cond, 'operator_id');?>
                                <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][operator_id]', $cond->operator_id, $cond->getOperatorsDropDownArray(), $cond->getHtmlOptions('operator_id')); ?>
                                <?php echo CHtml::error($cond, 'operator_id');?>
                            </div>
                            <div class="col-lg-3">
                                <?php echo CHtml::activeLabelEx($cond, 'value');?>
                                <?php echo CHtml::textField($cond->modelName.'['.$index.'][value]', $cond->value, $cond->getHtmlOptions('value')); ?>
                                <?php echo CHtml::error($cond, 'value');?>
                            </div>
                            <div class="col-lg-3">
                                <label><?php echo Yii::t('app', 'Action');?></label><br />
                                <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete');?></a>
                            </div>
                            <div class="clearfix"><!-- --></div>
                        </div>
                        <?php }} ?>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="campaigns-conditions-container">
                        <div class="col-lg-12">
                            <h5>
                                <div class="pull-left">
						            <?php echo Yii::t('list_segments', 'Conditions that apply to the campaigns sent to the list this segment belongs to:');?>
                                </div>
                                <div class="pull-right">
                                    <a href="javascript:;" class="btn btn-primary btn-flat btn-add-campaign-condition"><?php echo IconHelper::make('create');?></a>
                                </div>
                                <div class="clearfix"><!-- --></div>
                            </h5>
                        </div>
			            <?php if (!empty($campaignConditions)) { foreach ($campaignConditions as $index => $cond) {?>
                            <div class="item">
                                <div class="col-lg-3">
						            <?php echo CHtml::activeLabelEx($cond, 'action');?>
						            <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][action]', $cond->action, $cond->getActionsList(), $cond->getHtmlOptions('action')); ?>
						            <?php echo CHtml::error($cond, 'action');?>
                                </div>
                                <div class="col-lg-3">
						            <?php echo CHtml::activeLabelEx($cond, 'campaign_id');?>
						            <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][campaign_id]', $cond->campaign_id, $cond->getCampaignsList($list->list_id), $cond->getHtmlOptions('campaign_id')); ?>
						            <?php echo CHtml::error($cond, 'campaign_id');?>
                                </div>
                                <div class="col-lg-5">
                                    <div class="row">
                                        <div class="col-lg-4">
		                                    <?php echo CHtml::activeLabelEx($cond, 'time_comparison_operator');?>
		                                    <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][time_comparison_operator]', $cond->time_comparison_operator, $cond->getTimeComparisonOperatorsList(), $cond->getHtmlOptions('time_comparison_operator')); ?>
		                                    <?php echo CHtml::error($cond, 'time_comparison_operator');?>
                                        </div>
                                        <div class="col-lg-4">
		                                    <?php echo CHtml::activeLabelEx($cond, 'time_value');?>
		                                    <?php echo CHtml::textField($cond->modelName.'['.$index.'][time_value]', $cond->time_value, $cond->getHtmlOptions('time_value')); ?>
		                                    <?php echo CHtml::error($cond, 'time_value');?>
                                        </div>
                                        <div class="col-lg-4">
		                                    <?php echo CHtml::activeLabelEx($cond, 'time_unit');?>
		                                    <?php echo CHtml::dropDownList($cond->modelName.'['.$index.'][time_unit]', $cond->time_unit, $cond->getTimeUnitsList(), $cond->getHtmlOptions('time_unit')); ?>
		                                    <?php echo CHtml::error($cond, 'time_unit');?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-1">
                                    <label><?php echo Yii::t('app', 'Action');?></label><br />
                                    <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-campaign-condition"><?php echo IconHelper::make('delete');?></a>
                                </div>
                                <div class="clearfix"><!-- --></div>
                            </div>
			            <?php }} ?>
                    </div>
                </div>
                <hr />
                <div class="row">
                    <div class="col-lg-12">
                        <div class="subscribers-wrapper" style="display: none;">
                            <h5><?php echo Yii::t('list_segments', 'Subscribers matching your segment:');?></h5>
                            <hr />
                            <div id="subscribers-wrapper"></div>
                        </div>
                    </div>
                </div>
                <?php 
                /**
                 * This hook gives a chance to append content after the active form fields.
                 * Please note that from inside the action callback you can access all the controller view variables 
                 * via {@CAttributeCollection $collection->controller->data}
                 * @since 1.3.3.1
                 */
                $hooks->doAction('after_active_form_fields', new CAttributeCollection(array(
                    'controller'    => $this,
                    'form'          => $form    
                )));
                ?>
                <div class="clearfix"><!-- --></div>
            </div>
            <div class="box-footer">
                <div class="pull-right">
                    <?php if (!$segment->isNewRecord && (!empty($conditions) || !empty($campaignConditions))) { ?>
                    <a href="<?php echo $this->createUrl('list_segments/subscribers', array('list_uid' => $list->list_uid, 'segment_uid' => $segment->segment_uid));?>" class="btn btn-primary btn-flat btn-show-segment-subscribers"><?php echo IconHelper::make('view') . Yii::t('app', 'Show matching subscribers');?></a>
                    <?php } ?>
                    <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . Yii::t('app', 'Save changes');?></button>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <?php 
        $this->endWidget(); 
    } 
    /**
     * This hook gives a chance to append content after the active form fields.
     * Please note that from inside the action callback you can access all the controller view variables 
     * via {@CAttributeCollection $collection->controller->data}
     * @since 1.3.3.1
     */
    $hooks->doAction('after_active_form', new CAttributeCollection(array(
        'controller'      => $this,
        'renderedForm'    => $collection->renderForm,
    )));
    ?>
    <div id="condition-template" style="display: none;">
        <div class="item">
            <div class="col-lg-3">
                <?php echo CHtml::activeLabelEx($condition, 'field_id');?>
                <?php echo CHtml::dropDownList($condition->modelName.'[{index}][field_id]', $condition->field_id, $segment->getFieldsDropDownArray(), $condition->getHtmlOptions('field_id')); ?>
                <?php echo CHtml::error($condition, 'field_id');?>
            </div>
            <div class="col-lg-3">
                <?php echo CHtml::activeLabelEx($condition, 'operator_id');?>
                <?php echo CHtml::dropDownList($condition->modelName.'[{index}][operator_id]', $condition->operator_id, $condition->getOperatorsDropDownArray(), $condition->getHtmlOptions('operator_id')); ?>
                <?php echo CHtml::error($condition, 'operator_id');?>
            </div>
            <div class="col-lg-3">
                <?php echo CHtml::activeLabelEx($condition, 'value');?>
                <?php echo CHtml::textField($condition->modelName.'[{index}][value]', $condition->value, $condition->getHtmlOptions('value')); ?>
                <?php echo CHtml::error($condition, 'value');?>
            </div>
            <div class="col-lg-3">
                <label><?php echo Yii::t('app', 'Action');?></label><br />
                <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-condition"><?php echo IconHelper::make('delete');?></a>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>

    <div id="campaign-condition-template" style="display: none;">
        <div class="item">
            <div class="col-lg-3">
			    <?php echo CHtml::activeLabelEx($campaignCondition, 'action');?>
			    <?php echo CHtml::dropDownList($campaignCondition->modelName.'[{index}][action]', $campaignCondition->action, $campaignCondition->getActionsList(), $campaignCondition->getHtmlOptions('action')); ?>
			    <?php echo CHtml::error($campaignCondition, 'action');?>
            </div>
            <div class="col-lg-3">
			    <?php echo CHtml::activeLabelEx($campaignCondition, 'campaign_id');?>
			    <?php echo CHtml::dropDownList($campaignCondition->modelName.'[{index}][campaign_id]', $campaignCondition->campaign_id, $campaignCondition->getCampaignsList($list->list_id), $campaignCondition->getHtmlOptions('campaign_id')); ?>
			    <?php echo CHtml::error($campaignCondition, 'campaign_id');?>
            </div>
            <div class="col-lg-5">
                <div class="row">
                    <div class="col-lg-4">
		                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_comparison_operator');?>
		                <?php echo CHtml::dropDownList($campaignCondition->modelName.'[{index}][time_comparison_operator]', $campaignCondition->time_comparison_operator, $campaignCondition->getTimeComparisonOperatorsList(), $campaignCondition->getHtmlOptions('time_comparison_operator')); ?>
		                <?php echo CHtml::error($campaignCondition, 'time_comparison_operator');?>
                    </div>
                    <div class="col-lg-4">
		                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_value');?>
		                <?php echo CHtml::textField($campaignCondition->modelName.'[{index}][time_value]', $campaignCondition->time_value, $campaignCondition->getHtmlOptions('time_value')); ?>
		                <?php echo CHtml::error($campaignCondition, 'time_value');?>
                    </div>
                    <div class="col-lg-4">
		                <?php echo CHtml::activeLabelEx($campaignCondition, 'time_unit');?>
		                <?php echo CHtml::dropDownList($campaignCondition->modelName.'[{index}][time_unit]', $campaignCondition->time_unit, $campaignCondition->getTimeUnitsList(), $campaignCondition->getHtmlOptions('time_unit')); ?>
		                <?php echo CHtml::error($campaignCondition, 'time_unit');?>
                    </div>
                </div>
            </div>
            <div class="col-lg-1">
                <label><?php echo Yii::t('app', 'Action');?></label><br />
                <a href="javascript:;" class="btn btn-danger btn-flat btn-remove-campaign-condition"><?php echo IconHelper::make('delete');?></a>
            </div>
            <div class="clearfix"><!-- --></div>
        </div>
    </div>
    
    <div class="modal fade" id="conditions-value-tags" tabindex="-1" role="dialog" aria-labelledby="conditions-value-tags-label" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
              <h4 class="modal-title"><?php echo Yii::t('list_segments', 'Available value tags');?></h4>
            </div>
            <div class="modal-body">
                <div class="callout callout-info">
                    <?php echo Yii::t('list_segments', 'Following tags can be used as dynamic values. They will be replaced as shown below.');?>
                </div>
                <table class="table table-bordered table-condensed">
                    <tr>
                        <td><?php echo Yii::t('list_segments', 'Tag');?></td>
                        <td><?php echo Yii::t('list_segments', 'Description');?></td>
                    </tr>
                    <?php foreach ($conditionValueTags as $tagInfo) { ?>
                    <tr>
                        <td><?php echo CHtml::encode($tagInfo['tag']);?></td>
                        <td><?php echo CHtml::encode($tagInfo['description']);?></td>
                    </tr>
                    <?php } ?>
                </table>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-default btn-flat" data-dismiss="modal"><?php echo Yii::t('app', 'Close');?></button>
            </div>
          </div>
        </div>
    </div>
<?php 
}
/**
 * This hook gives a chance to append content after the view file default content.
 * Please note that from inside the action callback you can access all the controller view
 * variables via {@CAttributeCollection $collection->controller->data}
 * @since 1.3.3.1
 */
$hooks->doAction('after_view_file_content', new CAttributeCollection(array(
    'controller'        => $this,
    'renderedContent'   => $viewCollection->renderContent,
)));