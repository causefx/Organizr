<?php
// PLUGIN INFORMATION
$GLOBALS['plugins']['Bookmark'] = array( // Plugin Name
	'name' => 'Bookmark', // Plugin Name
	'author' => 'leet1994', // Who wrote the plugin
	'category' => 'Utilities', // One to Two Word Description
	'link' => '', // Link to plugin info
	'license' => 'personal,business', // License Type use , for multiple
	'idPrefix' => 'BOOKMARK', // html element id prefix
	'configPrefix' => 'BOOKMARK', // config file prefix for array items without the hyphen
	'dbPrefix' => 'BOOKMARK', // db prefix
	'version' => '0.1.0', // SemVer of plugin
	'image' => 'api/plugins/bookmark/logo.png', // 1:1 non transparent image for plugin
	'settings' => true, // does plugin need a settings modal?
	'bind' => true, // use default bind to make settings page - true or false
	'api' => 'api/v2/plugins/bookmark/settings', // api route for settings page
	'homepage' => false // Is plugin for use on homepage? true or false
);

// Logo image under Public Domain from https://openclipart.org/detail/182527/open-book
class Bookmark extends Organizr
{
	public function writeLog($type = 'error', $message = null, $username = null)
	{
		parent::writeLog($type, "Plugin 'Bookmark': " . $message, $username);
	}

	public function _bookmarkGetOrganizrTabInfo()
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM tabs',
					'WHERE url = ?',
					'api/v2/plugins/bookmark/page'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _bookmarkGetOrganizrTabGroupId()
	{
		$tab = $this->_bookmarkGetOrganizrTabInfo();
		if ($tab) {
			return $tab['group_id'];
		} else {
			return 999;
		}
	}

	public function _checkRequest($request)
	{
		$result = false;
		if ($this->config['BOOKMARK-enabled'] && $this->hasDB()) {
			if (!$this->_checkDatabaseTablesExist()) {
				$this->_createDatabaseTables();
			}
			$result = true;
		}
		return $result;
	}

