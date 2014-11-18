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