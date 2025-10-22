<?php
// functions.php
// ไฟล์สำหรับรวบรวมฟังก์ชันที่ใช้งานบ่อยทั่วทั้งระบบ

/**
 * ฟังก์ชันสำหรับ Sanitize ข้อมูลจากผู้ใช้เพื่อป้องกัน XSS
 * @param string $data ข้อมูลที่ต้องการทำความสะอาด
 * @return string ข้อมูลที่ผ่านการทำความสะอาดแล้ว
 */
function e($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * ฟังก์ชันสำหรับแปลงข้อความที่มีการขึ้นบรรทัดใหม่ (\n) และ **bold** ให้เป็น HTML
 * @param string $text ข้อความดิบ
 * @return string ข้อความในรูปแบบ HTML
 */
function format_description($text) {
    if (!$text) return '';
    $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', e($text));
    $blocks = preg_split('/(\r\n|\n|\r){2,}/', $html);
    $finalHtml = '';
    foreach ($blocks as $block) {
        $trimmedBlock = trim($block);
        if (empty($trimmedBlock)) continue;
        if (strpos($trimmedBlock, '- ') === 0) {
            $items = preg_split('/(\r\n|\n|\r)/', $trimmedBlock);
            $listItemsHtml = '';
            foreach($items as $item) {
                $trimmedItem = trim($item);
                if (strpos($trimmedItem, '- ') === 0) {
                     $listItemsHtml .= '<li>' . trim(substr($trimmedItem, 2)) . '</li>';
                }
            }
            if ($listItemsHtml) {
                $finalHtml .= '<ul>' . $listItemsHtml . '</ul>';
            }
        } else {
            $finalHtml .= '<p>' . nl2br($trimmedBlock) . '</p>';
        }
    }
    return $finalHtml;
}

/**
 * ฟังก์ชันสำหรับดึงข้อมูลการตั้งค่า Global จากฐานข้อมูล
 * @param mysqli $db Connection object
 * @return array ข้อมูลการตั้งค่า
 */
function get_global_settings($db) {
    $settings = [];
    if ($db) {
        $result = $db->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }
    return $settings;
}

/**
 * ฟังก์ชันสำหรับปกปิดข้อมูลอีเมล
 * @param string $email อีเมลเต็ม
 * @return string อีเมลที่ถูกปกปิดแล้ว
 */
function mask_email($email) {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) return '';
    list($user, $domain) = explode('@', $email);
    return substr($user, 0, 3) . '...@' . $domain;
}

/**
 * ฟังก์ชันสำหรับปกปิดข้อมูลเบอร์โทรศัพท์
 * @param string $phone เบอร์โทรศัพท์เต็ม
 * @return string เบอร์โทรที่ถูกปกปิดแล้ว
 */
function mask_phone($phone) {
    if (empty($phone)) return '';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) >= 10) {
        return substr($phone, 0, 2) . 'xxxx' . substr($phone, -3);
    }
    return 'xxx-xxxx';
}

/**
 * ฟังก์ชันสำหรับตรวจสอบความถูกต้องของหมายเลขบัตรประชาชนไทย
 * @param string $id หมายเลขบัตรประชาชน 13 หลัก
 * @return bool True ถ้าถูกต้อง, False ถ้าไม่ถูกต้อง
 */
function validateThaiID($id) {
    if (!preg_match('/^\d{13}$/', $id)) return false;
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int)$id[$i] * (13 - $i);
    }
    $checkDigit = (11 - ($sum % 11)) % 10;
    return (int)$id[12] === $checkDigit;
}

/**
 * [NEW] ฟังก์ชันสำหรับสร้างและเก็บ CSRF token ใน session
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * [NEW] ฟังก์ชันสำหรับตรวจสอบ CSRF token ที่ส่งมาจากฟอร์ม
 * @return void จะหยุดการทำงาน (die) หาก token ไม่ถูกต้อง
 */
function validate_csrf_token() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        // Token ไม่ตรงกันหรือไม่มีอยู่: อาจเป็นการโจมตี CSRF
        unset($_SESSION['csrf_token']); // ล้าง token ทิ้งเพื่อความปลอดภัย
        die('Invalid CSRF token.');
    }
    // เมื่อตรวจสอบผ่านแล้ว ให้ล้าง token ทิ้ง เพื่อบังคับให้ฟอร์มถัดไปสร้างใหม่
    unset($_SESSION['csrf_token']);
}


/**
 * NEW: ฟังก์ชันสำหรับแปลงวันที่เป็นรูปแบบไทย (วัน เดือน ปี พ.ศ.)
 * @param string $dateStr วันที่ในรูปแบบ YYYY-MM-DD
 * @return string|null วันที่ในรูปแบบไทย หรือ null ถ้าข้อมูลเข้าไม่ถูกต้อง
 */
function formatThaiDate($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00') return null;
    try {
        $date = new DateTime($dateStr);
        $thai_months = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        $day = $date->format('j');
        $month = $thai_months[$date->format('n') - 1];
        $year = (int)$date->format('Y') + 543; // Convert to Buddhist year
        return "$day $month $year";
    } catch (Exception $e) {
        return null;
    }
}
?>

