<section class="content-header">
  <h1>
	<?php echo lang('add_media_heading');?>
	<small><?php echo lang('add_media_subheading');?></small>
  </h1>
   <?=$this->application->breadcrumb()?>
</section>
<?php 
	$atts = array(
			'class' => 'form-horizontal',
			//'data-bind' => 'submit: Media.addMedia', 
			'data-bind' => 'Dropzone: { url: \'' . base_url('media/add') . '\'}', 
			'onsubmit' => 'return false;', 
			'id' => ''
	);
	$hidden 		= array();		
?>
<section class="content">	
	<?php echo $message;?>
	<div class="dropzone widget widget-md" data-bind="Dropzone: { 
		url: '<?=base_url('media/add')?>'}"></div>
	<div class="row">
		<div class="col-md-9">
			
		</div>
	</div>
	<div class="col-md-3">	
	</div>	
</section>






