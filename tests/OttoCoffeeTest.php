<?php

use PHPUnit\Framework\TestCase;

class OttoCoffeeTest extends TestCase
{
	protected $coffeeMaker;

	private $db;

	public function setUp(): void
	{
		$this->db = new \PDO('sqlite::memory:');
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec(file_get_contents('seed_db.sql'));

		$this->coffeeMaker = new Coffee\OttoCoffee($this->db);
	}
	
	/**
	 * @test
	 */
	public function has_enough_coffee(){
		$coffeeResult = $this->db->query('SELECT * FROM coffees');
		$coffees = $coffeeResult->fetchAll(\PDO::FETCH_OBJ);

		$coffee = $coffees[mt_rand(0, count($coffees)-1)];

		$this->assertEquals(TRUE, $this->coffeeMaker->enoughCoffee($coffee->id, $coffee->amount_left - 1.0));
	}
	
	/**
	 * @test
	 */
	public function not_enough_coffee(){
		$coffeeResult = $this->db->query('SELECT * FROM coffees');
		$coffees = $coffeeResult->fetchAll(\PDO::FETCH_OBJ);

		$coffee = $coffees[mt_rand(0, count($coffees)-1)];

		$this->assertEquals(FALSE, $this->coffeeMaker->enoughCoffee($coffee->id, $coffee->amount_left + 1.0));
	}
	
	/**
	 * @test
	 */
	public function take_an_order(){
		
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$orderID = $this->coffeeMaker->order($coffeeID, $cupID);
		
		$checkStatement = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE id = ?");
		$checkStatement->execute([$orderID]);
		$hasOrderID = (int)$checkStatement->fetchColumn();
		
		$this->assertEquals(1, $hasOrderID);
	}
	
	/**
	 * @test
	 */
	public function refuse_an_order(){
		
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 1)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$this->expectException(\Exception::class);
		
		$orderID = $this->coffeeMaker->order($coffeeID, $cupID);
		
		
	}
	
	/**
	 * @test
	 */
	public function not_paid(){
		
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$orderID = $this->coffeeMaker->order($coffeeID, $cupID);
		
		$this->expectException(\Exception::class);
		
		$this->coffeeMaker->brew($orderID);
	}
	
	/**
	 * @test
	 */
	public function already_paid(){
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$this->db->prepare("INSERT INTO orders (coffee_id, cup_id, paid) VALUES(?, ?, DATETIME())")->execute([$coffeeID, $cupID]);
		$orderID = $this->db->lastInsertID();
		
		$totalDue = $this->coffeeMaker->amountOwed($orderID);
		
		$this->assertEquals(0, $totalDue);
	}
	
	/**
	 * @test
	 */
	public function uses_correct_coffee(){
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$orderID = $this->coffeeMaker->order($coffeeID, $cupID);
		
		$this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
		
		$this->coffeeMaker->brew($orderID);
		
		$shouldHaveLeft = 100 - (10 * Coffee\OttoCoffee::COFFEE_PER_OUNCE);
		
		$coffeeStatement = $this->db->prepare("SELECT amount_left FROM coffees WHERE id = ?");
		$coffeeStatement->execute([$coffeeID]);
		$coffeeLeft = (float)$coffeeStatement->fetchColumn();
		
		$this->assertEqualsWithDelta($shouldHaveLeft, $coffeeLeft, 0.01);
	}
	
	/**
	 * @test
	 */
	public function free_cup_at_five(){
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		for($j = 1; $j < 2; $j++){
			for($i = 1; $i <= 4; $i++){
				$orderID = $this->coffeeMaker->order($coffeeID, $cupID, 12345);
				$amountDue = $this->coffeeMaker->amountOwed($orderID);
				
				$this->assertNotEquals(0, $amountDue, 'Should not be free.');
				
				$this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
			}
			
			$orderID = $this->coffeeMaker->order($coffeeID, $cupID, 12345);
			$amountDue = $this->coffeeMaker->amountOwed($orderID);
			
			$this->assertEquals(0, $amountDue, 'Should be free.');
			
			$this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
		}
		
	}

	
	
	/**
	 * @test
	 */
	public function coffee_is_getting_dispensed(){
		$this->db->query("INSERT INTO coffees(name, amount_left) VALUES ('Coffee A', 100)");
		$coffeeID = $this->db->lastInsertID();
		
		$this->db->query("INSERT INTO cups(name, size, cost) VALUES ('Cup A', 10, 5)");
		$cupID = $this->db->lastInsertID();
		
		$orderID = $this->coffeeMaker->order($coffeeID, $cupID);
		
		$this->db->prepare("UPDATE orders SET paid = DATETIME() WHERE id = ?")->execute([$orderID]);
		
		$dispensedBefore = $this->coffeeMaker->dispensed;
		
		$this->coffeeMaker->brew($orderID);
		
		$dispensedAfter = $this->coffeeMaker->dispensed;
		
		$this->assertEquals($dispensedBefore + 1, $dispensedAfter);
	}
}
