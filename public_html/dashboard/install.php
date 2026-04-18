<?php
/**
 * GDPS Dashboard - Installer / Database Migrator
 * Lokasi: /public_html/dashboard/install.php
 * ✅ Compatible dengan shared hosting (disable_functions, open_basedir)
 */

// ✅ Definisi path yang konsisten
define('DASHBOARD_ROOT', __DIR__ . '/');
define('CONFIG_FILE', DASHBOARD_ROOT . '../config/dashboard.php');

// ✅ Load config dulu untuk cek $installed dan $dbPath
if (!file_exists(CONFIG_FILE)) {
    die("❌ Error: File config/dashboard.php tidak ditemukan. Silakan upload file config terlebih dahulu.");
}

// Baca config secara manual untuk ambil $installed dan $dbPath
$configContent = file_get_contents(CONFIG_FILE);
preg_match('/\$installed\s*=\s*(true|false)/', $configContent, $installedMatch);
preg_match('/\$dbPath\s*=\s*[\'"]([^\'"]+)[\'"]/', $configContent, $dbPathMatch);

$installed = isset($installedMatch[1]) && $installedMatch[1] === 'true';
$dbPath = isset($dbPathMatch[1]) ? $dbPathMatch[1] : DASHBOARD_ROOT;

// Jika sudah terinstall, redirect ke dashboard
if ($installed) {
    header('Location: ./');
    exit();
}

// ✅ Load libraries dengan path absolut
$libs = [
    DASHBOARD_ROOT . 'incl/dashboardLib.php',
$dbPath . 'incl/lib/connection.php',
$dbPath . 'incl/lib/mainLib.php',
];

foreach ($libs as $lib) {
    if (!file_exists($lib)) {
        die("❌ Error: File library tidak ditemukan: " . htmlspecialchars(basename($lib)));
    }
    require $lib;
}

// ✅ Inisialisasi classes
if (!class_exists('dashboardLib') || !class_exists('mainLib')) {
    die("❌ Error: Gagal memuat class library.");
}
$dl = new dashboardLib();
$gs = new mainLib();

// ✅ Cek koneksi database
if (!isset($db) || !$db) {
    die("❌ Error: Koneksi database gagal. Cek config/dashboard.php");
}

// ✅ Helper function untuk execute query dengan error handling
function safeQuery($db, $sql, $desc) {
    try {
        return $db->query($sql);
    } catch (Exception $e) {
        error_log("Install Error [$desc]: " . $e->getMessage());
        return false;
    }
}

