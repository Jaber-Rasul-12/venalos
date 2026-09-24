<?php namespace Store\Store\Models;

use Model;
// use Winter\Storm\Database\Builder;
// use BackendAuth;
/**
 * Model
 */
class Price extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\Nullable;

    
  

    protected $nullable = ['price_merchant' , 'profit_percentage'];


    /**
     * @var string The database table used by the model.
     */
    public $table = 'store_store_prices';

        /**
     * @var array Validation rules
     */
    public $rules = [
        'price' => 'required|numeric|min:0',
        'price_merchant' => 'nullable|numeric|min:0',
        'profit_percentage' => 'nullable|numeric|min:0',
        'product_id' => 'required|exists:store_store_products,id',
        'color_id' => 'nullable|exists:store_store_colors,id',
        'status' => 'required|boolean'
    ];

    protected $fillable = ['price' , 'price_merchant' , 'profit_percentage' , 'product_id' , 'color_id' , 'status'];

        public $belongsTo = [
        'product' => [Product::class, 'key' => 'product_id'],
        'color' => [Color::class, 'key' => 'color_id'],
    ];



        public $attachOne = [
        'image' =>[\System\Models\File::class] 
    ];

public function getColorOptions()
{
    $options = [];

    foreach (Color::all() as $color) {
        $code = e($color->code);
        $name = e($color->name ?? $color->code);

        $options[$color->id] = sprintf(
            '<span style="display:inline-flex;align-items:center;gap:6px;">
                <span style="
                    display:inline-block;
                    width:18px;
                    height:18px;
                    border-radius:50%%;
                    background:%s;
                    border:1px solid #ccc;
                "></span>
                <span>%s</span>
            </span>',
            $code,
            $name
        );
    }

    return $options;
}

 

    /**
   * Filter and set options for form fields based on certain conditions.
   *
   * @param object $fields   The form fields.
   * @param mixed  $context  Additional context information if needed.
   *
   */
public function filterFields($fields, $context = null)
{
    if($context === 'create') {
        // التحقق من وجود القيم وعدم كونها فارغة
        if (isset($fields->price_merchant->value, $fields->profit_percentage->value) 
            && !empty($fields->price_merchant->value) 
            && !empty($fields->profit_percentage->value)) {
            
            // جلب القيم وتحويلها إلى أرقام عشرية (float)
            $priceMerchant = (float) $fields->price_merchant->value;
            $profitPercentage = (float) $fields->profit_percentage->value;
            
            // حساب سعر البيع: السعر الأصلي + (السعر الأصلي * نسبة الربح المئوية / 100)
            $calculatedPrice = $priceMerchant * (1 + ($profitPercentage / 100));
            
            // تعيين القيمة المحسوبة لحقل price
            $fields->price->value = $calculatedPrice;
        }
    }
}

 

}
