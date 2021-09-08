<?php defined('MW_INSTALLER_PATH') || exit('No direct script access allowed');

/**
 * WelcomeController
 * 
 * @package MailWizz EMA
 * @author Serban George Cristian <cristian.serban@mailwizz.com> 
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */
 
class WelcomeController extends Controller
{
    public function actionIndex()
    {
        // start clean
        $_SESSION = array();
        
        $this->validateRequest();
        
        if (getSession('welcome')) {
            redirect('index.php?route=requirements');
        }
        
        $this->data['marketPlaces'] = $this->getMarketPlaces();
        
        $this->data['pageHeading'] = 'Welcome';
        $this->data['breadcrumbs'] = array(
            'Welcome' => 'index.php?route=welcome',
        );
        
        $this->render('welcome');
    }
    
    protected function validateRequest()
    {
        if (!getPost('next')) {
            return;
        }
        
        $marketPlace  = getPost('market_place');
        $purchaseCode = getPost('purchase_code');
        $termsConsent = getPost('terms_consent');
        
        if (empty($marketPlace)) {
            $this->addError('market_place', 'Please enter the market place from where you have bought the license!');
        }
        
        if (empty($purchaseCode)) {
            $this->addError('purchase_code', 'Please enter the purchase code!');
        }
        
        if (empty($termsConsent)) {
            $this->addError('terms_consent', 'You have to agree with our Terms and Conditions in order to proceed!');
        }
        
        if ($this->hasErrors()) {
            return $this->addError('general', 'Your form has a few errors, please fix them and try again!');
        }
        
        // license check.
        $postData = array(
            'purchase_code' => $purchaseCode,
        );
        
        $url     = 'https://www.mailwizz.com/api/license/' . $marketPlace;
        $request = AppInitHelper::simpleCurlPost($url, $postData);
        
        if ($request['status'] == 'error') {
            return $this->addError('general', $request['message']);
        }

        $response = @json_decode($request['message'], true);
        if (empty($response['status'])) {
            return $this->addError('general', 'Invalid response, please contact support!');
        }
        
        if ($response['status'] != 'success') {
            if (isset($response['message'])) {
                return $this->addError('general', $response['message']);    
            }
            if (isset($response['error']) && is_string($response['error'])) {
                return $this->addError('general', $response['error']);    
            }
            return $this->addError('general', 'Invalid response, please contact support!');
        }
        
        $licenseData = $response['license_data'];
        
        setSession('license_data', $licenseData);
        setSession('welcome', 1);
    }
    
    public function getMarketPlaces()
    {
        return array(
            'envato'    => 'Envato Market Places',
            'mailwizz'  => 'Mailwizz Website',
        );
    }

}