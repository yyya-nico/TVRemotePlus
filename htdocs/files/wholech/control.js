function getVideoDuration(video){
    let duration = video.duration;
    if (duration === Infinity) {
        if (video.seekable.length > 0) {
            duration = video.seekable.end(0);
        } else if (video.buffered.length > 0) {
            duration = video.buffered.end(0);
        }
    }
    return duration;
}

function sync(video) {
    const time = getVideoDuration(video) - 0.4; // 0.4s is play buffer
    try {
        video.currentTime = time;
    } catch (error) {
        // seek failed
        console.log(error);
        return;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const wrap = document.getElementById('wrap');
    const chFrames = document.getElementsByClassName('chframe');
    const chList = document.getElementById('chlist');
    const videos = document.querySelectorAll('#chlist video');
    const broadcastInfoList = document.getElementsByClassName('broadcast-wrap');
    const broadcastTitles = document.getElementsByClassName('broadcast-title');
    const controlWrap = document.getElementById('control');
    const volumeButton = document.getElementById('volumebutton');
    const liveButton = document.getElementById('livebutton');
    const showSw = document.getElementById('showsw');
    let fadeTimer = 0,delayfadeTimer = 0,expandTimer = Array(broadcastTitles.length).fill(0);

    function fade() {
        clearTimeout(fadeTimer);
        clearTimeout(delayfadeTimer);
        wrap.classList.remove('hide');
        for (let index = 0; index < broadcastInfoList.length; index++) {
            broadcastInfoList[index].classList.remove('hide');
        }
        controlWrap.classList.remove('hide');
        fadeTimer = setTimeout(function() {
            wrap.classList.add('hide');
            controlWrap.classList.add('hide');
            if (showSw.checked) {
                return;
            }
            delayfadeTimer = setTimeout(function() {
                for (let index = 0; index < broadcastInfoList.length; index++) {
                    broadcastInfoList[index].classList.add('hide');
                }
            },3000);
        },3000);
    }
    let expandedIndex = null;
    function expandWrap() {
        expand(this.name);
    }
    function expand(index) {
        if (index != expandedIndex) {
            if(expandedIndex != null){
                broadcastTitles[expandedIndex].classList.remove('expand');
            }
            expandedIndex = index;
        }
        clearTimeout(expandTimer[index]);
        broadcastTitles[index].classList.add('expand');
        expandTimer[index] = setTimeout(function() {
            broadcastTitles[index].classList.remove('expand');
        },6000);
    }
    window.addEventListener('pointerdown',fade,false);
    window.addEventListener('pointermove',fade,false);
    window.addEventListener('scroll',fade,false);
    showSw.addEventListener('change',fade,false);
    for (let index = 0; index < chFrames.length; index++) {
        chFrames[index].addEventListener('touchstart',{name: index, handleEvent: expandWrap},false);
        chFrames[index].addEventListener('pointermove',{name: index, handleEvent: expandWrap},false);
        chFrames[index].addEventListener('mouseleave',function() {
            broadcastTitles[index].classList.remove('expand');
        },false);
    }
    window.addEventListener('keydown',function(e){//キーボード操作
        const keyName = e.key;
        function chPick() {
            listening = 0;
            chList.classList.add('choiced');
            for (let index = 0; index < videos.length; index++) {
                if (index == listening) {
                    videos[index].muted = false;
                } else {
                    videos[index].muted = true;
                }
            }
        }
        fade();
        switch(keyName){
          case 'x':
            if(listening == 'all' || listening == null) {
                listening = listening == null ? 'all' : null;
                for (let index = 0; index < chFrames.length; index++) {
                    videos[index].muted = !(videos[index].muted);
                }
            } else {
                chList.classList.toggle('choiced');
                videos[listening].muted = !(videos[listening].muted);
            }
            break;
          case 'A':
          case 'a':
            volumeButton.click();
            fade();
            break;
          case 'ArrowUp':
            if (!isNaN(listening) && listening !== null) {
                if (listening - 3 >= 0) {
                    videos[listening].muted = true;
                    listening -= 3;
                    videos[listening].muted = false;
                    chList.classList.add('choiced');
                }
            } else {
                chPick();
            }
            expand(listening);
            break;
          case 'ArrowLeft':
            if (!isNaN(listening) && listening !== null) {
                if (listening - 1 >= 0) {
                    videos[listening].muted = true;
                    listening -= 1;
                    videos[listening].muted = false;
                    chList.classList.add('choiced');
                }
            } else {
                chPick();
            }
            expand(listening);
            break;
          case 'ArrowRight':
            if (!isNaN(listening) && listening !== null) {
                if (listening + 1 < chFrames.length) {
                    videos[listening].muted = true;
                    listening += 1;
                    videos[listening].muted = false;
                    chList.classList.add('choiced');
                }
            } else {
                chPick();
            }
            expand(listening);
            break;
          case 'ArrowDown':
            if (!isNaN(listening) && listening !== null) {
                if (listening + 3 < chFrames.length) {
                    videos[listening].muted = true;
                    listening += 3;
                    videos[listening].muted = false;
                    chList.classList.add('choiced');
                }
            } else {
                chPick();
            }
            expand(listening);
            break;
        }
      },false);
    volumeButton.addEventListener('click',function() {
        chList.classList.remove('choiced');
        if (listening != 'all') {
            listening = 'all';
            for (let index = 0; index < chFrames.length; index++) {
                videos[index].muted = false;
            }
        } else {
            listening = null;
            for (let index = 0; index < chFrames.length; index++) {
                videos[index].muted = true;
            }
        }
    },false);
    liveButton.addEventListener('click',function() {
        if (videos[0].canPlayType('application/vnd.apple.mpegurl') == false && Hls.isSupported()) {
            for (let index = 0; index < videos.length; index++) {
                sync(videos[index]);
            }
        } else {
            location.reload();
        }
    },false);
    window.addEventListener('scroll',function() {
        function getScrollBottom() {
            const body = window.document.body;
            const html = window.document.documentElement;
            const scrollTop = body.scrollTop || html.scrollTop;
            return html.scrollHeight - window.innerHeight - scrollTop;
        }
        if (getScrollBottom() <= 10) {
            //スクロールの位置が下10pxの範囲に来た場合
            controlWrap.classList.add('slide');
        } else {
            //それ以外のスクロールの位置の場合
            controlWrap.classList.remove('slide');
        }
    },false);
    fade();
},false);