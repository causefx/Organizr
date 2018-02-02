<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageHomepage = '
<script>
</script>
<link href="plugins/bower_components/owl.carousel/owl.carousel.min.css" rel="stylesheet" type="text/css" />
<link href="plugins/bower_components/owl.carousel/owl.theme.default.css" rel="stylesheet" type="text/css" />
<div class="container-fluid p-t-10" id="homepage-items">
    '.buildHomepage().'
</div>
<!-- /.container-fluid -->
';
}
