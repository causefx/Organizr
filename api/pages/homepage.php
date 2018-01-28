<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageHomepage = '
<script>
$("#owl-demo2").owlCarousel({
    margin:20,
    nav:true,
    autoplay:true,
    responsive:{
        0:{
            items:1
        },
        480:{
            items:2
        },
        700:{
            items:4
        },
        1000:{
            items:3
        },
        1100:{
            items:5
        }
    }
})
</script>
<link href="plugins/bower_components/owl.carousel/owl.carousel.min.css" rel="stylesheet" type="text/css" />
<link href="plugins/bower_components/owl.carousel/owl.theme.default.css" rel="stylesheet" type="text/css" />
<div class="container-fluid p-t-10" id="homepage-items">'.buildHomepage().'</div>
<!-- /.container-fluid -->
';
}
