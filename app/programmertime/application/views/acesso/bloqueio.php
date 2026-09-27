<!DOCTYPE html>
<html class="lockscreen">
    <head>
        <meta charset="UTF-8">
        <title>Programmer Time</title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

        <link href="<?php echo base_url('assets/adminLTE/css/bootstrap.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/adminLTE/css/font-awesome.min.css'); ?>" rel="stylesheet" type="text/css" />
        <link href="<?php echo base_url('assets/adminLTE/css/AdminLTE.css'); ?>" rel="stylesheet" type="text/css" />

        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
    </head>

    <style>
    .lockscreen{
        background: radial-gradient(#0077bb, #fff);
    } 

    .lockscreen-link a{
        color:#fff !important; 
    }
    </style>

    <body ondragstart="return false" oncontextmenu="return false" onselectstart="return false" class="touch" data-twttr-rendered="true">

        <div class="center">            
            <div class="headline text-center" id="time_"></div>
            
            <div class="lockscreen-name"><?php echo $this->session->userdata('nome_duplo'); ?></div>
            
            <div class="lockscreen-item">
                <div class="lockscreen-image">
                    <img src="<?php echo base_url('assets/images/usuarios/'.$this->session->userdata('imagem')); ?>" alt="imagem do usuário"/>
                </div>

                <div class="lockscreen-credentials">   
                	<form name="" action="<?php echo base_url('acesso/desbloquear'); ?>" method="post">
		                <div class="input-group">
		                    <input type="password" class="form-control" placeholder="<?php echo lang('senha'); ?>" name="senha" />
		                    <div class="input-group-btn">
		                        <button class="btn btn-flat" type="submit"><i class="fa fa-arrow-right text-muted"></i></button>
		                    </div>
		                </div>
                	</form>
                </div>

            </div>

            <div class="lockscreen-link" style="margin-top: 3px; text-align: center; margin-left: 75px;">
                <a href="<?php print base_url('acesso/sair') ?>">Entrar com um usuário diferente</a>
            </div>            
        </div>

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
        <script src="<?php echo base_url('assets/adminLTE/js/bootstrap.min.js'); ?>" type="text/javascript"></script>

        <script type="text/javascript">
            $(function() {
                startTime();
                $(".center").center();
                $(window).resize(function() {
                    $(".center").center();
                });
            });

            /*  */
            function startTime()
            {
                var today = new Date();
                var h = today.getHours();
                var m = today.getMinutes();
                var s = today.getSeconds();

                // add a zero in front of numbers<10
                m = checkTime(m);
                s = checkTime(s);

                //Check for PM and AM
                // var day_or_night = (h > 11) ? "PM" : "AM";

                //Convert to 12 hours system
                // if (h > 12)
                //     h -= 12;

                //Add time to the headline and update every 500 milliseconds
                $('#time').html(h + ":" + m + ":" + s + " ");
                setTimeout(function() {
                    startTime()
                }, 500);
            }

            function checkTime(i)
            {
                if (i < 10)
                {
                    i = "0" + i;
                }
                return i;
            }

            /* CENTER ELEMENTS IN THE SCREEN */
            jQuery.fn.center = function() {
                this.css("position", "absolute");
                this.css("top", Math.max(0, (($(window).height() - $(this).outerHeight()) / 2) +
                        $(window).scrollTop()) - 30 + "px");
                this.css("left", Math.max(0, (($(window).width() - $(this).outerWidth()) / 2) +
                        $(window).scrollLeft()) + "px");
                return this;
            }
        </script>
    </body>
</html>