<div
    id="squareByWeightPaymentForm"
    class="payment-form"
    data-application-id="{{ $paymentMethod->getAppId() }}"
    data-location-id="{{ $paymentMethod->getLocationId() }}"
    data-order-total="{{ Cart::total() }}"
    data-currency-code="{{ currency()->getUserCurrency() }}"
    data-error-selector="#square-card-errors"
>
    @foreach ($paymentMethod->getHiddenFields() as $name => $value)
        <input type="hidden" name="{{ $name }}" value="{{ $value }}"/>
    @endforeach

    <div class="form-group">
        <div class="square-ccbox">
            <div id="sq-card"></div>
        </div>
    </div>
</div>
