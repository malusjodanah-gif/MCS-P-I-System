<?php

namespace Database\Seeders;

use App\Models\Adjustment;
use App\Models\Asset;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Issue;
use App\Models\Item;
use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds interrelated demo data in FK-safe order:
 * Branch → Users → Suppliers → Items → ItemBranch → Customers
 * → Assets → Orders → Issue / Adjustment (with stock updates)
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedBranch();
            $this->seedUsers();
            $this->seedSuppliers();
            $this->seedItemsAndStock();
            $this->seedCustomers();
            $this->seedAssets();
            $this->seedOrders();
            $this->seedIssueAndAdjustment();
        });
    }

    private function seedBranch(): void
    {
        Branch::create([
            'branch_id' => 201,
            'branch' => 'School of MCS',
            'faculty' => 'Faculty of Sciences',
            'hos' => 'Prof. Dr. A. Sample (HoS - informational only)',
        ]);
    }

    private function seedUsers(): void
    {
        $users = [
            ['user_id' => 'admin', 'name' => 'System Administrator', 'role' => 'Administrator', 'password' => 'admin123'],
            ['user_id' => 'po1', 'name' => 'Purchasing Officer One', 'role' => 'Purchasing Officer', 'password' => 'po123'],
            ['user_id' => 'io1', 'name' => 'Inventory Officer One', 'role' => 'Inventory Officer', 'password' => 'io123'],
            ['user_id' => 'eu1', 'name' => 'End User One', 'role' => 'EndUser', 'password' => 'eu123'],
        ];

        foreach ($users as $u) {
            User::create([
                'user_id' => $u['user_id'],
                'name' => $u['name'],
                'email' => $u['user_id'] . '@mcs.pngut.ac.pg',
                'password' => Hash::make($u['password']),
                'branch_id' => 201,
                'role' => $u['role'],
            ]);
        }
    }

    private function seedSuppliers(): void
    {
        $suppliers = [
            [
                'supplier_id' => 5001,
                'supplier' => 'PNG Office Supplies Ltd',
                'address' => 'Section 1, Lot 5, Port Moresby',
                'contact' => 'John Doe - 3250000',
                'payment_term' => 'CREDIT',
                'currency' => 'PGK',
            ],
            [
                'supplier_id' => 5002,
                'supplier' => 'TechWorld PNG',
                'address' => 'Waigani Drive, NCD',
                'contact' => 'Jane Smith - 3251111',
                'payment_term' => 'CASH',
                'currency' => 'PGK',
            ],
            [
                'supplier_id' => 5003,
                'supplier' => 'CleanPro Services',
                'address' => 'Gordons Industrial',
                'contact' => 'Mike Brown - 3252222',
                'payment_term' => 'CREDIT',
                'currency' => 'PGK',
            ],
        ];

        foreach ($suppliers as $s) {
            Supplier::create($s);
        }
    }

    private function seedItemsAndStock(): void
    {
        // Every ItemBranch row references Item + Branch; UOM matches Item
        $catalogue = [
            // item_id, description, uom, category, sub_category, stock, unit_cost, location, reorder_lvl, reorder_qty
            [1001, 'A4 Copy Paper 80gsm (Ream)', 'REAM', 'CONSUMABLE', 'Stationery', 50, 25.00, 'Store Room A - Shelf 1', 20, 40],
            [1002, 'Ballpoint Pen Blue (Box of 50)', 'BOX', 'CONSUMABLE', 'Stationery', 15, 45.00, 'Store Room A - Shelf 2', 5, 10],
            [1003, 'Desktop Computer Dell OptiPlex', 'UNIT', 'ASSET', 'Computer', 8, 3500.00, 'ICT Lab 1', 2, 3],
            [1004, 'Laser Printer HP LaserJet', 'UNIT', 'ASSET', 'ICT Equipment', 3, 1200.00, 'Admin Office', 1, 1],
            [1005, 'Whiteboard Marker Black (Pack)', 'PACK', 'CONSUMABLE', 'Stationery', 30, 12.50, 'Store Room A - Shelf 3', 10, 20],
            [1006, 'Cleaning Detergent 5L', 'BOTTLE', 'CONSUMABLE', 'Cleaning', 12, 35.00, 'Cleaning Closet', 5, 10],
            [1007, 'Office Chair Ergonomic', 'UNIT', 'ASSET', 'Fixtures & Furniture', 20, 450.00, 'Staff Offices', 5, 5],
            [1008, 'Network Switch 24-Port', 'UNIT', 'ASSET', 'ICT Equipment', 2, 850.00, 'Server Room', 1, 1],
        ];

        foreach ($catalogue as $row) {
            $supplierId = 5001;
            if ($row[3] === 'ASSET') {
                $supplierId = 5002;
            } elseif ($row[4] === 'Cleaning') {
                $supplierId = 5003;
            }
            Item::create([
                'item_id' => $row[0],
                'item' => $row[1],
                'uom' => $row[2],
                'category' => $row[3],
                'sub_category' => $row[4],
                'supplier_id' => $supplierId,
            ]);

            ItemBranch::create([
                'item_id' => $row[0],
                'branch_id' => 201,
                'uom' => $row[2],
                'current_stock' => $row[5],
                'unit_cost' => $row[6],
                'location' => $row[7],
                'reorder_level' => $row[8],
                'reorder_quantity' => $row[9],
            ]);
        }
    }

    private function seedCustomers(): void
    {
        $customers = [
            ['customer_id' => 4001, 'customer' => 'Dr. Alice Kila', 'customer_type' => 'STAFF', 'email' => 'alice.kila@pngut.ac.pg'],
            ['customer_id' => 4002, 'customer' => 'Mr. Ben Tau', 'customer_type' => 'STAFF', 'email' => 'ben.tau@pngut.ac.pg'],
            ['customer_id' => 4003, 'customer' => 'Student Council MCS', 'customer_type' => 'STUDENT', 'email' => 'student.mcs@pngut.ac.pg'],
            ['customer_id' => 4004, 'customer' => 'MCS Operations', 'customer_type' => 'MCS', 'email' => 'ops.mcs@pngut.ac.pg'],
        ];

        foreach ($customers as $c) {
            Customer::create($c);
        }
    }

    private function seedAssets(): void
    {
        // Assets reclassed from ASSET catalogue items; linked to Item + Branch
        $assets = [
            [
                'asset_id' => 3001,
                'asset' => 'Dell OptiPlex — Lab Workstation 1',
                'serial_number' => 'SN-DELL-001',
                'brand' => 'Dell',
                'model' => 'OptiPlex 7090',
                'purchase_date' => '2025-03-15',
                'unit_cost' => 3500.00,
                'location' => 'ICT Lab 1',
                'branch_id' => 201,
                'status' => 'used',
                'po_number' => 1001,
                'item_id' => 1003,
            ],
            [
                'asset_id' => 3002,
                'asset' => 'HP LaserJet — Admin Printer',
                'serial_number' => 'SN-HP-LJ-014',
                'brand' => 'HP',
                'model' => 'LaserJet Pro M404',
                'purchase_date' => '2025-06-01',
                'unit_cost' => 1200.00,
                'location' => 'Admin Office',
                'branch_id' => 201,
                'status' => 'new',
                'po_number' => 1002,
                'item_id' => 1004,
            ],
            [
                'asset_id' => 3003,
                'asset' => 'Ergonomic Chair — Staff Room',
                'serial_number' => null,
                'brand' => 'OfficePro',
                'model' => 'Ergo-200',
                'purchase_date' => '2024-11-20',
                'unit_cost' => 450.00,
                'location' => 'Staff Offices',
                'branch_id' => 201,
                'status' => 'used',
                'po_number' => null,
                'item_id' => 1007,
            ],
            [
                'asset_id' => 3004,
                'asset' => '24-Port Network Switch — Server Room',
                'serial_number' => 'SN-SW-24-09',
                'brand' => 'Cisco',
                'model' => 'SG350-28',
                'purchase_date' => '2025-01-10',
                'unit_cost' => 850.00,
                'location' => 'Server Room',
                'branch_id' => 201,
                'status' => 'new',
                'po_number' => 1003,
                'item_id' => 1008,
            ],
        ];

        foreach ($assets as $a) {
            Asset::create($a);
        }
    }

    private function seedOrders(): void
    {
        $today = now()->toDateString();

        // GE 1101 — Pending STOCK
        Order::create([
            'ge_number' => 1101,
            'inventory_flag' => 'STOCK',
            'po_number' => 1101,
            'supplier_id' => 5001,
            'order_date' => $today,
            'user_id' => 'po1',
            'branch_id' => 201,
            'account_code' => '6100',
            'item_id' => 1001,
            'item' => 'A4 Copy Paper 80gsm (Ream)',
            'uom' => 'REAM',
            'quantity' => 20,
            'unit_cost' => 25.00,
            'total_cost' => 500.00,
            'status' => 'Pending',
        ]);

        // GE 1102 — Approved STOCK
        Order::create([
            'ge_number' => 1102,
            'inventory_flag' => 'STOCK',
            'po_number' => 1102,
            'supplier_id' => 5002,
            'order_date' => $today,
            'user_id' => 'po1',
            'branch_id' => 201,
            'account_code' => '6200',
            'item_id' => 1002,
            'item' => 'Ballpoint Pen Blue (Box of 50)',
            'uom' => 'BOX',
            'quantity' => 5,
            'unit_cost' => 45.00,
            'total_cost' => 225.00,
            'status' => 'Approved',
        ]);

        // GE 1103 — Ordered STOCK (ready to receive)
        Order::create([
            'ge_number' => 1103,
            'inventory_flag' => 'STOCK',
            'po_number' => 1103,
            'supplier_id' => 5001,
            'order_date' => $today,
            'user_id' => 'admin',
            'branch_id' => 201,
            'account_code' => '6100',
            'item_id' => 1005,
            'item' => 'Whiteboard Marker Black (Pack)',
            'uom' => 'PACK',
            'quantity' => 10,
            'unit_cost' => 12.50,
            'total_cost' => 125.00,
            'status' => 'Ordered',
        ]);

        // GE 1104 — NON-STOCK service
        Order::create([
            'ge_number' => 1104,
            'inventory_flag' => 'NON-STOCK',
            'po_number' => 1104,
            'supplier_id' => 5003,
            'order_date' => $today,
            'user_id' => 'po1',
            'branch_id' => 201,
            'account_code' => '6300',
            'item_id' => null,
            'item' => 'Quarterly laboratory cleaning service',
            'uom' => 'JOB',
            'quantity' => 1,
            'unit_cost' => 1500.00,
            'total_cost' => 1500.00,
            'status' => 'Pending',
        ]);

        // GE 1105 — multi-line Approved
        Order::create([
            'ge_number' => 1105,
            'inventory_flag' => 'STOCK',
            'po_number' => 1105,
            'supplier_id' => 5002,
            'order_date' => $today,
            'user_id' => 'po1',
            'branch_id' => 201,
            'account_code' => '6200',
            'item_id' => 1006,
            'item' => 'Cleaning Detergent 5L',
            'uom' => 'BOTTLE',
            'quantity' => 6,
            'unit_cost' => 35.00,
            'total_cost' => 210.00,
            'status' => 'Approved',
        ]);
        Order::create([
            'ge_number' => 1105,
            'inventory_flag' => 'STOCK',
            'po_number' => 1105,
            'supplier_id' => 5002,
            'order_date' => $today,
            'user_id' => 'po1',
            'branch_id' => 201,
            'account_code' => '6200',
            'item_id' => 1002,
            'item' => 'Ballpoint Pen Blue (Box of 50)',
            'uom' => 'BOX',
            'quantity' => 2,
            'unit_cost' => 45.00,
            'total_cost' => 90.00,
            'status' => 'Approved',
        ]);
    }

    private function seedIssueAndAdjustment(): void
    {
        // Issue 7001: markers to staff — decreases ItemBranch 1005
        $markerStock = ItemBranch::where('item_id', 1005)->where('branch_id', 201)->first();
        $issueQty = 2;
        Issue::create([
            'issue_number' => 7001,
            'branch_id' => 201,
            'item_id' => 1005,
            'quantity' => $issueQty,
            'uom' => $markerStock->uom,
            'customer_id' => 4001,
            'purpose' => 'Tutorial room markers',
            'user_id' => 'io1',
            'issue_date' => now()->toDateString(),
        ]);
        $markerStock->current_stock = $markerStock->current_stock - $issueQty;
        $markerStock->save();

        // Adjustment 5101 IN: detergent found — increases ItemBranch 1006
        // Number 5101 avoids clashing with supplier IDs 5001–5003 (different tables; clearer demo IDs)
        $detergentStock = ItemBranch::where('item_id', 1006)->where('branch_id', 201)->first();
        $adjQty = 3;
        Adjustment::create([
            'adjustment_number' => 5101,
            'adjustment_type' => 'IN',
            'branch_id' => 201,
            'item_id' => 1006,
            'quantity' => $adjQty,
            'uom' => $detergentStock->uom,
            'adjustment_date' => now()->toDateString(),
            'purpose' => 'Stocktake — extra bottles found',
            'user_id' => 'io1',
        ]);
        $detergentStock->current_stock = $detergentStock->current_stock + $adjQty;
        $detergentStock->save();
    }
}
