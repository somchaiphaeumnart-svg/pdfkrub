<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ToolController extends Controller
{
    /**
     * Tool definitions — single source of truth for all 50+ tools.
     *
     * @return array<string, mixed>
     */
    public static function allTools(): array
    {
        return [
            // --- Convert FROM PDF ---
            [
                'slug' => 'pdf-to-word',
                'name' => 'PDF to Word',
                'name_th' => 'PDF เป็น Word',
                'description_th' => 'แปลงไฟล์ PDF เป็น Word (.docx) รักษา Layout และฟอนต์ไทยดั้งเดิม',
                'icon' => '📄',
                'category' => 'convert-from-pdf',
                'color' => 'from-blue-600 to-blue-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'docx',
            ],
            [
                'slug' => 'pdf-to-excel',
                'name' => 'PDF to Excel',
                'name_th' => 'PDF เป็น Excel',
                'description_th' => 'แปลงตาราง PDF เป็น Excel (.xlsx) ที่แก้ไขได้',
                'icon' => '📊',
                'category' => 'convert-from-pdf',
                'color' => 'from-green-600 to-green-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'xlsx',
            ],
            [
                'slug' => 'pdf-to-pptx',
                'name' => 'PDF to PowerPoint',
                'name_th' => 'PDF เป็น PowerPoint',
                'description_th' => 'แปลงไฟล์ PDF เป็น PowerPoint (.pptx) ที่แก้ไขได้',
                'icon' => '🎯',
                'category' => 'convert-from-pdf',
                'color' => 'from-orange-600 to-orange-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pptx',
            ],
            [
                'slug' => 'pdf-to-jpg',
                'name' => 'PDF to JPG',
                'name_th' => 'PDF เป็น JPG',
                'description_th' => 'แปลงทุกหน้า PDF เป็นภาพ JPG คุณภาพสูง',
                'icon' => '🖼️',
                'category' => 'convert-from-pdf',
                'color' => 'from-yellow-600 to-yellow-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'jpg',
            ],
            [
                'slug' => 'pdf-to-png',
                'name' => 'PDF to PNG',
                'name_th' => 'PDF เป็น PNG',
                'description_th' => 'แปลงทุกหน้า PDF เป็นภาพ PNG พื้นหลังโปร่งใส',
                'icon' => '🖼️',
                'category' => 'convert-from-pdf',
                'color' => 'from-cyan-600 to-cyan-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'png',
            ],
            [
                'slug' => 'pdf-to-txt',
                'name' => 'PDF to Text',
                'name_th' => 'PDF เป็น Text',
                'description_th' => 'ดึงข้อความจาก PDF ออกมาเป็นไฟล์ .txt',
                'icon' => '📝',
                'category' => 'convert-from-pdf',
                'color' => 'from-slate-600 to-slate-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'txt',
            ],
            // --- Convert TO PDF ---
            [
                'slug' => 'word-to-pdf',
                'name' => 'Word to PDF',
                'name_th' => 'Word เป็น PDF',
                'description_th' => 'แปลง Word (.doc, .docx) เป็น PDF คุณภาพสูง รองรับฟอนต์ไทย',
                'icon' => '📝',
                'category' => 'convert-to-pdf',
                'color' => 'from-blue-700 to-blue-600',
                'accepts' => '.doc,.docx',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'excel-to-pdf',
                'name' => 'Excel to PDF',
                'name_th' => 'Excel เป็น PDF',
                'description_th' => 'แปลง Excel (.xls, .xlsx) เป็น PDF รักษาเส้นตาราง',
                'icon' => '📊',
                'category' => 'convert-to-pdf',
                'color' => 'from-emerald-700 to-emerald-600',
                'accepts' => '.xls,.xlsx',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'pptx-to-pdf',
                'name' => 'PowerPoint to PDF',
                'name_th' => 'PowerPoint เป็น PDF',
                'description_th' => 'แปลง PowerPoint (.ppt, .pptx) เป็น PDF',
                'icon' => '🎯',
                'category' => 'convert-to-pdf',
                'color' => 'from-red-700 to-red-600',
                'accepts' => '.ppt,.pptx',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'image-to-pdf',
                'name' => 'Image to PDF',
                'name_th' => 'รูปภาพเป็น PDF',
                'description_th' => 'แปลงภาพ JPG, PNG, WEBP เป็น PDF รวมหลายรูปในไฟล์เดียว',
                'icon' => '📷',
                'category' => 'convert-to-pdf',
                'color' => 'from-pink-600 to-pink-500',
                'accepts' => '.jpg,.jpeg,.png,.webp,.gif',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            // --- Edit & Organize ---
            [
                'slug' => 'merge-pdf',
                'name' => 'Merge PDF',
                'name_th' => 'รวมไฟล์ PDF',
                'description_th' => 'รวมหลาย PDF เข้าเป็นไฟล์เดียว จัดเรียงลำดับได้ตามต้องการ',
                'icon' => '🔗',
                'category' => 'organize',
                'color' => 'from-purple-600 to-purple-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'split-pdf',
                'name' => 'Split PDF',
                'name_th' => 'แยกไฟล์ PDF',
                'description_th' => 'แยก PDF เป็นหลายไฟล์ ตามหน้าที่ต้องการ หรือแยกทุกหน้า',
                'icon' => '✂️',
                'category' => 'organize',
                'color' => 'from-pink-600 to-pink-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'compress-pdf',
                'name' => 'Compress PDF',
                'name_th' => 'บีบอัด PDF',
                'description_th' => 'ลดขนาดไฟล์ PDF โดยไม่เสียคุณภาพมากนัก เหมาะสำหรับส่งอีเมล',
                'icon' => '🗜️',
                'category' => 'optimize',
                'color' => 'from-green-600 to-green-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'rotate-pdf',
                'name' => 'Rotate PDF',
                'name_th' => 'หมุนหน้า PDF',
                'description_th' => 'หมุนหน้า PDF ทั้งหมดหรือบางหน้า 90°, 180°, 270°',
                'icon' => '🔄',
                'category' => 'organize',
                'color' => 'from-indigo-600 to-indigo-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'delete-pages',
                'name' => 'Delete Pages',
                'name_th' => 'ลบหน้า PDF',
                'description_th' => 'ลบหน้าที่ไม่ต้องการออกจาก PDF',
                'icon' => '🗑️',
                'category' => 'organize',
                'color' => 'from-red-600 to-red-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'watermark-pdf',
                'name' => 'Watermark PDF',
                'name_th' => 'ใส่ลายน้ำ PDF',
                'description_th' => 'ใส่ลายน้ำข้อความหรือรูปภาพลงใน PDF',
                'icon' => '💧',
                'category' => 'organize',
                'color' => 'from-teal-600 to-teal-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'page-numbers',
                'name' => 'Page Numbers',
                'name_th' => 'ใส่เลขหน้า PDF',
                'description_th' => 'ใส่เลขหน้าเอกสาร PDF กำหนดตำแหน่ง รูปแบบตัวเลข และฟอนต์ได้อย่างอิสระ',
                'icon' => '🔢',
                'category' => 'organize',
                'color' => 'from-violet-600 to-violet-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'crop-pdf',
                'name' => 'Crop PDF',
                'name_th' => 'ครอบตัด PDF',
                'description_th' => 'ครอบตัดพื้นที่เอกสาร PDF ตัดขอบขาว หรือขอบดำสแกนที่ไม่ต้องการออก',
                'icon' => '📐',
                'category' => 'organize',
                'color' => 'from-sky-600 to-cyan-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'protect-pdf',
                'name' => 'Protect PDF',
                'name_th' => 'ใส่รหัสผ่าน PDF',
                'description_th' => 'เพิ่มรหัสผ่านป้องกันการเปิดและแก้ไข PDF',
                'icon' => '🔒',
                'category' => 'security',
                'color' => 'from-red-700 to-red-600',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'unlock-pdf',
                'name' => 'Unlock PDF',
                'name_th' => 'ถอดรหัส PDF',
                'description_th' => 'ลบรหัสผ่านออกจาก PDF (ต้องรู้รหัสผ่านเดิม)',
                'icon' => '🔓',
                'category' => 'security',
                'color' => 'from-amber-600 to-amber-500',
                'accepts' => '.pdf',
                'premium' => false,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'sign-pdf',
                'name' => 'Sign PDF',
                'name_th' => 'เซ็นเอกสาร PDF',
                'description_th' => 'เซ็นชื่อดิจิทัลบน PDF วาดลายเซ็น อัปโหลดรูป หรือพิมพ์',
                'icon' => '✍️',
                'category' => 'sign',
                'color' => 'from-indigo-600 to-indigo-500',
                'accepts' => '.pdf',
                'premium' => true,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'ocr-pdf',
                'name' => 'OCR PDF',
                'name_th' => 'OCR ภาษาไทย',
                'description_th' => 'อ่านข้อความจากภาพสแกนหรือ PDF ที่สแกนมา รองรับภาษาไทย 95+ ภาษา',
                'icon' => '🔍',
                'category' => 'ocr',
                'color' => 'from-orange-600 to-orange-500',
                'accepts' => '.pdf,.jpg,.jpeg,.png',
                'premium' => true,
                'output_format' => 'pdf',
            ],
            [
                'slug' => 'ai-summary',
                'name' => 'AI Summarize PDF',
                'name_th' => 'AI สรุป PDF',
                'description_th' => 'ให้ AI สรุปสาระสำคัญ จับประเด็นสำคัญ และทำ Action Items จากเอกสารของคุณ',
                'icon' => '✨',
                'category' => 'ai',
                'color' => 'from-purple-600 to-pink-500',
                'accepts' => '.pdf,.docx,.doc,.txt',
                'premium' => true,
                'output_format' => 'txt',
            ],
            [
                'slug' => 'pdf-editor',
                'name' => 'PDF Editor',
                'name_th' => 'แก้ไข PDF (Pro)',
                'description_th' => 'เครื่องมือแก้ไข PDF แบบครบวงจร วาด ไฮไลต์ ใส่โน้ต พิมพ์ข้อความ ประทับตรา จัดการหน้า',
                'icon' => '📝',
                'category' => 'edit',
                'color' => 'from-indigo-600 to-purple-600',
                'accepts' => '.pdf',
                'premium' => true,
                'output_format' => 'pdf',
            ],
        ];
    }

    /**
     * All tools index page.
     */
    public function index(): View
    {
        $tools = $this->allTools();
        $categories = [
            'convert-from-pdf' => 'แปลงจาก PDF',
            'convert-to-pdf' => 'แปลงเป็น PDF',
            'organize' => 'จัดระเบียบ PDF',
            'optimize' => 'บีบอัดและซ่อมแซม',
            'security' => 'ความปลอดภัย',
            'sign' => 'ลงนามเอกสาร',
            'ocr' => 'OCR',
            'ai' => 'ปัญญาประดิษฐ์ (AI)',
        ];

        $grouped = collect($tools)->groupBy('category');

        return view('tools.index', compact('grouped', 'categories'));
    }

    /**
     * Generic tool page — renders tool.show view with tool config.
     */
    private function showTool(string $slug): View
    {
        $tools = collect($this->allTools());
        $tool = $tools->firstWhere('slug', $slug);

        abort_unless($tool !== null, 404);

        return view('tools.show', compact('tool'));
    }

    // --- Individual tool routes ---
    public function pdfToWord(): View
    {
        return $this->showTool('pdf-to-word');
    }

    public function pdfToExcel(): View
    {
        return $this->showTool('pdf-to-excel');
    }

    public function pdfToPptx(): View
    {
        return $this->showTool('pdf-to-pptx');
    }

    public function pdfToJpg(): View
    {
        return $this->showTool('pdf-to-jpg');
    }

    public function pdfToPng(): View
    {
        return $this->showTool('pdf-to-png');
    }

    public function pdfToTxt(): View
    {
        return $this->showTool('pdf-to-txt');
    }

    public function wordToPdf(): View
    {
        return $this->showTool('word-to-pdf');
    }

    public function excelToPdf(): View
    {
        return $this->showTool('excel-to-pdf');
    }

    public function pptxToPdf(): View
    {
        return $this->showTool('pptx-to-pdf');
    }

    public function imageToPdf(): View
    {
        return $this->showTool('image-to-pdf');
    }

    public function mergePdf(): View
    {
        return $this->showTool('merge-pdf');
    }

    public function splitPdf(): View
    {
        return $this->showTool('split-pdf');
    }

    public function compressPdf(): View
    {
        return $this->showTool('compress-pdf');
    }

    public function rotatePdf(): View
    {
        return $this->showTool('rotate-pdf');
    }

    public function deletePages(): View
    {
        return $this->showTool('delete-pages');
    }

    public function cropPdf(): View
    {
        return $this->showTool('crop-pdf');
    }

    public function watermarkPdf(): View
    {
        return $this->showTool('watermark-pdf');
    }

    public function protectPdf(): View
    {
        return $this->showTool('protect-pdf');
    }

    public function unlockPdf(): View
    {
        return $this->showTool('unlock-pdf');
    }

    public function signPdf(): View
    {
        return view('tools.sign-pdf');
    }

    public function ocrPdf(): View
    {
        return view('tools.ocr-pdf');
    }

    public function aiSummary(): View
    {
        return view('tools.ai-summary');
    }

    public function editor(): View
    {
        return view('tools.pdf-editor');
    }

    public function pageNumbers(): View
    {
        return $this->showTool('page-numbers');
    }
}
