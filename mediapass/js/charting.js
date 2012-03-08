MM = typeof MM === 'undefined' ? {} : MM;
MM.UI = typeof MM.UI === 'undefined' ? {} : MM.UI;
MM.UI.Charting = typeof MM.UI.Charting === 'undefined' ? {} : MM.UI;

MM.UI.Charting.Util = {
    dateStringToDate: function (dtString) {
        var reISO = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/;
        var reMsAjax = /^\/Date\((d|-|.*)\)[\/|\\]$/;

        var a = reISO.exec(dtString);
        if (a)
            return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3],
                        +a[4], +a[5], +a[6]));
        a = reMsAjax.exec(dtString);
        if (a) {
            var b = a[1].split(/[-,.]/);
            return new Date(+b[0]);
        }
        return null;
    }
};

MM.UI.Charting.ImpressionAndFinancials = function (config) {
	var $ = jQuery;
	
    var impressions = [],
        sales = [],
        ecpm = [],
        chartConfig = {},
        chart;

	var cfg = $.extend(true,{},{
		chart: {
			interval: 24 * 3600 * 1000
		},
		
		serviceUrl: '/reportingv1/ViewingData'
	}, config);

    var ecpmIndex = 0,
        salesIndex = 1,
        impressionIndex = 2;

    var defaultDisplayOpts = {
        defaultEcpmColor: '#8D8D8D',
        defaultImpressionColor: "#333333",
        defaultSalesColor: "#1B9031"
    };


    var getData = function (opts, cb) {
        $.ajax({
            dataType: 'jsonp',
            url: cfg.serviceUrl,
            data: opts,
            success: cb
        });
    };

    var processResultData = function (data) {
        impressions = [];
        sales = [];
        ecpm = [];

        $.each(data, function (i, v) {
            v.CreateDate = MM.UI.Charting.Util.dateStringToDate(v.CreateDate);

            var dt = v.CreateDate.getTime() - (8 * 60 * 60 * 1000);

            impressions.push([dt, v.DisplayedImpressions]);
            sales.push([dt, v.Amount]);
            ecpm.push([dt, v.Ecpm]);
        });
    };

    var clearChart = function () {
        $.each([0, 1, 2], function (i) {
            chart.series[i].setData([]);
        });
    };

    var setSeriesData = function (impressionData, salesData, ecpmData) {
        chart.series[ecpmIndex].setData(ecpmData);
        chart.series[salesIndex].setData(salesData);
        chart.series[impressionIndex].setData(impressionData);
    };

    var renderChart = function (renderTo, impressionData, salesData, ecpmData) {
        chartConfig = {
            chart: {
                renderTo: renderTo,
                zoomType: 'x'
            },
            title: {
                text: 'Traffic and Revenue'
            },
            tooltip: {
                shared: true/*,
                formatter: function () {
                    var s = '<span>Impressions:</span><span>' + this.points[impressionIndex].y + "</span><br/>";
                    s += '<span class="t-c">Sales:</span><span>' + this.points[salesIndex].y + '</span><br/>';
                    s += '<span>eCPM:</span><span>' + this.points[ecpmIndex].y + '</span><br/>';

                    return s;
                }*/
            },
            xAxis: [{
                type: 'datetime',

                tickInterval: null,
                dateTimeLabelFormats: {
                    month: '%e. %b',
                    year: '%b'
                }
            }],

            yAxis: [{
                title: { text: 'Impressions', style: { color: defaultDisplayOpts.defaultImpressionColor} },
                labels: { style: { color: defaultDisplayOpts.defaultImpressionColor} }
            }, {
                title: { text: 'eCPM', style: { color: defaultDisplayOpts.defaultEcpmColor} },
                labels: {
                    style: { color: defaultDisplayOpts.defaultEcpmColor },
                    formatter: function () {
                        return "$" + Highcharts.numberFormat(this.value, 2);
                    }
                }
            }, {
                title: { text: 'Sales', style: { color: defaultDisplayOpts.defaultSalesColor} },
                labels: {
                    style: { color: defaultDisplayOpts.defaultSalesColor },
                    formatter: function () { return "$" + Highcharts.numberFormat(this.value, 2); }
                },
                opposite: true
            }],

            series: [{
                name: 'eCPM',
                yAxis: ecpmIndex,
                type: 'column',
                data: ecpmData,
                color: defaultDisplayOpts.defaultEcpmColor
            }, {
                name: 'Sales',
                yAxis: salesIndex,
                type: 'column',
                data: salesData,
                dataLabels: { enabled: true, formatter: function () { return "$" + Highcharts.numberFormat(this.y, 2); } },
                color: defaultDisplayOpts.defaultSalesColor
            }, {
                name: 'Impressions',
                yAxis: impressionIndex,
                type: 'line',
                data: impressionData,
                color: defaultDisplayOpts.defaultImpressionColor
            }]
        };

        chart = new Highcharts.Chart(chartConfig);
    };

    var reloadChart = function (dataOpts) {
        getData(dataOpts, function (data) {
            processResultData(data);

            setSeriesData(impressions, sales, ecpm);
        });
    };

    var loadAndRenderChart = function (renderTo, dataOpts) {
        getData(dataOpts, function (data) {
            processResultData(data);

            renderChart(renderTo, impressions, sales, ecpm);
        });
    };

    return {
        setSeriesData: setSeriesData,
        clearChart: clearChart,
        reloadChart: reloadChart,
        loadAndRenderChart: loadAndRenderChart,
        getData: getData,
        renderChart: renderChart
    };
};


