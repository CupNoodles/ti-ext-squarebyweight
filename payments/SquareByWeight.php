<?php

namespace CupNoodles\SquareByWeight\Payments;

use Igniter\PayRegister\Payments\Square;
use Admin\Models\Customers_model;
use Admin\Models\Payment_profiles_model;
use Event;
use ApplicationException;
use Exception;

class SquareByWeight extends Square
{

        /**
     * @param self $host
     * @param \Main\Classes\MainController $controller
     */
    public function beforeRenderPaymentForm($host, $controller)
    {
        $endpoint = $this->isTestMode() ? 'squareupsandbox' : 'squareup';
        $controller->addJs('https://js.'.$endpoint.'.com/v2/paymentform', 'square-js');
        $controller->addJs('$/cupnoodles/squarebyweight/assets/js/process.squarebyweight.js', 'process-square-js');
    }


    public function processPayment($order){
        
        // find payment method saved for this order
        $profile = Payment_profiles_model::where('order_id', $order->order_id)->first();

        // charge the saved payment method the current order total
        try{
            $fields = $this->getPaymentFormFields($order);
            $fields['customerCardId'] = array_get($profile->profile_data, 'card_id');
            $fields['customerReference'] = array_get($profile->profile_data, 'customer_id');

            $gateway = $this->createGateway();
            
            $response = $gateway->purchase($fields)->send();   
            
            if ($response->isSuccessful()) { 
                $order->logPaymentAttempt('Successfully charged customer card.', 0, $fields, []);
                $order->markAsPaymentProcessed();
            }
            else{
                $order->logPaymentAttempt('Payment error -> '.$response->getMessage(), 0, $fields, []);
                throw new ApplicationException($response->getMessage());
            }
        }
        catch (Exception $ex) {
            $order->logPaymentAttempt('Payment error -> '.$ex->getMessage(), 0, $fields, []);
            throw new ApplicationException($ex->getMessage());
        }

    }


    /**
     * Processes payment using passed data, but only if the order doens't contain any itmes that are priced by weight.
     *
     * @param array $data
     * @param \Admin\Models\Payments_model $host
     * @param \Admin\Models\Orders_model $order
     *
     * @throws \ApplicationException
     */
    public function processPaymentForm($data, $host, $order)
    {
        $this->validatePaymentMethod($order, $host);

        $fields = $this->getPaymentFormFields($order, $data);

        if (array_get($data, 'has_price_by_weight', 0) == 1) {
            $pay_later = true;
        }
        else{
            $pay_later = false;
        }
        
        $fields['nonce'] = array_get($data, 'square_card_nonce');

        // Sandbox
        if($fields['nonce'] == '' && $this->isTestMode()){
            // success
            $fields['nonce'] = "cnon:card-nonce-ok";
            $data['square_card_nonce'] = "cnon:card-nonce-ok";
            //fail
            //$fields['nonce'] = "cnon:card-nonce-declined";
            //$data['square_card_nonce'] = "cnon:card-nonce-declined";
        }

        if (!$pay_later) {
            $fields['token'] = array_get($data, 'square_card_token');
        }



        try {
            if($pay_later){

                $customer = new Customers_model();
                $customer->first_name = $data['first_name'];
                $customer->last_name = $data['last_name'];
                $customer->email = str_replace('@', '+osakana'.$order->order_id.'@', $data['email']);

                $response = $this->createOrFetchCustomer([], $customer);
                $customerId = $response->getCustomerReference();
                
                $response = $this->createOrFetchCard($customerId, [], $data);
                $cardData = $response->getData();
                $cardId = $response->getCardReference();
        
                $profile = new Payment_profiles_model();
                $profile->order_id = $order->order_id;
                $profile->payment_id = $this->model->payment_id;
        
                $this->updatePaymentProfileData($profile, [
                    'order_id' => $order->order_id,
                    'customer_id' => $customerId,
                    'card_id' => $cardId,
                ], $cardData);

            }
            else{
                $gateway = $this->createGateway();
                $response = $gateway->purchase($fields)->send();    
            }



            if ($response->isSuccessful()) {
                
                if($pay_later){
                    $order->logPaymentAttempt('Sucessfully Saved Payment Method', 1, $fields, $response->getData());
                    $order->updateOrderStatus($host->order_status_unpaid, ['notify' => FALSE]);
                    Event::fire('admin.order.beforePaymentProcessed', [$order]);
                    $order->processed = 0;
                    $order->save();
                    Event::fire('admin.order.paymentProcessed', [$order]);
                }
                else{
                    $order->logPaymentAttempt('Payment successful', 1, $fields, $response->getData());
                    $order->updateOrderStatus($host->order_status_paid, ['notify' => FALSE]);
                    $order->markAsPaymentProcessed();
                }
                
            }
            else {
                $order->logPaymentAttempt('Payment error -> '.$response->getMessage(), 0, $fields, $response->getData());
                throw new Exception($response->getMessage());
            }


        }
        catch (Exception $ex) {
            $order->logPaymentAttempt('Payment error -> '.$ex->getMessage(), 0, $fields, []);
            throw new ApplicationException('Sorry, there was an error processing your payment. Please try again later.');
        }
    }

}
