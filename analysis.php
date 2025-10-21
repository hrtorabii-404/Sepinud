<?php
// ========== فعال‌سازی نمایش خطا (فقط log، نه display) ==========
ini_set('display_errors', 0);  // off برای جلوگیری از JSON خراب
ini_set('log_errors', 1);      // خطاها رو log کن
error_reporting(E_ALL);
// =================================================================

header('Content-Type: application/json; charset=utf-8');

// ========== تنظیمات مهم ==========
// !! توکن Hugging Face خودت رو اینجا قرار بده !!
$hfToken = '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'; // <--- توکن Hugging Face شما
// ==================================

// آدرس API مدل Llama 3 8B Instruct در Hugging Face
$modelUrl = 'https://api-inference.huggingface.co/models/meta-llama/Meta-Llama-3-8B-Instruct';

try {
    // مرحله 1: بررسی پیش‌نیازهای سرور
    if (!function_exists('curl_init')) {
        throw new Exception('ماژول cURL روی سرور فعال نیست.');
    }

    // مرحله 2: بررسی نوع درخواست
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('درخواست نامعتبر است. (POST expected)');
    }

    // مرحله 3: دریافت و پاکسازی اطلاعات فرم (بدون تغییر)
    $firstName = htmlspecialchars($_POST['FirstName'] ?? 'کاربر');
    $lastName = htmlspecialchars($_POST['LastName'] ?? '');
    $mobile = htmlspecialchars($_POST['Mobile'] ?? 'ارسال نشده');
    // ... (سایر متغیرهای فرم) ...
    $what = htmlspecialchars($_POST['What'] ?? 'مشخص نشده');
    $whichPlace = htmlspecialchars($_POST['WhichPlace'] ?? 'مشخص نشده');
    $description = htmlspecialchars($_POST['Description'] ?? '');

    if (empty($description)) {
        throw new Exception('فیلد "میزان سرمایه گذاری و سایر موارد" الزامی است.');
    }
    if (strpos($hfToken, 'hf_') !== 0 || strlen($hfToken) < 30) {
         throw new Exception('توکن Hugging Face در فایل analysis.php به درستی تنظیم نشده است.');
    }


    // ========== مرحله 4: طراحی پرامپت داشبورد HTML (بدون تغییر) ==========
    // از همان پرامپت عالی قبلی که داشبورد HTML می‌ساخت استفاده می‌کنیم
    $prompt = "
    **نقش:** شما 'طراح رابط کاربری و تحلیلگر هوشمند سپینود' هستید.
    **لحن:** حرفه‌ای، مثبت، جذاب بصری، دقیق و کاربرپسند.
    **مخاطب:** $firstName {$lastName}.
    **وظیفه:** یک تحلیل اولیه هوشمند از طرح ($what) بر اساس $description ارائه دهید.
    **خروجی:** فقط و فقط کد HTML خالص با CSS داخلی (inline-style). از هیچ Markdownی استفاده نکن. از رنگ‌های جذاب، ایموجی‌ها و انیمیشن ساده برای نمودار استفاده کن. تمام بخش‌ها کامل باشند.

    **اطلاعات ورودی:**
    * نیاز: $what
    * موقعیت: $whichPlace
    * محل: $province / $city
    * توضیحات: $description
    * (اگر نام برندی مثل 'پلاستیران' بود، از دانش عمومی خود برای تحلیل آن در بخش نقاط قوت استفاده کن.)

    **ساختار HTML دقیق داشبورد (این قالب جذاب را پر کن):**

    <div style='font-family: Vazirmatn, sans-serif; border: 1px solid #e0e0e0; border-radius: 12px; background: #fdfdfd; padding: 30px; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);'>

        <h3 style='color: #1a237e; border-bottom: 3px solid #3f51b5; padding-bottom: 12px; text-align: center; font-size: 20px;'>
            📊 تحلیل اولیه هوشمند سپینود (براساس داده‌های واقعی و تجارب پیشین) برای جناب/سرکار {$lastName}
        </h3>
        <p style='font-size: 15px; color: #555; text-align: center; margin-bottom: 25px;'>ارزیابی طرح «{$what}» شما:</p>

        <h4 style='color: #1a237e; margin-top: 20px; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>🎯</span> امتیاز پتانسیل اولیه (P.P.S)</h4>
        <div style='background-color: #e9ecef; border-radius: 10px; padding: 5px; border: 1px solid #ced4da; margin-top: 10px; overflow: hidden;'>
            <style> @keyframes progressBarAnimation { 0% { width: 0%; } 100% { width: [SCORE]%; } } </style>
            <div style='width: [SCORE]%; background: linear-gradient(90deg, #3f51b5, #5c6bc0); color: white; padding: 12px; border-radius: 8px; text-align: center; font-weight: 700; font-size: 18px; animation: progressBarAnimation 1.5s ease-out forwards; white-space: nowrap;'>
                [SCORE] / 100
            </div>
        </div>
        <p style='font-size: 15px; color: #444; margin-top: 12px; background: #f8f9fa; padding: 10px; border-radius: 6px; border-left: 4px solid #3f51b5;'>
            <strong>🔍 توجیه امتیاز:</strong> [توضیح 2-3 جمله‌ای دلیل محاسبه این امتیاز]
        </p>

        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 30px;'>
            
            <div style='background: #e8f5e9; border: 1px solid #c8e6c9; border-left: 5px solid #4caf50; border-radius: 8px; padding: 20px;'>
                <h5 style='color: #2e7d32; font-size: 17px; margin-top: 0; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>💡</span> نقاط قوت کلیدی</h5>
                <ul style='padding-right: 20px; color: #333; line-height: 1.8; list-style: none;'>
                    <li>✅ <strong>[نقطه قوت 1]:</strong> [توضیح]</li>
                    <li>✅ <strong>[نقطه قوت 2]:</strong> [توضیح]</li>
                    <li>✅ <strong>[نقطه قوت 3]:</strong> [توضیح]</li>
                </ul>
            </div>

            <div style='background: #ffebee; border: 1px solid #ffcdd2; border-left: 5px solid #f44336; border-radius: 8px; padding: 20px;'>
                <h5 style='color: #c62828; font-size: 17px; margin-top: 0; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>⚠️</span> چالش‌ها و گلوگاه‌های حساس</h5>
                <ul style='padding-right: 20px; color: #333; line-height: 1.8; list-style: none;'>
                    <li>❌ <strong>[چالش 1]:</strong> [توضیح]</li>
                    <li>❌ <strong>[چالش 2]:</strong> [توضیح]</li>
                    <li>❌ <strong>[چالش 3]:</strong> [توضیح]</li>
                </ul>
            </div>
        </div>

        <h4 style='color: #1a237e; margin-top: 30px; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>🎨</span> ماتریس ارزیابی اولیه (رنگی)</h4>
        <table style='width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
            <thead style='background-color: #e3f2fd; color: #0d47a1;'>
                <tr>
                    <th style='border-bottom: 1px solid #dee2e6; padding: 14px; text-align: right;'>معیار</th>
                    <th style='border-bottom: 1px solid #dee2e6; padding: 14px; text-align: right;'>ارزیابی اولیه</th>
                    <th style='border-bottom: 1px solid #dee2e6; padding: 14px; text-align: right;'>توضیح مختصر</th>
                </tr>
            </thead>
            <tbody>
                <tr style='background-color: #fff;'>
                    <td style='border-bottom: 1px solid #eee; padding: 12px;'>شفافیت ایده</td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px; background-color: [COLOR];'><strong>[بالا/متوسط/پایین]</strong></td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px;'>[تحلیل 1 جمله‌ای]</td>
                </tr>
                <tr style='background-color: #f8f9fa;'>
                    <td style='border-bottom: 1px solid #eee; padding: 12px;'>پتانسیل بازار (تخمینی)</td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px; background-color: [COLOR];'><strong>[بالا/متوسط/پایین]</strong></td>
                    <td style='border-bottom: 1px solid #eee; padding: 12px;'>[تحلیل 1 جمله‌ای]</td>
                </tr>
                <tr style='background-color: #fff;'>
                    <td style='padding: 12px;'>کفایت سرمایه (تخمینی)</td>
                    <td style='padding: 12px; background-color: [COLOR];'><strong>[بالا/متوسط/نامشخص]</strong></td>
                    <td style='padding: 12px;'>[تحلیل 1 جمله‌ای]</td>
                </tr>
            </tbody>
        </table>

        <h4 style='color: #1a237e; margin-top: 30px; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>📝</span> گام بعدی: تکمیل اطلاعات برای «{$what}»</h4>
        <div style='background: #fff9c4; border: 1px solid #fff176; border-left: 5px solid #fdd835; border-radius: 8px; padding: 25px;'>
            <h5 style='margin-top: 0; font-size: 17px; color: #795548; display: flex; align-items: center; gap: 8px;'><span style='font-size: 20px;'>❓</span> سؤال‌های ساده برای تحلیل دقیق‌تر:</h5>
            <p style='color: #333;'>این ارزیابی اولیه بود. برای تهیه یک طرح جامع، لطفاً به این موارد فکر کنید:</p>
            <ul style='padding-right: 20px; color: #333; line-height: 1.8; list-style: none;'>
                <li><span style='color: #795548;'>◉</span> برآورد دقیق سرمایه (ثابت/گردش) چقدر است؟</li>
                <li><span style='color: #795548;'>◉</span> مشخصات فنی کلیدی (ظرفیت/متراژ) چیست؟</li>
                <li><span style='color: #795548;'>◉</span> بازار هدف و رقبای اصلی چه کسانی هستند؟</li>
            </ul>
        </div>

        <div style='background-color: #e3f2fd; color: #1a237e; border: 1px solid #bbdefb; border-radius: 10px; padding: 30px; margin-top: 30px; text-align: center; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1); animation: subtlePulse 6s infinite ease-in-out;'>
            <style> @keyframes subtlePulse { 0% { background-color: #e3f2fd; } 50% { background-color: #e8eaf6; } 100% { background-color: #e3f2fd; } } </style>
            <h4 style='margin-top: 0; font-size: 20px; display: flex; align-items: center; justify-content: center; gap: 10px; color: #0d47a1;'><span style='font-size: 24px;'>🚀</span> توصیه تخصصی سپینود</h4>
            <p style='font-size: 16px; line-height: 1.7; color: #333;'>طرح شما ارزشمند است و نیازمند بررسی دقیق‌تر. تخصص ما در سپینود، ارائه راهکارهای عملی برای <strong>'{$what}'</strong> و تحقق اهداف سرمایه‌گذاری شماست. کارشناسان ما آماده‌اند تا در یک <strong style='color: #0d47a1; font-weight: bold;'>جلسه مشاوره رایگان</strong>، جزئیات بیشتری را با شما در میان بگذارند. به زودی با شماره {$mobile} با شما تماس خواهیم گرفت.</p>
        </div>

    </div>
    ";
    // ================================================================


    // ========== مرحله 5: آماده‌سازی و ارسال درخواست به Hugging Face API ==========
    $data = [
        'inputs' => $prompt, // پرامپت به عنوان ورودی اصلی
        'parameters' => [   // پارامترهای تولید متن
            'temperature' => 0.3, // دما کمتر برای دقت HTML
            'max_new_tokens' => 4096, // حداکثر توکن‌های *جدید* تولیدی
            'return_full_text' => false, // فقط متن تولید شده را برگردان، نه پرامپت ورودی
            'repetition_penalty' => 1.1, // کمی جریمه برای تکرار
        ],
        'options' => [
            'wait_for_model' => true // اگر مدل در حال بارگذاری بود، صبر کن
        ]
    ];
    $jsonData = json-encode($data);

    $ch = curl_init($modelUrl); // از URL مدل Hugging Face استفاده می‌کنیم
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // ========== تغییر هدرها برای Hugging Face ==========
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $hfToken // استفاده از توکن Hugging Face
    ]);
    // =================================================
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // زمان انتظار

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        throw new Exception('خطای cURL: ' . curl_error($ch));
    }
    curl_close($ch);

    // ========== مرحله 6: پردازش پاسخ از Hugging Face ==========
    $responseData = json_decode($response, true);

    // بررسی خطاهای رایج Hugging Face
    if ($httpCode !== 200 || isset($responseData['error'])) {
        $errorMessage = 'خطای ناشناخته از Hugging Face API';
        if (isset($responseData['error'])) {
            $errorMessage = $responseData['error'];
            if(isset($responseData['estimated_time'])) {
                $errorMessage .= ' - مدل در حال بارگذاری است، لطفاً ' . ceil($responseData['estimated_time']) . ' ثانیه دیگر تلاش کنید.';
            }
        } else {
            $errorMessage = "سرور Hugging Face پاسخی با کد $httpCode برگرداند.";
        }
        // خطایابی ساده‌تر برای توکن نامعتبر
        if ($httpCode === 401) {
             $errorMessage = 'توکن Hugging Face نامعتبر است. لطفاً توکن خود را در خط ۱۱ بررسی کنید.';
        }
        throw new Exception('خطا در ارتباط با هوش مصنوعی: ' . $errorMessage);
    }

    // استخراج متن اصلی پاسخ از ساختار JSON Hugging Face
    // مسیر پاسخ معمولا: [0]['generated_text']
    $aiHtmlOutput = $responseData[0]['generated_text'] ?? null;

    if ($aiHtmlOutput === null || empty(trim($aiHtmlOutput))) {
        throw new Exception('پاسخ معتبری از هوش مصنوعی Hugging Face دریافت نشد. Response: ' . $response);
    }

    // ========== مرحله 7: ارسال پاسخ موفقیت‌آمیز به جاوا اسکریپت ==========
    echo json_encode(['analysis_text' => $aiHtmlOutput]);

} catch (Exception $e) {
    // ارسال هرگونه خطا به صورت JSON
    error_log("AI Analysis Error (HF): " . $e->getMessage()); // Log خطا
    echo json_encode(['error' => $e->getMessage()]);
}
?>
