AppServices.treeNode = ['$http', 'treeInit', function ($http, treeInit) {

	this.scope = null;
	this.model = null;
	this.title = null;

	this.init = function (scope) {
		this.scope = (typeof scope == 'undefined')
			? null
			: scope;
		this.model = (this.scope)
			? scope.$modelValue
			: {id: 0, parent_id: 0, title: 'root', nodes: treeInit.getRootChild()};
		this.title = this.model.title;
		return this;
	};

	this.getScope = function () {
		return this.scope
			? this.scope
			: treeInit.rootScope();
	};

	this.id = function () {
		return this.model.id;
	};

	this.newSubItem = function (category, extras) {
		this.getScope().newSubItem(category, extras);
	};

	this.apply = function (data) {
		this.scope.setTreeAttributes(data);
	}

}];
