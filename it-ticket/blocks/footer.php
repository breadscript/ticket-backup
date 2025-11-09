<!--[if !IE]> -->
<script src="assets/js/jquery-2.1.4.min.js"></script>
<script type="text/javascript">
			if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
</script>
<script src="assets/js/bootstrap.min.js"></script>
<!--[if lte IE 8]>
<script src="assets/js/excanvas.min.js"></script>
<![endif]-->
<script src="assets/js/jquery-ui.custom.min.js"></script>
<script src="assets/js/jquery.ui.touch-punch.min.js"></script>
<script src="assets/js/jquery.easypiechart.min.js"></script>
<script src="assets/js/jquery.sparkline.index.min.js"></script>
<script src="assets/js/jquery.flot.min.js"></script>
<script src="assets/js/jquery.flot.pie.min.js"></script>
<script src="assets/js/jquery.flot.resize.min.js"></script>

<!-- ace scripts -->
<script src="assets/js/ace-elements.min.js"></script>
<script src="assets/js/ace.min.js"></script>

<!-- inline scripts related to this page -->
		

<div class="footer">
	<div class="footer-inner">
		<div class="footer-content">
			<span class="bigger-120">
				<span class="blue bolder">&copy; The Laguna Creamery Inc.</span>
				All rights reserved.
				Powered by IT Department 2024
			</span>

			&nbsp; &nbsp;
			<span class="action-buttons">
				

				<a href="https://www.facebook.com/watch/?v=1434541407929503">
					<i class="ace-icon fa fa-facebook-square text-primary bigger-150"></i>
				</a>

				
			</span>
		</div>
	</div>
</div>

<script type="text/javascript">
jQuery(function($) {
    // Initialize dropdowns
    $('.dropdown-toggle').dropdown();
    
    // Fix any conflicts with custom modal scripts
    $(document).off('click.dropdown-modal').on('click.dropdown-modal', '.dropdown-modal .dropdown-menu', function(e) {
        e.stopPropagation();
    });
    
    // Ensure dropdowns work on hover for desktop
    $('.dropdown-modal').hover(
        function() { 
            $('.dropdown-menu', this).stop(true, true).fadeIn('fast');
            $(this).toggleClass('open');
        },
        function() {
            $('.dropdown-menu', this).stop(true, true).fadeOut('fast');
            $(this).toggleClass('open');
        }
    );
});
</script>
