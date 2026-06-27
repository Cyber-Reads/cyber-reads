document.addEventListener( 'DOMContentLoaded' , function ()  {

    
    var homeButton  = document.getElementById( 'homeButton' );
    if (homeButton) {

        homeButton.addEventListener('click', function () {
            window.location.href =  "index.html";

        });
    }

    var urlParams  =  new URLSearchParams(window.location.search);
    var bookId  = urlParams.get('id');

    var bookCover  =  document.getElementById('bookCover');
    var bookDetailsContainer  = document.getElementById('bookDetailsContainer');
    var pdfIframe  =  document.getElementById('pdfIframe');
    var downloadLink  = document.getElementById('downloadLink');
    var readingModeTitle  = document.getElementById('readingModeTitle');


    if (bookId) {
        

        var xhr = new XMLHttpRequest();

        xhr.onload = function () {
            
            if (xhr.status >= 200 && xhr.status < 300) {

                var book =  JSON.parse(xhr.responseText);

                if (book.error) {

                    console.error(book.error);
                    bookDetailsContainer.innerHTML = '<div class="flex items-center justify-center h-full"><h1 class="text-3xl text-red-500">' + book.error + '</h1></div>';
                
                } 
                
                else {
                    // Update document title
                    document.title = book.title ? book.title : 'Read Book';

                    // Update book cover 
                    bookCover.src = book.cover ? book.cover : 'img/default_cover.jpg';
                    bookCover.alt = book.title;

                    
                    var bookDetailsHTML = 

                        '<div class = " flex-grow " >' +
                            '<h1 class = " font-bold text-4xl  mb-2 gradient-text lg:text-6xl ">' + (book.title ? book.title : 'Untitled') + '</h1>' +
                            '<p class = "text-xl text-gray-600 mb-6 ">' + (book.authors ? 'by ' + book.authors : 'Unknown Author') + '</p>' +
                            '<div id = "bookCategories" class=" gap-4 mb-8 flex flex-wrap  ">' +
                                '' +
                            '</div>' +
                            '<div class = " rounded-2xl mb-8 glass-effect p-6 ">' +
                                '<div id = "bookDescription" class = "leading-relaxed  text-lg text-gray-950 ">' +
                                    (book.description ? book.description.replace(/\n/g, '<br><br>') : 'No description available.') +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div class = " gap-4 mt-auto pt-4 flex flex-col sm:flex-row ">' +
                            '<a href = "#readNow" class = " font-medium text-white bg-slate-200 btn-primary flex-1 py-4 rounded-xl text-center  hover-lift">' +
                                '<span class = "relative z-10 justify-center gap-2 flex items-center ">' +
                                    '<svg class = "w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>' +
                                    ' Read Now' +
                                '</span>' +
                            '</a>' +
                        '</div>';

                    bookDetailsContainer.innerHTML  = bookDetailsHTML;
                    
                    // Add categories 
                    var categoriesContainer = document.getElementById('bookCategories');

                    if ( book.categories ) {

                        var categoriesArray = book.categories.split(', ');

                        for (var i = 0; i < categoriesArray.length; i++) {

                            var category = categoriesArray[i];
                            categoriesContainer.innerHTML +=  '<div class="glass-effect px-4 py-2 rounded-full bg-gray-400/10"><span class="text-sm text-black">' + category + '</span></div>';
                        
                        }

                    }

                    // Update PDF readering area
                    readingModeTitle.textContent =  'Reading: ' +  book.title;
                    pdfIframe.src = book.pdf;

                    // Update download bouuton
                    downloadLink.href = book.pdf;
                    downloadLink.download = book.title.replace(/ /g,  '_') + '.pdf';

                }
            } 
            
            else {
               
                console.error('HTTP error! status: ' +  xhr.status);
                bookDetailsContainer.innerHTML  = '<div class="flex items-center justify-center h-full"><h1 class="text-3xl text-red-500">Failed to load book details.</h1></div>';
            
            }
        };

        
        xhr.onerror = function () {

            console.error ('Error fetching book details: Network error.');
            bookDetailsContainer.innerHTML =  '<div class="flex items-center justify-center h-full"><h1 class="text-3xl text-red-500">Failed to load book details.</h1></div>';
        
        };

        // Open and send requests
        xhr.open('GET', 'src/books/get_book_details.php?id=' + bookId);
        xhr.send();

    } else {

        console.error('No book ID found in URL.');
        bookDetailsContainer.innerHTML =  '<div class="flex items-center justify-center h-full"><h1 class="text-3xl text-red-500">Book ID is missing.</h1></div>';
    
    }
});