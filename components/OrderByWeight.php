<?php

namespace CupNoodles\SquareByWeight\Components;

use Igniter\Cart\Components\Order;
use Admin\Models\Payment_profiles_model;

use Redirect;

class OrderByWeight extends Order
{
    public function onRun()
    {
        $this->page['ordersPage'] = $this->property('ordersPage');
        $this->page['hideReorderBtn'] = $this->property('hideReorderBtn');
        $this->page['orderDateTimeFormat'] = lang('system::lang.moment.date_time_format_short');

        $this->page['hashParam'] = $this->param('hash');
        $this->page['order'] = $order = $this->getOrder();

        $this->addJs('js/order.js', 'checkout-js');

        if (!$order OR !$order->isPaymentProcessed() && !$this->orderHasPaymentProfile($order))
            return Redirect::to($this->property('ordersPage'));

        if ($this->orderManager->isCurrentOrderId($order->order_id))
            $this->orderManager->clearOrder();
    }

    protected function orderHasPaymentProfile($order)
    {
        $profile = Payment_profiles_model::where('order_id', $order->order_id)->get();
        if(count($profile) >0){
            return true;
        }
        return false;
    }
}