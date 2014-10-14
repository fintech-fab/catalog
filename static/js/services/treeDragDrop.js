App.service('treeDragDrop', ['$http', 'treeInit', function ($http, treeInit) {

	this.scope = null;
	this.init = function (scope) {
		this.scope = scope;
		return this;
	};
	this.event = null;

	var $this = this;
	this.dropped = function (event) {

		$this.setEvent(event);
		var id = $this.getSourceModelId();

		var post = {id: id, after: 0, pid: $this.getDestId()};

		var url = 'category/move/after';
		jQuery.each($this.getDestChild(), function (key, value) {
			if (value.id == id) {
				return false;
			}
			post.after = value.id;
			return true;
		});

		treeInit.rootScope().loadingOverlay = $http.post(url, post).success(function (res) {
			$this.setSourceModelParentId(res.parent_id);
		}).error(function (data) {
			ffCatApp.alert('fatal', data.error);
		});
	};

	this.accept = function (sourceNodeScope, destNodesScope) {
		var destScope = destNodesScope.$nodeScope;
		return !(destScope && destScope.$modelValue && destScope.$modelValue.symlink_id > 0);
	};

	this.getDestId = function () {
		return this.event.dest.nodesScope.$parent.$modelValue
			? this.event.dest.nodesScope.$parent.$modelValue.id
			: 0;
	};

	this.getSourceModelId = function () {
		return this.event.source.nodeScope.$modelValue.id;
	};

	this.setSourceModelParentId = function (id) {
		return this.event.source.nodeScope.$modelValue.parent_id = id;
	};

	this.getDestChild = function () {
		return this.event.dest.nodesScope.$modelValue;
	};

	this.setEvent = function (event) {
		$this.init(event.source.nodeScope);
		this.event = event;
	};

}]);
