<?php

$stu_id       = '';                      
$password     = '';                      
$tg_bot_token = '';        
$tg_chat_id   = '';            

$baseUrl      = 'https://aca.nuk.edu.tw/Student2/';
$loginPage    = $baseUrl . 'login.asp';
$loginAction  = $baseUrl . 'Menu1.asp';
$ddddocr_url  = 'http://127.0.0.1:8000/ocr';

$cookie_file  = sys_get_temp_dir() . '/nuk_login_' . md5($stu_id) . '.cookie';




//  request 函數
function request($url, $postData = null, $referer = '') {
    global $cookie_file;
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE     => $cookie_file,
        CURLOPT_COOKIEJAR      => $cookie_file,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: zh-TW,zh;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
        ],
        CURLOPT_REFERER        => $referer ?: 'https://www.nuk.edu.tw/',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_ENCODING       => 'gzip',
    ]);
    
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }
    
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// ================= 登入流程（與你原版相同，略作整理） =================
echo "[" . date('Y-m-d H:i:s') . "] 開始執行成績檢查...<br>";

$html_big5 = request($loginPage);
$html = iconv('BIG5', 'UTF-8//IGNORE', $html_big5);

if (strpos($html, 'CSRFToken') === false) {
    die("[" . date('Y-m-d H:i:s') . "] 登入頁面異常，無法找到 CSRFToken<br>");
}

preg_match('/name\s*=\s*["\']CSRFToken["\']\s*[^>]*value\s*=\s*["\']([^"\']+)["\']/i', $html, $m);
$csrfToken = $m[1] ?? '';

preg_match_all('/<input[^>]+type=["\'](text|password)["\'][^>]+name=["\']([0-9A-F-]{32,})["\']/i', $html, $matches, PREG_SET_ORDER);
$accountName = $passwordName = null;
foreach ($matches as $match) {
    if ($match[1] === 'text') $accountName = $match[2];
    if ($match[1] === 'password') $passwordName = $match[2];
}

preg_match('/id=["\']Certify_Image["\'][^>]*src=["\']([^"\']+)["\']/i', $html, $m);
$captchaUrl = $m[1] ?? die("找不到驗證碼圖片<br>");
$captchaUrl = strpos($captchaUrl, 'http') === 0 ? $captchaUrl : $baseUrl . $captchaUrl;

$captchaBinary = request($captchaUrl);
$base64Captcha = base64_encode($captchaBinary);

$data = ['image' => $base64Captcha, 'probability' => false];
$ch = curl_init($ddddocr_url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($data),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
]);
$ocrResponse = curl_exec($ch);
curl_close($ch);

$ocr = json_decode($ocrResponse, true);
$code = trim($ocr['data'] ?? $ocr['result'] ?? '');

if (strlen($code) < 3) {
    die("[" . date('Y-m-d H:i:s') . "] 驗證碼辨識失敗<br>");
}

$postFields = [
    'CSRFToken'   => $csrfToken,
    $accountName  => $stu_id,
    $passwordName => $password,
    'Certify'     => $code,
    'B1'          => '登　　入',
];
$postData = http_build_query($postFields);

$result_big5 = request($loginAction, $postData, $loginPage);
$result = iconv('BIG5', 'UTF-8//IGNORE', $result_big5);

if (stripos($result, '登入失敗') !== false || stripos($result, '驗證碼錯誤') !== false) {
    die("[" . date('Y-m-d H:i:s') . "] 登入失敗<br>");
}

echo "[" . date('Y-m-d H:i:s') . "] 登入成功<br>";

// ================= 直接查詢成績 =================
$scoreUrl = $baseUrl . 'SO/ScoreQuery.asp';
$postParams = [
    'Classno'  => $stu_id, 
    
];
$postData = http_build_query($postParams);

$score_big5 = request($scoreUrl, $postData, $baseUrl . 'SO/SOMenu.asp');
$scoreHtml = iconv('BIG5', 'UTF-8//IGNORE', $score_big5);
// ================= 加強 debug =================
echo "[" . date('Y-m-d H:i:s') . "] 成績頁面取得成功，長度: " . strlen($scoreHtml) . " bytes<br>";

// debug:儲存完整成績頁面原始碼（非常重要！）
//$debug_file = 'debug_score_' . date('Ymd_His') . '.html';
//file_put_contents($debug_file, $scoreHtml);
//echo "[" . date('Y-m-d H:i:s') . "] 完整成績頁面已存為: $debug_file<br>";

// 搜尋關鍵字出現次數（幫助判斷是否有成績表格）
//$keywords = ['成績', '學期成績', '科目', '未送', 'border="1"', 'cellpadding="0"', 'cellspacing="0"', 'width="100%"', '新細明體'];
//foreach ($keywords as $kw) {
//    $count = substr_count($scoreHtml, $kw);
//    echo "關鍵字 '$kw' 出現次數: $count<br>";
//}

// 如果有 border="1"，只列出最後一個出現位置（幫助定位）
if (preg_match_all('/<table[^>]*border\s*=\s*["\']?1["\']?/i', $scoreHtml, $m, PREG_OFFSET_CAPTURE)) {

    $count = count($m[0]);
    echo "找到 {$count} 個 border 相關的 table ({$count}個學期成績)<br>";

    // 取最後一個
    $lastMatch = $m[0][$count - 1];
    $pos = $lastMatch[1];

    $snippet = substr($scoreHtml, max(0, $pos - 80), 200);
    $snippet = str_replace(["\r", "<br>"], ' ', $snippet);

    echo "最後一個 table 位置 {$pos} 附近片段: ...{$snippet}...<br>";

} else {
    echo "完全沒有找到任何 border 相關的 table 標籤<br>";
}

