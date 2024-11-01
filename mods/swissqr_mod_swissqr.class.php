<?php
	
	declare(strict_types=1);
	
	/**
	 * @date: 14.10.23
	 * @time: 07:02
	 * swissqr_mod_swissqr
	 */
	 
	class wpsgswissqr_mod_swissqr extends \wpsg_mod_basic {
		
		protected static $instance = null;
		
		var $id = 5503;
		
		/**
		 * Kostructor
		 */
		public function __construct() {
			
			parent::__construct();
			
			$this->name = esc_html__('SwissQR', 'swissqr');
			$this->group = esc_html__('Bestellung', 'swissqr');
			$this->desc = esc_html__('', 'swissqr');
			
		}
		
		public function install(): void {
			
			$this->shop->checkDefault('swissqr_mod_swissqr_order_done', '1');
			$this->shop->checkDefault('swissqr_mod_swissqr_customer_mail', '1');

			if ($this->shop->hasMod('wpsg_mod_rechnungen')) {
				
				$this->shop->checkDefault('swissqr_mod_swissqr_invoice_mail', '1');
				
			}
			
			$this->shop->checkDefault('swissqr_mod_swissqr_payment_types', '1');
			
			$this->shop->checkDefault('swissqr_mod_swissqr_target_name', '%shopinfo_name%');
			$this->shop->checkDefault('swissqr_mod_swissqr_target_street', '%shopinfo_street%');
			$this->shop->checkDefault('swissqr_mod_swissqr_target_zipcity', '%shopinfo_zip% %shopinfo_city%');
			$this->shop->checkDefault('swissqr_mod_swissqr_target_countrycode', $this->shop->getDefaultCountry()->getShorttext());
			$this->shop->checkDefault('swissqr_mod_swissqr_target_qriban', '%shopinfo_qriban%');
			
			$this->shop->checkDefault('swissqr_mod_swissqr_subject', 'O%order_id% - K%kunde_id%');
			
		}
		
		public function settings_edit(): void {
		
			$this->render(dirname(__FILE__).DIRECTORY_SEPARATOR.'../views/mods/swissqr_mod_swissqr/settings_edit.phtml');
		
		}
		
		/**
		 * @throws Exception
		 */
		public function settings_save(): void {
			
			$this->shop->update_option('swissqr_mod_swissqr_order_done', intval($_REQUEST['swissqr_mod_swissqr_order_done']??0));
			$this->shop->update_option('swissqr_mod_swissqr_customer_mail', intval($_REQUEST['swissqr_mod_swissqr_customer_mail']??0));
			
			if ($this->shop->hasMod('wpsg_mod_rechnungen')) $this->shop->update_option('swissqr_mod_swissqr_invoice_mail', intval($_REQUEST['swissqr_mod_swissqr_invoice_mail']??0));
			
			$arPaymentTypes = array_map('sanitize_text_field', $_REQUEST['swissqr_mod_swissqr_payment_types']??[]);
			
			// Sanitize: Nur aktivierte PaymentTypes erlauben
			$arPaymentType = []; foreach ($arPaymentTypes as $payment_type) if (array_key_exists($payment_type, $this->shop->arPayment)) $arPaymentType[] = $payment_type;
			
			$this->shop->update_option('swissqr_mod_swissqr_payment_types', implode(',', $arPaymentType));
			
			$this->shop->update_option('swissqr_mod_swissqr_target_name', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_target_name']));
			$this->shop->update_option('swissqr_mod_swissqr_target_street', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_target_street']));
			$this->shop->update_option('swissqr_mod_swissqr_target_zipcity', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_target_zipcity']));
			$this->shop->update_option('swissqr_mod_swissqr_target_countrycode', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_target_countrycode']));
			$this->shop->update_option('swissqr_mod_swissqr_target_qriban', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_target_qriban']));
			
			$this->shop->update_option('swissqr_mod_swissqr_subject', \sanitize_text_field($_REQUEST['swissqr_mod_swissqr_subject']));
			
		}
		
		/* Modul */
		
		public function getActivePaymentTypeKeys(): array {
			
			$arPaymentTypes = [];
			
			$arPaymentTypesSet = $this->shop->get_option('swissqr_mod_swissqr_payment_types');
			
			if (is_string($arPaymentTypesSet)) {
				
				foreach (explode(',', $arPaymentTypesSet) as $pamyent_type) {
					
					if (array_key_exists($pamyent_type, $this->shop->arPayment)) $arPaymentTypes[] = $pamyent_type;
					
				}
				
			}
			
			return $arPaymentTypes;
			
		}
		
		public static function getInstance(): wpsgswissqr_mod_swissqr {
			
			if (!isset(static::$instance)) static::$instance = new static;
			
			return static::$instance;
			
		}

	}