## Square By Weight

Square By Weight is an extension of `igniter/payregister`'s square payment method which handles delayed token billing in the following use-case:

- site has cupnoodles.priceByWeight enabled to allow for individual items to be priced according to weight
- site has cupnoodles.orderMenuEdit enabled to allow editing of orders after they've been packaged. 
- site does not use customer profiles 

If all of these criteria are met, then Square By Weight will create a payment method that defaults to igniter.payregister's square payment when an order contains only fixed-price items. When an order contains editable quantity items, Square by Weight will create a payment profile on Square for the customer under the email address and order number, and save the card token for later use. 

### Dependancies


### Installation

Clone these files into `extensions/cupnoodles/squarebyweight/`. 

### Usage 

SquareByWeight extends the `Order` class, for the sole reason of allowing a checkout process to complete without the `order.processed` payment proccessing flag having been set. In order for the delayed payment option to work, you'll need to replace the `[Order]` tag in your checkout page template with `[OrderByWeight]`.

in (probably) `<your_theme>/_pages/checkout/success.blade.php`

replace 
```
'[order]':
    hideReorderBtn: 1
```
with
```
'[orderPageByWeight]':
    hideReorderBtn: 1
```

Don't forget to set a paid/unpaid status in the payment settings so your staff knows which orders still need to be paid for!

Known Issues:
Payment Attempts aren't logging.