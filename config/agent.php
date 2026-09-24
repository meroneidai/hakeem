<?php

return [

    'retention_days' => (int) env('AGENT_RETENTION_DAYS', 90),

    'forms' => [
        'booking' => [
            'title' => 'بيانات الحجز',
            'submit' => 'أكّد الحجز',
            'fields' => [
                ['name' => 'patient_name', 'label' => 'الاسم', 'type' => 'text', 'required' => true],
                ['name' => 'patient_phone', 'label' => 'رقم الموبايل', 'type' => 'tel', 'required' => true],
                [
                    'name' => 'patient_home_address',
                    'label' => 'العنوان بالتفصيل',
                    'type' => 'text',
                    'required_when' => 'requires_patient_address',
                ],
                ['name' => 'notes', 'label' => 'ملاحظات (اختياري)', 'type' => 'textarea', 'required' => false],
            ],
        ],
        'register' => [
            'title' => 'إنشاء حساب',
            'submit' => 'سجّل',
            'fields' => [
                ['name' => 'name', 'label' => 'الاسم', 'type' => 'text', 'required' => true],
                ['name' => 'phone', 'label' => 'رقم الموبايل', 'type' => 'tel', 'required' => true],
                ['name' => 'password', 'label' => 'كلمة المرور', 'type' => 'password', 'required' => true],
            ],
        ],
    ],

];
