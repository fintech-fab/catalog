var AppControllers = {};
var AppServices = {};
var AppExtends = {};
AppExtends.alerts = function () {

	var opened = undefined;

	return {

		alertOpen: function (title, text, buttons) {
			buttons = typeof buttons !== 'undefined' ? buttons : [];
			text = typeof text !== 'undefined' ? text : '';

			var div = $('.ff-cat-alert');
			div.find('.title').html(title);
			div.find('.text').html(text);
			div.find('.buttons').html('');
			buttons.forEach(function (item) {
				var button = $('<button type="button" class="btn btn-' + item.type + '">' + item.title + '</button>');
				if (item.title == 'close' || item.title == 'Close') {
					button.on('click', function () {
						$(this).parents('.alert').hide();
					});
				} else {
					button.on('click', item.click);
				}
				button.appendTo(div.find('.buttons'));
			});
			opened = div;
			opened.removeClass('alert-warning').removeClass('alert-danger').addClass('alert-info');
			opened.show();
		},

		alertConfirm: function (title, text, buttonText, callback) {
			this.alertOpen(
				title,
				text,
				[
					{
						type: 'default',
						title: 'close'
					},
					{
						type: 'primary',
						title: buttonText,
						click: callback
					}
				]
			);
			opened.removeClass('alert-info').addClass('alert-warning');
		},

		alertClose: function () {
			opened.hide();
		},

		alertBusy: function () {
			opened.find('.btn-primary').button('loading')
		},

		alertError: function (title, text) {
			this.alertOpen(title, text, [
				{
					type: 'default',
					title: 'Okay...',
					click: function () {
						$(this).parents('.alert').hide();
					}
				}
			]);
			opened.removeClass('alert-info').addClass('alert-danger');
		}

	};

};
AppExtends.form = function (params) {

	var id = params["id"];
	var $form = null;
	var model = params["model"];

	return {

		errors: {},

		button: function () {

			this.$form();
			var $button = $form.find('.btn-primary');

			return {
				loading: function () {
					$button.button('loading');
				},
				reset: function () {
					$button.button('reset');
				}
			};
		},

		$form: function () {
			if (!$form || $form.length == 0) {
				$form = $('#' + id).find('form');
			}
		},

		showErrors: function (response) {
			if (typeof response.errors !== 'object') {
				return false;
			}
			this.errors = response.errors;
			return true;
		},

		token: function () {
			this[model] = this[model] || {};
			this[model]._token = $('#' + model + '-token').val();
		},

		cleanup: function () {
			this[model] = {};
			this.errors = {};
		}

	}
};
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

		$this.getDestChild().forEach(function (value) {
			if (value.id == id) {
				return false;
			}
			post.after = value.id;
			return true;
		});

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
AppServices.treeInit = ['treeServer', '$timeout', 'localStorageService', function (http, $timeout, storage) {

	var $this = this;
	this.treeScope = null;
	this.rootScopeEl = null;

	this.getTree = function (result) {
		http.loadTree(function (res) {
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
		var list = storage.get('treeCollapseList');
		return !list ? [] : list;
	};

	this.setCollapseStorage = function (list) {
		storage.set('treeCollapseList', list);
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

}];


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

	this.dragDropped = function (post, success, error) {
		this.overlay.over(
			$http.post('category/move/after', post).success(success).error(error)
		);
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
AppControllers.modalCategoryEdit = ['$scope', 'treeServer', 'treeNode', function ($scope, http, node) {

	$scope.category = {};

	angular.extend($scope, AppExtends.form({
		id: 'modalCategoryEdit',
		model: 'category'
	}));

	$scope.button().loading();
	http.getById(node.id(), function (res) {
		$scope.doUpdate(res.data);
		$scope.button().reset();
	});

	$scope.save = function () {
		$scope.button().loading();
		$scope.token();

		http.updateCategory(node.id(), $scope.category, function (res) {
			if (!$scope.showErrors(res)) {
				$scope.doUpdate(res);
			}
			$scope.button().reset();
		});

	};

	$scope.doUpdate = function (res) {

		$scope.types = res.types;
		$scope.category = res.category;
		$scope.extras = res.extras;
		$scope.category.category_tags = $scope.extras.tags;

		var data = jQuery.extend({}, res.category);
		data.symlink = res.extras.symlink;
		data.category_type_id = res.extras.category_type_id;
		data.type = res.extras.type;
		node.apply(data);

	};

}];

AppControllers.modalCategoryNew = ['$scope', 'treeServer', 'treeNode', function ($scope, http, node) {

	$scope.category = {};
	angular.extend($scope, AppExtends.form({
		id: 'modalCategoryNew',
		model: 'category'
	}));

	$scope.save = function () {
		$scope.button().loading();
		$scope.category.parent_id = node.id();

		http.createCategory($scope.category, function (res) {
			if (!$scope.showErrors(res)) {
				node.newSubItem(res.category, res.extras);
				$scope.cleanup();
			}
			$scope.button().reset();
		});

	};

}];

(function () {
	'use strict';

	var App = angular.module('treeApp', ['ui.tree', 'mgcrea.ngStrap', 'cgBusy', 'ngTagsInput', 'LocalStorageModule']);

	App.service('treeServer', AppServices.treeServer);
	App.service('treeInit', AppServices.treeInit);
	App.service('treeDragDrop', AppServices.treeDragDrop);
	App.service('treeNode', AppServices.treeNode);

	App.controller('modalCategoryNew', AppControllers.modalCategoryNew);
	App.controller('modalCategoryEdit', AppControllers.modalCategoryEdit);
	App.controller('categoryTreeEdit', AppControllers.categoryTreeEdit);

})();
