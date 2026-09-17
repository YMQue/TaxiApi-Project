<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
     // إلغاء استخدام auto-increment لأن المفتاح الأساسي نصي
    public $incrementing = false;
    
    // تحديد نوع المفتاح الأساسي
    protected $keyType = 'string';

    // الأعمدة القابلة للتعبئة (Mass Assignment)
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    // تحويل بعض القيم إلى النوع المناسب عند القراءة (اختياري)
    protected function casts(): array
    {
        return [
            'value' => 'string', // يمكنك تخصيص التحويل حسب الحاجة
        ];
    }

    /**
     * إضافة Accessor لتسهيل قراءة القيمة كنوع محدد
     * مثلاً لقراءة commission_rate كـ float
     */
    public function getFloatValueAttribute()
    {
        return (float) $this->value;
    }

    public function getIntValueAttribute()
    {
        return (int) $this->value;
    }
}
