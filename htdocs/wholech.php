<?php

// レスポンスをバッファに貯める
ob_start();

// モジュール読み込み
require_once ('../modules/require.php');
require_once ('../modules/module.php');
require_once ('../modules/stream.php');

// 設定変更用のトークンを管理
if (isset($_COOKIE['tvrp_csrf_token']) && is_string($_COOKIE['tvrp_csrf_token'])) {
    $csrf_token = $_COOKIE['tvrp_csrf_token'];
} else {
    $csrf_token = '_'.bin2hex(openssl_random_pseudo_bytes(16));
    setcookie('tvrp_csrf_token', $csrf_token, 0, '/');
}

// iniファイル読み込み
$ini = json_decode(file_get_contents_lock_sh($inifile), true);

$backtrace = debug_backtrace();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>まるごとch on <?= $site_title; ?></title>
    <link rel="stylesheet" href="/files/wholech/style.css">
    <script type="text/javascript" src="/files/jquery.min.js"></script>
    <script type="text/javascript" src="/files/js.cookie.min.js"></script>
    <script type="text/javascript" src="/files/hls.min.js"></script>
    <script src="/files/wholech/script.js"></script>
    <script src="/files/wholech/fsswitch.js"></script>
    <script src="/files/wholech/control.js"></script>
    <!-- Material icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/files/wholech/material-icons.css">
    <!-- /Material icons -->
</head>
<body>
<?php

	echo '    <pre id="debug">';

	// BonDriverとチャンネルを取得
	list($BonDriver_dll, $BonDriver_dll_T, $BonDriver_dll_S, // BonDriver
		$ch, $ch_T, $ch_S, $ch_CS, // チャンネル番号
		$sid, $sid_T, $sid_S, $sid_CS, // SID
		$onid, $onid_T, $onid_S, $onid_CS, // ONID(NID)
		$tsid, $tsid_T, $tsid_S, $tsid_CS) // TSID
		= initBonChannel($BonDriver_dir);

	// 溜めてあった出力を解放しフラッシュする
	ob_end_flush();
	ob_flush();
	flush();

	echo '</pre>'."\n";

    // ストリームがないときにストリームを開始する

    if (isSettingsItem('subchannel_show', true)){ // サブチャンネルを表示していたら非表示にする(サブチャンネルのストリーム開始を防ぐ)
?>
        <script>
            settings = JSON.parse(Cookies.get('tvrp_settings'));
            settings['subchannel_show'] = false;
            Cookies.set('tvrp_settings', JSON.stringify(settings), { expires: 365 });
            // リロード
            setTimeout(function(){
                location.reload();
            }, 300);
        </script>
<?php
    } else { // サブチャンネルを表示していないときだけ
        // ストリーム番号
        $stream = 1;
        foreach ($ch_T as $i => $value){ // 地デジchの数だけ繰り返す
            if (!isStreamActive($ini, $stream)){
                // ステータス
                $ini[$stream]['state'] = 'ONAir';

                // チャンネル
                $ini[$stream]['channel'] = $i;

                // ↓ はデフォルト値を使う

                // 動画の画質
                $ini[$stream]['quality'] = getQualityDefault();

                // エンコーダー
                $ini[$stream]['encoder'] = $encoder_default;

                // 字幕データ
                $ini[$stream]['subtitle'] = $subtitle_default;

                // BonDriver
                $ini[$stream]['BonDriver'] = $BonDriver_default_T;

                // ストリームを終了する
                stream_stop($stream);

                // ストリームを開始する
                stream_start($stream, $ini[$stream]['channel'], $sid[$ini[$stream]['channel']], $tsid[$ini[$stream]['channel']], $ini[$stream]['BonDriver'], $ini[$stream]['quality'], $ini[$stream]['encoder'], $ini[$stream]['subtitle']);

                // 準備中用の動画を流すためにm3u8をコピー
                if ($silent == 'true') {
                    copy($standby_silent_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
                } else {
                    copy($standby_m3u8, $base_dir.'htdocs/stream/stream'.$stream.'.m3u8');
                }

                // ファイル書き込み
                file_put_contents($inifile, json_encode($ini, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
            }
            $stream++;
        }
    }
?>
    <div id="wrap">
        <div id="chlist">
<?php	foreach ($ini as $i => $value): // ストリームの数だけ繰り返す ?>
<?php
        $channel = @$ch[strval($ini[$i]['channel'])];
      	if ($ini[$i]['channel'] < 55){
            $ch_T_channel = sprintf('%d', intval($ini[$i]['channel']))/* sprintf('%03d', str_replace('_', '', $ini[$i]['channel'])) */;
        }else{
            $ch_T_channel = sprintf('%03d', $ini[$i]['channel']);
        }
?>
            <div class="chframe">
                <video src="/stream/stream<?= $i; ?>.m3u8" playsinline controlsList="noremoteplayback"></video>
                
                <div id="ch<?= str_replace('.', '_', $ini[$i]['channel']); ?>" class="broadcast-wrap" data-ch="<?= $ini[$i]['channel']; ?>"
                        data-channel="<?= $ch_T_channel; ?>" data-name="<?= $channel; ?>"
                        data-starttime="00:00" data-endtime="00:00" data-title="取得中です…" data-logo="<?= getLogoURL($ini[$i]['channel']); ?>">

                    <div class="broadcast-channel-box">
                        <span class="broadcast-channel"><?= $ch_T_channel; ?></span>
                        <img class="broadcast-logo" src="<?= getLogoURL($ini[$i]['channel']); ?>" alt="<?= $channel; ?>">
                    </div>
                    <div class="broadcast-title">
                        <span class="broadcast-title-id">取得中です…</span>
                        <div class="broadcast-time">
                            <span class="broadcast-start">00:00</span>
                            <span class="broadcast-to">～</span>
                            <span class="broadcast-end">00:00</span>
                        </div>
                    </div>

                </div>
            </div>
<?php	endforeach; ?>
        </div>
        <div id="control">
            <a href="/"><?= $site_title; ?></a>
            <button type="button" id="volumebutton" title="全ch聴く"><i class="material-icons">volume_up</i></button>
            <button type="button" id="livebutton" title="同期する">Live</button>
            <div class="togglesw" title="常に表示">
                <input type="checkbox" id="showsw">
                <label for="showsw"><i class="material-icons">info</i></label>
            </div>
            <form method="post" name="allstop" action="/settings/">
                <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="state" value="Offline">
                <input type="hidden" name="allstop" value="true">
                <button type="submit" title="すべてのストリームを終了"><i class="material-icons">cancel_presentation</i></button>
            </form>
            <button type="button" id="fsbutton" title="全画面表示"><i class="material-icons">fullscreen</i></button>
        </div>
    </div>
</body>
</html>