var module =  angular.module('ldsNavigation', ['ngRoute']);

module.directive('ldsNavigation', [function () {
    return {
        scope: true,
        replace: true,
        controller: ['$attrs', '$route', '$scope', function ($attrs, $route, $scope) {
            function upsertNavItems() {
                var navItems = [],
                    routeDef,
                    route,
                    currentRoute   = $route.current,
                    routeScope = $scope.$new(),
                    url,
                    urlParamNum,
                    urlParam,
                    urlParamFind,
                    urlParamReplace
                for (routeDef in $route.routes) {
                    route            = $route.routes[routeDef]
                    routeScope.route = route
                    if ((!$attrs.filter || routeScope.$eval($attrs.filter)) &&
                        route.navigationLabel
                    ) {
                        url = "#" + routeDef

                        for (urlParamNum = 0; urlParamNum < route.keys.length; urlParamNum++) {
                            urlParam        = route.keys[urlParamNum]
                            urlParamFind    = "/:" + urlParam.name
                            urlParamReplace = currentRoute.params[urlParam.name]

                            if (urlParam.optional) {
                                urlParamFind += "?"
                            }
                            else if (!urlParamReplace) {
                                throw "'" + urlParam.name + "' is required by navigation but cannot be found in current route"
                            }

                            urlParamReplace = urlParamReplace ? "/" + urlParamReplace : ""

                            url = url.replace(urlParamFind, urlParamReplace)
                        }

                        if (routeDef.navItem) {
                            routeDef.navItem.url = url
                            routeDef.navItem.selected = (currentRoute.originalPath == route.originalPath)
                        } else {
                            navItems.push({
                                url:      url,
                                label:    route.navigationLabel,
                                selected: (currentRoute.originalPath == route.originalPath)
                            })
                        }
                    }
                }

                $scope.navItems = navItems
            }

            upsertNavItems();

            $scope.$on('$routeChangeSuccess', upsertNavItems);
        }]
    }
}])
