<?php
if (file_exists('config' . DIRECTORY_SEPARATOR . 'config.php')) {
	$hideOrganizrLogin = (checkoAuth()) ? 'collapse' : 'collapse in';
	$hideOrganizrLoginHeader = (checkoAuthOnly()) ? 'hidden' : '';
	$hideOrganizrLoginHeader2 = (checkoAuth()) ? '' : 'hidden';
	$pageLogin = '
<script>
if(activeInfo.settings.login.rememberMe){
	$(\'#checkbox-login\').prop(\'checked\',true);
}
</script>
<section id="wrapper" class="login-register">
  <div class="login-box login-sidebar animated slideInRight">
    <div class="white-box">
      <form class="form-horizontal" id="loginform" onsubmit="return false;">
      	<input id="login-attempts" class="form-control" name="loginAttempts" type="hidden">
        <a href="javascript:void(0)" class="text-center db visible-xs" id="login-logo">' . logoOrText() . '</a>
        <div id="oAuth-div" class="form-group hidden">
          <div class="col-xs-12">
            <div class="panel panel-success animated tada">
                <div class="panel-heading">oAuth Successful - Please wait...</div>
            </div>
          </div>
        </div>
		<div id="tfa-div" class="form-group hidden">
          <div class="col-xs-12">
            <div class="panel panel-warning animated tada">
                <div class="panel-heading"> 2FA
                    <div class="pull-right"><a href="#" data-perform="panel-collapse"><i class="ti-minus"></i></a> <a href="#" data-perform="panel-dismiss"><i class="ti-close"></i></a> </div>
                </div>
                <div class="panel-wrapper collapse in" aria-expanded="true">
                    <div class="panel-body">
	                    <div class="input-group" style="width: 100%;">
	                        <div class="input-group-addon hidden-xs"><i class="ti-lock"></i></div>
	                        <input type="text" class="form-control tfa-input" name="tfaCode" placeholder="Code" autocomplete="off" autocorrect="off" autocapitalize="off" maxlength="6" spellcheck="false" autofocus="">
	                    </div>
	                    <button class="btn btn-warning btn-lg btn-block text-uppercase waves-effect waves-light login-button m-t-10" type="submit" lang="en">Login</button>
                    </div>
                </div>
            </div>
          </div>
        </div>
        <div class="panel-group" id="login-panels" data-type="accordion" aria-multiselectable="true" role="tablist">
	        <!-- ORGANIZR LOGIN -->
	        <div class="panel">
	            <div class="panel-heading bg-org ' . $hideOrganizrLoginHeader . ' ' . $hideOrganizrLoginHeader2 . '" id="organizr-login-heading" role="tab">
	            	<a class="panel-title collapsed" data-toggle="collapse" href="#organizr-login-collapse" data-parent="#login-panels" aria-expanded="false" aria-controls="organizr-login-collapse">
                        <img class="lazyload loginTitle" data-src="plugins/images/organizr/logo-no-border.png"> &nbsp;
                        <span class="text-uppercase fw300" lang="en">Login with Organizr</span>
	            	</a>
	            	<div class="clearfix"></div>
	            </div>
	            <div class="panel-collapse ' . $hideOrganizrLogin . '" id="organizr-login-collapse" aria-labelledby="organizr-login-heading" role="tabpanel">
	                <div class="panel-body">
	                
	                	<div class="form-group">
				          <div class="col-xs-12">
				            <input id="login-username-Input" class="form-control" name="username" type="text" required="" placeholder="Username" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" lang="en" autofocus>
				          </div>
				        </div>
				        <div class="form-group">
				          <div class="col-xs-12">
				            <input id="login-password-Input" class="form-control" name="password" type="password" required="" placeholder="Password" lang="en">
				          </div>
				        </div>
				        <div class="form-group">
				          <div class="col-md-12">
				            <div class="checkbox checkbox-primary pull-left p-t-0 remember-me">
				              <input id="checkbox-login" name="remember" type="checkbox">
				              <label for="checkbox-login" lang="en">Remember Me</label>
				            </div>
				            </div>
				        </div>
				        <div class="form-group text-center m-t-20 m-b-0">
				          <div class="col-xs-12">
				            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light login-button" type="submit" lang="en">Login</button>
				          </div>
				        </div>
				        <div class="form-group m-b-0">
				          <div class="col-sm-12 text-center">
				            <input id="oAuth-Input" class="form-control" name="oAuth" type="hidden">
				            <input id="oAuthType-Input" class="form-control" name="oAuthType" type="hidden">
				            ' . showLogin() . '
				          </div>
				        </div>
	                </div>
	            </div>
	        </div>
	        <!-- END ORGANIZR LOGIN -->
        	<!-- PLEX OAUTH LOGIN -->
	        ' . showoAuth() . '
	        <!-- END PLEX OAUTH LOGIN -->
        </div>
      </form>
      <form class="form-horizontal form-material hidden" id="registerForm" onsubmit="return false;">
        <div class="form-group m-t-40">
          <div class="col-xs-12">
            <input class="form-control" type="text" name="registrationPassword" required="" placeholder="Registration Password" lang="en" autofocus>
          </div>
        </div>
        <div class="form-group">
          <div class="col-xs-12">
            <input class="form-control" name="username" type="text" required="" placeholder="Username" lang="en">
          </div>
        </div>
        <div class="form-group">
          <div class="col-xs-12">
            <input class="form-control" name="email" type="text" required="" placeholder="Email" lang="en">
          </div>
        </div>
        <div class="form-group">
          <div class="col-xs-12">
            <input class="form-control" name="password" type="password" required="" placeholder="Password" lang="en">
          </div>
        </div>
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button class="btn btn-info btn-lg btn-block text-uppercase waves-effect waves-light register-button" type="submit" lang="en">Register</button>
          </div>
        </div>
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button id="leave-registration" class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" type="button" lang="en">Go Back</button>
          </div>
        </div>
      </form>
      <form class="form-horizontal" id="recoverform" onsubmit="return false;">
        <div class="form-group ">
          <div class="col-xs-12">
            <h3 lang="en">Recover Password</h3>
            <p class="text-muted" lang="en">Enter your Email and instructions will be sent to you!</p>
          </div>
        </div>
        <div class="form-group ">
          <div class="col-xs-12">
            <input id="recover-input" class="form-control" name="email" type="text" placeholder="Email" lang="en" required>
          </div>
        </div>
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light reset-button" type="submit" lang="en">Reset</button>
          </div>
        </div>
        <div class="form-group text-center m-t-20">
          <div class="col-xs-12">
            <button id="leave-recover" class="btn btn-primary btn-lg btn-block text-uppercase waves-effect waves-light" type="button" lang="en">Go Back</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
';
}
