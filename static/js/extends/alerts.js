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