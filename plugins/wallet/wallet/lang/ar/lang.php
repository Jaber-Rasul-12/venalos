<?php

return [
    'plugin' => [
        'name' => 'المحفظة',
        'description' => '',
        'setting_store' => 'إعدادات المحفظة',
        'wallet' => 'المحفظة',
        'wallets' => 'المحافظ',
        'transactions' => 'العمليات',
    ],
    'model' => [
        'wallet' => [
            'id' => 'المعرف',
            'user' => 'المستخدم',
            'current_balance' => 'الرصيد الحالي',
            'public_id' => 'المعرف العام',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ],
        'transaction' => [
            'id' => 'المعرف',
            'public_id' => 'المعرف العام',
            'wallet' => 'المحفظة',
            'amount' => 'المبلغ',
            'description' => 'الوصف',
            'status' => 'الحالة',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
        ],
    ],
    'controller' => [
        'wallets' => [
            'wallets' => 'المحافظ',
        ],
        'transactions' => [
            'transactions' => 'العمليات',
        ],
    ],
];