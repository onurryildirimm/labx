<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/sidebar.php';

/* ==== Yardımcı fonksiyonlar ==== */
function ix_nm(array $r, string $lang): string {
    return $lang==='en' ? ($r['name_en'] ?? $r['name_tr']) : ($lang==='mk' ? ($r['name_mk'] ?? $r['name_tr']) : ($r['name_tr'] ?? ''));
}

/* ==== Kullanıcı ve yetki bilgileri ==== */
$userFacilityId = (int)($user['facility_id'] ?? 0);
$facilityFilter = $isAdmin ? null : $userFacilityId;

/* ==== İstatistikler ==== */
// Toplam numune sayısı
$sqlTotal = "SELECT COUNT(*) FROM samples s" . ($facilityFilter ? " WHERE s.facility_id = :fid" : "");
$stTotal = $pdo->prepare($sqlTotal);
if ($facilityFilter) $stTotal->execute([':fid' => $facilityFilter]); else $stTotal->execute();
$totalSamples = (int)$stTotal->fetchColumn();

// Bugün eklenen numuneler
$sqlToday = "SELECT COUNT(*) FROM samples s WHERE DATE(s.sample_date) = CURDATE()" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
$stToday = $pdo->prepare($sqlToday);
if ($facilityFilter) $stToday->execute([':fid' => $facilityFilter]); else $stToday->execute();
$todaySamples = (int)$stToday->fetchColumn();

// Bu hafta eklenen numuneler
$sqlWeek = "SELECT COUNT(*) FROM samples s WHERE YEARWEEK(s.sample_date, 1) = YEARWEEK(CURDATE(), 1)" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
$stWeek = $pdo->prepare($sqlWeek);
if ($facilityFilter) $stWeek->execute([':fid' => $facilityFilter]); else $stWeek->execute();
$weekSamples = (int)$stWeek->fetchColumn();

// Bu ay eklenen numuneler
$sqlMonth = "SELECT COUNT(*) FROM samples s WHERE YEAR(s.sample_date) = YEAR(CURDATE()) AND MONTH(s.sample_date) = MONTH(CURDATE())" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
$stMonth = $pdo->prepare($sqlMonth);
if ($facilityFilter) $stMonth->execute([':fid' => $facilityFilter]); else $stMonth->execute();
$monthSamples = (int)$stMonth->fetchColumn();

// Kabul bekleyen numuneler
$sqlPending = "SELECT COUNT(*) FROM sample_items si 
    INNER JOIN samples s ON s.id = si.sample_id 
    LEFT JOIN sample_full_accepts sfa ON sfa.sample_item_id = si.id 
    WHERE sfa.id IS NULL" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
$stPending = $pdo->prepare($sqlPending);
if ($facilityFilter) $stPending->execute([':fid' => $facilityFilter]); else $stPending->execute();
$pendingSamples = (int)$stPending->fetchColumn();

// Kabul edilmiş numuneler
$sqlAccepted = "SELECT COUNT(*) FROM sample_items si 
    INNER JOIN samples s ON s.id = si.sample_id 
    INNER JOIN sample_full_accepts sfa ON sfa.sample_item_id = si.id" . ($facilityFilter ? " WHERE s.facility_id = :fid" : "");
$stAccepted = $pdo->prepare($sqlAccepted);
if ($facilityFilter) $stAccepted->execute([':fid' => $facilityFilter]); else $stAccepted->execute();
$acceptedSamples = (int)$stAccepted->fetchColumn();

// Tamamlanan analizler (is_finalized = 1)
$sCols = $pdo->query("SHOW COLUMNS FROM samples")->fetchAll(PDO::FETCH_COLUMN);
$hasFinal = in_array('is_finalized', $sCols);
$completedSamples = 0;
if ($hasFinal) {
    $sqlComplete = "SELECT COUNT(*) FROM samples s WHERE s.is_finalized = 1" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
    $stComplete = $pdo->prepare($sqlComplete);
    if ($facilityFilter) $stComplete->execute([':fid' => $facilityFilter]); else $stComplete->execute();
    $completedSamples = (int)$stComplete->fetchColumn();
}

