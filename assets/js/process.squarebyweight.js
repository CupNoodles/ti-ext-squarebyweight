+function ($) {
    "use strict"

    var ProcessSquare = function (element, options) {
        this.$el = $(element);
        this.options = options || {};
        this.$checkoutForm = this.$el.closest('#checkout-form');
        this.$modal = this.$el.closest("#creditCardModal");
        this.square = null;

        if(this.$el.is(":visible")){
            this.init();
        }
        
    }

    ProcessSquare.prototype.init = function () {

        if (!$('#'+this.options.cardFields.cardNumber.elementId).length)
            return

        var spOptions = {
            applicationId: this.options.applicationId,
            locationId: this.options.locationId,
            autoBuild: false,
            inputClass: 'form-control',
            callbacks: {
                cardNonceResponseReceived: $.proxy(this.onResponseReceived, this)
            }
        }

        if (this.options.applicationId === undefined)
            throw new Error('Missing square application id')

        this.square = new SqPaymentForm($.extend(spOptions, this.options.cardFields))

        this.square.build()

        $('.add-card').on('click', $.proxy(this.submitFormHandler, this))
    }

    ProcessSquare.prototype.submitFormHandler = function (event) {
        
        // Prevent the form from submitting with the default action
        event.preventDefault()
        event.stopPropagation();

        this.square.requestCardNonce();
    }

    ProcessSquare.prototype.onResponseReceived = function (errors, nonce, cardData) {

      

        var self = this,
            $form = this.$checkoutForm,
            verificationDetails = {
                intent: 'CHARGE',
                amount: this.options.orderTotal.toString(),
                currencyCode: this.options.currencyCode,
                billingContact: {
                    givenName: $('input[name="first_name"]', this.$checkoutForm).val(),
                    familyName: $('input[name="last_name"]', this.$checkoutForm).val(),
                }
            }
        
        $('.payment-error').hide();
        if (errors) {
            errors.forEach(function (error) {
                $("#sq-" + error.field +  "-errors").html( error.message ).show();
            });
            return;
        }


        var last_4 = cardData.last_4;

        this.$modal.modal('hide');

        this.square.verifyBuyer(nonce, verificationDetails, function (err, response) {
            if (err == null) {

                $form.find('input[name="square_card_nonce"]').val(nonce);
                $form.find('input[name="square_card_token"]').val(response.token);
                $form.find('input[name="payment"]').val('squarebyweight');
                var html = '<span class="payment-title">Pay with Card ending in ' + last_4 + '</span><span class="payment-remove cusrsor-pointer"><i class="fas fa-times"></i></span>';
                if($('#has-price-by-weight').val() == 1){
                    html += '<span class="payment-desc"><br />Note: Since your order contains items that are priced by weight, your card will not be charged until an exact order total is calculated.</span>';
                }
                $('#payment-text').html(html);

                $('.payment-remove').on('click', function(){
                    $form.find('input[name="square_card_nonce"]').val('');
                    $form.find('input[name="square_card_token"]').val('');

                    $('#payment-text').html('');
                    $form.find('input[name="payment"]').val('');
                });
            }
        });
    }

    var inputstyles = {
        fontSize: '16px',
        color: '#000',            //Sets color of CVV & Zip
        placeholderColor: '#A5A5A5', //Sets placeholder text color
        backgroundColor: '#ebe6e0'  //Card entry background color
    };

    ProcessSquare.DEFAULTS = {
        applicationId: undefined,
        locationId: undefined,
        orderTotal: undefined,
        currencyCode: undefined,
        errorSelector: '#square-card-errors',
        // Customize the CSS for SqPaymentForm iframe elements
        cardFields: {
            cardNumber: {
                elementId: 'sq-card',
                placeholder: '0000 0000 0000 0000'
            },
            cvv: {
                elementId: 'sq-cvv',
                placeholder: 'CVC'

            },
            expirationDate:{
                elementId: 'sq-exp',
                placeholder: 'MM/YY'
            },
            postalCode: {
                elementId: 'sq-zip',
                placeholder: '00000'

            },
            inputStyles: [inputstyles]
        }
    }

    // PLUGIN DEFINITION
    // ============================

    var old = $.fn.processSquare

    $.fn.processSquare = function (option) {
        var $this = $(this).first()
        var options = $.extend(true, {}, ProcessSquare.DEFAULTS, $this.data(), typeof option == 'object' && option)

        // only do this once per page load - important for modal display
        if($('#sq-card').is("div")){
            return new ProcessSquare($this, options)
        }
    }

    $.fn.processSquare.Constructor = ProcessSquare

    $.fn.processSquare.noConflict = function () {
        $.fn.processSquare = old
        return this
    }

    $(document).render(function () {
        $('#squareByWeightPaymentForm').processSquare()
    })
}(window.jQuery)