jQuery(document).ready(function($) {

  //localStorage.clear(); 
  AlephBet.options.debug = true;

  var h1_experiment = new AlephBet.Experiment({
    name: 'h1 text', 
    namespace: 'index-goal-page',
    trigger: function() {
      return window.location.href.match(/index-goal.html/);
    },
    variants: {  
      text1: {
        activate: function() {
          jQuery('h1').text('Subscribe to our Newsletter');
        }
      },
      text2: {
        activate: function() {
          jQuery('h1').text('Join us and move Europe!');
        }
      }
    },
  });

  
  var first_goal = new AlephBet.Goal('First goal');
  var second_goal = new AlephBet.Goal('Second goal');
  var third_goal = new AlephBet.Goal('Third goal');

  
  jQuery('.first').click(function() {
    first_goal.complete();
  });
  
  jQuery('.second').click(function() {
    second_goal.complete();
  });
  
  jQuery('.third').click(function() {
    third_goal.complete();
  });
  
  var goals = [first_goal, second_goal, third_goal];
  for (var i in goals) {
    goals[i].add_experiment(h1_experiment);
  }


  var messageDiv = jQuery('#message');
  messageDiv.text('Choosen text variant: ' + h1_experiment.get_stored_variant());
  console.log('Experiment.get_stored_variant() - choosen variant: ' + h1_experiment.get_stored_variant());

  
  for (var i in goals) {
    if (h1_experiment.storage().get(h1_experiment.options.namespace, h1_experiment.options.name + ":" + goals[i].name) == true) {
      console.log('Goal achieved: ' + goals[i].name + '! :-)');
      messageDiv.append('<p>Goal achieved: '+goals[i].name+'</p>');
    } else {
      console.log('Goal NOT achieved: ' + goals[i].name + ' :-(');
    }
  }

  console.log('Namespace:' + h1_experiment.options.namespace);

});