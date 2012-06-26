<div class="wrap">
	<h2 class="header"><img src="<?php echo plugins_url('/images/logo-icon.png', dirname(__FILE__)) ?>" class="mp-icon" /><span>Reporting</span></h2>
	<p class="subtitle" style="padding-left:0px;">Select Traffic and Revenue to view your MediaPass Subscriptions impression and sales performance.  Or use the Subscribers report to review registration and subscriptions information.</p>
	<br/>
	<div style="text-align: center; width: 100%;">
	<label for="stats">Display</label>
	<select id="stats">
		<option value="traffic_revenue">traffic and revenue</option>
		<option value="subscribers">subscribers</option>
	</select>
	<label for="from">from</label>
    <input type="text" id="from" name="from"/>
    <label for="to">to</label>
    <input type="text" id="to" name="to"/>
    <select>
    	<option value="day">by day</option>
    	<option value="week">by week</option>
    	<option value="month">by month</option>
    </select>
    <input type="button" value="update" class="button" id="update-chart" />
    
    </div>
	<div id="chart-container" style="height: 500px;"></div>
	<div id="sub-chart-container" style="height: 500px; width: 100%;" class="ui-helper-hidden-accessible"></div>
	
	<script type="text/javascript">
		 jQuery(document).ready(function ($) {
		 	var serviceUrl = '<?php echo esc_js( MediaPass_Plugin::API_PREFIX ); ?>reportingV1/ViewingData',
		 		assetId    =  <?php echo intval( get_option(MediaPass_Plugin::OPT_USER_NUMBER) ); ?>;
		 	
            var chart = new MM.UI.Charting.ImpressionAndFinancials({
            	serviceUrl: serviceUrl
            });
			
			var chart2 = new MM.UI.Charting.RegistrationAndSubscriptions({
            	serviceUrl: serviceUrl
			});
			
			var today   = new Date(),
				weekAgo = new Date();
			
			weekAgo.setDate(today.getDate()-7);
			
			var $from = $('#from'),
				$to = $('#to');
			
			var todayText = (today.getMonth()+1) + '/' + today.getDate() + '/' + today.getFullYear();
			var weekAgoText = (weekAgo.getMonth()+1) + '/' + weekAgo.getDate() + '/' + weekAgo.getFullYear();
			
			$from.val(weekAgoText);
			$to.val(todayText);
			
            chart.loadAndRenderChart('chart-container', {
                context: 'asset',
                contextId: assetId,
                startDate: weekAgoText,
                endDate: todayText
            });
            
            chart2.loadAndRenderChart('sub-chart-container', {
                context: 'asset',
                contextId: assetId,
                startDate: weekAgoText,
                endDate: todayText
            });
            
            $('#update-chart').click(function () {
                var from = $from.datepicker("getDate"),
                	to   = $to.datepicker("getDate"),
					type = $('#stats').val();
				
				console.log(type);
				
				var theChart = type === 'traffic_revenue'
					? chart : chart2;
				
				if( type === 'traffic_revenue' ) {
					$('#sub-chart-container').addClass('ui-helper-hidden-accessible');
					$('#chart-container').removeClass('ui-helper-hidden-accessible');
				} else {
					$('#chart-container').addClass('ui-helper-hidden-accessible');
					$('#sub-chart-container').removeClass('ui-helper-hidden-accessible');
				}
				
                theChart.reloadChart({
                    startDate: $.datepicker.formatDate('yy-mm-dd', from),
                    endDate: $.datepicker.formatDate('yy-mm-dd', to),
                    context: 'asset',
                    contextId: assetId
                });
            });

            $('#from,#to').datepicker();
		});
	</script>
</div>