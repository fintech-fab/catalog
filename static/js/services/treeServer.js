AppServices.treeServer = function ($http) {

	this.loadTree = function (callback) {
		$http.get('rest/categories/tree').then(callback);
	};

	this.removeById = function (id, callback) {
		$http.post('category/remove', {id: id}).success(callback);
	};

	this.enableById = function (id, callback) {
		this.overlay.over(
			$http.post('category/enable', {id: id}).success(callback)
		);
	};

	this.findTags = function (term) {
		return $http.get('category/tags/autocomplete?term=' + term);
	};

	this.createCategory = function (category, callback) {
		$http.post('category/create', category).success(callback);
	};

	this.getById = function (id, callback) {
		$http.get('rest/categories/item/' + id).then(callback);
	};

	this.updateCategory = function (id, category, callback) {
		$http.post('category/update/' + id, category).success(callback);
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
