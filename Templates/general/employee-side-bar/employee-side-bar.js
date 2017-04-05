var $templateCell;
var $list;

function setup() {
	$templateCell = $('#template>li');
	$list = $('.employee-side-bar');
}

function initiateList(addItems) {

	for (var i = 0; i < addItems.length; i++) {
		addItem(addItems[i]);
	}

}

function addItem(add) {
	var name = add.name;
	var body = add.body;

	var $newCell = $templateCell.clone();
	$newCell.find('.name').html(name);
	$newCell.find('.shifts').html(body);

	$list.append($newCell);
}