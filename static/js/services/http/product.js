AppServices.productServer = function ($http) {

	this.loadList = function (params) {
		console.log('<< reload product list >>');
		return $http.post('product/list', params);
	};

	this.loadCategories = function () {
		return $http.get('category/tree/simple');
	};

	this.loadTags = function () {
		return $http.get('product/tags');
	};

	this.loadTypes = function () {
		return $http.get('product/types');
	};

	this.overlay = function () {

		var scope;

		return {
			/**
			 * @param rootScope function, must returns tree root scope
			 */
			set: function (rootScope) {
				scope = rootScope;
			},
			/**
			 * @param request promise
			 */
			over: function (request) {
				// cg-busy="loadingOverlay" called in directive
				scope().loadingOverlay = request;
			}
		};

	}();

};
