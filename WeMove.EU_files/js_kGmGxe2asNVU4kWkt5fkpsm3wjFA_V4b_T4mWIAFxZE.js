jQuery (function($) {
	$(window).scroll(function(){
	  if($(document).scrollTop() == 0) {
	    $("body").removeClass("scrolled");
	    return;
	  }
	  if (!$("body").hasClass("scrolled")) {
	    $("body").addClass("scrolled")
	  }
	});
});

;
