<?php

namespace Coffee;

abstract class VendingMachine{

	protected $db;
	/**
	 * Tracks how many cups have been dispensed.
	 */
	public $dispensed = 0;
	
	/**
	 * The amount of coffee needed for each ounce of brewed coffee.
	 */
	const COFFEE_PER_OUNCE = 1.8;

	public function __construct($db){
		$this->db = $db;
	}
	
	/**
	 * Checks whether or not there is enough coffee available.
	 * 
	 * @param int The ID of the coffee to check.
	 * @param int The size in ounces of the coffee required.
	 * @return bool Whether or not there is enough coffee.
	 */ 
	abstract public function enoughCoffee(int $coffeeID, float $coffeeRequired): bool;
	
	/**
	 * Takes an order for a particular coffee and cup size.
	 *
	 * @return int The ID of the new order.
	 * @throws \Exception If the particular combination is not possible.
	 */
	abstract public function order(int $coffeeID, int $cupID, int $loyalty = NULL): int;
	
	/** 
	 * Calculates the amount owed for an order.
	 * 
	 * @return float The amount owed.
	 */
	abstract public function amountOwed(int $orderID): float;
	
	/**
	 * Makes a cash payment on a coffee order and returns the amount of change given to the custoemr.
	 */
	abstract public function pay(int $orderID, $amount);
	
	/**
	 * Brews the coffee for the order and dispenses it.
	 *
	 * @throws \Exception if they have not paid.
	 */
	abstract public function brew(int $orderID);
	
	/**
	 * Dispenses the coffee to the customer.
	 */
	protected function dispense(){
		$this->dispensed++;
	}
}

class OttoCoffee extends VendingMachine{
	public function enoughCoffee(int $coffeeID, float $coffeeRequired) : bool {
		$result = $this->db->query('SELECT amount_left FROM coffees WHERE id ==' . $coffeeID);
		$result.fetch();
		$amount_left = $result['amount_left'];
//		echo $amount_left;
		return $amount_left > $coffeeRequired ? TRUE : FALSE;
	}

	public function order(int $coffeeID, int $cupID, int $loyalty = NULL) : int{
		if (enoughCoffee($coffeeID, $cupID)) {
			$this->db->query("INSERT INTO orders(coffee_id, cup_id, loyalty_number) VALUES ($coffeeID, $cupID, $loyalty");
			$orderID = $this->db->lastInsertID();
            //free cups on five
            if ($loyalty) {
                $result2 = $this->db->query('SELECT count(id) AS \'OrderAmount\' FROM orders WHERE loyalty =' . $loyalty . ';');
                $result2.fetch();
                $orderAmount = $result2['OrderAmount'];
                if ($orderAmount != 0 && $orderAmount % 5 == 0) {
                    $this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
                }
            }

		} else {
			throw new Exception("This combination is not possible");
		}

		return intval($orderID);
	}


	public function amountOwed(int $orderID) : float {
        $result = $this->db->query('SELECT paid FROM orders WHERE id ==' . $orderID);
        $result.fetch();



	    if (!$result['paid']) {
            $result3 = $this->db->query('SELECT cost FROM cups INNER JOIN orders ON orders.cup_id = cups.id AND orders.id = ' . $orderID . ';');
            $result3.fetch();
            $cost = $result3['cost'];
            return $cost;
        } else {
	        return 0;
        }


	}

	public function pay(int $orderID, $amount) {
	    // Dealing with register
        $change = 0;
        if ($amount >= amountOwed($orderID)) {
            $this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
            $change = amountOwed($orderID) - $amount;
        }
        $changeArray = array("20" => 0,
                            "1" => 0,
                            "0.25" => 0,
                            "0.10" => 0,
                            "0.05" => 0,
                            "0.01" => 0);

        $changeArray["20"] = $change / 20;
        $changeArray["1"] = $change / 1;
        $changeArray["0.25"] = $change / 0.25;
        $changeArray["0.10"] = $change / 0.10;
        $changeArray["0.05"] = $change / 0.05;
        $changeArray["0.01"] = $change / 0.01;

        return $changeArray;


	}

	public function brew(int $orderID) {
		if (amountOwed($orderID) == 0) {
			// Reduce the amount of coffees
            $result = $this->db->query('SELECT size FROM cups INNER JOIN orders ON orders.cup_id = cups.id AND orders.id = ' . $orderID . ';');
            $result.fetch();
            $size = $result['size'];

            $result2 = $this->db->query('SELECT amount_left FROM coffees INNER JOIN orders ON orders.coffee_id = coffees.id AND $orderID = ' . $orderID . ';');
            $result2.fetch();
            $amount_left = $result2['amount_left'];

            $amount_left = $amount_left - ($size * COFFEE_PER_OUNCE);

            $this->db->prepare("UPDATE coffees SET amount_left = ? WHERE id = ?")->execute([$amount_left, $orderID]);
			dispense();
		} else {
			throw new Exception("Not paid yet");
		}
	}
}