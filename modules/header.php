<?php

	// モジュール読み込み
	require_once (dirname(__FILE__).'/require.php');
	require_once (dirname(__FILE__).'/module.php');

	// 設定変更用のトークンを管理
	if (isset($_COOKIE['tvrp_csrf_token']) && is_string($_COOKIE['tvrp_csrf_token'])) {
		$csrf_token = $_COOKIE['tvrp_csrf_token'];
	} else {
		$csrf_token = '_'.bin2hex(openssl_random_pseudo_bytes(16));
		setcookie('tvrp_csrf_token', $csrf_token, 0, '/');
	}

	// ストリーム番号を取得
	$stream = getStreamNumber($_SERVER['REQUEST_URI']);

	// iniファイル読み込み
	$ini = json_decode(file_get_contents_lock_sh($inifile), true);

	$backtrace = debug_backtrace();

?>

<!DOCTYPE html>
<html lang="ja">

<head>

<?php	if (strpos($backtrace[0]['file'], 'watch.php') !== false) { ?>
  <title>録画番組 - <?= $site_title; ?></title>
<?php	} else if (strpos($backtrace[0]['file'], 'settings.php') !== false) { ?>
  <title>設定 - <?= $site_title; ?></title>
<?php	} else { ?>
  <title><?= $site_title; ?></title>
<?php	} // 括弧終了 ?>
  <meta charset="UTF-8">
  <meta name="theme-color" content="#191919">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">

  <!-- Style -->
  <link rel="manifest" href="/manifest.json">
  <link rel="manifest" href="/manifest.webmanifest">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" type="text/css" href="https://use.fontawesome.com/releases/v5.15.3/css/all.css">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap">
  <link rel="stylesheet" type="text/css" href="/files/toastr.min.css">
  <link rel="stylesheet" type="text/css" href="/files/balloon.min.css">
  <link rel="stylesheet" type="text/css" href="/files/luminous.min.css">
  <link rel="stylesheet" type="text/css" href="/files/style.css?<?= $version; ?>">
<?php
	if (strpos($backtrace[0]['file'], 'index.php') !== false) { // index.php のみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/swiper.min.css">'."\n";
		echo '  <link rel="stylesheet" type="text/css" href="/files/customize.css">'."\n";
	}
	if (strpos($backtrace[0]['file'], 'watch.php') !== false) { // watch.php のみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/watch.css?'.$version.'">'."\n";
	}
	if (strpos($backtrace[0]['file'], 'settings.php') !== false) { // settings.php のみ
		echo '  <link rel="stylesheet" type="text/css" href="/files/settings.css?'.$version.'">'."\n";
	}
?>

  <!-- Script -->
  <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
  <script type="text/javascript" src="/files/pwacompat.min.js" async></script>
  <script type="text/javascript" src="/files/jquery.min.js"></script>
  <script type="text/javascript" src="/files/toastr.min.js"></script>
  <script type="text/javascript" src="/files/js.cookie.min.js"></script>
  <script type="text/javascript" src="/files/velocity.min.js"></script>
  <script type="text/javascript" src="/files/moment.min.js"></script>
  <script type="text/javascript" src="/files/luminous.min.js"></script>
  <script type="text/javascript" src="/files/css_browser_selector.js"></script>
  <script type="text/javascript" src="/files/common.js?<?= $version; ?>"></script>
<?php
	if (strpos($backtrace[0]['file'], 'index.php') !== false) { // index.php のみ
    echo '  <script type="text/javascript" src="/files/DPlayer.min.js"></script>'."\n";
    echo '  <script type="text/javascript" src="/files/hls.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/clusterize.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/swiper.min.js"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/index.js?'.$version.'"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/script.js?'.$version.'"></script>'."\n";
		echo '  <script type="text/javascript" src="/files/jikkyo.js?'.$version.'"></script>'."\n";
	}
	if (strpos($backtrace[0]['file'], 'watch.php') !== false) { // watch.php のみ
		echo '  <script type="text/javascript" src="/files/watch.js?'.$version.'"></script>'."\n";
	} else if (strpos($backtrace[0]["file"], 'settings.php') !== false) { // settings.php のみ
		echo '  <script type="text/javascript" src="/files/settings.js?'.$version.'"></script>'."\n";
	}
?>

  <script>

    // 個人設定の初期値
    settings = {
        twitter_show: false,
        comment_show: true,
        dark_theme: matchMedia('(prefers-color-scheme: dark)').matches,  // ダークモードなら true になる
        subchannel_show: false,
        list_view: false,
        logo_show: true,
        vertical_navmenu: false,
        comment_size: 35,
        comment_delay: 5,
        comment_file_delay: 0,
        comment_list_performance: 'light',
        quality_user_default: 'environment',
        list_view_number: 30,
        onclick_stream: false,
        player_floating: true,
    };
    if (Cookies.get('tvrp_settings') === undefined) {
        var json = JSON.stringify(settings);
        Cookies.set('tvrp_settings', json, { expires: 365 });
    } else {
        settings = JSON.parse(Cookies.get('tvrp_settings'));
    }
    if (settings['dark_theme']) {
        document.documentElement.classList.add('dark-theme');
    }
    if (settings['vertical_navmenu']) {
        document.documentElement.classList.add('vertical-navmenu');
    }

    window.addEventListener('load', function() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register("/serviceworker.js");
        }
    });

