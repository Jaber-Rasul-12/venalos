<?php namespace Store\Store\Components;

use Winter\User\Facades\Auth;
use Cms\Classes\ComponentBase;
use Store\Store\Models\Category;
use Store\Store\Models\EmailSubscribe;
use Store\Store\Models\Product;
use Store\Store\Models\ReturnPolicy;
use Flash;
use Store\Store\Models\Badge;
use Store\Store\Models\Brand;
use Store\Store\Models\Color;
use Store\Store\Models\Comment;
use Store\Store\Models\ContactMessage;
use Store\Store\Models\Size;
use Store\Store\Models\SubCategory;
use Store\Store\Models\Team;

class Store extends ComponentBase
{
    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'Store Component',
            'description' => 'No description provided yet...'
        ];
    }


        public function onGetProductWithSlug()
    {
        $slug =  $this->param('slug');
        if (isset($slug) && !empty($slug)) {
            return Product::with('prices')->where('slug', $slug)->get()->first();
        } else {
            return null;
        }
    }


    public function onAddComment()
    {
        $product_id = post('product_id');
        $comment = post('comment');
        $user_id = Auth::getUser()->id;
        $rating = post('rating');

        if (Comment::create(['product_id' => $product_id, 'comment' => $comment, 'user_id' => $user_id, 'rating' => $rating])) {
            Flash::success(trans('store.store::lang.plugin.success_save_comment'));

            $product_comments = Comment::where('product_id', $product_id)->get();
            
         return redirect('detail/' . $this->param('slug'));
        } else {
            Flash::error(trans('store.store::lang.plugin.please_check_your_input'));
        }
    }

    public function onSearchProductsWithCategory()
    {
        $queryString = post('text');
        $slug =  $this->param('slug');
        if (isset($slug) && !empty($slug)) {
            $products = Product::with('prices')->whereHas('subcategory_products', function($query) use ($slug) {
                $query->whereHas('category', function($query) use ($slug) {
                    $query->where('slug', $slug);
                });
            })->where('status', '=', true)->where('name', 'like', '%' . $queryString . '%')->orderBy('id' , 'desc')->get();
            return ['#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        } else {
            return null;
        }
        
    }

    public function onSearchProductsWithBadge()
    {
        $queryString = post('text');
        $slug =  $this->param('slug');
        if (isset($slug) && !empty($slug)) {
            $products = Product::with('prices')->whereHas('badges', function($query) use ($slug) {
                    $query->where('slug', $slug);
            })->where('status', '=', true)->where('name', 'like', '%' . $queryString . '%')->orderBy('id' , 'desc')->get();
            return ['#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        } else {
            return null;
        }
        
    }


    

    public function onSearchProductsWithSubCategory(){
                $queryString = post('text');
        $slug =  $this->param('slug');
       
        if (isset($slug) && !empty($slug)) {
            $products = Product::with('prices')->whereHas('subcategory_products', function($query) use ($slug) {
                $query->whereHas('subcategory', function($query) use ($slug) {
                    $query->where('slug', $slug);
                });
            })->where('status', '=', true)->where('name', 'like', '%' . $queryString . '%')->orderBy('id' , 'desc')->get();
            return ['#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        } else {
            return null;
        }
    }

     public function onSearchProductsWithBrand()
    {
        $queryString = post('name_brand');
        $slug =  $this->param('slug');
        if(empty($queryString)){
            
        }else if (isset($slug) && !empty($slug)) {
            $products = Product::with('prices')->whereHas('brand', function($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('status', '=', true)->where('name', 'like', '%' . $queryString . '%')->orderBy('id' , 'desc')->get();
            return ['#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false]) , '#pagindation' => ''];
        } else {
            return null;
        }
        
    }



    public function onGetTeamsBasics()
    {
        return Team::where('type', '=', 'basics')->get();
    }

    public function onGetTeamsAgent()
    {
        return Team::where('type', '=', 'agent')->get();
    }






    




    



        public function onGetProductsWhereCategory()
        {
            $slug = $this->param('slug');
            $page = post('page', 1);
            $perPage = 12; 
            
            if (isset($slug) && !empty($slug)) {
                $category_id = Category::where('slug', $slug)->first()->id;
                
                $products = Product::with('prices')->whereHas('subcategory_products', function($query) use ($category_id) {
                        $query->where('category_id', $category_id);
                    })->where('status', '=', true)->orderBy('id' , 'desc')
                    ->paginate($perPage, $page);
                if($page == 1 ){
                    return $products;
                }else{
                    return [
                        '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                        '@#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
                }        
            }
            
            return null;
        }

                public function onGetProductsWhereBadge()
        {
            $slug = $this->param('slug');
            $page = post('page', 1);
            $perPage = 12; 
            
            if (isset($slug) && !empty($slug)) {
                $badge_id = Badge::where('slug', $slug)->first()->id;
                
                $products = Product::with('prices')->whereHas('badges', function($query) use ($badge_id) {
                        $query->where('badge_id', $badge_id);
                    })->where('status', '=', true)->orderBy('id' , 'desc')
                    ->paginate($perPage, $page);
                if($page == 1 ){
                    return $products;
                }else{
                    return [
                        '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                        '@#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
                }        
            }
            
            return null;
        }


    public function onGetProductsWhereSubCategory()
{
    $slug = $this->param('slug');
    $page = post('page', 1);
    $perPage = 12; 
    
    if (isset($slug) && !empty($slug)) {
        $subcategory_id = SubCategory::where('slug', $slug)->first()->id;
        
        $products = Product::with('prices')
            ->whereHas('subcategory_products', function($query) use ($subcategory_id) {
                $query->where('subcategory_id', $subcategory_id);
            })
            ->orderBy('id', 'desc')
            ->paginate($perPage, $page);
        if($page == 1 ){
            return $products;
        }else{
            return [
                '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                '@#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        }        
    }
    
    return null;
}

    public function onGetRelatedCategories()
    {
        $slug = $this->param('slug');
    
        if (!$slug) {
            return null;
        }
    
        $category = Category::where('slug', $slug)
            ->where('status', true)
            ->first();
    
        if (!$category) {
            return null;
        }
    
        return $category->related_categories()->withCount('products')->where('status', true)->get();
    }

        public function onGetRelatedBadge()
    {
        $slug = $this->param('slug');
    
        if (!$slug) {
            return null;
        }
    
        $badge = Badge::where('slug', $slug)
            ->where('status', true)
            ->first();
    
        if (!$badge) {
            return null;
        }
    
        return $badge->related_badges()->withCount('products')->where('status', true)->get();
    }



        public function onGetRelatedSubCategories()
    {
        $slug = $this->param('slug');
    
        if (!$slug) {
            return null;
        }
    
        $subcategory = SubCategory::where('slug', $slug)
            ->first();
    
        if (!$subcategory) {
            return null;
        }
    
        return $subcategory->related_subcategories()->withCount('products')->get();
    }
    public function onGetRelatedProducts()
    {
        $slug = $this->param('slug');
    
        if (!$slug) {
            return null;
        }
    
        $category = Product::where('slug', $slug)
            ->where('status', true)
            ->first();
    
        if (!$category) {
            return null;
        }
    
        return $category->related_products()->where('status', true)->get();
    }
    public function onGetRelatedBrands()
    {
        $slug = $this->param('slug');
    
        if (!$slug) {
            return null;
        }
    
        $category = Brand::where('slug', $slug)
            ->where('status', true)
            ->first();
    
        if (!$category) {
            return null;
        }
    
        return $category->related_brands()->withCount('products')->where('status', true)->get();
    }


    

       public function onGetProductsWhereBrand()
{
    $slug = $this->param('slug');
    $page = post('page', 1);
    $perPage = 12; 
    
    if (isset($slug) && !empty($slug)) {    
        $products = Product::with('prices')->whereHas('brand', function($query) use ($slug) {
                $query->where('slug', $slug);
            })->where('status', '=', true)->orderBy('id' , 'desc')->paginate($perPage, $page);
        if($page == 1 ){
            return $products;
        }else{
            return [
                '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                '@#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        }        
    }
    
    return null;
}


       public function onGetProductsWherePromotions()
{
   
    $page = post('page', 1);
    $perPage = 12; 
    
    
        $products = Product::whereHas('promotions', function ($query) {
            $query->where('status', '=', true);
        })->where('status', '=', true)->orderBy('id' , 'desc')->paginate($perPage, $page);
        if($page == 1 ){
            return $products;
        }else{
            return [
                '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                '@#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        }            
}

 

         public function onSearchProductsWithPromotions()
    {
        $queryString = post('name_promotions');
        if (isset($queryString) && !empty($queryString)) {
            $products = Product::whereHas('promotions', function ($query) {
            $query->where('status', '=', true);
        })->where('status', '=', true)->where('name', 'like', '%' . $queryString . '%')->orderBy('id' , 'desc')->get();
            return ['#products-list_container' => $this->renderPartial('@products_lists_container.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false])];
        } else {
            return null;
        }
        
    }



    

    

     public function onSearchSuggestions()
    {
        $searchTerm = post('search');
        $filteredSuggestions = Product::where('name', 'like', '%' . $searchTerm . '%')->get();
        
        if ($filteredSuggestions->isEmpty() ) {
            return [
                '#searchSuggestions' =>  '<div class="p-3 text-center text-muted">لا توجد اقتراحات</div>',
            ];
        }

        if ( $searchTerm == '') {
            return [
                '#searchSuggestions' =>  '',
            ];
        }
        return [
            '#searchSuggestions' =>  $this->renderPartial('@suggestionsList.htm', ['suggestions' => $filteredSuggestions]),
        ];
    }


     public function onSendMessage()
    {
        $full_name = post('full_name');
        $email = post('email');
        $subject = post('subject');
        $message = post('message');

        if (ContactMessage::create(['full_name' => $full_name, 'email' => $email, 'subject' => $subject, 'message' => $message])) {
            Flash::success(trans('store.store::lang.plugin.success_save'));
        } else {
            Flash::error(trans('store.store::lang.plugin.please_check_your_input'));
        }
    }


    public function onSaveEmail()
    {
        $email = post('email');
        EmailSubscribe::create(['email' => $email]);
        Flash::success('save');
    }




    public function onGetAllProducts()
    {
       
        $page = post('page', 1);
        $perPage = 10; 
        
        
            $products = Product::with('prices')->where('status', '=', true)->orderBy('id' , 'desc')->paginate($perPage, $page);
            if($page == 1 ){
                return $products;
            }else{
                return [
                    '#pagindation' => $this->renderPartial('@pagindation.htm', ['GetAllProducts' => $products , 'pageNumber' => $page + 1 , 'nameAlgorithm' => __FUNCTION__]),
                    '@#mshop-products-list' => $this->renderPartial('@shop_list_products.htm', ['GetAllProducts' => $products , 'isAuth' => Auth::check() ? true : false]),];
            }            
    }

    public function onGetAllColors()
    {
        return Color::get();
    }   

    //     public function onGetAllBadges()
    // {
    //     return Badge::get();
    // }   

    public function onGetAllSizes()
    {
        return Size::get();
    }   

    

    public function onGetProductsIsBest()
    {
        return Product::with('prices')->where('is_best', '=', true)->where('status', '=', true)->orderBy('id' , 'desc')->get()->take(12);
    }

    public function onGetProductsIsFeatured()
    {
        return Product::with('prices')->where('is_featured', '=', true)->where('status', '=', true)->orderBy('id' , 'desc')->get()->take(12);
    }
    public function onGetProductsNew()
    {
        return Product::with('prices')->where('new_product', '=', true)->where('status', '=', true)->orderBy('id' , 'desc')->get()->take(12);
    }
    public function onGetProductsIsTop()
    {
        return Product::with('prices')->where('is_top', '=', true)->where('status', '=', true)->orderBy('id' , 'desc')->get()->take(12);
    }
    


    



    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [];
    }

    public function getCateogries()
    {
        return Category::with('subcategories')->withCount('products')->where('status' ,'=', true)->get();
    }

    public function getBrands()
    {
        return Brand::withCount('products')->where('status' ,'=', true)->get();
    }


public function getTopSubcategories()
{
    // جلب التصنيفات التي لها منتجات فقط
    $subcategories = SubCategory::whereHas('products')
        
        ->get();
    
    // إضافة عدد المنتجات يدوياً
    foreach ($subcategories as $subcategory) {
        $subcategory->products_count = $subcategory->products()->count();
    }
    
    // ترتيب حسب عدد المنتجات وأخذ أول 10
    return $subcategories->sortByDesc('products_count')->take(10);
}


public function getTopBrands()
{
    // جلب التصنيفات التي لها منتجات فقط
    $brands = Brand::whereHas('products')
        
        ->get();
    
    // إضافة عدد المنتجات يدوياً
    foreach ($brands as $brand) {
        $brand->products_count = $brand->products()->count();
    }
    
    // ترتيب حسب عدد المنتجات وأخذ أول 10
    return $brands->sortByDesc('products_count')->take(10);
}



public function getCateogriesOnHomePage()
{


    return Category::withCount(['products' => function($query) {
        $query->where('status', '=', true);
    }])
    ->where('status', '=', true)
    ->where('show_homepage', '=', true)
    ->get();
}
    public function getProductsShowHomePage()
    {
        return Product::whereHas('promotions', function ($query) {
            $query->where('status', '=', true);
        })->where('status', '=', true)->get();
    }

    public function onSearchCategories()
    {
        $queryString = post('text');
        $Categories = Category::withCount('products')->where('name', 'LIKE', "%" . $queryString . "%")->where('status' ,'=', true)->get();
        return ['#categories-items' => $this->renderPartial('@search_categories.htm', ['Categories' => $Categories])];
    }

    public function onSearchBrands()
    {
        $queryString = post('name_brands');
        $Brands = Brand::withCount('products')->where('name', 'LIKE', "%" . $queryString . "%")->where('status' ,'=', true)->get();
        return ['#brands-items' => $this->renderPartial('@search_brands.htm', ['Brands' => $Brands])];
    }


    


    public function onSearchProductsOnShop()
    {
        $queryString = post('text');
        $GetAllProducts = Product::where('name', 'LIKE', "%" . $queryString . "%")->where('status' ,'=', true)->orderBy('id' , 'desc')->paginate(6);
        return ['#mshop-products-list' => $this->renderPartial('@mshop_products_list.htm', ['GetAllProducts' => $GetAllProducts])];
    }




//     public function onFilterProducts()
// {
//     $minPrice = post('minPrice');
//     $maxPrice = post('maxPrice');
//     $colors = post('colors', []);
//     $sizes = post('sizes', []);
//     $searchText = post('searchText');

//     $query = Product::where('status', '=', true);

//     // فلترة السعر
//     if (!is_null($minPrice) && !is_null($maxPrice) && ($minPrice > 0 || $maxPrice > 0)) {
//         $query->whereHas('prices', function ($q) use ($minPrice, $maxPrice) {
//             if ($minPrice > 0 && $maxPrice > 0) {
//                 $q->whereBetween('price', [$minPrice, $maxPrice]);
//             } elseif ($minPrice > 0) {
//                 $q->where('price', '>=', $minPrice);
//             } elseif ($maxPrice > 0) {
//                 $q->where('price', '<=', $maxPrice);
//             }
//         });
//     }

//     // فلترة الألوان
//     if (!empty($colors) && is_array($colors)) {
//         $query->whereHas('colors', function ($q) use ($colors) {
//             $q->whereIn('code', $colors);
//         });
//     }

//     // فلترة الأحجام
//     if (!empty($sizes) && is_array($sizes)) {
//         $query->whereHas('sizes', function ($q) use ($sizes) {
//             $q->whereIn('name', $sizes);
//         });
//     }

//     // البحث بالاسم
//     if (!empty($searchText)) {
//         $query->where('name', 'LIKE', '%' . $searchText . '%');
//     }

//     $GetAllProducts = $query->orderBy('id', 'desc')->paginate(6);

//     return [
//         '#mshop-products-list' => $this->renderPartial('@mshop_products_list.htm', [
//             'GetAllProducts' => $GetAllProducts
//         ])
//     ];
// }
    
public function onGetAllBrands()
{
    return \Store\Store\Models\Brand::has('products')->get();
}

public function onGetAllMerchants()
{
    return \Store\Store\Models\Merchant::has('products')->get();
}

public function onGetAllBadges()
{
    return \Store\Store\Models\Badge::has('products')->get();
}

public function onGetAllReturnPolicies()
{
    return \Store\Store\Models\ReturnPolicy::has('products')->get();
}

public function onGetAllTaxes()
{
    return \Store\Store\Models\ProductTaxe::has('products')->get();
}
public function onFilterProducts()
{
    // ========== قراءة القيم ==========
    $minPrice       = post('minPrice', 0);
    $maxPrice       = post('maxPrice', 0);
    $colors         = post('colors', []);
    $sizes          = post('sizes', []);
    $searchText     = post('searchText', '');
    $brands         = post('brands', []);
    $merchants      = post('merchants', []);
    $badges         = post('badges', []);
    $returnPolicies = post('return_policies', []);
    $taxes          = post('taxes', []);
    $featured       = post('featured', false);
    $newProduct     = post('new_product', false);
    $isTop          = post('is_top', false);
    $isBest         = post('is_best', false);
    $hasPromotions  = post('has_promotions', false);
    $minRating      = post('minRating', 0);
    $sortBy         = post('sortBy', 'latest');

    // ========== بناء الاستعلام ==========
    $query = \Store\Store\Models\Product::where('status', true);

    // السعر
    if ($minPrice > 0 || $maxPrice > 0) {
        $query->whereHas('prices', function ($q) use ($minPrice, $maxPrice) {
            if ($minPrice > 0 && $maxPrice > 0) {
                $q->whereBetween('price', [$minPrice, $maxPrice]);
            } elseif ($minPrice > 0) {
                $q->where('price', '>=', $minPrice);
            } else {
                $q->where('price', '<=', $maxPrice);
            }
        });
    }

    // الألوان
    if (!empty($colors)) {
        $query->whereHas('colors', function ($q) use ($colors) {
            $q->whereIn('code', $colors);
        });
    }

    // الأحجام
    if (!empty($sizes)) {
        $query->whereHas('sizes', function ($q) use ($sizes) {
            $q->whereIn('name', $sizes);
        });
    }

    // العلامات التجارية
    if (!empty($brands)) {
        $query->whereIn('brand_id', $brands);
    }

    // التجار
    if (!empty($merchants)) {
        $query->whereIn('merchant_id', $merchants);
    }

    // الشارات
    if (!empty($badges)) {
        $query->whereHas('badges', function ($q) use ($badges) {
            $q->whereIn('id', $badges);
        });
    }

    // سياسات الإرجاع
    if (!empty($returnPolicies)) {
        $query->whereHas('return_policies', function ($q) use ($returnPolicies) {
            $q->whereIn('id', $returnPolicies);
        });
    }

    // الضرائب
    if (!empty($taxes)) {
        $query->whereHas('taxes_products', function ($q) use ($taxes) {
            $q->whereIn('id', $taxes);
        });
    }

    // الخصائص
    if ($featured)    $query->where('is_featured', true);
    if ($newProduct)  $query->where('new_product', true);
    if ($isTop)       $query->where('is_top', true);
    if ($isBest)      $query->where('is_best', true);

    if ($hasPromotions) {
        $query->whereHas('promotions', function ($q) {
            $q->where('status', true);
        });
    }

    // التقييم
    if ($minRating > 0) {
        $query->whereHas('comments', function ($q) use ($minRating) {
            $q->where('rating', '>=', $minRating);
        });
    }

    // البحث
    if (!empty($searchText)) {
        $query->where(function ($q) use ($searchText) {
            $q->where('name', 'LIKE', '%' . $searchText . '%')
              ->orWhere('short_name', 'LIKE', '%' . $searchText . '%');
        });
    }

    // الترتيب
    if ($sortBy === 'price_asc') {
        $query->orderBy('id', 'desc');
    } elseif ($sortBy === 'rating') {
        $query->orderBy('id', 'desc');
    } else {
        $query->orderBy('id', 'desc');
    }

    $GetAllProducts = $query->paginate(6);

    return [
        '#mshop-products-list' => $this->renderPartial('@mshop_products_list.htm', [
            'GetAllProducts' => $GetAllProducts
        ])
    ];
}







    


    public function onReturnPolicies(){
        return ReturnPolicy::where('status', '=', true)->get();
    }

    
}
