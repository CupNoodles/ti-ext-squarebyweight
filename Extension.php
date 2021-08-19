<?php 

namespace CupNoodles\SquareByWeight;

use System\Classes\BaseExtension;
use Event;
use Admin\Models\Payment_profiles_model;
use Admin\Models\Payments_model;
use Admin\Models\Orders_model as Admin_Orders_Model;
use Admin\Controllers\Orders;
use Admin\Widgets\Form;
use Admin\Widgets\Toolbar;

use CupNoodles\SquareByWeight\Payments\SquareByWeight;

class Extension extends BaseExtension
{
    /**
     * Returns information about this extension.
     *
     * @return array
     */
    public function extensionMeta()
    {
        return [
            'name'        => 'SquareByWeight',
            'author'      => 'CupNoodles',
            'description' => 'Square Token Billing Manager for TastyIgniter',
            'icon'        => 'fa-file-invoice',
            'version'     => '1.0.0'
        ];
    }

    /**
     * Register method, called when the extension is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return void
     */
    public function boot()
    {
        // why doesn't this happen automatically?
        Event::listen('igniter.checkout.beforePayment', function($order, $data){
            if($order->order_total > 0){
                if($data['payment'] == 'squarebyweight'){
                    $order->payment = 'squarebyweight';
                }
            }
        });



        // create an order page button that charges the final amount for an order (if it's not payment processed)
        Event::listen('admin.form.extendFieldsBefore', function (Form $form) {

            if ($form->model instanceof Admin_Orders_Model) {
                if ($form->model->processed == 0){

                    Event::listen('admin.toolbar.extendButtonsBefore', function (Toolbar $toolbar) use ($form) {
                        $toolbar->buttons['process_payment']  = [
                            'label' => 'lang:cupnoodles.squarebyweight::default.process_payment',
                            'class' => 'btn btn-primary',
                            'data-request' => 'onProcessPayment',
                            'data-request-data' => "_method:'POST', order_id:" . $form->model->order_id . ", refresh:1",
                            'data-request-confirm' => 'lang:cupnoodles.squarebyweight::default.process_payment_confirmation',
                        ];

                    });						
                }
            }
        });

        Orders::extend(function($controller){
            $controller->addDynamicMethod('edit_onProcessPayment', function($action, $order_id) use ($controller) {
                $model = $controller->formFindModelObject($order_id);
                $sqbw = new SquareByWeight(Payments_model::where('code', 'squarebyweight')->first());
                $sqbw->processPayment($model);

                if ($redirect = $controller->makeRedirect('edit', $model)) {
                    return $redirect;
                }

            });
        } );

    }



    /**
     * Registers any front-end components implemented in this extension.
     *
     * @return array
     */
    
    public function registerComponents()
    {

        return [
            'CupNoodles\SquareByWeight\Components\OrderByWeight' => [
                'code' => 'orderPageByWeight',
                'name' => 'lang:igniter.local::default.menu.component_title',
                'description' => 'lang:igniter.local::default.menu.component_desc',
            ]
        ];
    }
    

    public function registerPaymentGateways()
    {
        return [
            'CupNoodles\SquareByWeight\Payments\SquareByWeight' => [
                'code' => 'squarebyweight',
                'name' => 'lang:cupnoodles.squarebyweight::default.text_payment_title',
                'description' => 'lang:cupnoodles.squarebyweight::default.text_payment_desc',
            ],
        ];
    }


    public function registerSettings()
    {

    }




    /**
     * Registers any admin permissions used by this extension.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [

        ];
    }

    public function registerNavigation()
    {
        return [

        ];
    }



}
