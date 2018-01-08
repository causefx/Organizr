/**
 * Created by hale on 2016/12/29.
 */

;(function ($, window, document, undefined) {
	
	var Gauge = function (el) {
		this.$element = el,
			this.defaults = {},
			this.options = $.extend({}, this.defaults, {})
	};
	
	Gauge.prototype = {
		colors: ['gauge-green', 'gauge-orange', 'gauge-yellow', 'gauge-red'],
		partSize: 0,
		initParams: function () {
			var colorLen = Gauge.prototype.colors.length;
			Gauge.prototype.partSize = 100.0 / colorLen;
		},
		createGauge: function (elArray) {
			elArray.each(function () {
				Gauge.prototype.updateGauge($(this));
			});
			
			//添加updateGauge事件 更新百分比
			elArray.bind('updateGauge', function (e, num) {
				$(this).data('percentage', num);
				Gauge.prototype.updateGauge($(this));
			});
		},
		updateGauge: function (el) {
			Gauge.prototype.initParams();
			var percentage = el.data('percentage');
			percentage = (percentage > 100) ? 100 : (percentage < 0) ? 0 : percentage;
			
			el.css('transform', 'rotate(' + ((1.8 * percentage) - 90) + 'deg)');
			el.parent()
				.removeClass(Gauge.prototype.colors.join(' '))
				.addClass(Gauge.prototype.colors[Math.floor(percentage / Gauge.prototype.partSize)]);
		}
	};
	
	$.fn.cmGauge = function () {
		var gauge = new Gauge(this);
		return gauge.createGauge(this);
	}
	
})(jQuery, window, document);