	public function _checkDatabaseTablesExist()
	{
		if ($this->config['driver'] == 'sqlite3') {
			$queryCategories = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'BOOKMARK-categories'"];
			$queryTabs = ["SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' AND `name` = 'BOOKMARK-tabs'"];
		} else {
			$queryCategories = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_TYPE LIKE "BASE TABLE" AND TABLE_NAME = %s', (string)$this->config['dbName'], 'BOOKMARK-categories'];
			$queryTabs = ['SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_TYPE LIKE "BASE TABLE" AND TABLE_NAME = %s', (string)$this->config['dbName'], 'BOOKMARK-categories'];
		}
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => $queryCategories,
				'key' => 'BOOKMARK-categories'
			),
			array(
				'function' => 'fetchSingle',
				'query' => $queryTabs,
				'key' => 'BOOKMARK-tabs'
			),
		];
		$data = $this->processQueries($response);
		return ($data["BOOKMARK-categories"] != false && $data["BOOKMARK-tabs"] != false);
	}

	protected function _createDatabaseTables()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `BOOKMARK-categories` (
					`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
					`order`	INTEGER,
					`category`	TEXT UNIQUE,
					`category_id`	INTEGER,
					`default` INTEGER
				);'
			),
			array(
				'function' => 'query',
				'query' => 'CREATE TABLE `BOOKMARK-tabs` (
					`id`	INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
					`order`	INTEGER,
					`category_id`	INTEGER,
					`name`	TEXT,
					`url`	TEXT,
					`enabled`	INTEGER,
					`group_id`	INTEGER,
					`image`	TEXT,
					`background_color` TEXT,
					`text_color` TEXT
				);'
			)
		];
		$this->processQueries($response);
	}

	public function _getSettings()
	{
		return array(
			'custom' => '
				<div class="row">
					<div class="col-lg-6 col-sm-12 col-md-6">
						<div class="white-box">
							<h3 class="box-title" lang="en">Automatic Setup Tasks</h3>
							<ul class="feeds">
								<li class="bookmark-check-tab">
									<div class="bg-info">
										<i class="sticon ti-layout-tab-v text-white"></i>
									</div>
									<small lang="en">Checking for Bookmark tab...</small>
									<span class="text-muted result"><i class="fa fa-spin fa-refresh"></i></span>
								</li>
								<li class="bookmark-check-category">
									<div class="bg-success">
										<i class="ti-layout-list-thumb text-white"></i>
									</div>
									<small lang="en">Checking for bookmark default category...</small>
									<span class="text-muted result"><i class="fa fa-spin fa-refresh"></i></span>
								</li>
							</ul>
						</div>
					</div>
					<div class="col-lg-6 col-sm-12 col-md-6">
						<div class="panel panel-info">
							<div class="panel-heading">
								<span lang="en">Notice</span>
							</div>
							<div class="panel-wrapper collapse in" aria-expanded="true">
								<div class="panel-body">
									<ul class="list-icons">
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Add tab that points to <i>api/v2/plugins/bookmark/page</i> and set it\'s type to <i>Organizr</i>.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Create Bookmark categories in the new area in <i>Tab Editor</i>.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Create Bookmark tabs in the new area in <i>Tab Editor</i>.</span></li>
										<li><i class="fa fa-chevron-right text-info"></i> <span lang="en">Open your custom Bookmark page via menu.</span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			'
		);
	}

	public function _getPage()
	{
		$bookmarks = '<div id="BOOKMARK-wrapper">';
		foreach ($this->_getAllCategories() as $category) {
			$tabs = $this->_getRelevantTabsForCategory($category['category_id']);
			if (count($tabs) == 0) continue;
			$bookmarks .= '<div class="BOOKMARK-category">
				<div class="BOOKMARK-category-title">
					' . $category['category'] . '
				</div>
				<div class="BOOKMARK-category-content">';
			foreach ($tabs as $tab) {
				$bookmarks .= '<a href="' . $tab['url'] . '" target="_SELF">
					<div class="BOOKMARK-tab"
						style="border-color: ' . $this->adjustBrightness($tab['background_color'], 0.3) . '; background: linear-gradient(90deg, ' . $this->adjustBrightness($tab['background_color'], -0.3) . ' 0%, ' . $tab['background_color'] . ' 70%, ' . $this->adjustBrightness($tab['background_color'], 0.1) . ' 100%);">
						<span class="BOOKMARK-tab-image">' . $this->_iconPrefix($tab['image']) . '</span>
						<span class="BOOKMARK-tab-title" style="color: ' . $tab['text_color'] . ';">' . $tab['name'] . '</span>
					</div>
				</a>';
			}
			$bookmarks .= '</div></div>';
		}
		$bookmarks .= '</div>';
		return $bookmarks;
	}

	protected function _iconPrefix($source)
	{
		$tabIcon = explode("::", $source);
		$icons = array(
			"materialize" => "mdi mdi-",
			"fontawesome" => "fa fa-",
			"themify" => "ti-",
			"simpleline" => "icon-",
			"weathericon" => "wi wi-",
			"alphanumeric" => "fa-fw",
		);
		if (is_array($tabIcon) && count($tabIcon) == 2) {
			if ($tabIcon[0] !== 'url' && $tabIcon[0] !== 'alphanumeric') {
				return '<i class="' . $icons[$tabIcon[0]] . $tabIcon[1] . '"></i>';
			} else if ($tabIcon[0] == 'alphanumeric') {
				return '<i>' . $tabIcon[1] . '</i>';
			} else {
				return '<img src="' . $tabIcon[1] . '" alt="tabIcon" />';
			}
		} else {
			return '<img src="' . $source . '" alt="tabIcon" />';
		}
	}

	protected function _getAllCategories()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM `BOOKMARK-categories` ORDER BY `order` ASC'
			)
		];
		return $this->processQueries($response);
	}

	protected function _getRelevantTabsForCategory($category_id)
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					"SELECT * FROM `BOOKMARK-tabs` WHERE `enabled`='1' AND `category_id`=? AND `group_id`>=? ORDER BY `order` ASC",
					$category_id,
					$this->getUserLevel()
				)
			)
		];
		return $this->processQueries($response);
	}

	public function _getTabs()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM `BOOKMARK-tabs` ORDER BY `order` ASC',
				'key' => 'tabs'
			),
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM `BOOKMARK-categories` ORDER BY `order` ASC',
				'key' => 'categories'
			),
			array(
				'function' => 'fetchAll',
				'query' => 'SELECT * FROM `groups` ORDER BY `group_id` ASC',
				'key' => 'groups'
			)
		];
		return $this->processQueries($response);
	}

	// Tabs
	public function _getSettingsTabEditorBookmarkTabsPage()
	{
		$iconSelectors = '
			$(".bookmarkTabIconIconList").select2({
				ajax: {
					url: \'api/v2/icon\',
					data: function (params) {
						var query = {
							search: params.term,
							page: params.page || 1
						}
						return query;
					},
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
							results: data.response.data.results,
							pagination: {
								more: (params.page * 20) < data.response.data.total
							}
						};
					},
					//cache: true
				},
				placeholder: \'Search for an icon\',
				templateResult: formatIcon,
				templateSelection: formatIcon
			});

			$(".bookmarkTabIconImageList").select2({
				 ajax: {
					url: \'api/v2/image/select\',
					data: function (params) {
						var query = {
							search: params.term,
							page: params.page || 1
						}
						return query;
					},
					processResults: function (data, params) {
						params.page = params.page || 1;
						return {
							results: data.response.data.results,
							pagination: {
								more: (params.page * 20) < data.response.data.total
							}
						};
					},
					//cache: true
				},
				placeholder: \'Search for an image\',
				templateResult: formatImage,
				templateSelection: formatImage
			});
		';
		return '
		<script>
		buildBookmarkTabEditor();
		!function(a){function f(a,b){if(!(a.originalEvent.touches.length>1)){a.preventDefault();var c=a.originalEvent.changedTouches[0],d=document.createEvent("MouseEvents");d.initMouseEvent(b,!0,!0,window,1,c.screenX,c.screenY,c.clientX,c.clientY,!1,!1,!1,!1,0,null),a.target.dispatchEvent(d)}}if(a.support.touch="ontouchend"in document,a.support.touch){var e,b=a.ui.mouse.prototype,c=b._mouseInit,d=b._mouseDestroy;b._touchStart=function(a){var b=this;!e&&b._mouseCapture(a.originalEvent.changedTouches[0])&&(e=!0,b._touchMoved=!1,f(a,"mouseover"),f(a,"mousemove"),f(a,"mousedown"))},b._touchMove=function(a){e&&(this._touchMoved=!0,f(a,"mousemove"))},b._touchEnd=function(a){e&&(f(a,"mouseup"),f(a,"mouseout"),this._touchMoved||f(a,"click"),e=!1)},b._mouseInit=function(){var b=this;b.element.bind({touchstart:a.proxy(b,"_touchStart"),touchmove:a.proxy(b,"_touchMove"),touchend:a.proxy(b,"_touchEnd")}),c.call(b)},b._mouseDestroy=function(){var b=this;b.element.unbind({touchstart:a.proxy(b,"_touchStart"),touchmove:a.proxy(b,"_touchMove"),touchend:a.proxy(b,"_touchEnd")}),d.call(b)}}}(jQuery);
		$( \'#bookmarkTabEditorTable\' ).sortable({
			stop: function () {
				$(\'input.order\').each(function(idx) {
					$(this).val(idx + 1);
				});
				var newTabs = $( "#submit-bookmark-tabs-form" ).serializeToJSON();
				newBookmarkTabsGlobal = newTabs;
				$(\'.saveBookmarkTabOrderButton\').removeClass(\'hidden\');
				//submitTabOrder(newTabs);
			}
		});
		$( \'#bookmarkTabEditorTable\' ).disableSelection();
		' . $iconSelectors . '
		</script>
		<div class="panel bg-org panel-info">
			<div class="panel-heading">
				<span lang="en">Bookmark Tab Editor</span>
				<button type="button" class="btn btn-info btn-circle pull-right popup-with-form m-r-5" href="#new-bookmark-tab-form" onclick="newBookmarkTabForm()" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
				<button onclick="submitBookmarkTabOrder(newBookmarkTabsGlobal)" class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right animated loop-animation rubberBand m-r-20 saveBookmarkTabOrderButton hidden" type="button"><span class="btn-label"><i class="fa fa-save"></i></span><span lang="en">Save Tab Order</span></button>
			</div>
			<div class="table-responsive">
				<form id="submit-bookmark-tabs-form" onsubmit="return false;">
					<table class="table table-hover manage-u-table">
						<thead>
							<tr>
								<th width="70" class="text-center">#</th>
								<th lang="en">NAME</th>
								<th lang="en">CATEGORY</th>
								<th lang="en">GROUP</th>
								<th lang="en" style="text-align:center">ACTIVE</th>
								<th lang="en" style="text-align:center">EDIT</th>
								<th lang="en" style="text-align:center">DELETE</th>
							</tr>
						</thead>
						<tbody id="bookmarkTabEditorTable">
							<td class="text-center" colspan="12"><i class="fa fa-spin fa-spinner"></i></td>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<form id="new-bookmark-tab-form" class="mfp-hide white-popup-block mfp-with-anim">
			<h1 lang="en">Add New Tab</h1>
			<fieldset style="border:0;">
				<div class="form-group">
					<label class="control-label" for="new-bookmark-tab-form-inputName" lang="en">Tab Name</label>
					<input type="text" class="form-control" id="new-bookmark-tab-form-inputName" name="name" required="" autofocus>
				</div>
				<div class="form-group">
					<label class="control-label" for="new-bookmark-tab-form-inputURL" lang="en">Tab URL</label>
					<input type="text" class="form-control" id="new-bookmark-tab-form-inputURL" name="url"  required="">
				</div>
				<div class="row">
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-tab-form-chooseImage" lang="en">Choose Image</label>
						<select class="form-control bookmarkTabIconImageList" id="new-bookmark-tab-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-tab-form-chooseIcon" lang="en">Choose Icon</label>
						<select class="form-control bookmarkTabIconIconList" id="new-bookmark-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-tab-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
						<button id="new-bookmark-tab-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'new-bookmark-tab-form-inputImageNew\');" type="button">
							<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
						</button>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label" for="new-bookmark-tab-form-inputImage" lang="en">Tab Image</label>
					<input type="text" class="form-control" id="new-bookmark-tab-form-inputImage" name="image" required="">
				</div>
				<div class="row">
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-tab-form-inputBackgroundColor" lang="en">Background Color</label>
						<input type="text" class="form-control bookmark-pick-a-color" id="new-bookmark-tab-form-inputBackgroundColor" name="background_color" required="" value="#fff">
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-tab-form-inputTextColor" lang="en">Text Color</label>
						<input type="text" class="form-control bookmark-pick-a-color" id="new-bookmark-tab-form-inputTextColor" name="text_color" required="" value="#000">
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="new-bookmark-preview" lang="en">Preview</label>
						<div id="new-bookmark-preview"></div>
					</div>
				</div>
			</fieldset>
			<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewBookmarkTab" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Tab</span></button>
			<div class="clearfix"></div>
		</form>
		<form id="edit-bookmark-tab-form" class="mfp-hide white-popup-block mfp-with-anim">
			<input type="hidden" name="id" value="x">
			<span class="hidden" id="originalBookmarkTabName"></span>
			<h1 lang="en">Edit Tab</h1>
			<fieldset style="border:0;">
				<div class="form-group">
					<label class="control-label" for="edit-bookmark-tab-form-inputName" lang="en">Tab Name</label>
					<input type="text" class="form-control" id="edit-bookmark-tab-form-inputName" name="name" required="" autofocus>
				</div>
				<div class="form-group">
					<label class="control-label" for="edit-bookmark-tab-form-inputURL" lang="en">Tab URL</label>
					<input type="text" class="form-control" id="edit-bookmark-tab-form-inputURL" name="url"  required="">
				</div>
				<div class="row">
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-tab-form-chooseImage" lang="en">Choose Image</label>
						<select class="form-control bookmarkTabIconImageList" id="edit-bookmark-tab-form-chooseImage" name="chooseImage"><option lang="en">Select or type Image</option></select>
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-tab-form-chooseIcon" lang="en">Choose Icon</label>
						<select class="form-control bookmarkTabIconIconList" id="edit-bookmark-tab-form-chooseIcon" name="chooseIcon"><option lang="en">Select or type Icon</option></select>
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-tab-form-chooseBlackberry" lang="en">Choose Blackberry Theme Icon</label>
						<button id="edit-bookmark-tab-form-chooseBlackberry" class="btn btn-xs btn-primary waves-effect waves-light form-control" onclick="showBlackberryThemes(\'edit-bookmark-tab-form-inputImage\');" type="button">
							<i class="fa fa-search"></i>&nbsp; <span lang="en">Choose</span>
						</button>
					</div>
				</div>
				<div class="form-group">
					<label class="control-label" for="edit-bookmark-tab-form-inputImage" lang="en">Tab Image</label>
					<input type="text" class="form-control" id="edit-bookmark-tab-form-inputImage" name="image"  required="">
				</div>
				<div class="row">
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-tab-form-inputBackgroundColor" lang="en">Background Color</label>
						<input type="text" class="form-control bookmark-pick-a-color" id="edit-bookmark-tab-form-inputBackgroundColor" name="background_color" required="">
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-tab-form-inputTextColor" lang="en">Text Color</label>
						<input type="text" class="form-control bookmark-pick-a-color" id="edit-bookmark-tab-form-inputTextColor" name="text_color" required="">
					</div>
					<div class="form-group col-lg-4">
						<label class="control-label" for="edit-bookmark-preview" lang="en">Preview</label>
						<div id="edit-bookmark-preview"></div>
					</div>
				</div>
			</fieldset>
			<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editBookmarkTab" type="button"><span class="btn-label"><i class="fa fa-check"></i></span><span lang="en">Edit Tab</span></button>
			<div class="clearfix"></div>
		</form>
		';
	}

	public function _isBookmarkTabNameTaken($name, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM `BOOKMARK-tabs` WHERE `name` LIKE ? AND `id` != ?',
						$name,
						$id
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM `BOOKMARK-tabs` WHERE `name` LIKE ?',
						$name
					)
				),
			];
		}
		return $this->processQueries($response);
	}

	public function _getNextBookmarkTabOrder()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `order` from `BOOKMARK-tabs` ORDER BY `order` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _getBookmarkTabById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM `BOOKMARK-tabs` WHERE `id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _getTabByIdCheckUser($id)
	{
		$tabInfo = $this->_getBookmarkTabById($id);
		if ($tabInfo) {
			if ($this->qualifyRequest($tabInfo['group_id'], true)) {
				return $tabInfo;
			}
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}

	public function _deleteTab($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM `BOOKMARK-tabs` WHERE id = ?',
					$id
				)
			),
		];
		$tabInfo = $this->_getBookmarkTabById($id);
		if ($tabInfo) {
			$this->writeLog('success', 'Tab Delete Function -  Deleted Tab [' . $tabInfo['name'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'Tab deleted', 204);
			return $this->processQueries($response);
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}

	public function _addTab($array)
	{
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$array = $this->checkKeys($this->getTableColumnsFormatted('BOOKMARK-tabs'), $array);
		$array['group_id'] = ($array['group_id']) ?? $this->getDefaultGroupId();
		$array['category_id'] = ($array['category_id']) ?? $this->_getDefaultBookmarkCategoryId();
		$array['enabled'] = ($array['enabled']) ?? 0;
		$array['order'] = ($array['order']) ?? $this->_getNextBookmarkTabOrder() + 1;
		if (array_key_exists('name', $array)) {
			if ($this->_isBookmarkTabNameTaken($array['name'])) {
				$this->setAPIResponse('error', 'Tab name: ' . $array['name'] . ' is already taken', 409);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Tab name was not supplied', 422);
			return false;
		}
		if (!array_key_exists('url', $array)) {
			$this->setAPIResponse('error', 'Tab url was not supplied', 422);
			return false;
		}
		if (!array_key_exists('image', $array)) {
			$this->setAPIResponse('error', 'Tab image was not supplied', 422);
			return false;
		}
		if (array_key_exists('background_color', $array)) {
			if (!$this->_checkColorHexCode($array['background_color'])) {
				$this->setAPIResponse('error', 'Tab background color is invalid', 422);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Tab background color was not supplied', 422);
			return false;
		}
		if (array_key_exists('text_color', $array)) {
			if (!$this->_checkColorHexCode($array['text_color'])) {
				$this->setAPIResponse('error', 'Tab text color is invalid', 422);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Tab text color was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [BOOKMARK-tabs]',
					$array
				)
			),
		];
		$this->setAPIResponse(null, 'Tab added');
		$this->writeLog('success', 'Tab Editor Function -  Added Tab for [' . $array['name'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}

	public function _updateTab($id, $array)
	{
		if (!$id || $id == '') {
			$this->setAPIResponse('error', 'id was not set', 422);
			return null;
		}
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$tabInfo = $this->_getBookmarkTabById($id);
		if ($tabInfo) {
			$array = $this->checkKeys($tabInfo, $array);
		} else {
			$this->setAPIResponse('error', 'No tab info found', 404);
			return false;
		}
		if (array_key_exists('name', $array)) {
			if ($this->_isBookmarkTabNameTaken($array['name'], $id)) {
				$this->setAPIResponse('error', 'Tab name: ' . $array['name'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('background_color', $array)) {
			if (!$this->_checkColorHexCode($array['background_color'])) {
				$this->setAPIResponse('error', 'Tab background color is invalid', 422);
				return false;
			}
		}
		if (array_key_exists('text_color', $array)) {
			if (!$this->_checkColorHexCode($array['text_color'])) {
				$this->setAPIResponse('error', 'Tab text color is invalid', 422);
				return false;
			}
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE `BOOKMARK-tabs` SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'Tab info updated');
		$this->writeLog('success', 'Tab Editor Function -  Edited Tab Info for [' . $tabInfo['name'] . ']', $this->user['username']);
		return $this->processQueries($response);
	}

	public function _updateTabOrder($array)
	{
		if (count($array) >= 1) {
			foreach ($array as $tab) {
				if (count($tab) !== 2) {
					$this->setAPIResponse('error', 'data is malformed', 422);
					break;
				}
				$id = $tab['id'] ?? null;
				$order = $tab['order'] ?? null;
				if ($id && $order) {
					$response = [
						array(
							'function' => 'query',
							'query' => array(
								'UPDATE `BOOKMARK-tabs` set `order` = ? WHERE `id` = ?',
								$order,
								$id
							)
						),
					];
					$this->processQueries($response);
					$this->setAPIResponse(null, 'Tab Order updated');
				} else {
					$this->setAPIResponse('error', 'data is malformed', 422);
				}
			}
		} else {
			$this->setAPIResponse('error', 'data is empty or not in array', 422);
			return false;
		}
	}

	// Categories
	public function _getSettingsTabEditorBookmarkCategoriesPage()
	{
		return '
	<script>
	buildBookmarkCategoryEditor();
	$( \'#bookmarkCategoryEditorTable\' ).sortable({
		stop: function () {
			var inputs = $(\'input.order\');
			var nbElems = inputs.length;
			inputs.each(function(idx) {
				$(this).val(idx + 1);
			});
			submitBookmarkCategoryOrder();
		}
	});
	</script>
	<div class="panel bg-org panel-info">
		<div class="panel-heading">
			<span lang="en">Bookmark Category Editor</span>
			<button type="button" class="btn btn-info btn-circle pull-right popup-with-form m-r-5" href="#new-bookmark-category-form" data-effect="mfp-3d-unfold"><i class="fa fa-plus"></i> </button>
		</div>
		<div class="table-responsive">
			<form id="submit-bookmark-categories-form" onsubmit="return false;">
				<table class="table table-hover manage-u-table">
					<thead>
						<tr>
							<th lang="en">NAME</th>
							<th lang="en" style="text-align:center">TABS</th>
							<th lang="en" style="text-align:center">DEFAULT</th>
							<th lang="en" style="text-align:center">EDIT</th>
							<th lang="en" style="text-align:center">DELETE</th>
						</tr>
					</thead>
					<tbody id="bookmarkCategoryEditorTable"><td class="text-center" colspan="6"><i class="fa fa-spin fa-spinner"></i></td></tbody>
				</table>
			</form>
		</div>
	</div>
	<form id="new-bookmark-category-form" class="mfp-hide white-popup-block mfp-with-anim">
		<h1 lang="en">Add New Bookmark Category</h1>
		<fieldset style="border:0;">
			<div class="form-group">
				<label class="control-label" for="new-bookmark-category-form-inputName" lang="en">Category Name</label>
				<input type="text" class="form-control" id="new-bookmark-category-form-inputName" name="category" required="" autofocus>
			</div>
		</fieldset>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none addNewBookmarkCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Add Category</span></button>
		<div class="clearfix"></div>
	</form>
	<form id="edit-bookmark-category-form" class="mfp-hide white-popup-block mfp-with-anim">
		<input type="hidden" name="id" value="">
		<h1 lang="en">Edit Category</h1>
		<fieldset style="border:0;">
			<div class="form-group">
				<label class="control-label" for="edit-bookmark-category-form-inputName" lang="en">Category Name</label>
				<input type="text" class="form-control" id="edit-bookmark-category-form-inputName" name="category" required="" autofocus>
			</div>
		</fieldset>
		<button class="btn btn-sm btn-info btn-rounded waves-effect waves-light pull-right row b-none editBookmarkCategory" type="button"><span class="btn-label"><i class="fa fa-plus"></i></span><span lang="en">Edit Category</span></button>
		<div class="clearfix"></div>
	</form>
	';
	}

	public function _getDefaultBookmarkCategoryId()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `category_id` FROM `BOOKMARK-categories` WHERE `default` = 1'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _getNextBookmarkCategoryOrder()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `order` from `BOOKMARK-categories` ORDER BY `order` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _getNextBookmarkCategoryId()
	{
		$response = [
			array(
				'function' => 'fetchSingle',
				'query' => array(
					'SELECT `category_id` from `BOOKMARK-categories` ORDER BY `category_id` DESC'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _isBookmarkCategoryNameTaken($name, $id = null)
	{
		if ($id) {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM `BOOKMARK-categories` WHERE `category` LIKE ? AND `id` != ?',
						$name,
						$id
					)
				),
			];
		} else {
			$response = [
				array(
					'function' => 'fetchAll',
					'query' => array(
						'SELECT * FROM `BOOKMARK-categories` WHERE `category` LIKE ?',
						$name
					)
				),
			];
		}
		return $this->processQueries($response);
	}

	public function _getBookmarkCategoryById($id)
	{
		$response = [
			array(
				'function' => 'fetch',
				'query' => array(
					'SELECT * FROM `BOOKMARK-categories` WHERE `id` = ?',
					$id
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _clearBookmarkCategoryDefault()
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE `BOOKMARK-categories` SET `default` = 0'
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _addCategory($array)
	{
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$array = $this->checkKeys($this->getTableColumnsFormatted('BOOKMARK-categories'), $array);
		$array['default'] = ($array['default']) ?? 0;
		$array['order'] = ($array['order']) ?? $this->_getNextBookmarkCategoryOrder() + 1;
		$array['category_id'] = ($array['category_id']) ?? $this->_getNextBookmarkCategoryId() + 1;
		if (array_key_exists('category', $array)) {
			if ($this->_isBookmarkCategoryNameTaken($array['category'])) {
				$this->setAPIResponse('error', 'Category name: ' . $array['category'] . ' is already taken', 409);
				return false;
			}
		} else {
			$this->setAPIResponse('error', 'Category name was not supplied', 422);
			return false;
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [BOOKMARK-categories]',
					$array
				)
			),
		];
		$this->setAPIResponse(null, 'Category added');
		$this->writeLog('success', 'Category Editor Function -  Added Category for [' . $array['category'] . ']', $this->user['username']);
		$result = $this->processQueries($response);
		$this->_correctDefaultCategory();
		return $result;
	}

	public function _updateCategory($id, $array)
	{
		if (!$id || $id == '') {
			$this->setAPIResponse('error', 'id was not set', 422);
			return null;
		}
		if (!$array) {
			$this->setAPIResponse('error', 'no data was sent', 422);
			return null;
		}
		$categoryInfo = $this->_getBookmarkCategoryById($id);
		if ($categoryInfo) {
			$array = $this->checkKeys($categoryInfo, $array);
		} else {
			$this->setAPIResponse('error', 'No category info found', 404);
			return false;
		}
		if (array_key_exists('category', $array)) {
			if ($this->_isBookmarkCategoryNameTaken($array['category'], $id)) {
				$this->setAPIResponse('error', 'Category name: ' . $array['category'] . ' is already taken', 409);
				return false;
			}
		}
		if (array_key_exists('default', $array)) {
			if ($array['default']) {
				$this->_clearBookmarkCategoryDefault();
			}
		}
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'UPDATE `BOOKMARK-categories` SET',
					$array,
					'WHERE id = ?',
					$id
				)
			),
		];
		$this->setAPIResponse(null, 'Category info updated');
		$this->writeLog('success', 'Category Editor Function -  Edited Category Info for [' . $categoryInfo['category'] . ']', $this->user['username']);
		$result = $this->processQueries($response);
		$this->_correctDefaultCategory();
		return $result;
	}

	public function _updateCategoryOrder($array)
	{
		if (count($array) >= 1) {
			foreach ($array as $category) {
				if (count($category) !== 2) {
					$this->setAPIResponse('error', 'data is malformed', 422);
					break;
				}
				$id = $category['id'] ?? null;
				$order = $category['order'] ?? null;
				if ($id && $order) {
					$response = [
						array(
							'function' => 'query',
							'query' => array(
								'UPDATE `BOOKMARK-categories` set `order` = ? WHERE `id` = ?',
								$order,
								$id
							)
						),
					];
					$this->processQueries($response);
					$this->setAPIResponse(null, 'Category Order updated');
				} else {
					$this->setAPIResponse('error', 'data is malformed', 422);
				}
			}
		} else {
			$this->setAPIResponse('error', 'data is empty or not in array', 422);
			return false;
		}
	}

	public function _deleteCategory($id)
	{
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'DELETE FROM `BOOKMARK-categories` WHERE id = ?',
					$id
				)
			),
		];
		$categoryInfo = $this->_getBookmarkCategoryById($id);
		if ($categoryInfo) {
			$this->writeLog('success', 'Category Delete Function -  Deleted Category [' . $categoryInfo['category'] . ']', $this->user['username']);
			$this->setAPIResponse('success', 'Category deleted', 204);
			$result = $this->processQueries($response);
			$this->_correctDefaultCategory();
			return $result;
		} else {
			$this->setAPIResponse('error', 'id not found', 404);
			return false;
		}
	}

	protected function _correctDefaultCategory()
	{
		if ($this->_getDefaultBookmarkCategoryId() == null) {
			$response = [
				array(
					'function' => 'query',
					'query' => 'UPDATE `BOOKMARK-categories` SET `default` = 1 WHERE `category_id` = (SELECT `category_id` FROM `BOOKMARK-categories` ORDER BY `category_id` ASC LIMIT 0,1)'
				)
			];
			return $this->processQueries($response);
		}
	}

	protected function _checkColorHexCode($hex)
	{
		return preg_match('/^\#([0-9a-fA-F]{3}){1,2}$/', $hex);
	}

	/**
	 * Increases or decreases the brightness of a color by a percentage of the current brightness.
	 *
	 * @param string $hexCode Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
	 * @param float $adjustPercent A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
	 *
	 * @return  string
	 *
	 * @author  maliayas
	 * @link    https://stackoverflow.com/questions/3512311/how-to-generate-lighter-darker-color-with-php
	 */
	protected function adjustBrightness($hexCode, $adjustPercent)
	{
		$hexCode = ltrim($hexCode, '#');
		if (strlen($hexCode) == 3) {
			$hexCode = $hexCode[0] . $hexCode[0] . $hexCode[1] . $hexCode[1] . $hexCode[2] . $hexCode[2];
		}
		$hexCode = array_map('hexdec', str_split($hexCode, 2));
		foreach ($hexCode as &$color) {
			$adjustableLimit = $adjustPercent < 0 ? $color : 255 - $color;
			$adjustAmount = ceil($adjustableLimit * $adjustPercent);
			$color = str_pad(dechex($color + $adjustAmount), 2, '0', STR_PAD_LEFT);
		}
		return '#' . implode($hexCode);
	}

	public function _checkForBookmarkTab()
	{
		$response = [
			array(
				'function' => 'fetchAll',
				'query' => array(
					'SELECT * FROM tabs',
					'WHERE url = ?',
					'api/v2/plugins/bookmark/page'
				)
			),
		];
		$tab = $this->processQueries($response);
		if ($tab) {
			$this->setAPIResponse('success', 'Tab already exists', 200);
			return $tab;
		} else {
			$createTab = $this->_createBookmarkTab();
			if ($createTab) {
				$tab = $this->processQueries($response);
				$this->setAPIResponse('success', 'Tab created', 200);
				return $tab;
			} else {
				$this->setAPIResponse('error', 'Tab creation error', 500);
			}
		}
	}

	public function _createBookmarkTab()
	{
		$tabInfo = [
			'order' => $this->getNextTabOrder() + 1,
			'category_id' => $this->getDefaultCategoryId(),
			'name' => 'Bookmarks',
			'url' => 'api/v2/plugins/bookmark/page',
			'default' => false,
			'enabled' => true,
			'group_id' => $this->getDefaultGroupId(),
			'image' => 'fontawesome::book',
			'type' => 0
		];
		$response = [
			array(
				'function' => 'query',
				'query' => array(
					'INSERT INTO [tabs]',
					$tabInfo
				)
			),
		];
		return $this->processQueries($response);
	}

	public function _checkForBookmarkCategories()
	{
		$categories = $this->_getAllCategories();
		if ($categories) {
			$this->setAPIResponse('success', 'Categories already exists', 200);
			return $categories;
		} else {
			$createCategory = $this->_addCategory(['category' => 'Unsorted', 'default' => 1]);
			if ($createCategory) {
				$categories = $this->_getAllCategories();
				$this->setAPIResponse('success', 'Category created', 200);
				return $categories;
			} else {
				$this->setAPIResponse('error', 'Category creation error', 500);
			}
		}
	}
}