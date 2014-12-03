AppControllers.productListFilter = [
	'$scope', '$routeParams', '$modal', 'productFilterStorage', 'productServer',
	function ($scope, $routeParams, $modal, storage, http) {

		$scope.fields = [
			'categories',
			'types',
			'tags',
			'enabled',
			'deleted',
			'created_at',
			'updated_at',
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

		$scope.deleted = function (value) {
			if (typeof value === 'undefined') {
				return storage.get('deleted');
			}
			var exists = storage.get('deleted');
			if (value === exists) {
				value = '';
			}
			storage.set('deleted', value);
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

		$scope.removeParam = function (name) {
			storage.set(name, null);
			this.emitChanged();
		};

		$scope.sizeOfParam = function (name) {
			var value = storage.get(name);
			return value && parseInt(value.length);
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