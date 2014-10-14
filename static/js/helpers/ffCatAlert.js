$.fn.ffCatAlert = function (title, text, buttons) {

	buttons = typeof buttons !== 'undefined' ? buttons : [];
	text = typeof text !== 'undefined' ? text : '';

	var div = this;
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
	ffCatApp.alertOpen(div);

};