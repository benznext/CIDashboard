<?php 
		
	$av_options = $this->application->get_config('options', 'actions');
	$config		= $this->application->get_config('query', 'actions');
	$options	= array_merge($config, array());	
	$rule 		= $this->rules_model->get_rule(element('id', $parameters));	
	$rule_setting = unserialize(element('rule_settings', $rule));
	
	$atts = array(
			'class' => 'form-horizontal',
			'data-bind' => 'submit: $root.formSubmit', 
			'method' => 'POST',
			'onSubmit' => 'return false;',
			'id' => 'query_settings_form'
	);	
	$hidden 		= array('category' => element('category',$rule), 'active' => false);
	
?>

<?php echo form_open( '/profile/edit_rule/' . element('id',$rule) , $atts, $hidden ); ?>
		
			<p class="lead"><?=lang('query_description') ?></p>		

			<div class="form-group">
				<label class="col-sm-3 control-label" for="name">Name</label>
				<div class="col-sm-9 ">
					<?php
						$data = array(
								  'name'        => 'name',
								  'id'          => 'name',							  
								  'class'		=> 'form-control',
								  'value'		=>  element('name',$rule_setting)
								);
						echo form_input($data);
					?>
				</div>
			</div>
			
			<div class="form-group">
				<label class="col-sm-3 control-label" for="active">Activate</label>
				<div class="col-sm-9 checkbox">
					<?php $data = array(
						'name'        => 'active',
						'id'          => 'active',
						'value'       => true,
						'checked'     => element('active',$rule_setting),
						'style'       => 'margin:10px',
						'class'		  => 'm-0 p-absolute',
						'data-bind'	=> 'BootstrapSwitch:{ size: \'mini\' }'
						);
					echo form_checkbox($data); 
					
					?>
				</div>
			</div>	
<?php echo form_close(); ?>