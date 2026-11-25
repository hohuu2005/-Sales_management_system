<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LichSuHoaDonPosTest extends TestCase
{
    use RefreshDatabase;

    private function createSalesUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'employee_code' => 'NV-HISTORY',
            'username' => 'thu-ngan-history',
            'password_hash' => Hash::make('12345678'),
            'full_name' => 'Thu ngan History',
            'email' => 'thu-ngan-history@sims.vn',
            'phone' => null,
            'role' => 'SALES',
            'work_shift' => 'Ca test',
            'is_active' => true,
        ], $overrides));
    }

    private function createOrder(User $cashier, string $code, string $createdAt, float $total, string $status = 'COMPLETED'): Order
    {
        $order = Order::create([
            'order_code' => $code,
            'cashier_id' => $cashier->id,
            'total_amount' => $total,
            'customer_paid' => $status === 'CANCELLED' ? 0 : $total,
            'change_amount' => 0,
            'payment_method' => 'CASH',
            'status' => $status,
        ]);
        DB::table('orders')->where('id', $order->id)->update(['created_at' => $createdAt]);
        return $order->fresh();
    }

    public function test_loc_lich_su_theo_ngay(): void
    {
        $sales = $this->createSalesUser();
        $this->createOrder($sales, 'ORD-DATE-1', '2026-11-20 10:00:00', 100000);
        $this->createOrder($sales, 'ORD-DATE-2', '2026-11-22 10:00:00', 200000);
        $this->actingAs($sales)->get('/pos/history?start_date=2026-11-21&end_date=2026-11-25')
            ->assertOk()->assertSee('ORD-DATE-2')->assertDontSee('ORD-DATE-1');
    }

    public function test_end_date_truoc_start_date_bao_loi_tieng_viet(): void
    {
        $sales = $this->createSalesUser();
        $this->actingAs($sales)->from('/pos/history')->get('/pos/history?start_date=2026-11-25&end_date=2026-11-20')
            ->assertRedirect('/pos/history')->assertSessionHasErrors(['end_date']);
    }

    public function test_lich_su_phan_trang_20_trang_hai_co_phan_con_lai(): void
    {
        $sales = $this->createSalesUser();
        for ($i = 1; $i <= 25; $i++) {
            $this->createOrder($sales, 'ORD-PAGE-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT), '2026-11-' . str_pad((string) (($i % 20) + 1), 2, '0', STR_PAD_LEFT) . ' 10:00:00', 1000 * $i);
        }
        $this->actingAs($sales)->get('/pos/history?page=2')->assertOk()->assertSeeText('Trang 2')->assertSee('ORD-PAGE-22');
    }

    public function test_khong_thay_hoa_don_cua_thu_ngan_khac(): void
    {
        $sales = $this->createSalesUser();
        $other = $this->createSalesUser(['employee_code' => 'NV-OTHER', 'username' => 'thu-ngan-khac', 'email' => 'thu-ngan-khac@sims.vn']);
        $this->createOrder($sales, 'ORD-MINE', '2026-11-20 10:00:00', 100000);
        $this->createOrder($other, 'ORD-OTHER', '2026-11-20 11:00:00', 200000);
        $this->actingAs($sales)->get('/pos/history')->assertOk()->assertSee('ORD-MINE')->assertDontSee('ORD-OTHER');
    }

    public function test_xem_chi_tiet_hoa_don_cua_minh_khong_lo_unit_cost(): void
    {
        $sales = $this->createSalesUser();
        $order = $this->createOrder($sales, 'ORD-DETAIL-MINE', '2026-11-20 10:00:00', 100000);
        $this->actingAs($sales)->get('/pos/history/' . $order->id)
            ->assertOk()->assertSee('ORD-DETAIL-MINE')->assertDontSee('unit_cost')->assertDontSee('giá vốn');
    }

    public function test_xem_hoa_don_thu_ngan_khac_tra_404(): void
    {
        $sales = $this->createSalesUser();
        $other = $this->createSalesUser(['employee_code' => 'NV-OTHER-2', 'username' => 'thu-ngan-khac-2', 'email' => 'thu-ngan-khac-2@sims.vn']);
        $order = $this->createOrder($other, 'ORD-OTHER-DETAIL', '2026-11-20 10:00:00', 100000);
        $this->actingAs($sales)->get('/pos/history/' . $order->id)->assertNotFound();
    }

    public function test_xem_hoa_don_khong_ton_tai_tra_404(): void
    {
        $sales = $this->createSalesUser();
        $this->actingAs($sales)->get('/pos/history/999999')->assertNotFound();
    }

    public function test_tai_khoan_khong_phai_sales_khong_vao_lich_su(): void
    {
        $admin = $this->createSalesUser(['employee_code' => 'NV-ADMIN-HISTORY', 'username' => 'admin-history', 'email' => 'admin-history@sims.vn', 'role' => 'ADMIN']);
        $this->actingAs($admin)->get('/pos/history')->assertForbidden();
    }

    public function test_hoa_don_cancelled_hien_da_huy_va_khong_tinh_tong(): void
    {
        $sales = $this->createSalesUser();
        $this->createOrder($sales, 'ORD-COMPLETED', '2026-11-20 10:00:00', 100000);
        $this->createOrder($sales, 'ORD-CANCELLED', '2026-11-20 11:00:00', 900000, 'CANCELLED');
        $this->actingAs($sales)->get('/pos/history')
            ->assertOk()->assertSee('Hoàn tất')->assertSee('Đã hủy')->assertSee('100.000 đ');
    }

    public function test_so_truy_van_khong_tang_theo_so_hoa_don(): void
    {
        $sales = $this->createSalesUser();
        for ($i = 1; $i <= 25; $i++) {
            $this->createOrder($sales, 'ORD-Q-' . $i, '2026-11-' . str_pad((string) (($i % 20) + 1), 2, '0', STR_PAD_LEFT) . ' 10:00:00', 1000);
        }
        DB::enableQueryLog();
        $this->actingAs($sales)->get('/pos/history');
        $countOne = count(DB::getQueryLog());
        DB::flushQueryLog();
        $this->actingAs($sales)->get('/pos/history');
        $countTwo = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual($countOne + 1, $countTwo);
    }
}
