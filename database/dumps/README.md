# Database Backup

## Database: laravel_restaurant_db

This directory contains database dumps for the Restaurant Management System.

### Current Features Included:
- Employee authentication system with role-based access
- Waiter dashboard with table management
- Order creation and management system
- Customer order tracking
- Menu planning integration
- Allergen management (cleaned up)
- Mobile-optimized modal interfaces

### How to Import:
```bash
mysql -u root -p laravel_restaurant_db < database/dumps/laravel_restaurant_db.sql
```

### Tables Included:
- users
- employees
- roles
- employee_roles
- restaurant_data
- tables
- dishes
- menu_categories
- menu_plans
- menu_plan_dishes
- customer_orders (new)
- customer_order_items (new)
- ingredients
- suppliers
- And more...

### Recent Updates:
- Added waiter order management system
- Implemented modal-based quantity selection
- Fixed mobile layout for order creation
- Removed image dependencies from dishes
- Added Philippine peso currency support
- Enhanced allergen display logic

Note: Please manually export your database using your preferred MySQL client and place the SQL file here as `laravel_restaurant_db.sql`