// ✅ Mulai proses instalasi
$migrations = [
    // === CREATE TABLES ===
    ['type' => 'table', 'name' => 'replies', 'sql' => "CREATE TABLE `replies` (
        `replyID` int(11) NOT NULL AUTO_INCREMENT,
        `commentID` int(11) NOT NULL,
        `accountID` int(11) NOT NULL,
        `body` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
        `timestamp` int(11) NOT NULL,
        PRIMARY KEY (`replyID`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'demonlist', 'sql' => "CREATE TABLE `demonlist` (
    `levelID` int(11) NOT NULL,
    `authorID` int(11) NOT NULL,
    `pseudoPoints` int(11) NOT NULL,
    `giveablePoints` int(11) NOT NULL,
    `youtube` varchar(255) CHARACTER SET utf8mb4 NOT NULL,
    PRIMARY KEY (`levelID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'clans', 'sql' => "CREATE TABLE `clans` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `clan` varchar(255) NOT NULL DEFAULT '',
    `tag` varchar(15) NOT NULL DEFAULT '',
    `desc` varchar(2048) NOT NULL DEFAULT '',
    `clanOwner` int(11) NOT NULL DEFAULT '0',
    `color` varchar(6) NOT NULL DEFAULT 'FFFFFF',
    `isClosed` int(11) NOT NULL DEFAULT '0',
    `creationDate` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'clanrequests', 'sql' => "CREATE TABLE `clanrequests` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `accountID` int(11) NOT NULL DEFAULT '0',
    `clanID` int(11) NOT NULL DEFAULT '0',
    `timestamp` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'events', 'sql' => "CREATE TABLE `events` (
    `feaID` int(11) NOT NULL AUTO_INCREMENT,
    `levelID` int(11) NOT NULL,
    `timestamp` int(11) NOT NULL,
    `duration` int(11) NOT NULL,
    `rewards` varchar(2048) NOT NULL DEFAULT '',
    `webhookSent` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`feaID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'vaultcodes', 'sql' => "CREATE TABLE `vaultcodes` (
    `rewardID` int(11) NOT NULL AUTO_INCREMENT,
    `code` varchar(255) NOT NULL DEFAULT '',
    `rewards` varchar(2048) NOT NULL DEFAULT '',
    `duration` int(11) NOT NULL DEFAULT 0,
    `uses` int(11) NOT NULL DEFAULT -1,
    `timestamp` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`rewardID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"],

['type' => 'table', 'name' => 'bans', 'sql' => "CREATE TABLE `bans` (
    `banID` int(11) NOT NULL AUTO_INCREMENT,
    `modID` varchar(255) NOT NULL DEFAULT '',
    `person` varchar(50) NOT NULL DEFAULT '',
    `reason` varchar(2048) NOT NULL DEFAULT '',
    `banType` int(11) NOT NULL DEFAULT 0,
    `personType` int(11) NOT NULL DEFAULT 0,
    `expires` int(11) NOT NULL DEFAULT 0,
    `isActive` int(11) NOT NULL DEFAULT 1,
    `timestamp` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`banID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"],

['type' => 'table', 'name' => 'automod', 'sql' => "CREATE TABLE `automod` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `type` int(11) NOT NULL DEFAULT 0,
    `value1` varchar(255) NOT NULL DEFAULT '',
    `value2` varchar(255) NOT NULL DEFAULT '',
    `value3` varchar(255) NOT NULL DEFAULT '',
    `value4` varchar(255) NOT NULL DEFAULT '',
    `value5` varchar(255) NOT NULL DEFAULT '',
    `value6` varchar(255) NOT NULL DEFAULT '',
    `timestamp` int(11) NOT NULL DEFAULT 0,
    `resolved` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"],

['type' => 'table', 'name' => 'sfxs', 'sql' => "CREATE TABLE `sfxs` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `authorName` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `download` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
    `milliseconds` int(11) NOT NULL DEFAULT '0',
    `size` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
    `isDisabled` int(11) NOT NULL DEFAULT '0',
    `levelsCount` int(11) NOT NULL DEFAULT '0',
    `reuploadID` int(11) NOT NULL DEFAULT '0',
    `reuploadTime` int(11) NOT NULL DEFAULT '0',
    `token` varchar(255) NOT NULL DEFAULT '',
    PRIMARY KEY (`ID`),
    KEY `name` (`name`),
    KEY `authorName` (`authorName`)
    ) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"],

['type' => 'table', 'name' => 'dlsubmits', 'sql' => "CREATE TABLE `dlsubmits` (
    `ID` int(11) NOT NULL AUTO_INCREMENT,
    `accountID` int(11) NOT NULL,
    `levelID` int(11) NOT NULL,
    `atts` int(255) NOT NULL,
    `ytlink` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
    `auth` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
    `approve` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

['type' => 'table', 'name' => 'favsongs', 'sql' => "CREATE TABLE `favsongs` (
    `ID` int(20) NOT NULL AUTO_INCREMENT,
    `songID` int(20) NOT NULL DEFAULT '0',
    `accountID` int(20) NOT NULL DEFAULT '0',
    `timestamp` int(20) NOT NULL DEFAULT '0',
    PRIMARY KEY (`ID`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"],

// === ALTER TABLES - Roles ===
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD dashboardLevelPackCreate INT NOT NULL DEFAULT '0' AFTER dashboardModTools"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD dashboardAddMod INT NOT NULL DEFAULT '0' AFTER dashboardLevelPackCreate"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD dashboardManageSongs INT NOT NULL DEFAULT '0' AFTER dashboardAddMod"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD dashboardForceChangePassNick INT NOT NULL DEFAULT '0' AFTER dashboardManageSongs"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD demonlistAdd INT NOT NULL DEFAULT '0' AFTER dashboardForceChangePassNick"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE roles ADD demonlistApprove INT NOT NULL DEFAULT '0' AFTER demonlistAdd"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` CHANGE `toolPackcreate` `dashboardGauntletCreate` INT(11) NOT NULL DEFAULT '0'"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `commandLockCommentsOwn` INT NOT NULL DEFAULT '1' AFTER `commandSongAll`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `commandLockCommentsAll` INT NOT NULL DEFAULT '0' AFTER `commandLockCommentsOwn`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `commandLockUpdating` INT NOT NULL DEFAULT '0' AFTER `commandLockCommentsAll`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `dashboardDeleteLeaderboards` INT NOT NULL DEFAULT '0' AFTER `dashboardForceChangePassNick`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `dashboardManageLevels` INT NOT NULL DEFAULT '0' AFTER `dashboardDeleteLeaderboards`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `dashboardManageAutomod` INT NOT NULL DEFAULT '0' AFTER `dashboardManageLevels`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `dashboardVaultCodesManage` INT NOT NULL DEFAULT '0' AFTER `dashboardManageAutomod`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` ADD `commandEvent` INT NOT NULL DEFAULT '0' AFTER `commandWeekly`"],
['type' => 'alter', 'table' => 'roles', 'sql' => "ALTER TABLE `roles` DROP `profilecommandDiscord`"],

// === ALTER TABLES - Accounts/Users ===
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD auth varchar(16) NOT NULL DEFAULT 'none' AFTER isActive"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD passCode varchar(255) NOT NULL DEFAULT '' AFTER auth"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD timezone varchar(255) NOT NULL DEFAULT '' AFTER passCode"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD mail varchar(255) NOT NULL DEFAULT '' AFTER auth"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD instagram varchar(255) NOT NULL DEFAULT '' AFTER twitch"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD tiktok varchar(255) NOT NULL DEFAULT '' AFTER instagram"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD discord varchar(255) NOT NULL DEFAULT '' AFTER tiktok"],
['type' => 'alter', 'table' => 'accounts', 'sql' => "ALTER TABLE accounts ADD custom varchar(255) NOT NULL DEFAULT '' AFTER discord"],

['type' => 'alter', 'table' => 'users', 'sql' => "ALTER TABLE users ADD clan INT NOT NULL DEFAULT '0' AFTER userName"],
['type' => 'alter', 'table' => 'users', 'sql' => "ALTER TABLE users ADD joinedAt INT NOT NULL DEFAULT '0' AFTER clan"],
['type' => 'alter', 'table' => 'users', 'sql' => "ALTER TABLE users ADD dlPoints INT NOT NULL DEFAULT '0' AFTER joinedAt"],

// === ALTER TABLES - Levels/Lists/Songs ===
['type' => 'alter', 'table' => 'levels', 'sql' => "ALTER TABLE `levels` ADD `originalServer` VARCHAR(255) NOT NULL DEFAULT '' AFTER `originalReup`"],
['type' => 'alter', 'table' => 'levels', 'sql' => "ALTER TABLE `levels` ADD `updateLocked` INT NOT NULL DEFAULT '0' AFTER `settingsString`"],
['type' => 'alter', 'table' => 'levels', 'sql' => "ALTER TABLE `levels` ADD `commentLocked` INT NOT NULL DEFAULT '0' AFTER `updateLocked`"],

['type' => 'alter', 'table' => 'lists', 'sql' => "ALTER TABLE `lists` ADD `commentLocked` INT NOT NULL DEFAULT '0' AFTER `unlisted`"],

['type' => 'alter', 'table' => 'songs', 'sql' => "ALTER TABLE songs ADD reuploadID INT NOT NULL DEFAULT '0' AFTER reuploadTime"],
['type' => 'alter', 'table' => 'songs', 'sql' => "ALTER TABLE songs ADD duration INT NOT NULL DEFAULT '0' AFTER size"],

['type' => 'alter', 'table' => 'gauntlets', 'sql' => "ALTER TABLE gauntlets ADD timestamp INT NOT NULL DEFAULT '0' AFTER level5"],
['type' => 'alter', 'table' => 'mappacks', 'sql' => "ALTER TABLE mappacks ADD timestamp INT NOT NULL DEFAULT '0' AFTER colors2"],

// === ALTER TABLES - Actions ===
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` CHANGE `account` `account` VARCHAR(255) NOT NULL DEFAULT ''"],
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` ADD `IP` VARCHAR(255) NOT NULL DEFAULT '' AFTER `account`"],
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` CHANGE `value3` `value3` VARCHAR(255) NOT NULL DEFAULT ''"],
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` CHANGE `value4` `value4` VARCHAR(255) NOT NULL DEFAULT ''"],
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` CHANGE `value5` `value5` VARCHAR(255) NOT NULL DEFAULT ''"],
['type' => 'alter', 'table' => 'actions', 'sql' => "ALTER TABLE `actions` CHANGE `value6` `value6` VARCHAR(255) NOT NULL DEFAULT ''"],

// === ALTER TABLES - DailyFeatures/Messages ===
['type' => 'alter', 'table' => 'dailyfeatures', 'sql' => "ALTER TABLE dailyfeatures ADD webhookSent INT NOT NULL DEFAULT '0' AFTER type"],
['type' => 'alter', 'table' => 'messages', 'sql' => "ALTER TABLE `messages` ADD `readTime` INT NOT NULL DEFAULT '0' AFTER `isNew`"],
];

// ✅ Execute migrations
$success = 0;
$skipped = 0;
$errors = [];

foreach ($migrations as $mig) {
    if ($mig['type'] === 'table') {
        $check = $db->query("SHOW TABLES LIKE '{$mig['name']}'");
        if ($check && empty($check->fetchAll())) {
            if (safeQuery($db, $mig['sql'], $mig['name'])) {
                $success++;
            } else {
                $errors[] = "Failed to create table: {$mig['name']}";
            }
        } else {
            $skipped++;
        }
    } elseif ($mig['type'] === 'alter') {
        // Cek apakah column sudah ada sebelum ALTER
        if (preg_match('/ADD\s+`?(\w+)`?/i', $mig['sql'], $colMatch) ||
            preg_match('/CHANGE\s+`?\w+`?\s+`?(\w+)`?/i', $mig['sql'], $colMatch)) {
            $colName = $colMatch[1];
        $check = $db->query("SHOW COLUMNS FROM `{$mig['table']}` LIKE '{$colName}'");
        if ($check && empty($check->fetchAll())) {
            if (safeQuery($db, $mig['sql'], "{$mig['table']}.{$colName}")) {
                $success++;
            } else {
                $errors[] = "Failed to alter {$mig['table']}.{$colName}";
            }
        } else {
            $skipped++;
        }
            } elseif (preg_match('/DROP\s+`?(\w+)`?/i', $mig['sql'], $colMatch)) {
                // Untuk DROP, cek dulu apakah column ada
                $colName = $colMatch[1];
                $check = $db->query("SHOW COLUMNS FROM `{$mig['table']}` LIKE '{$colName}'");
                if ($check && !empty($check->fetchAll())) {
                    if (safeQuery($db, $mig['sql'], "{$mig['table']}.DROP.{$colName}")) {
                        $success++;
                    } else {
                        $errors[] = "Failed to drop {$mig['table']}.{$colName}";
                    }
                } else {
                    $skipped++;
                }
            }
    }
}

// ✅ Migrasi ban system (jika diperlukan)
$checkBans = $db->query("SHOW COLUMNS FROM `users` LIKE 'isUploadBanned'");
if ($checkBans && !empty($checkBans->fetchAll())) {
    $allBans = $db->prepare('SELECT userID, isBanned, isCreatorBanned, isUploadBanned, isCommentBanned, banReason FROM users WHERE isBanned > 0 OR isCreatorBanned > 0 OR isUploadBanned > 0 OR isCommentBanned > 0');
    if ($allBans->execute()) {
        $allBans = $allBans->fetchAll();
        foreach($allBans AS &$ban) {
            if($ban['banReason'] == 'none' || $ban['banReason'] == 'banned') $ban['banReason'] = '';
            $banType = 0;
            if ($ban['isBanned'] > 0) $banType = 1;
            elseif ($ban['isCreatorBanned'] > 0) $banType = 2;
            elseif ($ban['isUploadBanned'] > 0) $banType = 3;
            elseif ($ban['isCommentBanned'] > 0) $banType = 4;

            if ($banType > 0) {
                $gs->banPerson(0, $ban['userID'], $ban['banReason'], $banType - 1, 1, 2147483647);
            }
        }
    }
    // Drop old ban columns
    foreach (['isBanned', 'isCreatorBanned', 'isUploadBanned', 'isCommentBanned', 'banReason'] as $col) {
        $chk = $db->query("SHOW COLUMNS FROM `users` LIKE '$col'");
        if ($chk && !empty($chk->fetchAll())) {
            safeQuery($db, "ALTER TABLE `users` DROP `$col`", "Drop $col");
        }
    }
}

// ✅ Migrasi events/vaultcodes reward field (jika diperlukan)
foreach (['events', 'vaultcodes'] as $tbl) {
    $chk = $db->query("SHOW COLUMNS FROM `$tbl` LIKE 'reward'");
    if ($chk && !empty($chk->fetchAll())) {
        $chk2 = $db->query("SHOW COLUMNS FROM `$tbl` LIKE 'rewards'");
        if ($chk2 && empty($chk2->fetchAll())) {
            safeQuery($db, "ALTER TABLE `$tbl` ADD `rewards` varchar(2048) NOT NULL DEFAULT '' AFTER `duration`", "$tbl add rewards");
        }
        safeQuery($db, "UPDATE $tbl SET rewards = CONCAT(IFNULL(type,''), ',', IFNULL(reward,''))", "$tbl migrate rewards");
        safeQuery($db, "ALTER TABLE `$tbl` DROP `type`", "$tbl drop type");
        safeQuery($db, "ALTER TABLE `$tbl` DROP `reward`", "$tbl drop reward");
    }
}

// ✅ Update config: set $installed = true
try {
    $configLines = file(CONFIG_FILE, FILE_IGNORE_NEW_LINES);
    $newConfig = "<?php\n\$installed = true; // Auto-set by installer\n";

    $skipNext = false;
    foreach ($configLines as $i => $line) {
        if ($skipNext) {
            $skipNext = false;
            continue;
        }
        // Skip old $installed line + next comment line
        if (preg_match('/^\$installed\s*=/', $line)) {
            $skipNext = true;
            continue;
        }
        $newConfig .= $line . "\n";
    }

    if (is_writable(CONFIG_FILE)) {
        file_put_contents(CONFIG_FILE, $newConfig);
    } else {
        $errors[] = "Tidak bisa update config/dashboard.php (permission denied). Silakan set \$installed = true manual.";
    }
} catch (Exception $e) {
    $errors[] = "Gagal update config: " . $e->getMessage();
}

// ✅ Output hasil instalasi
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>✅ Instalasi Selesai - GDPS Dashboard</title>
<style>
body { font-family: system-ui, -apple-system, sans-serif; background: #1a1a2e; color: #eee; padding: 2rem; }
.container { max-width: 800px; margin: 0 auto; background: #16213e; border-radius: 12px; padding: 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
h1 { color: #00d9ff; margin-bottom: 1rem; }
.success { color: #00e676; }
.warning { color: #ffab40; }
.error { color: #ff5252; }
.stats { display: flex; gap: 1rem; margin: 1.5rem 0; flex-wrap: wrap; }
.stat { background: #0f3460; padding: 1rem; border-radius: 8px; min-width: 120px; text-align: center; }
.stat-num { font-size: 2rem; font-weight: bold; }
.log { background: #0a0a1a; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.9rem; max-height: 300px; overflow-y: auto; margin-top: 1rem; }
.btn { display: inline-block; background: #00d9ff; color: #000; padding: 0.8rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600; margin-top: 1rem; transition: transform 0.2s; }
.btn:hover { transform: translateY(-2px); }
ul { padding-left: 1.5rem; }
li { margin: 0.3rem 0; }
</style>
</head>
<body>
<div class="container">
<h1>🎉 Instalasi Dashboard Selesai!</h1>

<div class="stats">
<div class="stat">
<div class="stat-num success"><?php echo $success; ?></div>
<div>Migrations Sukses</div>
</div>
<div class="stat">
<div class="stat-num warning"><?php echo $skipped; ?></div>
<div>Sudah Ada (Skip)</div>
</div>
<div class="stat">
<div class="stat-num <?php echo empty($errors) ? 'success' : 'error'; ?>"><?php echo count($errors); ?></div>
<div>Error</div>
</div>
</div>

<?php if (!empty($errors)): ?>
<p class="error">⚠️ Terdapat error selama instalasi:</p>
<ul>
<?php foreach ($errors as $err): ?>
<li><?php echo htmlspecialchars($err); ?></li>
<?php endforeach; ?>
</ul>
<p class="warning">💡 Jika error tentang config/dashboard.php, buka file tersebut dan pastikan baris ke-2 berisi: <code>$installed = true;</code></p>
<?php else: ?>
<p class="success">✅ Semua migrasi database berhasil dijalankan!</p>
<?php endif; ?>

<div class="log">
<strong>📋 Ringkasan:</strong><br>
• Tabel baru: replies, demonlist, clans, events, vaultcodes, bans, automod, sfxs, dlsubmits, favsongs<br>
• Kolom baru di roles: dashboardLevelPackCreate, dashboardAddMod, demonlistAdd, dll<br>
• Kolom baru di accounts: auth, timezone, mail, discord, tiktok, instagram, custom<br>
• Kolom baru di users: clan, joinedAt, dlPoints<br>
• Migrasi sistem ban lama → baru<br>
• Konversi field reward → rewards
</div>

<a href="./" class="btn">🚀 Buka Dashboard Sekarang</a>

<p style="margin-top: 2rem; font-size: 0.9rem; color: #888;">
🔐 <strong>Keamanan:</strong> Setelah dashboard berjalan normal, Anda bisa menghapus file <code>install.php</code> untuk mencegah eksekusi ulang.
</p>
</div>
</body>
</html>
<?php
// Pastikan script berhenti di sini
exit();
?>
