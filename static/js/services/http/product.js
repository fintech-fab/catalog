AppServices.productServer = function ($http) {

	this.loadList = function (params, callback) {
		$http.post('product/list', params).success(callback);
	};

	this.loadCategories = function (callback) {
		$http.get('category/tree/simple').success(callback);
	};

	this.loadTags = function (callback) {
		$http.get('product/tags').success(callback);
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
