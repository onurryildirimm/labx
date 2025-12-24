<?php
// Dil İçerikleri
$texts = [
    'tr' => [
        'page_title' => 'Su Testleri',
        'main_menu' => 'Menü',
        'menu' => 'Analizler',
        'menu_operation' => 'Operasyon',
        'menu-admin' => 'Admin Menü',
        'home' => [
            ['name' => 'Ana Sayfa', 'link' => 'index.php'],
            ['name' => 'Kullanıcı Profili', 'link' => 'profile.php']
        ],
        'profile' => 'Kullanıcı Profili',
        'test_types' => [
            ['name' => 'Numune İzlenebilirlik', 'link' => 'sample-watch.php']
            
        ],
        'test_numune' => [
            ['name' => 'Numune Alım', 'link' => 'lab-sample-add.php'],
            ['name' => 'Numune Kabul', 'link' => 'sample-accept.php'],
           
            ['name' => 'Analiz Sonuç', 'link' => 'analysis-result.php'],
            ['name' => 'Rapor Onaylama', 'link' => 'report-ok.php'],
            ['name' => 'Fiyat Teklif', 'link' => 'price-quest.php'],
            ['name' => 'Fatura', 'link' => 'bill.php']
            
        ],
        'admin' => [
            ['name' => 'Laboratuvar Tanımla', 'link' => 'add-analysis-lab.php'],
            ['name' => 'Analiz Türü Tanımla', 'link' => 'add-analysis-type.php'],
            ['name' => 'Alt Analiz Türü Tanımla', 'link' => 'sub-analysis.php'],
            ['name' => 'Analiz Parametre Tanımla', 'link' => 'analysis-parameter-add.php'],
            ['name' => 'Yeni Tesis Kaydı', 'link' => 'facility-add.php'],
            ['name' => 'Tesis Anlaşma Tanımlama', 'link' => 'facility-aggrement.php'],
            
            ['name' => 'Kullanıcı Ekle', 'link' => 'user-add.php']
        ], 
        'sample_count' => 'Alınan Numune Sayısı',
        'code' => 'Kod',
        'location' => 'Yer',
        'file' => 'Dosya',
        'upload_date' => 'Yüklenme Tarihi',
        'show_report' => 'Rapor Görüntüle',
        'settings' => 'Ayarlar',
        'logout' => 'Çıkış Yap',
        'select_db' => 'Veritabanı Seç',
        'facility_select' => 'Tesis Seç',
        'all' => 'Tümü',
        'report_upload_title' => 'Rapor Yükleme',
        'report_upload_success' => 'Rapor başarıyla yüklendi.',
        'report_upload_error' => 'Rapor yüklenirken bir hata oluştu.',
        'report_upload_error_fields' => 'Lütfen tüm alanları doldurun ve bir dosya seçin.',
        'upload_report' => 'Rapor Yükle',
        'analysis_date' => 'Analiz Tarihi',
        'report_file' => 'Rapor Dosyası',
        'cancel' => 'İptal',
        'upload' => 'Yükle',
        'sample_info' => 'Numune Bilgisi',
        'footer' => '2025 &copy; Labx by Vektraweb.',
        'analysis_report_date' => 'Analiz Raporu Tarihi',
        'numune_kodu' => 'Numune Kodu',
        'view_report' => 'Raporu Görüntüle',
        'report' => 'Rapor',
        
        // Numune rapor tablosu için yeni eklenen dil anahtarları
        'no' => 'No',
        'sample_type' => 'Numune Türü',
        'sample_name' => 'Numune Adı',
        'sample_date' => 'Numune Alım/Teslim Tarihi',
        'sample_person' => 'Numune Alan/Teslim Eden',
        'sample_code' => 'Numune Kodu',
        'actions' => 'Güncelle/Silme',
        'confirm_delete' => 'Bu kaydı silmek istediğinize emin misiniz?',
        // Numune ekleme formu için yeni metinler
        'add_sample' => 'Numune Ekle',
        'edit_sample' => 'Numune Düzenle',
        'save' => 'Kaydet',
        'cancel' => 'İptal',
        'select' => 'Seçiniz',
        
        // Numune türleri
        'sample_types' => [
            'Gıda' => 'Gıda',
            'Su' => 'Su',
            'Swab' => 'Swab',
            'Çevresel' => 'Çevresel',
            'Legionella' => 'Legionella',
            'Havuz Suyu' => 'Havuz Suyu',
            'Atık Su' => 'Atık Su'
        ]
    ],
    'en' => [
        'page_title' => 'Water Tests',
        'menu' => 'Analysis',
        'menu-admin' => 'Admin Menu',
        'menu_operation' => 'Operation',
        'home' => [
            ['name' => 'Main Page', 'link' => 'index.php'],
            ['name' => 'User Profile', 'link' => 'profile.php']
        ],
        'profile' => 'User Profile',
        'test_types' => [
            ['name' => 'Sample Traceability', 'link' => 'sample-watch.php']
    
        ],
        'test_numune' => [
    ['name' => 'Sample Collection', 'link' => 'lab-sample-add.php'],
    ['name' => 'Sample Acceptance', 'link' => 'sample-accept.php'],
    ['name' => 'Analysis Result', 'link' => 'analysis-result.php'],
    ['name' => 'Report Approval', 'link' => 'report-ok.php'],
    ['name' => 'Price Quote', 'link' => 'price-quest.php'],
    ['name' => 'Invoice', 'link' => 'bill.php']
],
        'admin' => [
            ['name' => 'Define Laboratory', 'link' => 'add-analysis-lab.php'],
            ['name' => 'Define Analysis Type', 'link' => 'add-analysis-type.php'],
            ['name' => 'Define Sub-Analysis Type', 'link' => 'sub-analysis.php'],
            ['name' => 'Define Analysis Parameter', 'link' => 'analysis-parameter-add.php'],
            ['name' => 'Add New Facility', 'link' => 'facility-add.php'],
            ['name' => 'Define Facility Agreement', 'link' => 'facility-aggrement.php'],
         
            ['name' => 'Add New User', 'link' => 'user-add.php']
        ],
        'sample_count' => 'Sample Count',
        'code' => 'Code',
        'location' => 'Location',
        'file' => 'File',
        'upload_date' => 'Upload Date',
        'show_report' => 'Show Report',
        'settings' => 'Settings',
        'logout' => 'Logout',
        'select_db' => 'Select Database',
        'facility_select' => 'Select Facility',
        'all' => 'All',
        'report_upload_title' => 'Report Upload',
        'report_upload_success' => 'Report uploaded successfully.',
        'report_upload_error' => 'An error occurred while uploading the report.',
        'report_upload_error_fields' => 'Please fill all fields and select a file.',
        'upload_report' => 'Upload Report',
        'analysis_date' => 'Analysis Date',
        'report_file' => 'Report File',
        'cancel' => 'Cancel',
        'upload' => 'Upload',
        'sample_info' => 'Sample Information',
        'footer' => '2025 &copy; Labx by Vektraweb.',
        'analysis_report_date' => 'Analysis Report Date',
        'view_report' => 'View Report',
        'report' => 'Report',
        
        // Numune rapor tablosu için yeni eklenen dil anahtarları
        'no' => 'No',
        'sample_type' => 'Sample Type',
        'sample_name' => 'Sample Name',
        'sample_date' => 'Sample Date',
        'sample_person' => 'Sample Taker',
        'sample_code' => 'Sample Code',
        'actions' => 'Actions',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        // Numune ekleme formu için yeni metinler
        'add_sample' => 'Add Sample',
        'edit_sample' => 'Edit Sample',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'select' => 'Select',
        
        
        // Numune türleri
        'sample_types' => [
            'Gıda' => 'Food',
            'Su' => 'Water',
            'Swab' => 'Swab',
            'Çevresel' => 'Environmental',
            'Legionella' => 'Legionella',
            'Havuz Suyu' => 'Pool Water',
            'Atık Su' => 'Wastewater'
        ]
    ],
    'mk' => [
        'page_title' => 'Тестови за вода',
        'menu' => 'Мени',
        'menu-admin' => 'Администраторско мени',
        'menu_operation' => 'Операција',
        'home' => [
            ['name' => 'Главна страница', 'link' => 'index.php'],
            ['name' => 'Кориснички профил', 'link' => 'profile.php']
        ],
        'profile' => 'Кориснички профил',
        'test_types' => [
            ['name' => 'Следливост на Примерок', 'link' => 'sample-watch.php']
        ],
        'test_numune' => [
    ['name' => 'Земање Примерок', 'link' => 'lab-sample-add.php'],
    ['name' => 'Преден Прием на Примерок', 'link' => 'sample-accept.php'],
    ['name' => 'Резултат од Анализа', 'link' => 'analysis-result.php'],
    ['name' => 'Одобрување Извештај', 'link' => 'report-ok.php'],
    ['name' => 'Ценовна Понуда', 'link' => 'price-quest.php'],
    ['name' => 'Фактура', 'link' => 'bill.php']
],
        'admin' => [
            ['name' => 'Дефинирај Лабораторија', 'link' => 'add-analysis-lab.php'],
            ['name' => 'Дефинирај Тип на Анализа', 'link' => 'add-analysis-type.php'],
            ['name' => 'Дефинирај Подтип на Анализа', 'link' => 'sub-analysis.php'],
            ['name' => 'Дефинирај Параметар на Анализа', 'link' => 'analysis-parameter-add.php'],
           ['name' => 'Додадете нов објект', 'link' => 'facility-add.php'],
           ['name' => 'Дефинирај Договор за Објект', 'link' => 'facility-aggrement.php'],
          
            ['name' => 'Додадете нов корисник', 'link' => 'user-add.php']
        ],
        'sample_count' => 'Број на примероци',
        'location' => 'Локација',
        'file' => 'Датотека',
        'upload_date' => 'Датум на поставување',
        'show_report' => 'Прикажи извештај',
        'settings' => 'Подесувања',
        'logout' => 'Одјави се',
        'select_db' => 'Избери база',
        'facility_select' => 'Избери објект',
        'all' => 'Сите',
        'footer' => '2025 &copy; Labx by Vektraweb.',
        'report_upload_title' => 'Поставете извештај',
        'report_upload_success' => 'Извештајот е успешно поставен.',
        'report_upload_error' => 'Се појави грешка при поставувањето на извештајот.',
        'report_upload_error_fields' => 'Пополнете ги сите полиња и изберете датотека.',
        'upload_report' => 'Поставете извештај',
        'analysis_date' => 'Датум на анализа',
        'report_file' => 'Датотека со извештај',
        'cancel' => 'Откажи',
        'upload' => 'Постави',
        'sample_info' => 'Информации за примерокот',
        'analysis_report_date' => 'Датум на извештај за анализа',
        'view_report' => 'Прикажи извештај',
        'report' => 'Извештај',
        
        // Numune rapor tablosu için yeni eklenen dil anahtarları
        'no' => 'Бр',
        'sample_type' => 'Вид на примерок',
        'sample_name' => 'Име на примерок',
        'sample_date' => 'Датум на примерок',
        'sample_person' => 'Земач на примерок',
        'sample_code' => 'Код на примерок',
        'actions' => 'Акции',
        'confirm_delete' => 'Дали сте сигурни дека сакате да го избришете овој запис?',
        // Numune ekleme formu için yeni metinler
        'add_sample' => 'Додади примерок',
        'edit_sample' => 'Уреди примерок',
        'save' => 'Зачувај',
        'cancel' => 'Откажи',
        'select' => 'Изберете',
        
        // Numune türleri
        'sample_types' => [
            'Gıda' => 'Храна',
            'Su' => 'Вода',
            'Swab' => 'Брис',
            'Çevresel' => 'Животна средина',
            'Legionella' => 'Легионела',
            'Havuz Suyu' => 'Базенска вода',
            'Atık Su' => 'Отпадна вода'
        ]
    ],


];
?>