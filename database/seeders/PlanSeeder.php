<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            // ─── FREE ────────────────────────────────────────────────────
            [
                'name' => 'free',
                'display_name' => 'Free',
                'display_name_th' => 'ฟรี',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'currency' => 'THB',
                'max_file_size_mb' => 10,
                'daily_conversions' => 5,
                'file_retention_hours' => 1,   // 1 hour — PDPA friendly
                'has_ocr' => false,
                'has_esign' => false,
                'has_watermark' => true,
                'has_api_access' => false,
                'max_team_members' => 1,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    'tools' => 'เครื่องมือ PDF พื้นฐาน',
                    'storage' => 'เก็บไฟล์ 1 ชั่วโมง (PDPA)',
                    'limit' => 'แปลงได้ 5 ครั้ง/วัน',
                    'support' => 'Community Support',
                ],
            ],

            // ─── PRO ─────────────────────────────────────────────────────
            [
                'name' => 'pro',
                'display_name' => 'Pro',
                'display_name_th' => 'โปร',
                'price_monthly' => 69,
                'price_yearly' => 590,   // ≈ ฿49/mo
                'currency' => 'THB',
                'max_file_size_mb' => 100,
                'daily_conversions' => -1,    // unlimited
                'file_retention_hours' => 168,   // 7 days
                'has_ocr' => true,
                'has_esign' => true,
                'has_watermark' => false,
                'has_api_access' => false,
                'max_team_members' => 1,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    'tools' => 'เครื่องมือ PDF ครบทุกอย่าง',
                    'storage' => 'เก็บไฟล์ 7 วัน',
                    'ocr' => 'OCR ภาษาไทย (Google Vision)',
                    'esign' => 'เซ็นเอกสารดิจิทัล',
                    'stamp' => 'ประทับสำเนาถูกต้อง',
                    'support' => 'Email Support',
                ],
            ],

            // ─── TEACHER ─────────────────────────────────────────────────
            [
                'name' => 'teacher',
                'display_name' => 'Teacher',
                'display_name_th' => 'ครู',
                'price_monthly' => 49,    // yearly-only, displayed as monthly equiv
                'price_yearly' => 390,
                'currency' => 'THB',
                'max_file_size_mb' => 200,
                'daily_conversions' => -1,
                'file_retention_hours' => 168,   // 7 days
                'has_ocr' => true,
                'has_esign' => true,
                'has_watermark' => false,
                'has_api_access' => false,
                'max_team_members' => 1,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    'tools' => 'เครื่องมือ PDF ครบทุกอย่าง',
                    'storage' => 'เก็บไฟล์ 7 วัน',
                    'pa' => 'รวม/เรียง/ใส่เลขหน้าหลักฐาน PA',
                    'ocr' => 'OCR ภาษาไทย (Google Vision)',
                    'esign' => 'ลงลายเซ็น + ลายน้ำโรงเรียน',
                    'stamp' => 'ประทับ "สำเนาถูกต้อง"',
                    'cert' => 'รวมเกียรติบัตรทีละหลายร้อยไฟล์',
                    'support' => 'Priority Email Support',
                ],
            ],

            // ─── SCHOOL ──────────────────────────────────────────────────
            [
                'name' => 'school',
                'display_name' => 'School',
                'display_name_th' => 'โรงเรียน',
                'price_monthly' => 499,   // representative monthly
                'price_yearly' => 2990,  // entry tier
                'currency' => 'THB',
                'max_file_size_mb' => 200,
                'daily_conversions' => -1,
                'file_retention_hours' => 720,   // 30 days
                'has_ocr' => true,
                'has_esign' => true,
                'has_watermark' => false,
                'has_api_access' => false,
                'max_team_members' => 30,    // up to 30 teachers
                'is_active' => true,
                'sort_order' => 4,
                'features' => [
                    'tools' => 'เครื่องมือ PDF ครบทุกอย่าง',
                    'storage' => 'เก็บไฟล์ 30 วัน',
                    'team' => 'ครูในโรงเรียนสูงสุด 30 คน',
                    'pa' => 'รวม PA หลักฐานทีมงาน',
                    'ocr' => 'OCR ภาษาไทย ไม่จำกัด',
                    'esign' => 'ลายน้ำ/ตราโรงเรียน',
                    'stamp' => 'ประทับสำเนาถูกต้องหมู่',
                    'cert' => 'รวมเกียรติบัตรทีละหลายร้อยไฟล์',
                    'admin' => 'แอดมินจัดการสิทธิ์ครู',
                    'support' => 'Priority Support + Chat',
                ],
            ],

            // ─── BUSINESS ────────────────────────────────────────────────
            [
                'name' => 'business',
                'display_name' => 'Business',
                'display_name_th' => 'ธุรกิจ',
                'price_monthly' => 990,
                'price_yearly' => 9900,
                'currency' => 'THB',
                'max_file_size_mb' => 200,
                'daily_conversions' => -1,
                'file_retention_hours' => 720,
                'has_ocr' => true,
                'has_esign' => true,
                'has_watermark' => false,
                'has_api_access' => true,
                'max_team_members' => 100,
                'is_active' => true,
                'sort_order' => 5,
                'features' => [
                    'tools' => 'เครื่องมือ PDF ครบทุกอย่าง',
                    'storage' => 'เก็บไฟล์ 30 วัน',
                    'api' => 'REST API Access ไม่จำกัด',
                    'team' => 'สมาชิกสูงสุด 100 คน',
                    'ocr' => 'OCR ภาษาไทย + อังกฤษ',
                    'esign' => 'e-Sign ระดับองค์กร',
                    'support' => 'Dedicated Account Manager',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(
                ['name' => $planData['name']],
                $planData
            );
        }
    }
}
