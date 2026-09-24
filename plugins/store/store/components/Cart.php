<?php namespace Store\Store\Components;

use Cms\Classes\ComponentBase;
use Store\Store\Classes\CartManager;
use Flash;
use Store\Store\Models\Coupon;
use Store\Store\Models\Product;
use Store\Store\Models\Cart as CartModel;
use Input;
use Session;
use Winter\User\Facades\Auth;

class Cart extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Cart Component',
            'description' => 'No description provided yet...'
        ];
    }

    public function defineProperties()
    {
        return [];
    }

    /* ============================================================
     |  Helper: احصل على CartManager مشترك
     ============================================================ */
    protected function manager()
    {
        return new CartManager();
    }

    /* ============================================================
     |  إضافة/تحديث كمية (زر + أو -)
     ============================================================ */
    public function onUpdateQuantityPluseOrSub()
    {
        $productId = input('idProduct');
        $quantity  = input('Quantity');
        $colorId   = input('color_id', null);

        $this->manager()->addToSessionCart($productId, $quantity, $colorId);

        return [
            '#cart-items' => $this->renderPartial('@cart-items.htm'),
            '#cart-count' => $this->manager()->getCartCount(),
        ];
    }

    /* ============================================================
     |  إضافة منتج للسلة
     ============================================================ */
    public function onAddToCart()
    {
        $productId = post('product_id');
        $quantity  = post('quantity', null);
        $colorId   = post('color_id', null);

        if (!$productId) {
            Flash::error('المنتج غير موجود');
            return;
        }

        $result = $this->manager()->addToSessionCart($productId, $quantity, $colorId);

        if (!$result) {
            Flash::error('تعذّر إضافة المنتج. تأكد من اختيار اللون.');
            return;
        }

        Flash::success('تمت إضافة المنتج إلى السلة بنجاح!');

return ['#cart-count' => $this->manager()->getCartCount()];

    }

    /* ============================================================
     |  تحميل محتوى الـ Popup
     ============================================================ */
    public function onLoadPageContent()
    {
        $id      = post('id', 1);
        $product = Product::with(['prices', 'sizes'])->find($id);

        return [
            '#winter-popup-content' => $this->renderPartial('@popup-partial.htm', ['product' => $product])
        ];
    }

    /* ============================================================
     |  عدّاد السلة
     ============================================================ */
    public function getCountCarts()
    {
        return $this->manager()->getCartCount();
    }

    public function getCountCartsCheckout()
    {
        return CartModel::where('user_id', Auth::getUser()->id)
            ->where('status', false)
            ->count();
    }

    /* ============================================================
     |  حذف منتج كامل بكل ألوانه (من زر حذف رأس المنتج)
     ============================================================ */
