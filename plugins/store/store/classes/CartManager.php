<?php namespace Store\Store\Classes;

use Session;
use Store\Store\Models\Product;
use Store\Store\Models\Color;
use Store\Store\Models\Size;
use Winter\User\Facades\Auth;

class CartManager
{
    public $userId;

    public function __construct()
    {
        $this->userId = Auth::getUser()->id;
    }

    /* ============================================================
     |  المفتاح المركّب: "productId-colorId-sizeId"
     ============================================================ */
    protected function buildKey($productId, $colorId = null, $sizeId = null)
    {
        return $productId . '-' . ($colorId ?? '0') . '-' . ($sizeId ?? '0');
    }

    /* ============================================================
     |  إضافة منتج للسلة (يستخدم من popup + onAddToCart)
     ============================================================ */
    public function addToSessionCart($productId, $quantity = 1, $colorId = null, $sizeId = null)
    {
        $userId = $this->userId;
        $cart   = Session::get("cart-$userId", []);

        $product = Product::with(['prices' => function ($q) {
            $q->where('status', true);
        }])->find($productId);

        if (!$product) {
            return false;
        }

        // اختر السعر حسب اللون
        if ($colorId) {
            $selectedPrice = $product->prices->firstWhere('color_id', $colorId);
        } else {
            $selectedPrice = $product->prices->first();
        }

        if (!$selectedPrice) {
            return false;
        }

        $color = $colorId ? Color::find($colorId) : null;
        $size  = $sizeId  ? Size::find($sizeId)  : null;

        $unitPrice      = $selectedPrice->price;
        $taxes_products = $product->taxes_products()->where('status', true)->sum('price') ?? 0;
        $discount_price = $product->promotions()->where('status', true)->sum('discount_value') ?? 0;

        $cartKey = $this->buildKey($productId, $selectedPrice->color_id, $sizeId);

        if (isset($cart[$cartKey])) {
            // نفس المنتج/اللون/القياس → زد الكمية فقط
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $cart[$cartKey] = [
                'id'                => $productId,
                'color_id'          => $selectedPrice->color_id,
                'color_name'        => $color->name ?? ($selectedPrice->color->name ?? null),
                'color_code'        => $color->code ?? ($selectedPrice->color->code ?? null),
                'size_id'           => $sizeId,
                'size_name'         => $size->name ?? null,
                'quantity'          => $quantity,
                'name'              => $product->name,
                'price_main'        => $unitPrice,
                'price_after_taxes' => $unitPrice + $taxes_products - $discount_price,
                'taxes'             => $taxes_products,
                'image_path'        => $product->image ? $product->image->getPath() : null,
                'discount_price'    => $discount_price,
            ];
        }

        Session::put("cart-$userId", $cart);
        return true;
    }

    /* ============================================================
     |  alias للـ popup (نفس المنطق)
     ============================================================ */
    public function addQuantityToProduct($productId, $quantity = 1, $sizeId = null, $colorId = null)
    {
        return $this->addToSessionCart($productId, $quantity, $colorId, $sizeId);
    }

    /* ============================================================
     |  حذف عنصر واحد بواسطة المفتاح
     ============================================================ */
    public function removeByCartKey($cartKey)
    {
        $userId = $this->userId;
        $cart   = Session::get("cart-$userId", []);

        if (isset($cart[$cartKey])) {
            unset($cart[$cartKey]);
            Session::put("cart-$userId", $cart);
            return true;
        }

        return false;
    }

    /* ============================================================
     |  حذف كل ألوان منتج معيّن
     ============================================================ */
    public function removeProductFromSessionCart($productId)
    {
        $userId = $this->userId;
        $cart   = Session::get("cart-$userId", []);
        $prefix = $productId . '-';

        foreach (array_keys($cart) as $key) {
            if (strpos((string)$key, $prefix) === 0) {
                unset($cart[$key]);
            }
        }

        Session::put("cart-$userId", $cart);
        return true;
    }

    /* ============================================================
     |  إزالة لون+قياس محدد (توافق للخلف)
     ============================================================ */
    public function removeQuantityFromProduct($productId, $sizeId = null, $colorId = null)
    {
        $cartKey = $this->buildKey($productId, $colorId, $sizeId);
        return $this->removeByCartKey($cartKey);
    }

    /* ============================================================
     |  جلب عناصر السلة
     ============================================================ */
    public function getCartItems()
    {
        $userId = $this->userId;
        return Session::get("cart-$userId", []);
    }

    public function getCoupon()
    {
        $userId = $this->userId;
        return Session::get("coupon-$userId", []);
    }

    public function getCartCount()
    {
        $userId = $this->userId;
        return count(Session::get("cart-$userId", []));
    }

    /* ============================================================
     |  الإجماليات
     ============================================================ */
    public function getCartTotals()
    {
        $cart = $this->getCartItems();

        $totalprice    = 0;
        $totaltaxes    = 0;
        $totaldiscount = 0;
        $totalfinal    = 0;

        foreach ($cart as $item) {
            $qty = (int)($item['quantity'] ?? 0);
            if ($qty <= 0) continue;

            $totalprice    += ($item['price_main'] ?? 0) * $qty;
            $totaltaxes    += (($item['price_after_taxes'] ?? 0) - ($item['price_main'] ?? 0)) * $qty;
            $totaldiscount += ($item['discount_price'] ?? 0) * $qty;
            $totalfinal    += ($item['price_after_taxes'] ?? 0) * $qty;
        }

        $coupon           = $this->getCoupon();
        $couponPercentage = $coupon['percentage'] ?? 0;
        $couponDiscount   = ($totalfinal * $couponPercentage) / 100;
        $finalAfterCoupon = $totalfinal - $couponDiscount;

        return [
            'totalprice'        => $totalprice,
            'totaltaxes'        => $totaltaxes,
            'totaldiscount'     => $totaldiscount,
            'totalfinal'        => $totalfinal,
            'coupon_percentage' => $couponPercentage,
            'coupon_discount'   => $couponDiscount,
            'final_total'       => $finalAfterCoupon,
        ];
    }

    /* ============================================================
     |  حذف الكوبون / تفريغ السلة
     ============================================================ */
    public function removeCouponFromSessionCart()
    {
        Session::forget("coupon-{$this->userId}");
    }

    public function clearCart()
    {
        Session::forget("cart-{$this->userId}");
    }

    // alias للتوافق مع الكود القديم
    public function removeFromSessionCart($productId = null, $removeAll = false)
    {
        if ($removeAll) {
            return $this->clearCart();
        }
        if ($productId) {
            return $this->removeProductFromSessionCart($productId);
        }
        return $this->clearCart();
    }
}