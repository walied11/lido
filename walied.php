<?php
ob_start();

// ==================================================
// تعريف التوكن والمعرفات الأساسية
// ==================================================
$token = "8011095393:AAH6jn8m6_szuf5aZDGrQnWo4elOmDrh0Fs";
define("API_KEY", $token);
$admin = "7662734265";
$domin = $_SERVER['HTTP_HOST'];

// ==================================================
// تعريف الدوال الأساسية
// ==================================================
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}

function callAPI($action, $channel_id, $user_id = null, $number = 1) {
    $api_url = 'https://dev-lido11.pantheonsite.io/wp-admin/walied/walied.php';
    $data = ['action' => $action, 'channel_id' => $channel_id];
    if ($action === 'check' && $user_id !== null) $data['user_id'] = $user_id;
    if ($action === 'link') $data['number'] = $number;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    if (curl_error($ch)) return ['error' => curl_error($ch)];
    curl_close($ch);
    return json_decode($response, true);
}

function send_message($message, $from_id, $tk) {
    $url = "https://api.telegram.org/bot" . $tk . "/sendMessage?chat_id=" . $from_id;
    $url .= "&text=" . urlencode($message);
    $url .= "&parse_mode=markdown";
    file_get_contents($url);
}

// ==================================================
// تحميل البيانات من الملفات
// ==================================================
$bot = file_exists('bot.json') ? json_decode(file_get_contents('bot.json'), true) : [];
$abdo = file_exists('abdo.json') ? json_decode(file_get_contents('abdo.json'), true) : [];
$eshterak = json_decode(file_get_contents("eshterak.json"), true);

// ==================================================
// حفظ البيانات في الملفات
// ==================================================
function saveData() {
    global $abdo, $bot, $eshterak;
    file_put_contents('abdo.json', json_encode($abdo));
    file_put_contents('bot.json', json_encode($bot));
    file_put_contents('eshterak.json', json_encode($eshterak));
}

// ==================================================
// تهيئة الإعدادات الافتراضية
// ==================================================
if (!isset($bot['tak'])) $bot['tak'] = "on";
if (!isset($bot['tawgeh'])) $bot['tawgeh'] = "on";
if (!isset($bot['bott'])) $bot['bott'] = "on";
if (!isset($bot['premium'])) $bot['premium'] = "off";
if (!isset($bot['VIP_button'])) $bot['VIP_button'] = "on";
if (!isset($bot['check'])) $bot['check'] = "on";
if (!isset($bot['upload'])) $bot['upload'] = "on";
if (!isset($bot['folder'])) $bot['folder'] = "on";
if (!isset($bot['from_folder'])) {
    mkdir("all");
    mkdir("all/$chat_id/{$bot['from_folder']}");
    $bot['from_folder'] = "bots";
}
saveData();

// ==================================================
// التعامل مع التحديثات
// ==================================================
date_default_timezone_set('Africa/Cairo');
$update = json_decode(file_get_contents('php://input'));

$bot_id = bot("getme")->result->id;
$bot_user = bot("getme")->result->username;
$bot_name = bot("getme")->result->first_name;

$message = $update->message ?? null;
$callback_query = $update->callback_query ?? null;

$message_id = $message->message_id ?? $callback_query->message->message_id ?? null;
$username = $message->from->username ?? $callback_query->from->username ?? null;
$chat_id = $message->chat->id ?? $callback_query->message->chat->id ?? null;
$title = $message->chat->title ?? $callback_query->message->chat->title ?? null;
$text = $message->text ?? $callback_query->message->text ?? null;
$photo = $message->photo ?? null;
$voice = $message->voice ?? null;
$audio = $message->audio ?? null;
$video = $message->video ?? null;
$document = $message->document ?? null;
$sticker = $message->sticker ?? null;
$caption = $message->caption ?? null;
$name = $message->from->first_name ?? $callback_query->from->first_name ?? null;
$from_id = $message->from->id ?? $callback_query->from->id ?? null;
$type = $message->chat->type ?? null;

$reply = $message->reply_to_message ?? null;
$reply_message_id = $reply->message_id ?? null;
$rep_for = $reply->forward_from->id ?? null;

$document_file_id = $document->file_id ?? null;
$document_file_name = $document->file_name ?? null;

$data = $callback_query->data ?? null;

// ==================================================
// التعامل مع الأوامر والوظائف
// ==================================================
if ($text == '/start' || $data == 'bot') {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "مرحبًا بك في البوت!",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "الاعدادات", 'callback_data' => "settings"]],
                [['text' => "المساعدة", 'callback_data' => "help"]]
            ]
        ])
    ]);
}

// ==================================================
// التعامل مع رفع الملفات
// ==================================================
if ($message && $message->document) {
    if ($bot['upload'] == "off") {
        bot('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "الرفع معطل حاليًا.",
            'parse_mode' => "markdown"
        ]);
        exit;
    }

    $file_id = $message->document->file_id;
    $file_name = $message->document->file_name;
    $file_path = "all/$chat_id/$file_name";
    file_put_contents($file_path, file_get_contents("https://api.telegram.org/file/bot" . API_KEY . "/" . bot("getfile", ["file_id" => $file_id])->result->file_path));

    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "تم رفع الملف بنجاح: $file_name",
        'parse_mode' => "markdown"
    ]);
}

// ==================================================
// التعامل مع الأوامر الأخرى
// ==================================================
if ($data == "settings") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "إعدادات البوت:",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "تفعيل/تعطيل الرفع", 'callback_data' => "toggle_upload"]],
                [['text' => "رجوع", 'callback_data' => "back"]]
            ]
        ])
    ]);
}

if ($data == "toggle_upload") {
    $bot['upload'] = $bot['upload'] === "on" ? "off" : "on";
    saveData();
    bot('answerCallbackQuery', [
        'callback_query_id' => $update->callback_query->id,
        'text' => "تم " . ($bot['upload'] === "on" ? "تفعيل" : "تعطيل") . " الرفع."
    ]);
}

if ($data == "help") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "مساعدة: يمكنك استخدام الأوامر التالية:\n/start - بدء البوت\n/settings - الإعدادات",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "رجوع", 'callback_data' => "back"]]
            ]
        ])
    ]);
}

if ($data == "back") {
    bot('editMessageText', [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => "مرحبًا بك في البوت!",
        'parse_mode' => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "الاعدادات", 'callback_data' => "settings"]],
                [['text' => "المساعدة", 'callback_data' => "help"]]
            ]
        ])
    ]);
}