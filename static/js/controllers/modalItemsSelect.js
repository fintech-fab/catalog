AppControllers.modalItemsSelect = ['$scope', function ($scope) {

	var $source = $scope.$parent;

	$scope.list = [];
	$scope.index = [];
	$scope.term = {
		busy: false,
		search: '',
		value: '',
		selected: false
	};
	$scope.busy = false;

	var entity = $source.selectListEntity;

	$source.getCollection(entity, function (data) {
		$scope.list = data;
		$scope.search = data;
		createIndex();
		$source.itemsSelectLoaded(entity, $scope.list);
	});

	$scope.toggleItem = function (id) {
		var node = this.list[this.index[id]];
		node.checked = !node.checked;
		$scope.busy = true;
		$source.itemsSelectToggle(entity, node.id, node.checked, function(){
			$scope.busy = false;
		});
	};


	$scope.filter = function(){

		if(this.term.busy && this.term.value !== this.term.search){
			this.term.busy = false;
			return true;
		}

		showByTerm();

		return true;

	};

	function createIndex(){
		for (var i = 0, qnt = $scope.list.length; i < qnt; i++) {
			$scope.index[$scope.list[i].id] = i;
		}
	}

	function showByTerm(){
		$scope.term.busy = true;
		var node,
			name,
			selected = $scope.term.selected,
			term = $scope.term.value.toUpperCase();
		$scope.term.search = $scope.term.value;
		for (var i = 0, qnt = $scope.list.length; i < qnt; i++) {
			if(!$scope.term.busy){
				showByTerm();
				return true;
			}
			node = $scope.list[i];
			name = node.name.toUpperCase();
			node.hidden = (
				(term.length > 0 && name.indexOf(term) < 0)
				||
				(selected && !node.checked)
			);
			if(!node.hidden){
				showByParent(node.parent_id);
			}
		}
		$scope.term.busy = false;
		$scope.term.search = '';
	}

	function showByParent(id){
		var node = $scope.list[$scope.index[id]];
		if(!node){
			return;
		}
		node.hidden = false;
		if(node.parent_id > 0){
			showByParent(node.parent_id)
		}
	}

}];