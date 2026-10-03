MCS Purchasing & Inventory Tracking System (v4)

Stack: Laravel 11/12 + PostgreSQL
Theme: Icy Blue & Silver
IDE: VSCodium / VS Code

Complete database-driven application for the School of MCS (PNG University of Technology) covering Purchasing (GE orders) and Inventory.



Spec notes applied





HoS on Branch is informational only.



HoS is not a valid role. Allowed roles:
Administrator · Purchasing Officer · Inventory Officer · EndUser



GE order status is manually updated by Purchasing Officer / Administrator:
{Pending} → {Approved/Rejected/Cancelled} → {Ordered} → {Received} → {Backorder/Complete}



Requirements





PHP 8.2+



Composer



PostgreSQL 14+ (or SQLite for local/quick test)



Extensions: pdo_pgsql, pgsql, mbstring, xml, curl, zip



Setup (VSCodium)

cd mcs-pi-system

# Install PHP dependencies
composer install

# Environment
cp .env.example .env
php artisan key:generate

# Edit .env for PostgreSQL:
#   DB_CONNECTION=pgsql
#   DB_HOST=127.0.0.1
#   DB_PORT=5432
#   DB_DATABASE=mcs_pi
#   DB_USERNAME=postgres
#   DB_PASSWORD=your_password

# Create database in PostgreSQL first:
#   CREATE DATABASE mcs_pi;

php artisan migrate --seed

php artisan serve

Open http://127.0.0.1:8000

Demo accounts







User ID



Password



Role





admin



admin123



Administrator





po1



po123



Purchasing Officer





io1



io123



Inventory Officer





eu1



eu123



EndUser (view only)



Quick test without PostgreSQL

# In .env set:
DB_CONNECTION=sqlite
# touch database/database.sqlite
php artisan migrate --seed
php artisan serve



Running the full functionality tests

php artisan test --filter=McsPiSystemTest
# or
./vendor/bin/phpunit --filter=McsPiSystemTest

Tests cover:





Login & dashboard access



Role permissions (EndUser blocked from creating orders)



GE order creation (Pending)



Manual status update



Receipt → stock increase + weighted unit cost



Receipt blocked while status is still Pending



HoS is not in User::ROLES



Modules







Area



Features





Purchasing



GE Orders (STOCK/NON-STOCK), manual status, Suppliers, Receipts (partial + weighted avg cost)





Inventory



Items, ItemBranch stock, Issues, Adjustments, Customers, Assets





Reports



Stock levels, reorder list, purchase summary, issue history





Common



Role-based access, Branches (HoS info-only)

Unit cost on STOCK receipt

Unit Cost = (AmountReceived + currentValue) / (QtyReceived + currentStock)



Project layout (key paths)

app/Models/           Branch, User, Supplier, Item, ItemBranch, Order, Receipt, …
app/Http/Controllers/ Dashboard, Order, Receipt, Stock, Report, Auth, …
app/Http/Middleware/  EnsureRole
database/migrations/  All MCS tables (PostgreSQL-ready)
database/seeders/     Demo users, suppliers, items, stock, customers
resources/views/      Blade UI with icy blue & silver theme
public/css/theme.css  Theme
tests/Feature/        McsPiSystemTest.php



Development tips





php artisan migrate:fresh --seed resets data.



Frontend is server-rendered Blade; no Node build required for basic use.



Role middleware: ->middleware('role:Purchasing Officer') (Administrator always allowed).



Change history: see CHANGELOG.md. For future edits, append a dated entry there listing files touched.

