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
