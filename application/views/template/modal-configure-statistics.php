<!-- Modal -->
<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="Close">
	<span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title text-capitalize"><?=$modal_title;?></h4>
</div>
<div class="modal-body">
	<?=$this->load->view($modal_content);?>
</div>
<div class="modal-footer">
	<a data-dismiss="modal" class="btn btn-default btn-flat pull-left" type="button">Close</a>
	<a data-bind="
		css: { 'has-spinner active' : ajaxProcess},
		click: function(data, event){
			$(event.currentTarget)
			.closest('.modal-content')
			.find('.modal-body form')
			.trigger('submit');
			return false;
		}"	
	class="btn btn-primary btn-flat"
	>
		<span class="spinner"><i class="fa fa-spinner fa-spin"></i></span>	
		Save
	</a>
</div>


