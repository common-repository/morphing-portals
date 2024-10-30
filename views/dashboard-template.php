<div class="main-header">
	<div class="flex-container-ab">
		<div class="flex-item-ab">Dashboard</div>
		<div class="flex-item-ab text-right regulartext"> Data Range:
			<select style="width: 100px;" data-ng-options="option.name for option in dataRangeChartOptions" data-ng-model="selectedRangeChartOption" ng-change="changeStatsRange(selectedRangeChartOption)">
			</select>
		</div>
	</div>
</div>

<div class="main-dashboard">
	<div class="flex-container-dashboard">

		<div class="flex-item-dashboard-left">
			<div ng-show="messageService.message" ng-class="messageService.messageType">
				<span>{{messageService.message}}</span>
				<span class="close-message"><i class="fa fa-times" ng-click="messageService.closeMessage()"></i></span>
			</div>
			<div class="box-dashboard expand marginbottom40" style="height: inherit;">
				<div class="flex-container-title">
					<div class="flex-item-title">
						<h2 class="title-graph">
							Click-Through Rate
						</h2>
					</div>
					<div class="flex-item-title textright" style="display: flex;">
							<div class="flex-item-ab text-right regulartext">
							</div>
							<div class="graph-buttons" style="margin-left: 20px;">
									<input ng-model="globalButtonFlag" ng-change="changeGlobalFlag()" type="checkbox" style="margin: 0px;"><span style="margin-left: 3px;">Cumulative</span>
							</div>
					</div>
				</div>

				<div class="content-box">

					<div class="flex-container-ab" style="min-height: 364px;">
						<div class="flex-item-ab">
							<div id="overall-chart" d3-line-tooltip data="overallData" options="overallOptions" colors="colors"></div>
						</div>
						<div id="global-average" class="flex-item-ab text-center">

						</div>

					</div>
				</div>
				<div class="date-update">
					<h4><img ng-src="{{images.src.time}}" class="clock-update" alt="time"><span>Last data update times</span></h4>
					<p><strong>Charts: </strong>{{globalChartTimestampDate}}</p>
					<p><strong>Average CTRs: </strong>{{ctrTimestampDate}}</p>
				</div>
			</div>


			<div ng-show="advancedReporting" class="box-dashboard expand marginbottom40" style="height: inherit;">
				<div class="flex-container-title">
					<div class="flex-item-title">
						<h2>
							Click-Through Rate by Content
						</h2>
					</div>
					<div class="flex-item-title textright" style="display: flex;">
							<div class="flex-item-ab text-right regulartext">
							</div>
							<div class="graph-buttons" style="margin-left: 20px;">
									<input ng-model="bannerButtonFlag" ng-change="changeBannerFlag()" type="checkbox" style="margin: 0px;"><span style="margin-left: 3px;">Cumulative</span>
							</div>
					</div>
				</div>
				<div class="content-box" ng-class="{'content-box-500' : moduleStatsExists}">
					<h3 class="marginbottom40"> Content:
						<select  style="width: 200px;" data-ng-options="option.name for option in dataItemOptions" data-ng-model="selectedItemOption" ng-change="changeBannerStatsAndCTR()">
						</select>
						<img ng-show="loadingBannerStats" ng-src="{{images.src.loading}}"/>
					</h3>
					<div class="flex-container-ab" style="min-height: 364px;">
						<div class="flex-item-ab">

							<div id="banner-chart" d3-line-tooltip data="bannerData" options="bannerOptions"></div>
						</div>
						<div id="banner-average" class="flex-item-ab text-center" ng-show="existsBannersCTR"></div>
					</div>
				</div>
				<div class="date-update">
					<h4><img ng-src="{{images.src.time}}" class="clock-update" alt="time"><span>Last data update times</span></h4>
					<p><strong>Charts: </strong>{{bannerChartTimestampDate}}</p>
					<p><strong>Average CTRs: </strong>{{ctrTimestampDate}}</p>
				</div>
			</div>

		</div>
		<div class="flex-item-dashboard-right">
			<div ng-show="advancedReporting" class="box-dashboard expand marginbottom40" style="height: inherit;">
				<h2>Highest CTR</h2>
				<div class="content-box padding20">
					<div id="highest-ctr-banner" class="highest-ctr">

					</div>
				</div>
			</div>
			<div ng-show="advancedReporting" class="box-dashboard expand" style="height: inherit;">
				<h2>Variable Importance</h2>
				<div class="content-box padding20">
					<div>
						<table class="table-compare table-list variable-table">
						<tr ng-show="false"><td></td><td></td></tr>
						<tr ng-repeat="variable in importanceVariables">
							<td>{{variable.parameters}}</td>
							<td ng-show="variable.importance == 'POS'"><i class="fa fa-plus" style="color: #008000;"></td>
							<td ng-show="variable.importance == 'NEG'"><i class="fa fa-minus" style="color: #FF0000;"></td>
						</tr>
						</table>
						<div style="color: #4d5664;text-align: center;" ng-show="importanceVariablesEmpty">{{importanceVariablesMessage}}</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
