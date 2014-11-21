var AppControllers = {};
var AppServices = {};
var AppExtends = {};
var AppDirectives = {};
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
AppServices.treeServer = function ($http) {

	this.loadTree = function () {
		console.log('treeServer.loadTree');
		return $http.get('rest/categories/tree');
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
AppServices.treeInit = ['treeServer', '$timeout', 'localStorageService', function (http, $timeout, storage) {

	var $this = this;
	this.treeScope = null;
	this.rootScopeEl = null;

	this.getTree = function () {
		this.clear();
		return http.loadTree();
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

	this.clear = function () {
		this.treeScope = null;
		this.rootScopeEl = null;
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

AppServices.productFilterStorage = ['localStorageService', function (storage) {

	return {

		replace: function (sid, value, checked) {

			value = value || null;
			if (null === value) {
				return;
			}

			var
				list = this.get(sid) || [],
				exists = list.indexOf(value);

			if (exists >= 0) {
				list.splice(exists, 1);
			}

			if (checked) {
				list.push(value);
			}

			this.set(sid, list);

		},

		get: function (sid) {
			sid = 'PFS.' + sid;
			var value = storage.get(sid);
			if (typeof value === 'number') {
				value += '';
			}
			return value;
		},

		set: function (sid, value) {
			sid = 'PFS.' + sid;
			return storage.set(sid, value);
		},

		checked: function (sid, value) {
			var list = this.get(sid) || [];
			return list.indexOf(value) >= 0;
		},

		setChecked2List: function (sid, list) {
			var storageList = this.get(sid);
			for (var i = 0, qnt = list.length; i < qnt; i++) {
				list[i].$checked = (storageList && storageList.indexOf(list[i].id) >= 0);
			}
		},

		post: function (fields) {
			fields = fields || [];
			var post = {};
			for (var i = 0, qnt = fields.length; i < qnt; i++) {
				post[fields[i]] = this.get(fields[i]);
			}
			return post;
		},

		initList: function (sid, value) {
			this.set(sid, value);
		}

	};

}];

AppDirectives.productFilterListSorting = ['productFilterStorage', function (storage) {
	return {
		restrict: "E",
		template: '<a href class="btn btn-default btn-xs" ng-click="sorting()">' +
		'<span ng-transclude></span>' +
		'<i class="fa sorting-ico" ng-class="{\'fa-caret-up\': sorting(\'asc\'),\'fa-caret-down\': sorting(\'desc\')}"></i>' +
		'</a>',
		transclude: true,
		scope: {},
		link: function (scope, element, attributes) {

			scope.name = attributes.name;
			scope.direction = undefined;
			scope.sorting = function (direction) {

				console.log('asdasdsd');

				var existName = storage.get('sortBy');
				var existDirection = storage.get('directionBy');

				if (direction) {
					return scope.name == existName && direction == existDirection;
				}

				if (existName != scope.name) {
					scope.direction = undefined;
				}

				if (!scope.direction) {
					scope.direction = 'asc';
				} else if (scope.direction === 'asc') {
					scope.direction = 'desc';
				} else if (scope.direction === 'desc') {
					scope.direction = undefined;
				}

				storage.set('sortBy', scope.name);
				storage.set('directionBy', scope.direction);
				scope.$parent.$broadcast('$productFilterListSortingChange');


			};

		}
	};
}];
AppDirectives.selectListCallback = function() {
	return {
		templateUrl: 'template/product.selector',
		controller: AppControllers.modalItemsSelect
	};
};
AppControllers.categoryTreeEdit = ['$route', '$scope', 'treeServer', '$modal', 'treeInit', 'treeDragDrop', 'treeNode', function ($route, $scope, http, $modal, treeInit, treeDragDrop, node) {

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
	treeInit.getTree().then(function (result) {
		$scope.data = result.data;
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

AppControllers.modalItemsSelect = ['$scope', function ($scope) {

	var $source = $scope.$parent;
	var back = $source.backSelector;
	var entity = back.entity();

	$scope.value = '';
	$scope.selected = false;
	$scope.busy = false;

	$scope.list = function (s) {

		var
			items = [],
			index = [],
			search = '';

		return {
			set: function (data) {
				items = data;
				search = data;
				this.index();
			},
			get: function (i) {
				i = (i === 0 || i > 0) ? i : null;
				if (null === i) {
					return items;
				} else {
					return items[i];
				}
			},
			index: function () {
				for (var i = 0, qnt = items.length; i < qnt; i++) {
					index[items[i].id] = i;
				}
			},
			node: function (id) {
				return items[index[id]];
			},
			busy: function () {
				s.busy = true;
			},
			free: function () {
				s.busy = false;
			},
			isBusy: function () {
				return s.busy;
			},
			value: function () {
				return s.value;
			},
			search: function (val) {
				if (typeof val !== 'undefined') {
					search = val;
				}
				return search;
			},
			selected: function () {
				return s.selected;
			},
			hidden: function (checked, needle) {
				var term = this.value().toUpperCase();
				needle = needle.toUpperCase();
				return (
				(term.length > 0 && needle.indexOf(term) < 0)
				||
				(this.selected() && !checked)
				);
			}
		};

	}($scope);

	back.collection(entity).success(function (data) {
		$scope.list.set(data);
		back.ready(entity, $scope.list.get());
	});

	$scope.$on('$productListFilterChangeSource', function () {
		back.ready(entity, $scope.list.get());
	});

	$scope.toggleItem = function (id) {
		var node = this.list.node(id);
		node.$checked = !node.$checked;
		this.list.busy();
		back.toggle(entity, node.id, node.$checked)
			.then(function () {
				$scope.list.free();
			});
	};

	$scope.filter = function () {
		if (this.list.isBusy() && this.list.value() !== this.list.search()) {
			this.list.free();
			return true;
		}
		showByTerm(this.list);
		return true;
	};

	function showByTerm(l) {
		var node;
		l.busy();
		l.search(l.value());
		for (var i = 0, qnt = l.get().length; i < qnt; i++) {
			if (!l.isBusy()) {
				showByTerm(l);
				return true;
			}
			node = l.get(i);
			node.$hidden = l.hidden(node.$checked, node.name);
			if (!node.$hidden) {
				showByParent(l, node.parent_id);
			}
		}
		l.free();
		l.search('');
	}

	function showByParent(l, id) {
		var node = l.node(id);
		if (!node) {
			return;
		}
		node.$hidden = false;
		if (node.parent_id > 0) {
			showByParent(l, node.parent_id)
		}
	}

}];
AppControllers.productListEdit = ['$scope', '$route', '$location', 'productServer', 'productFilterStorage', function ($scope, $route, $location, http, filter) {

	$scope.data = [];
	$scope.parseBack = null;
	$scope.lastUrl = null;

	$scope.load = function (params) {

		params = params || [];
		var values = filter.post(params);
		var changed = $scope.changeUrl(values);

		if (changed) {
			var promise = http.loadList(values);
			promise.success(function (result) {
				$scope.data = result.data;
			});
			return promise;
		}

	};

	$scope.changeUrl = function (values) {
		var url = $location.path(),
			delimiter = '?';
		for (var key in values) {
			if (!values.hasOwnProperty(key)) {
				continue;
			}
			if (!values[key] || (values[key] && values[key].length == 0)) {
				continue;
			}
			if (values[key].length > 300) {
				throw "too long parameter value";
			}
			url = url + delimiter + key + '=' + values[key];
			delimiter = '&';
		}

		if (url != $scope.lastUrl) {
			$location.url(url);
			$scope.lastUrl = url;
			return true;
		}
		return false;
	};

	$scope.productListFilterChanged = function (fields, parseBack) {
		this.parseBack = parseBack || this.parseBack;
		return $scope.load(fields);
	};

	$scope.$on('$locationChangeSuccess', function () {

		console.log('$locationChangeSuccess');
		console.log('parseBack: ' + typeof $scope.parseBack);

		if (typeof $scope.parseBack === 'function') {
			console.log('parseBack!');
			$scope.parseBack($scope.parseUrl());
		}
	});

	$scope.parseUrl = function () {

		var hash = $location.url(),
			parameters = hash.split('?')[1],
			items = ( parameters && parameters.split('&') ) || [],
			params = {};

		for (var i = 0, qnt = items.length, part; i < qnt; i++) {
			part = items[i] && items[i].split('=');
			if (part) {
				params[part[0]] = part[1];
			}
		}

		return params;

	};

}];
AppControllers.productListFilter = [
	'$scope', '$routeParams', '$modal', 'productFilterStorage', 'productServer',
	function ($scope, $routeParams, $modal, storage, http) {

		$scope.fields = [
			'categories',
			'types',
			'tags',
			'enabled',
			'name',
			'sid',
			'code',
			'sortBy',
			'directionBy'
		];

		$scope.characterFields = [
			'name',
			'sid',
			'code'
		];

		$scope.form = {};

		$scope.initParams = function (p) {

			storage.set('tags', p.tags && p.tags.split(',') || null);
			storage.set('categories', p.categories && p.categories.split(',') || null);
			storage.set('types', p.types && p.types.split(',') || null);
			storage.set('enabled', (p.enabled && p.enabled.length == 1) ? p.enabled : '');
			storage.set('sortBy', p.sortBy);
			storage.set('directionBy', p.directionBy);

			this.initCharParams(p);
		};

		$scope.initCharParams = function (p) {
			var list = this.characterFields;
			for (var i = 0, qnt = list.length; i < qnt; i++) {
				this.form[list[i]] = decodeURIComponent(p[list[i]] || '');
				storage.set(list[i], this.form[list[i]]);
			}
		};

		$scope.emitChanged = function () {
			return this.$parent.productListFilterChanged(this.fields, this.changeSource);
		};

		$scope.changeSource = function (p) {
			$scope.initParams(p);
			$scope.emitChanged();
			$scope.$broadcast('$productListFilterChangeSource');
		};

		$scope.showSelectCategoryForm = function () {
			$scope.showFilterForm('categories', 'Select a category');
		};

		$scope.showSelectTagsForm = function () {
			$scope.showFilterForm('tags', 'Check a labels');
		};

		$scope.showSelectTypeForm = function () {
			$scope.showFilterForm('types', 'Check a types');
		};

		$scope.$on('$productFilterListSortingChange', function () {
			$scope.emitChanged();
		});

		$scope.enabled = function (value) {
			if (typeof value === 'undefined') {
				return storage.get('enabled');
			}
			var exists = storage.get('enabled');
			if (value === exists) {
				value = '';
			}
			storage.set('enabled', value);
			this.emitChanged();
		};

		$scope.submit = function () {
			var changed = false;

			var list = this.characterFields;
			for (var i = 0, qnt = list.length; i < qnt; i++) {
				var item = storage.get(list[i]);
				if (item !== this.form[list[i]]) {
					changed = true;
					storage.set(list[i], this.form[list[i]]);
				}
			}

			if (changed) {
				this.emitChanged();
			}
		};

		$scope.showFilterForm = function (entity, title) {
			$scope.backSelector.entity(entity);
			$scope.modalEditForm = $modal({
				title: title,
				template: 'template/product.select-list-callback',
				scope: $scope,
				show: true
			});
			var listener = $scope.$on('modal.hide', function (e) {
				listener();
				e.targetScope.$destroy();
				$scope.modalEditForm.destroy();
			});
		};

		$scope.backSelector = function () {

			var entity = null;

			return {
				entity: function (sid) {
					if (typeof sid !== 'undefined') {
						entity = sid;
					}
					return entity;
				},
				ready: function (sid, list) {
					storage.setChecked2List(sid, list);
				},
				toggle: function (sid, id, checked) {
					storage.replace(sid, id, checked);
					return $scope.emitChanged();
				},
				collection: function (name) {
					switch (name) {
						case 'categories':
							return http.loadCategories();
							break;
						case 'tags':
							return http.loadTags();
							break;
						case 'types':
							return http.loadTypes();
							break;
						default:
							return null;
							break;
					}
				}
			}

		}();

		$scope.initParams($routeParams);
		$scope.emitChanged();

	}];
(function () {
	'use strict';

	var App = angular.module('treeApp', ['ui.tree', 'mgcrea.ngStrap', 'cgBusy', 'ngTagsInput', 'LocalStorageModule', 'ngRoute']);

	App.config(function ($routeProvider) {
		$routeProvider.
			when('/', {
				templateUrl: 'template/category.index',
				controller: 'categoryTreeEdit'
			}).
			when('/products', {
				templateUrl: 'template/product.index',
				controller: 'productListEdit',
				reloadOnSearch: false
			}).
			otherwise({
				redirectTo: '/'
			});
	});

	App.service('productServer', AppServices.productServer);
	App.service('treeServer', AppServices.treeServer);
	App.service('treeInit', AppServices.treeInit);
	App.service('treeDragDrop', AppServices.treeDragDrop);
	App.service('treeNode', AppServices.treeNode);
	App.service('productFilterStorage', AppServices.productFilterStorage);

	App.controller('modalCategoryNew', AppControllers.modalCategoryNew);
	App.controller('modalCategoryEdit', AppControllers.modalCategoryEdit);
	App.controller('categoryTreeEdit', AppControllers.categoryTreeEdit);
	App.controller('productListEdit', AppControllers.productListEdit);

	var ctrl = App.controller('productListFilter', AppControllers.productListFilter);
	ctrl.directive('selectListCallback', AppDirectives.selectListCallback);
	ctrl.directive('productFilterListSorting', AppDirectives.productFilterListSorting);

})();