// ================= 提取最後一學期成績表格 =================

// ================= 提取最後一學期成績表格 =================
$pattern = '/<table\s+border="1"\s+cellpadding="0"\s+cellspacing="0"\s+width="100%"[^>]*>/i';

preg_match_all($pattern, $scoreHtml, $matches, PREG_OFFSET_CAPTURE);

if (empty($matches[0])) {
    echo "[" . date('Y-m-d H:i:s') . "] 找不到符合條件的成績表格<br>";
    exit;
}

$tableCount = count($matches[0]);
echo "[" . date('Y-m-d H:i:s') . "] 找到 $tableCount 個符合條件的 table<br>";

// 取最後一個 table 的完整 HTML
$lastStartPos = end($matches[0])[1];
$fromThere = substr($scoreHtml, $lastStartPos);
$endPos = strpos($fromThere, '</table>');
$lastTableHtml = substr($fromThere, 0, $endPos !== false ? $endPos + 8 : strlen($fromThere));

// 清理多餘空白，讓比較更準確
$lastTableHtml = preg_replace('/>\s+</', '><', $lastTableHtml); // 移除標籤間多餘空白
$lastTableHtml = preg_replace('/\s+/', ' ', $lastTableHtml);

// 儲存檔案路徑
$html_file = __DIR__ . '/last_semester_table.html';

// 讀取上次儲存的 HTML（如果存在）
// 讀取上次儲存的 HTML（如果存在）
$previous_html = file_exists($html_file) ? file_get_contents($html_file) : '';

// 比對完整 HTML 內容（已清理）

if (trim($lastTableHtml) !== trim($previous_html)) {
    echo "[" . date('Y-m-d H:i:s') . "] 最後一學期成績表格有變動！<br>";
    
    // 更新檔案為最新版本
    file_put_contents($html_file, $lastTableHtml);
    echo "已更新 last_semester_table.html 為最新版本<br>";

    // 解析表格成純文字成績列表（課程名稱 + 學期成績）
       // 解析表格成純文字成績列表（課程名稱 + 學期成績）
        // 解析表格成純文字成績列表（課程名稱 + 學期成績）
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $lastTableHtml);
    $rows = $dom->getElementsByTagName('tr');

    $row_index = 0;
 
 
 $score_list = "";

foreach ($rows as $row) {

    // 如果這一列有 <th>，代表是標題列，直接跳過
    if ($row->getElementsByTagName('th')->length > 0) {
        continue;
    }

    // 取得所有 td（照 DOM 實際順序）
    $tds = $row->getElementsByTagName('td');

    // 成績資料列一定至少有 6 個 td
    if ($tds->length < 6) {
        continue;
    }

    // 依照實際表格結構取值（不要再用動態 array）
    $course = trim($tds->item(1)->textContent);      // 課程名稱
    $final_score = trim($tds->item(5)->textContent); // 學期成績

    // 清理多餘空白
    $course = preg_replace('/\s+/', ' ', $course);
    $final_score = preg_replace('/\s+/', ' ', $final_score);

    // 防呆（真的抓不到才跳）
    if ($course === '' || $final_score === '') {
        continue;
    }

    $score_list .= "{$course}：{$final_score}\n";
}

 
 
 
 
 
 
 
    // 如果 $score_list 還是空，顯示 debug 資訊
    if (empty(trim($score_list))) {
        $score_list = "解析失敗，無法提取成績列表（請檢查表格結構）\n";
    }

   // 準備 Telegram 通知訊息
    if($previous_html==''){
        
    $message = "【國立高雄大學期末成績監控通知系統】\n" .
               date('Y-m-d H:i:s') . "\n" .
               "監控啟用成功！\n\n" .
               "目前完整成績如下：\n" .
               $score_list . "\n" ;
}else{
    $message = "【國立高雄大學期末成績監控通知系統】\n" .
               date('Y-m-d H:i:s') . "\n" .
               "最新成績有變動！\n\n" .
               "目前完整成績如下：\n" .
               $score_list . "\n" ;
}

    // 發送通知
    $tg_url = "https://api.telegram.org/bot{$tg_bot_token}/sendMessage";
    $tg_data = [
        'chat_id'    => $tg_chat_id,
        'text'       => $message,
        'parse_mode' => 'Markdown',
    ];

    $ch = curl_init($tg_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tg_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $tg_result = curl_exec($ch);
    curl_close($ch);

    $tg_response = json_decode($tg_result, true);
    if ($tg_response['ok'] ?? false) {
        echo "[" . date('Y-m-d H:i:s') . "] Telegram 通知發送成功（已包含所有課程的學期成績）<br>";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] Telegram 通知發送失敗: " . ($tg_response['description'] ?? '未知錯誤') . "<br>";
    }
} else {
    echo "[" . date('Y-m-d H:i:s') . "] 最後一學期成績表格無變化<br>";
}

echo "[" . date('Y-m-d H:i:s') . "] 本次執行結束<br>";

