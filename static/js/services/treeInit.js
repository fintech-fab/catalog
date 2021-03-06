App.service('treeInit', ['$http', '$timeout', 'localStorageService', function ($http, $timeout, localStorageService) {

	var $this = this;
	this.treeScope = null;
	this.rootScopeEl = null;

	this.getTree = function (result) {
		$http.get('rest/categories/tree').then(function (res) {
			result(res.data);
		});
	};

	this.initCollapse = function (scope) {
		this.treeScope = scope;
		$timeout(this.waitingInitialTree, 200);
	};

	this.waitingInitialTree = function () {
		var initLength = $this.treeScope.countRootChild();
		var dataLength = $this.treeScope.countRootData();
		if (initLength > 0 && initLength == dataLength) {
			$this.treeInitCollapse();
		} else {
			$timeout($this.waitingInitialTree, 200);
		}
	};

	this.treeInitCollapse = function (root) {
		var parent = root ? root : $this.rootScope();
		var nodes = parent.getChildNodes();
		nodes.forEach(function (node) {
			if ($this.isCollapsed(node.$modelValue.id)) {
				node.collapse();
			} else {
				node.expand();
			}
			$this.treeInitCollapse(node);
		});
	};

	this.getCollapseStorage = function () {
		var list = localStorageService.get('treeCollapseList');
		return !list ? [] : list;
	};

	this.setCollapseStorage = function (list) {
		localStorageService.set('treeCollapseList', list);
	};

	this.listModify = function (scope) {
		var id = scope.$modelValue.id;
		var list = this.getCollapseStorage();
		if (!scope.collapsed) {
			list.push(id);
		} else {
			var i = list.indexOf(id);
			if (i != -1) {
				list.splice(i, 1);
			}
		}
		this.setCollapseStorage(list);
	};

	this.isCollapsed = function (id) {
		var list = this.getCollapseStorage();
		return list.indexOf(id) == -1;
	};

	this.rootScope = function () {
		if (!this.rootScopeEl) {
			this.rootScopeEl = angular.element(document.getElementById("tree-root")).scope();
		}
		return this.rootScopeEl;
	};

	this.getRootChild = function () {
		return this.treeScope.$$childHead.$nodesScope.$modelValue;
	};

}]);
