AppServices.treeDragDrop = ['treeServer', function (http) {

	this.scope = null;
	this.init = function (scope) {
		this.scope = scope;
		return this;
	};
	this.event = null;

	var $this = this;
	angular.extend(this, AppExtends.alerts());

	this.dropped = function (event) {

		$this.setEvent(event);
		var id = $this.getSourceModelId();

		var post = {id: id, after: 0, pid: $this.getDestId()};
		var list = $this.getDestChild();
		for (var i = 0, qnt = list.length; i < qnt; i++) {
			if (list[i].id == id) break;
			post.after = list[i].id;
		}

		http.dragDropped(
			post,
			function (res) {
				$this.setSourceModelParentId(res.parent_id);
			},
			function (data) {
				$this.alertError('Server Error', data.error.message)
			}
		);

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

}];