<?php namespace Store\Store\Models;

use Model;
// use Winter\Storm\Database\Builder;
// use BackendAuth;
/**
 * Model
 */
class Badge extends Model
{
    use \Winter\Storm\Database\Traits\Validation;
        use \Winter\Storm\Database\Traits\Sluggable;



    protected $slugs = ['slug' =>'name'];
 


    /**
     * @var string The database table used by the model.
     */
    public $table = 'store_store_badges';

    /**
     * @var array Validation rules
     */
       public $rules = [
        'name'   => 'required|string|min:2|max:255|unique:store_store_badges,name',
        'status' => 'boolean',
        'icon' => 'required',
        'slug' => 'required|alpha_dash|max:255|unique:store_store_badges,slug',
    ];

        public $belongsToMany = [
          'products' => ['Store\Store\Models\Product', 'table' => 'store_store_badges_products'],
         'related_badges' => [
        self::class,
        'table' => 'store_store_badges_related',
        'key' => 'badge_id',
        'otherKey' => 'badge_related_id'
    ]
    ];

  
    public $jsonable = [];


        public function afterSave()
{
    foreach ($this->related_badges as $badge) {
        $badge->related_badges()->syncWithoutDetaching([$this->id]);
    }
}



}
