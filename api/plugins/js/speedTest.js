/* PHP MAILER JS FILE */
/*
$(document).on('click', '#PHPMAILER-settings-button', function() {
	var post = {
        plugin:'PHPMailer/settings/get', // used for switch case in your API call
        api:'api/?v1/plugin', // API Endpoint will always be this for custom plugin API calls
        name:$(this).attr('data-plugin-name'),
        configName:$(this).attr('data-config-name'),
        messageTitle:'', // Send succees message title (top line)
        messageBody:'Disabled '+$(this).attr('data-plugin-name'), // Send succees message body (bottom line)
        error:'Organizr Function: API Connection Failed' // conole error message
    };
	var callbacks = $.Callbacks(); // init callbacks var
    //callbacks.add(  ); // add function to callback to be fired after API call
    //settingsAPI(post,callbacks); // exec API call
    //ajaxloader(".content-wrap","in");
    //setTimeout(function(){ buildPlugins();ajaxloader(); }, 3000);
});
*/
function clamp(num, min, max) {
  return num <= min ? min : num >= max ? max : num;
}
function I(id){return document.getElementById(id);}
var w=null; //speedtest worker
function startStop(){
	if(w!=null){
		//speedtest is running, abort
		w.postMessage('abort');
		w=null;
		$('#speedTestButtonText').text('Start');
		initUI();
	}else{
		//test is not running, begin
		w=new Worker('api/plugins/misc/speedTest/speedtest_worker.min.js');
		w.postMessage('start'); //Add optional parameters as a JSON object to this command
		$('#speedTestButtonText').text('Running');
		w.onmessage=function(e){
			var data=e.data.split(';');
			var status=Number(data[0]);
			if(status>=4){
				//test completed
				$('#speedTestButtonText').text('Re-Test');
				w=null;
			}
			var downloadText = Math.ceil((data[1]/1000)*1000);
			var downloadPercent = clamp(Math.ceil(((data[1]/1000)*100)/5)*5, 0,100);
			var uploadText = Math.ceil((data[2]/1000)*1000);
			var uploadPercent = clamp(Math.ceil(((data[2]/1000)*100)/5)*5, 0,100);
			I("ip").textContent=data[4];
			I("dlText").textContent=(status==1&&data[1]==0)?"...":Math.ceil(data[1]);
			I("ulText").textContent=(status==3&&data[2]==0)?"...":Math.ceil(data[2]);
			I("pingText").textContent=Math.ceil(data[3]);
			I("jitText").textContent=Math.ceil(data[5]);
			var prog=(Number(data[6])*2+Number(data[7])*2+Number(data[8]))/5;
			I("progress").style.width=(100*prog)+"%";
			$('#downloadPercent').attr('class', 'css-bar css-bar-'+downloadPercent+' css-bar-lg css-bar-default').attr('data-label', downloadText+'Mbps');
			$('#uploadPercent').attr('class', 'css-bar css-bar-'+uploadPercent+' css-bar-lg css-bar-warning pull-right').attr('data-label', uploadText+'Mbps');
		};
	}
}
//poll the status from the worker every 200ms (this will also update the UI)
setInterval(function(){
	if(w) w.postMessage('status');
},200);
//function to (re)initialize UI
function initUI(){
	I("dlText").textContent="";
	I("ulText").textContent="";
	I("pingText").textContent="";
	I("jitText").textContent="";
	I("ip").textContent="";
	I("progress").style.width="";
	$('#downloadPercent').attr('class', 'css-bar css-bar-0 css-bar-lg css-bar-default').attr('data-label', '0Mbps');
	$('#uploadPercent').attr('class', 'css-bar css-bar-0 css-bar-lg css-bar-warning pull-right').attr('data-label', '0Mbps');
}
// FUNCTIONS
speedTestLaunch()
function speedTestLaunch(){
    if(typeof activeInfo == 'undefined'){
        setTimeout(function () {
            speedTestLaunch();
        }, 1000);
    }else{
        if(activeInfo.plugins["SPEEDTEST-enabled"] == true){
            if (activeInfo.user.groupID <= activeInfo.plugins.includes["SPEEDTEST-Auth-include"]) {
                var menuList = `<li><a class="inline-popups speedTestModal" href="#speedtest-area" data-effect="mfp-zoom-out"><i class="fa fa-rocket fa-fw"></i> <span lang="en">Test Server Speed</span></a></li>`;
				var htmlDOM = `
		    	<div id="speedtest-area" class="white-popup mfp-with-anim mfp-hide">
		    		<div class="col-md-4 col-md-offset-4">
						<div class="panel bg-org panel-info">
							<div class="panel-heading">
								<span lang="en">Test Speed to Server</span>
								<button id="startStopBtn" onclick="startStop()" class="btn btn-info waves-effect waves-light pull-right"><span lang="en" id="speedTestButtonText">Start</span> <i class="fa fa-rocket m-l-5"></i></button>
							</div>
							<div class="panel-body">
								<div id="test">
									<div class="row hidden-xs">
										<div class="col-md-6 col-xs-6"><div id="downloadPercent" data-label="0Mbps" style="font-size: 15px;"></div></div>
										<div class="col-md-6 col-xs-6"><div id="uploadPercent" data-label="0Mbps" style="font-size: 15px;"></div></div>
									</div>
									<div class="progress progress-sm">
										<div id="progress" class="progress-bar progress-bar-info active progress-bar-striped" role="progressbar" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
											<span class="sr-only">0% Complete (success)</span>
										</div>
									</div>
				                    <div class="white-box m-b-0">
				                        <div class="user-btm-box">
				                            <div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center">
				                                <p class="text-success"><i class="ti-download fa-2x"></i></p>
				                                <h1 id="dlText"></h1>
												<h4 class="">Mbps</h4>
											</div>
				                            <div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center">
				                                <p class="text-warning"><i class="ti-upload fa-2x"></i></p>
				                                <h1 id="ulText"></h1>
												<h4 class="">Mbps</h4>
											</div>
				                            <div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center">
				                                <p class="text-purple"><i class="ti-direction-alt fa-2x"></i></p>
				                                <h1 id="pingText"></h1>
												<h4 class="">ms</h4>
											</div>
				                            <div class="col-md-3 col-xs-6 p-l-0 p-r-0 text-center">
				                                <p class="text-info"><i class="ti-pulse fa-2x"></i></p>
				                                <h1 id="jitText"></h1>
												<h4 class="">ms</h4>
											</div>
				                        </div>
				                    </div>
								</div>
								<script type="text/javascript">initUI();</script>
							</div>
							<div class="panel-footer"> IP Address: <span id="ip"></span> </div>
						</div>
		    		</div>
		    	</div>
		    	`;
				$('.append-menu').after(menuList);
	            $('.organizr-area').after(htmlDOM);
	            pageLoad();
			}
        }
    }
}

