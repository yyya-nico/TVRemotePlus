var listening = null;
document.addEventListener('DOMContentLoaded', function() {
    const chList = document.getElementById('chlist');
    const chFrames = document.getElementsByClassName('chframe');
    const videos = document.getElementsByTagName('video');
    const volumeButton = document.getElementById('volumebutton');
    const liveButton = document.getElementById('livebutton');
    for (let index = 0; index < chFrames.length; index++) {
        const video = videos[index];
        const chFrame = chFrames[index];
        if (video.canPlayType('application/vnd.apple.mpegurl') == false && Hls.isSupported()) {
            let hls = new Hls();
            hls.loadSource(video.src);
            hls.attachMedia(video);
        } else {
            liveButton.innerHTML = '<span class="material-icons">cached</span>';
            liveButton.title = '再読み込みする';
        }
        video.addEventListener('volumechange',() => {
            if (video.muted) {
                chFrame.classList.remove('listening');
                volumeButton.title = '全ch聴く';
                if (listening == null) {
                    volumeButton.innerHTML = '<i class="material-icons">volume_off</i>';
                } else {
                    volumeButton.innerHTML = '<i class="material-icons">volume_up</i>';
                }
            } else {
                chFrame.classList.add('listening');
                if (listening == 'all') {
                    volumeButton.title = 'ミュートする';
                } else {
                    volumeButton.title = '全ch聴く';
                }
                volumeButton.innerHTML = '<i class="material-icons">volume_up</i>';
            }
        });
        video.play()
        .then(function () {
            listening = 'all';
            chFrame.classList.add('listening');
            volumeButton.title = 'ミュートする';
            volumeButton.innerHTML = '<i class="material-icons">volume_up</i>';
        })
        .catch(function () {
            video.muted = true;
            video.play();
        });
        chFrame.addEventListener('click',function() {
            if (listening == index) {
                listening = null;
                video.muted = true;
                chList.classList.remove('choiced');
            } else {
                listening = index;
                chList.classList.add('choiced');
                for (let index = 0; index < videos.length; index++) {
                    if (index == listening) {
                        videos[index].muted = false;
                    } else {
                        videos[index].muted = true;
                    }
                }
            }
        },false);
    }

    // ***** 番組表・ストリーム一覧表示 *****

    let epginfo_hash = '';
    let epginfo_data = {};
    function refresh_epginfo() {
        $.ajax({
            url: '/api/epginfo',
            data: { 'hash': epginfo_hash },
            dataType: 'json',
            cache: false,
        }).done(function(data) {

            epginfo_hash = data[0];
            if (data[1]) {
                epginfo_data = data[1];
            }
            data = epginfo_data;

            // 結果をHTMLにぶち込む

            // **** チャンネルリストの更新 ****
            for (key in data['stream']) {
                const ch_str = data['stream'][key]['ch_str'];
                const broadcast_wrap = document.getElementById(`ch${ch_str}`);
                // 変化ある場合のみ書き換え
                // 特に内容変わってもいないのに DOM を再構築するのはリソースの無駄
                if (broadcast_wrap.dataset.starttime != data['stream'][key]['starttime'] ||
                    broadcast_wrap.dataset.endtime != data['stream'][key]['endtime'] ||
                    broadcast_wrap.dataset.title != data['stream'][key]['program_name']) {

                    // 書き換え用html
                    let html =
                        `<div class="broadcast-channel-box">
                            <span class="broadcast-channel">` + broadcast_wrap.dataset.channel + `</span>
                            <img class="broadcast-logo" src="` + broadcast_wrap.dataset.logo + `" alt="` + broadcast_wrap.dataset.name + `">
                        </div>
                        <div class="broadcast-title">
                            <span class="broadcast-title-id">` + data['stream'][key]['program_name'] + `</span>
                            <div class="broadcast-time">
                                <span class="broadcast-start">` + data['stream'][key]['starttime'] + `</span>
                                <span class="broadcast-to">` + data['stream'][key]['to'] + `</span>
                                <span class="broadcast-end">` + data['stream'][key]['endtime'] + `</span>
                            </div>
                        </div>`;

                    // 番組情報を書き換え
                    broadcast_wrap.innerHTML = html;

                    // 番組情報を保存
                    broadcast_wrap.dataset.starttime = data['stream'][key]['starttime'];
                    broadcast_wrap.dataset.endtime = data['stream'][key]['endtime'];
                    broadcast_wrap.dataset.title =  data['stream'][key]['program_name'];

                }
            }
            setTimeout(refresh_epginfo, 8000);
        }).fail(function(data, status, error) {

            // エラーメッセージ
            console.error(`failed to get epginfo. status: ${status}\nerror: ${error.message}`);
            setTimeout(refresh_epginfo, 8000);
        });
    }
    refresh_epginfo();
},false);