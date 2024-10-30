'use strict';


var NO_LAST_UPDATE_DATE_STR = 'No last update date available.';
var PLACEHOLDER_DEFAULT_FLAG = true;
var CONTENT_DEFAULT_FLAG = true;


function defaultOptions() {
    return {
        margin: {
            top: 30,
            right: 50,
            bottom: 90,
            left: 50
        },
        width: 600,
        height: 310
    };
}

function getDefaultOptionswWithDailyDateFormat() {
    var newDefault = defaultOptions();
    newDefault.dateDisplayFormat = "%Y-%m-%d";
    newDefault.dateTooltipFormat = "%A %Y-%m-%d";
    return newDefault;
}

function getDefaultOptions(isByDay) {
    if (isByDay) {
        return getDefaultOptionswWithDailyDateFormat();
    } else {
        return defaultOptions();
    }
}

angular.module('ImpPortalAngularApp')
    .controller('DashboardCtrl', function($scope, $timeout, $compile, $window, $location, messageService,
        httpService, dashboardService, settingService, statisticsService, bannerService, utilsService, precedentMap) {

	var IMAGES_BASE_URL = hd_mpi_url+"assets/img/";

        if (httpService.setToken) {
            httpService.setToken(mp_api_token);
        }

        var overallStatistics = null;

        var bannersData = null;

        var bannerStats = null;

        var imageTooltip = angular.element(document.querySelector('#image-tooltip'));

        var ctrStructure = null;

        $scope.showHighestPreview = showHighestPreview;
        $scope.hideHighestPreview = hideHighestPreview;

        $scope.showBannerPreview = showBannerPreview;
        $scope.hideBannerPreview = hideBannerPreview;

        $scope.changeBannerStatsAndCTR = changeBannerStatsAndCTR;

        $scope.changeStatsRange = changeStatsRange;

        $scope.changeGlobalFlag = changeGlobalFlag;

        $scope.changeBannerFlag = changeBannerFlag;

        $scope.globalButtonFlag = PLACEHOLDER_DEFAULT_FLAG; //true - left | false - right

        $scope.bannerButtonFlag = CONTENT_DEFAULT_FLAG; //true - left | false - right

        $scope.moduleStatsExists = false;


        $scope.globalChartTimestampDate = NO_LAST_UPDATE_DATE_STR;
        $scope.ctrTimestampDate = NO_LAST_UPDATE_DATE_STR;
        $scope.bannerChartTimestampDate = NO_LAST_UPDATE_DATE_STR;

        $scope.advancedReporting = false;

        $scope.existsCTRs = true;
	$scope.images = {
	    src: {
		time: IMAGES_BASE_URL+"time.svg",
		loading: IMAGES_BASE_URL+"loading.gif"
		
	    }
	}

        $scope.colors = {
            Ml: "#1f77b4",
            Manual: "#ff7f0e",
            ml: "#1f77b4",
            manual: "#ff7f0e"
        };

        $scope.dataRangeChartOptions = [{
            value: "1D",
            name: "1 Day",
            items: 24,
            period: "day",
            byDay: false
        }, {
            value: "1W",
            name: "1 Week",
            items: (24 * 8),
            period: "week",
            byDay: false
        }, {
            value: "2W",
            name: "2 Weeks",
            items: (24 * 15),
            period: "two weeks",
            byDay: false
        }, {
            value: "1M",
            name: "1 Month",
            items: (24 * 30),
            period: "month",
            byDay: false
        }, {
            value: "6M",
            name: "6 Months",
            items: (24 * 30 * 6),
            period: "six months",
            byDay: true
        }];

        $scope.importanceVariablesEmpty = false;
        $scope.importanceVariables = [];

        var precMapper = precedentMap.createNew({
            "globalStats": [],
            "bannersInfo": [],
            "ctrStats": [],
            "advancedReport": [],
            "hightestCTR": ["ctrStats", "bannersInfo", "advancedReport"],
            "bannerCTR": ["ctrStats", "bannersInfo", "advancedReport"],
            "bannerStats": ["bannersInfo", "advancedReport"],
            "overallCTR": ["ctrStats"]
        });
        var taskMapper = {
            "hightestCTR": changeHighestCTRBanner,
            "bannerCTR": changeBannerCTR,
            "bannerStats": changeBannerStats,
            "overallCTR": changeOverallCTRs
        };

        messageService.registerScope($scope);
        if (messageService.hasMessages()) {
            messageService.popMessageToScopes();
        }

        startDashboard();

        function startDashboard() {

            $scope.selectedRangeChartOption = $scope.dataRangeChartOptions[0];

            statisticsService.getOverallStatistics($scope.selectedRangeChartOption.period).then(overallStatisticsHandler);


            settingService.getSubscriptionInfo().then(subscriptionTypeHandler);

            dashboardService.getVariablesImportance().then(function(response) {
                if (200 <= response.status && response.status < 300) {
                    $scope.importanceVariables = response.data.varimp;
                    $scope.importanceVariablesEmpty = false;
                } else {
                    $scope.importanceVariables = [];
                    $scope.importanceVariablesEmpty = true;
                    $scope.importanceVariablesMessage = "No content to display";
                }
            });

            statisticsService.getCTRStatistics().then(function(response) {
                if (200 <= response.status && response.status < 300) {

                    var ctrData = response.data;

                    ctrStructure = statisticsService.convertCTRStructure(ctrData);

                    $scope.ctrTimestampDate = new Date(ctrStructure.timestamp * 1000).toString();
                    precMapper.finishTask("ctrStats");
                    executeTasksIfPossible();
                } else if (response.status == 404) {
                    var domElement = angular.element(document.querySelector('#banner-average'));
                    var template = '<p>No CTR data available!</p>';
                    utilsService.insertScopeHTML($scope, domElement, template);
                } else {
                    displayGetDataError();
                }


            });

        }

        function overallStatisticsHandler(response) {
            if (200 <= response.status && response.status < 300) {
                precMapper.finishTask("globalStats");
                overallStatistics = response.data;
                var overallStatsDisplay = statisticsService.convertStatisticsToDisplay(overallStatistics, false);
                var globalStatsDisplay = statisticsService.convertStatisticsToDisplay(overallStatistics, true);

                var overallData = statisticsService.getLastItems(overallStatsDisplay);
                var globalData = statisticsService.getLastItems(globalStatsDisplay);

                if (overallData.data && overallData.data.length > 0) {
                    $timeout(function() {

                        $scope.overallOptions = $scope.globalOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);

                        $scope.overallData = globalData;
                        $scope.globalData = globalData;

                        if (overallStatsDisplay.lastUpdated)
                            $scope.globalChartTimestampDate = new Date(overallStatsDisplay.lastUpdated * 1000).toString();
                        else
                            $scope.globalChartTimestampDate = NO_LAST_UPDATE_DATE_STR;
                    });
                } else {
                    var domElement = angular.element(document.querySelector('#overall-chart'));
                    domElement.empty();
                    insertMissingDataMessage(domElement);
                }


                executeTasksIfPossible();
            } else {
                displayGetDataError();
            }
        }

        function subscriptionTypeHandler(response) {
            if (200 <= response.status && response.status < 300) {
                var subscription = response.data;
                if (subscription.advancedReporting) {
                    $scope.advancedReporting = true;
                    precMapper.finishTask("advancedReport");
                    bannerService.getAllBanners().then(function(response) {
                        if (200 <= response.status && response.status < 300) {
                            bannersData = response.data;
                            precMapper.finishTask("bannersInfo");
                            $scope.dataItemOptions = bannersData.banners.map(function(banner) {
                                return {
                                    value: banner.bannerID,
                                    name: banner.name,
                                    url: banner.sourceUrl
                                };
                            });
                            $scope.selectedItemOption = $scope.dataItemOptions[0];
                            executeTasksIfPossible();
                        } else {
                            displayGetDataError();
                        }
                    });
                }
            }
        }

        function executeTasksIfPossible() {
            Object.keys(taskMapper).forEach(function(keyTask) {
                if (!precMapper.hasFinished(keyTask) && precMapper.canExecuteTask(keyTask)) {
                    precMapper.startTask(keyTask);
                    taskMapper[keyTask]();
                    precMapper.finishTask(keyTask);
                }
            });
        }

        function showHighestPreview($event, url) {

            $scope.tooltipBanner = {
                url: url
            };
            imageTooltip[0].style.opacity = 1;
            imageTooltip[0].style.display = 'initial';

            var position = getTopAndLeft($event, imageTooltip[0]);
            imageTooltip[0].style.top = position.top + 'px';
            imageTooltip[0].style.left = position.left + 'px';

        }

        function getTopAndLeft($event, element) {
            var x = $event.pageX,
                y = $event.pageY,
                xPad = 15,
                yPad = 0,
                tooltipMargin = 30;




            var viewport = {
                top: $window.scrollY,
                left: $window.scrollX
            };

            viewport.right = viewport.left + $window.innerWidth;
            viewport.bottom = viewport.top + $window.innerHeight;
            var height = element.offsetHeight;
            var width = element.offsetWidth;


            var top = ((y + yPad + height + tooltipMargin) <= viewport.bottom) ? (y + yPad) : (y - yPad - height);
            var left = ((x + xPad + width + tooltipMargin) <= viewport.right) ? (x + xPad) : (x - xPad - width);
            return {
                top: top,
                left: left
            };
        }

        function hideHighestPreview() {
            imageTooltip[0].style.opacity = 0;
            imageTooltip[0].style.display = 'none';
            imageTooltip[0].style.top = 0 + 'px';
            imageTooltip[0].style.left = 0 + 'px';
            $scope.tooltipBanner = null;
        }

        function showBannerPreview($event) {
            $scope.tooltipBanner = $scope.selectedItemOption;


            var position = getTopAndLeft($event, imageTooltip[0]);

            $timeout(function() {
                imageTooltip[0].style.top = position.top + 'px';
                imageTooltip[0].style.left = position.left + 'px';
                imageTooltip[0].style.opacity = 1;
                imageTooltip[0].style.display = 'initial';
            }, 250);


        }

        function hideBannerPreview() {

            imageTooltip[0].style.opacity = 0;
            $scope.tooltipBanner = null;
            imageTooltip[0].style.display = 'none';
            imageTooltip[0].style.top = 0 + 'px';
            imageTooltip[0].style.left = 0 + 'px';
        }

        function changeGlobalFlag() {

            var isGlobal = $scope.globalButtonFlag;

            var statDisplay = statisticsService.convertStatisticsToDisplay(overallStatistics, isGlobal);
            if (statDisplay.data && statDisplay.data.length > 0) {
                $timeout(function() {
                    var data = statisticsService.getLastItems(statDisplay);
                    $scope.overallOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);
                    $scope.overallData = data;
                }, 1);
            } else {
                var domElement = angular.element(document.querySelector('#overall-chart'));
                domElement.empty();
                insertMissingDataMessage(domElement);
            }



        }

        function changeBannerFlag() {
            var isGlobal = $scope.bannerButtonFlag;

            var statDisplay = statisticsService.convertStatisticsToDisplay(bannerStats.stats, isGlobal);
            var domElement = angular.element(document.querySelector('#banner-chart'));
            domElement.empty();

            if (statDisplay.data && statDisplay.data.length > 0)
                $timeout(function() {
                    $scope.moduleStatsExists = true;
                    var data = statisticsService.getLastItems(statDisplay);
                    $scope.bannerOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);
                    $scope.bannerData = data;
                }, 1);
            else {
                $scope.moduleStatsExists = false;
                domElement.empty();
                insertMissingDataMessage(domElement);
            }

        }

        function changeBannerStatsAndCTR() {
            $scope.bannerButtonFlag = CONTENT_DEFAULT_FLAG;
            var insertMessageIfError = true;
            changeBannerStats(insertMessageIfError);
            changeBannerCTR();
        }

        function changeBannerStats(insertMessageIfError) {
            $scope.loadingBannerStats = true;
            if (insertMessageIfError == undefined)
                insertMessageIfError = true;
            var domElement = angular.element(document.querySelector('#banner-chart'));
            domElement.empty();
            if ($scope.selectedItemOption && $scope.selectedItemOption.value) {
                statisticsService.getBannerStatistics($scope.selectedItemOption.value, $scope.selectedRangeChartOption.period).then(function changeBannerStatsHandler(response) {
                    if (200 <= response.status && response.status < 300) {
                        bannerStats = response.data;

                        var bannerData = statisticsService.getLastItems(statisticsService.convertStatisticsToDisplay(response.data.stats, CONTENT_DEFAULT_FLAG));

                        if (bannerData.data && bannerData.data.length > 0) {
                            $timeout(function() {
                                $scope.moduleStatsExists = true;
                                $scope.bannerOptions = $scope.globalBannerOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);
                                $scope.bannerData = bannerData;
                                if ($scope.bannerData.lastUpdated)
                                    $scope.bannerChartTimestampDate = new Date($scope.bannerData.lastUpdated * 1000).toString();
                                else
                                    $scope.bannerChartTimestampDate = NO_LAST_UPDATE_DATE_STR;

                            }, 1);
                        } else {
                            if (insertMessageIfError) {
                                $scope.moduleStatsExists = false;
                                insertMissingDataMessage(domElement);
                            }
                        }

                    } else {
                        if (insertMessageIfError) {
                            $scope.moduleStatsExists = false;
                            domElement.empty();
                            insertMissingDataMessage(domElement);
                        }
                    }
                    $scope.loadingBannerStats = false;
                });
            } else if (insertMessageIfError) {
                $scope.moduleStatsExists = false;
                domElement.empty();
                $scope.loadingBannerStats = false;
                insertMissingDataMessage(domElement);
            }
        }









        function changeStatsRange(selectedRangeChartOption) {

            //reset banner options to first
            //change global stats
            //change banner stats
            //change ctr global
            //change ctr banner
            //change best banner ctr

            forceCurrentBanner();

            resetNodes();

            $scope.globalButtonFlag = true;
            statisticsService.getOverallStatistics($scope.selectedRangeChartOption.period).then(overallStatisticsHandler);
            changeBannerStatsAndCTR();
            changeOverallCTRs();
            changeHighestCTRBanner();
        }

        function resetNodes() {

            if (overallStatistics)
                angular.element(document.querySelector('#overall-chart')).empty();

            angular.element(document.querySelector('#global-average')).empty();
            angular.element(document.querySelector('#banner-chart')).empty();
            angular.element(document.querySelector('#banner-average')).empty();
            angular.element(document.querySelector('#highest-ctr-banner')).empty();

        }

        function changeHighestCTRBanner() {
            if (ctrStructure && ctrStructure.CTRs) {
                var ctrsArray = ctrStructure.CTRs[$scope.selectedRangeChartOption.value];
                var element = angular.element(document.querySelector('#highest-ctr-banner'));
                element.empty();
                var counter = 1;
                ctrsArray.forEach(function(ctrStat) {
                    var name = statisticsService.getName(ctrStat.type);
                    var ctr = (ctrStat.bestCTR != undefined) ? getRoundValue(ctrStat.bestCTR * 100) : " - ";
                    var ctrChange = getRoundValue(ctrStat.bestCTRChange * 100);
                    var bannerID = ctrStat.best;
                    var bannerInfo, bannerName, bannerSrc;
                    $scope.dataItemOptions.forEach(function(bannerData) {
                        if (bannerData.value == bannerID) {
                            bannerInfo = bannerData;
                        }
                    });
                    if (bannerInfo) {
                        bannerName = bannerInfo.name;
                        bannerSrc = bannerInfo.url;
                    } else {
                        bannerName = bannerSrc = "";
                    }
                    insertHighestCTRBanner(element,
                        name,
                        $scope.colors[ctrStat.type],
                        ctr,
                        ctrChange,
                        bannerName,
                        bannerSrc);

                });
            }
        }

        function changeBannerCTR() {
            if (ctrStructure && ctrStructure.CTRs) {
                var ctrsArray = ctrStructure.CTRs[$scope.selectedRangeChartOption.value];
                var element = angular.element(document.querySelector('#banner-average'));
                element.empty();
                var counter = 1;
                if (ctrsArray.lenght == 0) {
                    $scope.existsBannersCTR = false;
                } else {
                    $scope.existsBannersCTR = true;
                }
                ctrsArray.forEach(function(ctrStat) {
                    var bannerCTR = ctrStat.banners.filter(function(stat) {
                        return stat.bannerID == $scope.selectedItemOption.value;
                    });
                    if (bannerCTR.length !== 0) {
                        bannerCTR = bannerCTR[0];
                        var name = statisticsService.getName(ctrStat.type);
                        var ctr = (bannerCTR.CTR != undefined) ? getRoundValue(bannerCTR.CTR * 100) : " - ";
                        var ctrChange = getRoundValue(bannerCTR.CTRChange * 100);
                        insertAverageCTRHtml(element,
                            name,
                            $scope.colors[ctrStat.type],
                            ctr,
                            ctrChange);
                    }
                });
            }

        }

        function getRoundValue(value) {
            if (value >= 10) {
                return utilsService.roundToAtMostFloat(value, 1);
            }
            return utilsService.roundToAtMostFloat(value, 2);

        }


        function changeOverallChart(selectedRangeChartOption) {

            var overallStatsDisplay = statisticsService.convertStatisticsToDisplay(overallStatistics, PLACEHOLDER_DEFAULT_FLAG);
            var resultData = statisticsService.getLastItems(overallStatsDisplay);
            if (resultData && resultData.data && resultData.data.length > 0)

                $timeout(function() {

                $scope.overallOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);

                $scope.overallData = resultData;
            }, 1);
            else {
                var element = angular.element(document.querySelector('#overall-chart'));
                element.empty();
                insertMissingDataMessage(element);
            }


        }

        function changeGlobalChart(selectedRangeChartOption) {
            var globalStatsDisplay = statisticsService.convertStatisticsToDisplay(overallStatistics, PLACEHOLDER_DEFAULT_FLAG);
            var resultData = statisticsService.getLastItems(globalStatsDisplay);
            if (resultData && resultData.data && resultData.data.length > 0)
                $timeout(function() {

                    $scope.globalOptions = getDefaultOptions($scope.selectedRangeChartOption.byDay);
                    $scope.globalData = resultData;
                }, 1);
        }

        function resetBanner() {
            if ($scope.dataItemOptions.length > 0) {
                $scope.selectedItemOption = $scope.dataItemOptions[0];
                var insertMessageIfError = true;
                changeBannerStats(insertMessageIfError);

            }
        }

        function forceCurrentBanner() {
            if ($scope.dataItemOptions.length > 0) {
                var insertMessageIfError = false;
                changeBannerStats(insertMessageIfError);
            }
        }

        function changeOverallCTRs() {
            if (ctrStructure && ctrStructure.CTRs) {
                var ctrsArray = ctrStructure.CTRs[$scope.selectedRangeChartOption.value];
                var element = angular.element(document.querySelector('#global-average'));
                element.empty();
                ctrsArray.forEach(function(ctrStat) {
                    var name = statisticsService.getName(ctrStat.type);
                    var ctr = (ctrStat.CTR != undefined) ? getRoundValue(ctrStat.CTR * 100) : " - ";
                    var ctrChange = getRoundValue(ctrStat.CTRChange * 100);
                    insertAverageCTRHtml(element,
                        name,
                        $scope.colors[ctrStat.type],
                        ctr,
                        ctrChange);
                });
            }

        }

        function insertAverageCTRHtml(domElement, typeName, color, ctr, ctrChange) {

            var confs = getCTRArrowConfs(ctrChange);
            ctrChange = (ctrChange != 0) ? ctrChange : "";
            var template = '<h4 class="numbstats" style="background: ' + color + '">' + typeName + '</h4>' +
                '<div class="round-stats"><span>' + ctr + '%</span><div>' +
                '<img src="' + confs.src + '" class="' + confs.class + '" alt="' + confs.alt + '" aria-hidden="true"><span style="' + ((ctrChange < 0) ? "color: #d80027" : "") + '">' +
                ((ctrChange != 0) ? (ctrChange + "%") : "") + '</span></div></div>';
            utilsService.insertScopeHTML($scope, domElement, template);
        }

        function insertHighestCTRBanner(domElement, typeName, color, ctr, ctrChange, bannerName, bannerSrc) {

            var confs = getCTRArrowConfs(ctrChange);
            ctrChange = (ctrChange != 0) ? ctrChange : "";
            var template = '<h4 class="numbstats stats300" style="background: ' + color + '">' + typeName + '</h4>' +
                '<div class="round-stats stats300"><span>' + ctr + '%</span><div>' +
                '<img src="' + confs.src + '" class="' + confs.class + '" alt="' + confs.alt + '" aria-hidden="true"><span style="' + ((ctrChange < 0) ? "color: #d80027" : "") + '">' +
                ((ctrChange != 0) ? (ctrChange + "%") : "") + '</span></div>' +
                '<div class="highest-ctr-name"><h4>' + bannerName + '</h4></div></div>';
            utilsService.insertScopeHTML($scope, domElement, template);
        }

        function getCTRArrowConfs(ctrChange) {
            if (ctrChange < 0) {
                return {
                    src: IMAGES_BASE_URL+"down-arrow.svg",
                    "class": "down-arrow",
                    alt: "arrow down"
                };
            }
            if (ctrChange > 0) {
                return {
                    src: IMAGES_BASE_URL+"up-arrow.svg",
                    "class": "up-arrow",
                    alt: "arrow up"
                };
            }
            return {
                src: IMAGES_BASE_URL+"equal.svg",
                "class": "equal",
                alt: "equal"
            };
        }

        function displayGetDataError() {
            var nSeconds = 5;
            messageService.pushErrorMessage('Something went wrong. It was not possible to get the statistical data.', nSeconds);
            messageService.popMessageToScopes();
        }

        function insertMissingDataMessage(domElement) {
            var template = '<p style="text-align: center;">No data available!</p>';
            utilsService.insertScopeHTML($scope, domElement, template);
        }

    });