<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false) { // index.php のみ ?>
<?php		if ($ini[$stream]['state'] == 'File' and !preg_match('/^(?:ts|mts|m2t|m2ts)$/', $ini[$stream]['fileext']) and $ini[$stream]['encoder'] == 'Progressive') { ?>
    stream = '<?= $stream; ?>';
    streamurl = 'http://<?= $_SERVER['SERVER_NAME'].':'.$http_port; ?>/api/stream/<?= $stream; ?>';
    streamtype = 'video/mp4';

<?php		} else { ?>
    stream = '<?= $stream; ?>';
    streamurl = 'http://<?= $_SERVER['SERVER_NAME'].':'.$http_port; ?>/stream/stream<?= $stream; ?>.m3u8';
    streamtype = 'application/vnd.apple.mpegurl';

<?php		} //括弧終了 ?>
<?php	} // 括弧終了 ?>
  </script>

</head>

<body class="scrollbar">

  <nav id="top">
    <div id="nav-open">
      <i class="material-icons">menu</i>
    </div>
    <a id="logo" href="/">
      <img src="<?= $icon_file; ?>">
    </a>
    <a class="top-link<?= (strpos($backtrace[0]["file"], 'watch.php') !== false ? ' top-link-current' : '') ?>" href="/watch/">
      <i class="fas fa-video"></i>
      <span class="top-link-href">録画番組</span>
    </a>
    <a class="top-link<?= (strpos($backtrace[0]["file"], 'settings.php') !== false ? ' top-link-current' : '') ?>" href="/settings/">
      <i class="fas fa-cog"></i>
      <span class="top-link-href">設定</span>
    </a>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false or strpos($backtrace[0]["file"], 'watch.php') !== false) { // index.php・watch.php のみ ?>
    <div id="menu-button">
      <i class="material-icons">more_vert</i>
    </div>
<?php	} else { ?>
    <div id="menu-fakebutton"></div>
<?php	} // 括弧終了 ?>
  </nav>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false) { // index.php のみ ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
      <div id="fullscreen" class="menu-link" aria-label="画面全体をフルスクリーンで表示します" data-balloon-pos="up">
        <i class="fas fa-expand" style="font-size: 117%;"></i>
        <span class="menu-link-href">フルスクリーンで表示</span>
      </div>
<?php	if (isSettingsItem('subchannel_show', true)) { ?>
      <div id="subchannel-hide" class="menu-link" aria-label="メインチャンネルのみ番組表に表示します" data-balloon-pos="up">
        <i class="fas fa-broadcast-tower"></i>
        <span class="menu-link-href">サブチャンネルを隠す</span>
      </div>
<?php	} else { ?>
      <div id="subchannel-show" class="menu-link" aria-label="サブチャンネルを番組表に表示します" data-balloon-pos="up">
        <i class="fas fa-broadcast-tower"></i>
        <span class="menu-link-href">サブチャンネルを表示</span>
      </div>
<?php	} // 括弧終了 ?>
      <google-cast-launcher style="display: none;" aria-label="Chromecast や Android TV で再生できます" data-balloon-pos="up"></google-cast-launcher>
      <div id="cast-toggle" class="menu-link" aria-label="Chromecast や Android TV で再生できます" data-balloon-pos="up">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 21px;">
          <path fill="currentColor" d="M447.83 64H64a42.72 42.72 0 0 0-42.72 42.72v63.92H64v-63.92h383.83v298.56H298.64V448H448a42.72 42.72 0 0 0 42.72-42.72V106.72A42.72 42.72 0 0 0 448 64zM21.28 383.58v63.92h63.91a63.91 63.91 0 0 0-63.91-63.92zm0-85.28V341a106.63 106.63 0 0 1 106.64 106.66v.34h42.72a149.19 149.19 0 0 0-149-149.36h-.33zm0-85.27v42.72c106-.1 192 85.75 192.08 191.75v.5h42.72c-.46-129.46-105.34-234.27-234.8-234.64z"></path>
        </svg>
        <span class="menu-link-href">キャストを開始</span>
      </div>
      <div id="ljicrop" class="menu-link" aria-label="Ｌ字画面のクロップの設定を表示します" data-balloon-pos="up">
        <i class="fas fa-tv"></i>
        <span class="menu-link-href">Ｌ字画面のクロップ</span>
      </div>
      <div id="layout-toggle" class="menu-link" aria-label="画面のレイアウトを変更します" data-balloon-pos="up">
        <i style="position: relative; width: 23px; left: -1px;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path fill="currentColor" d="M9 21H5c-1.1 0-2-.9-2-2V5c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2v14c0 1.1-.9 2-2 2zm6 0h4c1.1 0 2-.9 2-2v-5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v5c0 1.1.9 2 2 2zm6-13V5c0-1.1-.9-2-2-2h-4c-1.1 0-2 .9-2 2v3c0 1.1.9 2 2 2h4c1.1 0 2-.9 2-2z"></path>
          </svg>
        </i>
        <span class="menu-link-href">画面レイアウトを変更</span>
      </div>
      <div id="hotkey" class="menu-link" aria-label="キーボードショートカットの一覧を表示します" data-balloon-pos="up">
        <i class="fas fa-keyboard"></i>
        <span class="menu-link-href">ショートカット一覧</span>
      </div>
    </div>
  </nav>
<?php	} // 括弧終了 ?>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false) { // watch.php のみ ?>

  <nav id="menu-content">
    <div id="menu-link-wrap">
