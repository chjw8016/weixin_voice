<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{csrf_token()}}">
    <script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
    <script src="http://cdn.bootcss.com/jquery/3.1.1/jquery.min.js"></script>
    <title>Voice</title>
    <style>
        .btn-voice, .btn-mp3 {
            display: block;
            border-radius: 5px;
            background-color: #2ab27b;
            text-align: center;
            height: 50px;
            line-height: 50px;
            margin: 10px auto;
            width: 90%;
            color: white;
            text-decoration: none;
            font-weight: bolder;
        }

        .audio_wrp {
            border: 1px solid #ebebeb;
            background-color: #fcfcfc;
            overflow: hidden;
            padding: 12px 20px 12px 12px;
            display: block;
            margin: 10px auto;
            width: 90%;
        }

        .audio_play_area {
            float: left;
            padding-right: 10px;
        }

        .audio_play_area > img {
            width: 15px
        }

        .txt_audio {
            color: #2ca02c;
            font-size: 12px;
            display: none;
        }
    </style>
</head>
<body>
<p>
    {{--{{$appId}}<br/>
    {{$timestamp}}<br/>
    {{$nonceStr}}<br/>
    {{$signature}}--}}
</p>

<div class="voice-list">
    @foreach ($list as $item)
        <span class="audio_wrp">
            @if ($item["mp3"] == "0")
                <span class="audio_play_area" type="amr" data="{{$item["media_id"]}}">
                    <img src="/img/voice.png" class="pic_audio">
                </span>
            @else
                <span class="audio_play_area" type="mp3" data="/mp3/{{$item["media_id"]}}.mp3">

                    <img src="/img/voice.png" class="pic_audio">
                </span>
            @endif
            <span class="txt_audio">播放中...</span>
     </span>
    @endforeach
</div>
<audio id="audio"></audio>
<a href="javasript:void(0)" class="btn-voice">我要录音</a>

<a href="javasript:void(0)" class="btn-mp3">转换MP3</a>
</body>
<script>
    wx.config({
        debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
        appId: "{{$appId}}", // 必填，公众号的唯一标识
        timestamp: "{{$timestamp}}", // 必填，生成签名的时间戳
        nonceStr: '{{$nonceStr}}', // 必填，生成签名的随机串
        signature: '{{$signature}}',// 必填，签名，见附录1
        jsApiList: ["onMenuShareTimeline", "onMenuShareAppMessage", "startRecord", "", "stopRecord", "onVoiceRecordEnd", "playVoice", "pauseVoice", "stopVoice", "onVoicePlayEnd", "uploadVoice", "downloadVoice"] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
    });
    wx.ready(function () {
        wx.onVoicePlayEnd({
            success: function (res) {
                //stopWave();
            }
        });
        wx.onMenuShareTimeline({
            title: '录音测试程序', // 分享标题
            link: 'http://wxweekly.gammainfo.com/index', // 分享链接
            imgUrl: 'http://wxweekly.gammainfo.com/img/auto.jpg', // 分享图标
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
        wx.onMenuShareAppMessage({
            title: '录音测试程序', // 分享标题
            desc: '我写的一个录音测试程序，Android和IOS都可以，还有转码功能', // 分享描述
            link: 'http://wxweekly.gammainfo.com/index', // 分享链接
            imgUrl: 'http://wxweekly.gammainfo.com/img/auto.jpg', // 分享图标
            type: 'link', // 分享类型,music、video或link，不填默认为link
            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
            success: function () {
                // 用户确认分享后执行的回调函数
            },
            cancel: function () {
                // 用户取消分享后执行的回调函数
            }
        });
    });
    wx.error(function (res) {
        alert(res);
    });
    var voice = {}, START = 0, END = 0, recordTimer = null;
    $(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        bindPlay();
        $('.btn-voice').on('touchstart', function (event) {
            event.preventDefault();
            START = new Date().getTime();
            recordTimer = setTimeout(function () {
                wx.startRecord({
                    success: function () {
                        localStorage.rainAllowRecord = 'true';
                    },
                    cancel: function () {
                        alert('用户拒绝授权录音');
                    }
                });
            }, 300);
        });
        $('.btn-voice').on('touchend', function (event) {
            event.preventDefault();
            END = new Date().getTime();
            if ((END - START) < 300) {
                END = 0;
                START = 0;
                //小于300ms，不录音
                clearTimeout(recordTimer);
            } else {
                wx.stopRecord({
                    success: function (res) {
                        voice.localId = res.localId;
                        uploadVoice();

                    },
                    fail: function (res) {
                        alert(JSON.stringify(res));
                    }
                });
            }
        });
        $(".btn-mp3").on("click", function () {
            var _this = this;
            $(_this).html("转换中...");
            $(_this).attr("disabled", "disabled");
            $.ajax({
                url: '/download',
                type: 'get',
                dataType: "json",
                success: function (data) {
                    //alert('文件已经保存到七牛的服务器');//这回，我使用七牛存储
                    $(_this).html("转换MP3");
                    $(_this).removeAttr("disabled");
                    alert(data.message);
                },
                error: function (xhr, errorType, error) {
                    console.log(error);
                }
            });
        });
    });
    var audio = document.getElementById("audio");
    function bindPlay() {
        $(".audio_wrp").on("click", function () {
            $(".txt_audio").hide();
            if (audio.paused == false) {
                audio.pause();
            }
            var _this = this;
            if ($(_this).find(".audio_play_area").attr("type") == "mp3") {
                //$("#audio").attr("src", $(_this).find(".audio_play_area").attr("data"));
                audio.src = $(_this).find(".audio_play_area").attr("data");
                audio.play();
                $(_this).find(".txt_audio").show();
            } else {
                wx.downloadVoice({
                    serverId: $(_this).find(".audio_play_area").attr("data"), // 需要下载的音频的服务器端ID，由uploadVoice接口获得
                    isShowProgressTips: 1, // 默认为1，显示进度提示
                    success: function (res) {
                        console.log(res);
                        var localId = res.localId; // 返回音频的本地ID
                        $(_this).find(".txt_audio").show();
                        wx.playVoice({
                            localId: localId // 需要播放的音频的本地ID，由stopRecord接口获得
                        });
                    }
                });
            }
        });
    }

    function uploadVoice() {
        //调用微信的上传录音接口把本地录音先上传到微信的服务器
        //不过，微信只保留3天，而我们需要长期保存，我们需要把资源从微信服务器下载到自己的服务器
        wx.uploadVoice({
            localId: voice.localId, // 需要上传的音频的本地ID，由stopRecord接口获得
            isShowProgressTips: 1, // 默认为1，显示进度提示
            success: function (res) {
                var html = '<span class="audio_wrp"><span class="audio_play_area" data="' + res.serverId + '"><img src="/img/voice.png" class="pic_audio"></span><span class="txt_audio">播放中...</span></span>';
                $(".voice-list").append(html);
                bindPlay();
                //把录音在微信服务器上的id（res.serverId）发送到自己的服务器供下载。
                //alert( JSON.stringify(res));
                $.ajax({
                    url: '/upload',
                    type: 'post',
                    data: res,
                    dataType: "json",
                    success: function (data) {
                        //alert('文件已经保存到七牛的服务器');//这回，我使用七牛存储
                    },
                    error: function (xhr, errorType, error) {
                        console.log(error);
                    }
                });
            }
        });
    }
</script>
</html>
