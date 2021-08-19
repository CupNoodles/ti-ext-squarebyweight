const sqccSelector = '#squareByWeightPaymentForm';  // this needs to be an ID
const sqccinfo = document.querySelector(sqccSelector);
const ccAppId = sqccinfo.dataset.applicationId;
const cclocationId = sqccinfo.dataset.locationId;


async function initializeCreditCard(payments) {
    const creditCard = await payments.card( {
      style: {
        'input': {
          backgroundColor: '#ebe6e0',
          color: '#000',
          fontSize: '16px',
          fontWeight: 'normal'
       }
      }
    });
    await creditCard.attach(sqccSelector);
    return creditCard;
}

async function tokenize(paymentMethod) {
    const tokenResult = await paymentMethod.tokenize();
    if (tokenResult.status === 'OK') {
      token = tokenResult.token;
    } else {
      let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
      if (tokenResult.errors) {
        errorMessage += ` and errors: ${JSON.stringify(
          tokenResult.errors
        )}`;
      }

      throw new Error(errorMessage);
    }

    return token;
}

 function createPayment(token) {

  document.querySelector('input[name="square_card_nonce"]').value = token;
  document.querySelector('input[name="payment"]').value = 'squarebyweight';
  /*
  $(window).on('ajaxUpdateComplete', function(){
    // manually trigger update on payment forms (gift card might remove payment options if it covers the full order value)
    $('[name=payment]').trigger('change');
  });
  */

}

document.addEventListener('DOMContentLoaded', async function () {

  let payments;
  try {
      payments = window.Square.payments(ccAppId, cclocationId);
  } catch {
      const statusContainer = document.getElementById(
      'payment-status-container'
      );
      statusContainer.className = 'missing-credentials';
      statusContainer.style.visibility = 'visible';
      return;
  }

  let creditCard;
  try {
    creditCard = await initializeCreditCard(payments);
  } catch (e) {
    console.error('Initializing Credit Card failed', e);
    return;
  }

  // code for handling tokenization and payments

  const creditCardButton = document.getElementById('squareCreditCardSubmitButton');
  creditCardButton.addEventListener('click', async function (event) {
    await handleCCPaymentMethodSubmission(event, creditCard);
  });

  async function handleCCPaymentMethodSubmission(event, paymentMethod) {
      event.preventDefault();
      
      try {
          const token = await tokenize(paymentMethod);
          const paymentResults = await createPayment(token);
      } catch (e) {
          console.error(e.message);
      }
  }

});