// CHANGE CUSTOMIZE Options
$(document).on('change asColorPicker::close', '#SPEEDTEST-settings-page1 :input', function(e) {
    var input = $(this);
    switch ($(this).attr('type')) {
        case 'switch':
        case 'checkbox':
            var value = $(this).prop("checked") ? true : false;
            break;
        default:
            var value = $(this).val().toString();
    }
	var post = {
        api:'api/?v1/update/config',
        name:$(this).attr("name"),
        type:$(this).attr("data-type"),
        value:value,
        messageTitle:'',
        messageBody:'Updated Value for '+$(this).parent().parent().find('label').text(),
        error:'Organizr Function: API Connection Failed'
    };
	var callbacks = $.Callbacks();
    //callbacks.add( buildCustomizeAppearance );
    settingsAPI(post,callbacks);
    //disable button then renable
    $('#SPEEDTEST-settings-page :input').prop('disabled', 'true');
    setTimeout(
        function(){
            $('#SPEEDTEST-settings-page :input').prop('disabled', null);
            input.emulateTab();
        },
        2000
    );

});
$(document).on('click', '#SPEEDTEST-settings-button', function() {
    var post = {
        plugin:'SpeedTest/settings/get', // used for switch case in your API call
    };
    ajaxloader(".content-wrap","in");
    organizrAPI('POST','api/?v1/plugin',post).success(function(data) {
        var response = JSON.parse(data);
        $('#SPEEDTEST-settings-items').html(buildFormGroup(response.data));
    }).fail(function(xhr) {
        console.error("Organizr Function: API Connection Failed");
    });
    ajaxloader();
});
