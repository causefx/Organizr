<?php
if(file_exists('config'.DIRECTORY_SEPARATOR.'config.php')){
$pageLockScreen = '
<script>
</script>
<section id="lockScreen" class="lock-screen" oncontextmenu="return false;">
  <div class="login-box">
    <div class="white-box">
      <form class="form-horizontal form-material" id="form-lockscreen">
        <div class="form-group">
          <div class="col-xs-12 text-center">
            <div class="user-thumb text-center"> <img alt="thumbnail" class="img-circle" width="100" src="'.$GLOBALS['organizrUser']['image'].'">
              <h3>'.$GLOBALS['organizrUser']['username'].'</h3>
            </div>
          </div>
        </div>
        <div class="form-group ">
          <div class="col-xs-12">
            <input name="username" class="form-control" type="hidden" value="'.$GLOBALS['organizrUser']['username'].'">
            <input name="password" class="form-control" type="password" required="" placeholder="password" lang="en">
          </div>
        </div>
        <div class="form-group text-center">
          <div class="col-xs-12">
            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light" type="submit" lang="en">Unlock</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
';
}