<?php	if (isSettingsItem('list_view', true)) { ?>
      <div id="normal-view" class="menu-link" aria-label="録画一覧を通常通り表示します" data-balloon-pos="up">
        <i class="fas fa-th-list"></i>
        <span class="menu-link-href">通常表示に切り替え</span>
      </div>
<?php	} else { ?>
      <div id="list-view" class="menu-link" aria-label="録画番組を細いリストで表示します" data-balloon-pos="up">
        <i class="fas fa-list"></i>
        <span class="menu-link-href">リスト表示に切り替え</span>
      </div>
<?php	} // 括弧終了 ?>
      <div id="list-update" class="menu-link">
        <i class="fas fa-redo-alt"></i>
        <span class="menu-link-href">リストを更新</span>
      </div>
      <div id="list-reset" class="menu-link">
        <i class="fas fa-trash-restore-alt"></i>
        <span class="menu-link-href">リストをリセット</span>
      </div>
      <div id="history-reset" class="menu-link">
        <i class="fas fa-trash-alt"></i>
        <span class="menu-link-href">再生履歴をリセット</span>
      </div>
    </div>
  </nav>
<?php	} // 括弧終了 ?>

  <nav id="nav-content">
    <div class="nav-logo">
      <img src="<?= $icon_file; ?>">
    </div>
    <a class="nav-link" href="/">
      <i class="fas fa-home"></i>
      <span class="nav-link-href">ホーム</span>
    </a>
<?php	if (strpos($backtrace[0]["file"], 'index.php') !== false) { // index.php のみ ?>
    <form method="post" name="quickstop" action="/settings/">
      <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="state" value="Offline">
      <input type="hidden" name="stream" value="<?= $stream; ?>">
      <a class="nav-link" href="javascript:quickstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">このストリームを終了</span>
      </a>
    </form>
<?php	} // 括弧終了 ?>
    <form method="post" name="allstop" action="/settings/">
      <input type="hidden" name="_csrf_token" value="<?= $csrf_token ?>">
      <input type="hidden" name="state" value="Offline">
      <input type="hidden" name="stream" value="<?= $stream; ?>">
      <input type="hidden" name="allstop" value="true">
      <a class="nav-link" href="javascript:allstop.submit()">
        <i class="far fa-stop-circle"></i>
        <span class="nav-link-href">全てのストリームを終了</span>
      </a>
    </form>
    <a class="nav-link" href="/wholech/">
      <i class="fas fa-table"></i>
      <span class="nav-link-href">まるごとchに移動</span>
    </a>
    <a class="nav-link" href="/watch/">
      <i class="fas fa-video"></i>
      <span class="nav-link-href">録画番組を再生</span>
    </a>
    <a class="nav-link" href="/tweet/auth">
      <i class="fab fa-twitter"></i>
      <span class="nav-link-href">Twitter ログイン</span>
    </a>
    <a class="nav-link" href="/settings/">
      <i class="fas fa-cog"></i>
      <span class="nav-link-href">設定</span>
    </a>
<?php
	if ($update_confirm == 'true') {
		$update_context = stream_context_create(['http' => ['timeout' => 1]]);  // 1秒でタイムアウト
		$update = file_get_contents('https://raw.githubusercontent.com/tsukumijima/TVRemotePlus/master/data/version.txt?_='.time(), false, $update_context);
		// 取得したバージョンと現在のバージョンが違う場合のみ
		if ($update != $version) {
			echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank" '.
						'aria-label="アップデートがあります (version '.str_replace('v', '', $update).')" data-balloon-pos="up">'."\n";
			echo '      <i class="fas fa-history" style="color: #e8004a;"></i>'."\n";
		} else {
			echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank">'."\n";
			echo '      <i class="fas fa-history"></i>'."\n";
		}
	} else {
		echo '    <a class="nav-link" href="https://github.com/tsukumijima/TVRemotePlus/releases" target="_blank">'."\n";
		echo '      <i class="fas fa-history"></i>'."\n";
	}
?>
      <span class="nav-link-href">
        version <?= str_replace('v', '', $version); ?>

      </span>
    </a>
  </nav>
<?php	if (strpos($backtrace[0]["file"], 'watch.php') !== false) { // watch.php のみ ?>
  <div id="cover" class="open"></div>
<?php	} else { ?>
  <div id="cover"></div>
<?php	} // 括弧終了 ?>
  <div id="nav-close"></div>
  <div id="menu-close"></div>

  <section id="main">