public function onRemoveFromCart()
{
    $productId = post('product_id');

    if (!$productId) {
        Flash::error('لم يتم تحديد المنتج');
        return;
    }

    $this->manager()->removeProductFromSessionCart($productId);

    Flash::success('تمت إزالة المنتج من السلة بنجاح!');

    return redirect('cart');
}

    /* ============================================================
     |  إضافة كمية للون/قياس من الـ Popup
     ============================================================ */
    public function onAddQuantityToProduct()
    {
        $productId = post('product_id');
        $quantity  = post('quantity', 1);
        $sizeId    = post('size_id', null);
        $colorId   = post('color_id', null);

        $this->manager()->addQuantityToProduct($productId, $quantity, $sizeId, $colorId);

        Flash::success('تم تحديث كمية المنتج في السلة بنجاح!');

        return redirect('cart');
    }

    /* ============================================================
     |  حذف لون واحد من السلة
     ============================================================ */
    public function onRemoveColorSizeFromCart()
    {
        $cartKey = post('cart_key');

        if ($cartKey) {
            $this->manager()->removeByCartKey($cartKey);
        } else {
            // fallback للتوافق مع الزر القديم
            $productId = post('product_id');
            $colorId   = post('color_id', null);
            $this->manager()->removeQuantityFromProduct($productId, null, $colorId);
        }

        Flash::success('تم حذف اللون من السلة');

        return redirect('cart');
    }

    

    /* ============================================================
     |  تطبيق الكوبون
     ============================================================ */
    public function onApplyCoupon()
    {
        $coupon = Coupon::where('code', post('coupon_code'))
            ->where('status', true)
            ->first();

        if (!$coupon) {
            Flash::error('كود الكوبون غير صالح!');
            return;
        }

        $userId = Auth::getUser()->id;
        Session::put("coupon-$userId", [
            'code'       => $coupon->code,
            'percentage' => $coupon->percentage,
            'coupon_id'  => $coupon->id,
        ]);

        Flash::success("تم تطبيق خصم {$coupon->percentage}% بنجاح!");

        return redirect('cart');
    }

    /* ============================================================
     |  إزالة الكوبون
     ============================================================ */
    public function onRemoveCoupon()
    {
        $userId = Auth::getUser()->id;
        Session::forget("coupon-$userId");

        Flash::success('تم إزالة الكوبون بنجاح!');

                return redirect('cart');

    }

    /* ============================================================
     |  تمرير البيانات للـ View
     ============================================================ */
    public function cartItems()
    {
        return $this->manager()->getCartItems();
    }

    public function getCoupon()
    {
        return $this->manager()->getCoupon();
    }

    /* ============================================================
     |  حفظ السلة في قاعدة البيانات (Checkout)
     ============================================================ */
    public function onSetCartTotal()
    {
        $user_id   = Auth::getUser()->id;
        $cartItems = $this->manager()->getCartItems();

        if (empty($cartItems)) {
            Flash::error('سلة التسوق فارغة. يرجى إضافة منتجات قبل متابعة الدفع.');
            return redirect()->back();
        }

        // ✅ استخدم الدالة الموحّدة لحساب الإجماليات
        $totals = $this->manager()->getCartTotals();

        $totalprice    = $totals['totalprice'];
        $totaltaxes    = $totals['totaltaxes'];
        $finalTotal    = $totals['final_total'];
        $coupon        = $this->manager()->getCoupon();

        if ($totalprice <= 0 && $finalTotal <= 0) {
            Flash::error('لا يمكن متابعة الدفع. قيمة الطلب غير صالحة.');
            return redirect()->back();
        }

        // إنشاء السلة في قاعدة البيانات
        $cart = CartModel::create([
            'user_id'          => $user_id,
            'total_price'      => $totalprice,
            'total_promotions' => $totaltaxes,
            'final_price'      => $finalTotal,
            'status'           => true,
            'delivered'        => false,
            'coupon_id'        => $coupon['coupon_id'] ?? null,
            'type'             => 'cashe',
        ]);

        // ✅ البنية الجديدة: كل عنصر = لون مستقل، بدون loop داخلي
        $cartItemsToInsert = [];
        foreach ($cartItems as $key => $item) {
            if (!isset($item['quantity']) || $item['quantity'] <= 0) {
                continue;
            }

            $cartItemsToInsert[] = [
                'product_id'   => $item['id'],
                'qty'          => $item['quantity'],
                'price'        => $item['price_after_taxes'] ?? $item['price_main'] ?? 0,
                'user_id'      => $user_id,
                'color_id'     => $item['color_id'] ?? null,
                'size_id'      => $item['size_id'] ?? null,
                'promotion_id' => null,
            ];
        }

        if (empty($cartItemsToInsert)) {
            Flash::error('لا توجد منتجات صالحة للإدراج.');
            return redirect()->back();
        }

        $cart->items()->createMany($cartItemsToInsert);

        // تفريغ السلة والكوبون
        $this->manager()->clearCart();
        $this->manager()->removeCouponFromSessionCart();

        Flash::success('تم حفظ سلة التسوق الخاصة بك بنجاح وهي جاهزة للدفع!');

        return redirect('checkout');
    }

    /* ============================================================
     |  جلب كل السلات من قاعدة البيانات
     ============================================================ */
    public function GetAllCartItems()
    {
        return CartModel::where('user_id', Auth::getUser()->id)->get();
    }

    /* ============================================================
     |  تأكيد الطلب
     ============================================================ */
    public function onPlaceOrder()
    {
        $cart_id = post('cart_id');
        $my_id   = post('my_id');

        if (CartModel::where('id', $cart_id)->where('user_id', Auth::getUser()->id)->exists()) {
            $cart = CartModel::find($cart_id);
            $cart->status       = true;
            $cart->location_lat = post('location_lat');
            $cart->location_lng = post('location_lng');
            $cart->address      = post('address');
            $cart->save();

            Flash::success('تم تقديم طلبك بنجاح!');
            return redirect('checkout');
        } else {
            Flash::error('عذراً، لم يتم العثور على سلة التسوق الخاصة بك.');
        }
    }
}