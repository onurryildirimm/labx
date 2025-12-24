<aside id="aside" class="ui-aside">
    <ul class="nav">
        <br>
         <?php foreach ($current_texts['home'] as $home): ?>
        <li>
           
                <a href="<?= $home['link'] ?>?lang=<?= htmlspecialchars($lang) ?>&db=<?= htmlspecialchars($selectedDb) ?>"
                    <i class="fa fa-home"></i> <?= htmlspecialchars($home['name']) ?>
                </a>
            </li>
            <?php endforeach; ?>
            
            
            
        <li class="nav-head"><hr>
            <h5-menu class="nav-title"><?= $current_texts['menu'] ?></h5-menu><hr>
        </li>
        <?php foreach ($current_texts['test_types'] as $test): ?>
            <li>
                <a href="<?= $test['link'] ?>?lang=<?= htmlspecialchars($lang) ?>&db=<?= htmlspecialchars($selectedDb) ?>">
                    <i class="fa fa-flask"></i> <?= htmlspecialchars($test['name']) ?>
                </a>
            </li>
            
            
        <?php endforeach; ?>
        
        <li class="nav-head"><hr>
            <h5-menu class="nav-title"><?= $current_texts['menu_operation'] ?></h5-menu><hr>
        </li>
        <?php foreach ($current_texts['test_numune'] as $numune): ?>
            <li>
                <a href="<?= $numune['link'] ?>?lang=<?= htmlspecialchars($lang) ?>&db=<?= htmlspecialchars($selectedDb) ?>">
                    <i class="fa fa-flask"></i> <?= htmlspecialchars($numune['name']) ?>
                </a>
            </li>
            
            
        <?php endforeach; ?>
        <?php if ($_SESSION["facility_id"] == 0): ?>
        <li class="nav-head"><hr>
            <h5-menu class="nav-title"><?= $current_texts['menu-admin'] ?></h5-menu><hr>
        </li>
        
    <?php foreach ($current_texts['admin'] as $admin): ?>
        <li>
            <a href="<?= $admin['link'] ?>?lang=<?= htmlspecialchars($lang) ?>&db=<?= htmlspecialchars($selectedDb) ?>">
                <i class="fa fa-shield"></i> <?= htmlspecialchars($admin['name']) ?>
            </a>
        </li>
    <?php endforeach; ?>
<?php endif; ?>
    </ul>
</aside>