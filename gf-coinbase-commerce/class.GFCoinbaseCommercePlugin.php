<?php
if (method_exists('GFForms', 'include_payment_addon_framework')) {
    GFForms::include_payment_addon_framework();
    require_once __DIR__ . '/class.GFCoinbaseCommerceAdmin.php';
    require_once __DIR__ . '/class.GFCoinbaseCommerceSettings.php';
    require_once __DIR__ . '/vendor/CoinbaseSDK/autoload.php';
    require_once __DIR__ . '/vendor/CoinbaseSDK/const.php';

    /**
     * Class for managing the plugin
     */
    class GFCoinbaseCommercePlugin extends GFPaymentAddOn
    {
        const RETURN_PAGE_PARAM = 'gf_coinbase_return';
        const FORM_ID_PARAM = 'form_id';
        const LEAD_ID_PARAM = 'lead_id';

        protected $_version = GF_COINBASE_COMMERCE_VERSION;
        protected $_min_gravityforms_version = '1.9';
        protected $_slug = GF_COINBASE_COMMERCE_SLUG;
        protected $_path = 'gf-coinbase-commerce/gf-coinbase-commerce.php';
        protected $_full_path = __FILE__;
        protected $_title = 'Gravity Forms Coinbase Commerce Add-On';
        protected $_short_title = 'Coinbase Commerce Payment';
        protected $_supports_callbacks = true;
        protected $_requires_credit_card = false;

        /**
         * @var object|null $_instance If available, contains an instance of this class.
         */
        private static $_instance = null;

        /**
         * Returns an instance of this class, and stores it in the $_instance property.
         *
         * @return object $_instance An instance of this class.
         */
        public static function get_instance()
        {
            if (self::$_instance == null) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        public function init()
        {
            parent::init();

            if (is_admin() == true) {
                new GFCoinbaseCommerceAdmin();
                new GFCoinbaseCommerceSettings();
            }
        }

        public function feed_settings_fields()
        {
            $default_settings = parent::feed_settings_fields();
            $default_settings = parent::remove_field('billingInformation', $default_settings);

            $transaction_type = parent::get_field('transactionType', $default_settings);
            $choices = $transaction_type['choices'];
            $subscription = array_search('subscription', array_column($choices, 'value'));

            if ($subscription !== false) {
                unset($choices[$subscription]);
            }

            $transaction_type['choices'] = $choices;
            $default_settings = $this->replace_field('transactionType', $transaction_type, $default_settings);

            $fields = array();

            //Add post fields if form has a post
            $default_settings = $this->add_field_before('conditionalLogic', $fields, $default_settings);
            return $default_settings;
        }

        function redirect_url($feed, $submission_data, $form, $entry)
        {
            try {
                $apiKey = self::get_coinbase_setting(GFCoinbaseCommerceSettings::APP_KEY_PARAM);
                \CoinbaseCommerce\ApiClient::init($apiKey);

                $description = '';
                $amount = rgar($submission_data, 'payment_amount');
                $currency = rgar($entry, 'currency');
                $title = rgar($submission_data, 'form_title');
                $orderId = rgar($entry, 'id');
                $formId = rgar($form, 'id');
                $items = rgar($submission_data, 'line_items');

                if (is_array($items) && count($items) !== 0) {
                    $products = array_map(function ($item) {
                        return $item['quantity'] . ' x ' . $item['name'];
                    }, $items);
                    $description = implode(' ,', $products);
                }

                if (empty($amount) || empty($currency)) {
                    throw new \Exception('Amount or currency is not set for form: ' . $title);
                }

                if (empty($orderId)) {
                    throw new \Exception('Entry id is not set for form: ' . $title);
                }

                $chargeData = array(
                    'local_price' => array(
                        'amount' => $submission_data['payment_amount'],
                        'currency' => $entry['currency']
                    ),
                    'pricing_type' => 'fixed_price',
                    'name' => $title ?: 'Payment',
                    'description' => $description,
                    'metadata' => array(
                        METADATA_SOURCE_PARAM => METADATA_SOURCE_VALUE,
                        METADATA_INVOICE_ID_PARAM => $orderId,
                        METADATA_CLIENT_ID_PARAM => is_user_logged_in() ? get_current_user_id() : ''
                    ),
                    'redirect_url' => $this->get_sucess_return_url($formId, $orderId, $entry['source_url']),
                    'cancel_url' => $entry['source_url']
                );

                $charge = \CoinbaseCommerce\Resources\Charge::create($chargeData);

                gform_update_meta( $entry['id'], METADATE_CHARGE_ID, $charge['id']);

                return $charge->hosted_url;
            } catch (\Exception $exception) {
                add_filter('gform_confirmation', array($this, 'display_payment_failure'), 1000, 4);

                $this->log_debug('Unable to create coinbase commerce charge.' . $exception->getMessage());
                echo __('Unable to complete payment.', 'gf-coinbase-commerce');
                die();
            }
        }

        static function get_sucess_return_url($form_id, $lead_id, $sourceUrl)
        {
            return add_query_arg(array(self::RETURN_PAGE_PARAM => '1', self::LEAD_ID_PARAM => $lead_id, self::FORM_ID_PARAM => $form_id), $sourceUrl);
        }

        static function get_coinbase_setting($name)
        {
            $options = get_option(GFCoinbaseCommerceSettings::SETTINGS_OPTIONS);

            return rgar($options, $name);
        }

        static function process_confirmation()
        {
            if (rgget(self::RETURN_PAGE_PARAM) && $form_id = rgget(self::FORM_ID_PARAM) && $lead_id = rgget(self::LEAD_ID_PARAM)) {
                $form = GFAPI::get_form($form_id);
                $lead = GFAPI::get_entry($lead_id);

                require_once(GFCommon::get_base_path() . '/form_display.php');
                $confirmation = GFFormDisplay::handle_confirmation($form, $lead, true);

                // preload the GF submission, ready for processing the confirmation message
                GFFormDisplay::$submission[$form['id']] = array(
                    'is_confirmation'		=> true,
                    'confirmation_message'	=> $confirmation,
                    'form'					=> $form,
                    'lead'					=> $lead,
                );

                if (is_array($confirmation) && isset($confirmation['redirect'])) {
                    header("Location: {$confirmation["redirect"]}");
                    /**
                     * Fires after submission, if the confirmation page includes a redirect
                     *
                     * Used to perform additional actions after submission
                     *
                     * @param array $lead The Entry object
                     * @param array $form The Form object
                     */
                    gf_do_action(array('gform_post_submission', $form['id']), $lead, $form);
                    exit;
                }

                gf_do_action( array( 'gform_post_process', $form['id'] ), $form);
            }
        }

        function callback()
        {
            $headers = array_change_key_case(getallheaders());
            $signatureHeader = isset($headers[SIGNATURE_HEADER]) ? $headers[SIGNATURE_HEADER] : null;
            $payload = trim(file_get_contents('php://input'));
            $apiKey = self::get_coinbase_setting(GFCoinbaseCommerceSettings::APP_KEY_PARAM);
            $sharedSecret = self::get_coinbase_setting(GFCoinbaseCommerceSettings::APP_SECRET_PARAM);

            try {
                $event = \CoinbaseCommerce\Webhook::buildEvent($payload, $signatureHeader, $sharedSecret);
            } catch (\Exception $exception) {
                $this->log_debug( __METHOD__ . ' Exception was throwed. Exception:' . $exception->getMessage() );
                throw new Exception($exception->getMessage());
            }

            \CoinbaseCommerce\ApiClient::init($apiKey);
            $charge = \CoinbaseCommerce\Resources\Charge::retrieve($event->data['id']);

            if ($charge->getMetadataParam(METADATA_SOURCE_PARAM) != METADATA_SOURCE_VALUE) {
                $this->log_debug( __METHOD__ .' Not ' . METADATA_SOURCE_VALUE .  ' charge');
                exit;
            }

            if (($entry_id = $charge->getMetadataParam(METADATA_INVOICE_ID_PARAM)) === null
                || gform_get_meta($entry_id, METADATE_CHARGE_ID) != $charge['id']) {
                $this->log_debug( __METHOD__ . ' Invoice id is not found.');
                exit;
            }

            $action = array(
                'type'             => false,
                'amount'           => false,
                'transaction_type' => false,
                'transaction_id'   => false,
                'subscription_id'  => false,
                'entry_id'         => $entry_id,
                'note'             => '',
            );

            $lastTimeLine = end($charge->timeline);

            switch ($lastTimeLine['status']) {
                case 'RESOLVED':
                case 'COMPLETED':
                    $action['type'] = 'complete_payment';
                    $action['note'] = sprintf('Charge %s was paid.', $charge['id']);
                    break;
                case 'PENDING':
                    $action['type'] = 'add_pending_payment';
                    $action['note'] = sprintf(
                        'Charge %s is pending. Charge has been detected but has not been confirmed yet.',
                        $charge['id']
                    );

                    break;
                case 'NEW':
                    $action['type'] = 'add_pending_payment';
                    $action['note'] = sprintf('Charge %s was created. Awaiting payment.', $charge['id']);
                    break;
                case 'UNRESOLVED':
                    // mark order as paid on overpaid or delayed
                    if ($lastTimeLine['context'] === 'OVERPAID') {
                        $action['type'] = 'complete_payment';
                        $action['note'] = sprintf('Charge %s was overpaid.', $charge['id']);
                    } else {
                        $action['note'] = sprintf(
                            'Charge %s was unresolved. Context %s.',
                            $charge['id'],
                            $lastTimeLine['context']
                        );
                        $action['type'] = 'fail_payment';
                    }
                    break;
                case 'CANCELED':
                    $action['note'] = sprintf('Charge %s was canceled.', $charge['id']);
                    $action['type'] = 'fail_payment';
                    break;
                case 'EXPIRED':
                    $action['note'] = sprintf('Charge %s has expired.', $charge['id']);
                    $action['type'] = 'fail_payment';
                    break;
            }

            foreach ($charge->payments as $payment) {
                if (strtolower($payment['status']) === 'confirmed') {
                    $transactionId = $payment['transaction_id'];
                    $paymentNetwork = $payment['network'];
                    $total = $payment['value']['local']['amount'];
                    $cryptoAmount =  $payment['value']['crypto']['amount'];
                    $cryptoCurrency =  $payment['value']['crypto']['currency'];

                    $action['transaction_id'] = $transactionId;
                    $action['transaction_type'] = $paymentNetwork;
                    $action['note'] .= sprintf(' Payment was detected. Crypto currency: %s, crypto amount: %s', $cryptoCurrency, $cryptoAmount);
                    $action['amount'] = $total;

                    $this->log_debug( __METHOD__ . ' ' . $action['note']);
                    break;
                }
            }

            return $action;
        }
    }
}
