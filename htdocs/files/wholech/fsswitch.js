document.addEventListener('DOMContentLoaded', function () {
	var target = document.documentElement;
	var btn = document.getElementById("fsbutton");
	
	btn.onclick = requestFullscreen;

	/*フルスクリーン実行用ファンクション*/
	function requestFullscreen() {
		if (target.webkitRequestFullscreen) {
			target.webkitRequestFullscreen(); //Chrome15+, Safari5.1+, Opera15+
		} else if (target.mozRequestFullScreen) {
			target.mozRequestFullScreen(); //FF10+
		} else if (target.msRequestFullscreen) {
			target.msRequestFullscreen(); //IE11+
		} else if (target.requestFullscreen) {
			target.requestFullscreen(); // HTML5 Fullscreen API仕様
		} else {
			alert('ご利用のブラウザは全画面表示に対応していません');
			return;
		}
		if (screen.orientation.lock) {
			screen.orientation.lock('landscape');
		}
		/* フルスクリーン終了用ファンクションボタンに切り替える */
		btn.onclick = exitFullscreen;
		btn.innerHTML = '<i class="material-icons">fullscreen_exit</i>';
		btn.setAttribute('title','全画面表示を終了');
	}
	/*フルスクリーン終了用ファンクション*/
	function exitFullscreen() {
		if (document.webkitCancelFullScreen) {
			document.webkitCancelFullScreen(); //Chrome15+, Safari5.1+, Opera15+
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen(); //FF10+
		} else if (document.msExitFullscreen) {
			document.msExitFullscreen(); //IE11+
		} else if (document.cancelFullScreen) {
			document.cancelFullScreen(); //Gecko:FullScreenAPI仕様
		} else if (document.exitFullscreen) {
			document.exitFullscreen(); // HTML5 Fullscreen API仕様
		}
		if (screen.orientation.unlock) {
			screen.orientation.unlock();
		}
		/*フルスクリーン実行用ファンクションボタンに切り替える*/
		btn.onclick = requestFullscreen;
		btn.innerHTML = '<i class="material-icons">fullscreen</i>';
		btn.setAttribute('title','全画面表示');
	}
	/*サポートしていない環境ではフルスクリーンボタンを非表示*/
	if ((document.uniqueID && document.documentMode < 11) || !(target.requestFullscreen)) {
		btn.style.display = 'none';
	}

	var trrigerFullscreenChange = function(event) {
		if( (document.webkitFullscreenElement && document.webkitFullscreenElement !== null)
		 || (document.mozFullScreenElement && document.mozFullScreenElement !== null)
		 || (document.msFullscreenElement && document.msFullscreenElement !== null)
		 || (document.fullScreenElement && document.fullScreenElement !== null) ) {

		}else{
			exitFullscreen();
		}
	}
	document.addEventListener("webkitfullscreenchange", trrigerFullscreenChange, false);
	document.addEventListener("mozfullscreenchange", trrigerFullscreenChange, false);
	document.addEventListener("msfullscreenchange", trrigerFullscreenChange, false);
	document.addEventListener('fullscreenchange', trrigerFullscreenChange, false);
},false);