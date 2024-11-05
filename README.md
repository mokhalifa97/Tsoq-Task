
## Multi-Tenancy Product Addition

Multi-Tenant Implementation: The approach in this project provides data isolation by assigning each tenant its own database. This design choice is ideal for applications requiring high security, scalability, and tenant-specific data handling.

The tenant databases are created dynamically and associated with a unique identifier, known as the Tenant ID, which is provided in the request header. This ID helps the application dynamically switch between databases based on the active tenant.

# Note: this is a custom implementation for multi-tenancy without using a package like Spatie's multi-tenant package. the package documentation is straightforward and it make it easy to implement multi-tenancy as i sow it but i wanted to build it from screatch to understand the concept in depth. #


## Dynamic Database Switching

- Middleware : The 'TenantDatabaseSwitcher' middleware is responsible for intercepting requests and setting the correct database connection based on the Tenant ID.
- you can make tenant  by running the custom artisan command: php artisan tenant:setup {TenantName} {DatabaseName}.
- For each request, the middleware retrieves the Tenant ID and looks up the database name associated with that tenant.
- In the production environment, it configures the tenant's MySQL database connection.
- In the testing environment, SQLite’s in-memory database is used to speed up tests and ensure isolation.

## Tools, Libraries, and Packages Used

+ Laravel: Core framework used for application development.
+ Laravel Database Migrations: For managing and versioning database changes.
+ Custom Middleware (TenantDatabaseSwitcher): Middleware for dynamically switching databases based on tenant.
+ Testing: SQLite for fast, isolated, in-memory tests.
+ PHPUnit: Unit and feature testing.

## Setup Process

- git clone https://github.com/mokhalifa97/Tsoq-Task.git
- cd Tsoq-Task
- composer install
- cp.env.example.env
- php artisan tenant:setup {TenantName} {DatabaseName}
- php artisan migrate
- run product seeder 
- php artisan serve
- use the postman collection to test the API endpoints.
