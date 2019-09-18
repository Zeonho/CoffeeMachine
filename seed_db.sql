-- SQLite
DROP TABLE IF EXISTS coffees;
DROP TABLE IF EXISTS cups;
DROP TABLE IF EXISTS register;
DROP TABLE IF EXISTS orders;

CREATE TABLE coffees (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL, amount_left FLOAT NOT NULL);
CREATE TABLE cups (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255) NOT NULL, size FLOAT NOT NULL, cost FLOAT NOT NULL DEFAULT 0);
CREATE TABLE register (id INTEGER PRIMARY KEY AUTOINCREMENT, denomination DECIMAL(4,2) NOT NULL UNIQUE, amount INT NOT NULL);
CREATE TABLE orders (id INTEGER PRIMARY KEY AUTOINCREMENT, coffee_id INT NOT NULL, cup_id INT NOT NULL, loyalty_number INT NULL, paid DATETIME NULL);

INSERT INTO coffees (name, amount_left) VALUES
('Folgers', 20),
('French Roast', 50.4),
('Italian Roast', 100),
('Dunkin Donuts Blend', 200.8);

INSERT INTO cups (name, size, cost) VALUES
('Small', 8, 1.50),
('Large', 12, 3.00),
('Medium', 10, 2.75);

INSERT INTO register (denomination, amount) VALUES
('20', 5),
('1', 10),
('0.25', 10),
('0.10', 10),
('0.05', 10),
('0.01', 20);