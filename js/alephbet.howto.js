jQuery(document).ready(function($) {

  //localStorage.clear(); 
  //AlephBet.options.debug = true;

  var header_experiment = new AlephBet.Experiment({
    name: 'h1 text experiment-2', 
    namespace: 'wemove-home-page-2',
    variants: {  
      'h1 text experiment-2 subscribe': {
        activate: function() {
          jQuery('#node-16 h2 a').text('Subscribe to our Newsletter');
          jQuery('#node-16 h2').attr('title', 'Click here to be rich!');
        }
      },
      'h1 text experiment-2 join us': {
        activate: function() {
          jQuery('#node-16 h2 a').text('Join us and move Europe!');
          jQuery('#node-16 h2').attr('title', 'Click here to be rich even more!!!');
        }
      }
    },
  });

  // creating a goal
  var h1_clicked_goal = new AlephBet.Goal('Clicked', {unique: false});
  
  jQuery('#node-16 h2').click(function() {
    // The chosen variant will be tied to the goal automatically
    h1_clicked_goal.complete();
  });
  
  // adding experiment to the goal
  h1_clicked_goal.add_experiment(header_experiment);

});
