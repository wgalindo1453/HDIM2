// $(document).ready(function() {
//     $('#search-button').click(function(event) {
//         event.preventDefault(); // Prevent the form from submitting

//         var query = $('#search-input').val();

//         if (query) {
//             // Convert query to lowercase and remove spaces
//             var formattedQuery = query.toLowerCase().replace(/\s+/g, '');

//             // Redirect to the formatted URL
//             window.location.href = 'http://localhost/HDIM2/recipes/' + formattedQuery;
//         } else {
//             alert('Please enter a search query.');
//         }
//     });
// });

// document.addEventListener('DOMContentLoaded', function() {
//     document.querySelector('form').addEventListener('submit', function(e) {
//         e.preventDefault(); // Prevent default form submission
//         let query = document.querySelector('input[name="query"]').value;

//         // Convert to lowercase 
//         let formattedQuery = query.toLowerCase().replace(/\s+/g, '');

//         fetch('validate.php', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded',
//             },
//             body: `query=${encodeURIComponent(query)}`
//         })
//         .then(response => response.json())
//         .then(data => {
//             if (data.valid) {
               
               
//                 window.location.href = `http://localhost/HDIM2/recipes/${encodeURIComponent(query)}`;

//             } else {
//                 alert('Invalid Query. Please try again.');
//             }
//         })
//         .catch(error => console.error('Error during fetch operation:', error));
//     });
// });


// $(document).ready(function() {
//     $('#search-button').click(function(event) {
//         var query = $('#search-input').val();
//         if (!query) {
//             event.preventDefault(); // Prevent the form from submitting if query is empty
//             alert('Please enter a search query.');
//         }
//     });
// });


// document.addEventListener('DOMContentLoaded', function() {
//     document.querySelector('form').addEventListener('submit', function(e) {
//         e.preventDefault(); // Prevent default form submission
//         let query = document.querySelector('input[name="query"]').value;

        

//         // Convert to lowercase 
//         let formattedQuery = query.toLowerCase().replace(/\s+/g, '');

//         document.querySelector('.overlay').style.display = 'block';
//         fetch('validate.php', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded',
//             },
//             body: `query=${encodeURIComponent(query)}`
//         })
//         .then(response => response.json())
//         .then(data => {
            

//             if (data.valid) {
//                 window.location.href = `http://localhost/HDIM2/recipes/${encodeURIComponent(query)}`;
//             } else {
//                 document.querySelector('.overlay').style.display = 'none';
//                 alert('Invalid Query. Please try again.');
                

//             }
//         })
//         .catch(error => {
//             // Hide overlay in case of an error
//             document.querySelector('.overlay').style.display = 'none';
//             console.error('Error during fetch operation:', error);
//         });
//     });
// });

// $(document).ready(function() {
//     $('#search-button').click(function(event) {
//         var query = $('#search-input').val();
//           //set overlay to block
//             document.querySelector('.overlay').style.display = 'block';
//         if (!query) {
//             // Hide overlay if query is empty
//             $( ".overlay" ).hide();
//             event.preventDefault(); // Prevent the form from submitting if query is empty
//             alert('Please enter a search query.');
//         }
//     });
// });
$(document).ready(function() {
    // Show overlay when the search button is clicked
    $('#search-button').click(function(event) {
        var query = $('#search-input').val();
        if (!query) {
            event.preventDefault(); // Prevent the form from submitting if the query is empty
            alert('Please enter a search query.');
        } else {
            $('.overlay').show(); // Show overlay
        }
    });

    // Attach submit event listener to the form
    $('form').submit(function(e) {
        e.preventDefault(); // Prevent default form submission
        let query = $('input[name="query"]').val();
        let formattedQuery = query.toLowerCase().replace(/\s+/g, '');

        // Show overlay
        $('.overlay').show();

        $.ajax({
            type: 'POST',
            url: 'validate.php',
            data: { query: query },
            dataType: 'json',
            success: function(data) {
                if (data.valid) {
                    window.location.href = `http://localhost/HDIM2/recipes/${encodeURIComponent(query)}`;
                } else {
                    $('.overlay').hide(); // Hide overlay
                    alert('Invalid Query. Please try again.');
                    $('.overlay').hide(); // Hide overlay
                }
            },
            error: function(error) {
                $('.overlay').hide(); // Hide overlay in case of an error
                console.error('Error during fetch operation:', error);
            }
        });
    });
});
