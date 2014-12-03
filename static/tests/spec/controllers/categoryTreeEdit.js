'use strict';

describe('Controller: categoryTreeEdit', function () {

	beforeEach(module('treeApp'));

	var treeApp, scope, treeServer, treeServerMock, q;

	// mocking treeServer
	beforeEach(function () {
		module(function ($provide) {
			treeServer = function () {
				this.loadTree = AppFakes.http.promiseSpy('loadTree', AppFixtures.tree);
				this.overlay = AppFakes.http.overlay;
			};
			treeServerMock = $provide.service('treeServer', treeServer);
		});
		inject(function (treeServer) {
			treeServerMock = treeServer;
		});
	});

	beforeEach(inject(function ($controller, $rootScope, $q) {
		q = $q;
		scope = $rootScope.$new();
		treeApp = $controller('categoryTreeEdit', {
			$scope: scope,
			http: treeServerMock
		});
	}));

	it('test', function () {
		expect(scope.data.length).toBe(2);
		expect(treeServerMock.loadTree).toHaveBeenCalled();
		expect(AppFakes.http.overlay.set).toHaveBeenCalled();
	});

});