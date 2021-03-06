<?php defined('MW_PATH') || exit('No direct script access allowed');

/**
 * CampaignOpenToCampaignQueueTableBehavior
 *
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.3.8.8
 *
 */

class CampaignOpenToCampaignQueueTableBehavior extends CActiveRecordBehavior
{
    /**
     * @inheritdoc
     */
    public function afterSave($event)
    {
        parent::afterSave($event);

        // ref
        $owner = $this->owner;
        
        $count = CampaignTrackOpen::model()->countByAttributes(array(
            'campaign_id'   => $owner->campaign_id,
            'subscriber_id' => $owner->subscriber_id,
        ));
        
        if ($count > 1) {
            return;
        }

        // ref
        $subscriber = $owner->subscriber;
        
        $criteria = new CDbCriteria();
        $criteria->compare('t.list_id', (int)$owner->campaign->list_id);
        $criteria->compare('t.type', Campaign::TYPE_AUTORESPONDER);
        $criteria->addNotInCondition('t.status', array(Campaign::STATUS_SENT, Campaign::STATUS_DRAFT, Campaign::STATUS_PENDING_DELETE));
        
        $criteria->with['option'] = array(
            'together'  => true,
            'joinType'  => 'INNER JOIN',
            'select'    => 'option.autoresponder_include_imported, autoresponder_include_current, option.autoresponder_time_value, option.autoresponder_time_unit, option.autoresponder_time_min_hour, option.autoresponder_time_min_minute',
            'condition' => 'option.autoresponder_event = :evt AND option.autoresponder_open_campaign_id = :cid',
            'params'    => array(
                ':evt' => CampaignOption::AUTORESPONDER_EVENT_AFTER_CAMPAIGN_OPEN,
                ':cid' => $owner->campaign_id
            ),
        );
        
        $campaigns = Campaign::model()->findAll($criteria);
        
        foreach ($campaigns as $campaign) {

            // ref
            $campaignOption = $campaign->option;
            
            // if imported are not allowed to receive
            if ($subscriber->isImported && !$campaignOption->autoresponderIncludeImported) {
                continue;
            }
            
            // if the subscriber does not fall into segments criteria
            if (!empty($campaign->segment_id) && !$campaign->segment->hasSubscriber((int)$owner->subscriber_id)) {
                continue;
            }
            
            $minTimeHour   = !empty($campaignOption->autoresponder_time_min_hour) ? $campaignOption->autoresponder_time_min_hour : null;
            $minTimeMinute = !empty($campaignOption->autoresponder_time_min_minute) ? $campaignOption->autoresponder_time_min_minute : null;
            $timeValue     = (int)$campaignOption->autoresponder_time_value;
            $timeUnit      = strtoupper($campaignOption->autoresponder_time_unit);
            
            try {

                $sendAt = new CDbExpression(sprintf('DATE_ADD(NOW(), INTERVAL %d %s)', $timeValue, $timeUnit));
                
                // 1.4.3
                if (!empty($minTimeHour) && !empty($minTimeMinute)) {
	                $sendAt = new CDbExpression(sprintf('
				        IF (
				            DATE_ADD(NOW(), INTERVAL %1$d %2$s) > DATE_FORMAT(DATE_ADD(NOW(), INTERVAL %1$d %2$s), \'%%Y-%%m-%%d %3$s:%4$s:00\'),
				            DATE_ADD(NOW(), INTERVAL %1$d %2$s),
				            DATE_FORMAT(DATE_ADD(NOW(), INTERVAL %1$d %2$s), \'%%Y-%%m-%%d %3$s:%4$s:00\')
				        )',
		                $timeValue, $timeUnit, $minTimeHour, $minTimeMinute
	                ));
                }
                
                $campaign->queueTable->addSubscriber(array(
                    'subscriber_id' => $owner->subscriber_id,
                    'send_at'       => $sendAt
                ));

            } catch (Exception $e) {

                Yii::log($e->getMessage(), CLogger::LEVEL_ERROR);

            }
        }
    }
}