<?php

namespace Database\Seeders;

use App\Models\Menu\Menu;
use App\Models\Spatie\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class AdminMenuSeeder extends Seeder
{
    public $role;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->role = Role::where('name', 'admin')->first();
        Menu::where('role_id', $this->role->id)->delete();

        // Clear cache
        Cache::forget('menus:role:'.$this->role->id);

        // Create menu
        $this->dashboardMenu();
        $this->orderMenu();
        $this->webMenu();
        $this->marketingMenu();
        $this->memberMenu();
        $this->resellerMenu();
    }

    public function dashboardMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Dashboard',
            'url' => '/cms/dashboard',
            'icon' => 'LayoutGrid',
            'order' => 1,
            'active_pattern' => '/cms/dashboard',
            'status' => 1,
        ]);
    }

    public function orderMenu()
    {
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'All Orders',
            'url' => '/cms/order/all-orders',
            'icon' => 'ListOrdered',
            'order' => 4,
            'active_pattern' => '/cms/order/all-orders',
            'status' => 1,
        ]);
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Topup Orders',
            'url' => '/cms/order/topup-orders',
            'icon' => 'ShoppingCart',
            'order' => 5,
            'active_pattern' => '/cms/order/topup-orders',
            'status' => 1,
        ]);
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Gift Orders',
            'url' => '/cms/order/gift-orders',
            'icon' => 'Gift',
            'order' => 6,
            'active_pattern' => '/cms/order/gift-orders',
            'status' => 1,
        ]);
        Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Archive Orders',
            'url' => '/cms/order/archives',
            'icon' => 'Archive',
            'order' => 8,
            'active_pattern' => '/cms/order/archives',
            'status' => 1,
        ]);
    }

    public function webMenu()
    {
        $web = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Web',
            'url' => '#',
            'icon' => 'Globe',
            'order' => 20,
            'active_pattern' => '/cms/web',
            'status' => 1,
        ]);
        $web->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Banner',
            'url' => '/cms/web/sliders',
            'order' => 1,
            'active_pattern' => '/cms/web/sliders',
            'status' => 1,
        ]);
        $web->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'FAQs',
            'url' => '/cms/web/faqs',
            'order' => 2,
            'active_pattern' => '/cms/web/faqs',
            'status' => 1,
        ]);
        $web->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Vouchers',
            'url' => '/cms/web/vouchers',
            'order' => 3,
            'active_pattern' => '/cms/web/vouchers',
            'status' => 1,
        ]);
    }

    public function marketingMenu()
    {
        $marketing = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Marketing',
            'url' => '#',
            'icon' => 'Megaphone',
            'order' => 22,
            'active_pattern' => '/cms/marketing',
            'status' => 1,
        ]);
        $marketing->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Flash Sale',
            'url' => '/cms/marketing/flash-sales',
            'order' => 1,
            'active_pattern' => '/cms/marketing/flash-sales',
            'status' => 1,
        ]);
    }

    public function memberMenu()
    {
        $member = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Members',
            'url' => '#',
            'icon' => 'Users',
            'order' => 35,
            'active_pattern' => '/cms/members',
            'status' => 1,
        ]);
        $member->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'List Member',
            'url' => '/cms/members',
            'order' => 1,
            'active_pattern' => '/cms/members',
            'status' => 1,
        ]);
        $member->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Deposits',
            'url' => '/cms/deposits',
            'order' => 2,
            'active_pattern' => '/cms/deposits',
            'status' => 1,
        ]);
    }

    public function resellerMenu()
    {
        $reseller = Menu::create([
            'role_id' => $this->role->id,
            'name' => 'Reseller',
            'url' => '#',
            'icon' => 'Handshake',
            'order' => 36,
            'active_pattern' => '/cms/reseller',
            'status' => 1,
        ]);
        $reseller->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Daftar Reseller',
            'url' => '/cms/reseller',
            'order' => 1,
            'active_pattern' => '/cms/reseller',
            'status' => 1,
        ]);
        $reseller->subMenu()->create([
            'role_id' => $this->role->id,
            'name' => 'Pengajuan Reseller',
            'url' => '/cms/reseller/applications',
            'order' => 2,
            'active_pattern' => '/cms/reseller/applications',
            'status' => 1,
        ]);
    }
}
