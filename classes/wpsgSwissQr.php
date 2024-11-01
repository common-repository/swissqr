<?php
	
	declare(strict_types=1);
	
	/**
	 * @date: 18.10.23
	 * @time: 13:11
	 */
	
	namespace wpsgSwissQr;
	
	use Sprain\SwissQrBill as QrBill;
	
	use Composer\Console\Application;
	use Symfony\Component\Console\Input\ArrayInput;
	
	class wpsgSwissQr {
		
		public static function wpsg_sendMail($arg): void {
			
			list($mail_key, $order_id, $k_id, &$empfaenger, &$subject, &$mail_text_send, &$headers, &$anhang, $payload) = $arg;
			
			if (!self::canGenerateQr(intval($order_id))) return;
			
			$oOrder = \wpsg_order::getInstance($order_id);
			
			if ($mail_key === 'kundenmail') {
				
				$payValue = $oOrder->getToPay(WPSG_BRUTTO);
				
				if (\wpsg_ShopController::getShop()->get_option('swissqr_mod_swissqr_customer_mail') !== '1') return;
				
			} else if (in_array($mail_key, ['invoicemail', 'rechnungscopy']) && \wpsg_ShopController::getShop()->hasMod('wpsg_mod_rechnungen')) {
				
				if (\wpsg_ShopController::getShop()->get_option('swissqr_mod_swissqr_invoice_mail') !== '1') return;
				
				$invoice_id = intval($payload['invoice_id']??0);
				
				if ($invoice_id > 0) {
					
					//$oInvoice = \wpsg\wpsg_invoice::getInstance($invoice_id);
					//$payValue = $oInvoice->getId()
					
					$payValue = $oOrder->getToPay(WPSG_BRUTTO);
					
				} else return;
				
			} else return;
			
			require_once WPSG_PATH_LIB.'FPDF_1.81/fpdf.php';
			require_once WPSG_PATH_LIB.'FPDI_2.2.0/autoload.php';
			require_once WPSG_PATH_LIB.'wpsg_fpdf.class.php';
			
			try {
				
				$qrBill = self::getQrBill(intval($order_id));
				
				$fpdf = new \Fpdf\Fpdf();
				$fpdf->AddPage();

				(new QrBill\PaymentPart\Output\FpdfOutput\FpdfOutput($qrBill, 'de', $fpdf))->setPrintable(false)->getPaymentPart();
				
				$tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
				$fpdf->Output('F', $tempFile);
				
				$anhang['swissqr.pdf'] = $tempFile;
				
			} catch (\Exception $e) {
			
			}
			
		}
		
		public static function wpsg_order_done_afterAction($arg): void {
		 
			$order_id = $arg[0];
			
			if (!self::canGenerateQr($order_id) || \wpsg_ShopController::getShop()->get_option('swissqr_mod_swissqr_order_done') !== '1') return;
			
			try {
			
				$qrBill = self::getQrBill($order_id);
				
				echo wp_kses((new QrBill\PaymentPart\Output\HtmlOutput\HtmlOutput($qrBill, 'de'))
					->setPrintable(false)
					->getPaymentPart(), [
						'img' => ['src' => [], 'alt' => [], 'decoding' => [], 'id' => []],
						'style' => [],
						'table' => ['id' => []],
						'tr' => ['id' => []],
						'td' => ['id' => []],
						'h1' => [],
						'div' => ['id' => []],
						'h2' => [],
						'p' => [],
						'br' => [],
						'tbody' => []
					], ['data']);
		 		
			} catch (\Exception $e) {
				
				$strError = esc_html__('Es gab Fehler bei der Generierung des swissQR Codes:', 'swissqr')."\r\n\r\n";
				
				foreach ($qrBill->getViolations() as $violation) {
					
					$strError .= $violation->getMessage()."\r\n";
					
				}
				
				wpsg_debug($strError);
				
			}
		
		}
		
		private static function getQrBill(int $order_id): \Sprain\SwissQrBill\QrBill {
			
			$oOrder = \wpsg_order::getInstance($order_id);
			
			require_once implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), '..', 'vendor', 'autoload.php']);
			
			$shop = \wpsg_ShopController::getShop();
			$qrBill = QrBill\QrBill::create();
			
			$qrBill->setCreditor(
				QrBill\DataGroup\Element\CombinedAddress::create(
					$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_target_name'), $order_id),
					$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_target_street'), $order_id),
					$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_target_zipcity'), $order_id),
					$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_target_countrycode'), $order_id)
				)
			);
			
			$qrBill->setCreditorInformation(
    			QrBill\DataGroup\Element\CreditorInformation::create(
        			$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_target_qriban'), $order_id)
    			)
			);
 
			$qrBill->setUltimateDebtor(
				QrBill\DataGroup\Element\StructuredAddress::createWithStreet(
					$oOrder->getInvoiceName(),
					$oOrder->getInvoiceStreetClear(true),
					$oOrder->getInvoiceStreetNr(),
					$oOrder->getInvoiceZip(),
					$oOrder->getInvoiceCity(),
					$oOrder->getInvoiceCountry()->getShorttext()
				)
			);
			
			$qrBill->setPaymentAmountInformation(
				QrBill\DataGroup\Element\PaymentAmountInformation::create(
					$shop->get_option('wpsg_currency'),
					$oOrder->getToPay(WPSG_BRUTTO)
				)
			);
			
			$customer_reference_number = $shop->get_option('swissqr_mod_swissqr_customerReferenceNr');
			if ($customer_reference_number === false || trim($customer_reference_number) === '') $customer_reference_number = null;
			
			$referenceNumber = QrBill\Reference\QrPaymentReferenceGenerator::generate(
				$customer_reference_number,
    			strval($oOrder->getId())
			);
			
			$qrBill->setPaymentReference(
				QrBill\DataGroup\Element\PaymentReference::create(
					QrBill\DataGroup\Element\PaymentReference::TYPE_QR,
					$referenceNumber
				)
			);
			
			$qrBill->setAdditionalInformation(
				QrBill\DataGroup\Element\AdditionalInformation::create(
					$shop->replaceUniversalPlatzhalter($shop->get_option('swissqr_mod_swissqr_subject'), $order_id)
				)
			);
			
			return $qrBill;
			
		}
		
		private static function canGenerateQr(int $order_id): bool {
			
			if (!self::isDependencyLoaded()) return false;
			
			$oOrder = \wpsg_order::getInstance($order_id);
			
			if (in_array($oOrder->getPaymentID(), \wpsgswissqr_mod_swissqr::getInstance()->getActivePaymentTypeKeys())) {
				
				return true;
				
			}
			
			return false;
			
		}
		
		public static function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status) {
		
			if ($plugin_file === 'wpsg_swissqr/wpsg_swissqr.php' && !self::isDependencyLoaded()) {
				
				$plugin_meta = array_merge([
					'<div style="padding:0.5rem 0; color:red; font-weight:bold;"><span style="user-select: none;">'.esc_html__('Abhängigkeiten nicht aufgelöst! Bitte', 'swissqr').' </span><pre style="display:inline; font-weight:normal; color:#333333;">composer install</pre><span style="user-select: none;"> '.esc_html__('im Pluginverzeichnis ausführen.', 'swissqr').'</span></div>'
				], $plugin_meta);
				
			} else if ($plugin_file === 'wpsg_swissqr/wpsg_swissqr.php') {
				
				$plugin_meta[] = '<a href="/wp-admin/admin.php?page=wpsg-Admin&action=module&modul=swissqr_mod_swissqr">'.esc_html__('Einstellungen', 'swissqr').'</a>';
				
			}
			
			return $plugin_meta;
			
		}
		
		public static function init(): void {
		
			if (!self::isDependencyLoaded()) {
			
			}
			
		}
		
		public static function isDependencyLoaded(): bool {
			
			return file_exists(implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), '..', 'vendor']));
			
		}
		
	}