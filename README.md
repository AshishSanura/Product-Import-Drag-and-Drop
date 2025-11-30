### Project Setup Follow Step

## 1. Clone the Repository
git clone https://github.com/AshishSanura/Product-Import-Drag-and-Drop.git
cd Product-Import-Drag-and-Drop

## 2. Environment Setup
.env (This file chege your config)


## 4. Install Dependencies

1) composer update

2) php artisan migrate

3) php artisan make:mock-csv
	--> THis Command Create Demo CSV.
	
4) Task - A:- Run After
	-->php artisan queue:work (Please run Command)
	--> Unit Test run command
		-->php artisan test --filter=CsvImportTest

5) Task - B:- Run After
	-->php artisan queue:work (Please run Command)
	--> Unit Test run command
		-->php artisan test