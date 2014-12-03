var AppFakes = {
	http: {
		promise: function (data) {
			return {
				success: function (clb) {
					clb(data);
				},
				then: function (clb) {
					clb({data: data});
				}
			};
		},
		promiseSpy: function (spy, data) {
			return jasmine.createSpy(spy).and.callFake(function () {
				return AppFakes.http.promise(data);
			});
		},
		overlay: {
			set: jasmine.createSpy('set').and.callFake(function () {
			})
		}
	}
};


var AppFixtures = {

	tree: [
		{
			id: 1,
			"name": 'Root category #1',
			"nodes": [
				{
					id: 3,
					"name": '2-Level category #3',
					"nodes": []
				}
			]
		},
		{
			id: 2,
			"name": 'Root category #2',
			"nodes": []
		}
	]

};