<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <title>医者脉连</title>
    <?php require_once(dirname(__FILE__) . '/page_parts/common/base_url.php');?>
<!--    --><?php //require_once(dirname(__FILE__).'/page_parts/common/css.php');?>
    <link rel="stylesheet" href="css/core/core.css?v=1.0.17">

    <style>
        #page-download {
            position: absolute;
            background-color: #96b57c;
            color: #fff;
            text-align: center;
            border-radius: 4px;
        }
    </style>

</head>
<body class="cf-invisible">

<div class="cf-wrap cf-wrap-no-bottom" data-cf-layout='{"height": 1684}'>
    <img src="img/YMPatientShareBkg.jpg" class="cf-img-bkg">
    <a href="https://itunes.apple.com/cn/app/yi-zhe-mai-lian/id1187938345" id="page-download" class="cf-row"
         data-cf-layout='{
         "top": 1080,
         "left": 50,
         "width": 650,
         "height": 82,
         "fontSize": 40,
         "lineHeight": 82
         }'
    >下载"医者脉连-看专家"</a>

</div>

<?php require_once(dirname(__FILE__) . '/page_parts/common/js.php');?>
<script src="js/lib/jquery.slides.min.js"></script>
<script src="js/lib/jquery.exif.js"></script>
<script src="js/lib/MegaPixImage.js"></script>
<script src="js/lib/common.js"></script>
<script src="js/lib/AlloyImage/alloyimage.js"></script>
<!--1.0.18-->
<script src="js/page/index.js?v=0.0.3"></script>

<script>

    $(function(){
        console.log("got here");
        g_jq_dom.$body.removeClass("cf-invisible");
        $("#page-download").on(g_event.touchend, function () {
            if(is_weixn()) {
                alert("请点击右上角，在系统浏览器中打开此链接。")
            }
        });
    });

    function is_weixn(){
        var ua = navigator.userAgent.toLowerCase();
        return (ua.match(/MicroMessenger/i) == "micromessenger")
    }
</script>
</body>
</html>
