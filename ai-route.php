<?php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = trim($data['message'] ?? '');

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Nasıl bir rota aradığını benimle paylaşabilirsin.']);
    exit;
}

// Tüm rotaları kategoriyle birlikte çek
$stmt = $pdo->query("
    SELECT product.id, product.title, product.price, product.duration, product.visa_status, category.name AS category_name
    FROM product
    LEFT JOIN category ON product.category_id = category.id
");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$productListText = "";
foreach ($products as $p) {
    $productListText .= "- ID: {$p['id']} | Başlık: {$p['title']} | Kategori: {$p['category_name']} | Fiyat: €{$p['price']} | Süre: {$p['duration']} Gün | Vize: {$p['visa_status']}\n";
}

$prompt = "Sen Notti Blu butik seyahat markasının zeki ve zarif seyahat danışmanısın.

GÖREVİN:
1. Kullanıcının talebine en uygun 2 veya 3 farklı rotayı aşağıdaki katalogdan seçip listele.
2. Birebir eşleşen rota yoksa konsept olarak en yakın alternatifleri öner.
3. Kesinlikle slogan, motto veya boş laf etme.
4. Her önerilen rotayı şu formatta alt alta listele:
• **Rota Adı** (Süre, Vize Durumu, Fiyat): 1 cümlelik kısa neden önerildiği.
[Rotayı İncele](products.php?id=ID)

KATALOGDAKİ ROTALAR:
{$productListText}

KULLANICI TALEBİ: {$userMessage}";

$apiKey = "AQ.Ab8RN6Ix8RKh15m-AWS08zvUvnYVw0SIdWct5rryeyUSd7kTvw";
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.6-flash:generateContent?key=" . $apiKey;

$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.5,
        "maxOutputTokens" => 4000
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['reply' => 'Bağlantı hatası: ' . $curlError]);
    exit;
}

$resData = json_decode($response, true);

if (isset($resData['error'])) {
    echo json_encode(['reply' => 'API Hatası: ' . ($resData['error']['message'] ?? 'Bilinmeyen hata')]);
    exit;
}

$reply = $resData['candidates'][0]['content']['parts'][0]['text'] ?? 'Aradığın kriterde rota bulunamadı, farklı tercihlerle arama yapabilirsin.';

// Kalın yazıları (Markdown **text**) HTML <strong> yap
$reply = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $reply);

// Markdown linklerini [Yazı](link) şık buton tarzı HTML linke çevir
$reply = preg_replace(
    '/\[(.*?)\]\((.*?)\)/', 
    '<a href="$2" target="_blank" style="display:inline-block; margin-top:4px; margin-bottom:10px; background:#203A63; color:#FFFFFF; padding:5px 12px; border-radius:6px; font-size:12px; text-decoration:none; font-weight:600;">$1 &rarr;</a>', 
    $reply
);

// Satır başlarını düzgün boşluklu HTML yap
$reply = nl2br(trim($reply));

echo json_encode(['reply' => $reply]);