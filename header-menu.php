<?php
require_once "db.php";
session_start();
// Dil Ayarı
$lang = $_GET['lang'] ?? 'tr';
$languages = ['tr' => 'Türkçe', 'en' => 'English', 'mk' => 'Македонски'];

// Dil İçerikleri
$texts = [
    'tr' => [
        'page_title' => 'Ana Sayfa',
        'menu' => 'Menü',
        'test_types' => [
            ['name' => 'Gıda Mikrobiyolojik', 'link' => 'food.php'],
            ['name' => 'Gıda Kimyasal', 'link' => 'food-chem.php'],
            ['name' => 'Gıda Enstrumental', 'link' => 'food-enst.php'],
            ['name' => 'Su Mikrobiyolojik', 'link' => 'water.php'],
            ['name' => 'Su Kimyasal', 'link' => 'water-chem.php'],
            ['name' => 'Yüzey Sürüntü(SWAB)', 'link' => 'swab.php'],
            ['name' => 'Ortam Havası', 'link' => 'air.php'],
            ['name' => 'Atık Su', 'link' => 'wastewater.php'],
            ['name' => 'Toprak', 'link' => 'soil.php'],
            ['name' => 'Trichinella', 'link' => 'trichinella.php'],
            ['name' => 'Tavuk Dışkısı', 'link' => 'chicken.php'],
            ['name' => 'Organoleptik', 'link' => 'organoleptik.php'],
            ['name' => 'Diğer', 'link' => 'other.php']
        ],
        'test_numune' => [
            ['name' => 'Numune Alım', 'link' => 'lab-sample-add.php'],
            ['name' => 'Numune Ön Kabul', 'link' => 'sample-accept.php'],
            ['name' => 'Numune Tam Kabul', 'link' => 'sample-full-accept.php'],
            ['name' => 'Analiz Sonuç', 'link' => 'analysis-result.php'],
            ['name' => 'Rapor Onaylama', 'link' => 'report-ok.php'],
            ['name' => 'Fiyat Teklif', 'link' => 'price-quest.php'],
            ['name' => 'Fatura', 'link' => 'bill.php']
            
        ],
        'sample_count' => 'Alınan Numune Sayısı',
        'settings' => 'Ayarlar',
        'logout' => 'Çıkış Yap',
        'select_db' => 'Veritabanı Seç',
        'facility_select' => 'Tesis Seç',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
    'en' => [
        'page_title' => 'Dashboard',
        'menu' => 'Menu',
        'test_types' => [
            ['name' => 'Food Microbiological', 'link' => 'food.php'],
    ['name' => 'Food Chemical', 'link' => 'food-chem.php'],
    ['name' => 'Food Instrumental', 'link' => 'food-enst.php'],
    ['name' => 'Water Microbiological', 'link' => 'water.php'],
    ['name' => 'Water Chemical', 'link' => 'water-chem.php'],
    ['name' => 'Surface Swab (SWAB)', 'link' => 'swab.php'],
    ['name' => 'Ambient Air', 'link' => 'air.php'],
    ['name' => 'Wastewater', 'link' => 'wastewater.php'],
    ['name' => 'Soil', 'link' => 'soil.php'],
    ['name' => 'Trichinella', 'link' => 'trichinella.php'],
    ['name' => 'Chicken Feces', 'link' => 'chicken.php'],
    ['name' => 'Organoleptic', 'link' => 'organoleptik.php'],
    ['name' => 'Other', 'link' => 'other.php']
        ],
        'sample_count' => 'Sample Count',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'select_db' => 'Select Database',
        'facility_select' => 'Select Facility',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
    'mk' => [
        'page_title' => 'Главна страница',
        'menu' => 'Мени',
        'test_types' => [
            ['name' => 'Микробиологија на храна', 'link' => 'food.php'],
    ['name' => 'Хемија на храна', 'link' => 'food-chem.php'],
    ['name' => 'Инструментална анализа на храна', 'link' => 'food-enst.php'],
    ['name' => 'Микробиологија на вода', 'link' => 'water.php'],
    ['name' => 'Хемија на вода', 'link' => 'water-chem.php'],
    ['name' => 'Површински брис (SWAB)', 'link' => 'swab.php'],
    ['name' => 'Воздух во околина', 'link' => 'air.php'],
    ['name' => 'Отпадна вода', 'link' => 'wastewater.php'],
    ['name' => 'Почва', 'link' => 'soil.php'],
    ['name' => 'Трихинела', 'link' => 'trichinella.php'],
    ['name' => 'Кокошки измет', 'link' => 'chicken.php'],
    ['name' => 'Органолептичка анализа', 'link' => 'organoleptik.php'],
    ['name' => 'Друго', 'link' => 'other.php']
        ],
        'sample_count' => 'Број на примероци',
        'settings' => 'Подесувања',
        'logout' => 'Одјави се',
        'select_db' => 'Избери база',
        'facility_select' => 'Избери објект',
        'footer' => '2025 &copy; Labx by Vektraweb.',
    ],
];

$current_texts = $texts[$lang];

// Oturum kontrolü
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
$userId = $_SESSION["user_id"];
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = :id");
$stmt->execute(["id" => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Admin kontrolü
$isAdmin = ($userId == 1);

// Veritabanı Seçimi (Yıl bazlı filtreleme)
$databases = ['2023', '2024', '2025'];
$selectedDb = $_GET['db'] ?? '2025';

// Kullanıcının tesislerini al
if ($isAdmin) {
    $stmt = $pdo->prepare("SELECT id, name FROM facilities");
} else {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name 
        FROM facilities f
        INNER JOIN user_facilities uf ON uf.facility_id = f.id
        WHERE uf.user_id = :user_id
    ");
    $stmt->execute(["user_id" => $userId]);
}
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Seçilen tesisin ID'sini al
$selectedFacility = $_GET['facility_id'] ?? null;

// Hangi dil sütunu kullanılacak
$sampleColumn = "`numune-" . $lang . "`";
$locationColumn = "`yer-" . $lang . "`";

// Seçilen tesisin verilerini listele (Yıl bazlı filtreleme)
if ($selectedFacility) {
    $stmt = $pdo->prepare("
        SELECT lr.id, lr.code, lr.test_name, lr.report_path, lr.uploaded_at, $sampleColumn AS sample_name, $locationColumn AS location_name
        FROM lab_results lr
        WHERE lr.facility_id = :facility_id 
        AND YEAR(lr.uploaded_at) = :selected_year
        AND lr.test_name = 'Su'
    ");
    $stmt->execute([
        "facility_id" => $selectedFacility,
        "selected_year" => $selectedDb
    ]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $results = [];
}

?>