// Analiz türlerine göre dağılım
$types = $pdo->query("SELECT id, name_tr, name_en, name_mk FROM analysis_types ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$hasTypeCol = in_array('type_id', $sCols) || in_array('analysis_type_id', $sCols);
$typeCol = in_array('type_id', $sCols) ? 'type_id' : (in_array('analysis_type_id', $sCols) ? 'analysis_type_id' : null);

$typeStats = [];
if ($typeCol) {
    foreach ($types as $t) {
        $sqlType = "SELECT COUNT(*) FROM samples s WHERE s.`$typeCol` = :tid" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
        $stType = $pdo->prepare($sqlType);
        $params = [':tid' => $t['id']];
        if ($facilityFilter) $params[':fid'] = $facilityFilter;
        $stType->execute($params);
        $typeStats[] = [
            'id' => $t['id'],
            'name' => ix_nm($t, $lang),
            'count' => (int)$stType->fetchColumn()
        ];
    }
}

// Son 7 gün numune trendi
$trendData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $sqlTrend = "SELECT COUNT(*) FROM samples s WHERE DATE(s.sample_date) = :dt" . ($facilityFilter ? " AND s.facility_id = :fid" : "");
    $stTrend = $pdo->prepare($sqlTrend);
    $params = [':dt' => $date];
    if ($facilityFilter) $params[':fid'] = $facilityFilter;
    $stTrend->execute($params);
    $trendData[] = [
        'date' => date('d.m', strtotime($date)),
        'count' => (int)$stTrend->fetchColumn()
    ];
}

// Son eklenen numuneler (son 10)
$sqlRecent = "SELECT s.id, s.sample_code, s.sample_date, s.address, f.name AS facility_name,
    si.sample_name, lu.full_name AS taker_name,
    sfa.sample_code AS accept_code, sfa.accepted_at
    FROM samples s
    INNER JOIN facilities f ON f.id = s.facility_id
    INNER JOIN sample_items si ON si.sample_id = s.id
    LEFT JOIN lab_users lu ON lu.id = s.taker_id
    LEFT JOIN sample_full_accepts sfa ON sfa.sample_item_id = si.id
    " . ($facilityFilter ? "WHERE s.facility_id = :fid" : "") . "
    ORDER BY s.id DESC, si.id DESC LIMIT 10";
$stRecent = $pdo->prepare($sqlRecent);
if ($facilityFilter) $stRecent->execute([':fid' => $facilityFilter]); else $stRecent->execute();
$recentSamples = $stRecent->fetchAll(PDO::FETCH_ASSOC);

// Admin için: Firma bazlı özet
$facilityStats = [];
if ($isAdmin) {
    $sqlFacility = "SELECT f.id, f.name, COUNT(s.id) as sample_count,
        SUM(CASE WHEN DATE(s.sample_date) = CURDATE() THEN 1 ELSE 0 END) as today_count
        FROM facilities f
        LEFT JOIN samples s ON s.facility_id = f.id
        GROUP BY f.id, f.name
        ORDER BY sample_count DESC
        LIMIT 10";
    $facilityStats = $pdo->query($sqlFacility)->fetchAll(PDO::FETCH_ASSOC);
}

