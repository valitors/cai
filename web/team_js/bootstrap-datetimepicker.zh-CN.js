/**
 * Simplified Chinese translation for bootstrap-datetimepicker
 * Yuan Cheung <advanimal@gmail.com>
 */
;(function($){
	if($.fn.datetimepicker){
		$.fn.datetimepicker.dates['zh-CN'] = {
				days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
				daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
				daysMin:  ["日", "一", "二", "三", "四", "五", "六", "日"],
				months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
				today: "今天",
				suffix: [],
				meridiem: ["上午", "下午"]
		};
	}

	if($.fn.datepicker){
		$.fn.datepicker.dates["zh-CN"] = {
			days : [ "星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六" ],
			daysShort : [ "周日", "周一", "周二", "周三", "周四", "周五", "周六" ],
			daysMin : [ "日", "一", "二", "三", "四", "五", "六" ],
			months : [ "一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月",
					"十一月", "十二月" ],
			monthsShort : [ "1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月",
					"10月", "11月", "12月" ],
			today : "今日",
			clear : "清除",
			format : "yyyy年mm月dd日",
			titleFormat : "yyyy年mm月",
			weekStart : 1
		}
	}
}(jQuery));

