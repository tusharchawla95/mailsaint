<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * This file is part of the MailWizz EMA application.
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.4.6
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
if ($viewCollection->renderContent) {
    if (!$domain->isNewRecord) {
        $this->renderPartial('_verify-domain');
    }
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
                        ->add('<h3 class="box-title">' . IconHelper::make('glyphicon-globe') . $pageHeading . '</h3>')
                        ->render();
                    ?>
                </div>
                <div class="pull-right">
                    <?php BoxHeaderContent::make(BoxHeaderContent::RIGHT)
                        ->addIf(CHtml::link(IconHelper::make('create') . Yii::t('app', 'Create new'), array('sending_domains/create'), array('class' => 'btn btn-primary btn-flat', 'title' => Yii::t('app', 'Create new'))), !$domain->isNewRecord)
                        ->add(CHtml::link(IconHelper::make('cancel') . Yii::t('app', 'Cancel'), array('sending_domains/index'), array('class' => 'btn btn-primary btn-flat', 'title' => Yii::t('app', 'Cancel'))))
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
                <div class="clearfix"><!-- --></div>
                <div class="form-group">
                    <?php echo $form->labelEx($domain, 'name');?>
                    <?php echo $form->textField($domain, 'name', $domain->getHtmlOptions('name')); ?>
                    <?php echo $form->error($domain, 'name');?>
                </div>
                <div class="clearfix"><!-- --></div>
                
                <?php if (!(DnsTxtHelper::getDefaultDkimPrivateKey() && DnsTxtHelper::getDefaultDkimPublicKey())) { ?>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($domain, 'dkim_private_key');?>
                    <?php echo $form->textArea($domain, 'dkim_private_key', $domain->getHtmlOptions('dkim_private_key', array('rows' => 10))); ?>
                    <?php echo $form->error($domain, 'dkim_private_key');?>
                </div>
                <div class="form-group col-lg-6">
                    <?php echo $form->labelEx($domain, 'dkim_public_key');?>
                    <?php echo $form->textArea($domain, 'dkim_public_key', $domain->getHtmlOptions('dkim_public_key', array('rows' => 10))); ?>
                    <?php echo $form->error($domain, 'dkim_public_key');?>
                </div>
                <div class="clearfix"><!-- --></div>
                <?php } ?>
                
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
                    <button type="submit" class="btn btn-primary btn-flat"><?php echo IconHelper::make('save') . Yii::t('app', 'Save changes');?></button>
                </div>
                <div class="clearfix"><!-- --></div>
            </div>
        </div>
        <?php 
        $this->endWidget(); 
    }
    /**
     * This hook gives a chance to append content after the active form.
     * Please note that from inside the action callback you can access all the controller view variables 
     * via {@CAttributeCollection $collection->controller->data}
     * @since 1.3.3.1
     */
    $hooks->doAction('after_active_form', new CAttributeCollection(array(
        'controller'      => $this,
        'renderedForm'    => $collection->renderForm,
    )));
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