// Laboratuvarlar
$labs = $pdo->query("SELECT id, name FROM laboratories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ==== Dil metinleri ==== */
$T = [
    'tr' => [
        'dashboard' => 'Kontrol Paneli',
        'welcome' => 'Hoş Geldiniz',
        'total_samples' => 'Toplam Numune',
        'today' => 'Bugün',
        'this_week' => 'Bu Hafta',
        'this_month' => 'Bu Ay',
        'pending' => 'Kabul Bekleyen',
        'accepted' => 'Kabul Edilmiş',
        'completed' => 'Tamamlanan Analiz',
        'by_type' => 'Analiz Türüne Göre',
        'trend' => 'Son 7 Gün Trendi',
        'recent' => 'Son Eklenen Numuneler',
        'facility_summary' => 'Firma Özeti',
        'facility' => 'Firma',
        'sample_count' => 'Numune Sayısı',
        'today_count' => 'Bugün',
        'sample_name' => 'Numune Adı',
        'sample_date' => 'Tarih',
        'sample_code' => 'Numune Kodu',
        'status' => 'Durum',
        'taker' => 'Alan Kişi',
        'pending_status' => 'Bekliyor',
        'accepted_status' => 'Kabul Edildi',
        'view_all' => 'Tümünü Gör',
        'labs' => 'Laboratuvarlar',
        'no_data' => 'Veri bulunamadı',
        'quick_actions' => 'Hızlı İşlemler',
        'add_sample' => 'Numune Ekle',
        'accept_sample' => 'Numune Kabul',
        'analysis' => 'Analiz Sonuç',
        'reports' => 'Raporlar',
    ],
    'en' => [
        'dashboard' => 'Dashboard',
        'welcome' => 'Welcome',
        'total_samples' => 'Total Samples',
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'pending' => 'Pending',
        'accepted' => 'Accepted',
        'completed' => 'Completed',
        'by_type' => 'By Analysis Type',
        'trend' => 'Last 7 Days Trend',
        'recent' => 'Recent Samples',
        'facility_summary' => 'Facility Summary',
        'facility' => 'Facility',
        'sample_count' => 'Sample Count',
        'today_count' => 'Today',
        'sample_name' => 'Sample Name',
        'sample_date' => 'Date',
        'sample_code' => 'Sample Code',
        'status' => 'Status',
        'taker' => 'Taken By',
        'pending_status' => 'Pending',
        'accepted_status' => 'Accepted',
        'view_all' => 'View All',
        'labs' => 'Laboratories',
        'no_data' => 'No data found',
        'quick_actions' => 'Quick Actions',
        'add_sample' => 'Add Sample',
        'accept_sample' => 'Accept Sample',
        'analysis' => 'Analysis Result',
        'reports' => 'Reports',
    ],
    'mk' => [
        'dashboard' => 'Контролна табла',
        'welcome' => 'Добредојдовте',
        'total_samples' => 'Вкупно примероци',
        'today' => 'Денес',
        'this_week' => 'Оваа недела',
        'this_month' => 'Овој месец',
        'pending' => 'Чекаат',
        'accepted' => 'Прифатени',
        'completed' => 'Завршени',
        'by_type' => 'По тип на анализа',
        'trend' => 'Тренд за последните 7 дена',
        'recent' => 'Последни примероци',
        'facility_summary' => 'Преглед по објекти',
        'facility' => 'Објект',
        'sample_count' => 'Број на примероци',
        'today_count' => 'Денес',
        'sample_name' => 'Име на примерок',
        'sample_date' => 'Датум',
        'sample_code' => 'Код на примерок',
        'status' => 'Статус',
        'taker' => 'Земено од',
        'pending_status' => 'Чека',
        'accepted_status' => 'Прифатено',
        'view_all' => 'Види ги сите',
        'labs' => 'Лаборатории',
        'no_data' => 'Нема податоци',
        'quick_actions' => 'Брзи акции',
        'add_sample' => 'Додади примерок',
        'accept_sample' => 'Прифати примерок',
        'analysis' => 'Резултат на анализа',
        'reports' => 'Извештаи',
    ]
];
$L = $T[$lang] ?? $T['tr'];

$userName = $user['full_name'] ?? $user['username'] ?? 'Kullanıcı';
$facilityName = '';
if ($facilityFilter) {
    foreach ($facilities as $f) {
        if ((int)$f['id'] === $facilityFilter) { $facilityName = $f['name']; break; }
    }
}
$months = [
    'tr' => ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'],
    'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
    'mk' => ['Јануари','Февруари','Март','Април','Мај','Јуни','Јули','Август','Септември','Октомври','Ноември','Декември']
];
$currentMonth = $months[$lang][date('n') - 1] ?? $months['tr'][date('n') - 1];
$currentYear = date('Y');
?>

<style>
.stat-card {
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s ease;
}
.stat-card:hover { transform: translateY(-5px); }
.stat-card .stat-icon {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 50px;
    opacity: 0.3;
}
.stat-card h2 { font-size: 36px; margin: 0 0 5px 0; font-weight: 700; color:white; }
.stat-card p { margin: 0; font-size: 14px; opacity: 0.9; }

.bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.bg-gradient-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.bg-gradient-danger { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); }
.bg-gradient-dark { background: linear-gradient(135deg, #232526 0%, #414345 100%); }
.bg-gradient-orange { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
.bg-gradient-purple { background: linear-gradient(135deg, #8e2de2 0%, #4a00e0 100%); }

.welcome-box {
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    color: #fff;
    padding: 25px 30px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.welcome-box h3 { margin: 0 0 5px 0; font-size: 24px; color: white; }
.welcome-box p { margin: 0; opacity: 0.8; }
.welcome-box .date-info { text-align: right; }
.welcome-box .date-info .day { font-size: 36px; font-weight: 700; }
.welcome-box .date-info .month { font-size: 14px; opacity: 0.8; }

.card-box {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}
.card-box .card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.card-box .card-header h4 { margin: 0; font-size: 16px; font-weight: 600; }
.card-box .card-body { padding: 20px; }

.type-badge {
    display: inline-block;
    padding: 8px 15px;
    border-radius: 20px;
    margin: 5px;
    font-size: 13px;
    font-weight: 500;
}
.type-badge .count { font-weight: 700; margin-left: 5px; }

.status-pending { background: #fff3cd; color: #856404; }
.status-accepted { background: #d4edda; color: #155724; }

.quick-action-btn {
    display: block;
    padding: 15px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
    color: #333;
    text-decoration: none;
    transition: all 0.3s ease;
}
.quick-action-btn:hover {
    background: #e9ecef;
    color: #333;
    text-decoration: none;
    transform: translateX(5px);
}
.quick-action-btn i { margin-right: 10px; width: 20px; text-align: center; }

.trend-bar {
    display: flex;
    align-items: flex-end;
    height: 100px;
    gap: 8px;
}
.trend-bar .bar {
    flex: 1;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    border-radius: 5px 5px 0 0;
    min-height: 10px;
    position: relative;
}
.trend-bar .bar-label {
    text-align: center;
    font-size: 11px;
    color: #666;
    margin-top: 5px;
}
.trend-bar .bar-count {
    position: absolute;
    top: -20px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 11px;
    font-weight: 600;
    color: #333;
}

.facility-row { padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
.facility-row:last-child { border-bottom: none; }
.facility-row .name { font-weight: 500; }
.facility-row .counts { text-align: right; }
.facility-row .badge { margin-left: 5px; }

.table-recent { margin-bottom: 0; }
.table-recent th { background: #f8f9fa; font-weight: 600; font-size: 13px; }
.table-recent td { font-size: 13px; vertical-align: middle; }

.lab-card {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 10px;
}
.lab-card i { font-size: 24px; color: #667eea; margin-bottom: 10px; }
.lab-card .name { font-weight: 500; font-size: 13px; }
</style>

<div id="content" class="ui-content">
  <div class="ui-content-body">
    <div class="ui-container">
      
      <!-- Hoşgeldin Kutusu -->
      <div class="welcome-box">
        <div>
          <h3><?= $L['welcome'] ?>, <?= htmlspecialchars($userName) ?>!</h3>
          <p><?= $isAdmin ? 'Yönetici Paneli' : htmlspecialchars($facilityName) ?></p>
        </div>
        <div class="date-info">
          <div class="day"><?= date('d') ?></div>
          <div class="month"><?= $currentMonth ?> <?= $currentYear ?></div>
        </div>
      </div>

      <!-- Ana İstatistikler -->
      <div class="row">
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-primary">
            <div class="stat-icon"><i class="fa fa-flask"></i></div>
            <h2><?= number_format($totalSamples) ?></h2>
            <p><?= $L['total_samples'] ?></p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-success">
            <div class="stat-icon"><i class="fa fa-calendar-check-o"></i></div>
            <h2><?= number_format($todaySamples) ?></h2>
            <p><?= $L['today'] ?></p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-warning">
            <div class="stat-icon"><i class="fa fa-clock-o"></i></div>
            <h2><?= number_format($pendingSamples) ?></h2>
            <p><?= $L['pending'] ?></p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-info">
            <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
            <h2><?= number_format($acceptedSamples) ?></h2>
            <p><?= $L['accepted'] ?></p>
          </div>
        </div>
      </div>

      <!-- İkinci Satır İstatistikler -->
      <div class="row">
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-orange">
            <div class="stat-icon"><i class="fa fa-calendar"></i></div>
            <h2><?= number_format($weekSamples) ?></h2>
            <p><?= $L['this_week'] ?></p>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-purple">
            <div class="stat-icon"><i class="fa fa-calendar-o"></i></div>
            <h2><?= number_format($monthSamples) ?></h2>
            <p><?= $L['this_month'] ?></p>
          </div>
        </div>
        <?php if ($hasFinal): ?>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-dark">
            <div class="stat-icon"><i class="fa fa-flag-checkered"></i></div>
            <h2><?= number_format($completedSamples) ?></h2>
            <p><?= $L['completed'] ?></p>
          </div>
        </div>
        <?php endif; ?>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card bg-gradient-danger">
            <div class="stat-icon"><i class="fa fa-building"></i></div>
            <h2><?= count($labs) ?></h2>
            <p><?= $L['labs'] ?></p>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Sol Kolon -->
        <div class="col-md-8">
          
          <!-- Trend Grafiği -->
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-line-chart"></i> <?= $L['trend'] ?></h4>
            </div>
            <div class="card-body">
              <div class="trend-bar">
                <?php 
                $maxCount = max(array_column($trendData, 'count')) ?: 1;
                foreach ($trendData as $td): 
                    $height = ($td['count'] / $maxCount) * 80 + 20;
                ?>
                <div style="flex:1; text-align:center;">
                  <div class="bar" style="height: <?= $height ?>px;">
                    <span class="bar-count"><?= $td['count'] ?></span>
                  </div>
                  <div class="bar-label"><?= $td['date'] ?></div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Son Eklenen Numuneler -->
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-list"></i> <?= $L['recent'] ?></h4>
              <a href="lab-sample-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" class="btn btn-sm btn-primary"><?= $L['view_all'] ?></a>
            </div>
            <div class="card-body" style="padding:0;">
              <div class="table-responsive">
                <table class="table table-recent">
                  <thead>
                    <tr>
                      <th><?= $L['sample_date'] ?></th>
                      <th><?= $L['facility'] ?></th>
                      <th><?= $L['sample_name'] ?></th>
                      <th><?= $L['sample_code'] ?></th>
                      <th><?= $L['status'] ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($recentSamples)): ?>
                      <tr><td colspan="5" class="text-center text-muted"><?= $L['no_data'] ?></td></tr>
                    <?php else: ?>
                      <?php foreach ($recentSamples as $rs): ?>
                      <tr>
                        <td><?= htmlspecialchars($rs['sample_date']) ?></td>
                        <td><?= htmlspecialchars($rs['facility_name']) ?></td>
                        <td><strong><?= htmlspecialchars($rs['sample_name']) ?></strong></td>
                        <td><?= htmlspecialchars($rs['accept_code'] ?: $rs['sample_code'] ?: '-') ?></td>
                        <td>
                          <?php if ($rs['accepted_at']): ?>
                            <span class="label label-success"><?= $L['accepted_status'] ?></span>
                          <?php else: ?>
                            <span class="label label-warning"><?= $L['pending_status'] ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <?php if ($isAdmin && !empty($facilityStats)): ?>
          <!-- Firma Özeti (Sadece Admin) -->
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-building-o"></i> <?= $L['facility_summary'] ?></h4>
            </div>
            <div class="card-body">
              <?php foreach ($facilityStats as $fs): ?>
              <div class="facility-row">
                <div class="row">
                  <div class="col-xs-6 name"><?= htmlspecialchars($fs['name']) ?></div>
                  <div class="col-xs-6 counts">
                    <span class="badge" style="background:#667eea;"><?= (int)$fs['sample_count'] ?> <?= $L['total_samples'] ?></span>
                    <span class="badge" style="background:#38ef7d; color:#155724;"><?= (int)$fs['today_count'] ?> <?= $L['today'] ?></span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

        </div>

        <!-- Sağ Kolon -->
        <div class="col-md-4">
          
          <!-- Hızlı İşlemler -->
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-bolt"></i> <?= $L['quick_actions'] ?></h4>
            </div>
            <div class="card-body">
              <a href="lab-sample-add.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" class="quick-action-btn">
                <i class="fa fa-plus-circle text-success"></i> <?= $L['add_sample'] ?>
              </a>
              <a href="sample-accept-new.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" class="quick-action-btn">
                <i class="fa fa-check-square-o text-primary"></i> <?= $L['accept_sample'] ?>
              </a>
              <a href="analysis-result.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" class="quick-action-btn">
                <i class="fa fa-flask text-warning"></i> <?= $L['analysis'] ?>
              </a>
              <a href="report-ok.php?lang=<?= $lang ?>&db=<?= $selectedDb ?>" class="quick-action-btn">
                <i class="fa fa-file-pdf-o text-danger"></i> <?= $L['reports'] ?>
              </a>
            </div>
          </div>

          <!-- Analiz Türleri -->
          <?php if (!empty($typeStats)): ?>
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-pie-chart"></i> <?= $L['by_type'] ?></h4>
            </div>
            <div class="card-body" style="text-align:center;">
              <?php 
              $colors = ['#667eea','#11998e','#f093fb','#4facfe','#ff416c','#f7971e','#8e2de2'];
              foreach ($typeStats as $i => $ts): 
              ?>
              <span class="type-badge" style="background:<?= $colors[$i % count($colors)] ?>20; color:<?= $colors[$i % count($colors)] ?>;">
                <?= htmlspecialchars($ts['name']) ?>
                <span class="count"><?= $ts['count'] ?></span>
              </span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Laboratuvarlar -->
          <?php if (!empty($labs)): ?>
          <div class="card-box">
            <div class="card-header">
              <h4><i class="fa fa-hospital-o"></i> <?= $L['labs'] ?></h4>
            </div>
            <div class="card-body">
              <div class="row">
                <?php foreach ($labs as $lab): ?>
                <div class="col-xs-6">
                  <div class="lab-card">
                    <i class="fa fa-flask"></i>
                    <div class="name"><?= htmlspecialchars($lab['name']) ?></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</div>

<footer id="footer" class="ui-footer">
  <?= $current_texts['footer'] ?? '2025 &copy; Labx by Vektraweb.' ?>
</footer>
</div>

<script src="bower_components/jquery/dist/jquery.min.js"></script>
<script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="bower_components/jquery.nicescroll/dist/jquery.nicescroll.min.js"></script>
<script src="bower_components/autosize/dist/autosize.min.js"></script>
<script src="dist/js/main.js"></script>
</body>
</html>