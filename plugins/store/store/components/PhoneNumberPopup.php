<?php namespace Store\Store\Components;

use Cms\Classes\ComponentBase;
use Winter\User\Facades\Auth;
use Flash;

class PhoneNumberPopup extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name' => 'Phone Number Popup',
            'description' => 'Shows popup for users without phone number'
        ];
    }
    
    public function defineProperties()
    {
        return [
            'delay' => [
                'title' => 'Popup Delay',
                'description' => 'Delay in milliseconds before showing popup',
                'default' => 1000,
                'type' => 'string'
            ]
        ];
    }

     public function prepareVars()
    {
        $user = Auth::getUser();
        $this->page['user'] = $user;
        $this->page['showPopup'] = $this->shouldShowPopup();
        $this->page['popupDelay'] = $this->property('delay');
    }
    
    public function onRun()
    {
         $this->prepareVars();
    }
    
    public function shouldShowPopup()
    {
        $user = Auth::getUser();
        // التحقق من وجود المستخدم وعدم وجود رقم هاتف
        return $user && empty($user->phone) || empty($user->location_lat) || empty($user->location_lng);
    }
    
public function onUpdatePhoneNumber()
{
    $user = \Auth::getUser();
    
    if (!$user) {
        Flash::error('يجب تسجيل الدخول أولاً');
        return ;
    }
    
    $phoneNumber = post('phone_number');
    $locationLat = post('location_lat');
    $locationLng = post('location_lng');
    
    // التحقق من صحة الرقم
    if (empty($phoneNumber) || !preg_match('/^[0-9]{10,15}$/', $phoneNumber)) {
        Flash::error('الرجاء إدخال رقم موبايل صحيح (10-15 رقم)');
        return ;
    }

        if (empty($locationLat) || empty($locationLng) || !is_numeric($locationLat) || !is_numeric($locationLng)) {
        Flash::error('الرجاء إدخال إحداثيات الموقع الصحيحة');
        return ;
    }
    
    // التحقق من أن رقم الموبايل فريد
    $existingUser = \Winter\User\Models\User::where('phone', $phoneNumber)
        ->where('id', '!=', $user->id)
        ->first();
    
    if ($existingUser) {
        Flash::error('رقم الموبايل هذا مستخدم بالفعل من قبل مستخدم آخر');   
        return ;
    }
    
    // التحقق من الإحداثيات (إذا تم إرسالها)
    if (!empty($locationLat) && !empty($locationLng)) {
        if (!is_numeric($locationLat) || !is_numeric($locationLng)) {
            Flash::error('إحداثيات الموقع غير صحيحة');
            return ;
        }
        if ($locationLat < -90 || $locationLat > 90 || $locationLng < -180 || $locationLng > 180) {
            Flash::error('إحداثيات الموقع خارج النطاق المسموح');
            return ;
        }
    }
    
    try {
        $user->phone = $phoneNumber;
        
        // حفظ الإحداثيات إذا تم إرسالها
        if (!empty($locationLat) && !empty($locationLng)) {
            $user->location_lat = $locationLat;
            $user->location_lng = $locationLng;
        }
        
        $user->save();
        
        \Flash::success('تم تحديث البيانات بنجاح');
        
               return redirect()->refresh();
        
    } catch (\Exception $e) {
        \Flash::error('حدث خطاء: ' . $e->getMessage());
        return ;
    }
}

}   