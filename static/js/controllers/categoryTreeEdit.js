AppControllers.categoryTreeEdit = ['$scope', 'treeServer', '$modal', 'treeInit', 'treeDragDrop', 'treeNode', function ($scope, http, $modal, treeInit, treeDragDrop, node) {

	$scope.treeOptions = {
		dropped: treeDragDrop.dropped,
		accept: treeDragDrop.accept
	};

	angular.extend($scope, AppExtends.alerts());

	$scope.removeConfirm = function (scope) {
		node.init(scope);
		$scope.alertConfirm(
			'Remove category [' + node.title + '] confirmation',
			'Are your sure?',
			'i\'m sure, remove it',
			function () {
				$scope.alertBusy();
				http.removeById(node.id(), function () {
					$scope.setTreeAttributes({deleted: true});
					$scope.alertClose();
				});
			}
		);

	};

	$scope.countRootChild = function () {
		try {
			return $scope.$$childHead.$nodesScope.childNodes().length;
		} catch (e) {
			return 0;
		}
	};

	$scope.countRootData = function () {
		return $scope.data.length;
	};

	$scope.setTreeAttributes = function (data) {
		if (!node.scope) {
			return;
		}
		var $m = node.model;
		node.scope.safeApply(function () {
			if (typeof data.name !== 'undefined') {
				$m.title = data.name;
				$m.name = data.name;
			}
			if (typeof data.enabled === 'boolean') {
				$m.enabled = data.enabled;
			}
			if (typeof data.deleted === 'boolean') {
				$m.deleted = data.deleted;
			}
			if (typeof data.parent_id != 'undefined') {
				$m.parent_id = data.parent_id;
			}
			if (typeof data.symlink != 'undefined') {
				$m.symlink = data.symlink;
			}
			if (typeof data.symlink_id != 'undefined') {
				$m.symlink_id = data.symlink_id;
			}
			if (typeof data.type != 'undefined') {
				$m.type = data.type;
			}
			if (typeof data.category_type_id != 'undefined') {
				$m.category_type_id = data.category_type_id;
			}
		});
	};

	$scope.toggleEnabled = function (scope) {
		node.init(scope);
		http.enableById(node.id(), function () {
			$scope.setTreeAttributes({enabled: !node.model.enabled});
		});
	};

	$scope.addRootItem = function () {
		this.showFormNewItem();
	};

	$scope.showFormNewItem = function (scope) {
		node.init(scope);
		$scope.modalEditForm = $modal({
			title: 'Add category into: [' + node.title + ']',
			contentTemplate: 'template/category.new',
			template: 'template/category.modal',
			scope: $scope,
			show: true
		});

	};

	$scope.showFormEdit = function (scope) {
		node.init(scope);
		$scope.modalEditForm = $modal({
			title: 'Edit category [' + node.title + ']',
			contentTemplate: 'template/category.edit',
			template: 'template/category.modal',
			scope: $scope,
			show: true
		});
	};

	$scope.loadTagItems = function (query) {
		return http.findTags(query);
	};

	$scope.newSubItem = function (data, extras) {
		node.model.nodes.push({
			id: data.id,
			title: data.name,
			name: data.name,
			parent_id: data.parent_id,
			enabled: false,
			deleted: false,
			category_type_id: extras.category_type_id,
			type: extras.type,
			symlink_id: extras.symlink_id,
			symlink: extras.symlink,
			nodes: []
		});
		return this;
	};

	$scope.getChildNodes = function () {
		return this.$$childHead.childNodes();
	};

	$scope.collapseAll = function () {
		treeInit.rootScope().collapseAll();
	};

	$scope.expandStorage = function () {
		treeInit.treeInitCollapse();
	};

	$scope.expandAll = function () {
		treeInit.rootScope().expandAll();
	};

	$scope.myToggle = function (scope) {
		scope.toggle();
		this.storeExpandChange(scope);
	};

	$scope.storeExpandChange = function (scope) {
		treeInit.listModify(scope);
	};

	$scope.data = [];
	treeInit.getTree(function (result) {
		$scope.data = result;
		treeInit.initCollapse($scope);
		// loadingOverlay it is block html when server request in process
		http.overlay.set(treeInit.rootScope);
	});

}];