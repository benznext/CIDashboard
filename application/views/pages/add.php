<?php 
$atts = array(
		'class' => '',
		'data-bind' => 'submit: addPage', 
		'method' => 'POST',
		'onSubmit' => 'return false;',
		'id' => 'page_add_form'
);

$hidden 		= array('active'=>false);	
?>			
<?php echo form_open( '/pages/add', $atts, $hidden ); ?>
	<div class="row">
		<div class="col-sm-5">
			<div class="form-group">
				<label class="control-label" for="post_title"><span class="text-danger">*</span> Page Title</label>		  
				<?php $data = array(
						  'name'        => 'post_title',
						  'id'          => 'post_title',
						  'class'		=> 'form-control',
						  'value'		=> '',
						  'placeholder'	=> 'Enter tile'
						);
				echo form_input($data); ?>			
				<p class="help-block">Some help text</p>	
			</div>
			
			<div class="form-group">		
				<label class="control-label" for="server"><span class="text-danger">*</span> Server</label>
			   <?php 
					$servers = $this->servers_model->get_servers();				
				?>
				<select name="server" id="server"
					class="form-control" data-bind="BootstrapSelect:{}">
					<option data-hidden="true" value="">-- Select one --</option>
					<?php 
						$value = '';
						foreach($servers as $k=>$server):	
						$selected = strcasecmp($value, element('server', $server) )==0 ? 'selected' : '';
						echo '<option value="' . element('server', $server)  . '" ' . $selected . '>' . element('server', $server) . '</option>';
						endforeach;				
					?>	
				</select>
				<p class="help-block">Some help text</p>	
			</div>		
			
			<div class="form-group">
				<label class="control-label" for="post_description">Description</label>	
				<div class="text-center">
				<?php $data = array(
						  'name'        => 'post_description',
						  'id'          => 'post_description',
						  'class'		=> 'form-control',
						  'value'		=>  '',
						  'rows'			=>  '2',
						  'data-bind'	=> 'BootstrapWYSIhtml5:{ html: true, image: false, \'font-styles\': false, link: false }',
						  'placeholder'			=> 'Some description'
						);
				echo form_textarea($data); ?>			
				</div>
				<p class="help-block"></p>
			</div>
			
			<div class="form-group">	
				<label class="control-label">Template</label>
			   <?php 
					$value = '';
					$templates = $this->application->get_templates('application/views/pages/templates/');
					$atts = 'class="form-control selectpicker" 
					data-bind="BootstrapSelect:{}"';
					echo form_dropdown('styles_meta[template]', $templates, $value, $atts);
				?>
			</div>
			
			<div class="form-group">
				<label class="control-label">Who can see this?</label>
				<div class="checkbox">
					<?php
						$groups=$this->users->groups()->result_array();
						foreach ($groups as $group):?>							 
							<div class="checkbox checkbox-primary m-l-0">	
								<input type="checkbox" 
								id="<?=element('id', $group)?>" 
								name="users_meta[groups][]" 
								value="<?=element('id', $group)?>" 
								class=""/>
								<label for="<?=element('id', $group)?>">
									<?php echo htmlspecialchars($group['name'],ENT_QUOTES,'UTF-8');?>						 
								</label>
							</div>
					  <?php endforeach?>
				 </div>
			</div>
			
			
			<div class="form-group">	
				<label class="control-label" for="active">Activate</label>
				<div class="">
					<?php 						
						$data = array(
						'name'        => 'active',
						'id'          => 'active',
						'value'       => true,
						'checked'     => false,
						'style'       => 'margin:10px',
						'class'		  => 'm-0 p-absolute',
						'data-bind'	=> 'BootstrapSwitch:{ size: \'small\'}'
						);
					echo form_checkbox($data); ?>
				</div>
			</div>
			
			
		</div>
		<div class="col-sm-7">			
			<div class="form-group">		
				<label class="control-label">Skin</label>
			   <?php 
					$skins = $this->application->skins();
					$value = '';
				?>
				<div data-bind="CustomScrollbar: { 
							axis:'x', 
							theme:'dark',
							scrollbarPosition:'inside',
							autoExpandScrollbar:true,
							advanced:{
								autoExpandHorizontalScroll:true
								}
							}" class="m-t-10 max-height-150 o-hidden">
							
					<ul class="users-list clearfix skin-selector list-selection" data-skins="<?=htmlspecialchars(json_encode($skins), ENT_QUOTES, 'UTF-8')?>">
						<?php 
						foreach($skins as $skin=>$thumb){
						$selected = strcasecmp($value, $skin)==0;
						?>
							<li class="w-auto <?=$selected? 'active' : ''?>">					 
							  <label for="<?=$skin?>"><img src="<?=base_url($thumb)?>" class="b-radius-0 cursor-pointer list-select" width="120px"/></label>
							  <a href="javascript:void(0)" class="users-list-name"><?=$skin?></a>
							  <span class="radio radio-primary m-l-0 hidden">
								<input type="radio" class="" name="styles_meta[skin]" value="<?=$skin?>" id="<?=$skin?>" <?=$selected? 'checked' : ''?>/>
								<label for="<?=$skin?>"><?=$skin?></label>
							  </span>						  
							</li>
						<?php
						}
						?>					
					</ul>						
				</div>
									
			</div>
			
			
			<h4 class="m-b-20">Button Colours</h4>
			<?php  
				$buttons_sizes = $this->application->get_config('buttons_size', 'template');
				$buttons = $this->application->get_config('buttons', 'template'); 
				$button_color_options = array(
								'button_color_submit' => 'Submit',
								'button_color_add' => 'Add',
								'button_color_cancel' => 'Cancel',
								'button_color_delete' => 'Delete',
								'button_color_info' => 'Info'
							);
				$button_size_options = array(
								'button_size_tool' => 'Tool',
								'button_size_form' => 'Form',
								'button_size_modal' => 'Modal'
							);?>
			
			<?php 
			foreach($button_color_options as $opt_key=>$opt_label){
			?>
				<div class="form-group">	
					<label class="control-label"><?=$opt_label?></label>
					<div class="">	
						<ul class="button-selector list-inline list-selection">
							<?php 
							$value = '';		
							foreach($buttons as $key=>$button){
							$selected = strcasecmp($key, $value)==0;						
							?>
								<li class="w-auto m-0 p-0 <?=$selected? 'active' : '';?>">	
									<label for="<?=$opt_key. '_'.$key?>" class="btn btn-sm btn-flat <?=$key?> list-select"><?=$key?></label>
									<span class="radio radio-primary m-l-0 hidden">
										<input type="radio" class="" name="styles_meta[<?=$opt_key?>]" value="<?=$key?>" id="<?=$opt_key. '_'.$key?>" <?=$selected? 'checked' : '';?>/>
										<label for="<?=$opt_key. '_'.$key?>"><?=$key?></label>
									</span>						  
								</li>
							<?php
							}
							?>					
						</ul>					
					</div>		
				</div>			
			<?php
			}		
			?>
		</div>	
	</div>
	
	

	

<?=form_close()?>