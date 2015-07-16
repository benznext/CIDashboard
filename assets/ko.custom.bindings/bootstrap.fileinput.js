ko.bindingHandlers.BootstrapFileInput= {
	init: function(element, valueAccessor, allBindingsAccessor){	
		var typed = false;
		var options =  jQuery.extend(valueAccessor(), {
							onChange: function (cm) {
								typed = true;
								allBindingsAccessor().value(cm.getValue());
								typed = false;
							}
						});
						
						
		
		ko.utils.domData.set(element, "options", options);		
	},
		
		
		
		
	update: function(element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) {
		var opts = ko.utils.domData.get(element, 'options');
		var $root = bindingContext.$root;
		
		
		$(element)
		.fileinput(opts)
		.on("filebatchselected", function(event, files) {
			// trigger upload method immediately after files are selected
			$(element).fileinput("upload");
		});
		
		
		
		
		
	}
}