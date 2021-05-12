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

