$(function() {
  $("#post_submit_success").hide();
  $("#post_submit_error").hide();
});

$("#post_form").submit(function(event) {
  var body = $("textarea");
  $.post("/posts/new", { body: body.val() })
    .done(function( data ) {
      if(data.status === 200) {
        $("#post_submit_success").show();
        setTimeout(function() {
          $("#post_submit_success").fadeOut();
        }, 2000);
        body.val("");
      } else {
        $("#post_submit_error").show();
        setTimeout(function() {
          $("#post_submit_error").fadeOut();
        }, 2000);
      }
    })
    .error(function( data ) {
      console.error(data);
    });
  event.preventDefault();
});

$("#comment_form").submit(function(event) {
  var body = $("#comment_body");
  console.log(body);
  $.post("/comments/new", {body: body.val(), post_id: body.attr('data-post-id') })
    .done(function(data) {
      if(data.status === 200) {
        console.log("Success");
        body.val("");
      } else {
        console.error("Error");
      }
    })
  event.preventDefault();
})

$(".upvote").on('click', function() {
  var upvote = $(this); 
  var post_id = $(this).attr('data-post-id');
  
  $.post("/upvote", { post_id: post_id })
    .done(function(data) {
      if(data.status === 200) {
        $(".score_number[data-post-id=" + post_id + "]").text(data.score);
        upvote.toggleClass('blue');
        $(".downvote[data-post-id=" + post_id + "]").removeClass('pink');
      } else {
        alert(data.message);
      }
    })
    .error(function(data) {
      console.error(data.message);
    })
})

$(".downvote").on('click', function() {
  var downvote = $(this);
  var post_id = $(this).attr('data-post-id');
  
  $.post("/downvote", { post_id: post_id })
    .done(function(data) {
      if(data.status === 200) {
        $(".score_number[data-post-id=" + post_id + "]").text(data.score);
        downvote.toggleClass('pink');
        $(".upvote[data-post-id=" + post_id + "]").removeClass('blue');
      } else {
        alert(data.message);
      }
    })
    .error(function(data) {
      console.error(data.message);
    })
})

$(".remove").on('click', function() {
  var confirmed = confirm("Are you sure you want to delete this post?");
  if(confirmed) {
    var post_id = $(this).attr('data-post-id');
  
    $.post("/remove", { post_id: post_id })
      .done(function(data) {
        if(data.status === 200) {
          window.location = "/";
        } else {
          alert(data.message);
        }
      })
      .error(function(data) {
        console.error(data.message);
      })
  }
})