MM.UI.Charting.RegistrationAndSubscriptions = function (config) {
	var $ = jQuery;
	
    var registrations = [],
        subscriptions = [],
        chartConfig = {},
        chart;

	var cfg = $.extend(true,{},{
		chart: {
			interval: 24 * 3600 * 1000
		},
		
		serviceUrl: '/reportingv1/ViewingData'
	}, config);

    var registrationsIndex = 0,
        subscriptionsIndex = 1;

    var defaultDisplayOpts = {
        defaultRegistrationsColor: "#333333",
        defaultSubscriptionsColor: "#1B9031"
    };


    var getData = function (opts, cb) {
        $.ajax({
            dataType: 'jsonp',
            url: cfg.serviceUrl,
            data: opts,
            success: cb
        });
    };

    var processResultData = function (data) {
        registrations = [];
        subscriptions = [];

        $.each(data, function (i, v) {
            v.CreateDate = MM.UI.Charting.Util.dateStringToDate(v.CreateDate);

            var dt = v.CreateDate.getTime() - (8 * 60 * 60 * 1000);

            registrations.push([dt, v.SubscribersRegistered]);
            subscriptions.push([dt, v.SubscribersPaid]);
        });
    };

    var clearChart = function () {
        $.each([0, 1, 2], function (i) {
            chart.series[i].setData([]);
        });
    };

    var setSeriesData = function (registrationsData, subscriptionsData) {
        chart.series[registrationsIndex].setData(registrationsData);
        chart.series[subscriptionsIndex].setData(subscriptionsData);
    };

    var renderChart = function (renderTo, registrationsData, subscriptionsData) {
        chartConfig = {
            chart: {
                renderTo: renderTo,
                zoomType: 'x'
            },
            title: {
                text: 'Registration and Subscriptions'
            },
            tooltip: {
                shared: true/*,
                formatter: function () {
                    var s = '<span>Impressions:</span><span>' + this.points[impressionIndex].y + "</span><br/>";
                    s += '<span class="t-c">Sales:</span><span>' + this.points[salesIndex].y + '</span><br/>';
                    s += '<span>eCPM:</span><span>' + this.points[ecpmIndex].y + '</span><br/>';

                    return s;
                }*/
            },
            xAxis: [{
                type: 'datetime',

                tickInterval: null,
                dateTimeLabelFormats: {
                    month: '%e. %b',
                    year: '%b'
                }
            }],

            yAxis: [{
                title: { text: 'Registrations', style: { color: defaultDisplayOpts.defaultRegistrationsColor} },
                labels: { style: { color: defaultDisplayOpts.defaultRegistrationsColor} }
            }, {
                title: { text: 'Subscriptions', style: { color: defaultDisplayOpts.defaultSubscriptionsColor} },
                labels: { style: { color: defaultDisplayOpts.defaultSubscriptionsColor } },
                opposite: true
            }],

            series: [{
                name: 'Registrations',
                yAxis: registrationsIndex,
                type: 'line',
                data: registrationsData,
                dataLabels: { enabled: true },
                color: defaultDisplayOpts.defaultRegistrationsColor
            }, {
                name: 'Subscriptions',
                yAxis: subscriptionsIndex,
                type: 'line',
                data: subscriptionsData,
                dataLabels: { enabled: true },
                color: defaultDisplayOpts.defaultSubscriptionsColor
            }]
        };

        chart = new Highcharts.Chart(chartConfig);
    };

    var reloadChart = function (dataOpts) {
        getData(dataOpts, function (data) {
            processResultData(data);

            setSeriesData(registrations, subscriptions);
        });
    };

    var loadAndRenderChart = function (renderTo, dataOpts) {
        getData(dataOpts, function (data) {
            processResultData(data);

            renderChart(renderTo, registrations, subscriptions);
        });
    };

    return {
        setSeriesData: setSeriesData,
        clearChart: clearChart,
        reloadChart: reloadChart,
        loadAndRenderChart: loadAndRenderChart,
        getData: getData,
        renderChart: renderChart
    };
};