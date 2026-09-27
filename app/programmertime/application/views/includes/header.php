<header>

	<style>
/*
		.header-main #programmer_time{
			color: <?php echo $this->session->userdata('color'); ?> !important;
		}

		.menu-main .glyphicon{
			color: <?php echo $this->session->userdata('color'); ?> !important;
		}
*/
		.off{
			visibility: hidden !important;
		}

		.gray{
			color:#CCC;
		}

	</style>

	<?php if($this->session->flashdata("just_logged_in")){ ?>

	<script>
	(function() {
	  jQuery(function($) {
	    var $characters, $claim, $claimCursor, claims, createElements, delay, drawFrame, frame, index, mode, pos;

	    claims = [
		'Programmer Time '];
		
	    index = 0;
	    frame = 0;
	    pos = 0;
	    mode = 0;
	    delay = 10;
	    $claim = $('#programmer_time');
	    $claimCursor = null;
	    $characters = null;
	    createElements = function() {
	      var c, _i, _len, _ref;

	      _ref = claims[index].split('');
	      for (_i = 0, _len = _ref.length; _i < _len; _i++) {
	        c = _ref[_i];

	        if(_i > 0 && _i < 10){
	        	$claim.append("<span id='i_"+_i+"' class='off gray'>" + c + "</span>");
	        }else{
	        	$claim.append("<span id='i_"+_i+"' class='off'>" + c + "</span>");
	        }
	      }
	      $claimCursor = $('<span id="claim_cursor" class="off gray"> _</span>');
	      $claim.append($claimCursor);
	      return $characters = $claim.children();
	    };
	    createElements();
	    drawFrame = function() {
	      var $character;

	      $character = $characters.eq(pos);
	      if ($character.hasClass('off')) {
	        $character.removeClass('off');
	      } else {
	        $character.addClass('off');
	      }
	      if (pos < claims[index].length) {
	        $claimCursor.addClass('off');
	      } else {
	        if (Math.floor(frame / 10) % 2 === 0) {
	          $claimCursor.addClass('off');
	        } else {
	          $claimCursor.removeClass('off');
	        }
	      }
	      if (mode === 0) {
	        if (pos < claims[index].length) {
	          pos++;
	        }
	      } else {
	        if (pos > 0) {
	          pos--;
	        } else {
	          mode = 1 - mode;
	          index++;
	          index %= claims.length;
	          $claim.empty();
	          createElements();
	        }
	      }
	      frame++;
	      // if (frame % delay === 0) {
	      //   mode = 1 - mode;
	      // }
	    };
	    return window.setInterval(drawFrame, 2500 / 25);
	  });

	}).call(this);
	</script>

	<?php } ?>

	<div id="header-main">
		<a class="ptime_logo" href="<?php echo base_url(); ?>">
			<?php if($this->session->flashdata("just_logged_in")){ ?>
			<div id="programmer_time"></div>

			<?php }else{ ?>

			<div id="programmer_time" class="visible-lg visible-md visible-sm">P<span style="color:#CCC;" >rogrammer</span> Time <span style="color:#CCC;"><span id="text"></span></span></div>
			<div id="programmer_time" class="hidden-lg hidden-md hidden-sm">P Time <span style="color:#CCC;"><span id="text_mobile"></span></span></div>

			<?php } ?>
		</a>
		<div id='image_user_div'>
			<a href="<?php echo base_url('user/view'); ?>">
				<img src="<?php echo base_url('assets/images/users/'.$this->session->userdata('image')); ?>" class="img-thumbnail img-circle" id="user_image" style="background-color:<?php echo $this->session->userdata('color'); ?>;" />
			</a>
		</div>

	</div>

	<!-- <button id="hide-header" rel="1">HIDE</button> -->

	<script>
	// $(document).ready(function(){
	// 	$("#hide-header").on('click', function(){
	// 			$("#header-main").toggle(1000);
	// 	});
	// });
	</script>

	
	
	<div class="menu-main">
	
	<ul class="nav nav-tabs">

		<li class="<?php if($this->session->userdata('dashboard')){echo 'active';} ?>" style="margin-left: 5px;">
			<a href="<?php echo base_url('dashboard/'); ?>" id="dashboard" title="<?php echo lang('home_page'); ?>">
				<i class='glyphicon glyphicon-home'></i>
			</a>
		</li>

		<li class="dropdown <?php if($this->session->userdata('clients')){echo 'active';}?>">
			
			<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_client'); ?>">
				<i class='glyphicon glyphicon-user'></i> <?php echo lang('clients'); ?> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">
				<li>
					<a tabindex="-1" href="<?php echo base_url('client/'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('client_list'); ?>
					</a>
				</li>

				<?php if($this->session->userdata('can_create_client')){ ?>

					<li class="divider"></li>

					<li>
						<a tabindex="-1" href="<?php echo base_url('client/create'); ?>">
							<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('menu_new_client'); ?>
						</a>
					</li>
				<?php } ?>
			</ul>
		</li>

		<li class="dropdown <?php if($this->session->userdata('projects')){echo 'active';}?>">

			<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_project'); ?>">
				<i class='glyphicon glyphicon-file'></i> <?php echo lang('projects'); ?> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">
				<li class="dropdown-header"><?php echo lang('menu_dashboard'); ?></li>

				<!--<li><a tabindex="-1" href="<?php echo base_url('dashboard/'); ?>"><i class='glyphicon glyphicon-home'></i> Dashboard</a></li>-->

				<li>
					<a tabindex="-1" href="<?php echo base_url('dashboard/calendar/'); ?>">
						<i class='glyphicon glyphicon-calendar'></i> <?php echo lang('calendar'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li class="dropdown-header">
					<?php echo lang('projects'); ?>
				</li>

					<li>
						<a tabindex="-1" href="<?php echo base_url('project/list'); ?>">
							<i class='glyphicon glyphicon-folder-close'></i> <?php echo lang('project_list'); ?></a></li>

							<?php if($this->session->userdata('can_create_project')){ ?>
								<li>
									<a tabindex="-1" href="<?php echo base_url('project/create'); ?>">
										<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('menu_new_project'); ?>
									</a>
								</li>
							<?php } ?>
				
					<?php if($this->session->userdata('can_log_time')){ ?>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('time_entry/list'); ?>">
								<i class='glyphicon glyphicon-time'></i> <?php echo lang('time_entry_list'); ?>
							</a>
						</li>

					<?php }
					if($this->session->userdata('can_log_payment')){ ?>
						<!--<li><a tabindex="-1" href="<?php echo base_url('finance/list'); ?>"><i class='glyphicon glyphicon-usd'></i> Payment List</a></li>-->
					<?php } ?>
			</ul>
		</li>
		
		<?php if($this->session->userdata('confirmed') == 'yes'){ ?>


			<?php if($this->session->userdata('can_log_time')){ ?>

				<!--<li class="<?php if($this->session->userdata('tasks')){echo 'active';} ?>">
					<a href="<?php echo base_url('task/list/'.$this->session->userdata('id')); ?>" title="<?php echo lang('alt_task'); ?>">
						<i class='glyphicon glyphicon-list-alt'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('task'); ?></span>
						<?php 
							$number_tasks = $this->task_model->get_tasks(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'count', 'completed');
							if($number_tasks > 0)echo '<span class="badge">'.$number_tasks.'</span>';
						?>
					</a>


				</li> -->

				<li class="dropdown <?php if($this->session->userdata('tasks')){echo 'active';}?>">

					<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_task'); ?>">
						<i class='glyphicon glyphicon-list-alt'></i> <?php echo lang('task'); ?> 
						<?php 
							$number_tasks = $this->task_model->get_tasks(0, $this->session->userdata('id'), "ASC", 0, 0, "", 'count', 'completed');
							$number_tasks_totals = $this->task_model->get_tasks(0, 0, "ASC", 0, 0, "", 'count', 'completed');
							if($number_tasks > 0)echo '<span class="badge">'.$number_tasks.'</span>';
						?>
						<span class="caret"></span>
					</a>
					
					<ul class="dropdown-menu">

						<li>
							<a tabindex="-1" href="<?php echo base_url('task/list/'.$this->session->userdata('id')); ?>">
								<i class='glyphicon glyphicon-folder-close'></i> My Tasks 
								<?php if($number_tasks > 0) echo '<span class="badge">'.$number_tasks.'</span>'; ?>
							</a>
						</li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('task/list/'); ?>">
								<i class='glyphicon glyphicon-folder-close'></i> All Tasks
								<?php if($number_tasks_totals > 0) echo '<span class="badge">'.$number_tasks_totals.'</span>'; ?>
							</a>
						</li>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('task/create/'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> New Task
							</a>
						</li>


						
					</ul>
				</li>

				<li class="<?php if($this->session->userdata('time_entries')){echo 'active';} ?>">
					<a href="<?php echo base_url('time_entry/'); ?>" id="log_time_entry" title="<?php echo lang('alt_time_entry'); ?>">
						<i class='glyphicon glyphicon-time'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('time_entry'); ?></span>
						<?php 
							$open_time_entry = $this->time_entry_model->open_time_entry($this->session->userdata('id'));
							if($open_time_entry->num_rows() > 0)echo '<span class="badge">1</span>';
						?>
					</a>
				</li>

			<?php } ?>
			
			<?php if($this->session->userdata('can_log_payment')){ ?>

				<li class="<?php if($this->session->userdata('finance')){echo 'active';} ?>" id="log_payment">
					<a href="<?php echo base_url('finance/'); ?>" title="<?php echo lang('alt_finance'); ?>">
						<i class='glyphicon glyphicon-usd'></i> <span class="visible-lg-in visible-md-in"><?php echo lang('finance'); ?></span>
					</a>
				</li>

			<?php } ?>
			
			<?php if($this->session->userdata('can_send_report')){ ?>

				<li class="<?php if($this->session->userdata('reports')){echo 'active';} ?>">
					<a href="<?php echo base_url('report/'); ?>" id="reports" title="<?php echo lang('alt_report'); ?>">
						<i class='glyphicon glyphicon-file'></i> <span class="visible-lg-in"><?php echo lang('reports'); ?></span>
					</a>
				</li>

			<?php } ?>
			
			<!--<li class="<?php if($this->session->userdata('message')){echo 'active';} ?>">
				<a href="<?php echo base_url('message/'); ?>" id="messages" title="Inbox">
					<i class='glyphicon glyphicon-envelope'></i> <span class="visible-lg-in">Messages</span>
						<?php
							$messages = $this->message_model->get_unread_messages();
							if($messages->num_rows() > 0) echo '<span class="badge">'. $messages->num_rows() .'</span>';
						?>
				</a>
			</li>-->
			
			<?php if($this->session->userdata('can_create_user')){ ?>

				<li class="dropdown <?php if($this->session->userdata('users')){echo 'active';}?>">

					<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('alt_user'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('users'); ?> <span class="caret"></span>
					</a>
					
					<ul class="dropdown-menu">

						<li>
							<a tabindex="-1" href="<?php echo base_url('user/list'); ?>">
								<i class='glyphicon glyphicon-user'></i> <?php echo lang('user_list'); ?>
							</a>
						</li>

						<li class="divider"></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('user/create'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('menu_new_user'); ?>
							</a>
						</li>

						<li class="dropdown-header"><?php echo lang('access_levels'); ?></li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('access_level/create'); ?>">
								<i class='glyphicon glyphicon-pencil'></i> <?php echo lang('create_role'); ?>
							</a>
						</li>

						<li>
							<a tabindex="-1" href="<?php echo base_url('access_level/'); ?>">
								<i class='glyphicon glyphicon-saved'></i> <?php echo lang('access_by_role'); ?>
							</a>
						</li>
					</ul>
				</li>

			<?php } ?>
		<?php } ?>
		
		<li class="navbar-right">
			<a href="<?php echo base_url('auth/logout'); ?>" style="color: #CC0000;" title="<?php echo lang('logout'); ?>" class="logout"><i class='glyphicon glyphicon-off'></i></a>
		</li>
		
		<?php #if(($this->session->userdata('id') == '1') OR ($this->session->userdata('id') == '2')){ ?>
			<li class="dropdown <?php if($this->session->userdata('settings')){echo 'active';}?> navbar-right">
				
				<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="<?php echo lang('settings'); ?>">
					<i class='glyphicon glyphicon-cog'></i>
				</a>
				
				<ul class="dropdown-menu">

					<li class="dropdown-header"><?php echo lang('general_settings'); ?></li>

					<li><a tabindex="-1" href="<?php echo base_url('access_level/'); ?>"> <?php echo lang('access_settings'); ?></a></li>

					<li><a tabindex="-1" href="<?php echo base_url('user/edit'); ?>"> <?php echo lang('account_settings'); ?></a></li>

					<li><a tabindex="-1" href="<?php echo base_url('company/edit'); ?>"> <?php echo lang('edit_company'); ?></a></li>

					<!--<li><a tabindex="-1" href="<?php echo base_url('company/payment'); ?>"> Payment Information <i class='glyphicon glyphicon-usd'></i></a></li>-->
					
					<li class="divider"></li>

					<li><a tabindex="-1" href="<?php echo base_url('dashboard/about'); ?>"> <?php echo lang('about'); ?></a></li>

					<!--<li><a tabindex="-1" href="<?php echo base_url('help/'); ?>"><i class='glyphicon glyphicon-info-sign'></i> Help </a></li>
					<?php if(($this->session->userdata('id') == '1') OR ($this->session->userdata('id') == '2')){ ?>
						<li><a tabindex="-1" href="<?php echo base_url('help/create'); ?>"><i class='glyphicon glyphicon-edit'></i> New Help Article </a></li>
					<?php } ?>-->
				</ul>
			</li>

		<?php #} ?>
		
		<li class="dropdown navbar-right <?php if($this->session->userdata('edit_user')){echo 'active';} ?>">

			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<i class='glyphicon glyphicon-user'></i> <span class="visible-lg-in"><?php echo lang('hello'); ?><b><?php echo $this->session->userdata('short_name'); ?></b>!</span> <span class="caret"></span>
			</a>
			
			<ul class="dropdown-menu">

				<li class="disabled">
					<a tabindex="-1" href="">
						<i class='glyphicon glyphicon-user'></i> <?php echo $this->session->userdata('full_name'); ?>
					</a>
				</li>

				<li class="disabled">
					<a tabindex="-1" href="">
						<?php echo $this->session->userdata('email'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('user/view/'); ?>">
						<i class='glyphicon glyphicon-user'></i> <?php echo lang('view_profile'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('user/edit/'); ?>">
						<i class='glyphicon glyphicon-edit'></i> <?php echo lang('edit_profile'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('user/deactivate'); ?>" style="color:#CC0000;">
						<i class='glyphicon glyphicon-remove'></i> <?php echo lang('deactivate_profile'); ?>
					</a>
				</li>

				<li class="divider"></li>

				<li>
					<a tabindex="-1" id="lock_session_" href="<?php echo base_url('auth/lock'); ?>">
						<i class='glyphicon glyphicon-ban-circle'></i> <?php echo lang('lock_session'); ?>
					</a>
				</li>

				<li>
					<a tabindex="-1" href="<?php echo base_url('auth/logout'); ?>" class="logout">
						<i class='glyphicon glyphicon-off'></i> <?php echo lang('logout'); ?>
					</a>
				</li>
			</ul>
		</li>
	
	</ul>

	<?php if($this->session->userdata('confirmed') == 'no'){ ?>
		<div class="alert alert-warning" style="text-align:center;">
			<?php echo lang('confirm_account'); ?><a href="<?php echo base_url('user/send_confirmation_email/'); ?>"><?php echo lang('click_here'); ?>.</a>
		</div>
	<?php } ?>
	
	</div>

<script>	
	$(function(){
		$.contextMenu({
			selector: '#image_user_div', 
			
			callback: function(key, options) {
				
				if(key == "view"){
					var url = '<?php echo base_url("user/view/"); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "edit"){
					var url = '<?php echo base_url("user/edit/".$this->session->userdata("id")); ?>';
					if (url) {
						window.location = url;
					}
				}
				
				if(key == "logout"){
					if(validateForm()){
						var url = '<?php echo base_url("auth/logout/"); ?>';
						if (url) {
							window.location = url;
						}
					}
				}
				
			},
			
			items: {
				"view": {name: "<?php echo lang('view_profile'); ?>", icon: "paste"},
				"edit": {name: "<?php echo lang('edit_profile'); ?>", icon: "edit"},
				"sep1": "---------",
				"logout": {name: "<?php echo lang('logout'); ?>", icon: "delete"},
			}
		});
	});
	
	$(".nav").on("click", "li ul li a, #log_time_entry, #log_payment, #reports", function(){
		$("html").css("cursor", "progress");
	});
	
	$(document).on("click", "input[type='submit'], button[type='submit'], a .btn", function(){
		$("html").css("cursor", "progress");
	});

		reset = function () {
			$("toggleCSS").href = "<?php echo base_url('assets/js/alertify/themes/alertify.ptime.css'); ?>";
			alertify.set({
				labels : {
					ok     : "<?php echo lang('lock_session'); ?>",
					cancel : "<?php echo lang('cancel_session'); ?>"
				},
				delay : 5000,
				buttonReverse : false,
				buttonFocus   : "ok"
			});
		};

	$(".logout").click(function () {
		$(".logout").html("<i class='glyphicon glyphicon-off'></i> <?php echo lang('logging_out'); ?> ...");
	});

	// $("#lock_session").click(function () {
	// 	reset();
	// 	alertify.confirm("Lunch break? Not using PTime right now? It is always a good idea to lock your session. Are you sure?", function (e) {
	// 		if (e) {
	// 			var url = '<?php echo base_url('auth/lock'); ?>';
			
	// 			if (url) {
	// 				window.location = url;
	// 			}
				
	// 		} else {
	// 			alertify.info("Session not locked.");
	// 		}
	// 	});
	// 	return false;
	// });
</script>

</header>

<main class="content">
<div>