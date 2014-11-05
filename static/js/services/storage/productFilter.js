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
			return storage.get(sid);
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
				list[i].checked = (storageList.indexOf(list[i].id) >= 0);
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
			this.set(sid, [value]);
		}

	};

}];
