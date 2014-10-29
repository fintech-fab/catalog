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