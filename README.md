# The Challenge

Develop the software befind a self-serve coffee vending machine for a company called Otto's Coffee. Customers will be able to select their roast and cup size, pay for their order, and watch as their coffee and brewed and dispensed. Go the extra mile and allow for customers to enter their loyalty rewards number and they get each 5th cup free!


## Ordering

Customers should be presented with a simple UI where they can select the roast of their choice--as long as their is enough coffee available in that blend.
	
The customer should be able to choose from the available cup sizes--again as long as their is enough coffee available to fill that cup.
	
The customer will pay for their order (cash only!) and receive the proper amount of change back from the available bills and coins in the machine.

The machine must measure out the appropriate amount of coffee for their cup size, brew the coffee, then dispense it.
	
## Notes

There is a VendingMachine abstract class that must be extended by a new class named OttoCoffee to perform these actions.

For the challenge a simple SQLite database with PDO connectivity will be used and a demo database is provided which provides the available blends, cup sizes, and a starting amount of money in the register. Feel free to modify or extend the database schema as needed!
	
Unit tests are provided that will validate that the logic in your OttoCoffee class works as inteded. Add any tests for cases that may be missing. You can run the tests by calling `composer test` from the root path.

Provide both the raw source and a compiled copy (if applicable) of your UI code. For the challenge you can use the PHP built-in server to simplify things.