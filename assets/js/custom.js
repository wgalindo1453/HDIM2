$(document).ready(function(){
    $("#search-button").click(function(event){
        event.preventDefault(); // Prevent the default form submission
        var query = $("#search-input").val();
        $.ajax({
            url: "/HDIM/recipes/" + query,
            type: "GET",
            data: {query: query},
            success: function(data){
                $("#recipe-results").html(data);
                $("#similar-recipes").html('');
            }
        });
    });
});
