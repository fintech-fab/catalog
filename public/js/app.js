var App;
(function () {
	'use strict';
	App = angular.module('treeApp', ['ui.tree', 'mgcrea.ngStrap', 'cgBusy', 'ngTagsInput', 'LocalStorageModule']);
})();

$.fn.ffCatAlert = function (title, text, buttons) {

	buttons = typeof buttons !== 'undefined' ? buttons : [];
	text = typeof text !== 'undefined' ? text : '';

	var div = this;
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
	ffCatApp.alertOpen(div);

};
var ffCatApp = {
	openedAlert: null,
	showErrors: function (res, id, model) {
		/** @namespace res.errors */
		if (typeof res == 'object' && typeof res.errors == 'object') {
			var error = '';
			jQuery.each(res.errors, function (key, value) {
				var $form = $('#' + id).find('form');
				var $input = $form.find('[ng-model="' + model + '.' + key + '"]');
				if ($input.length == 0) {
					error += value;
					return true;
				}
				var $div = $input.parent();
				$div.parent().addClass('has-error');
				$div.append('<div class="error">' + value + '</div>');
				$div.find('input,select,checkbox,radio').on('click', function () {
					var $div = $(this).parents('.has-error');
					$div.removeClass('has-error');
					$div.find('.error').remove();
				});
				return true;
			});

			if (error) {
				var $error = $('#' + id).parents('.modal-body').next();
				$error.html(error).show();
			}

			return true;
		}
		return false;
	},
	buttonLock: function (btn) {
		$('#' + btn).button('loading');
	},
	buttonUnlock: function (btn) {
		$('#' + btn).button('reset');
	},
	alert: function (mode, error) {

		switch (mode) {

			case 'fatal':

				$('.ff-cat-alert').ffCatAlert('Fatal server error', '<h3>' + error.message + '</h3> Page must be reload', [
					{
						title: 'Okay... reload page',
						type: 'warning',
						click: function () {
							ffCatApp.alertClose();
							window.location.reload();
						}
					}
				]);

				break;

			default:
				break;

		}

	},
	alertClose: function () {
		this.openedAlert.hide();
	},
	alertOpen: function (alert) {
		this.openedAlert = alert;
		this.openedAlert.show();
	}
};

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

App.service('treeNode', ['$http', 'treeInit', function ($http, treeInit) {

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

}]);

App.controller('categoryTreeEdit', ['$scope', '$http', '$modal', 'treeInit', 'treeDragDrop', 'treeNode', function ($scope, $http, $modal, treeInit, treeDragDrop, treeNode) {

	$scope.treeOptions = {
		dropped: treeDragDrop.dropped,
		accept: treeDragDrop.accept
	};

	$scope.removeConfirm = function (scope) {
		treeNode.init(scope);
		$('.ff-cat-alert').ffCatAlert(
			'Remove category [' + treeNode.title + '] confirmation', 'Are your sure?',
			[
				{
					type: 'default',
					title: 'close'
				},
				{
					type: 'primary',
					title: 'i\'m sure, remove it',
					click: function () {
						$(this).button('loading');
						$http.post('category/remove', {id: treeNode.model.id}).success(function () {
							$scope.setTreeAttributes({deleted: true});
							ffCatApp.alertClose();
						});
					}
				}
			]
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
		if (!treeNode.scope) {
			return;
		}
		var $m = treeNode.model;
		treeNode.scope.safeApply(function () {
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
		treeNode.init(scope);
		treeInit.rootScope().loadingOverlay =
			$http.post(
				'category/enable',
				{id: treeNode.model.id}
			)
				.success(function () {
					$scope.setTreeAttributes({enabled: !treeNode.model.enabled});
				});
	};

	$scope.addRootItem = function () {
		this.showFormNewItem();
	};

	$scope.showFormNewItem = function (scope) {
		treeNode.init(scope);
		$scope.modalEditForm = $modal({
			title: 'Add category into: [' + treeNode.title + ']',
			contentTemplate: 'template/category.new',
			template: 'template/category.modal',
			scope: $scope,
			show: true
		});

	};

	$scope.showFormEdit = function (scope) {
		treeNode.init(scope);
		$scope.modalEditForm = $modal({
			title: 'Edit category [' + treeNode.title + ']',
			contentTemplate: 'template/category.edit',
			template: 'template/category.modal',
			scope: $scope,
			show: true
		});
	};

	$scope.loadTagItems = function (query) {
		return $http.get('category/tags/autocomplete?term=' + query);
	};

	$scope.newSubItem = function (data, extras) {
		treeNode.model.nodes.push({
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
	});

}]);
App.controller('modalCategoryEdit', ['$scope', '$http', 'treeNode', function ($scope, $http, treeNode) {

	$http.get('rest/categories/item/' + treeNode.model.id)
		.then(function (res) {
			$scope.doUpdate(res.data);
		});

	$scope.save = function (category, btn) {
		ffCatApp.buttonLock(btn);
		category = (typeof category == 'undefined') ? {} : category;
		category._token = $('#category-token').val();
		$http.post(
			'category/update/' + $scope.category.id,
			category
		)
			.success(function (res) {
				if (!ffCatApp.showErrors(res, 'modalCategoryEdit', 'category')) {
					$scope.doUpdate(res);
				}
				ffCatApp.buttonUnlock(btn);
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
		treeNode.scope.setTreeAttributes(data);

	};

}]);
App.controller('modalCategoryNew', ['$scope', '$http', 'treeNode', function ($scope, $http, treeNode) {

	$scope.category = {};

	$scope.save = function (category, btn) {
		ffCatApp.buttonLock(btn);
		category = (typeof category == 'undefined') ? {} : category;
		category._token = $('#category-token').val();
		category.parent_id = treeNode.model.id;
		$http.post(
			'category/create',
			category
		)
			.success(function (res) {
				if (!ffCatApp.showErrors(res, 'modalCategoryNew', 'category')) {
					treeNode.getScope().newSubItem(res.category, res.extras);
					$scope.category = {};
				}
				ffCatApp.buttonUnlock(btn);
			});

	};

}]);
