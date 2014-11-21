AppDirectives.productFilterListSorting = ['productFilterStorage', function (storage) {
	return {
		restrict: "E",
		template: '<a href class="btn btn-default btn-xs" ng-click="sorting()">' +
		'<span ng-transclude></span>' +
		'<i class="fa sorting-ico" ng-class="{\'fa-caret-up\': sorting(\'asc\'),\'fa-caret-down\': sorting(\'desc\')}"></i>' +
		'</a>',
		transclude: true,
		scope: {},
		link: function (scope, element, attributes) {

			scope.name = attributes.name;
			scope.direction = undefined;
			scope.sorting = function (direction) {

				console.log('asdasdsd');

				var existName = storage.get('sortBy');
				var existDirection = storage.get('directionBy');

				if (direction) {
					return scope.name == existName && direction == existDirection;
				}

				if (existName != scope.name) {
					scope.direction = undefined;
				}

				if (!scope.direction) {
					scope.direction = 'asc';
				} else if (scope.direction === 'asc') {
					scope.direction = 'desc';
				} else if (scope.direction === 'desc') {
					scope.direction = undefined;
				}

				storage.set('sortBy', scope.name);
				storage.set('directionBy', scope.direction);
				scope.$parent.$broadcast('$productFilterListSortingChange');


			};

		}
	};
}];